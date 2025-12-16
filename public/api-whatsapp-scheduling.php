<?php
// CORS - deve vir antes de qualquer output
require_once __DIR__ . '/../app/helpers/cors.php';

header('Content-Type: application/json');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

// Verificar autenticação
$user = Auth::user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$reseller_id = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Listar mensagens agendadas
            $query = "
                SELECT 
                    wmq.id,
                    wmq.phone,
                    wmq.message,
                    wmq.scheduled_at,
                    wmq.status,
                    wmq.created_at,
                    c.name as client_name,
                    DATE(wmq.scheduled_at) as scheduled_date,
                    TIME(wmq.scheduled_at) as scheduled_time
                FROM whatsapp_message_queue wmq
                LEFT JOIN clients c ON wmq.client_id = c.id
                WHERE wmq.scheduled_at IS NOT NULL AND wmq.reseller_id = ?
                ORDER BY wmq.scheduled_at DESC
            ";
            
            $messages = Database::fetchAll($query, [$reseller_id]);
            
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            break;

        case 'POST':
            // Criar novo agendamento
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['client_id']) || !isset($data['scheduled_date']) || !isset($data['scheduled_time'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
                exit;
            }
            
            // Buscar dados do cliente
            $client = Database::fetch("SELECT phone FROM clients WHERE id = ?", [$data['client_id']]);
            
            if (!$client) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
                exit;
            }
            
            // Buscar mensagem do template se fornecido
            $message = $data['custom_message'] ?? '';
            if (!empty($data['template_id'])) {
                $template = Database::fetch("SELECT message FROM whatsapp_templates WHERE id = ?", [$data['template_id']]);
                if ($template) {
                    $message = $template['message'];
                }
            }
            
            // Combinar data e hora para timestamp
            $scheduled_at = $data['scheduled_date'] . ' ' . $data['scheduled_time'] . ':00';
            
            // Inserir na fila de mensagens
            $query = "
                INSERT INTO whatsapp_message_queue 
                (reseller_id, client_id, phone, message, template_id, scheduled_at, status, priority)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', 5)
            ";
            
            $id = Database::insert($query, [
                $reseller_id,
                $data['client_id'],
                $client['phone'],
                $message,
                !empty($data['template_id']) ? $data['template_id'] : null,
                $scheduled_at
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Mensagem agendada com sucesso',
                'id' => $id
            ]);
            break;

        case 'DELETE':
            // Cancelar agendamento
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
                exit;
            }
            
            $stmt = Database::query("DELETE FROM whatsapp_message_queue WHERE id = ? AND reseller_id = ? AND status = 'pending'", [$id, $reseller_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Agendamento cancelado']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado ou já processado']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
