<?php
/**
 * API para Automação de Faturas
 * Endpoint para executar automação manual e verificar status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/invoice-automation.php';

loadEnv(__DIR__ . '/../.env');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Verificar clientes elegíveis para automação
            $eligibleClients = Database::fetchAll(
                "SELECT 
                    c.id, 
                    c.name, 
                    c.renewal_date, 
                    c.value,
                    c.plan,
                    c.server,
                    DATEDIFF(c.renewal_date, CURDATE()) as days_until_renewal,
                    (SELECT COUNT(*) FROM invoices i 
                     WHERE i.client_id = c.id 
                     AND i.status IN ('pending', 'overdue') 
                     AND MONTH(i.issue_date) = MONTH(CURDATE()) 
                     AND YEAR(i.issue_date) = YEAR(CURDATE())
                    ) as pending_invoices_this_month
                 FROM clients c
                 WHERE c.reseller_id = 'admin-001' 
                 AND c.status = 'active'
                 AND DATEDIFF(c.renewal_date, CURDATE()) <= 10
                 AND DATEDIFF(c.renewal_date, CURDATE()) >= 0
                 ORDER BY c.renewal_date ASC"
            );
            
            // Separar clientes que precisam de fatura dos que já têm
            $needsInvoice = [];
            $hasInvoice = [];
            
            foreach ($eligibleClients as $client) {
                if ($client['pending_invoices_this_month'] == 0) {
                    $needsInvoice[] = $client;
                } else {
                    $hasInvoice[] = $client;
                }
            }
            
            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_eligible' => count($eligibleClients),
                    'needs_invoice' => count($needsInvoice),
                    'has_invoice' => count($hasInvoice)
                ],
                'clients_needing_invoice' => $needsInvoice,
                'clients_with_invoice' => $hasInvoice
            ]);
            break;
            
        case 'POST':
            // Executar automação de faturas
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? 'run_automation';
            
            if ($action === 'run_automation') {
                $report = runInvoiceAutomation();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Automação executada com sucesso',
                    'report' => $report
                ]);
            } elseif ($action === 'generate_for_client') {
                // Gerar fatura para cliente específico
                $clientId = $data['client_id'] ?? null;
                
                if (!$clientId) {
                    throw new Exception('ID do cliente é obrigatório');
                }
                
                // Buscar dados do cliente
                $client = Database::fetch(
                    "SELECT id, name, value, renewal_date FROM clients WHERE id = ? AND reseller_id = 'admin-001'",
                    [$clientId]
                );
                
                if (!$client) {
                    throw new Exception('Cliente não encontrado');
                }
                
                $result = processClientInvoiceAutomation($client);
                
                echo json_encode([
                    'success' => true,
                    'client_id' => $clientId,
                    'result' => $result
                ]);
            } else {
                throw new Exception('Ação não reconhecida');
            }
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