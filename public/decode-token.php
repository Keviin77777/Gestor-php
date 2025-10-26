<?php
/**
 * Decodificar token JWT para debug
 */

$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6ImFkbWluLTAwMSIsImVtYWlsIjoiYWRtaW5AdWx0cmFnZXN0b3IuY29tIiwibmFtZSI6IkFkbWluaXN0cmFkb3IiLCJyb2xlIjoiYWRtaW4iLCJpYXQiOjE3NjEyMzI4ODUsImV4cCI6MTc2MTgzNzY4NX0.m0zRvXkg0Ku4ZKeGDTENXE5ipXh7k6sDLvsU3Li_YBo";

function base64UrlDecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

$parts = explode('.', $token);

echo "<h1>Decodificação do Token JWT</h1>";

echo "<h2>Header:</h2>";
$header = json_decode(base64UrlDecode($parts[0]), true);
echo "<pre>" . print_r($header, true) . "</pre>";

echo "<h2>Payload:</h2>";
$payload = json_decode(base64UrlDecode($parts[1]), true);
echo "<pre>" . print_r($payload, true) . "</pre>";

echo "<h2>Verificação de Expiração:</h2>";
echo "<p>Timestamp atual: " . time() . " (" . date('Y-m-d H:i:s') . ")</p>";
echo "<p>Token expira em: " . $payload['exp'] . " (" . date('Y-m-d H:i:s', $payload['exp']) . ")</p>";
echo "<p>Token válido: " . ($payload['exp'] > time() ? 'SIM' : 'NÃO - EXPIRADO') . "</p>";

echo "<h2>Diferença:</h2>";
$diff = $payload['exp'] - time();
echo "<p>" . $diff . " segundos (" . round($diff / 3600, 2) . " horas)</p>";
?>