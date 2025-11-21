<?php
/**
 * API para gerenciamento de revendedores (apenas para admin)
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
    
    // Verificar se é admin
    $isAdmin = ($user['is_admin'] ?? false) || ($user['role'] === 'admin');
    if (!$isAdmin) {
        throw new Exception('Acesso negado. Apenas administradores podem acessar esta funcionalidade.');
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    
    // Extrair ID do revendedor se fornecido
    $resellerId = null;
    $action = null;
    
    if (count($pathParts) > 1) {
        $lastPart = $pathParts[count($pathParts) - 1];
        $secondLastPart = count($pathParts) > 2 ? $pathParts[count($pathParts) - 2] : null;
        
        if ($lastPart === 'suspend' && $secondLastPart) {
            $resellerId = $secondLastPart;
            $action = 'suspend';
        } elseif ($lastPart === 'activate' && $secondLastPart) {
            $resellerId = $secondLastPart;
            $action = 'activate';
        } elseif ($lastPart === 'change-plan' && $secondLastPart) {
            $resellerId = $secondLastPart;
            $action = 'change-plan';
        } elseif ($lastPart !== 'api-resellers.php' && $lastPart !== 'resellers') {
            $resellerId = $lastPart;
        }
    }
    
    switch ($method) {
        case 'GET':
            if ($resellerId) {
                // Buscar revendedor específico
                getResellerDetails($resellerId);
            } else {
                // Listar todos os revendedores
                listResellers();
            }
            break;
            
        case 'PUT':
            if ($action === 'suspend') {
                suspendReseller($resellerId);
            } elseif ($action === 'activate') {
                activateReseller($resellerId);
            } elseif ($action === 'change-plan') {
                changeResellerPlan($resellerId);
            } else {
                updateReseller($resellerId);
            }
            break;
            
        case 'DELETE':
            deleteReseller($resellerId);
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
 * Listar todos os revendedores
 */
function listResellers() {
    $resellers = Database::fetchAll("
        SELECT 
            u.id,
            u.email,
            u.name,
            u.current_plan_id,
            u.plan_expires_at,
            u.plan_status,
            u.registered_at,
            u.created_at,
            COALESCE(rp.name, 'Sem plano') as plan_name,
            COALESCE(rp.price, 0) as plan_price,
            COALESCE(rp.is_trial, FALSE) as is_trial,
            CASE 
                WHEN u.plan_expires_at IS NULL THEN 'no_plan'
                WHEN u.plan_expires_at < NOW() THEN 'expired'
                WHEN u.plan_status = 'suspended' THEN 'suspended'
                ELSE 'active'
            END as current_status,
            CASE 
                WHEN u.plan_expires_at IS NULL THEN 0
                WHEN DATE(u.plan_expires_at) < CURDATE() THEN DATEDIFF(DATE(u.plan_expires_at), CURDATE())
                WHEN DATE(u.plan_expires_at) = CURDATE() THEN 0
                ELSE DATEDIFF(DATE(u.plan_expires_at), CURDATE())
            END as days_remaining
        FROM users u
        LEFT JOIN reseller_plans rp ON u.current_plan_id = rp.id
        WHERE u.role != 'admin' AND u.email != 'admin@ultragestor.com'
        ORDER BY u.created_at DESC
    ");
    
    // Calcular estatísticas
    $stats = [
        'total' => count($resellers),
        'active' => 0,
        'expired' => 0,
        'trial' => 0,
        'suspended' => 0,
        'revenue_monthly' => 0
    ];
    
    foreach ($resellers as &$reseller) {
        // Formatar dados
        $reseller['plan_price'] = (float)($reseller['plan_price'] ?? 0);
        $reseller['days_remaining'] = (int)($reseller['days_remaining'] ?? 0);
        $reseller['is_trial'] = (bool)($reseller['is_trial'] ?? false);
        
        // Contar estatísticas baseado no status atual
        if ($reseller['current_status'] === 'active') {
            $stats['active']++;
        } elseif ($reseller['current_status'] === 'expired') {
            $stats['expired']++;
        } elseif ($reseller['current_status'] === 'suspended' || $reseller['plan_status'] === 'suspended') {
            $stats['suspended']++;
        }
        
        // Contar trials (independente do status, se o plano é trial)
        if ($reseller['is_trial'] && $reseller['current_plan_id'] === 'plan-trial') {
            $stats['trial']++;
        }
        
        // Calcular receita apenas de planos ativos e pagos (não trial)
        if ($reseller['current_status'] === 'active' && !$reseller['is_trial'] && $reseller['plan_price'] > 0) {
            $monthlyValue = $reseller['plan_price'];
            
            // Ajustar para valor mensal baseado no tipo de plano
            if ($reseller['current_plan_id'] === 'plan-quarterly') {
                $monthlyValue = $reseller['plan_price'] / 3;
            } elseif ($reseller['current_plan_id'] === 'plan-biannual') {
                $monthlyValue = $reseller['plan_price'] / 6;
            } elseif ($reseller['current_plan_id'] === 'plan-annual') {
                $monthlyValue = $reseller['plan_price'] / 12;
            }
            
            $stats['revenue_monthly'] += $monthlyValue;
        }
    }
    
    echo json_encode([
        'success' => true,
        'resellers' => $resellers,
        'stats' => $stats
    ]);
}

/**
 * Buscar detalhes de um revendedor específico
 */
function getResellerDetails($resellerId) {
    $reseller = Database::fetch("
        SELECT 
            u.*,
            rp.name as plan_name,
            rp.price as plan_price,
            rp.duration_days as plan_duration,
            rp.is_trial
        FROM users u
        LEFT JOIN reseller_plans rp ON u.current_plan_id = rp.id
        WHERE u.id = ? AND u.is_admin = FALSE
    ", [$resellerId]);
    
    if (!$reseller) {
        throw new Exception('Revendedor não encontrado');
    }
    
    // Buscar histórico de planos
    $planHistory = Database::fetchAll("
        SELECT 
            rph.*,
            rp.name as plan_name,
            rp.price as plan_price
        FROM reseller_plan_history rph
        JOIN reseller_plans rp ON rph.plan_id = rp.id
        WHERE rph.user_id = ?
        ORDER BY rph.created_at DESC
    ", [$resellerId]);
    
    // Buscar estatísticas do revendedor (clientes, faturas, etc.)
    $clientStats = Database::fetch("
        SELECT 
            COUNT(*) as total_clients,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_clients,
            COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_clients
        FROM clients 
        WHERE reseller_id = ?
    ", [$resellerId]);
    
    $invoiceStats = Database::fetch("
        SELECT 
            COUNT(*) as total_invoices,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN final_value END), 0) as total_revenue
        FROM invoices 
        WHERE reseller_id = ?
    ", [$resellerId]);
    
    echo json_encode([
        'success' => true,
        'reseller' => $reseller,
        'plan_history' => $planHistory,
        'client_stats' => $clientStats,
        'invoice_stats' => $invoiceStats
    ]);
}

/**
 * Suspender revendedor
 */
function suspendReseller($resellerId) {
    Database::query(
        "UPDATE users SET plan_status = 'suspended' WHERE id = ? AND is_admin = FALSE",
        [$resellerId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Revendedor suspenso com sucesso'
    ]);
}

/**
 * Ativar revendedor
 */
function activateReseller($resellerId) {
    Database::query(
        "UPDATE users SET plan_status = 'active' WHERE id = ? AND is_admin = FALSE",
        [$resellerId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Revendedor ativado com sucesso'
    ]);
}

/**
 * Alterar plano do revendedor
 */
function changeResellerPlan($resellerId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data['plan_id']) {
        throw new Exception('ID do plano é obrigatório');
    }
    
    // Verificar se o plano existe
    $plan = Database::fetch(
        "SELECT * FROM reseller_plans WHERE id = ? AND is_active = TRUE",
        [$data['plan_id']]
    );
    
    if (!$plan) {
        throw new Exception('Plano não encontrado ou inativo');
    }
    
    // Calcular nova data de expiração
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $plan['duration_days'] . ' days'));
    
    // Atualizar usuário
    Database::query(
        "UPDATE users 
         SET current_plan_id = ?, plan_expires_at = ?, plan_status = 'active' 
         WHERE id = ? AND is_admin = FALSE",
        [$data['plan_id'], $expiresAt, $resellerId]
    );
    
    // Registrar no histórico
    $historyId = 'hist-' . uniqid();
    Database::query(
        "INSERT INTO reseller_plan_history 
         (id, user_id, plan_id, started_at, expires_at, status, payment_amount, payment_method) 
         VALUES (?, ?, ?, NOW(), ?, 'active', ?, ?)",
        [
            $historyId,
            $resellerId,
            $data['plan_id'],
            $expiresAt,
            $data['payment_amount'] ?? $plan['price'],
            $data['payment_method'] ?? 'admin_change'
        ]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Plano alterado com sucesso',
        'new_expires_at' => $expiresAt
    ]);
}

