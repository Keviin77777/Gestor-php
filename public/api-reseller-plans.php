<?php
/**
 * API para gerenciamento de planos de revendedores
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    
    // Extrair ID do plano se fornecido
    $planId = null;
    if (count($pathParts) > 1) {
        $lastPart = $pathParts[count($pathParts) - 1];
        if ($lastPart !== 'api-reseller-plans.php' && $lastPart !== 'reseller-plans') {
            $planId = $lastPart;
        }
    }
    
    switch ($method) {
        case 'GET':
            if ($planId) {
                getPlan($planId);
            } else {
                listPlans($user);
            }
            break;
            
        case 'POST':
            // Apenas admin pode criar planos
            $isAdmin = ($user['is_admin'] ?? false) || ($user['role'] === 'admin');
            if (!$isAdmin) {
                throw new Exception('Acesso negado. Apenas administradores podem criar planos.');
            }
            createPlan();
            break;
            
        case 'PUT':
            // Apenas admin pode editar planos
            $isAdmin = ($user['is_admin'] ?? false) || ($user['role'] === 'admin');
            if (!$isAdmin) {
                throw new Exception('Acesso negado. Apenas administradores podem editar planos.');
            }
            updatePlan($planId);
            break;
            
        case 'DELETE':
            // Apenas admin pode excluir planos
            $isAdmin = ($user['is_admin'] ?? false) || ($user['role'] === 'admin');
            if (!$isAdmin) {
                throw new Exception('Acesso negado. Apenas administradores podem excluir planos.');
            }
            deletePlan($planId);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Listar planos
 */
