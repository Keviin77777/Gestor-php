<?php
/**
 * API para verificar status do WhatsApp
 */

header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
header('Access-Control-Allow-Origin: *');

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/helpers/auth-helper.php';

$user = getAuthenticatedUser();
$resellerId = $user['id'];

try {
    // Verificar sessão ativa
    $session = Database::fetch(
        "SELECT * FROM whatsapp_sessions WHERE reseller_id = ? AND status = 'connected' ORDER BY connected_at DESC LIMIT 1",
        [$resellerId]
    );
    
    // Verificar configurações
    $settings = Database::fetch(
        "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
        [$resellerId]
    );
    
    // Verificar templates
    $templates = Database::fetchAll(
        "SELECT COUNT(*) as total FROM whatsapp_templates WHERE reseller_id = ? AND is_active = 1",
        [$resellerId]
    );
    
    echo json_encode([
        'success' => true,
        'status' => [
            'session' => $session ? [
                'id' => $session['id'],
                'status' => $session['status'],
                'connected_at' => $session['connected_at']
            ] : null,
            'settings' => $settings ? [
                'id' => $settings['id'],
                'evolution_api_url' => $settings['evolution_api_url'],
                'api_key' => $settings['api_key'] ? 'Configurada' : 'Não configurada'
            ] : null,
            'templates_count' => $templates[0]['total'] ?? 0,
            'has_session' => !empty($session),
            'has_settings' => !empty($settings)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
