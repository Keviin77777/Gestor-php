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
require_once __DIR__ . '/../app/helpers/AsaasHelper.php';
require_once __DIR__ . '/../app/helpers/EfiBankHelper.php';

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

    // Buscar métodos de pagamento ativos (prioridade: Asaas > Mercado Pago > EFI Bank)
    $paymentMethods = Database::fetchAll(
        "SELECT method_name, config_value, enabled 
         FROM payment_methods 
         WHERE enabled = 1
         ORDER BY FIELD(method_name, 'asaas', 'mercadopago', 'efibank')"
    );

    if (empty($paymentMethods)) {
        throw new Exception('Nenhum método de pagamento configurado. Acesse "Métodos de Pagamento" para configurar.');
    }

    // Usar o primeiro método ativo (já ordenado por prioridade)
    $activeMethodRow = $paymentMethods[0];
    $provider = $activeMethodRow['method_name'];
    $config = json_decode($activeMethodRow['config_value'], true);

    // Preparar dados comuns
    $amount = floatval($invoice['final_value'] ?? $invoice['value']);
    $clientName = $invoice['client_name'];
    
    // Inicializar helper e preparar dados conforme provedor
    if ($provider === 'asaas') {
        // Asaas
        $apiKey = $config['api_key'] ?? '';
        $sandbox = $config['sandbox'] ?? false;
        
        if (empty($apiKey)) {
            throw new Exception('API Key do Asaas não configurada');
        }
        
        $asaasHelper = new AsaasHelper($apiKey, $sandbox);
        
        // Asaas exige CPF/CNPJ para PIX
        // Como não coletamos CPF dos clientes por segurança, usar CPF genérico válido
        $customerDoc = '24971563792';
        
        $paymentData = [
            'amount' => $amount,
            'description' => "Fatura #{$invoice['id']} - {$clientName}",
            'customer_name' => $clientName,
            'customer_email' => $invoice['client_email'] ?: null,
            'customer_phone' => $invoice['client_phone'] ?: null,
            'customer_doc' => $customerDoc,
            'external_reference' => "INVOICE_{$invoice['id']}"
        ];
        
        $pixResult = $asaasHelper->createPixPayment($paymentData);
        $paymentMethod = 'pix_asaas';
        
    } elseif ($provider === 'mercadopago') {
        // Mercado Pago
        $publicKey = $config['public_key'] ?? '';
        $accessToken = $config['access_token'] ?? '';
        
        if (empty($publicKey) || empty($accessToken)) {
            throw new Exception('Credenciais do Mercado Pago não configuradas');
        }
        
        $mpHelper = new MercadoPagoHelper($publicKey, $accessToken);
        
        $nameParts = explode(' ', $clientName);
        $paymentData = [
            'amount' => $amount,
            'description' => "Fatura #{$invoice['id']} - {$clientName}",
            'payer_email' => $invoice['client_email'] ?: 'cliente@email.com',
            'payer_name' => $nameParts[0],
            'external_reference' => "INVOICE_{$invoice['id']}_CLIENT_{$invoice['client_id']}",
            'notification_url' => env('APP_URL') . '/webhook-mercadopago.php'
        ];
        
        $pixResult = $mpHelper->createPixPayment($paymentData);
        $paymentMethod = 'pix_mercadopago';
        
    } elseif ($provider === 'efibank') {
        // EFI Bank
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $certificate = $config['certificate'] ?? '';
        $sandbox = $config['sandbox'] ?? false;
        
        if (empty($clientId) || empty($clientSecret)) {
            throw new Exception('Credenciais do EFI Bank não configuradas');
        }
        
        $efiHelper = new EfiBankHelper($clientId, $clientSecret, $certificate, $sandbox);
        
        $paymentData = [
            'amount' => $amount,
            'description' => "Fatura #{$invoice['id']} - {$clientName}",
            'payer_name' => $clientName,
            'payer_doc_number' => null, // CPF/CNPJ opcional
            'external_reference' => "INVOICE_{$invoice['id']}"
        ];
        
        $pixResult = $efiHelper->createPixPayment($paymentData);
        $paymentMethod = 'pix_efibank';
        
    } else {
        throw new Exception('Provedor de pagamento não suportado: ' . $provider);
    }

    // Verificar resultado
    if (!$pixResult['success']) {
        $errorMsg = $pixResult['error'] ?? 'Erro ao gerar PIX';
        
        // Mensagem mais clara para erro de conta não aprovada no Asaas
        if (strpos($errorMsg, 'conta precisa estar aprovada') !== false) {
            $errorMsg = "Asaas: Sua conta precisa estar aprovada para usar PIX.\n\n";
            if ($sandbox) {
                $errorMsg .= "No modo SANDBOX:\n";
                $errorMsg .= "1. Acesse: https://sandbox.asaas.com\n";
                $errorMsg .= "2. Complete o cadastro da conta\n";
                $errorMsg .= "3. Aguarde aprovação (pode levar alguns minutos)\n\n";
                $errorMsg .= "Ou use o Mercado Pago que já está configurado.";
            } else {
                $errorMsg .= "No modo PRODUÇÃO:\n";
                $errorMsg .= "1. Acesse: https://www.asaas.com\n";
                $errorMsg .= "2. Complete o cadastro e envie documentos\n";
                $errorMsg .= "3. Aguarde aprovação da conta";
            }
        }
        
        throw new Exception($errorMsg);
    }

    if (!$pixResult['success']) {
        throw new Exception($pixResult['error'] ?? 'Erro ao gerar PIX');
    }

    // Salvar pagamento no banco
    Database::query(
        "INSERT INTO invoice_payments 
         (invoice_id, payment_id, payment_method, amount, status, qr_code, qr_code_base64, payment_provider, created_at)
         VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, NOW())",
        [
            $invoice['id'],
            $pixResult['payment_id'],
            $paymentMethod,
            $amount,
            $pixResult['qr_code'],
            $pixResult['qr_code_base64'],
            $provider
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
