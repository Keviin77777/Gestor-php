<?php
/**
 * Script de teste para criar PIX via Mercado Pago
 * Execute: php test-pix.php
 */

echo "🧪 Teste de Criação de PIX - Mercado Pago\n";
echo "==========================================\n\n";

require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/helpers/MercadoPagoHelper.php';

try {
    $mp = new MercadoPagoHelper();
    
    // Verificar se está configurado
    if (!$mp->isEnabled()) {
        echo "❌ Mercado Pago NÃO está configurado!\n\n";
        echo "📋 Passos para configurar:\n";
        echo "1. Acesse: http://localhost:8000/payment-methods\n";
        echo "2. Faça login como admin\n";
        echo "3. Configure suas credenciais\n";
        echo "4. Ative o Mercado Pago\n";
        echo "5. Execute este script novamente\n\n";
        exit(1);
    }
    
    echo "✅ Mercado Pago está ATIVO\n\n";
    
    // Dados do pagamento de teste (mínimo necessário)
    $dados = [
        'amount' => 10.00,
        'description' => 'Teste de PIX - Fatura #123',
        'payer_email' => 'test@test.com'
    ];
    
    echo "📝 Dados do pagamento:\n";
    echo "   Valor: R$ " . number_format($dados['amount'], 2, ',', '.') . "\n";
    echo "   Descrição: {$dados['description']}\n";
    echo "   Email: {$dados['payer_email']}\n\n";
    
    echo "🔄 Criando pagamento PIX...\n\n";
    
    $result = $mp->createPixPayment($dados);
    
    if ($result['success']) {
        echo "✅ PIX CRIADO COM SUCESSO!\n\n";
        echo "📋 Informações do pagamento:\n";
        echo "   Payment ID: {$result['payment_id']}\n";
        echo "   Status: {$result['status']}\n";
        echo "   Expira em: " . ($result['expiration_date'] ?? 'N/A') . "\n\n";
        
        echo "📱 QR Code PIX:\n";
        echo "   " . substr($result['qr_code'], 0, 50) . "...\n\n";
        
        echo "🔗 Para testar o pagamento:\n";
        echo "1. Abra o app do seu banco\n";
        echo "2. Vá em PIX > Pagar com QR Code\n";
        echo "3. Escaneie o QR Code ou cole o código acima\n";
        echo "4. Confirme o pagamento de R$ 10,00\n\n";
        
        echo "💡 Dica: Se estiver usando credenciais de TESTE,\n";
        echo "   o pagamento não será processado de verdade.\n\n";
        
        // Salvar QR Code em arquivo para facilitar
        $qrFile = __DIR__ . '/test-qrcode.txt';
        file_put_contents($qrFile, $result['qr_code']);
        echo "💾 QR Code salvo em: test-qrcode.txt\n\n";
        
    } else {
        echo "❌ ERRO ao criar PIX!\n\n";
        echo "Erro: {$result['error']}\n\n";
        
        if (isset($result['details'])) {
            echo "Detalhes:\n";
            print_r($result['details']);
            echo "\n";
        }
        
        echo "🔍 Possíveis causas:\n";
        echo "- Credenciais inválidas\n";
        echo "- Access Token expirado\n";
        echo "- Problema de conexão com Mercado Pago\n";
        echo "- Dados do pagamento inválidos\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n\n";
}

echo "==========================================\n";
echo "Teste concluído!\n\n";
