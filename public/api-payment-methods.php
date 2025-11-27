<?php
/**
 * API de Métodos de Pagamento
 * Gerencia configurações de pagamento (Mercado Pago, PIX, etc)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Response.php';
require_once __DIR__ . '/../app/helpers/EfiBankHelper.php';
require_once __DIR__ . '/../app/helpers/AsaasHelper.php';

// Verificar autenticação
$user = Auth::user();

if (!$user) {
    Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
    exit;
}

// Verificar se é admin
$isAdmin = false;
if (isset($user['role']) && strtolower(trim($user['role'])) === 'admin') {
    $isAdmin = true;
} elseif (isset($user['is_admin']) && ($user['is_admin'] === true || $user['is_admin'] === 1 || $user['is_admin'] === '1')) {
    $isAdmin = true;
}

// Se não for admin, verificar no banco
if (!$isAdmin) {
    $userFromDB = Database::fetch(
        "SELECT role, is_admin FROM users WHERE id = ? OR email = ?",
        [$user['id'] ?? '', $user['email'] ?? '']
    );
    
    if ($userFromDB) {
        $role = strtolower(trim($userFromDB['role'] ?? ''));
        if ($role === 'admin') {
            $isAdmin = true;
        } elseif (isset($userFromDB['is_admin'])) {
            $isAdminValue = $userFromDB['is_admin'];
            if ($isAdminValue === 1 || $isAdminValue === true || $isAdminValue === '1' || $isAdminValue === 1.0) {
                $isAdmin = true;
            }
        }
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::connect();

try {
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
            
        case 'POST':
            handlePost($db);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
} catch (Exception $e) {
    error_log("API Payment Methods error: " . $e->getMessage());
    Response::json([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ], 500);
}

/**
 * GET - Obter configurações de um método de pagamento
 */
