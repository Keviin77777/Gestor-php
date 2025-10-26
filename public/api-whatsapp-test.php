<?php
/**
 * API para testar conexão com Evolution API
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
loadEnv(__DIR__ . '/../.env');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !$data['api_url']) {
        throw new Exception('URL da API é obrigatória');
    }
    
    $apiUrl = rtrim($data['api_url'], '/');
    $apiKey = $data['api_key'] ?? null;
    
    // Testar conexão com a Evolution API
    $headers = [
        'Content-Type: application/json'
    ];
    
    if ($apiKey) {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }
    
    // Fazer requisição para o endpoint de status da API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl . '/manager/fetchInstances',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Erro de conexão: ' . $error);
    }
    
    if ($httpCode === 401) {
        throw new Exception('Chave da API inválida ou não autorizada');
    }
    
    if ($httpCode !== 200) {
        throw new Exception('API retornou código HTTP: ' . $httpCode);
    }
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Resposta da API inválida');
    }
    
    // Verificar se a resposta tem a estrutura esperada da Evolution API
    if (!is_array($responseData)) {
        throw new Exception('Formato de resposta inesperado da API');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexão estabelecida com sucesso',
        'api_version' => 'Evolution API v1.7.4',
        'instances_count' => count($responseData),
        'api_url' => $apiUrl
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>