<?php
// API para histórico de pagamentos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth-helper.php';
loadEnv(__DIR__ . '/../.env');

// Carregar Database
require_once __DIR__ . '/../app/core/Database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['client_id'])) {
                // Buscar histórico de pagamentos de um cliente específico
                getClientPaymentHistory($_GET['client_id']);
            } else {
                throw new Exception('ID do cliente é obrigatório');
            }
            break;
            
        case 'POST':
            // Adicionar novo pagamento
            addPayment();
            break;
            
        case 'PUT':
            // Atualizar pagamento
            updatePayment();
            break;
            
        case 'DELETE':
            // Deletar pagamento
            deletePayment();
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

/**
 * Buscar histórico de pagamentos de um cliente
 */
function getClientPaymentHistory($clientId) {
    $user = getAuthenticatedUser();
    $resellerId = $user['id'];
    
    // Buscar faturas do cliente
    $invoices = Database::fetchAll(
        "SELECT i.*, pt.qr_code, pt.pix_code, pt.external_id, pt.paid_at,
                pm.name as payment_method_name, pm.type as payment_method_type
         FROM invoices i
         LEFT JOIN payment_transactions pt ON i.id = pt.invoice_id
         LEFT JOIN payment_methods pm ON pt.payment_method_id = pm.id
         WHERE i.client_id = ? AND i.reseller_id = ?
         ORDER BY i.created_at DESC",
        [$clientId, $resellerId]
    );
    
    // Buscar informações do cliente
    $client = Database::fetch(
        "SELECT name FROM clients WHERE id = ? AND reseller_id = ?",
        [$clientId, $resellerId]
    );
    
    if (!$client) {
        throw new Exception('Cliente não encontrado');
    }
    
    // Formatar dados para o frontend
    $formattedInvoices = array_map(function($invoice) {
        return [
            'id' => $invoice['id'],
            'value' => (float)$invoice['final_value'],
            'original_value' => (float)$invoice['value'],
            'discount' => (float)$invoice['discount'],
            'issue_date' => $invoice['issue_date'],
            'due_date' => $invoice['due_date'],
            'status' => $invoice['status'],
            'payment_date' => $invoice['payment_date'],
            'payment_method' => $invoice['payment_method_name'] ?: 'Manual',
            'payment_method_type' => $invoice['payment_method_type'] ?: 'manual',
            'transaction_id' => $invoice['transaction_id'],
            'external_id' => $invoice['external_id'],
            'qr_code' => $invoice['qr_code'],
            'pix_code' => $invoice['pix_code'],
            'created_at' => $invoice['created_at']
        ];
    }, $invoices);
    
    // Calcular estatísticas
    $stats = calculatePaymentStats($formattedInvoices);
    
    echo json_encode([
        'success' => true,
        'client' => $client,
        'invoices' => $formattedInvoices,
        'stats' => $stats,
        'total' => count($formattedInvoices)
    ]);
}

/**
 * Calcular estatísticas de pagamento
 */
function calculatePaymentStats($invoices) {
    $stats = [
        'total' => [
            'count' => count($invoices),
            'amount' => 0
        ],
        'paid' => [
            'count' => 0,
            'amount' => 0
        ],
        'pending' => [
            'count' => 0,
            'amount' => 0
        ],
        'cancelled' => [
            'count' => 0,
            'amount' => 0
        ],
        'overdue' => [
            'count' => 0,
            'amount' => 0
        ]
    ];
    
    foreach ($invoices as $invoice) {
        $value = $invoice['value'];
        $status = $invoice['status'];
        
        // Total
        $stats['total']['amount'] += $value;
        
        // Por status
        if (isset($stats[$status])) {
            $stats[$status]['count']++;
            $stats[$status]['amount'] += $value;
        }
    }
    
    return $stats;
}

/**
 * Adicionar novo pagamento
 */
function addPayment() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data['client_id'] || !$data['value'] || !$data['due_date']) {
        throw new Exception('Campos obrigatórios: client_id, value, due_date');
    }
    
    $user = getAuthenticatedUser();
    $resellerId = $user['id'];
    $invoiceId = 'inv-' . uniqid();
    
    // Verificar se o cliente existe
    $client = Database::fetch(
        "SELECT id FROM clients WHERE id = ? AND reseller_id = ?",
        [$data['client_id'], $resellerId]
    );
    
    if (!$client) {
        throw new Exception('Cliente não encontrado');
    }
    
    $finalValue = $data['value'] - ($data['discount'] ?? 0);
    
    Database::query(
        "INSERT INTO invoices (id, reseller_id, client_id, value, discount, final_value, 
                              issue_date, due_date, status, payment_method_id, payment_date, transaction_id) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $invoiceId,
            $resellerId,
            $data['client_id'],
            $data['value'],
            $data['discount'] ?? 0,
            $finalValue,
            $data['issue_date'] ?? date('Y-m-d'),
            $data['due_date'],
            $data['status'] ?? 'pending',
            $data['payment_method_id'] ?? null,
            $data['payment_date'] ?? null,
            $data['transaction_id'] ?? null
        ]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Pagamento adicionado com sucesso',
        'id' => $invoiceId
    ]);
}

