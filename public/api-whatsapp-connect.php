<?php
/**
 * API para conectar WhatsApp via Evolution API
 */

// Aumentar timeout para operações longas
set_time_limit(120); // 2 minutos
ini_set('max_execution_time', '120');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/whatsapp-helper.php';

loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/helpers/auth-helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = getAuthenticatedUser();
$resellerId = $user['id'];

// Log para debug
error_log("WhatsApp Connect - Iniciando conexão para reseller: " . $resellerId);

try {
    if ($method !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $input = file_get_contents('php://input');
    error_log("WhatsApp Connect - Input recebido: " . $input);
    
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Dados JSON inválidos: ' . json_last_error_msg());
    }
    
    if (!isset($data['instance_name']) || empty($data['instance_name'])) {
        throw new Exception('Nome da instância é obrigatório');
    }
    
    $instanceName = $data['instance_name'];
    error_log("WhatsApp Connect - Instance name: " . $instanceName);
    
    // Buscar configurações do WhatsApp
    $settings = Database::fetch(
        "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
        [$resellerId]
    );
    
    // Usar variáveis de ambiente como fallback
    $defaultApiUrl = getenv('EVOLUTION_API_URL') ?: getenv('WHATSAPP_API_URL') ?: 'http://localhost:8081';
    $defaultApiKey = getenv('EVOLUTION_API_KEY') ?: getenv('WHATSAPP_API_KEY') ?: '';
    
    if (!$settings) {
        // Criar configurações padrão se não existir
        $settingsId = 'ws-' . uniqid();
        Database::query(
            "INSERT INTO whatsapp_settings (id, reseller_id, evolution_api_url, evolution_api_key, reminder_days) 
             VALUES (?, ?, ?, ?, JSON_ARRAY(3, 7))",
            [$settingsId, $resellerId, $defaultApiUrl, $defaultApiKey]
        );
        
        $settings = Database::fetch(
            "SELECT * FROM whatsapp_settings WHERE id = ?",
            [$settingsId]
        );
        
        if (!$settings) {
            throw new Exception('Erro ao criar configurações padrão');
        }
    }
    
    $apiUrl = rtrim($settings['evolution_api_url'] ?: $defaultApiUrl, '/');
    $apiKey = $settings['evolution_api_key'] ?: $defaultApiKey;
    
    error_log("WhatsApp Connect - API URL: " . $apiUrl);
    error_log("WhatsApp Connect - API Key: " . ($apiKey ? 'Configurada' : 'Não configurada'));
    
    // Verificar se já existe uma sessão ativa
    $existingSession = Database::fetch(
        "SELECT * FROM whatsapp_sessions WHERE reseller_id = ? AND instance_name = ?",
        [$resellerId, $instanceName]
    );
    
    if ($existingSession && $existingSession['status'] === 'connected') {
        echo json_encode([
            'success' => true,
            'message' => 'WhatsApp já está conectado',
            'session' => $existingSession
        ]);
        exit();
    }
    
    // Criar ou atualizar sessão no banco
    $sessionId = $existingSession['id'] ?? 'ws-' . uniqid();
    
    if ($existingSession) {
        Database::query(
            "UPDATE whatsapp_sessions SET 
             status = 'connecting', 
             qr_code = NULL,
             provider = 'evolution',
             updated_at = CURRENT_TIMESTAMP 
             WHERE id = ?",
            [$sessionId]
        );
    } else {
        Database::query(
            "INSERT INTO whatsapp_sessions (id, reseller_id, session_name, instance_name, status, provider) 
             VALUES (?, ?, ?, ?, 'connecting', 'evolution')",
            [$sessionId, $resellerId, $instanceName, $instanceName]
        );
    }
    
    // Verificar se a Evolution API está rodando
    $apiCheck = checkEvolutionAPI($apiUrl, $apiKey);
    if (!$apiCheck['success']) {
        throw new Exception('Evolution API não está acessível: ' . $apiCheck['error']);
    }
    
    // Verificar se a instância já existe na lista
    $instanceExists = checkInstanceExists($apiUrl, $apiKey, $instanceName);
    
    // Verificar o estado real da instância (pode existir mas não aparecer na lista)
    $instanceState = checkInstanceState($apiUrl, $apiKey, $instanceName);
    
    if ($instanceState['exists']) {
        error_log("Evolution API - Instância existe (state check), verificando status...");
        
        // Se tem state válido e está conectada
        if ($instanceState['has_state'] && $instanceState['connected']) {
            error_log("Evolution API - Instância já está conectada!");
            Database::query(
                "UPDATE whatsapp_sessions SET 
                 status = 'connected',
                 profile_name = ?,
                 phone_number = ?,
                 updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [
                    $instanceState['profile_name'] ?? null,
                    $instanceState['phone_number'] ?? null,
                    $sessionId
                ]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'WhatsApp já está conectado',
                'connected' => true,
                'profile_name' => $instanceState['profile_name'],
                'phone_number' => $instanceState['phone_number']
            ]);
            exit();
        }
        
        // Se existe mas não tem state válido, está travada - deletar e recriar
        if (!$instanceState['has_state']) {
            error_log("Evolution API - Instância existe mas está travada (sem state), deletando...");
            deleteEvolutionInstance($apiUrl, $apiKey, $instanceName);
            sleep(2);
            
            // Criar nova instância
            error_log("Evolution API - Criando nova instância após deletar travada...");
            $createResult = createEvolutionInstanceAsync($apiUrl, $apiKey, $instanceName);
            
            echo json_encode([
                'success' => true,
                'message' => 'Instância recriada, aguarde o QR Code...',
                'session_id' => $sessionId,
                'qr_code' => null,
                'instance_name' => $instanceName,
                'async' => true,
                'create_status' => $createResult
            ]);
            exit();
        }
        
        // Se tem state mas não está conectada, usar existente
        error_log("Evolution API - Instância existe e está válida, usando existente...");
        echo json_encode([
            'success' => true,
            'message' => 'Conectando à instância existente, aguarde o QR Code...',
            'session_id' => $sessionId,
            'qr_code' => null,
            'instance_name' => $instanceName,
            'async' => true
        ]);
        exit();
    } else {
        error_log("Evolution API - Criando nova instância...");
        
        // Criar instância de forma assíncrona
        $createResult = createEvolutionInstanceAsync($apiUrl, $apiKey, $instanceName);
        
        // Se retornou que já existe (403), deletar e recriar
        if (isset($createResult['already_exists']) && $createResult['already_exists']) {
            error_log("Evolution API - Instância fantasma detectada, deletando...");
            deleteEvolutionInstance($apiUrl, $apiKey, $instanceName);
            sleep(2);
            
            error_log("Evolution API - Recriando instância...");
            $createResult = createEvolutionInstanceAsync($apiUrl, $apiKey, $instanceName);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Instância sendo criada, aguarde o QR Code...',
            'session_id' => $sessionId,
            'qr_code' => null,
            'instance_name' => $instanceName,
            'async' => true,
            'create_status' => $createResult
        ]);
        exit();
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Criar instância de forma assíncrona (não espera resposta completa)
 */
