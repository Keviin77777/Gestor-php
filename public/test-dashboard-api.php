<?php
// Teste da API do dashboard
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "<h2>Teste - API Dashboard</h2>";

$user = Auth::user();

if (!$user) {
    echo "<p style='color: red;'>Nenhum usuário logado!</p>";
    echo "<p><a href='/test-session.php'>Fazer login</a></p>";
    exit;
}

echo "<h3>Usuário Logado:</h3>";
echo "<pre>" . print_r($user, true) . "</pre>";

$userId = $user['id'];

// Testar cada métrica
echo "<h3>Métricas:</h3>";

// Total de clientes
$totalClients = Database::fetch(
    "SELECT COUNT(*) as total FROM clients WHERE reseller_id = ?",
    [$userId]
)['total'] ?? 0;
echo "<p>Total de clientes: <strong>$totalClients</strong></p>";

// Faturas pendentes
$pendingInvoices = Database::fetch(
    "SELECT COUNT(*) as total FROM invoices WHERE reseller_id = ? AND status = 'pending'",
    [$userId]
)['total'] ?? 0;
echo "<p>Faturas pendentes: <strong>$pendingInvoices</strong></p>";

// Receita do mês
$monthRevenue = Database::fetch(
    "SELECT COALESCE(SUM(final_value), 0) as total FROM invoices WHERE reseller_id = ? AND status = 'paid' AND MONTH(payment_date) = MONTH(NOW()) AND YEAR(payment_date) = YEAR(NOW())",
    [$userId]
)['total'] ?? 0;
echo "<p>Receita do mês: <strong>R$ " . number_format($monthRevenue, 2, ',', '.') . "</strong></p>";

// Servidores
$servers = Database::fetchAll(
    "SELECT id, name FROM servers WHERE user_id = ?",
    [$userId]
);
echo "<p>Total de servidores: <strong>" . count($servers) . "</strong></p>";

// Aplicativos
$applications = Database::fetchAll(
    "SELECT id, name FROM applications WHERE reseller_id = ?",
    [$userId]
);
echo "<p>Total de aplicativos: <strong>" . count($applications) . "</strong></p>";

echo "<hr>";
echo "<p><strong>Conclusão:</strong> Se todos os valores estiverem zerados, o sistema está funcionando corretamente!</p>";
echo "<p>Se ainda aparecer dados, pode ser cache do navegador. Pressione Ctrl+Shift+R para limpar o cache.</p>";
?>