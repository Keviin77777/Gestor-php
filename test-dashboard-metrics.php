<?php
/**
 * Teste - Verificar métricas do dashboard
 */

require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');

require_once __DIR__ . '/app/core/Database.php';

$userId = 'admin-001';

// Clientes que expiram HOJE
$expiringToday = Database::fetch(
    "SELECT COUNT(*) as total 
     FROM clients 
     WHERE reseller_id = ?
     AND status = 'active' 
     AND DATE(renewal_date) = CURDATE()",
    [$userId]
)['total'] ?? 0;

// Lista de clientes
$clients = Database::fetchAll(
    "SELECT id, name, renewal_date, 
            DATEDIFF(renewal_date, CURDATE()) as dias_ate_vencer
     FROM clients 
     WHERE reseller_id = ?
     AND status = 'active'
     ORDER BY renewal_date",
    [$userId]
);

echo "Data atual: " . date('Y-m-d') . "\n\n";
echo "Clientes que expiram HOJE: $expiringToday\n\n";
echo "Lista de clientes:\n";
foreach ($clients as $client) {
    echo "- {$client['name']}: vence em {$client['renewal_date']} (daqui {$client['dias_ate_vencer']} dias)\n";
}
