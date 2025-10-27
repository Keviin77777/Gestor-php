<?php
// API para gerenciar clientes
header('Content-Type: application/json');

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Carregar Database
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

// Carregar automação de faturas
require_once __DIR__ . '/../app/helpers/invoice-automation.php';

$method = $_SERVER['REQUEST_METHOD'];

// Verificar autenticação
$user = Auth::user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            // Buscar todos os clientes do reseller autenticado
            $clients = Database::fetchAll(
                "SELECT id, name, email, phone, username, password, iptv_password, start_date, renewal_date, 
                        status, value, notes, server, mac, notifications, screens, plan, created_at
                 FROM clients 
                 WHERE reseller_id = ?
                 ORDER BY created_at DESC",
                [$user['id']]
            );
            
            // Formatar dados para o frontend
            $formattedClients = array_map(function($client) {
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
                    'status' => $client['status'],
                    'notes' => $client['notes'] ?? '',
                    'server' => $client['server'] ?? 'Principal',
                    'mac' => $client['mac'] ?? '',
                    'notifications' => $client['notifications'] ?? 'sim',
                    'screens' => (int)($client['screens'] ?? 1),
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
            
            // Gerar ID único para o cliente
            $clientId = 'client-' . uniqid();
            
            $id = Database::insert(
                "INSERT INTO clients (id, reseller_id, name, email, phone, username, iptv_password, renewal_date, status, value, notes, server, mac, notifications, screens, plan) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $clientId,
                    $user['id'],
                    $data['name'],
                    $data['email'] ?? '',
                    $data['phone'] ?? '',
                    $data['username'] ?? '',
                    $data['iptv_password'] ?? '',
                    $data['renewal_date'],
                    'active',
                    $data['value'],
                    $data['notes'] ?? '',
                    $data['server'] ?? '',
                    $data['mac'] ?? '',
                    $data['notifications'] ?? 'sim',
                    $data['screens'] ?? 1,
                    $data['plan'] ?? 'Personalizado'
                ]
            );
            
            // Verificar se precisa gerar fatura automática
            $clientData = [
                'id' => $clientId,
                'name' => $data['name'],
                'value' => $data['value'],
                'renewal_date' => $data['renewal_date']
            ];
            
            $invoiceResult = checkAndGenerateInvoiceForClient($clientData);
            
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
                // Preparar dados para atualização
                $updateData = [
                    'name' => $data['name'],
                    'email' => $data['email'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'username' => $data['username'] ?? '',
                    'iptv_password' => $data['iptv_password'] ?? '',
                    'renewal_date' => $data['renewal_date'],
                    'value' => $data['value'],
                    'notes' => $data['notes'] ?? '',
                    'server' => $data['server'] ?? '',
                    'mac' => $data['mac'] ?? '',
                    'notifications' => $data['notifications'] ?? 'sim',
                    'screens' => $data['screens'] ?? 1,
                    'plan' => $data['plan'] ?? 'Personalizado'
                ];
                
                // Usar o método update da classe Database
                Database::update('clients', $updateData, 'id = :id', ['id' => $id]);
                
                // Verificar se precisa gerar fatura automática após atualização
                $clientData = [
                    'id' => $id,
                    'name' => $data['name'],
                    'value' => $data['value'],
                    'renewal_date' => $data['renewal_date']
                ];
                
                $invoiceResult = checkAndGenerateInvoiceForClient($clientData);
                
                $response = [
                    'success' => true,
                    'message' => 'Cliente atualizado com sucesso'
                ];
                
                // Adicionar informação sobre fatura se foi gerada
                if ($invoiceResult['invoice_generated']) {
                    $response['invoice_generated'] = true;
                    $response['invoice_id'] = $invoiceResult['invoice_id'];
                    $response['message'] .= ' - Fatura gerada automaticamente';
                }
                
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