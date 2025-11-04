<?php
/**
 * Testar geração de PIX de renovação
 */

echo "🧪 Teste de Renovação com PIX\n";
echo "=============================\n\n";

require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/helpers/MercadoPagoHelper.php';

try {
    $db = Database::connect();
    
    // Simular usuário revendedor
    $userId = '34009510-c221-49fe-8b11-97c1a1dff563'; // Seu ID
    $planId = 'plan-monthly'; // Plano mensal
    
    echo "1️⃣ Buscando usuário...\n";
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("❌ Usuário não encontrado\n");
    }
    
    echo "   ✅ Usuário: {$user['email']}\n\n";
    
    echo "2️⃣ Buscando plano...\n";
    $stmt = $db->prepare("SELECT * FROM reseller_plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        die("❌ Plano não encontrado\n");
    }
    
    echo "   ✅ Plano: {$plan['name']}\n";
    echo "   💰 Valor: R$ {$plan['price']}\n";
    echo "   📅 Duração: {$plan['duration_days']} dias\n\n";
    
    echo "3️⃣ Verificando Mercado Pago...\n";
    $mp = new MercadoPagoHelper();
    
    if (!$mp->isEnabled()) {
        die("❌ Mercado Pago não está configurado\n");
    }
    
    echo "   ✅ Mercado Pago ativo\n\n";
    
    echo "4️⃣ Criando pagamento PIX...\n";
    $result = $mp->createPixPayment([
        'amount' => (float)$plan['price'],
        'description' => "Renovação - {$plan['name']} ({$plan['duration_days']} dias)",
        'payer_email' => $user['email'],
        'payer_name' => $user['name'] ?? 'Revendedor',
        'payer_doc_type' => 'CPF',
        'payer_doc_number' => '',
        'external_reference' => "RENEW_USER_{$userId}_PLAN_{$planId}"
        // notification_url removida em desenvolvimento (localhost não é acessível)
    ]);
    
    if (!$result['success']) {
        echo "   ❌ Erro: {$result['error']}\n";
        if (isset($result['details'])) {
            echo "\n   Detalhes:\n";
            print_r($result['details']);
        }
        exit(1);
    }
    
    echo "   ✅ PIX criado!\n";
    echo "   🆔 Payment ID: {$result['payment_id']}\n";
    echo "   📱 QR Code: " . substr($result['qr_code'], 0, 50) . "...\n\n";
    
    echo "5️⃣ Salvando no banco...\n";
    $stmt = $db->prepare("
        INSERT INTO renewal_payments (
            user_id, 
            plan_id, 
            payment_id, 
            amount, 
            status, 
            qr_code,
            created_at
        ) VALUES (?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $planId,
        $result['payment_id'],
        $plan['price'],
        $result['qr_code']
    ]);
    
    echo "   ✅ Registro salvo!\n\n";
    
    echo "=============================\n";
    echo "✅ Teste concluído com sucesso!\n\n";
    
    echo "📋 Resumo:\n";
    echo "   Payment ID: {$result['payment_id']}\n";
    echo "   Valor: R$ {$plan['price']}\n";
    echo "   Status: Aguardando pagamento\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
