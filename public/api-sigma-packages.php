<?php
/**
 * API para buscar packages do Sigma
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    if ($method === 'GET') {
        $serverId = $_GET['server_id'] ?? null;
        
        if (!$serverId) {
            throw new Exception('ID do servidor é obrigatório');
        }
        
        $result = getSigmaPackagesHelper($serverId);
        
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