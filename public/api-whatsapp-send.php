<?php
/**
 * API para enviar mensagens WhatsApp
 */

// Suprimir warnings e notices para não quebrar o JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// Limpar qualquer output anterior
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/whatsapp-helper.php';
require_once __DIR__ . '/../app/helpers/plan-guard.php';

loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/helpers/auth-helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = getAuthenticatedUser();
$resellerId = $user['id'];

// 🔒 VERIFICAR SE O PLANO ESTÁ ATIVO ANTES DE ENVIAR MENSAGENS
requireActivePlan();

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('Dados inválidos');
        }
        
        // Validar campos obrigatórios
        if (empty($data['phone']) || empty($data['message'])) {
            throw new Exception('Telefone e mensagem são obrigatórios');
        }
        
        $phoneNumber = $data['phone'];
        $message = $data['message'];
        $templateId = $data['template_id'] ?? null;
        $clientId = $data['client_id'] ?? null;
        

        
        // Se tem template_id, processar o template no backend
        if ($templateId && $clientId) {
            // Buscar template
            $template = Database::fetch(
                "SELECT * FROM whatsapp_templates WHERE id = ? AND reseller_id = ?",
                [$templateId, $resellerId]
            );
            
            if ($template) {
                // Buscar dados do cliente (incluindo reseller_id)
                $client = Database::fetch(
                    "SELECT c.*, c.reseller_id FROM clients c WHERE c.id = ?",
                    [$clientId]
                );
                
                if ($client) {
                    // Usar prepareTemplateVariables para gerar todas as variáveis
                    require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';
                    $variables = prepareTemplateVariables($template, $client);
                    
                    // Processar template
                    $message = $template['message'];
                    foreach ($variables as $key => $value) {
                        // Suportar tanto {{variavel}} quanto {variavel}
                        $message = str_replace('{{' . $key . '}}', $value, $message);
                        $message = str_replace('{' . $key . '}', $value, $message);
                    }
                }
            }
        }
        
        // Enviar mensagem
        $result = sendWhatsAppMessage($resellerId, $phoneNumber, $message, $templateId, $clientId);
        
        // Verificar se houve erro
        if (!$result['success']) {
            throw new Exception($result['error'] ?? 'Erro ao enviar mensagem');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem enviada com sucesso',
            'message_id' => $result['message_id'] ?? null
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
