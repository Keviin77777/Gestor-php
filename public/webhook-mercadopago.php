<?php
/**
 * Webhook do Mercado Pago
 * Recebe notificações de pagamentos aprovados/rejeitados
 */

// Log de todas as requisições
$logFile = __DIR__ . '/../logs/mercadopago-webhook.log';
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
require_once __DIR__ . '/../app/helpers/MercadoPagoHelper.php';

try {
    $data = json_decode($body, true);
    
    if (!$data) {
        file_put_contents($logFile, "Erro: JSON inválido\n", FILE_APPEND);
        http_response_code(400);
        exit;
    }
    
    // Extrair payment ID do webhook
    $paymentId = $data['data']['id'] ?? null;
    
    if (!$paymentId) {
        file_put_contents($logFile, "Erro: Payment ID não encontrado no webhook\n", FILE_APPEND);
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
        file_put_contents($logFile, "Usando configuração global do Mercado Pago como fallback\n", FILE_APPEND);
        $mp = new MercadoPagoHelper();
    } else {
        file_put_contents($logFile, "Usando credenciais do revendedor: {$resellerId}\n", FILE_APPEND);
        $mp = new MercadoPagoHelper($mpCredentials['public_key'], $mpCredentials['access_token']);
    }
    
    // Processar webhook
    $result = $mp->processWebhook($data);
    
    if (!$result['success']) {
        file_put_contents($logFile, "Erro ao processar: {$result['error']}\n", FILE_APPEND);
        http_response_code(200); // Retornar 200 mesmo com erro para não reenviar
        exit;
    }
    
    $paymentId = $result['payment_id'];
    $status = $result['status'];
    $externalRef = $result['external_reference'] ?? '';
    
    file_put_contents($logFile, "Payment ID: $paymentId | Status: $status | Ref: $externalRef\n", FILE_APPEND);
    
    $db = Database::connect();
    
    // Verificar tipo de pagamento pelos metadados
    $metadata = $result['metadata'] ?? [];
    $paymentType = $metadata['type'] ?? '';
    
    // Processar pagamento de fatura
    if ($paymentType === 'invoice_payment' && isset($metadata['invoice_id'])) {
        $invoiceId = $metadata['invoice_id'];
        
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
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$invoiceId]);
            
            file_put_contents($logFile, "✅ Fatura #$invoiceId marcada como PAGA\n", FILE_APPEND);
            
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
                
                file_put_contents($logFile, "📅 Duração do plano: {$durationDays} dias\n", FILE_APPEND);
                
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
        } elseif ($status === 'rejected' || $status === 'cancelled') {
            file_put_contents($logFile, "❌ Pagamento da fatura #$invoiceId rejeitado/cancelado\n", FILE_APPEND);
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
            file_put_contents($logFile, "Fatura #$invoiceId não encontrada\n", FILE_APPEND);
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
            
            file_put_contents($logFile, "✅ Fatura #$invoiceId marcada como PAGA\n", FILE_APPEND);
            
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
            
            file_put_contents($logFile, "📅 Duração do plano: {$durationDays} dias\n", FILE_APPEND);
            
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
            
            file_put_contents($logFile, "✅ Cliente #{$invoice['client_id']} renovado no gestor até {$currentRenewal->format('Y-m-d')}\n", FILE_APPEND);
            
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
                
                $whatsappResult = sendRenewalMessage($invoice['client_id'], $invoiceId);
                
                if ($whatsappResult['success']) {
                    file_put_contents($logFile, "✅ Mensagem WhatsApp de renovação enviada\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, "⚠️ Erro ao enviar WhatsApp: {$whatsappResult['error']}\n", FILE_APPEND);
                }
            } catch (Exception $e) {
                file_put_contents($logFile, "⚠️ Erro ao enviar mensagem WhatsApp: " . $e->getMessage() . "\n", FILE_APPEND);
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
            
            file_put_contents($logFile, "❌ Fatura #$invoiceId marcada como CANCELADA\n", FILE_APPEND);
            
        } elseif ($status === 'pending' || $status === 'in_process') {
            // Pagamento pendente
            file_put_contents($logFile, "⏳ Fatura #$invoiceId aguardando pagamento\n", FILE_APPEND);
        }
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    file_put_contents($logFile, "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
