<?php
/**
 * Teste de conexão com EFI Bank
 * Execute: php test-efi-connection.php
 */

// Suas credenciais de teste
$clientId = 'Client_Id_XXXXXXXX'; // Substitua pelas suas credenciais reais
$clientSecret = 'Client_Secret_XXXXXXXX'; // Substitua pelas suas credenciais reais
$sandbox = true; // true para homologação, false para produção

// IMPORTANTE: Substitua as credenciais acima pelas suas credenciais reais do EFI Bank
if ($clientId === 'Client_Id_XXXXXXXX' || $clientSecret === 'Client_Secret_XXXXXXXX') {
    echo "⚠️  ATENÇÃO: Você precisa substituir as credenciais de teste pelas suas credenciais reais!\n\n";
    echo "Para obter suas credenciais:\n";
    echo "1. Acesse: https://sejaefi.com.br\n";
    echo "2. Faça login na sua conta\n";
    echo "3. Vá em API → Aplicações\n";
    echo "4. Copie o Client ID e Client Secret\n";
    echo "5. Cole no arquivo test-efi-connection.php\n\n";
    exit(1);
}

echo "=== Teste de Conexão EFI Bank ===\n\n";

// URL da API
$url = $sandbox ? 
    'https://api-pix-h.gerencianet.com.br/oauth/token' : 
    'https://api-pix.gerencianet.com.br/oauth/token';

echo "Ambiente: " . ($sandbox ? "Homologação (Sandbox)" : "Produção") . "\n";
echo "URL: $url\n";
echo "Client ID: " . substr($clientId, 0, 20) . "...\n\n";

// Preparar autenticação
$auth = base64_encode($clientId . ':' . $clientSecret);

// Inicializar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['grant_type' => 'client_credentials']));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $auth,
    'Content-Type: application/json'
]);

// Desabilitar verificação SSL (apenas para teste local)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// Timeouts
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// Verbose para debug
curl_setopt($ch, CURLOPT_VERBOSE, true);

echo "Enviando requisição...\n\n";

// Executar
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);

curl_close($ch);

// Resultados
echo "=== Resultado ===\n\n";

if ($error) {
    echo "❌ ERRO: $error\n\n";
    echo "Detalhes da conexão:\n";
    echo "- Total time: " . $info['total_time'] . "s\n";
    echo "- Connect time: " . $info['connect_time'] . "s\n";
    echo "- HTTP Code: $httpCode\n";
    exit(1);
}

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n\n";

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['access_token'])) {
    echo "✅ SUCESSO! Token obtido:\n";
    echo "- Access Token: " . substr($data['access_token'], 0, 50) . "...\n";
    echo "- Token Type: " . ($data['token_type'] ?? 'Bearer') . "\n";
    echo "- Expires In: " . ($data['expires_in'] ?? 3600) . "s\n";
    echo "\n🎉 Conexão com EFI Bank funcionando!\n";
} else {
    echo "❌ ERRO: Falha na autenticação\n";
    if (isset($data['error_description'])) {
        echo "Descrição: " . $data['error_description'] . "\n";
    }
    if (isset($data['error'])) {
        echo "Erro: " . $data['error'] . "\n";
    }
}

echo "\n=== Informações de Debug ===\n";
echo "- PHP Version: " . PHP_VERSION . "\n";
echo "- cURL Version: " . curl_version()['version'] . "\n";
echo "- SSL Version: " . curl_version()['ssl_version'] . "\n";
echo "- Total time: " . $info['total_time'] . "s\n";
echo "- Connect time: " . $info['connect_time'] . "s\n";