/**
 * Atualizar pagamento
 */
function updatePayment() {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? null;
    $user = getAuthenticatedUser();
    $resellerId = $user['id'];
    
    if (!$id) {
        throw new Exception('ID da fatura é obrigatório');
    }
    
    $finalValue = $data['value'] - ($data['discount'] ?? 0);
    
    // Buscar informações da fatura antes da atualização
    $invoice = Database::fetch(
        "SELECT client_id, status FROM invoices WHERE id = ? AND reseller_id = ?",
        [$id, $resellerId]
    );
    
    if (!$invoice) {
        throw new Exception('Fatura não encontrada');
    }
    
    $oldStatus = $invoice['status'];
    $newStatus = $data['status'];
    $clientId = $invoice['client_id'];
    
    // Atualizar a fatura
    Database::query(
        "UPDATE invoices 
         SET value = ?, discount = ?, final_value = ?, due_date = ?, 
             status = ?, payment_date = ?, transaction_id = ?
         WHERE id = ? AND reseller_id = ?",
        [
            $data['value'],
            $data['discount'] ?? 0,
            $finalValue,
            $data['due_date'],
            $newStatus,
            $data['payment_date'] ?? null,
            $data['transaction_id'] ?? null,
            $id,
            $resellerId
        ]
    );
    
    // Se o status mudou para "paid", renovar o cliente
    if ($oldStatus !== 'paid' && $newStatus === 'paid') {
        // Buscar data de vencimento atual do cliente
        $client = Database::fetch(
            "SELECT renewal_date FROM clients WHERE id = ? AND reseller_id = ?",
            [$clientId, $resellerId]
        );
        
        if ($client) {
            // Adicionar 30 dias à data de vencimento atual
            $currentRenewalDate = $client['renewal_date'];
            $newRenewalDate = date('Y-m-d', strtotime($currentRenewalDate . ' +30 days'));
            
            // Atualizar data de renovação do cliente
            Database::query(
                "UPDATE clients SET renewal_date = ? WHERE id = ? AND reseller_id = ?",
                [$newRenewalDate, $clientId, $resellerId]
            );
            
            // Registrar log de renovação
            $logId = 'log-' . uniqid();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            
            Database::query(
                "INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, new_values, ip_address, user_agent) 
                 VALUES (?, ?, 'client_renewed', 'client', ?, ?, ?, ?)",
                [
                    $logId,
                    $resellerId,
                    $clientId,
                    json_encode([
                        'old_renewal_date' => $currentRenewalDate,
                        'new_renewal_date' => $newRenewalDate,
                        'invoice_id' => $id,
                        'reason' => 'payment_confirmed'
                    ]),
                    $ipAddress,
                    $userAgent
                ]
            );
            
            // Enviar mensagem WhatsApp de renovação
            try {
                require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';
                $whatsappResult = sendAutomaticRenewalMessage($clientId, $id);
                if ($whatsappResult['success']) {
                    error_log("WhatsApp de renovação enviado para cliente {$clientId}: {$whatsappResult['message_id']}");
                } else {
                    error_log("Erro ao enviar WhatsApp de renovação para cliente {$clientId}: {$whatsappResult['error']}");
                }
            } catch (Exception $e) {
                error_log("Erro ao enviar WhatsApp de renovação para cliente {$clientId}: " . $e->getMessage());
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Pagamento atualizado com sucesso',
        'client_renewed' => ($oldStatus !== 'paid' && $newStatus === 'paid')
    ]);
}

/**
 * Deletar pagamento
 */
function deletePayment() {
    $id = $_GET['id'] ?? null;
    $user = getAuthenticatedUser();
    $resellerId = $user['id'];
    
    if (!$id) {
        throw new Exception('ID da fatura é obrigatório');
    }
    
    Database::query(
        "DELETE FROM invoices WHERE id = ? AND reseller_id = ?",
        [$id, $resellerId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Pagamento deletado com sucesso'
    ]);
}
?>