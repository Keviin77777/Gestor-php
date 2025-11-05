<?php
/**
 * API - Gerar PIX para Fatura
 * Gera pagamento PIX via Mercado Pago para uma fatura específica
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/MercadoPagoHelper.php';

try {
    // Validar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Obter dados da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    $invoiceId = $input['invoice_id'] ?? null;

    if (!$invoiceId) {
        throw new Exception('ID da fatura não fornecido');
    }

    // Buscar fatura
    $invoice = Database::fetch(
        "SELECT i.*, c.name as client_name, c.email as client_email, c.phone as client_phone,
                c.reseller_id
         FROM invoices i
         JOIN clients c ON i.client_id = c.id
         WHERE i.id = ?",
        [$invoiceId]
    );

    if (!$invoice) {
        throw new Exception('Fatura não encontrada');
    }

    // Verificar se fatura já está paga
    if ($invoice['status'] === 'paid') {
        throw new Exception('Esta fatura já foi paga');
    }

    // Buscar configuração do Mercado Pago do revendedor
    $mpConfig = Database::fetch(
        "SELECT * FROM payment_methods 
         WHERE reseller_id = ? AND provider = 'mercadopago' AND is_active = 1
         LIMIT 1",
        [$invoice['reseller_id']]
    );

    if (!$mpConfig) {
        throw new Exception('Mercado Pago não configurado. Acesse "Métodos de Pagamento" para configurar suas credenciais.');
    }

    // Inicializar Mercado Pago Helper
    $mpHelper = new MercadoPagoHelper(
        $mpConfig['public_key'],
        $mpConfig['access_token']
    );

    // Preparar dados do pagamento
    $amount = floatval($invoice['final_value'] ?? $invoice['value']);
    $clientName = $invoice['client_name'];
    $nameParts = explode(' ', $clientName);
    
    $paymentData = [
        'amount' => $amount,
        'description' => "Fatura #{$invoice['id']} - {$clientName}",
        'payer_email' => $invoice['client_email'] ?: 'cliente@email.com',
        'payer_name' => $nameParts[0],
        'external_reference' => "INVOICE_{$invoice['id']}_CLIENT_{$invoice['client_id']}",
        'notification_url' => env('APP_URL') . '/webhook-mercadopago.php'
    ];

    // Gerar pagamento PIX
    $pixResult = $mpHelper->createPixPayment($paymentData);

    if (!$pixResult['success']) {
        throw new Exception($pixResult['error'] ?? 'Erro ao gerar PIX');
    }

    // Salvar pagamento no banco
    Database::query(
        "INSERT INTO invoice_payments 
         (invoice_id, payment_id, payment_method, amount, status, qr_code, qr_code_base64, created_at)
         VALUES (?, ?, 'pix', ?, 'pending', ?, ?, NOW())",
        [
            $invoice['id'],
            $pixResult['payment_id'],
            $amount, // Usar a variável $amount que já temos
            $pixResult['qr_code'],
            $pixResult['qr_code_base64']
        ]
    );

    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'payment_id' => $pixResult['payment_id'],
        'qr_code' => $pixResult['qr_code'],
        'qr_code_base64' => $pixResult['qr_code_base64'],
        'amount' => $amount, // Usar a variável $amount que já temos
        'invoice' => [
            'id' => $invoice['id'],
            'client_name' => $invoice['client_name'],
            'value' => $invoice['value'],
            'due_date' => $invoice['due_date']
        ]
    ]);

} catch (Exception $e) {
    error_log("Erro em api-invoice-generate-pix.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