function handleGet($db) {
    global $isAdmin;
    
    $paymentMethod = $_GET['method'] ?? null;
    
    if (!$paymentMethod) {
        Response::json(['success' => false, 'error' => 'Método de pagamento não especificado'], 400);
        return;
    }
    
    // EFI Bank apenas para admin
    if ($paymentMethod === 'efibank' && !$isAdmin) {
        Response::json(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem acessar EFI Bank.'], 403);
        return;
    }
    
    // Buscar configuração no banco
    $stmt = $db->prepare("
        SELECT config_value, enabled, updated_at 
        FROM payment_methods 
        WHERE method_name = ?
    ");
    $stmt->execute([$paymentMethod]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $config = json_decode($result['config_value'], true);
        $config['enabled'] = (bool)$result['enabled'];
        $config['updated_at'] = $result['updated_at'];
        
        Response::json([
            'success' => true,
            'config' => $config
        ]);
    } else {
        Response::json([
            'success' => true,
            'config' => [
                'public_key' => '',
                'access_token' => '',
                'enabled' => false
            ]
        ]);
    }
}

/**
 * POST - Salvar ou testar configurações
 */
function handlePost($db) {
    global $isAdmin;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? 'save';
    
    if ($action === 'test') {
        handleTestConnection($input);
        return;
    }
    
    // Salvar configuração
    $paymentMethod = $input['method'] ?? null;
    $config = $input['config'] ?? null;
    
    if (!$paymentMethod || !$config) {
        Response::json(['success' => false, 'error' => 'Dados incompletos'], 400);
        return;
    }
    
    // EFI Bank apenas para admin
    if ($paymentMethod === 'efibank' && !$isAdmin) {
        Response::json(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem configurar EFI Bank.'], 403);
        return;
    }
    
    // Validar campos obrigatórios
    if ($paymentMethod === 'mercadopago') {
        if (empty($config['public_key']) || empty($config['access_token'])) {
            Response::json(['success' => false, 'error' => 'Public Key e Access Token são obrigatórios'], 400);
            return;
        }
    } elseif ($paymentMethod === 'efibank') {
        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['pix_key'])) {
            Response::json(['success' => false, 'error' => 'Client ID, Client Secret e Chave PIX são obrigatórios'], 400);
            return;
        }
    } elseif ($paymentMethod === 'asaas') {
        if (empty($config['api_key'])) {
            Response::json(['success' => false, 'error' => 'API Key é obrigatória'], 400);
            return;
        }
        // Sempre usar produção (não sandbox)
        $config['sandbox'] = false;
    }
    
    // Verificar se já existe
    $stmt = $db->prepare("SELECT id FROM payment_methods WHERE method_name = ?");
    $stmt->execute([$paymentMethod]);
    $exists = $stmt->fetch();
    
    // Preparar configuração baseada no método
    if ($paymentMethod === 'mercadopago') {
        $configJson = json_encode([
            'public_key' => $config['public_key'],
            'access_token' => $config['access_token']
        ]);
    } elseif ($paymentMethod === 'efibank') {
        $configJson = json_encode([
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'pix_key' => $config['pix_key'],
            'certificate' => $config['certificate'] ?? '',
            'sandbox' => $config['sandbox'] ?? false
        ]);
    } elseif ($paymentMethod === 'asaas') {
        $configJson = json_encode([
            'api_key' => $config['api_key'],
            'sandbox' => false // Sempre produção
        ]);
    } else {
        Response::json(['success' => false, 'error' => 'Método de pagamento não suportado'], 400);
        return;
    }
    
    if ($exists) {
        // Atualizar
        $stmt = $db->prepare("
            UPDATE payment_methods 
            SET config_value = ?, enabled = ?, updated_at = NOW()
            WHERE method_name = ?
        ");
        $stmt->execute([
            $configJson,
            $config['enabled'] ? 1 : 0,
            $paymentMethod
        ]);
    } else {
        // Inserir
        $stmt = $db->prepare("
            INSERT INTO payment_methods (method_name, config_value, enabled, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $paymentMethod,
            $configJson,
            $config['enabled'] ? 1 : 0
        ]);
    }
    
    Response::json([
        'success' => true,
        'message' => 'Configuração salva com sucesso'
    ]);
}

/**
 * Testar conexão com provedor de pagamento
 */
function handleTestConnection($input) {
    global $isAdmin;
    
    $paymentMethod = $input['method'] ?? null;
    
    // EFI Bank apenas para admin
    if ($paymentMethod === 'efibank' && !$isAdmin) {
        Response::json(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem testar EFI Bank.'], 403);
        return;
    }
    
    if ($paymentMethod === 'mercadopago') {
        $publicKey = $input['public_key'] ?? null;
        $accessToken = $input['access_token'] ?? null;
        
        if (!$publicKey || !$accessToken) {
            Response::json(['success' => false, 'error' => 'Public Key e Access Token são obrigatórios'], 400);
            return;
        }
        
        // Testar conexão com Mercado Pago
        $result = testMercadoPagoConnection($accessToken);
        
        if ($result['success']) {
            Response::json([
                'success' => true,
                'message' => 'Conexão testada com sucesso',
                'account_info' => $result['account_info']
            ]);
        } else {
            Response::json([
                'success' => false,
                'error' => $result['error']
            ], 400);
        }
    } elseif ($paymentMethod === 'asaas') {
        $apiKey = $input['api_key'] ?? null;
        
        if (!$apiKey) {
            Response::json(['success' => false, 'error' => 'API Key é obrigatória'], 400);
            return;
        }
        
        // Testar conexão com Asaas (sempre produção)
        $result = testAsaasConnection($apiKey, false);
        
        if ($result['success']) {
            Response::json([
                'success' => true,
                'message' => 'Conexão testada com sucesso',
                'account_info' => $result['account_info']
            ]);
        } else {
            Response::json([
                'success' => false,
                'error' => $result['error']
            ], 400);
        }
    } elseif ($paymentMethod === 'efibank') {
        $clientId = $input['client_id'] ?? null;
        $clientSecret = $input['client_secret'] ?? null;
        $pixKey = $input['pix_key'] ?? null;
        $certificate = $input['certificate'] ?? '';
        $sandbox = $input['sandbox'] ?? false;
        
        if (!$clientId || !$clientSecret || !$pixKey) {
            Response::json(['success' => false, 'error' => 'Client ID, Client Secret e Chave PIX são obrigatórios'], 400);
            return;
        }
        
        // Testar conexão com EFI Bank
        $result = testEfiBankConnection($clientId, $clientSecret, $certificate, $sandbox);
        
        if ($result['success']) {
            Response::json([
                'success' => true,
                'message' => 'Conexão testada com sucesso',
                'account_info' => $result['account_info']
            ]);
        } else {
            Response::json([
                'success' => false,
                'error' => $result['error']
            ], 400);
        }
    } else {
        Response::json(['success' => false, 'error' => 'Método não suportado'], 400);
    }
}

/**
 * Testar conexão real com API do Mercado Pago
 */
function testMercadoPagoConnection($accessToken) {
    try {
        // Testar criando um pagamento de teste (sem finalizar)
        // Este é o método mais confiável para validar credenciais
        $url = 'https://api.mercadopago.com/v1/payments';
        
        // Log para debug
        error_log("Testando Mercado Pago - Token: " . substr($accessToken, 0, 20) . "...");
        
        // Payload mínimo para testar
        $testPayload = [
            'transaction_amount' => 0.01,
            'description' => 'Test - Validação de credenciais',
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => 'test@test.com'
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'X-Idempotency-Key: test_' . uniqid()
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        // Desabilitar verificação SSL em desenvolvimento
        if (env('APP_ENV') === 'development') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log da resposta
        error_log("MP Test - HTTP Code: $httpCode");
        error_log("MP Test - Response: " . substr($response, 0, 300));
        
        if ($error) {
            error_log("MP Error: $error");
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $error
            ];
        }
        
        $data = json_decode($response, true);
        
        // Códigos de sucesso: 201 (criado) ou 400/422 com erro específico (mas token válido)
        if ($httpCode === 201 || $httpCode === 200) {
            // Token válido e pagamento criado - extrair informações da conta
            $collectorId = $data['collector_id'] ?? null;
            $currency = $data['currency_id'] ?? 'BRL';
            $paymentId = $data['id'] ?? 'N/A';
            
            // Buscar informações adicionais se disponíveis
            $accountInfo = [
                'status' => '✅ Credenciais válidas',
                'collector_id' => $collectorId,
                'currency' => $currency,
                'test_payment_id' => $paymentId,
                'message' => 'Mercado Pago configurado e pronto para uso'
            ];
            
            // Se tiver payer info, adicionar
            if (isset($data['payer']['email'])) {
                $accountInfo['test_email'] = $data['payer']['email'];
            }
            
            return [
                'success' => true,
                'account_info' => $accountInfo
            ];
        } elseif ($httpCode === 401 || $httpCode === 403) {
            // Token inválido
            $errorMsg = $data['message'] ?? 'Token inválido ou sem permissões';
            return [
                'success' => false,
                'error' => "Credenciais inválidas: $errorMsg",
                'details' => $data
            ];
        } elseif ($httpCode === 400 || $httpCode === 422) {
            // Token válido, mas dados do pagamento inválidos (esperado no teste)
            // Isso significa que o token está funcionando!
            return [
                'success' => true,
                'account_info' => [
                    'status' => 'Credenciais válidas',
                    'message' => 'Token autenticado com sucesso',
                    'note' => 'Pronto para criar pagamentos reais'
                ]
            ];
        } else {
            // Outro erro
            $errorMsg = $data['message'] ?? 'Erro desconhecido';
            return [
                'success' => false,
                'error' => "Erro ao validar: $errorMsg (HTTP $httpCode)",
                'details' => $data
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erro ao testar conexão: ' . $e->getMessage()
        ];
    }
}


/**
 * Testar conexão real com API do Asaas
 */
function testAsaasConnection($apiKey, $sandbox = false) {
    try {
        // Log para debug
        error_log("Testando Asaas - API Key: " . substr($apiKey, 0, 20) . "...");
        
        $asaas = new AsaasHelper($apiKey, $sandbox);
        $result = $asaas->testConnection();
        
        if ($result['success']) {
            return [
                'success' => true,
                'account_info' => [
                    'status' => '✅ Credenciais válidas',
                    'environment' => $result['environment'],
                    'message' => 'Asaas configurado e pronto para uso'
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['error']
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erro ao testar conexão: ' . $e->getMessage()
        ];
    }
}

/**
 * Testar conexão real com API do EFI Bank
 */
function testEfiBankConnection($clientId, $clientSecret, $certificate = '', $sandbox = false) {
    try {
        // Log para debug
        error_log("Testando EFI Bank - Client ID: " . substr($clientId, 0, 20) . "...");
        
        // URL da API
        $url = $sandbox ? 
            'https://api-pix-h.gerencianet.com.br/oauth/token' : 
            'https://api-pix.gerencianet.com.br/oauth/token';
        
        $auth = base64_encode($clientId . ':' . $clientSecret);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['grant_type' => 'client_credentials']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        
        // Configurações SSL
        $isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                       strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
        
        if ($isLocalhost || env('APP_ENV') === 'development') {
            // Em desenvolvimento, desabilitar verificação SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            // Em produção, usar certificado se fornecido
            if (!empty($certificate) && file_exists($certificate)) {
                curl_setopt($ch, CURLOPT_SSLCERT, $certificate);
            }
        }
        
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log da resposta
        error_log("EFI Test - HTTP Code: $httpCode");
        error_log("EFI Test - Response: " . substr($response, 0, 300));
        
        if ($error) {
            error_log("EFI Error: $error");
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $error
            ];
        }
        
        $data = json_decode($response, true);
        
        // Códigos de sucesso: 200 (token obtido)
        if ($httpCode === 200 && isset($data['access_token'])) {
            // Token válido - extrair informações
            $accountInfo = [
                'status' => '✅ Credenciais válidas',
                'token_type' => $data['token_type'] ?? 'Bearer',
                'expires_in' => $data['expires_in'] ?? 3600,
                'environment' => $sandbox ? 'Homologação (Sandbox)' : 'Produção',
                'message' => 'EFI Bank configurado e pronto para uso'
            ];
            
            return [
                'success' => true,
                'account_info' => $accountInfo
            ];
        } elseif ($httpCode === 401 || $httpCode === 403) {
            // Credenciais inválidas
            $errorMsg = $data['error_description'] ?? $data['mensagem'] ?? 'Credenciais inválidas';
            return [
                'success' => false,
                'error' => "Credenciais inválidas: $errorMsg",
                'details' => $data
            ];
        } else {
            // Outro erro
            $errorMsg = $data['error_description'] ?? $data['mensagem'] ?? 'Erro desconhecido';
            return [
                'success' => false,
                'error' => "Erro ao validar: $errorMsg (HTTP $httpCode)",
                'details' => $data
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erro ao testar conexão: ' . $e->getMessage()
        ];
    }
}
