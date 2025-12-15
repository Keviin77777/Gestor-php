<?php
/**
 * Webhook do Ciabra
 * Recebe notificações de pagamentos aprovados/rejeitados
 */

// Log de todas as requisições
$logFile = __DIR__ . '/../logs/ciabra-webhook.log';
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
require_once __DIR__ . '/../app/helpers/CiabraHelper.php';

try {
    $data = json_decode($body, true);
    
    if (!$data) {
        file_put_contents($logFile, "Erro: JSON inválido\n", FILE_APPEND);
        http_response_code(400);
        exit;
    }
    
    // Extrair payment ID do webhook
    $paymentId = $data['charge']['id'] ?? $data['charge']['charge_id'] ?? $data['data']['id'] ?? null;
    
    if (!$paymentId) {
        file_put_contents($logFile, "Erro: Payment ID não encontrado no webhook\n", FILE_APPEND);
        http_response_code(200);
        exit;
    }
    
    file_put_contents($logFile, "Payment ID: $paymentId\n", FILE_APPEND);
    
    // Buscar o pagamento na nossa base para identificar o revendedor
    $db = Database::connect();
    $stmt = $db->prepare("
        SELECT ip.*, i.reseller_id, i.client_id
        FROM invoice_payments ip
        JOIN invoices i ON ip.invoice_id = i.id
        WHERE ip.payment_id = ? AND ip.payment_provider = 'ciabra'
        LIMIT 1
    ");
    $stmt->execute([$paymentId]);
    $paymentRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paymentRecord) {
        file_put_contents($logFile, "Pagamento não encontrado na base de dados\n", FILE_APPEND);
        http_response_code(200);
        exit;
    }
    
    $resellerId = $paymentRecord['reseller_id'];
    $invoiceId = $paymentRecord['invoice_id'];
    $clientId = $paymentRecord['client_id'];
    
    file_put_contents($logFile, "Reseller: $resellerId | Invoice: $invoiceId | Client: $clientId\n", FILE_APPEND);
    
    // Buscar credenciais do revendedor
    $stmt = $db->prepare("
        SELECT config_value 
        FROM payment_methods 
        WHERE reseller_id = ? AND method_name = 'ciabra' AND enabled = 1
        LIMIT 1
    ");
    $stmt->execute([$resellerId]);
    $methodConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$methodConfig) {
        file_put_contents($logFile, "Configuração Ciabra não encontrada para reseller $resellerId\n", FILE_APPEND);
        http_response_code(200);
        exit;
    }
    
    $config = json_decode($methodConfig['config_value'], true);
    $ciabra = new CiabraHelper($config['api_key']);
    
    // Processar webhook
    $result = $ciabra->processWebhook($data);
    
    if (!$result['success']) {
        file_put_contents($logFile, "Erro ao processar: {$result['error']}\n", FILE_APPEND);
        http_response_code(200);
        exit;
    }
    
    $status = $result['status'];
    $event = $result['event'];
    
    file_put_contents($logFile, "Status: $status | Event: $event\n", FILE_APPEND);
    
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
    } elseif ($status === 'expired' || $status === 'cancelled') {
        file_put_contents($logFile, "❌ Pagamento da fatura #$invoiceId expirado/cancelado\n", FILE_APPEND);
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    file_put_contents($logFile, "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