function createEvolutionInstanceAsync($apiUrl, $apiKey, $instanceName) {
    error_log("Evolution API - Iniciando criação assíncrona (fire and forget)");
    
    $url = rtrim($apiUrl, '/') . '/instance/create';
    $payload = json_encode([
        'instanceName' => $instanceName,
        'qrcode' => true,
        'integration' => 'WHATSAPP-BAILEYS'
    ]);
    
    $headers = [
        'Content-Type: application/json'
    ];
    
    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }
    
    // Fazer requisição com timeout curto - não esperar resposta completa
    // A Evolution API cria a instância em background mesmo se der timeout
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 5, // 5 segundos apenas - suficiente para iniciar
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    error_log("Evolution API - Create response HTTP: $httpCode");
    if ($error) {
        error_log("Evolution API - Create error (esperado se timeout): $error");
    }
    
    // Se deu timeout (HTTP 0), considerar sucesso pois a instância será criada
    if ($httpCode === 0 && strpos($error, 'timed out') !== false) {
        error_log("Evolution API - Timeout esperado, instância sendo criada em background");
        return ['success' => true, 'http_code' => 0, 'timeout' => true];
    }
    
    if ($httpCode === 201 || $httpCode === 200) {
        error_log("Evolution API - Instância criada com sucesso!");
        return ['success' => true, 'http_code' => $httpCode];
    } elseif ($httpCode === 403) {
        // Instância já existe
        $responseData = json_decode($response, true);
        if (isset($responseData['response']['message'])) {
            $messages = $responseData['response']['message'];
            if (is_array($messages)) {
                foreach ($messages as $msg) {
                    if (strpos($msg, 'already in use') !== false || strpos($msg, 'already exists') !== false) {
                        error_log("Evolution API - Instância já existe, usando existente");
                        return ['success' => true, 'http_code' => $httpCode, 'already_exists' => true];
                    }
                }
            }
        }
        error_log("Evolution API - Erro 403: " . $response);
        return ['success' => false, 'http_code' => $httpCode, 'error' => 'Forbidden'];
    } else {
        // Outros erros - mas não falhar, pois pode estar criando em background
        error_log("Evolution API - HTTP $httpCode, mas pode estar criando em background");
        return ['success' => true, 'http_code' => $httpCode, 'uncertain' => true];
    }
}

