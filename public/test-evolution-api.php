<?php
/**
 * Teste da Evolution API
 */

header('Content-Type: application/json');

$apiUrl = 'http://localhost:8081';

// Testar se a Evolution API está rodando
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl . '/manager/fetchInstances',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo json_encode([
    'api_url' => $apiUrl,
    'http_code' => $httpCode,
    'curl_error' => $error,
    'response' => $response,
    'api_running' => $httpCode === 200,
    'message' => $httpCode === 200 ? 'Evolution API está rodando' : 'Evolution API não está acessível'
]);
?>