<?php
/**
 * API para verificar status da conexão WhatsApp
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/helpers/auth-helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = getAuthenticatedUser();
$resellerId = $user['id'];

try {
    if ($method !== 'GET') {
        throw new Exception('Método não permitido');
    }
    
    // Buscar APENAS sessões ativas (não desconectadas)
    $session = Database::fetch(
        "SELECT * FROM whatsapp_sessions 
         WHERE reseller_id = ? 
         AND status IN ('connecting', 'qr_code', 'connected')
         ORDER BY created_at DESC 
         LIMIT 1",
        [$resellerId]
    );
    
    if (!$session) {
        echo json_encode([
            'success' => true,
            'session' => null,
            'status' => 'disconnected'
        ]);
        exit();
    }
    
    // OTIMIZAÇÃO: Retornar status do banco IMEDIATAMENTE sem verificar Evolution API
    // A verificação com Evolution API será feita apenas quando necessário (conectar/desconectar)
    
    // Só retornar QR Code se estiver em processo de conexão
    $qrCode = null;
    if (in_array($session['status'], ['connecting', 'qr_code'])) {
        $qrCode = $session['qr_code'];
    }
    
    echo json_encode([
        'success' => true,
        'session' => [
            'id' => $session['id'],
            'instance_name' => $session['instance_name'],
            'status' => $session['status'],
            'qr_code' => $qrCode,
            'profile_name' => $session['profile_name'],
            'profile_picture' => $session['profile_picture'],
            'phone_number' => $session['phone_number'],
            'connected_at' => $session['connected_at'],
            'last_seen' => $session['last_seen']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Verificar status da instância na Evolution API
 */
function checkEvolutionInstanceStatus($apiUrl, $apiKey, $instanceName) {
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
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Resposta inválida'];
    }
    
    return [
        'success' => true,
        'status' => $responseData['instance']['state'] ?? 'close',
        'profile_name' => $responseData['instance']['profileName'] ?? null,
        'phone_number' => $responseData['instance']['owner'] ?? null,
        'data' => $responseData
    ];
}
?>