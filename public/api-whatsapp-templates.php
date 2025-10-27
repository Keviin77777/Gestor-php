<?php
/**
 * API para gerenciar templates do WhatsApp
 */

header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/helpers/auth-helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = getAuthenticatedUser();
$resellerId = $user['id'];

try {
    switch ($method) {
        case 'GET':
            // Listar templates
            $templates = Database::fetchAll(
                "SELECT * FROM whatsapp_templates WHERE reseller_id = ? ORDER BY is_default DESC, type, created_at DESC",
                [$resellerId]
            );
            
            echo json_encode([
                'success' => true,
                'templates' => $templates
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'POST':
            // Criar ou atualizar template
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception('Dados inválidos');
            }
            
            // Validar campos obrigatórios
            if (empty($data['name']) || empty($data['type']) || empty($data['message'])) {
                throw new Exception('Nome, tipo e mensagem são obrigatórios');
            }
            
            // Extrair variáveis da mensagem
            preg_match_all('/\{\{([a-z_]+)\}\}/', $data['message'], $matches);
            $variables = array_unique($matches[1]);
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Atualizar template existente
                Database::query(
                    "UPDATE whatsapp_templates SET 
                     name = ?,
                     type = ?,
                     title = ?,
                     message = ?,
                     variables = ?,
                     is_active = ?,
                     is_default = ?,
                     updated_at = CURRENT_TIMESTAMP
                     WHERE id = ? AND reseller_id = ?",
                    [
                        $data['name'],
                        $data['type'],
                        $data['title'] ?? '',
                        $data['message'],
                        json_encode($variables),
                        $data['is_active'] ?? 1,
                        $data['is_default'] ?? 0,
                        $data['id'],
                        $resellerId
                    ]
                );
                
                $templateId = $data['id'];
                $message = 'Template atualizado com sucesso';
            } else {
                // Criar novo template
                $templateId = 'tpl-' . uniqid();
                
                Database::query(
                    "INSERT INTO whatsapp_templates (id, reseller_id, name, type, title, message, variables, is_active, is_default) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $templateId,
                        $resellerId,
                        $data['name'],
                        $data['type'],
                        $data['title'] ?? '',
                        $data['message'],
                        json_encode($variables),
                        $data['is_active'] ?? 1,
                        $data['is_default'] ?? 0
                    ]
                );
                
                $message = 'Template criado com sucesso';
            }
            
            // Se marcou como padrão, desmarcar outros do mesmo tipo
            if (isset($data['is_default']) && $data['is_default']) {
                Database::query(
                    "UPDATE whatsapp_templates SET is_default = 0 
                     WHERE reseller_id = ? AND type = ? AND id != ?",
                    [$resellerId, $data['type'], $templateId]
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'template_id' => $templateId
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'PUT':
            // Atualizar template (especialmente para agendamento)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || empty($data['id'])) {
                throw new Exception('ID do template é obrigatório');
            }
            
            $templateId = $data['id'];
            
            // Verificar se o template existe
            $existingTemplate = Database::fetch(
                "SELECT id FROM whatsapp_templates WHERE id = ? AND reseller_id = ?",
                [$templateId, $resellerId]
            );
            
            if (!$existingTemplate) {
                throw new Exception('Template não encontrado');
            }
            
            // Preparar campos para atualização
            $updateFields = [];
            $updateValues = [];
            
            if (isset($data['is_scheduled'])) {
                $updateFields[] = 'is_scheduled = ?';
                $updateValues[] = $data['is_scheduled'] ? 1 : 0;
            }
            
            if (isset($data['scheduled_days'])) {
                $updateFields[] = 'scheduled_days = ?';
                $updateValues[] = json_encode($data['scheduled_days']);
            }
            
            if (isset($data['scheduled_time'])) {
                $updateFields[] = 'scheduled_time = ?';
                $updateValues[] = $data['scheduled_time'];
            }
            
            if (empty($updateFields)) {
                throw new Exception('Nenhum campo para atualizar');
            }
            
            $updateFields[] = 'updated_at = NOW()';
            $updateValues[] = $templateId;
            $updateValues[] = $resellerId;
            
            $sql = "UPDATE whatsapp_templates SET " . implode(', ', $updateFields) . " WHERE id = ? AND reseller_id = ?";
            
            Database::query($sql, $updateValues);
            
            echo json_encode([
                'success' => true,
                'message' => 'Agendamento atualizado com sucesso!'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'DELETE':
            // Excluir template
            $templateId = $_GET['id'] ?? null;
            
            if (!$templateId) {
                throw new Exception('ID do template não informado');
            }
            
            // Verificar se não é um template padrão do sistema
            $template = Database::fetch(
                "SELECT * FROM whatsapp_templates WHERE id = ? AND reseller_id = ?",
                [$templateId, $resellerId]
            );
            
            if (!$template) {
                throw new Exception('Template não encontrado');
            }
            
            // Não permitir excluir templates padrão do sistema (apenas se for realmente padrão)
            if ($template['is_default'] == 1) {
                throw new Exception('Não é possível excluir templates padrão do sistema');
            }
            
            Database::query(
                "DELETE FROM whatsapp_templates WHERE id = ? AND reseller_id = ?",
                [$templateId, $resellerId]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Template excluído com sucesso'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
