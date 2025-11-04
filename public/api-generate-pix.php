<?php
/**
 * API para gerar pagamento PIX via Mercado Pago
 * Endpoint para criar QR Code PIX para faturas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Response.php';
require_once __DIR__ . '/../app/helpers/MercadoPagoHelper.php';

// Verificar autenticação
$user = Auth::user();

if (!$user) {
    Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['success' => false, 'error' => 'Método não permitido'], 405);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar dados
    $invoiceId = $input['invoice_id'] ?? null;
    
    if (!$invoiceId) {
        Response::json(['success' => false, 'error' => 'ID da fatura é obrigatório'], 400);
        exit;
    }
    
    // Buscar dados da fatura
    $db = Database::connect();
    $stmt = $db->prepare("
        SELECT 
            i.*,
            c.name as client_name,
            c.email as client_email,
            c.cpf_cnpj as client_doc
        FROM invoices i
        LEFT JOIN clients c ON i.client_id = c.id
        WHERE i.id = ?
    ");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        Response::json(['success' => false, 'error' => 'Fatura não encontrada'], 404);
        exit;
    }
    
    // Verificar se já está paga
    if ($invoice['status'] === 'paid') {
        Response::json(['success' => false, 'error' => 'Fatura já está paga'], 400);
        exit;
    }
    
    // Inicializar Mercado Pago
    $mp = new MercadoPagoHelper();
    
    if (!$mp->isEnabled()) {
        Response::json([
            'success' => false, 
            'error' => 'Mercado Pago não está configurado. Configure em Métodos de Pagamento.'
        ], 400);
        exit;
    }
    
    // Criar pagamento PIX
    $result = $mp->createPixPayment([
        'amount' => (float)$invoice['amount'],
        'description' => "Fatura #{$invoice['id']} - " . ($invoice['description'] ?? 'Pagamento'),
        'payer_email' => $invoice['client_email'] ?? 'cliente@email.com',
        'payer_name' => $invoice['client_name'] ?? 'Cliente',
        'payer_doc_type' => strlen($invoice['client_doc'] ?? '') > 11 ? 'CNPJ' : 'CPF',
        'payer_doc_number' => preg_replace('/[^0-9]/', '', $invoice['client_doc'] ?? '00000000000'),
        'external_reference' => "INVOICE_{$invoice['id']}",
        'notification_url' => env('APP_URL') . '/webhook-mercadopago.php'
    ]);
    
    if (!$result['success']) {
        Response::json([
            'success' => false,
            'error' => $result['error']
        ], 400);
        exit;
    }
    
    // Salvar payment_id e QR code na fatura
    $stmt = $db->prepare("
        UPDATE invoices 
        SET 
            payment_id = ?,
            payment_qr_code = ?,
            payment_method = 'pix_mercadopago',
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $result['payment_id'],
        $result['qr_code'],
        $invoiceId
    ]);
    
    // Retornar sucesso
    Response::json([
        'success' => true,
        'payment_id' => $result['payment_id'],
        'qr_code' => $result['qr_code'],
        'qr_code_base64' => $result['qr_code_base64'],
        'expiration_date' => $result['expiration_date'],
        'message' => 'PIX gerado com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao gerar PIX: " . $e->getMessage());
    Response::json([
        'success' => false,
        'error' => 'Erro ao gerar PIX: ' . $e->getMessage()
    ], 500);
}
