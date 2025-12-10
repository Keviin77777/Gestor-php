<?php
/**
 * API para importação de clientes
 */

header('Content-Type: application/json');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Carregar Database
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

// Carregar automação de faturas
require_once __DIR__ . '/../app/helpers/invoice-automation.php';

// Carregar automação de WhatsApp
require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';

// Carregar integração Sigma
require_once __DIR__ . '/../app/helpers/clients-sync-sigma.php';

$method = $_SERVER['REQUEST_METHOD'];

// Verificar autenticação
$user = Auth::user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    if ($method !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Ler dados do corpo da requisição
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data || !isset($data['clients']) || !is_array($data['clients'])) {
        throw new Exception('Dados inválidos');
    }

    $clients = $data['clients'];

    // Validar limite de importação
    if (count($clients) > 1000) {
        throw new Exception('Máximo de 1000 clientes por importação');
    }

    // Buscar servidores do usuário para validação
    $servers = Database::fetchAll(
        "SELECT id, name FROM servers WHERE user_id = ?",
        [$user['id']]
    );

    $serverMap = [];
    foreach ($servers as $server) {
        $serverMap[$server['name']] = $server['id'];
    }

    // Buscar aplicativos (se existir tabela)
    // Por enquanto, vamos criar um mapeamento padrão
    $applicationMap = [
        'NextApp' => 1,
        'SmartIPTV' => 2,
        'IPTV Smarters' => 3,
        'TiviMate' => 4
    ];

    $imported = 0;
    $errors = [];

    // Iniciar transação
    Database::beginTransaction();

    foreach ($clients as $client) {
        try {
            // Validar campos obrigatórios (email é opcional)
            if (empty($client['name']) || empty($client['username']) || 
                empty($client['iptv_password']) || empty($client['phone']) || 
                empty($client['renewal_date']) || empty($client['server'])) {
                
                $missingFields = [];
                if (empty($client['name'])) $missingFields[] = 'nome';
                if (empty($client['username'])) $missingFields[] = 'usuário';
                if (empty($client['iptv_password'])) $missingFields[] = 'senha';
                if (empty($client['phone'])) $missingFields[] = 'telefone';
                if (empty($client['renewal_date'])) $missingFields[] = 'vencimento';
                if (empty($client['server'])) $missingFields[] = 'servidor';
                
                $errors[] = "Cliente {$client['index']}: Campos obrigatórios faltando (" . implode(', ', $missingFields) . ")";
                continue;
            }

            // Validar servidor
            if (!isset($serverMap[$client['server']])) {
                $errors[] = "Cliente {$client['index']}: Servidor '{$client['server']}' não encontrado";
                continue;
            }

            // Formatar data de vencimento
            $renewalDate = formatDateForDB($client['renewal_date']);
            if (!$renewalDate) {
                $errors[] = "Cliente {$client['index']}: Data de vencimento inválida ('{$client['renewal_date']}')";
                continue;
            }

            // Gerar ID único para o cliente
            $clientId = 'client-' . uniqid();

            // Buscar ID do aplicativo (se fornecido)
            $applicationId = null;
            if (!empty($client['application']) && isset($applicationMap[$client['application']])) {
                $applicationId = $applicationMap[$client['application']];
            }

            // Inserir cliente (email pode ser vazio)
            Database::insert(
                "INSERT INTO clients (
                    id, reseller_id, name, email, phone, username, iptv_password, 
                    renewal_date, status, value, notes, server, mac, notifications, 
                    screens, plan, application_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $clientId,
                    $user['id'],
                    $client['name'],
                    $client['email'] ?? '',
                    $client['phone'],
                    $client['username'],
                    $client['iptv_password'],
                    $renewalDate,
                    'active',
                    $client['value'] ?? 0,
                    $client['notes'] ?? '',
                    $client['server'],
                    $client['mac'] ?? '',
                    'sim',
                    $client['screens'] ?? 1,
                    $client['plan'] ?? 'Personalizado',
                    $applicationId
                ]
            );

            // Preparar dados do cliente para automações
            $clientData = [
                'id' => $clientId,
                'name' => $client['name'],
                'email' => $client['email'],
                'phone' => $client['phone'],
                'username' => $client['username'],
                'iptv_password' => $client['iptv_password'],
                'value' => $client['value'] ?? 0,
                'renewal_date' => $renewalDate,
                'status' => 'active',
                'notes' => $client['notes'] ?? ''
            ];

            // Verificar se precisa enviar lembrete de WhatsApp
            checkAndSendReminderForNewClient($clientId);

            // Verificar se precisa gerar fatura automática
            checkAndGenerateInvoiceForClient($clientData);

            // Tentar sincronizar com Sigma
            syncClientWithSigmaAfterSave($clientData, $user['id']);

            $imported++;

        } catch (Exception $e) {
            $errors[] = "Cliente {$client['index']}: " . $e->getMessage();
            error_log("Erro ao importar cliente {$client['index']}: " . $e->getMessage());
        }
    }

    // Commit da transação
    Database::commit();

    // Preparar resposta
    $response = [
        'success' => true,
        'imported' => $imported,
        'total' => count($clients),
        'message' => "$imported cliente(s) importado(s) com sucesso"
    ];

    if (count($errors) > 0) {
        $response['errors'] = $errors;
        $response['message'] .= '. ' . count($errors) . ' erro(s) encontrado(s)';
    }

    echo json_encode($response);

} catch (Exception $e) {
    // Rollback em caso de erro
    Database::rollback();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Formatar data para o banco de dados
 */
function formatDateForDB($dateString) {
    if (empty($dateString)) {
        return null;
    }

    // Se já estiver no formato YYYY-MM-DD, retornar
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
        return $dateString;
    }

    // Formato Sigma: YYYY-MM-DD HH:MM:SS -> YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateString)) {
        return explode(' ', $dateString)[0];
    }

    // Converter DD/MM/YYYY para YYYY-MM-DD
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateString)) {
        $parts = explode('/', $dateString);
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }

    // Converter DD-MM-YYYY para YYYY-MM-DD
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateString)) {
        $parts = explode('-', $dateString);
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }

    // Tentar criar data a partir de timestamp do Excel
    if (is_numeric($dateString)) {
        // Excel usa 1900-01-01 como base
        $unixTimestamp = ($dateString - 25569) * 86400;
        return date('Y-m-d', $unixTimestamp);
    }

    return null;
}
