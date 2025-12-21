<?php
/**
 * API para gerenciar notificações de revendedores
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
loadEnv(__DIR__ . '/../.env');

try {
    // Verificar autenticação
    $user = Auth::user();
    
    if (!$user) {
        throw new Exception('Não autenticado');
    }
    
    // Verificar se é admin
    $isAdmin = (isset($user['role']) && strtolower($user['role']) === 'admin') || 
               (isset($user['is_admin']) && $user['is_admin']);
    
    if (!$isAdmin) {
        throw new Exception('Acesso negado. Apenas administradores podem acessar esta funcionalidade.');
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Listar notificações enviadas
        $notifications = Database::fetchAll("
            SELECT 
                wml.*,
                u.name as recipient_name
            FROM whatsapp_messages_log wml
            LEFT JOIN users u ON wml.recipient_id = u.id
            WHERE wml.message_type = 'reseller_renewal'
            ORDER BY wml.sent_at DESC
            LIMIT 100
        ");
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
        
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if ($action === 'run_automation') {
            // Executar script de automação
            $scriptPath = __DIR__ . '/../scripts/reseller-renewal-automation.php';
            
            if (!file_exists($scriptPath)) {
                throw new Exception('Script de automação não encontrado');
            }
            
            // Executar em background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                pclose(popen("start /B php \"$scriptPath\"", "r"));
            } else {
                // Linux/Unix
                exec("php \"$scriptPath\" > /dev/null 2>&1 &");
            }
            
            // Aguardar um pouco para o script processar
            sleep(3);
            
            // Contar mensagens enviadas hoje
            $sentToday = Database::fetch("
                SELECT COUNT(*) as count 
                FROM whatsapp_messages_log 
                WHERE message_type = 'reseller_renewal' 
                AND DATE(sent_at) = CURDATE()
            ")['count'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Automação executada com sucesso',
                'sent' => $sentToday
            ]);
        } else {
            throw new Exception('Ação inválida');
        }
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