/**
 * Criar instância na Evolution API
 */
function createEvolutionInstance($apiUrl, $apiKey, $instanceName) {
    $headers = [
        'Content-Type: application/json'
    ];
    
    // A Evolution API exige uma API Key global
    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    } else {
        // Se não tiver API key configurada, tentar sem autenticação
        error_log("Evolution API - AVISO: Nenhuma API key configurada");
    }
    
    // Payload simplificado para compatibilidade
    $payload = [
        'instanceName' => $instanceName,
        'qrcode' => true
    ];
    
    $url = $apiUrl . '/instance/create';
    error_log("Evolution API - URL: " . $url);
    error_log("Evolution API - Payload: " . json_encode($payload));
    error_log("Evolution API - Headers: " . json_encode($headers));
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 60, // Aumentado para 60 segundos
        CURLOPT_CONNECTTIMEOUT => 10, // Timeout de conexão separado
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    error_log("Evolution API - Response Code: " . $httpCode);
    error_log("Evolution API - Response: " . $response);
    
    if ($error) {
        error_log("Evolution API - cURL Error: " . $error);
        return ['success' => false, 'error' => 'Erro de conexão: ' . $error];
    }
    
    if ($httpCode !== 201 && $httpCode !== 200) {
        $responseData = json_decode($response, true);
        
        // Verificar se o erro é porque a instância já existe
        if ($httpCode === 403 && isset($responseData['response']['message'])) {
            $messages = $responseData['response']['message'];
            if (is_array($messages)) {
                foreach ($messages as $msg) {
                    if (strpos($msg, 'already in use') !== false) {
                        error_log("Evolution API - Instância já existe mas não está na lista, deletando...");
                        
                        // Deletar a instância "fantasma"
                        deleteEvolutionInstance($apiUrl, $apiKey, $instanceName);
                        sleep(3); // Aguardar 3 segundos para garantir que foi deletada
                        
                        // Tentar criar novamente de forma assíncrona
                        error_log("Evolution API - Tentando criar novamente após deletar...");
                        
                        // Fazer requisição com timeout menor e não esperar QR Code
                        $headers = [
                            'Content-Type: application/json'
                        ];
                        
                        if ($apiKey) {
                            $headers[] = 'apikey: ' . $apiKey;
                        }
                        
                        $payload = [
                            'instanceName' => $instanceName,
                            'qrcode' => true
                        ];
                        
                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/create',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => json_encode($payload),
                            CURLOPT_TIMEOUT => 15, // Timeout menor
                            CURLOPT_CONNECTTIMEOUT => 5,
                            CURLOPT_HTTPHEADER => $headers,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false
                        ]);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        error_log("Evolution API - Recreate Response Code: " . $httpCode);
                        
                        // Retornar sucesso mesmo sem QR Code, será buscado via polling
                        return [
                            'success' => true,
                            'data' => ['message' => 'Instância recriada, QR Code será gerado'],
                            'qr_code' => null,
                            'instance_name' => $instanceName
                        ];
                    }
                }
            }
        }
        
        $errorMsg = $responseData['message'] ?? $response ?? 'Erro HTTP: ' . $httpCode;
        error_log("Evolution API - Final Error: " . $errorMsg);
        return ['success' => false, 'error' => 'Evolution API não está acessível (HTTP: ' . $httpCode . ')'];
    }
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Resposta inválida da API'];
    }
    
    // Extrair QR Code da resposta
    $qrCode = null;
    if (isset($responseData['qrcode'])) {
        if (isset($responseData['qrcode']['base64'])) {
            $qrCode = $responseData['qrcode']['base64'];
            error_log("Evolution API - QR Code base64 encontrado");
        } elseif (isset($responseData['qrcode']['code'])) {
            // Se vier apenas o código, tentar buscar o base64 depois
            error_log("Evolution API - Apenas código encontrado, QR Code será buscado depois");
            $qrCode = null; // Não retornar o código, apenas o base64
        }
    }
    
    error_log("Evolution API - QR Code extraído: " . ($qrCode ? 'Sim (base64)' : 'Não'));
    
    return [
        'success' => true,
        'data' => $responseData,
        'qr_code' => $qrCode,
        'instance_name' => $instanceName
    ];
}

