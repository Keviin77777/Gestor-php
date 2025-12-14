<?php
/**
 * Script para verificar configuração de plano do cliente
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';

if ($argc < 2) {
    echo "Uso: php scripts/check-client-plan.php <client_id>\n";
    echo "Exemplo: php scripts/check-client-plan.php client-123\n";
    exit(1);
}

$clientId = $argv[1];

echo "\n=== DIAGNÓSTICO DE PLANO DO CLIENTE ===\n\n";

// Buscar cliente
$client = Database::fetch(
    "SELECT c.*, p.id as plan_table_id, p.name as plan_table_name, p.duration_days
     FROM clients c
     LEFT JOIN plans p ON c.plan_id = p.id
     WHERE c.id = ?",
    [$clientId]
);

if (!$client) {
    echo "❌ Cliente não encontrado: {$clientId}\n";
    exit(1);
}

echo "✅ Cliente encontrado!\n\n";
echo "DADOS DO CLIENTE:\n";
echo "─────────────────────────────────────\n";
echo "ID: {$client['id']}\n";
echo "Nome: {$client['name']}\n";
echo "Plano (campo texto): {$client['plan']}\n";
echo "Plan ID (FK): " . ($client['plan_id'] ?? 'NULL') . "\n";
echo "Valor: R$ {$client['value']}\n";
echo "Renovação: {$client['renewal_date']}\n\n";

echo "DADOS DO PLANO (tabela plans):\n";
echo "─────────────────────────────────────\n";
if ($client['plan_table_id']) {
    echo "✅ Plano vinculado encontrado!\n";
    echo "ID do Plano: {$client['plan_table_id']}\n";
    echo "Nome do Plano: {$client['plan_table_name']}\n";
    echo "Duração: {$client['duration_days']} dias\n";
} else {
    echo "❌ Nenhum plano vinculado (plan_id é NULL)\n";
    echo "⚠️  Cliente usa apenas o campo 'plan' (texto): {$client['plan']}\n";
    echo "⚠️  Sistema usará padrão de 30 dias\n";
}

echo "\n";

// Listar todos os planos disponíveis
echo "PLANOS DISPONÍVEIS:\n";
echo "─────────────────────────────────────\n";
$plans = Database::fetchAll(
    "SELECT id, name, duration_days, price, status 
     FROM plans 
     WHERE user_id = ? 
     ORDER BY duration_days",
    [$client['reseller_id']]
);

if (empty($plans)) {
    echo "❌ Nenhum plano cadastrado para este revendedor\n";
    echo "\n";
    echo "SOLUÇÃO:\n";
    echo "1. Cadastre planos em: Menu > Planos\n";
    echo "2. Ao editar o cliente, selecione o plano correto\n";
} else {
    foreach ($plans as $plan) {
        $selected = ($plan['id'] == $client['plan_id']) ? ' ← SELECIONADO' : '';
        echo "• {$plan['name']} - {$plan['duration_days']} dias - R$ {$plan['price']}{$selected}\n";
    }
}

echo "\n";
echo "RECOMENDAÇÃO:\n";
echo "─────────────────────────────────────\n";
if (!$client['plan_id']) {
    echo "⚠️  Vincule o cliente a um plano da tabela 'plans'\n";
    echo "   para que a renovação use a duração correta.\n";
} else if ($client['duration_days'] == 30) {
    echo "✅ Plano configurado corretamente (30 dias)\n";
} else {
    echo "✅ Plano configurado: {$client['duration_days']} dias\n";
}

echo "\n";
