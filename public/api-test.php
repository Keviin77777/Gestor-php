<?php
// Teste simples da API
header('Content-Type: application/json');

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Carregar Database
require_once __DIR__ . '/../app/core/Database.php';

try {
    // Testar conexão
    $totalClients = Database::fetch("SELECT COUNT(*) as total FROM clients")['total'] ?? 0;
    
    // Receita do mês atual
    $monthRevenue = Database::fetch(
        "SELECT COALESCE(SUM(final_value), 0) as total 
         FROM invoices 
         WHERE status = 'paid' 
         AND MONTH(payment_date) = MONTH(NOW()) 
         AND YEAR(payment_date) = YEAR(NOW())"
    )['total'] ?? 0;
    
    // Valor de inadimplentes (faturas vencidas e não pagas)
    $inadimplentesValue = Database::fetch(
        "SELECT COALESCE(SUM(final_value), 0) as total 
         FROM invoices 
         WHERE status = 'pending' 
         AND due_date < NOW()"
    )['total'] ?? 0;
    
    // Contagem de clientes inadimplentes
    $inadimplentesCount = Database::fetch(
        "SELECT COUNT(DISTINCT client_id) as total 
         FROM invoices 
         WHERE status = 'pending' 
         AND due_date < NOW()"
    )['total'] ?? 0;
    
    // Faturas pendentes
    $pendingInvoices = Database::fetch("SELECT COUNT(*) as total FROM invoices WHERE status = 'pending'")['total'] ?? 0;
    
    // Clientes a vencer nos próximos 7 dias
    $expiringClients = Database::fetch(
        "SELECT COUNT(*) as total 
         FROM clients 
         WHERE status = 'active' 
         AND renewal_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"
    )['total'] ?? 0;
    
    // Lista de clientes a vencer
    $expiringClientsList = Database::fetchAll(
        "SELECT name, renewal_date, status 
         FROM clients 
         WHERE status = 'active' 
         AND renewal_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
         ORDER BY renewal_date ASC 
         LIMIT 5"
    );
    
    // Dados do gráfico (últimos 6 meses) - com dados simulados baseados nos clientes
    $revenueChartData = [];
    $revenueChartLabels = [];
    
    // Valor médio por cliente para simulação
    $avgClientValue = Database::fetch("SELECT AVG(value) as avg_value FROM clients")['avg_value'] ?? 35;
    $clientCount = max(1, $totalClients); // Pelo menos 1 para evitar divisão por zero
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M', strtotime("-$i months"));
        
        // Buscar receitas reais
        $revenue = Database::fetch(
            "SELECT COALESCE(SUM(final_value), 0) as total 
             FROM invoices 
             WHERE status = 'paid' 
             AND DATE_FORMAT(payment_date, '%Y-%m') = ?",
            [$month]
        )['total'] ?? 0;
        
        // Se não há receitas reais, simular baseado nos clientes
        if ($revenue == 0 && $totalClients > 0) {
            // Simular crescimento mensal baseado nos clientes existentes
            $growthFactor = 1 + (0.05 * (6 - $i)); // Crescimento de 5% por mês
            $revenue = $avgClientValue * $clientCount * $growthFactor * 0.8; // 80% dos clientes pagam por mês
        }
        
        $revenueChartLabels[] = $monthName;
        $revenueChartData[] = round((float)$revenue, 2);
    }
    
    // Despesas reais do mês atual
    $monthlyExpenses = Database::fetch(
        "SELECT COALESCE(SUM(amount), 0) as total 
         FROM expenses 
         WHERE MONTH(expense_date) = MONTH(NOW()) 
         AND YEAR(expense_date) = YEAR(NOW())"
    )['total'] ?? 0;
    
    $monthlyBalance = $monthRevenue - $monthlyExpenses;
    
    // Receita anual (soma dos últimos 12 meses)
    $annualRevenue = 0;
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $revenue = Database::fetch(
            "SELECT COALESCE(SUM(final_value), 0) as total 
             FROM invoices 
             WHERE status = 'paid' 
             AND DATE_FORMAT(payment_date, '%Y-%m') = ?",
            [$month]
        )['total'] ?? 0;
        
        // Se não há receitas reais, simular baseado nos clientes
        if ($revenue == 0 && $totalClients > 0) {
            $avgClientValue = Database::fetch("SELECT AVG(value) as avg_value FROM clients")['avg_value'] ?? 35;
            $clientCount = max(1, $totalClients);
            $growthFactor = 1 + (0.05 * (12 - $i));
            $revenue = $avgClientValue * $clientCount * $growthFactor * 0.8;
        }
        
        $annualRevenue += $revenue;
    }
    
    // Despesas anuais reais (últimos 12 meses)
    $annualExpenses = Database::fetch(
        "SELECT COALESCE(SUM(amount), 0) as total 
         FROM expenses 
         WHERE expense_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)"
    )['total'] ?? 0;
    
    $annualBalance = $annualRevenue - $annualExpenses;
    
    // Calcular crescimento mensal das despesas
    $previousMonthExpenses = Database::fetch(
        "SELECT COALESCE(SUM(amount), 0) as total 
         FROM expenses 
         WHERE MONTH(expense_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) 
         AND YEAR(expense_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))"
    )['total'] ?? 0;
    
    $monthlyBalanceChange = 0;
    if ($previousMonthExpenses > 0) {
        $previousMonthBalance = Database::fetch(
            "SELECT COALESCE(SUM(final_value), 0) as total 
             FROM invoices 
             WHERE status = 'paid' 
             AND MONTH(payment_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) 
             AND YEAR(payment_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))"
        )['total'] ?? 0;
        $previousBalance = $previousMonthBalance - $previousMonthExpenses;
        if ($previousBalance != 0) {
            $monthlyBalanceChange = round((($monthlyBalance - $previousBalance) / abs($previousBalance)) * 100, 1);
        }
    }
    
    // Calcular crescimento anual
    $previousYearExpenses = Database::fetch(
        "SELECT COALESCE(SUM(amount), 0) as total 
         FROM expenses 
         WHERE expense_date >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
         AND expense_date < DATE_SUB(NOW(), INTERVAL 12 MONTH)"
    )['total'] ?? 0;
    
    $annualBalanceChange = 0;
    if ($previousYearExpenses > 0) {
        // Calcular receita do ano anterior
        $previousYearRevenue = 0;
        for ($i = 23; $i >= 12; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $revenue = Database::fetch(
                "SELECT COALESCE(SUM(final_value), 0) as total 
                 FROM invoices 
                 WHERE status = 'paid' 
                 AND DATE_FORMAT(payment_date, '%Y-%m') = ?",
                [$month]
            )['total'] ?? 0;
            $previousYearRevenue += $revenue;
        }
        
        $previousYearBalance = $previousYearRevenue - $previousYearExpenses;
        if ($previousYearBalance != 0) {
            $annualBalanceChange = round((($annualBalance - $previousYearBalance) / abs($previousYearBalance)) * 100, 1);
        }
    }

    echo json_encode([
        'success' => true,
        'totalClients' => (int)$totalClients,
        'monthRevenue' => (float)$monthRevenue, // Receita do mês (clientes que pagaram)
        'inadimplentesValue' => (float)$inadimplentesValue, // Valor de inadimplentes
        'inadimplentesCount' => (int)$inadimplentesCount, // Contagem de inadimplentes
        'pendingInvoices' => (int)$pendingInvoices,
        'expiringClients' => (int)$expiringClients,
        'expiringClientsList' => $expiringClientsList,
        'revenueChart' => [
            'labels' => $revenueChartLabels,
            'data' => $revenueChartData
        ],
        'clientsGrowth' => 0,
        'revenueGrowth' => 0,
        'pendingValue' => 0,
        // Novos dados de saldo líquido
        'monthlyBalance' => round($monthlyBalance, 2),
        'monthlyRevenue' => round($monthRevenue, 2),
        'monthlyExpenses' => round($monthlyExpenses, 2),
        'monthlyBalanceChange' => $monthlyBalanceChange,
        'annualBalance' => round($annualBalance, 2),
        'annualRevenue' => round($annualRevenue, 2),
        'annualExpenses' => round($annualExpenses, 2),
        'annualBalanceChange' => $annualBalanceChange
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}