<?php
/**
 * API para gerar PIX de renovação para revendedores
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
require_once __DIR__ . '/../app/helpers/EfiBankHelper.php';

// Verificar autenticação
$user = Auth::user();

if (!$user) {
    Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
    exit;
}

// Apenas revendedores podem renovar
if ($user['role'] !== 'reseller') {
    Response::json(['success' => false, 'error' => 'Acesso negado'], 403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['success' => false, 'error' => 'Método não permitido'], 405);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $planId = $input['plan_id'] ?? null;
    
    // Log para debug
    error_log("Renovação PIX - User ID: {$user['id']}, Plan ID: $planId");
    
    if (!$planId) {
        Response::json(['success' => false, 'error' => 'ID do plano é obrigatório'], 400);
        exit;
    }
    
    $db = Database::connect();
    
    // Buscar dados do plano
    $stmt = $db->prepare("
        SELECT * FROM reseller_plans 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        error_log("Plano não encontrado: $planId");
        Response::json(['success' => false, 'error' => 'Plano não encontrado ou inativo'], 404);
        exit;
    }
    
    error_log("Plano encontrado: {$plan['name']}, Preço: {$plan['price']}");
    
    // Não permitir renovação com plano trial
    if ($plan['is_trial']) {
        Response::json(['success' => false, 'error' => 'Plano trial não disponível para renovação'], 400);
        exit;
    }
    
    // Buscar dados completos do usuário
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        error_log("Usuário não encontrado: {$user['id']}");
        Response::json(['success' => false, 'error' => 'Usuário não encontrado'], 404);
        exit;
    }
    
    error_log("Usuário encontrado: {$userData['email']}");
    
    // Verificar qual método de pagamento está ativo (prioridade: EFI Bank > Mercado Pago)
    $paymentProvider = null;
    $providerName = '';
    
    // Tentar EFI Bank primeiro
    $efi = new EfiBankHelper();
    if ($efi->isEnabled()) {
        $paymentProvider = $efi;
        $providerName = 'efibank';
        error_log("EFI Bank habilitado, usando como provedor");
    } else {
        // Fallback para Mercado Pago
        $mp = new MercadoPagoHelper();
        if ($mp->isEnabled()) {
            $paymentProvider = $mp;
            $providerName = 'mercadopago';
            error_log("Mercado Pago habilitado, usando como provedor");
        }
    }
    
    if (!$paymentProvider) {
        error_log("Nenhum método de pagamento está habilitado");
        Response::json([
            'success' => false,
            'error' => 'Sistema de pagamento não está configurado. Entre em contato com o suporte.'
        ], 400);
        exit;
    }
    
    error_log("Provedor $providerName habilitado, criando pagamento...");
    
    // Preparar dados do pagamento baseado no provedor
    if ($providerName === 'efibank') {
        // Limpar e validar CPF/CNPJ
        $docNumber = preg_replace('/[^0-9]/', '', $userData['cpf_cnpj'] ?? '');
        
        // Log do documento
        error_log("Documento original: " . ($userData['cpf_cnpj'] ?? 'vazio'));
        error_log("Documento limpo: " . $docNumber);
        error_log("Tamanho do documento: " . strlen($docNumber));
        
        $paymentData = [
            'amount' => (float)$plan['price'],
            'description' => "Renovação - {$plan['name']} ({$plan['duration_days']} dias)",
            'payer_name' => $userData['name'] ?? 'Revendedor',
            'payer_doc_number' => $docNumber,
            'external_reference' => "RENEW_USER_{$user['id']}_PLAN_{$planId}"
        ];
        
        error_log("Payment Data EFI: " . json_encode($paymentData));
    } else {
        // Mercado Pago
        $paymentData = [
            'amount' => (float)$plan['price'],
            'description' => "Renovação - {$plan['name']} ({$plan['duration_days']} dias)",
            'payer_email' => $userData['email'],
            'payer_name' => $userData['name'] ?? 'Revendedor',
            'payer_doc_type' => !empty($userData['cpf_cnpj']) && strlen($userData['cpf_cnpj']) > 11 ? 'CNPJ' : 'CPF',
            'payer_doc_number' => preg_replace('/[^0-9]/', '', $userData['cpf_cnpj'] ?? ''),
            'external_reference' => "RENEW_USER_{$user['id']}_PLAN_{$planId}"
        ];
        
        // Adicionar notification_url apenas se não for localhost
        $appUrl = env('APP_URL');
        if (strpos($appUrl, 'localhost') === false && strpos($appUrl, '127.0.0.1') === false) {
            $paymentData['notification_url'] = $appUrl . '/webhook-mercadopago.php';
        }
    }
    
    // Criar pagamento PIX
    $result = $paymentProvider->createPixPayment($paymentData);
    
    if (!$result['success']) {
        error_log("Erro ao criar PIX: {$result['error']}");
        Response::json([
            'success' => false,
            'error' => $result['error']
        ], 400);
        exit;
    }
    
    error_log("PIX criado com sucesso: {$result['payment_id']}");
    
    // Salvar registro de renovação pendente
    $stmt = $db->prepare("
        INSERT INTO renewal_payments (
            user_id, 
            plan_id, 
            payment_id, 
            amount, 
            status, 
            qr_code,
            payment_provider,
            created_at
        ) VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())
    ");
    $stmt->execute([
        $user['id'],
        $planId,
        $result['payment_id'],
        $plan['price'],
        $result['qr_code'],
        $providerName
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
    error_log("Erro ao gerar PIX de renovação: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    Response::json([
        'success' => false,
        'error' => 'Erro ao gerar PIX: ' . $e->getMessage(),
        'details' => $e->getTraceAsString()
    ], 400);
}
