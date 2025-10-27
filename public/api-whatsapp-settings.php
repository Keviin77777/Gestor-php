<?php
/**
 * API para configurações do WhatsApp
 */

header('Content-Type: application/json');
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
            // Buscar configurações do reseller
            $settings = Database::fetch(
                "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
                [$resellerId]
            );
            
            if (!$settings) {
                // Criar configurações padrão se não existir
                $settingsId = 'ws-' . uniqid();
                Database::query(
                    "INSERT INTO whatsapp_settings (id, reseller_id, evolution_api_url, reminder_days) 
                     VALUES (?, ?, 'http://localhost:8081', JSON_ARRAY(3, 7))",
                    [$settingsId, $resellerId]
                );
                
                $settings = Database::fetch(
                    "SELECT * FROM whatsapp_settings WHERE id = ?",
                    [$settingsId]
                );
            }
            
            // Buscar sessão ativa
            $session = Database::fetch(
                "SELECT * FROM whatsapp_sessions WHERE reseller_id = ? ORDER BY created_at DESC LIMIT 1",
                [$resellerId]
            );
            
            echo json_encode([
                'success' => true,
                'settings' => [
                    'id' => $settings['id'],
                    'evolution_api_url' => $settings['evolution_api_url'],
                    'evolution_api_key' => $settings['evolution_api_key'],
                    'instance_name' => $session['instance_name'] ?? 'ultragestor-' . $resellerId,
                    'auto_send_welcome' => (bool)$settings['auto_send_welcome'],
                    'auto_send_invoice' => (bool)$settings['auto_send_invoice'],
                    'auto_send_renewal' => (bool)$settings['auto_send_renewal'],
                    'auto_send_reminders' => (bool)$settings['auto_send_reminders'],
                    'reminder_days' => json_decode($settings['reminder_days'] ?? '[3,7]', true),
                    'business_hours_start' => $settings['business_hours_start'],
                    'business_hours_end' => $settings['business_hours_end'],
                    'send_only_business_hours' => (bool)$settings['send_only_business_hours']
                ]
            ]);
            break;
            
        case 'POST':
            // Atualizar configurações
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception('Dados inválidos');
            }
            
            // Buscar configurações existentes
            $existingSettings = Database::fetch(
                "SELECT id FROM whatsapp_settings WHERE reseller_id = ?",
                [$resellerId]
            );
            
            if ($existingSettings) {
                // Atualizar configurações existentes
                Database::query(
                    "UPDATE whatsapp_settings SET 
                     evolution_api_url = ?, 
                     evolution_api_key = ?,
                     auto_send_welcome = ?,
                     auto_send_invoice = ?,
                     auto_send_renewal = ?,
                     auto_send_reminders = ?,
                     reminder_days = ?,
                     business_hours_start = ?,
                     business_hours_end = ?,
                     send_only_business_hours = ?,
                     updated_at = CURRENT_TIMESTAMP
                     WHERE reseller_id = ?",
                    [
                        $data['evolution_api_url'] ?? 'http://localhost:8081',
                        $data['evolution_api_key'] ?? null,
                        $data['auto_send_welcome'] ?? true,
                        $data['auto_send_invoice'] ?? true,
                        $data['auto_send_renewal'] ?? true,
                        $data['auto_send_reminders'] ?? true,
                        json_encode($data['reminder_days'] ?? [3, 7]),
                        $data['business_hours_start'] ?? '08:00:00',
                        $data['business_hours_end'] ?? '18:00:00',
                        $data['send_only_business_hours'] ?? false,
                        $resellerId
                    ]
                );
            } else {
                // Criar novas configurações
                $settingsId = 'ws-' . uniqid();
                Database::query(
                    "INSERT INTO whatsapp_settings (
                        id, reseller_id, evolution_api_url, evolution_api_key,
                        auto_send_welcome, auto_send_invoice, auto_send_renewal, auto_send_reminders,
                        reminder_days, business_hours_start, business_hours_end, send_only_business_hours
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $settingsId,
                        $resellerId,
                        $data['evolution_api_url'] ?? 'http://localhost:8081',
                        $data['evolution_api_key'] ?? null,
                        $data['auto_send_welcome'] ?? true,
                        $data['auto_send_invoice'] ?? true,
                        $data['auto_send_renewal'] ?? true,
                        $data['auto_send_reminders'] ?? true,
                        json_encode($data['reminder_days'] ?? [3, 7]),
                        $data['business_hours_start'] ?? '08:00:00',
                        $data['business_hours_end'] ?? '18:00:00',
                        $data['send_only_business_hours'] ?? false
                    ]
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Configurações salvas com sucesso'
            ]);
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
?>