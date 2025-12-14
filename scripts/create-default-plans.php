<?php
/**
 * Script para criar planos padrão
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';

if ($argc < 2) {
    echo "Uso: php scripts/create-default-plans.php <reseller_id>\n";
    echo "Exemplo: php scripts/create-default-plans.php usr-123\n";
    exit(1);
}

$resellerId = $argv[1];

echo "\n=== CRIANDO PLANOS PADRÃO ===\n\n";

// Verificar se revendedor existe
$reseller = Database::fetch("SELECT id, name FROM users WHERE id = ?", [$resellerId]);

if (!$reseller) {
    echo "❌ Revendedor não encontrado: {$resellerId}\n";
    exit(1);
}

echo "✅ Revendedor: {$reseller['name']}\n\n";

// Planos padrão
$defaultPlans = [
    [
        'name' => '1 Mês',
        'description' => 'Plano mensal',
        'duration_days' => 30,
        'price' => 25.00
    ],
    [
        'name' => '2 Meses',
        'description' => 'Plano bimestral',
        'duration_days' => 60,
        'price' => 45.00
    ],
    [
        'name' => '3 Meses',
        'description' => 'Plano trimestral',
        'duration_days' => 90,
        'price' => 65.00
    ],
    [
        'name' => '6 Meses',
        'description' => 'Plano semestral',
        'duration_days' => 180,
        'price' => 120.00
    ],
    [
        'name' => '1 Ano',
        'description' => 'Plano anual',
        'duration_days' => 365,
        'price' => 220.00
    ]
];

echo "Criando planos...\n";
echo "─────────────────────────────────────\n";

foreach ($defaultPlans as $plan) {
    try {
        Database::query(
            "INSERT INTO plans (user_id, name, description, price, duration_days, max_screens, status, created_at)
             VALUES (?, ?, ?, ?, ?, 1, 'active', NOW())",
            [
                $resellerId,
                $plan['name'],
                $plan['description'],
                $plan['price'],
                $plan['duration_days']
            ]
        );
        
        echo "✅ {$plan['name']} - {$plan['duration_days']} dias - R$ " . number_format($plan['price'], 2, ',', '.') . "\n";
    } catch (Exception $e) {
        echo "❌ Erro ao criar {$plan['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Planos criados com sucesso!\n";
echo "\nPRÓXIMOS PASSOS:\n";
echo "1. Acesse: Menu > Clientes\n";
echo "2. Edite cada cliente\n";
echo "3. Selecione o plano correto no campo 'Plano'\n";
echo "4. Salve as alterações\n\n";
