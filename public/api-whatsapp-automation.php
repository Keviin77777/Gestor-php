<?php
/**
 * API para Automação de WhatsApp
 * Endpoint para executar automações manualmente
 */

header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';

loadEnv(__DIR__ . '/../.env');

$method = $_SERVER['REQUEST_METHOD'];
$resellerId = 'admin-001'; // Por enquanto fixo

try {
    if ($method === 'GET') {
        // Executar automação de lembretes
        $report = runWhatsAppReminderAutomation();
        
        echo json_encode([
            'success' => true,
            'message' => 'Automação executada com sucesso',
            'report' => $report
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('Dados inválidos');
        }
        
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'send_reminder':
                $clientId = $data['client_id'] ?? null;
                if (!$clientId) {
                    throw new Exception('ID do cliente é obrigatório');
                }
                
                $result = checkAndSendReminder($clientId);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            case 'send_invoice':
                $invoiceId = $data['invoice_id'] ?? null;
                if (!$invoiceId) {
                    throw new Exception('ID da fatura é obrigatório');
                }
                
                $result = sendAutomaticInvoiceMessage($invoiceId);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            case 'send_renewal':
                $clientId = $data['client_id'] ?? null;
                $invoiceId = $data['invoice_id'] ?? null;
                if (!$clientId || !$invoiceId) {
                    throw new Exception('ID do cliente e da fatura são obrigatórios');
                }
                
                $result = sendAutomaticRenewalMessage($clientId, $invoiceId);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            default:
                throw new Exception('Ação não reconhecida');
        }
        
    } else {
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    error_log("WhatsApp Automation API - Erro: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
