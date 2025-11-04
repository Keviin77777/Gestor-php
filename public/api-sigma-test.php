<?php
/**
 * API para testar conexão com Sigma
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/helpers/sigma-integration.php';

$method = $_SERVER['REQUEST_METHOD'];

// Verificar autenticação
$user = Auth::user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['panel_url']) || !isset($data['sigma_token']) || !isset($data['reseller_user'])) {
            throw new Exception('Dados obrigatórios: panel_url, sigma_token, reseller_user');
        }
        
        $result = testSigmaConnectionHelper($data['panel_url'], $data['sigma_token'], $data['reseller_user']);
        
        echo json_encode($result);
    } else {
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}