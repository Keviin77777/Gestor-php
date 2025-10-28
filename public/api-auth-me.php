<?php
/**
 * API para obter informações do usuário autenticado
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth-helper.php';
require_once __DIR__ . '/../app/core/Database.php';
loadEnv(__DIR__ . '/../.env');

try {
    // Verificar autenticação
    $user = getAuthenticatedUser();
    
    // Buscar informações completas do usuário
    $userDetails = Database::fetch("
        SELECT 
            u.*,
            rp.name as plan_name,
            rp.price as plan_price,
            rp.duration_days as plan_duration,
            rp.is_trial as plan_is_trial,
            CASE 
                WHEN u.plan_expires_at < NOW() THEN 'expired'
                WHEN u.plan_expires_at IS NULL THEN 'no_plan'
                ELSE u.plan_status
            END as current_status,
            DATEDIFF(u.plan_expires_at, NOW()) as days_remaining
        FROM users u
        LEFT JOIN reseller_plans rp ON u.current_plan_id = rp.id
        WHERE u.id = ?
    ", [$user['id']]);
    
    if (!$userDetails) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Remover senha do retorno
    unset($userDetails['password']);
    
    // Converter campos booleanos
    $userDetails['is_admin'] = (bool)$userDetails['is_admin'];
    $userDetails['plan_is_trial'] = (bool)$userDetails['plan_is_trial'];
    $userDetails['days_remaining'] = (int)$userDetails['days_remaining'];
    $userDetails['plan_price'] = (float)$userDetails['plan_price'];
    
    echo json_encode([
        'success' => true,
        'user' => $userDetails
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