/**
 * Atualizar dados do revendedor
 */
function updateReseller($resellerId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$resellerId) {
        throw new Exception('ID do revendedor é obrigatório');
    }
    
    // Verificar se o revendedor existe
    $reseller = Database::fetch(
        "SELECT * FROM users WHERE id = ? AND is_admin = FALSE",
        [$resellerId]
    );
    
    if (!$reseller) {
        throw new Exception('Revendedor não encontrado');
    }
    
    // Preparar campos para atualização
    $fields = [];
    $values = [];
    
    if (isset($data['name'])) {
        $fields[] = 'name = ?';
        $values[] = $data['name'];
    }
    
    if (isset($data['email'])) {
        // Verificar se o email já existe em outro usuário
        $existingUser = Database::fetch(
            "SELECT id FROM users WHERE email = ? AND id != ?",
            [$data['email'], $resellerId]
        );
        
        if ($existingUser) {
            throw new Exception('Este email já está em uso por outro usuário');
        }
        
        $fields[] = 'email = ?';
        $values[] = $data['email'];
    }
    
    if (isset($data['whatsapp'])) {
        $fields[] = 'whatsapp = ?';
        $values[] = $data['whatsapp'];
    }
    
    if (isset($data['password']) && !empty($data['password'])) {
        $fields[] = 'password_hash = ?';
        $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    if (empty($fields)) {
        throw new Exception('Nenhum campo para atualizar');
    }
    
    $values[] = $resellerId;
    
    Database::query(
        "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ? AND is_admin = FALSE",
        $values
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Dados atualizados com sucesso'
    ]);
}

/**
 * Excluir revendedor
 */
function deleteReseller($resellerId) {
    // Verificar se o revendedor tem clientes
    $clientCount = Database::fetch(
        "SELECT COUNT(*) as count FROM clients WHERE reseller_id = ?",
        [$resellerId]
    )['count'];
    
    if ($clientCount > 0) {
        throw new Exception("Não é possível excluir este revendedor pois ele possui $clientCount cliente(s) cadastrado(s)");
    }
    
    // Excluir revendedor
    Database::query(
        "DELETE FROM users WHERE id = ? AND is_admin = FALSE",
        [$resellerId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Revendedor excluído com sucesso'
    ]);
}
?>
