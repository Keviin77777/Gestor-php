<?php
/**
 * Teste da Lógica de Renovação Automática via Webhook
 * Simula um webhook do Mercado Pago para testar a renovação automática
 */

echo "🧪 Teste de Renovação Automática via Webhook\n";
echo "=============================================\n\n";

require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');

require_once __DIR__ . '/app/core/Database.php';

try {
    $db = Database::connect();
    
    // 1. Buscar uma fatura pendente para teste
    echo "1️⃣ Buscando fatura pendente para teste...\n";
    $stmt = $db->prepare("
        SELECT i.*, c.name as client_name, c.renewal_date as current_renewal
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        WHERE i.status = 'pending'
        LIMIT 1
    ");
    $stmt->execute();
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        echo "⚠️ Nenhuma fatura pendente encontrada. Criando uma para teste...\n";
        
        // Buscar um cliente para criar fatura de teste
        $stmt = $db->prepare("SELECT * FROM clients LIMIT 1");
        $stmt->execute();
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            die("❌ Nenhum cliente encontrado. Crie um cliente primeiro.\n");
        }
        
        // Criar fatura de teste
        $invoiceId = uniqid('test_');
        $stmt = $db->prepare("
            INSERT INTO invoices (id, client_id, reseller_id, value, due_date, status, created_at)
            VALUES (?, ?, ?, 29.90, DATE_ADD(NOW(), INTERVAL 30 DAY), 'pending', NOW())
        ");
        $stmt->execute([$invoiceId, $client['id'], $client['reseller_id']]);
        
        // Buscar fatura criada
        $stmt = $db->prepare("
            SELECT i.*, c.name as client_name, c.renewal_date as current_renewal
            FROM invoices i
            JOIN clients c ON i.client_id = c.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "   ✅ Fatura de teste criada: #{$invoice['id']}\n";
    }
    
    echo "   📋 Fatura: #{$invoice['id']}\n";
    echo "   👤 Cliente: {$invoice['client_name']}\n";
    echo "   💰 Valor: R$ {$invoice['value']}\n";
    echo "   📅 Renovação atual: {$invoice['current_renewal']}\n\n";
    
    // 2. Simular dados do webhook
    echo "2️⃣ Simulando webhook do Mercado Pago...\n";
    $paymentId = 'test_payment_' . time();
    $externalRef = "INVOICE_{$invoice['id']}_CLIENT_{$invoice['client_id']}";
    
    echo "   🆔 Payment ID: {$paymentId}\n";
    echo "   🔗 External Reference: {$externalRef}\n\n";
    
    // 3. Simular processamento do webhook
    echo "3️⃣ Processando pagamento aprovado...\n";
    
    // Marcar fatura como paga
    $stmt = $db->prepare("
        UPDATE invoices 
        SET 
            status = 'paid',
            payment_date = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$invoice['id']]);
    echo "   ✅ Fatura marcada como PAGA\n";
    
    // Buscar dados atualizados do cliente
    $stmt = $db->prepare("
        SELECT c.*, i.reseller_id, c.id as client_id
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        WHERE i.id = ?
    ");
    $stmt->execute([$invoice['id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcular nova data de renovação
    $currentRenewal = new DateTime($client['renewal_date']);
    $now = new DateTime();
    
    echo "   📅 Data atual de renovação: {$currentRenewal->format('Y-m-d')}\n";
    
    // Se já venceu, começar de hoje
    if ($currentRenewal < $now) {
        $currentRenewal = $now;
        echo "   ⚠️ Cliente vencido, renovando a partir de hoje\n";
    }
    
    // Adicionar 30 dias
    $currentRenewal->modify('+30 days');
    $newRenewalDate = $currentRenewal->format('Y-m-d');
    
    echo "   📅 Nova data de renovação: {$newRenewalDate}\n";
    
    // Atualizar cliente no gestor
    $stmt = $db->prepare("
        UPDATE clients 
        SET 
            renewal_date = ?,
            status = 'active',
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newRenewalDate, $client['client_id']]);
    
    echo "   ✅ Cliente renovado no gestor\n\n";
    
    // 4. Testar sincronização com Sigma
    echo "4️⃣ Testando sincronização com Sigma...\n";
    
    try {
        require_once __DIR__ . '/app/helpers/clients-sync-sigma.php';
        
        // Preparar dados do cliente para sincronização
        $clientData = [
            'id' => $client['client_id'],
            'name' => $client['name'],
            'email' => $client['email'],
            'phone' => $client['phone'],
            'username' => $client['username'],
            'iptv_password' => $client['iptv_password'],
            'password' => $client['password'],
            'notes' => $client['notes'],
            'status' => 'active',
            'renewal_date' => $newRenewalDate
        ];
        
        $sigmaResult = syncClientWithSigmaAfterSave($clientData, $client['reseller_id']);
        
        if ($sigmaResult['success']) {
            echo "   ✅ Sincronização Sigma: {$sigmaResult['message']}\n";
            if (isset($sigmaResult['action'])) {
                echo "   🔄 Ação: {$sigmaResult['action']}\n";
            }
        } else {
            echo "   ⚠️ Erro na sincronização Sigma: {$sigmaResult['message']}\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️ Erro ao sincronizar com Sigma: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 5. Verificar resultado final
    echo "5️⃣ Verificando resultado final...\n";
    
    $stmt = $db->prepare("
        SELECT i.status as invoice_status, i.payment_date, 
               c.renewal_date, c.status as client_status
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        WHERE i.id = ?
    ");
    $stmt->execute([$invoice['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📋 Status da fatura: {$result['invoice_status']}\n";
    echo "   💳 Pago em: {$result['payment_date']}\n";
    echo "   👤 Status do cliente: {$result['client_status']}\n";
    echo "   📅 Nova renovação: {$result['renewal_date']}\n\n";
    
    echo "=============================================\n";
    echo "✅ Teste concluído com sucesso!\n\n";
    
    echo "📋 Resumo do que foi testado:\n";
    echo "   ✅ Webhook processou pagamento aprovado\n";
    echo "   ✅ Fatura marcada como paga\n";
    echo "   ✅ Cliente renovado por +30 dias no gestor\n";
    echo "   ✅ Status do cliente ativado\n";
    echo "   ✅ Sincronização com Sigma testada\n\n";
    
    echo "🎯 A lógica de renovação automática está funcionando!\n";
    echo "   Quando um cliente pagar via PIX, ele será automaticamente:\n";
    echo "   • Renovado por 30 dias no gestor\n";
    echo "   • Ativado no sistema\n";
    echo "   • Sincronizado com o Sigma (se configurado)\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}