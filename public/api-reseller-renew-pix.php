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
require_once __DIR__ . '/../app/helpers/AsaasHelper.php';

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
    
    // IMPORTANTE: Para renovação de revendedores, SEMPRE usar método de pagamento do ADMIN
    // Buscar o primeiro admin do sistema
    $admin = Database::fetch("SELECT id FROM users WHERE is_admin = 1 OR role = 'admin' LIMIT 1");
    
    if (!$admin) {
        error_log("Nenhum admin encontrado no sistema");
        Response::json([
            'success' => false,
            'error' => 'Sistema não configurado corretamente. Entre em contato com o suporte.'
        ], 400);
        exit;
    }
    
    error_log("Admin encontrado: {$admin['id']}");
    
    // Buscar métodos de pagamento ativos do ADMIN (prioridade: EFI Bank > Asaas > Mercado Pago)
    $paymentMethods = Database::fetchAll(
        "SELECT method_name, config_value, enabled 
         FROM payment_methods 
         WHERE reseller_id = ? AND enabled = 1
         ORDER BY FIELD(method_name, 'efibank', 'asaas', 'mercadopago')",
        [$admin['id']]
    );
    
    error_log("Métodos de pagamento encontrados para admin: " . count($paymentMethods));
    
    if (empty($paymentMethods)) {
        error_log("Nenhum método de pagamento configurado para o admin");
        Response::json([
            'success' => false,
            'error' => 'Sistema de pagamento não está configurado. Entre em contato com o suporte.'
        ], 400);
        exit;
    }
    
    // Usar o primeiro método ativo (já ordenado por prioridade)
    $activeMethodRow = $paymentMethods[0];
    $providerName = $activeMethodRow['method_name'];
    $config = json_decode($activeMethodRow['config_value'], true);
    
    error_log("Usando método de pagamento do ADMIN: $providerName");
    
    // Inicializar helper com as credenciais do ADMIN
    $paymentProvider = null;
    
    if ($providerName === 'asaas') {
        $apiKey = $config['api_key'] ?? '';
        $sandbox = $config['sandbox'] ?? false;
        
        if (empty($apiKey)) {
            error_log("API Key do Asaas não configurada");
            Response::json([
                'success' => false,
                'error' => 'Asaas não está configurado corretamente. Entre em contato com o suporte.'
            ], 400);
            exit;
        }
        
        error_log("Asaas - Sandbox: " . ($sandbox ? 'SIM' : 'NÃO'));
        error_log("Asaas - API Key: " . substr($apiKey, 0, 20) . '...');
        
        $paymentProvider = new AsaasHelper($apiKey, $sandbox);
        error_log("Asaas habilitado com credenciais do ADMIN");
        
    } elseif ($providerName === 'efibank') {
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $certificate = $config['certificate'] ?? '';
        $sandbox = $config['sandbox'] ?? false;
        
        if (empty($clientId) || empty($clientSecret)) {
            error_log("Credenciais do EFI Bank não configuradas");
            Response::json([
                'success' => false,
                'error' => 'EFI Bank não está configurado corretamente. Entre em contato com o suporte.'
            ], 400);
            exit;
        }
        
        error_log("EFI Bank - Sandbox: " . ($sandbox ? 'SIM (Homologação)' : 'NÃO (Produção)'));
        error_log("EFI Bank - Client ID: " . substr($clientId, 0, 20) . '...');
        error_log("EFI Bank - Client Secret: " . substr($clientSecret, 0, 20) . '...');
        error_log("EFI Bank - Certificado: " . ($certificate ?: 'não configurado'));
        
        $paymentProvider = new EfiBankHelper($clientId, $clientSecret, $certificate, $sandbox);
        error_log("EFI Bank habilitado com credenciais do ADMIN");
        
    } elseif ($providerName === 'mercadopago') {
        $publicKey = $config['public_key'] ?? '';
        $accessToken = $config['access_token'] ?? '';
        
        if (empty($publicKey) || empty($accessToken)) {
            error_log("Credenciais do Mercado Pago não configuradas");
            Response::json([
                'success' => false,
                'error' => 'Mercado Pago não está configurado corretamente. Entre em contato com o suporte.'
            ], 400);
            exit;
        }
        
        error_log("Mercado Pago - Public Key: " . substr($publicKey, 0, 20) . '...');
        error_log("Mercado Pago - Access Token: " . substr($accessToken, 0, 20) . '...');
        
        $paymentProvider = new MercadoPagoHelper($publicKey, $accessToken);
        error_log("Mercado Pago habilitado com credenciais do ADMIN");
        
    } else {
        error_log("Provedor não suportado: $providerName");
        Response::json([
            'success' => false,
            'error' => 'Provedor de pagamento não suportado. Entre em contato com o suporte.'
        ], 400);
        exit;
    }
    
    error_log("Provedor $providerName habilitado com credenciais do ADMIN, criando pagamento...");
    
    // Preparar dados do pagamento baseado no provedor
    if ($providerName === 'asaas') {
        // Asaas
        $paymentData = [
            'amount' => (float)$plan['price'],
            'description' => "Renovação - {$plan['name']} ({$plan['duration_days']} dias)",
            'customer_name' => $userData['name'] ?? $userData['email'] ?? 'Revendedor',
            'customer_email' => $userData['email'] ?? null,
            'customer_phone' => $userData['phone'] ?? null,
            'customer_doc' => $userData['cpf_cnpj'] ?? null,
            'external_reference' => "RENEW_USER_{$user['id']}_PLAN_{$planId}"
        ];
        
        error_log("Renovação Asaas - Payment Data: " . json_encode($paymentData));
        
    } elseif ($providerName === 'efibank') {
        // EFI Bank
        $docNumber = '';
        if (!empty($userData['cpf_cnpj'])) {
            $docNumber = preg_replace('/[^0-9]/', '', $userData['cpf_cnpj']);
        }
        
        error_log("Renovação EFI - Documento original: " . ($userData['cpf_cnpj'] ?? 'vazio'));
        error_log("Renovação EFI - Documento limpo: " . ($docNumber ?: 'vazio'));
        
        $paymentData = [
            'amount' => (float)$plan['price'],
            'description' => "Renovação - {$plan['name']} ({$plan['duration_days']} dias)",
            'payer_name' => $userData['name'] ?? $userData['email'] ?? 'Revendedor',
            'payer_doc_number' => $docNumber,
            'external_reference' => "RENEW_USER_{$user['id']}_PLAN_{$planId}"
        ];
        
        error_log("Renovação EFI - Payment Data: " . json_encode($paymentData));
        
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
        
        error_log("Renovação MP - Payment Data: " . json_encode($paymentData));
    }
    
    // Criar pagamento PIX
    error_log("Tentando criar PIX com provedor: $providerName");
    error_log("Sandbox mode: " . ($config['sandbox'] ?? 'não definido'));
    
    $result = $paymentProvider->createPixPayment($paymentData);
    
    if (!$result['success']) {
        $errorMsg = $result['error'] ?? 'Erro desconhecido';
        error_log("Erro ao criar PIX: $errorMsg");
        error_log("Detalhes do erro: " . json_encode($result));
        
        // Mensagem mais amigável para o usuário
        $userMessage = $errorMsg;
        
        // Se for erro de API key, dar uma mensagem mais clara
        if (strpos($errorMsg, 'API') !== false || strpos($errorMsg, 'chave') !== false || strpos($errorMsg, 'ambiente') !== false) {
            $userMessage = 'Erro de configuração do sistema de pagamento. Entre em contato com o suporte.';
        }
        
        Response::json([
            'success' => false,
            'error' => $userMessage,
            'technical_error' => $errorMsg
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
