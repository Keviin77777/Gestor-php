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
    
    // Processar notificação
    $mp = new MercadoPagoHelper();
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
                    paid_at = NOW(),
                    payment_method = 'pix_mercadopago',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$invoiceId]);
            
            file_put_contents($logFile, "✅ Fatura #$invoiceId marcada como PAGA\n", FILE_APPEND);
            
            // Buscar dados do cliente para renovar acesso
            $stmt = $db->prepare("
                SELECT c.*, i.value, i.due_date
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
                
                // Atualizar cliente
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
                
                file_put_contents($logFile, "✅ Cliente #{$client['id']} renovado até {$currentRenewal->format('Y-m-d')}\n", FILE_APPEND);
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
        
        // Buscar fatura
        $stmt = $db->prepare("SELECT * FROM invoices WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            file_put_contents($logFile, "Fatura #$invoiceId não encontrada\n", FILE_APPEND);
            http_response_code(200);
            exit;
        }
        
        // Atualizar status conforme pagamento
        if ($status === 'approved') {
            // Pagamento aprovado
            $stmt = $db->prepare("
                UPDATE invoices 
                SET 
                    status = 'paid',
                    paid_at = NOW(),
                    payment_method = 'pix_mercadopago',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$invoiceId]);
            
            file_put_contents($logFile, "✅ Fatura #$invoiceId marcada como PAGA\n", FILE_APPEND);
            
            // TODO: Enviar email de confirmação
            // TODO: Ativar serviços do cliente
            
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
