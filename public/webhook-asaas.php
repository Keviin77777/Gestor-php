<?php
/**
 * Webhook do Asaas
 * Recebe notificações de pagamentos aprovados/rejeitados
 */

// Log de todas as requisições
$logFile = __DIR__ . '/../logs/asaas-webhook.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$timestamp = date('Y-m-d H:i:s');
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$body = file_get_contents('php://input');

file_put_contents($logFile, "\n[$timestamp] $method Request\n", FILE_APPEND);
file_put_contents($logFile, "Headers: " . json_encode($headers) . "\n", FILE_APPEND);
file_put_contents($logFile, "Body: $body\n", FILE_APPEND);

// Processar webhook
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/AsaasHelper.php';

try {
    $data = json_decode($body, true);
    
    if (!$data) {
        file_put_contents($logFile, "Erro: JSON inválido\n", FILE_APPEND);
        http_response_code(400);
        exit;
    }
    
    // Inicializar Asaas
    $asaas = new AsaasHelper();
    
    if (!$asaas->isEnabled()) {
        file_put_contents($logFile, "Asaas não está habilitado\n", FILE_APPEND);
        http_response_code(200);
        exit;
    }
    
    // Processar webhook
    $result = $asaas->processWebhook($data);
    
    if (!$result['success']) {
        file_put_contents($logFile, "Erro ao processar: {$result['error']}\n", FILE_APPEND);
        http_response_code(200); // Retornar 200 mesmo com erro para não reenviar
        exit;
    }
    
    $paymentId = $result['payment_id'];
    $status = $result['status'];
    $event = $result['event'];
    $externalRef = $result['external_reference'] ?? '';
    
    file_put_contents($logFile, "Payment ID: $paymentId | Status: $status | Event: $event | Ref: $externalRef\n", FILE_APPEND);
    
    $db = Database::connect();
    
    // Processar pagamento de fatura
    if (preg_match('/INVOICE_(\d+)/', $externalRef, $matches)) {
        $invoiceId = $matches[1];
        
        file_put_contents($logFile, "Pagamento de fatura detectado - Invoice: $invoiceId\n", FILE_APPEND);
        
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
            
            file_put_contents($logFile, "✅ Fatura #$invoiceId marcada como PAGA\n", FILE_APPEND);
            
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
                
                file_put_contents($logFile, "✅ Cliente #{$client['id']} renovado no gestor até {$currentRenewal->format('Y-m-d')}\n", FILE_APPEND);
                
                // Renovar cliente no Sigma após pagamento aprovado
                try {
                    require_once __DIR__ . '/../app/helpers/clients-sync-sigma.php';
                    
                    $sigmaResult = renewClientInSigmaAfterPayment($client, $client['reseller_id']);
                    
                    if ($sigmaResult['success']) {
                        file_put_contents($logFile, "✅ Cliente renovado no Sigma: {$sigmaResult['message']}\n", FILE_APPEND);
                    } else {
                        file_put_contents($logFile, "⚠️ Erro na renovação Sigma: {$sigmaResult['message']}\n", FILE_APPEND);
                    }
                } catch (Exception $e) {
                    file_put_contents($logFile, "⚠️ Erro ao renovar no Sigma: " . $e->getMessage() . "\n", FILE_APPEND);
                }
                
                // Enviar mensagem WhatsApp de renovação
                try {
                    require_once __DIR__ . '/../app/helpers/whatsapp-helper.php';
                    
                    $whatsappResult = sendRenewalMessage($client['id'], $invoiceId);
                    
                    if ($whatsappResult['success']) {
                        file_put_contents($logFile, "✅ Mensagem WhatsApp de renovação enviada\n", FILE_APPEND);
                    } else {
                        file_put_contents($logFile, "⚠️ Erro ao enviar WhatsApp: {$whatsappResult['error']}\n", FILE_APPEND);
                    }
                } catch (Exception $e) {
                    file_put_contents($logFile, "⚠️ Erro ao enviar mensagem WhatsApp: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
        } elseif ($status === 'cancelled' || $status === 'refunded') {
            file_put_contents($logFile, "❌ Pagamento da fatura #$invoiceId cancelado/reembolsado\n", FILE_APPEND);
        }
    }
    // Verificar se é renovação de revendedor
    elseif (preg_match('/RENEW_USER_(\d+)_PLAN_(.+)/', $externalRef, $matches)) {
        $userId = $matches[1];
        $planId = $matches[2];
        
        file_put_contents($logFile, "Renovação detectada - User: $userId, Plan: $planId\n", FILE_APPEND);
        
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
                    
                    file_put_contents($logFile, "✅ Renovação aprovada - User: $userId, Novo vencimento: {$currentExpires->format('Y-m-d')}\n", FILE_APPEND);
                }
            }
        }
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    file_put_contents($logFile, "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
