<?php
/**
 * API para Sincronização Reversa Sigma -> Gestor
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../app/core/Database.php';
    require_once __DIR__ . '/../app/helpers/functions.php';
    require_once __DIR__ . '/../app/helpers/sigma-sync-dates.php';
    
    loadEnv(__DIR__ . '/../.env');
    
    // Verificar autenticação
    require_once __DIR__ . '/../app/helpers/auth-helper.php';
    $user = getAuthenticatedUser();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? null;
    
    if ($method === 'POST' && $action === 'sync-date') {
        // Sincronizar data de um cliente específico
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['client_id'])) {
            throw new Exception('client_id é obrigatório');
        }
        
        $result = syncDateFromSigmaToGestor($data['client_id'], $user['id']);
        
        echo json_encode($result);
        exit;
    }
    
    if ($method === 'POST' && $action === 'sync-all-dates') {
        // Sincronizar datas de todos os clientes
        $result = syncAllClientsDatesFromSigma($user['id']);
        
        echo json_encode($result);
        exit;
    }
    
    if ($method === 'GET' && $action === 'check-sigma-server') {
        // Verificar se há servidor Sigma configurado
        $server = Database::fetch(
            "SELECT id FROM servers WHERE user_id = ? AND panel_type = 'sigma' AND status = 'active' LIMIT 1",
            [$user['id']]
        );
        
        echo json_encode([
            'success' => true,
            'has_sigma_server' => !empty($server)
        ]);
        exit;
    }
    
    throw new Exception('Ação não reconhecida');
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
