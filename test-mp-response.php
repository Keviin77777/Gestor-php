<?php
/**
 * Ver resposta completa do Mercado Pago
 */

require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');
require_once __DIR__ . '/app/core/Database.php';

$db = Database::connect();

// Buscar token
$stmt = $db->prepare("SELECT config_value FROM payment_methods WHERE method_name = 'mercadopago'");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    die("❌ Configuração não encontrada\n");
}

$config = json_decode($result['config_value'], true);
$accessToken = $config['access_token'] ?? '';

if (empty($accessToken)) {
    die("❌ Access Token não configurado\n");
}

echo "🧪 Testando Mercado Pago\n";
echo "========================\n\n";

$url = 'https://api.mercadopago.com/v1/payments';

$testPayload = [
    'transaction_amount' => 0.01,
    'description' => 'Test - Validação',
    'payment_method_id' => 'pix',
    'payer' => [
        'email' => 'test@test.com'
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json',
    'X-Idempotency-Key: test_' . uniqid()
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";

$data = json_decode($response, true);

if ($httpCode === 201) {
    echo "✅ SUCESSO!\n\n";
    echo "📋 Informações Extraídas:\n";
    echo "   Collector ID: " . ($data['collector_id'] ?? 'N/A') . "\n";
    echo "   Payment ID: " . ($data['id'] ?? 'N/A') . "\n";
    echo "   Currency: " . ($data['currency_id'] ?? 'N/A') . "\n";
    echo "   Status: " . ($data['status'] ?? 'N/A') . "\n";
    echo "   Date Created: " . ($data['date_created'] ?? 'N/A') . "\n";
    
    if (isset($data['payer'])) {
        echo "\n📧 Payer Info:\n";
        echo "   Email: " . ($data['payer']['email'] ?? 'N/A') . "\n";
        echo "   ID: " . ($data['payer']['id'] ?? 'N/A') . "\n";
    }
    
    echo "\n📄 Resposta Completa (primeiros 500 caracteres):\n";
    echo substr(json_encode($data, JSON_PRETTY_PRINT), 0, 500) . "...\n";
} else {
    echo "❌ ERRO!\n\n";
    print_r($data);
}
