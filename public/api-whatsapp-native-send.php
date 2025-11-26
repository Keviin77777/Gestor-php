<?php
/**
 * API para enviar mensagens via API Nativa
 */

header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/whatsapp-native-api.php';

loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/helpers/auth-helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = getAuthenticatedUser();
$resellerId = $user['id'];

try {
    if ($method !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Dados inválidos');
    }
    
    $api = new WhatsAppNativeAPI();
    
    // Verificar se é envio em massa
    if (isset($data['messages']) && is_array($data['messages'])) {
        $result = $api->sendBulk($resellerId, $data['messages']);
    } else {
        // Envio único
        if (empty($data['phone']) || empty($data['message'])) {
            throw new Exception('Telefone e mensagem são obrigatórios');
        }
        
        $result = $api->sendMessage(
            $resellerId,
            $data['phone'],
            $data['message'],
            $data['template_id'] ?? null,
            $data['client_id'] ?? null,
            $data['invoice_id'] ?? null
        );
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