/**
 * Verificar se a Evolution API está rodando
 */
function checkEvolutionAPI($apiUrl, $apiKey) {
    $headers = [
        'Content-Type: application/json'
    ];
    
    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }
    
    // Tentar diferentes endpoints para verificar se a API está rodando
    $endpoints = [
        '/manager/fetchInstances',
        '/instance/fetchInstances',
        '/'
    ];
    
    foreach ($endpoints as $endpoint) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => rtrim($apiUrl, '/') . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("Evolution API Check - Endpoint: $endpoint, Code: $httpCode");
        
        if (!$error && ($httpCode === 200 || $httpCode === 401)) {
            return ['success' => true, 'endpoint' => $endpoint];
        }
    }
    
    return ['success' => false, 'error' => 'API não está rodando em ' . $apiUrl];
}

/**
 * Verificar se a instância já existe
 */
function checkInstanceExists($apiUrl, $apiKey, $instanceName) {
    $headers = [
        'Content-Type: application/json'
    ];
    
    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }
    
    // Tentar ambos os endpoints
    $endpoints = [
        '/manager/fetchInstances',
        '/instance/fetchInstances'
    ];
    
    $allInstances = [];
    
    foreach ($endpoints as $endpoint) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => rtrim($apiUrl, '/') . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("checkInstanceExists - Endpoint: $endpoint, HTTP Code: $httpCode");
        
        if ($httpCode === 200) {
            $instances = json_decode($response, true);
            if (is_array($instances)) {
                error_log("checkInstanceExists - Found " . count($instances) . " instances in $endpoint");
                $allInstances = array_merge($allInstances, $instances);
            }
        }
    }
    
    error_log("checkInstanceExists - Total instances: " . count($allInstances));
    
    // Verificar se a instância existe
    foreach ($allInstances as $instance) {
        $foundName = $instance['instance']['instanceName'] ?? 'N/A';
        error_log("checkInstanceExists - Checking: " . $foundName);
        
        if ($foundName === $instanceName) {
            error_log("checkInstanceExists - MATCH! Instance exists");
            return true;
        }
    }
    
    error_log("checkInstanceExists - Instance NOT found");
    return false;
}

/**
 * Conectar a uma instância existente
 */
function connectToExistingInstance($apiUrl, $apiKey, $instanceName) {
    $headers = [
        'Content-Type: application/json'
    ];
    
    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }
    
    // Tentar conectar à instância
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/connect/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60, // Aumentado para 60 segundos
        CURLOPT_CONNECTTIMEOUT => 10, // Timeout de conexão separado
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    error_log("Evolution API - Connect Response Code: " . $httpCode);
    error_log("Evolution API - Connect Response: " . $response);
    
    if ($error) {
        return ['success' => false, 'error' => 'Erro de conexão: ' . $error];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Erro HTTP: ' . $httpCode];
    }
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Resposta inválida da API'];
    }
    
    // Extrair QR Code da resposta
    $qrCode = null;
    if (isset($responseData['qrcode'])) {
        if (isset($responseData['qrcode']['base64'])) {
            $qrCode = $responseData['qrcode']['base64'];
            error_log("Evolution API - QR Code base64 encontrado");
        }
    }
    
    return [
        'success' => true,
        'data' => $responseData,
        'qr_code' => $qrCode,
        'instance_name' => $instanceName
    ];
}

