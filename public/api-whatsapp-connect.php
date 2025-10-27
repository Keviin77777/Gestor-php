<?php
/**
 * API para conectar WhatsApp via Evolution API
 */

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
             updated_at = CURRENT_TIMESTAMP 
             WHERE id = ?",
            [$sessionId]
        );
    } else {
        Database::query(
            "INSERT INTO whatsapp_sessions (id, reseller_id, session_name, instance_name, status) 
             VALUES (?, ?, ?, ?, 'connecting')",
            [$sessionId, $resellerId, $instanceName, $instanceName]
        );
    }
    
    // Verificar se a Evolution API está rodando
    $apiCheck = checkEvolutionAPI($apiUrl, $apiKey);
    if (!$apiCheck['success']) {
        throw new Exception('Evolution API não está acessível: ' . $apiCheck['error']);
    }
    
    // Verificar se a instância já existe
    $instanceExists = checkInstanceExists($apiUrl, $apiKey, $instanceName);
    
    if ($instanceExists) {
        error_log("Evolution API - Instância já existe, conectando...");
        // Se já existe, apenas conectar
        $evolutionResponse = connectToExistingInstance($apiUrl, $apiKey, $instanceName);
    } else {
        error_log("Evolution API - Criando nova instância...");
        // Criar nova instância
        $evolutionResponse = createEvolutionInstance($apiUrl, $apiKey, $instanceName);
    }
    
    if (!$evolutionResponse['success']) {
        // Atualizar status para erro
        Database::query(
            "UPDATE whatsapp_sessions SET status = 'error' WHERE id = ?",
            [$sessionId]
        );
        
        throw new Exception($evolutionResponse['error']);
    }
    
    // Buscar QR Code se disponível
    $qrCode = null;
    if (isset($evolutionResponse['qr_code'])) {
        $qrCode = $evolutionResponse['qr_code'];
        
        // Salvar QR Code no banco
        Database::query(
            "UPDATE whatsapp_sessions SET qr_code = ? WHERE id = ?",
            [$qrCode, $sessionId]
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexão iniciada com sucesso',
        'session_id' => $sessionId,
        'qr_code' => $qrCode,
        'instance_name' => $instanceName
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
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
        CURLOPT_TIMEOUT => 30,
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
                        error_log("Evolution API - Instância já existe, tentando conectar...");
                        // Retornar sucesso mas sem QR Code, será buscado depois
                        return [
                            'success' => true,
                            'data' => ['message' => 'Instância já existe'],
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
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/fetchInstances',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $instances = json_decode($response, true);
        if (is_array($instances)) {
            foreach ($instances as $instance) {
                if (isset($instance['instance']['instanceName']) && 
                    $instance['instance']['instanceName'] === $instanceName) {
                    return true;
                }
            }
        }
    }
    
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
        CURLOPT_TIMEOUT => 30,
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
?>