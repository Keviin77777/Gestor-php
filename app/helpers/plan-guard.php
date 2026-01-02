<?php
/**
 * Guard para verificar se o revendedor tem plano ativo
 * Bloqueia acesso às funcionalidades se o plano estiver vencido
 */

require_once __DIR__ . '/../core/Database.php';

/**
 * Verificar se o revendedor tem plano ativo
 * @param string $userId ID do usuário
 * @param bool $throwException Se deve lançar exceção ou retornar false
 * @return bool|array
 * @throws Exception
 */
function checkResellerPlanActive($userId, $throwException = true) {
    // Buscar dados do usuário
    $user = Database::fetch("
        SELECT 
            u.id,
            u.role,
            u.is_admin,
            u.plan_expires_at,
            u.plan_status,
            CASE 
                WHEN u.plan_expires_at IS NULL THEN -999
                WHEN u.plan_expires_at < NOW() THEN -1
                ELSE DATEDIFF(DATE(u.plan_expires_at), CURDATE())
            END as days_remaining
        FROM users u
        WHERE u.id = ?
    ", [$userId]);
    
    if (!$user) {
        if ($throwException) {
            throw new Exception('Usuário não encontrado');
        }
        return false;
    }
    
    // Admin sempre tem acesso
    if ($user['role'] === 'admin' || $user['is_admin'] == 1) {
        return [
            'has_access' => true,
            'is_admin' => true,
            'days_remaining' => 999,
            'plan_status' => 'active'
        ];
    }
    
    // Verificar se o plano está vencido
    $daysRemaining = (int)$user['days_remaining'];
    $isExpired = $daysRemaining < 0;
    
    if ($isExpired) {
        if ($throwException) {
            http_response_code(403);
            throw new Exception('Seu plano expirou. Renove para continuar usando o sistema.');
        }
        return [
            'has_access' => false,
            'is_admin' => false,
            'days_remaining' => $daysRemaining,
            'plan_status' => 'expired',
            'message' => 'Plano expirado. Renove para continuar.'
        ];
    }
    
    return [
        'has_access' => true,
        'is_admin' => false,
        'days_remaining' => $daysRemaining,
        'plan_status' => $user['plan_status'] ?? 'active'
    ];
}

/**
 * Middleware para proteger rotas que exigem plano ativo
 * Uso: requireActivePlan() no início de cada API
 */
function requireActivePlan() {
    require_once __DIR__ . '/auth-helper.php';
    
    try {
        $user = getAuthenticatedUser();
        $planCheck = checkResellerPlanActive($user['id'], false);
        
        if (!$planCheck['has_access']) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Plano expirado',
                'message' => 'Seu plano expirou. Renove para continuar usando o sistema.',
                'plan_expired' => true,
                'days_remaining' => $planCheck['days_remaining']
            ]);
            exit;
        }
        
        return $planCheck;
    } catch (Exception $e) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

/**
 * Verificar se o revendedor pode enviar mensagens WhatsApp
 * @param string $resellerId
 * @return bool
 */
function canSendWhatsAppMessages($resellerId) {
    $planCheck = checkResellerPlanActive($resellerId, false);
    return $planCheck && $planCheck['has_access'];
}
