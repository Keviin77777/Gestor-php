<?php
/**
 * Webhook do Mercado Pago
 * Recebe notificações de pagamentos aprovados/rejeitados
 */

// Desabilitar exibição de erros em produção
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Função helper para log seguro
function logWebhook($message) {
    $logFile = __DIR__ . '/../logs/mercadopago-webhook.log';
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
        error_log("MercadoPago Webhook: $message");
    }
}

// Log de todas as requisições
$timestamp = date('Y-m-d H:i:s');
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$body = file_get_contents('php://input');

logWebhook("\n$method Request");
logWebhook("Headers: " . json_encode($headers));
logWebhook("Body: $body");

// Processar webhook
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/MercadoPagoHelper.php';

try {
    $data = json_decode($body, true);
    
    if (!$data) {
        logWebhook("Erro: JSON inválido");
        http_response_code(400);
        exit;
    }
    
    // Extrair payment ID do webhook
    $paymentId = $data['data']['id'] ?? null;
    
    if (!$paymentId) {
        logWebhook("Erro: Payment ID não encontrado no webhook");
        http_response_code(200);
        exit;
    }
    
    // Primeiro, tentar identificar o revendedor através do external_reference
    // Para isso, vamos buscar na tabela invoice_payments
    $stmt = $db->prepare("
        SELECT ip.*, i.reseller_id 
        FROM invoice_payments ip
        JOIN invoices i ON ip.invoice_id = i.id
        WHERE ip.payment_id = ?
        LIMIT 1
    ");
    $stmt->execute([$paymentId]);
    $paymentRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $resellerId = null;
    $mpCredentials = null;
    
    if ($paymentRecord) {
        // Encontrou o pagamento na nossa base, usar credenciais do revendedor
        $resellerId = $paymentRecord['reseller_id'];
        
        $stmt = $db->prepare("
            SELECT public_key, access_token 
            FROM payment_methods 
            WHERE reseller_id = ? AND provider = 'mercadopago' AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$resellerId]);
        $mpCredentials = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Se não encontrou credenciais específicas, usar configuração global como fallback
    if (!$mpCredentials) {
        logWebhook("Usando configuração global do Mercado Pago como fallback");
        $mp = new MercadoPagoHelper();
    } else {
        logWebhook("Usando credenciais do revendedor: {$resellerId}");
        $mp = new MercadoPagoHelper($mpCredentials['public_key'], $mpCredentials['access_token']);
    }
    
    // Processar webhook
    $result = $mp->processWebhook($data);
    
    if (!$result['success']) {
        logWebhook("Erro ao processar: {$result['error']}");
        http_response_code(200); // Retornar 200 mesmo com erro para não reenviar
        exit;
    }
    
    $paymentId = $result['payment_id'];
    $status = $result['status'];
    $externalRef = $result['external_reference'] ?? '';
    
    logWebhook("Payment ID: $paymentId | Status: $status | Ref: $externalRef");
    
    $db = Database::connect();
    
    // Verificar tipo de pagamento pelos metadados
    $metadata = $result['metadata'] ?? [];
    $paymentType = $metadata['type'] ?? '';
    
    // Processar pagamento de fatura
    if ($paymentType === 'invoice_payment' && isset($metadata['invoice_id'])) {
        $invoiceId = $metadata['invoice_id'];
        
        logWebhook("Pagamento de fatura detectado - Invoice: $invoiceId");
        
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
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$invoiceId]);
            
            logWebhook("✅ Fatura #$invoiceId marcada como PAGA");
            
            // Buscar dados do cliente para renovar acesso
            $stmt = $db->prepare("
                SELECT c.*, i.value, i.due_date, c.reseller_id, p.duration_days
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                LEFT JOIN plans p ON c.plan_id = p.id
                WHERE i.id = ?
            ");
            $stmt->execute([$invoiceId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($client) {
                // Buscar duração do plano (padrão 30 dias se não encontrar)
                $durationDays = $client['duration_days'] ?? 30;
                
                logWebhook("📅 Duração do plano: {$durationDays} dias");
                
                // Calcular nova data de renovação
                $currentRenewal = new DateTime($client['renewal_date']);
                $now = new DateTime();
                
                // Se já venceu, começar de hoje
                if ($currentRenewal < $now) {
                    $currentRenewal = $now;
                }
                
                // Adicionar dias conforme duração do plano
                $currentRenewal->modify("+{$durationDays} days");
                
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
                
                logWebhook("✅ Cliente #{$client['id']} renovado no gestor até {$currentRenewal->format('Y-m-d')}");
                
                // Renovar cliente no Sigma após pagamento aprovado
                try {
                    require_once __DIR__ . '/../app/helpers/clients-sync-sigma.php';
                    
                    $sigmaResult = renewClientInSigmaAfterPayment($client, $client['reseller_id']);
                    
                    if ($sigmaResult['success']) {
                        logWebhook("✅ Cliente renovado no Sigma: {$sigmaResult['message']}");
                    } else {
                        logWebhook("⚠️ Erro na renovação Sigma: {$sigmaResult['message']}");
                    }
                } catch (Exception $e) {
                    logWebhook("⚠️ Erro ao renovar no Sigma: " . $e->getMessage());
                }
                
                // Enviar mensagem WhatsApp de renovação
                try {
                    require_once __DIR__ . '/../app/helpers/whatsapp-helper.php';
                    
                    $whatsappResult = sendRenewalMessage($client['id'], $invoiceId);
                    
                    if ($whatsappResult['success']) {
                        logWebhook("✅ Mensagem WhatsApp de renovação enviada");
                    } else {
                        logWebhook("⚠️ Erro ao enviar WhatsApp: {$whatsappResult['error']}");
                    }
                } catch (Exception $e) {
                    logWebhook("⚠️ Erro ao enviar mensagem WhatsApp: " . $e->getMessage());
                }
            }
        } elseif ($status === 'rejected' || $status === 'cancelled') {
            logWebhook("❌ Pagamento da fatura #$invoiceId rejeitado/cancelado");
        }
    }
    // Verificar se é renovação de revendedor
    elseif (preg_match('/RENEW_USER_(\d+)_PLAN_(.+)/', $externalRef, $matches)) {
        $userId = $matches[1];
        $planId = $matches[2];
        
        logWebhook("Renovação detectada - User: $userId, Plan: $planId");
        
        // Atualizar status do pagamento
        $stmt = $db->prepare("
            UPDATE renewal_payments 
            SET status = ?, updated_at = NOW()
            WHERE payment_id = ?
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
                            plan_status = 'active',
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $plan['id'],
                        $currentExpires->format('Y-m-d H:i:s'),
                        $userId
                    ]);
                    
                    logWebhook("✅ Renovação aprovada - User: $userId, Novo vencimento: {$currentExpires->format('Y-m-d')}");
                }
            }
        }
    }
    // Extrair ID da fatura
    elseif (preg_match('/INVOICE_(\d+)/', $externalRef, $matches)) {
        $invoiceId = $matches[1];
        
        // Buscar fatura com dados do cliente
        $stmt = $db->prepare("
            SELECT i.*, c.*, c.id as client_id
            FROM invoices i
            JOIN clients c ON i.client_id = c.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            logWebhook("Fatura #$invoiceId não encontrada");
            http_response_code(200);
            exit;
        }
        
        // Atualizar status conforme pagamento
        if ($status === 'approved') {
            // Pagamento aprovado - marcar fatura como paga
            $stmt = $db->prepare("
                UPDATE invoices 
                SET 
                    status = 'paid',
                    payment_date = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$invoiceId]);
            
            logWebhook("✅ Fatura #$invoiceId marcada como PAGA");
            
            // Buscar duração do plano do cliente
            $stmt = $db->prepare("
                SELECT p.duration_days 
                FROM clients c
                LEFT JOIN plans p ON c.plan_id = p.id
                WHERE c.id = ?
            ");
            $stmt->execute([$invoice['client_id']]);
            $planData = $stmt->fetch(PDO::FETCH_ASSOC);
            $durationDays = $planData['duration_days'] ?? 30;
            
            logWebhook("📅 Duração do plano: {$durationDays} dias");
            
            // Renovar cliente automaticamente
            $currentRenewal = new DateTime($invoice['renewal_date']);
            $now = new DateTime();
            
            // Se já venceu, começar de hoje
            if ($currentRenewal < $now) {
                $currentRenewal = $now;
            }
            
            // Adicionar dias conforme duração do plano
            $currentRenewal->modify("+{$durationDays} days");
            
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
                $invoice['client_id']
            ]);
            
            logWebhook("✅ Cliente #{$invoice['client_id']} renovado no gestor até {$currentRenewal->format('Y-m-d')}");
            
            // Renovar cliente no Sigma após pagamento aprovado
            try {
                require_once __DIR__ . '/../app/helpers/clients-sync-sigma.php';
                
                // Preparar dados do cliente para renovação
                $clientData = [
                    'id' => $invoice['client_id'],
                    'name' => $invoice['name'],
                    'email' => $invoice['email'],
                    'phone' => $invoice['phone'],
                    'username' => $invoice['username'],
                    'iptv_password' => $invoice['iptv_password'],
                    'password' => $invoice['password'],
                    'notes' => $invoice['notes'],
                    'status' => 'active',
                    'renewal_date' => $currentRenewal->format('Y-m-d')
                ];
                
                $sigmaResult = renewClientInSigmaAfterPayment($clientData, $invoice['reseller_id']);
                
                if ($sigmaResult['success']) {
                    logWebhook("✅ Cliente renovado no Sigma: {$sigmaResult['message']}");
                } else {
                    logWebhook("⚠️ Erro na renovação Sigma: {$sigmaResult['message']}");
                }
            } catch (Exception $e) {
                logWebhook("⚠️ Erro ao renovar no Sigma: " . $e->getMessage());
            }
            
            // Enviar mensagem WhatsApp de renovação
            try {
                require_once __DIR__ . '/../app/helpers/whatsapp-helper.php';
                
                $whatsappResult = sendRenewalMessage($invoice['client_id'], $invoiceId);
                
                if ($whatsappResult['success']) {
                    logWebhook("✅ Mensagem WhatsApp de renovação enviada");
                } else {
                    logWebhook("⚠️ Erro ao enviar WhatsApp: {$whatsappResult['error']}");
                }
            } catch (Exception $e) {
                logWebhook("⚠️ Erro ao enviar mensagem WhatsApp: " . $e->getMessage());
            }
            
        } elseif ($status === 'rejected' || $status === 'cancelled') {
            // Pagamento rejeitado/cancelado
            $stmt = $db->prepare("
                UPDATE invoices 
                SET 
                    status = 'cancelled',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$invoiceId]);
            
            logWebhook("❌ Fatura #$invoiceId marcada como CANCELADA");
            
        } elseif ($status === 'pending' || $status === 'in_process') {
            // Pagamento pendente
            logWebhook("⏳ Fatura #$invoiceId aguardando pagamento");
        }
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    logWebhook("ERRO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
