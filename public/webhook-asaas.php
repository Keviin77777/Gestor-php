<?php
/**
 * Webhook do Asaas
 * Recebe notificações de pagamentos aprovados/rejeitados
 */

// Desabilitar exibição de erros em produção
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Função helper para log seguro
function logWebhookAsaas($message) {
    $logFile = __DIR__ . '/../logs/asaas-webhook.log';
    $logDir = dirname($logFile);
    
    // Tentar criar diretório se não existir
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    // Tentar escrever no arquivo, se falhar usar error_log do sistema
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    if (@file_put_contents($logFile, $logMessage, FILE_APPEND) === false) {
        // Fallback: usar error_log do PHP (vai para log do servidor)
        error_log("Asaas Webhook: $message");
    }
}

// Log de todas as requisições
$timestamp = date('Y-m-d H:i:s');
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$body = file_get_contents('php://input');

logWebhookAsaas("\n$method Request");
logWebhookAsaas("Headers: " . json_encode($headers));
logWebhookAsaas("Body: $body");

// Processar webhook
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/AsaasHelper.php';

try {
    $data = json_decode($body, true);
    
    if (!$data) {
        logWebhookAsaas("Erro: JSON inválido");
        http_response_code(400);
        exit;
    }
    
    // Inicializar Asaas
    $asaas = new AsaasHelper();
    
    if (!$asaas->isEnabled()) {
        logWebhookAsaas("Asaas não está habilitado");
        http_response_code(200);
        exit;
    }
    
    // Processar webhook
    $result = $asaas->processWebhook($data);
    
    if (!$result['success']) {
        logWebhookAsaas("Erro ao processar: {$result['error']}");
        http_response_code(200); // Retornar 200 mesmo com erro para não reenviar
        exit;
    }
    
    $paymentId = $result['payment_id'];
    $status = $result['status'];
    $event = $result['event'];
    $externalRef = $result['external_reference'] ?? '';
    
    logWebhookAsaas("Payment ID: $paymentId | Status: $status | Event: $event | Ref: $externalRef");
    
    $db = Database::connect();
    
    // Processar pagamento de fatura
    if (preg_match('/INVOICE_(\d+)/', $externalRef, $matches)) {
        $invoiceId = $matches[1];
        
        logWebhookAsaas("Pagamento de fatura detectado - Invoice: $invoiceId");
        
        // Atualizar status do pagamento na tabela invoice_payments
        $stmt = $db->prepare("
            UPDATE invoice_payments 
            SET status = ?, 
                approved_at = ?,
                updated_at = NOW()
            WHERE payment_id = ?
        ");
        $approvedAt = $status === 'approved' ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$status, $approvedAt, $paymentId]);
        
        if ($status === 'approved') {
            // Marcar fatura como paga
            $stmt = $db->prepare("
                UPDATE invoices 
                SET 
                    status = 'paid',
                    payment_date = NOW(),
                    payment_method = 'pix_asaas',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$invoiceId]);
            
            logWebhookAsaas("✅ Fatura #$invoiceId marcada como PAGA");
            
            // Buscar dados do cliente para renovar acesso
            $stmt = $db->prepare("
                SELECT c.*, i.value, i.due_date, c.reseller_id
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                WHERE i.id = ?
            ");
            $stmt->execute([$invoiceId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($client) {
                // Calcular nova data de renovação (adicionar 30 dias à data atual de renovação)
                $currentRenewal = new DateTime($client['renewal_date']);
                $now = new DateTime();
                
                // Se já venceu, começar de hoje
                if ($currentRenewal < $now) {
                    $currentRenewal = $now;
                }
                
                // Adicionar 30 dias (ou período da fatura)
                $currentRenewal->modify('+30 days');
                
                // Atualizar cliente no gestor
                $stmt = $db->prepare("
                    UPDATE clients 
                    SET 
                        renewal_date = ?,
                        status = 'active',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $currentRenewal->format('Y-m-d'),
                    $client['id']
                ]);
                
                logWebhookAsaas("✅ Cliente #{$client['id']} renovado no gestor até {$currentRenewal->format('Y-m-d')}");
                
                // Renovar cliente no Sigma após pagamento aprovado
                try {
                    require_once __DIR__ . '/../app/helpers/clients-sync-sigma.php';
                    
                    $sigmaResult = renewClientInSigmaAfterPayment($client, $client['reseller_id']);
                    
                    if ($sigmaResult['success']) {
                        logWebhookAsaas("✅ Cliente renovado no Sigma: {$sigmaResult['message']}");
                    } else {
                        logWebhookAsaas("⚠️ Erro na renovação Sigma: {$sigmaResult['message']}");
                    }
                } catch (Exception $e) {
                    logWebhookAsaas("⚠️ Erro ao renovar no Sigma: " . $e->getMessage());
                }
                
                // Enviar mensagem WhatsApp de renovação
                try {
                    require_once __DIR__ . '/../app/helpers/whatsapp-helper.php';
                    
                    $whatsappResult = sendRenewalMessage($client['id'], $invoiceId);
                    
                    if ($whatsappResult['success']) {
                        logWebhookAsaas("✅ Mensagem WhatsApp de renovação enviada");
                    } else {
                        logWebhookAsaas("⚠️ Erro ao enviar WhatsApp: {$whatsappResult['error']}");
                    }
                } catch (Exception $e) {
                    logWebhookAsaas("⚠️ Erro ao enviar mensagem WhatsApp: " . $e->getMessage());
                }
            }
        } elseif ($status === 'cancelled' || $status === 'refunded') {
            logWebhookAsaas("❌ Pagamento da fatura #$invoiceId cancelado/reembolsado");
        }
    }
    // Verificar se é renovação de revendedor
    elseif (preg_match('/RENEW_USER_(\d+)_PLAN_(.+)/', $externalRef, $matches)) {
        $userId = $matches[1];
        $planId = $matches[2];
        
        logWebhookAsaas("Renovação detectada - User: $userId, Plan: $planId");
        
        // Atualizar status do pagamento
        $stmt = $db->prepare("
            UPDATE renewal_payments 
            SET status = ?, updated_at = NOW()
            WHERE payment_id = ? AND payment_provider = 'asaas'
        ");
        $stmt->execute([$status, $paymentId]);
        
        if ($status === 'approved') {
            // Buscar dados do plano
            $stmt = $db->prepare("SELECT * FROM reseller_plans WHERE id = ?");
            $stmt->execute([$planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plan) {
                // Buscar dados do usuário
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Calcular nova data de expiração
                    $currentExpires = new DateTime($user['plan_expires_at'] ?? 'now');
                    $now = new DateTime();
                    
                    // Se o plano já expirou, começar de hoje
                    if ($currentExpires < $now) {
                        $currentExpires = $now;
                    }
                    
                    // Adicionar dias do novo plano
                    $currentExpires->modify("+{$plan['duration_days']} days");
                    
                    // Atualizar usuário
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET 
                            current_plan_id = ?,
                            plan_expires_at = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $plan['id'],
                        $currentExpires->format('Y-m-d H:i:s'),
                        $userId
                    ]);
                    
                    logWebhookAsaas("✅ Renovação aprovada - User: $userId, Novo vencimento: {$currentExpires->format('Y-m-d')}");
                }
            }
        }
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    logWebhookAsaas("ERRO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
