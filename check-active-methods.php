<?php
require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');
require_once __DIR__ . '/app/core/Database.php';

$db = Database::connect();

echo "=== MÉTODOS DE PAGAMENTO ATIVOS ===\n\n";

$stmt = $db->query("SELECT * FROM payment_methods WHERE enabled = 1 OR is_active = 1 LIMIT 10");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($methods as $method) {
    echo "ID: {$method['id']}\n";
    echo "Reseller ID: {$method['reseller_id']}\n";
    echo "Method Name: {$method['method_name']}\n";
    echo "Provider: " . ($method['provider'] ?? 'NULL') . "\n";
    echo "Enabled: {$method['enabled']}\n";
    echo "Is Active: " . ($method['is_active'] ?? 'NULL') . "\n";
    echo "Config Value: " . substr($method['config_value'], 0, 100) . "...\n";
    echo "Public Key: " . ($method['public_key'] ? substr($method['public_key'], 0, 50) . '...' : 'NULL') . "\n";
    echo str_repeat("-", 80) . "\n\n";
}
