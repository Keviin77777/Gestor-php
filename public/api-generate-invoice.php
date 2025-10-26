<?php
// API para gerar faturas
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Carregar Database
require_once __DIR__ . '/../app/core/Database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        generateInvoice();
    } else {
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
 * Gerar nova fatura para um cliente
 */
function generateInvoice() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data['client_id']) {
        throw new Exception('ID do cliente é obrigatório');
    }
    
    $resellerId = 'admin-001'; // Por enquanto fixo, depois pegar do token
    $clientId = $data['client_id'];
    
    // Buscar informações do cliente
    $client = Database::fetch(
        "SELECT * FROM clients WHERE id = ? AND reseller_id = ?",
        [$clientId, $resellerId]
    );
    
    if (!$client) {
        throw new Exception('Cliente não encontrado');
    }
    
    // Verificar se já existe uma fatura pendente para este cliente
    $existingInvoice = Database::fetch(
        "SELECT id, issue_date FROM invoices 
         WHERE client_id = ? AND reseller_id = ? AND status IN ('pending', 'overdue')
         ORDER BY created_at DESC LIMIT 1",
        [$clientId, $resellerId]
    );
    
    if ($existingInvoice) {
        $issueDate = date('d/m/Y', strtotime($existingInvoice['issue_date']));
        throw new Exception("Cliente já possui uma fatura pendente criada em {$issueDate}. Finalize a fatura anterior antes de gerar uma nova.");
    }
    
    // Verificar se já foi gerada uma fatura para o período de vencimento do cliente
    $clientRenewalDate = $client['renewal_date'];
    $renewalMonth = date('Y-m', strtotime($clientRenewalDate));
    
    $monthlyInvoice = Database::fetch(
        "SELECT id, issue_date, due_date FROM invoices 
         WHERE client_id = ? AND reseller_id = ? 
         AND DATE_FORMAT(due_date, '%Y-%m') = ?
         ORDER BY created_at DESC LIMIT 1",
        [$clientId, $resellerId, $renewalMonth]
    );
    
    if ($monthlyInvoice) {
        $issueDate = date('d/m/Y', strtotime($monthlyInvoice['issue_date']));
        $dueDate = date('d/m/Y', strtotime($monthlyInvoice['due_date']));
        throw new Exception("Já foi gerada uma fatura para o período de vencimento deste cliente (vencimento em {$dueDate}, criada em {$issueDate}). Finalize a fatura existente antes de gerar uma nova.");
    }
    
    // Gerar ID único para a fatura
    $invoiceId = 'inv-' . uniqid();
    
    // Calcular datas baseadas no vencimento do cliente
    $issueDate = date('Y-m-d');
    $dueDate = $clientRenewalDate; // A fatura vence na data de renovação do cliente
    
    // Usar valor do cliente ou valor padrão
    $value = (float)($client['value'] ?? 35.00);
    $discount = 0.00;
    $finalValue = $value - $discount;
    
    // Inserir fatura no banco
    Database::query(
        "INSERT INTO invoices (id, reseller_id, client_id, value, discount, final_value, 
                              issue_date, due_date, status, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())",
        [
            $invoiceId,
            $resellerId,
            $clientId,
            $value,
            $discount,
            $finalValue,
            $issueDate,
            $dueDate
        ]
    );
    
    // NOTA: A data de renovação do cliente só será atualizada quando a fatura for paga
    // Não atualizamos aqui para evitar renovação automática apenas por gerar a fatura
    
    // Registrar log de auditoria
    $logId = 'log-' . uniqid();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    Database::query(
        "INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, new_values, ip_address, user_agent) 
         VALUES (?, ?, 'invoice_generated', 'invoice', ?, ?, ?, ?)",
        [
            $logId,
            $resellerId,
            $invoiceId,
            json_encode([
                'client_id' => $clientId,
                'client_name' => $client['name'],
                'value' => $finalValue,
                'due_date' => $dueDate
            ]),
            $ipAddress,
            $userAgent
        ]
    );
    
    // Enviar mensagem WhatsApp se configurado
    try {
        require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';
        $whatsappResult = sendAutomaticInvoiceMessage($invoiceId);
        if ($whatsappResult['success']) {
            error_log("WhatsApp enviado para fatura manual {$invoiceId}: {$whatsappResult['message_id']}");
        } else {
            error_log("Erro ao enviar WhatsApp para fatura manual {$invoiceId}: {$whatsappResult['error']}");
        }
    } catch (Exception $e) {
        error_log("Erro ao enviar WhatsApp para fatura manual {$invoiceId}: " . $e->getMessage());
    }
    
    // Buscar a fatura criada para retornar
    $invoice = Database::fetch(
        "SELECT * FROM invoices WHERE id = ?",
        [$invoiceId]
    );
    
    // Formatar dados da fatura
    $formattedInvoice = [
        'id' => $invoice['id'],
        'client_id' => $clientId,
        'client_name' => $client['name'],
        'value' => (float)$invoice['final_value'],
        'original_value' => (float)$invoice['value'],
        'discount' => (float)$invoice['discount'],
        'issue_date' => $invoice['issue_date'],
        'due_date' => $invoice['due_date'],
        'status' => $invoice['status'],
        'payment_method' => 'Manual',
        'payment_method_type' => 'manual',
        'created_at' => $invoice['created_at']
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Fatura gerada com sucesso!',
        'invoice' => $formattedInvoice
    ]);
}
?>