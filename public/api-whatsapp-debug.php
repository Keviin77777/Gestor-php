<?php
/**
 * Script de debug para testar conexão com Evolution API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/helpers/auth-helper.php';

$user = getAuthenticatedUser();
$resellerId = $user['id'];

// Buscar configurações
$settings = Database::fetch(
    "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
    [$resellerId]
);

if (!$settings) {
    $apiUrl = getenv('EVOLUTION_API_URL') ?: 'http://localhost:8081';
    $apiKey = getenv('EVOLUTION_API_KEY') ?: '';
} else {
    $apiUrl = rtrim($settings['evolution_api_url'] ?: 'http://localhost:8081', '/');
    $apiKey = $settings['evolution_api_key'] ?: '';
}

$instanceName = 'ultragestor-' . $resellerId;

$debug = [
    'api_url' => $apiUrl,
    'api_key_set' => !empty($apiKey),
    'instance_name' => $instanceName,
    'tests' => []
];

// Teste 1: Verificar se a API está acessível
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($apiUrl, '/') . '/manager/fetchInstances',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => $apiKey ? ['apikey: ' . $apiKey] : [],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$debug['tests']['api_accessible'] = [
    'success' => $httpCode === 200 || $httpCode === 401,
    'http_code' => $httpCode,
    'error' => $error,
    'response' => substr($response, 0, 200)
];

// Teste 2: Verificar estado da instância
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/connectionState/' . $instanceName,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => $apiKey ? ['apikey: ' . $apiKey] : [],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$debug['tests']['instance_state'] = [
    'success' => $httpCode === 200,
    'http_code' => $httpCode,
    'error' => $error,
    'response' => $response
];

// Teste 3: Tentar conectar a instância
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/connect/' . $instanceName,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => $apiKey ? ['apikey: ' . $apiKey] : [],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$debug['tests']['connect_instance'] = [
    'success' => $httpCode === 200,
    'http_code' => $httpCode,
    'error' => $error,
    'response' => substr($response, 0, 500)
];

echo json_encode($debug, JSON_PRETTY_PRINT);
?>


