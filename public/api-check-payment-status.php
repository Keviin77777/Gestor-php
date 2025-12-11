<?php
/**
 * API para verificar status de pagamento PIX
 * Consulta diretamente na API do provedor (Mercado Pago/Asaas/EFI)
 * Funciona SEM webhook - usa polling
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/MercadoPagoHelper.php';
require_once __DIR__ . '/../app/helpers/AsaasHelper.php';
require_once __DIR__ . '/../app/helpers/EfiBankHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

try {
    $paymentId = $_GET['payment_id'] ?? null;
    
    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID do pagamento é obrigatório']);
        exit;
    }
    
    // Buscar pagamento no banco
    $payment = Database::fetch(
        "SELECT ip.*, i.client_id, i.reseller_id, i.id as invoice_id, i.value as invoice_value
         FROM invoice_payments ip
         JOIN invoices i ON ip.invoice_id = i.id
         WHERE ip.payment_id = ?",
        [$paymentId]
    );
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pagamento não encontrado']);
        exit;
    }
    
    // Se já está aprovado, retornar status
    if ($payment['status'] === 'approved') {
        echo json_encode([
            'success' => true,
            'status' => 'approved',
            'message' => 'Pagamento já aprovado'
        ]);
        exit;
    }
    
    // Buscar credenciais do provedor para este revendedor
    $paymentProvider = $payment['payment_provider'] ?? 'mercadopago';
    
    $credentials = Database::fetch(
        "SELECT config_value FROM payment_methods 
         WHERE reseller_id = ? AND method_name = ? AND enabled = 1",
        [$payment['reseller_id'], $paymentProvider]
    );
    
    if (!$credentials) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Provedor de pagamento não configurado']);
        exit;
    }
    
    $config = json_decode($credentials['config_value'], true);
    
    // Criar instância do provedor correto
    if ($paymentProvider === 'asaas') {
        $provider = new AsaasHelper($config['api_key'] ?? '', $config['sandbox'] ?? false);
    } elseif ($paymentProvider === 'efibank') {
        $provider = new EfiBankHelper(
            $config['client_id'] ?? '',
            $config['client_secret'] ?? '',
            $config['certificate'] ?? '',
            $config['sandbox'] ?? false
        );
    } else {
        // Mercado Pago
        $provider = new MercadoPagoHelper($config['public_key'] ?? '', $config['access_token'] ?? '');
    }
    
    // Consultar status na API do provedor
    $result = $provider->getPaymentStatus($paymentId);
    
    if (!$result['success']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Erro ao consultar pagamento'
        ]);
        exit;
    }
    
    // Atualizar status no banco
    Database::query(
        "UPDATE invoice_payments 
         SET status = ?, 
             approved_at = ?,
             updated_at = NOW()
         WHERE payment_id = ?",
        [
            $result['status'],
            $result['status'] === 'approved' ? date('Y-m-d H:i:s') : null,
            $paymentId
        ]
    );
    
    // Se foi aprovado, processar renovação
    if ($result['status'] === 'approved') {
        error_log("💰 Pagamento aprovado via polling: {$paymentId}");
        
        // Marcar fatura como paga
        Database::query(
            "UPDATE invoices 
             SET status = 'paid',
                 payment_date = NOW(),
                 updated_at = NOW()
             WHERE id = ?",
            [$payment['invoice_id']]
        );
        
        error_log("✅ Fatura marcada como paga: {$payment['invoice_id']}");
        
        // Buscar dados completos do cliente COM duração do plano
        $client = Database::fetch(
            "SELECT c.*, i.value, i.due_date, p.duration_days
             FROM clients c
             JOIN invoices i ON i.client_id = c.id
             LEFT JOIN plans p ON c.plan_id = p.id
             WHERE i.id = ?",
            [$payment['invoice_id']]
        );
        
        if ($client) {
            // Buscar duração do plano (padrão 30 dias se não encontrar)
            $durationDays = $client['duration_days'] ?? 30;
            
            error_log("📅 Duração do plano: {$durationDays} dias");
            
            // Calcular nova data de renovação
            $currentRenewal = new DateTime($client['renewal_date']);
            $now = new DateTime();
            
            if ($currentRenewal < $now) {
                $currentRenewal = $now;
            }
            
            $currentRenewal->modify("+{$durationDays} days");
            $newRenewalDate = $currentRenewal->format('Y-m-d');
            
            // Atualizar cliente no gestor
            Database::query(
                "UPDATE clients 
                 SET renewal_date = ?,
                     status = 'active',
                     updated_at = NOW()
                 WHERE id = ?",
                [$newRenewalDate, $client['id']]
            );
            
            error_log("✅ Cliente renovado no gestor: {$client['id']} até {$newRenewalDate}");
            
            // Renovar no Sigma (se configurado)
            try {
                require_once __DIR__ . '/../app/helpers/clients-sync-sigma.php';
                
                $sigmaResult = renewClientInSigmaAfterPayment($client, $payment['reseller_id']);
                
                if ($sigmaResult['success']) {
                    error_log("✅ Cliente renovado no Sigma: {$client['id']}");
                } else {
                    error_log("⚠️ Erro ao renovar no Sigma: {$sigmaResult['message']}");
                }
            } catch (Exception $e) {
                error_log("⚠️ Erro ao renovar no Sigma: " . $e->getMessage());
            }
            
            // Enviar WhatsApp (se configurado)
            try {
                require_once __DIR__ . '/../app/helpers/whatsapp-helper.php';
                
                $whatsappResult = sendRenewalMessage($client['id'], $payment['invoice_id']);
                
                if ($whatsappResult['success']) {
                    error_log("✅ WhatsApp de renovação enviado: {$client['id']}");
                }
            } catch (Exception $e) {
                error_log("⚠️ Erro ao enviar WhatsApp: " . $e->getMessage());
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'status' => $result['status'],
        'status_detail' => $result['status_detail'] ?? null,
        'amount' => $result['amount'] ?? null,
        'message' => $result['status'] === 'approved' ? 'Pagamento aprovado! Cliente renovado automaticamente.' : 'Aguardando pagamento...'
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao verificar status do pagamento: " . $e->getMessage());
    Response::json([
        'success' => false,
        'error' => 'Erro ao verificar pagamento: ' . $e->getMessage()
    ], 500);
}