function listPlans($user) {
    $isAdmin = ($user['is_admin'] ?? false) || ($user['role'] === 'admin');
    if ($isAdmin) {
        // Admin vê todos os planos com estatísticas
        $plans = Database::fetchAll("
            SELECT 
                rp.*,
                COUNT(u.id) as active_users,
                COALESCE(SUM(CASE WHEN u.plan_status = 'active' AND rp.is_trial = FALSE THEN rp.price END), 0) as total_revenue
            FROM reseller_plans rp
            LEFT JOIN users u ON rp.id = u.current_plan_id AND u.is_admin = FALSE
            GROUP BY rp.id
            ORDER BY rp.is_trial DESC, rp.price ASC
        ");
        
        foreach ($plans as &$plan) {
            $plan['price'] = (float)$plan['price'];
            $plan['active_users'] = (int)$plan['active_users'];
            $plan['total_revenue'] = (float)$plan['total_revenue'];
            $plan['is_active'] = (bool)$plan['is_active'];
            $plan['is_trial'] = (bool)$plan['is_trial'];
        }
    } else {
        // Revendedores veem apenas planos ativos (para renovação)
        $plans = Database::fetchAll("
            SELECT id, name, description, price, duration_days, is_trial
            FROM reseller_plans 
            WHERE is_active = TRUE 
            ORDER BY is_trial DESC, price ASC
        ");
        
        foreach ($plans as &$plan) {
            $plan['price'] = (float)$plan['price'];
            $plan['is_trial'] = (bool)$plan['is_trial'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'plans' => $plans,
        'is_admin' => $isAdmin
    ]);
}

/**
 * Buscar plano específico
 */
function getPlan($planId) {
    $plan = Database::fetch("
        SELECT * FROM reseller_plans WHERE id = ?
    ", [$planId]);
    
    if (!$plan) {
        throw new Exception('Plano não encontrado');
    }
    
    $plan['price'] = (float)$plan['price'];
    $plan['is_active'] = (bool)$plan['is_active'];
    $plan['is_trial'] = (bool)$plan['is_trial'];
    
    echo json_encode([
        'success' => true,
        'plan' => $plan
    ]);
}

/**
 * Criar novo plano
 */
function createPlan() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar dados obrigatórios
    if (!$data['name'] || !isset($data['price']) || !$data['duration_days']) {
        throw new Exception('Campos obrigatórios: name, price, duration_days');
    }
    
    if ($data['price'] < 0) {
        throw new Exception('O preço não pode ser negativo');
    }
    
    if ($data['duration_days'] <= 0) {
        throw new Exception('A duração deve ser maior que zero');
    }
    
    $planId = 'plan-' . uniqid();
    
    Database::query("
        INSERT INTO reseller_plans 
        (id, name, description, price, duration_days, is_active, is_trial) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ", [
        $planId,
        $data['name'],
        $data['description'] ?? null,
        $data['price'],
        $data['duration_days'],
        $data['is_active'] ?? true,
        $data['is_trial'] ?? false
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Plano criado com sucesso',
        'id' => $planId
    ]);
}

/**
 * Atualizar plano
 */
function updatePlan($planId) {
    if (!$planId) {
        throw new Exception('ID do plano é obrigatório');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verificar se o plano existe
    $plan = Database::fetch("SELECT * FROM reseller_plans WHERE id = ?", [$planId]);
    if (!$plan) {
        throw new Exception('Plano não encontrado');
    }
    
    // Validar dados
    if (isset($data['price']) && $data['price'] < 0) {
        throw new Exception('O preço não pode ser negativo');
    }
    
    if (isset($data['duration_days']) && $data['duration_days'] <= 0) {
        throw new Exception('A duração deve ser maior que zero');
    }
    
    // Preparar campos para atualização
    $fields = [];
    $values = [];
    
    if (isset($data['name'])) {
        $fields[] = 'name = ?';
        $values[] = $data['name'];
    }
    
    if (isset($data['description'])) {
        $fields[] = 'description = ?';
        $values[] = $data['description'];
    }
    
    if (isset($data['price'])) {
        $fields[] = 'price = ?';
        $values[] = $data['price'];
    }
    
    if (isset($data['duration_days'])) {
        $fields[] = 'duration_days = ?';
        $values[] = $data['duration_days'];
    }
    
    if (isset($data['is_active'])) {
        $fields[] = 'is_active = ?';
        $values[] = $data['is_active'];
    }
    
    if (isset($data['is_trial'])) {
        $fields[] = 'is_trial = ?';
        $values[] = $data['is_trial'];
    }
    
    if (empty($fields)) {
        throw new Exception('Nenhum campo para atualizar');
    }
    
    $fields[] = 'updated_at = NOW()';
    $values[] = $planId;
    
    Database::query(
        "UPDATE reseller_plans SET " . implode(', ', $fields) . " WHERE id = ?",
        $values
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Plano atualizado com sucesso'
    ]);
}

/**
 * Excluir plano
 */
function deletePlan($planId) {
    if (!$planId) {
        throw new Exception('ID do plano é obrigatório');
    }
    
    // Verificar se o plano existe
    $plan = Database::fetch("SELECT * FROM reseller_plans WHERE id = ?", [$planId]);
    if (!$plan) {
        throw new Exception('Plano não encontrado');
    }
    
    // Verificar se há usuários usando este plano
    $userCount = Database::fetch(
        "SELECT COUNT(*) as count FROM users WHERE current_plan_id = ?",
        [$planId]
    )['count'];
    
    if ($userCount > 0) {
        throw new Exception("Não é possível excluir este plano pois $userCount usuário(s) estão utilizando-o");
    }
    
    // Verificar se há histórico usando este plano
    $historyCount = Database::fetch(
        "SELECT COUNT(*) as count FROM reseller_plan_history WHERE plan_id = ?",
        [$planId]
    )['count'];
    
    if ($historyCount > 0) {
        // Se há histórico, apenas desativar o plano
        Database::query(
            "UPDATE reseller_plans SET is_active = FALSE WHERE id = ?",
            [$planId]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Plano desativado com sucesso (não foi excluído devido ao histórico existente)'
        ]);
    } else {
        // Se não há histórico, pode excluir
        Database::query("DELETE FROM reseller_plans WHERE id = ?", [$planId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Plano excluído com sucesso'
        ]);
    }
}
?>
