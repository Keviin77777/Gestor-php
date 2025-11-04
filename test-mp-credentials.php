<?php
/**
 * Teste direto das credenciais do Mercado Pago
 * Execute: php test-mp-credentials.php
 */

echo "🔐 Teste de Credenciais Mercado Pago\n";
echo "====================================\n\n";

// Solicitar credenciais
echo "Cole seu Access Token: ";
$accessToken = trim(fgets(STDIN));

if (empty($accessToken)) {
    die("❌ Access Token não pode ser vazio\n");
}

echo "\n🔄 Testando conexão com Mercado Pago...\n\n";

// Testar conexão
$url = 'https://api.mercadopago.com/v1/users/me';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 Resultado:\n";
echo "   HTTP Code: $httpCode\n";

if ($error) {
    echo "   ❌ Erro cURL: $error\n\n";
    exit(1);
}

echo "\n📄 Resposta completa:\n";
echo $response . "\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    echo "✅ CREDENCIAIS VÁLIDAS!\n\n";
    echo "📋 Informações da conta:\n";
    echo "   ID: " . ($data['id'] ?? 'N/A') . "\n";
    echo "   Email: " . ($data['email'] ?? 'N/A') . "\n";
    echo "   Nickname: " . ($data['nickname'] ?? 'N/A') . "\n";
    echo "   País: " . ($data['site_id'] ?? 'N/A') . "\n";
    echo "   Tipo: " . ($data['user_type'] ?? 'N/A') . "\n\n";
    
    echo "✅ Você pode usar essas credenciais!\n";
    
} else {
    $errorData = json_decode($response, true);
    
    echo "❌ CREDENCIAIS INVÁLIDAS!\n\n";
    echo "📋 Detalhes do erro:\n";
    echo "   Mensagem: " . ($errorData['message'] ?? 'N/A') . "\n";
    echo "   Status: " . ($errorData['status'] ?? 'N/A') . "\n";
    echo "   Error: " . ($errorData['error'] ?? 'N/A') . "\n\n";
    
    echo "🔍 Possíveis causas:\n";
    echo "   - Access Token incorreto ou expirado\n";
    echo "   - Credenciais de teste em vez de produção\n";
    echo "   - Token não tem permissões necessárias\n";
    echo "   - Conta do Mercado Pago não está ativa\n\n";
    
    echo "💡 Dica: Verifique se você copiou o token completo,\n";
    echo "   incluindo o prefixo APP_USR-\n\n";
}

echo "====================================\n";
