<?php
// Desabilitar exibição de erros para evitar HTML na resposta JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Iniciar output buffering para capturar qualquer output inesperado
ob_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit();
}

try {
    require_once __DIR__ . '/../app/core/Database.php';
    require_once __DIR__ . '/../app/helpers/functions.php';
    
    loadEnv(__DIR__ . '/../.env');
    
    $pdo = Database::connect();
    
    // Verificar autenticação
    require_once __DIR__ . '/../app/helpers/auth-helper.php';
    $user = getAuthenticatedUser();
    
    // Limpar qualquer output que possa ter sido gerado antes
    ob_clean();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    
    // Extrair ID e ação (suportar query parameter e path parameter)
    $invoiceId = $_GET['id'] ?? null;
    $action = $_GET['action'] ?? null;
    
    // Se não veio por query parameter, tentar path parameter (retrocompatibilidade)
    if (!$invoiceId && count($pathParts) > 1) {
        $lastPart = $pathParts[count($pathParts) - 1];
        $secondLastPart = count($pathParts) > 2 ? $pathParts[count($pathParts) - 2] : null;
        
        if ($lastPart === 'mark-paid' && $secondLastPart) {
            $invoiceId = $secondLastPart;
            $action = 'mark-paid';
        } elseif ($lastPart !== 'invoices' && $lastPart !== 'api-invoices.php') {
            $invoiceId = $lastPart;
        }
    }
    
    if ($method === 'PUT' && $action === 'mark-paid') {
        // Buscar informações da fatura antes da atualização
        $invoice = Database::fetch(
            "SELECT client_id, status FROM invoices WHERE id = ? AND reseller_id = ?",
            [$invoiceId, $user['id']]
        );
        
        if (!$invoice) {
            throw new Exception('Fatura não encontrada');
        }
        
        $oldStatus = $invoice['status'];
        $clientId = $invoice['client_id'];
        
        // Marcar como paga
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET status = 'paid', payment_date = NOW(), updated_at = NOW() 
            WHERE id = ? AND reseller_id = ?
        ");
        
        if ($stmt->execute([$invoiceId, $user['id']])) {
            $clientRenewed = false;
            $sigmaResult = null;
            
            // Se o status mudou para "paid", renovar o cliente
            if ($oldStatus !== 'paid') {
                // Buscar data de vencimento atual do cliente
                $client = Database::fetch(
                    "SELECT renewal_date FROM clients WHERE id = ? AND reseller_id = ?",
                    [$clientId, $user['id']]
                );
                
                if ($client) {
                    // Adicionar 30 dias à data de vencimento atual
                    $currentRenewalDate = $client['renewal_date'];
                    $newRenewalDate = date('Y-m-d', strtotime($currentRenewalDate . ' +30 days'));
                    
                    // Atualizar data de renovação do cliente
                    Database::query(
                        "UPDATE clients SET renewal_date = ? WHERE id = ? AND reseller_id = ?",
                        [$newRenewalDate, $clientId, $user['id']]
                    );
                    
                    // Registrar log de renovação
                    $logId = 'log-' . uniqid();
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
                    
                    Database::query(
                        "INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, new_values, ip_address, user_agent) 
                         VALUES (?, ?, 'client_renewed', 'client', ?, ?, ?, ?)",
                        [
                            $logId,
                            $user['id'],
                            $clientId,
                            json_encode([
                                'old_renewal_date' => $currentRenewalDate,
                                'new_renewal_date' => $newRenewalDate,
                                'invoice_id' => $invoiceId,
                                'reason' => 'payment_confirmed_invoice'
                            ]),
                            $ipAddress,
                            $userAgent
                        ]
                    );
                    
                    $clientRenewed = true;
                    
                    // Sincronizar renovação com Sigma
                    try {
                        error_log("🔄 INICIANDO SINCRONIZAÇÃO SIGMA - RENOVAÇÃO POR PAGAMENTO");
                        error_log("Cliente ID: {$clientId}");
                        error_log("Fatura ID: {$invoiceId}");
                        
                        require_once __DIR__ . '/../app/helpers/clients-sync-sigma.php';
                        
                        // Buscar dados completos do cliente
                        $clientData = Database::fetch(
                            "SELECT * FROM clients WHERE id = ? AND reseller_id = ?",
                            [$clientId, $user['id']]
                        );
                        
                        if ($clientData) {
                            $sigmaResult = syncClientWithSigmaAfterSave($clientData, $user['id']);
                            
                            if ($sigmaResult['success']) {
                                error_log("✅ Cliente renovado no Sigma com sucesso: " . $sigmaResult['message']);
                            } else {
                                error_log("❌ Erro ao renovar cliente no Sigma: " . $sigmaResult['message']);
                            }
                        } else {
                            error_log("⚠️ Cliente não encontrado para sincronização Sigma");
                            $sigmaResult = ['success' => false, 'message' => 'Cliente não encontrado'];
                        }
                    } catch (Exception $e) {
                        error_log("❌ Exceção na sincronização Sigma: " . $e->getMessage());
                    }
                    
                    // Enviar mensagem WhatsApp de renovação
                    try {
                        error_log("=== INICIANDO ENVIO DE WHATSAPP DE RENOVAÇÃO (FATURA) ===");
                        error_log("Cliente ID: {$clientId}");
                        error_log("Fatura ID: {$invoiceId}");
                        error_log("Reseller ID: {$user['id']}");
                        
                        require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';
                        $whatsappResult = sendAutomaticRenewalMessage($clientId, $invoiceId);
                        
                        error_log("Resultado do envio: " . json_encode($whatsappResult));
                        
                        if ($whatsappResult['success']) {
                            error_log("✅ WhatsApp de renovação enviado com sucesso para cliente {$clientId}: {$whatsappResult['message_id']}");
                        } else {
                            error_log("❌ Erro ao enviar WhatsApp de renovação para cliente {$clientId}: {$whatsappResult['error']}");
                        }
                    } catch (Exception $e) {
                        error_log("❌ Exceção ao enviar WhatsApp de renovação para cliente {$clientId}: " . $e->getMessage());
                        error_log("Stack trace: " . $e->getTraceAsString());
                    }
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'Fatura marcada como paga com sucesso',
                'client_renewed' => $clientRenewed
            ];
            
            // Adicionar informação sobre sincronização Sigma se disponível
            if ($clientRenewed && isset($sigmaResult)) {
                $response['sigma_sync'] = $sigmaResult;
                if ($sigmaResult['success']) {
                    $response['message'] .= ' - Cliente renovado no Sigma';
                } else {
                    $response['message'] .= ' - Erro na sincronização Sigma: ' . $sigmaResult['message'];
                }
            }
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            throw new Exception('Erro ao atualizar fatura');
        }
        exit;
    }
    
    if ($method === 'DELETE' && $invoiceId) {
        // Excluir fatura
        $stmt = $pdo->prepare("
            DELETE FROM invoices 
            WHERE id = ? AND reseller_id = ?
        ");
        
        if ($stmt->execute([$invoiceId, $user['id']])) {
            echo json_encode([
                'success' => true,
                'message' => 'Fatura excluída com sucesso'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            throw new Exception('Erro ao excluir fatura');
        }
        exit;
    }
    
    // Listar faturas (GET)
    $clientId = $_GET['client_id'] ?? null;
    
    if ($clientId) {
        // Filtrar por cliente específico
        $stmt = $pdo->prepare("
            SELECT i.*, c.name as client_name 
            FROM invoices i
            LEFT JOIN clients c ON i.client_id = c.id
            WHERE i.reseller_id = ? AND i.client_id = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$user['id'], $clientId]);
    } else {
        // Listar todas as faturas do reseller
        $stmt = $pdo->prepare("
            SELECT i.*, c.name as client_name 
            FROM invoices i
            LEFT JOIN clients c ON i.client_id = c.id
            WHERE i.reseller_id = ? 
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$user['id']]);
    }
    
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Resumo simples
    $summary = [
        'pending' => ['count' => 0, 'amount' => 0],
        'paid' => ['count' => 0, 'amount' => 0],
        'overdue' => ['count' => 0, 'amount' => 0],
        'total' => ['count' => count($invoices), 'amount' => 0]
    ];
    
    foreach ($invoices as $invoice) {
        $summary['total']['amount'] += $invoice['final_value'];
        
        if ($invoice['status'] === 'paid') {
            $summary['paid']['count']++;
            $summary['paid']['amount'] += $invoice['final_value'];
        } else {
            $summary['pending']['count']++;
            $summary['pending']['amount'] += $invoice['final_value'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'invoices' => $invoices,
        'summary' => $summary
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    // Limpar qualquer output antes de enviar erro
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Error $e) {
    // Capturar erros fatais também
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Garantir que não há output extra
ob_end_flush();
?>