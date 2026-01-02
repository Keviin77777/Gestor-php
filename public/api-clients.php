<?php
// API para gerenciar clientes

// CORS - deve vir antes de qualquer output
require_once __DIR__ . '/../app/helpers/cors.php';

header('Content-Type: application/json');

// Iniciar sessão antes de qualquer coisa
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

// Carregar verificação de plano
require_once __DIR__ . '/../app/helpers/plan-guard.php';

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

// 🔒 VERIFICAR SE O PLANO ESTÁ ATIVO (exceto para GET que só visualiza)
if ($method !== 'GET') {
    requireActivePlan();
}

try {
    switch ($method) {
        case 'GET':
            // Buscar todos os clientes do reseller autenticado
            $clients = Database::fetchAll(
                "SELECT c.id, c.name, c.email, c.phone, c.username, c.password, c.iptv_password, 
                        c.start_date, c.renewal_date, c.status, c.value, c.notes, c.server, c.mac, 
                        c.notifications, c.screens, c.plan, c.application_id, c.created_at,
                        a.name as application_name
                 FROM clients c
                 LEFT JOIN applications a ON c.application_id = a.id
                 WHERE c.reseller_id = ?
                 ORDER BY c.created_at DESC",
                [$user['id']]
            );
            
            // Formatar dados para o frontend
            $formattedClients = array_map(function($client) {
                // Calcular status baseado na data de vencimento
                $renewalDate = new DateTime($client['renewal_date']);
                $today = new DateTime('today');
                $currentStatus = $client['status'];
                
                // Se a data de vencimento já passou e o status é 'active', mudar para 'inactive'
                if ($renewalDate < $today && $currentStatus === 'active') {
                    $currentStatus = 'inactive';
                    
                    // Atualizar no banco de dados
                    Database::update('clients', 
                        ['status' => 'inactive'], 
                        'id = :id', 
                        ['id' => $client['id']]
                    );
                }
                
                return [
                    'id' => $client['id'], // Manter como string se for VARCHAR
                    'name' => $client['name'],
                    'email' => $client['email'] ?? '',
                    'phone' => $client['phone'] ?? '',
                    'username' => $client['username'] ?? '',
                    'password' => $client['password'] ?? '',
                    'iptv_password' => $client['iptv_password'] ?? '',
                    'plan' => $client['plan'] ?? 'Personalizado',
                    'value' => (float)$client['value'],
                    'renewal_date' => $client['renewal_date'],
                    'status' => $currentStatus,
                    'notes' => $client['notes'] ?? '',
                    'server' => $client['server'] ?? 'Principal',
                    'mac' => $client['mac'] ?? '',
                    'notifications' => $client['notifications'] ?? 'sim',
                    'screens' => (int)($client['screens'] ?? 1),
                    'application_id' => $client['application_id'] ? (int)$client['application_id'] : null,
                    'application_name' => $client['application_name'] ?? 'Nenhum',
                    'created_at' => $client['created_at'] ?? null
                ];
            }, $clients);
            
            echo json_encode([
                'success' => true,
                'clients' => $formattedClients,
                'total' => count($formattedClients)
            ]);
            break;
            
        case 'POST':
            // Criar novo cliente
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            
            
            if (!$data || !is_array($data)) {
                throw new Exception('Dados inválidos recebidos');
            }
            
            if (!$data['name'] || !$data['value'] || !$data['renewal_date']) {
                throw new Exception('Campos obrigatórios: name, value, renewal_date');
            }
            
            // Log para debug da data recebida
            error_log("Data recebida do frontend: " . $data['renewal_date']);
            
            // Garantir que a data seja salva no formato correto (YYYY-MM-DD)
            // Não fazer conversão de timezone, usar a data exata que veio do frontend
            $renewalDate = $data['renewal_date'];
            
            // Gerar ID único para o cliente
            $clientId = 'client-' . uniqid();
            
            $id = Database::insert(
                "INSERT INTO clients (id, reseller_id, name, email, phone, username, iptv_password, renewal_date, status, value, notes, server, mac, notifications, screens, plan, application_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $clientId,
                    $user['id'],
                    $data['name'],
                    $data['email'] ?? '',
                    $data['phone'] ?? '',
                    $data['username'] ?? '',
                    $data['iptv_password'] ?? '',
                    $renewalDate,
                    'active',
                    $data['value'],
                    $data['notes'] ?? '',
                    $data['server'] ?? '',
                    $data['mac'] ?? '',
                    $data['notifications'] ?? 'sim',
                    $data['screens'] ?? 1,
                    $data['plan'] ?? 'Personalizado',
                    !empty($data['application_id']) ? (int)$data['application_id'] : null
                ]
            );
            
            // Verificar se precisa gerar fatura automática
            $clientData = [
                'id' => $clientId,
                'name' => $data['name'],
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'username' => $data['username'] ?? '',
                'iptv_password' => $data['iptv_password'] ?? '',
                'value' => $data['value'],
                'renewal_date' => $data['renewal_date'],
                'status' => 'active',
                'notes' => $data['notes'] ?? ''
            ];
            
            // 1. Verificar se precisa enviar lembrete de WhatsApp imediatamente (PRIMEIRO!)
            $whatsappResult = checkAndSendReminderForNewClient($clientId);
            
            // 2. Verificar se precisa gerar fatura automática (DEPOIS)
            error_log("API Clients - Verificando fatura automática para cliente: " . $clientId);
            $invoiceResult = checkAndGenerateInvoiceForClient($clientData);
            error_log("API Clients - Resultado da fatura: " . json_encode($invoiceResult));
            
            // 3. Tentar sincronizar com Sigma automaticamente
            $sigmaResult = syncClientWithSigmaAfterSave($clientData, $user['id']);
            
            $response = [
                'success' => true,
                'message' => 'Cliente criado com sucesso',
                'id' => $clientId
            ];
            
            // Adicionar informação sobre fatura se foi gerada
            if ($invoiceResult['invoice_generated']) {
                $response['invoice_generated'] = true;
                $response['invoice_id'] = $invoiceResult['invoice_id'];
                $response['message'] .= ' - Fatura gerada automaticamente';
            }
            
            // Adicionar informação sobre lembrete WhatsApp se foi enviado
            if ($whatsappResult && $whatsappResult['success']) {
                $response['whatsapp_sent'] = true;
                $response['whatsapp_template'] = $whatsappResult['template_type'];
                $response['message'] .= ' - Lembrete WhatsApp enviado';
            }
            
            // Adicionar informação sobre sincronização Sigma
            if ($sigmaResult) {
                $response['sigma_sync'] = $sigmaResult;
                if ($sigmaResult['success']) {
                    $response['message'] .= ' - Sincronizado com Sigma';
                    
                    // Se foram geradas credenciais, incluir na resposta
                    if (isset($sigmaResult['username'])) {
                        $response['sigma_username'] = $sigmaResult['username'];
                    }
                    if (isset($sigmaResult['password'])) {
                        $response['sigma_password'] = $sigmaResult['password'];
                    }
                } else {
                    $response['message'] .= ' - Erro na sincronização Sigma: ' . $sigmaResult['message'];
                }
            }
            
            // Log para debug
            error_log("Cliente criado - ID: " . $clientId . " - Sigma Result: " . json_encode($sigmaResult));
            
            echo json_encode($response);
            break;
            
        case 'PUT':
            // Atualizar cliente
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            
            // Validar ID - permitir 0 como ID válido
            if ($id === null || $id === '') {
                throw new Exception('ID do cliente é obrigatório');
            }
            
            try {
                // Log para debug da data recebida
                error_log("Data recebida do frontend (UPDATE): " . $data['renewal_date']);
                
                // Garantir que a data seja salva no formato correto (YYYY-MM-DD)
                $renewalDate = $data['renewal_date'];
                
                // Preparar dados para atualização
                $updateData = [
                    'name' => $data['name'],
                    'email' => $data['email'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'username' => $data['username'] ?? '',
                    'iptv_password' => $data['iptv_password'] ?? '',
                    'renewal_date' => $renewalDate,
                    'value' => $data['value'],
                    'notes' => $data['notes'] ?? '',
                    'server' => $data['server'] ?? '',
                    'mac' => $data['mac'] ?? '',
                    'notifications' => $data['notifications'] ?? 'sim',
                    'screens' => $data['screens'] ?? 1,
                    'plan' => $data['plan'] ?? 'Personalizado',
                    'application_id' => !empty($data['application_id']) ? (int)$data['application_id'] : null
                ];
                
                // Usar o método update da classe Database
                Database::update('clients', $updateData, 'id = :id', ['id' => $id]);
                
                // Verificar se precisa gerar fatura automática após atualização
                $clientData = [
                    'id' => $id,
                    'name' => $data['name'],
                    'email' => $data['email'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'username' => $data['username'] ?? '',
                    'iptv_password' => $data['iptv_password'] ?? '',
                    'value' => $data['value'],
                    'renewal_date' => $data['renewal_date'],
                    'status' => $data['status'] ?? 'active',
                    'notes' => $data['notes'] ?? ''
                ];
                
                // 1. Verificar se precisa enviar lembrete de WhatsApp (PRIMEIRO!)
                $whatsappResult = checkAndSendReminderForNewClient($id);
                
                // 2. Verificar se precisa gerar fatura automática após atualização (DEPOIS)
                $invoiceResult = checkAndGenerateInvoiceForClient($clientData);
                
                // NÃO sincronizar com Sigma na edição para evitar renovações indesejadas
                // A sincronização acontece apenas:
                // 1. Na criação do cliente (primeira vez)
                // 2. Quando uma fatura é marcada como paga (renovação real)
                // Isso evita que mudanças de data no gestor causem renovações no Sigma
                
                $response = [
                    'success' => true,
                    'message' => 'Cliente atualizado com sucesso'
                ];
                
                // Adicionar informação sobre lembrete se foi enviado
                if ($whatsappResult && $whatsappResult['success']) {
                    $response['whatsapp_sent'] = true;
                    $response['message'] .= ' - Lembrete WhatsApp enviado';
                }
                
                // Adicionar informação sobre fatura se foi gerada
                if ($invoiceResult['invoice_generated']) {
                    $response['invoice_generated'] = true;
                    $response['invoice_id'] = $invoiceResult['invoice_id'];
                    $response['message'] .= ' - Fatura gerada automaticamente';
                }
                
                // Log para debug
                error_log("Cliente atualizado - ID: " . $id);
                
                echo json_encode($response);
            } catch (Exception $e) {
                throw new Exception('Erro ao atualizar cliente: ' . $e->getMessage());
            }
            break;
            
        case 'DELETE':
            // Deletar cliente
            $id = $_GET['id'] ?? null;
            
            // Validar ID - permitir 0 como ID válido
            if ($id === null || $id === '') {
                throw new Exception('ID do cliente é obrigatório');
            }
            
            // Usar o método delete da classe Database
            Database::delete('clients', 'id = :id', ['id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cliente deletado com sucesso'
            ]);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}