/**
 * Buscar QR Code na Evolution API
 */
function fetchQRCodeFromEvolution($apiUrl, $apiKey, $instanceName) {
    $headers = [
        'Content-Type: application/json'
    ];
    
    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }
    
    // Primeiro, verificar o status da conexão
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/connectionState/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Erro de conexão: ' . $error];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'HTTP: ' . $httpCode];
    }
    
    $statusData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Resposta inválida'];
    }
    
    // Verificar se já está conectado
    if (isset($statusData['instance']['state']) && $statusData['instance']['state'] === 'open') {
        return [
            'success' => true,
            'connected' => true,
            'profile_name' => $statusData['instance']['profileName'] ?? null,
            'phone_number' => $statusData['instance']['profilePictureUrl'] ?? null
        ];
    }
    
    // Se não está conectado, buscar QR Code
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/connect/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Erro de conexão: ' . $error];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'HTTP: ' . $httpCode];
    }
    
    $qrData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Resposta inválida'];
    }
    
    // Extrair QR Code da resposta
    $qrCode = null;
    if (isset($qrData['qrcode'])) {
        if (isset($qrData['qrcode']['base64'])) {
            $qrCode = $qrData['qrcode']['base64'];
        }
    } elseif (isset($qrData['base64'])) {
        $qrCode = $qrData['base64'];
    }
    
    error_log("fetchQRCodeFromEvolution - QR Code encontrado: " . ($qrCode ? 'Sim' : 'Não'));
    
    return [
        'success' => true,
        'connected' => false,
        'qr_code' => $qrCode
    ];
}

/**
 * Verificar estado real da instância (detecta instâncias travadas)
 */
function checkInstanceState($apiUrl, $apiKey, $instanceName)
{
    $headers = [
        'Content-Type: application/json'
    ];

    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/connectionState/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("checkInstanceState - HTTP: $httpCode, Response: " . substr($response, 0, 200));

    // Se retornou 404, a instância não existe
    if ($httpCode === 404) {
        return ['exists' => false, 'has_state' => false, 'connected' => false];
    }

    // Se retornou 200, a instância existe
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        // Verificar se tem o campo state
        $hasState = isset($data['instance']['state']);
        $isConnected = $hasState && $data['instance']['state'] === 'open';
        
        error_log("checkInstanceState - Has state: " . ($hasState ? 'YES' : 'NO') . ", Connected: " . ($isConnected ? 'YES' : 'NO'));
        
        return [
            'exists' => true,
            'has_state' => $hasState,
            'connected' => $isConnected,
            'state' => $data['instance']['state'] ?? null,
            'profile_name' => $data['instance']['profileName'] ?? null,
            'phone_number' => $data['instance']['owner'] ?? null
        ];
    }

    // Outros códigos HTTP - considerar que não existe
    return ['exists' => false, 'has_state' => false, 'connected' => false];
}

/**
 * Verificar status da instância
 */
function checkInstanceStatus($apiUrl, $apiKey, $instanceName)
{
    $headers = [
        'Content-Type: application/json'
    ];

    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/connectionState/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['instance']['state']) && $data['instance']['state'] === 'open') {
            return [
                'connected' => true,
                'profile_name' => $data['instance']['profileName'] ?? null,
                'phone_number' => $data['instance']['owner'] ?? null
            ];
        }
    }

    return ['connected' => false];
}

/**
 * Deletar instância da Evolution API
 */
function deleteEvolutionInstance($apiUrl, $apiKey, $instanceName)
{
    $headers = [
        'Content-Type: application/json'
    ];

    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }

    error_log("Evolution API - Deletando instância: " . $instanceName);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/delete/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("Evolution API - Delete Response Code: " . $httpCode);
    error_log("Evolution API - Delete Response: " . $response);

    return $httpCode === 200 || $httpCode === 204;
}

?>
