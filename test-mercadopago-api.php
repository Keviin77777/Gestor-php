<?php
/**
 * Script de teste para API do Mercado Pago
 */

echo "🧪 Testando API de Métodos de Pagamento\n";
echo "========================================\n\n";

// 1. Testar se a tabela existe
echo "1️⃣ Verificando tabela payment_methods...\n";

require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = Database::connect();
    $stmt = $db->query("SELECT COUNT(*) as count FROM payment_methods");
    $result = $stmt->fetch();
    echo "   ✅ Tabela existe com {$result['count']} registro(s)\n\n";
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 2. Testar MercadoPagoHelper
echo "2️⃣ Testando MercadoPagoHelper...\n";

require_once __DIR__ . '/app/helpers/MercadoPagoHelper.php';

try {
    $mp = new MercadoPagoHelper();
    
    if ($mp->isEnabled()) {
        echo "   ✅ Mercado Pago está ATIVO\n";
        echo "   📝 Public Key: " . substr($mp->getPublicKey(), 0, 20) . "...\n\n";
    } else {
        echo "   ⚠️  Mercado Pago NÃO está configurado (normal se ainda não configurou)\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n\n";
}

// 3. Verificar estrutura da API
echo "3️⃣ Verificando arquivos da API...\n";

$files = [
    'public/api-payment-methods.php' => 'API Principal',
    'app/helpers/MercadoPagoHelper.php' => 'Helper do Mercado Pago',
    'app/views/payment-methods/index.php' => 'Interface Admin',
    'public/assets/js/payment-methods.js' => 'JavaScript',
    'public/assets/css/payment-methods.css' => 'CSS'
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        echo "   ✅ $desc\n";
    } else {
        echo "   ❌ $desc - FALTANDO\n";
    }
}

echo "\n========================================\n";
echo "✅ Testes concluídos!\n\n";

echo "📋 Próximos passos:\n";
echo "1. Acesse: http://localhost/payment-methods\n";
echo "2. Configure suas credenciais do Mercado Pago\n";
echo "3. Teste a conexão\n";
echo "4. Ative o método de pagamento\n\n";
