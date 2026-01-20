<?php
/**
 * API de Histórico de Pagamentos (Admin)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Response.php';

// Verificar autenticação
$user = Auth::user();

if (!$user) {
    Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
    exit;
}

// Apenas admin pode acessar
if (!isset($user['role']) || $user['role'] !== 'admin') {
    Response::json(['success' => false, 'error' => 'Acesso negado'], 403);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::connect();

try {
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
            
        case 'POST':
            handlePost($db, $user);
            break;
            
        case 'DELETE':
            handleDelete($db);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
} catch (Exception $e) {
    error_log("API Payment History error: " . $e->getMessage());
    Response::json([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ], 500);
}

/**
 * GET - Listar pagamentos
 */
function handleGet($db) {
    $status = $_GET['status'] ?? '';
    $period = $_GET['period'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    // Construir query
    $sql = "
        SELECT 
            rp.*,
            u.name as user_name,
            u.email as user_email,
            p.name as plan_name
        FROM renewal_payments rp
        LEFT JOIN users u ON rp.user_id = u.id
        LEFT JOIN reseller_plans p ON rp.plan_id = p.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filtro de status
    if ($status) {
        $sql .= " AND rp.status = ?";
        $params[] = $status;
    }
    
    // Filtro de período
    if ($period !== 'all') {
        switch ($period) {
            case 'today':
                $sql .= " AND DATE(rp.created_at) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND rp.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $sql .= " AND rp.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }
    
    // Filtro de busca
    if ($search) {
        $sql .= " AND (u.email LIKE ? OR u.name LIKE ? OR rp.payment_id LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY rp.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estatísticas
    $stats = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'total' => 0
    ];
    
    foreach ($payments as $payment) {
        switch ($payment['status']) {
            case 'pending':
                $stats['pending']++;
                break;
            case 'approved':
                $stats['approved']++;
                $stats['total'] += (float)$payment['amount'];
                break;
            case 'rejected':
            case 'cancelled':
                $stats['rejected']++;
                break;
        }
    }
    
    Response::json([
        'success' => true,
        'payments' => $payments,
        'stats' => $stats
    ]);
}

/**
 * POST - Aprovar pagamento
 */
function handlePost($db, $user) {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;
    $id = $data['id'] ?? null;
    
    if ($action !== 'approve') {
        Response::json(['success' => false, 'error' => 'Ação inválida'], 400);
        exit;
    }
    
    if (!$id) {
        Response::json(['success' => false, 'error' => 'ID do pagamento é obrigatório'], 400);
        exit;
    }
    
    // Buscar pagamento
    $stmt = $db->prepare("SELECT * FROM renewal_payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        Response::json(['success' => false, 'error' => 'Pagamento não encontrado'], 404);
        exit;
    }
    
    if ($payment['status'] === 'approved') {
        Response::json(['success' => false, 'error' => 'Este pagamento já foi aprovado'], 400);
        exit;
    }
    
    // Buscar plano
    $stmt = $db->prepare("SELECT * FROM reseller_plans WHERE id = ?");
    $stmt->execute([$payment['plan_id']]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        Response::json(['success' => false, 'error' => 'Plano não encontrado'], 404);
        exit;
    }
    
    // Buscar usuário (revendedor)
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$payment['user_id']]);
    $reseller = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reseller) {
        Response::json(['success' => false, 'error' => 'Revendedor não encontrado'], 404);
        exit;
    }
    
    // Iniciar transação
    $db->beginTransaction();
    
    try {
        // Calcular nova data de expiração
        $currentExpiry = $reseller['plan_expires_at'];
        if ($currentExpiry && strtotime($currentExpiry) > time()) {
            // Se ainda tem plano ativo, adicionar dias ao vencimento atual
            $newExpiry = date('Y-m-d H:i:s', strtotime($currentExpiry . ' +' . $plan['duration_days'] . ' days'));
        } else {
            // Se expirado ou sem plano, começar de hoje
            $newExpiry = date('Y-m-d H:i:s', strtotime('+' . $plan['duration_days'] . ' days'));
        }
        
        // Atualizar usuário com novo plano
        $stmt = $db->prepare("
            UPDATE users 
            SET current_plan_id = ?, 
                plan_expires_at = ?, 
                plan_status = 'active' 
            WHERE id = ?
        ");
        $stmt->execute([$payment['plan_id'], $newExpiry, $payment['user_id']]);
        
        // Atualizar status do pagamento
        $stmt = $db->prepare("
            UPDATE renewal_payments 
            SET status = 'approved', 
                approved_at = NOW(), 
                approved_by = ? 
            WHERE id = ?
        ");
        $stmt->execute([$user['id'], $id]);
        
        // Registrar no histórico de planos
        $historyId = 'hist-' . uniqid();
        $stmt = $db->prepare("
            INSERT INTO reseller_plan_history 
            (id, user_id, plan_id, started_at, expires_at, status, payment_amount, payment_method) 
            VALUES (?, ?, ?, NOW(), ?, 'active', ?, ?)
        ");
        $stmt->execute([
            $historyId,
            $payment['user_id'],
            $payment['plan_id'],
            $newExpiry,
            $payment['amount'],
            $payment['payment_method']
        ]);
        
        // Commit da transação
        $db->commit();
        
        Response::json([
            'success' => true,
            'message' => 'Pagamento aprovado e plano renovado com sucesso',
            'new_expiry' => $newExpiry
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * DELETE - Excluir pagamento
 */
function handleDelete($db) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        Response::json(['success' => false, 'error' => 'ID do pagamento é obrigatório'], 400);
        exit;
    }
    
    // Verificar se existe
    $stmt = $db->prepare("SELECT * FROM renewal_payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        Response::json(['success' => false, 'error' => 'Pagamento não encontrado'], 404);
        exit;
    }
    
    // Excluir
    $stmt = $db->prepare("DELETE FROM renewal_payments WHERE id = ?");
    $stmt->execute([$id]);
    
    Response::json([
        'success' => true,
        'message' => 'Pagamento excluído com sucesso'
    ]);
}
