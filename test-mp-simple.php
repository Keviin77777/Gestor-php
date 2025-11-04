<?php
/**
 * Teste simples - Busca credenciais do banco e testa
 */

echo "🧪 Teste Rápido - Mercado Pago\n";
echo "==============================\n\n";

require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = Database::connect();
    
    // Buscar credenciais do banco
    $stmt = $db->prepare("SELECT config_value FROM payment_methods WHERE method_name = 'mercadopago'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        die("❌ Nenhuma configuração encontrada no banco\n");
    }
    
    $config = json_decode($result['config_value'], true);
    $accessToken = $config['access_token'] ?? '';
    
    if (empty($accessToken)) {
        die("❌ Access Token não configurado\n");
    }
    
    echo "✅ Credenciais encontradas no banco\n";
    echo "   Token: " . substr($accessToken, 0, 20) . "...\n\n";
    
    echo "🔄 Testando API do Mercado Pago...\n\n";
    
    // Testar
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
    
    echo "📊 HTTP Code: $httpCode\n\n";
    
    if ($error) {
        echo "❌ Erro cURL: $error\n";
        exit(1);
    }
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "✅ SUCESSO!\n\n";
        echo "📋 Conta:\n";
        echo "   Email: " . ($data['email'] ?? 'N/A') . "\n";
        echo "   ID: " . ($data['id'] ?? 'N/A') . "\n";
        echo "   Nickname: " . ($data['nickname'] ?? 'N/A') . "\n\n";
    } else {
        $errorData = json_decode($response, true);
        echo "❌ ERRO!\n\n";
        echo "Resposta completa:\n";
        print_r($errorData);
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
