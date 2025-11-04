<?php
/**
 * API para verificar status de pagamento PIX
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
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Response.php';
require_once __DIR__ . '/../app/helpers/MercadoPagoHelper.php';

// Verificar autenticação
$user = Auth::user();

if (!$user) {
    Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::json(['success' => false, 'error' => 'Método não permitido'], 405);
    exit;
}

try {
    $paymentId = $_GET['payment_id'] ?? null;
    
    if (!$paymentId) {
        Response::json(['success' => false, 'error' => 'ID do pagamento é obrigatório'], 400);
        exit;
    }
    
    // Verificar se é pagamento de renovação ou fatura
    $db = Database::connect();
    
    // Tentar buscar em renewal_payments primeiro
    $stmt = $db->prepare("
        SELECT * FROM renewal_payments 
        WHERE payment_id = ? AND user_id = ?
    ");
    $stmt->execute([$paymentId, $user['id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    $paymentType = 'renewal';
    
    // Se não encontrou, buscar em invoice_payments
    if (!$payment) {
        $stmt = $db->prepare("
            SELECT ip.*, i.client_id, i.value as invoice_value
            FROM invoice_payments ip
            JOIN invoices i ON ip.invoice_id = i.id
            JOIN clients c ON i.client_id = c.id
            WHERE ip.payment_id = ? AND c.reseller_id = ?
        ");
        $stmt->execute([$paymentId, $user['id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        $paymentType = 'invoice';
    }
    
    if (!$payment) {
        Response::json(['success' => false, 'error' => 'Pagamento não encontrado'], 404);
        exit;
    }
    
    // Se já está aprovado, retornar status
    if ($payment['status'] === 'approved') {
        Response::json([
            'success' => true,
            'status' => 'approved',
            'message' => 'Pagamento já aprovado'
        ]);
        exit;
    }
    
    // Consultar status no Mercado Pago
    $mp = new MercadoPagoHelper();
    $result = $mp->getPaymentStatus($paymentId);
    
    if (!$result['success']) {
        Response::json([
            'success' => false,
            'error' => $result['error']
        ], 400);
        exit;
    }
    
    // Atualizar status no banco conforme tipo
    if ($paymentType === 'renewal') {
        $stmt = $db->prepare("
            UPDATE renewal_payments 
            SET status = ?, updated_at = NOW()
            WHERE payment_id = ?
        ");
        $stmt->execute([$result['status'], $paymentId]);
        
        // Se foi aprovado, renovar o acesso do usuário
        if ($result['status'] === 'approved') {
            // Buscar dados do plano
            $stmt = $db->prepare("SELECT * FROM reseller_plans WHERE id = ?");
            $stmt->execute([$payment['plan_id']]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plan) {
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
                    $user['id']
                ]);
                
                error_log("Acesso renovado - User: {$user['id']}, Plan: {$plan['name']}, Expires: {$currentExpires->format('Y-m-d')}");
            }
        }
    } else {
        // Pagamento de fatura
        $stmt = $db->prepare("
            UPDATE invoice_payments 
            SET status = ?, 
                approved_at = ?,
                updated_at = NOW()
            WHERE payment_id = ?
        ");
        $approvedAt = $result['status'] === 'approved' ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$result['status'], $approvedAt, $paymentId]);
        
        // Se foi aprovado, marcar fatura como paga e renovar cliente
        if ($result['status'] === 'approved') {
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
            $stmt->execute([$payment['invoice_id']]);
            
            // Renovar cliente
            $stmt = $db->prepare("
                SELECT c.*, i.value, i.due_date
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                WHERE i.id = ?
            ");
            $stmt->execute([$payment['invoice_id']]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($client) {
                $currentRenewal = new DateTime($client['renewal_date']);
                $now = new DateTime();
                
                if ($currentRenewal < $now) {
                    $currentRenewal = $now;
                }
                
                $currentRenewal->modify('+30 days');
                
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
                
                error_log("Cliente renovado - ID: {$client['id']}, Novo vencimento: {$currentRenewal->format('Y-m-d')}");
            }
        }
    }
    
    Response::json([
        'success' => true,
        'status' => $result['status'],
        'status_detail' => $result['status_detail'] ?? null,
        'amount' => $result['amount'] ?? null
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao verificar status do pagamento: " . $e->getMessage());
    Response::json([
        'success' => false,
        'error' => 'Erro ao verificar pagamento: ' . $e->getMessage()
    ], 500);
}
