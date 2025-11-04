<?php
/**
 * API de Histórico de Pagamentos (Admin)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
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
