<?php
/**
 * API para gerenciar fila de mensagens WhatsApp
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

$user = Auth::user();
if (!$user) {
    Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
    exit;
}

$resellerId = $user['id'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::connect();
    
    switch ($action) {
        case 'get_config':
            handleGetConfig($db, $resellerId);
            break;
            
        case 'save_config':
            handleSaveConfig($db, $resellerId);
            break;
            
        case 'get_stats':
            handleGetStats($db, $resellerId);
            break;
            
        case 'get_queue':
            handleGetQueue($db, $resellerId);
            break;
            
        case 'retry':
            handleRetry($db, $resellerId);
            break;
            
        case 'delete':
            handleDelete($db, $resellerId);
            break;
            
        case 'force_process':
            handleForceProcess($db, $resellerId);
            break;
            
        case 'delete_sent':
            handleDeleteSent($db, $resellerId);
            break;
            
        case 'delete_all':
            handleDeleteAll($db, $resellerId);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Ação inválida'], 400);
    }
    
} catch (Exception $e) {
    Response::json(['success' => false, 'error' => $e->getMessage()], 500);
}

/**
 * Obter configuração de rate limit
 */
function handleGetConfig($db, $resellerId) {
    $stmt = $db->prepare("
        SELECT * FROM whatsapp_rate_limit_config 
        WHERE reseller_id = ?
    ");
    $stmt->execute([$resellerId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        // Criar configuração padrão
        $stmt = $db->prepare("
            INSERT INTO whatsapp_rate_limit_config 
            (reseller_id, messages_per_minute, messages_per_hour, delay_between_messages)
            VALUES (?, 20, 100, 3)
        ");
        $stmt->execute([$resellerId]);
        
        $config = [
            'messages_per_minute' => 20,
            'messages_per_hour' => 100,
            'delay_between_messages' => 3,
            'enabled' => 1
        ];
    }
    
    Response::json([
        'success' => true,
        'config' => $config
    ]);
}

/**
 * Salvar configuração de rate limit
 */
function handleSaveConfig($db, $resellerId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $messagesPerMinute = $input['messages_per_minute'] ?? 20;
    $messagesPerHour = $input['messages_per_hour'] ?? 100;
    $delayBetween = $input['delay_between_messages'] ?? 3;
    
    // Validar limites
    if ($messagesPerMinute < 1 || $messagesPerMinute > 60) {
        Response::json(['success' => false, 'error' => 'Mensagens por minuto deve estar entre 1 e 60'], 400);
        return;
    }
    
    if ($messagesPerHour < 10 || $messagesPerHour > 500) {
        Response::json(['success' => false, 'error' => 'Mensagens por hora deve estar entre 10 e 500'], 400);
        return;
    }
    
    if ($delayBetween < 1 || $delayBetween > 60) {
        Response::json(['success' => false, 'error' => 'Delay deve estar entre 1 e 60 segundos'], 400);
        return;
    }
    
    $stmt = $db->prepare("
        INSERT INTO whatsapp_rate_limit_config 
        (reseller_id, messages_per_minute, messages_per_hour, delay_between_messages)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            messages_per_minute = VALUES(messages_per_minute),
            messages_per_hour = VALUES(messages_per_hour),
            delay_between_messages = VALUES(delay_between_messages)
    ");
    
    $stmt->execute([$resellerId, $messagesPerMinute, $messagesPerHour, $delayBetween]);
    
    Response::json([
        'success' => true,
        'message' => 'Configuração salva com sucesso'
    ]);
}

/**
 * Obter estatísticas da fila
 */
function handleGetStats($db, $resellerId) {
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM whatsapp_message_queue
        WHERE reseller_id = ?
        GROUP BY status
    ");
    $stmt->execute([$resellerId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'pending' => 0,
        'processing' => 0,
        'sent' => 0,
        'failed' => 0
    ];
    
    foreach ($results as $row) {
        $stats[$row['status']] = (int)$row['count'];
    }
    
    Response::json([
        'success' => true,
        'stats' => $stats
    ]);
}

/**
 * Obter fila de mensagens
 */
function handleGetQueue($db, $resellerId) {
    $status = $_GET['status'] ?? '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? max(10, min(100, intval($_GET['per_page']))) : 20;
    $offset = ($page - 1) * $perPage;
    
    // Contar total
    $countSql = "
        SELECT COUNT(*) as total
        FROM whatsapp_message_queue q
        WHERE q.reseller_id = ?
    ";
    
    $params = [$resellerId];
    
    if ($status) {
        $countSql .= " AND q.status = ?";
        $params[] = $status;
    }
    
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Buscar dados paginados
    $sql = "
        SELECT 
            q.*,
            c.name as client_name
        FROM whatsapp_message_queue q
        LEFT JOIN clients c ON q.client_id = c.id
        WHERE q.reseller_id = ?
    ";
    
    $params = [$resellerId];
    
    if ($status) {
        $sql .= " AND q.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY q.priority DESC, q.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::json([
        'success' => true,
        'queue' => $queue,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage),
            'has_next' => $page < ceil($total / $perPage),
            'has_prev' => $page > 1
        ]
    ]);
}

/**
 * Reenviar mensagem
 */
function handleRetry($db, $resellerId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        Response::json(['success' => false, 'error' => 'ID não fornecido'], 400);
        return;
    }
    
    // Verificar se a mensagem pertence ao revendedor
    $stmt = $db->prepare("
        SELECT * FROM whatsapp_message_queue 
        WHERE id = ? AND reseller_id = ?
    ");
    $stmt->execute([$id, $resellerId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        Response::json(['success' => false, 'error' => 'Mensagem não encontrada'], 404);
        return;
    }
    
    // Resetar status para pending
    $stmt = $db->prepare("
        UPDATE whatsapp_message_queue 
        SET status = 'pending', 
            scheduled_at = NOW(),
            error_message = NULL
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    
    Response::json([
        'success' => true,
        'message' => 'Mensagem reenviada para a fila'
    ]);
}

/**
 * Excluir mensagem
 */
function handleDelete($db, $resellerId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        Response::json(['success' => false, 'error' => 'ID não fornecido'], 400);
        return;
    }
    
    $stmt = $db->prepare("
        DELETE FROM whatsapp_message_queue 
        WHERE id = ? AND reseller_id = ?
    ");
    $stmt->execute([$id, $resellerId]);
    
    if ($stmt->rowCount() === 0) {
        Response::json(['success' => false, 'error' => 'Mensagem não encontrada'], 404);
        return;
    }
    
    Response::json([
        'success' => true,
        'message' => 'Mensagem excluída'
    ]);
}

/**
 * Forçar processamento da fila
 */
function handleForceProcess($db, $resellerId) {
    // Executar o processador de fila
    $scriptPath = __DIR__ . '/../scripts/process-queue.php';
    
    if (!file_exists($scriptPath)) {
        Response::json(['success' => false, 'error' => 'Script de processamento não encontrado'], 500);
        return;
    }
    
    // Executar em background
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        pclose(popen("start /B php \"$scriptPath\"", "r"));
    } else {
        // Linux/Unix
        exec("php \"$scriptPath\" > /dev/null 2>&1 &");
    }
    
    Response::json([
        'success' => true,
        'message' => 'Processamento da fila iniciado! As mensagens serão enviadas respeitando os limites configurados.'
    ]);
}

/**
 * Excluir mensagens enviadas
 */
function handleDeleteSent($db, $resellerId) {
    $stmt = $db->prepare("
        DELETE FROM whatsapp_message_queue 
        WHERE reseller_id = ? AND status = 'sent'
    ");
    $stmt->execute([$resellerId]);
    
    $deleted = $stmt->rowCount();
    
    Response::json([
        'success' => true,
        'deleted' => $deleted,
        'message' => "$deleted mensagens enviadas foram excluídas"
    ]);
}

/**
 * Excluir todas as mensagens
 */
function handleDeleteAll($db, $resellerId) {
    $stmt = $db->prepare("
        DELETE FROM whatsapp_message_queue 
        WHERE reseller_id = ?
    ");
    $stmt->execute([$resellerId]);
    
    $deleted = $stmt->rowCount();
    
    Response::json([
        'success' => true,
        'deleted' => $deleted,
        'message' => "$deleted mensagens foram excluídas"
    ]);
}
