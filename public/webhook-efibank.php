<?php
/**
 * Webhook do EFI Bank
 * Recebe notificações de pagamentos aprovados/rejeitados
 */

// Log de todas as requisições
$logFile = __DIR__ . '/../logs/efibank-webhook.log';
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
require_once __DIR__ . '/../app/helpers/EfiBankHelper.php';

try {
    $data = json_decode($body, true);
    
    if (!$data) {
        file_put_contents($logFile, "Erro: JSON inválido\n", FILE_APPEND);
        http_response_code(400);
        exit;
    }
    
    // Extrair txid do webhook
    $txid = $data['pix'][0]['txid'] ?? null;
    
    if (!$txid) {
        file_put_contents($logFile, "Erro: TXID não encontrado no webhook\n", FILE_APPEND);
        http_response_code(200);
        exit;
    }
    
    file_put_contents($logFile, "TXID: $txid\n", FILE_APPEND);
    
    // Inicializar EFI Bank
    $efi = new EfiBankHelper();
    
    if (!$efi->isEnabled()) {
        file_put_contents($logFile, "EFI Bank não está habilitado\n", FILE_APPEND);
        http_response_code(200);
        exit;
    }
    
    // Processar webhook
    $result = $efi->processWebhook($data);
    
    if (!$result['success']) {
        file_put_contents($logFile, "Erro ao processar: {$result['error']}\n", FILE_APPEND);
        http_response_code(200);
        exit;
    }
    
    $paymentId = $result['payment_id'];
    $status = $result['status'];
    
    file_put_contents($logFile, "Payment ID: $paymentId | Status: $status\n", FILE_APPEND);
    
    $db = Database::connect();
    
    // Verificar se é renovação de revendedor
    $stmt = $db->prepare("
        SELECT * FROM renewal_payments 
        WHERE payment_id = ? AND payment_provider = 'efibank'
    ");
    $stmt->execute([$paymentId]);
    $renewal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($renewal) {
        file_put_contents($logFile, "Renovação detectada - User: {$renewal['user_id']}, Plan: {$renewal['plan_id']}\n", FILE_APPEND);
        
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
            $stmt->execute([$renewal['plan_id']]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plan) {
                // Buscar dados do usuário
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$renewal['user_id']]);
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
                        $renewal['user_id']
                    ]);
                    
                    file_put_contents($logFile, "✅ Renovação aprovada - User: {$renewal['user_id']}, Novo vencimento: {$currentExpires->format('Y-m-d')}\n", FILE_APPEND);
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
