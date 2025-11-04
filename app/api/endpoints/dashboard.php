<?php
/**
 * Endpoints do Dashboard
 */

// Carregar classes necessárias
require_once __DIR__ . '/../../core/Response.php';

/**
 * Obter métricas do dashboard
 */
function getMetrics() {
    // Verificar autenticação
    $user = Auth::user();
    if (!$user) {
        Response::error('Não autorizado', 401);
    }
    
    $userId = $user['id'];
    $isAdmin = $user['role'] === 'admin';
    
    try {
        // Cada usuário vê apenas seus próprios dados (incluindo admin)
        $whereClause = 'WHERE reseller_id = ?';
        $params = [$userId];
        
        // Total de clientes
        $totalClients = Database::fetch(
            "SELECT COUNT(*) as total FROM clients $whereClause",
            $params
        )['total'] ?? 0;
        
        // Clientes ativos
        $activeClients = Database::fetch(
            "SELECT COUNT(*) as total FROM clients WHERE reseller_id = ? AND status = 'active'",
            [$userId]
        )['total'] ?? 0;
        
        // Crescimento de clientes (comparar com mês anterior)
        $lastMonthClients = Database::fetch(
            "SELECT COUNT(*) as total FROM clients $whereClause 
             AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            $params
        )['total'] ?? 1;
        
        $clientsGrowth = $lastMonthClients > 0 
            ? round((($totalClients - $lastMonthClients) / $lastMonthClients) * 100, 1)
            : 0;
        
        // Receita do mês atual
        $monthRevenue = Database::fetch(
            "SELECT COALESCE(SUM(final_value), 0) as total 
             FROM invoices 
             $whereClause 
             AND status = 'paid' 
             AND MONTH(payment_date) = MONTH(NOW()) 
             AND YEAR(payment_date) = YEAR(NOW())",
            $params
        )['total'] ?? 0;
        
        // Receita do mês anterior
        $lastMonthRevenue = Database::fetch(
            "SELECT COALESCE(SUM(final_value), 0) as total FROM invoices WHERE reseller_id = ? AND status = 'paid' AND MONTH(payment_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(payment_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))",
            [$userId]
        )['total'] ?? 1;
        
        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;
        
        // Faturas pendentes
        $pendingInvoices = Database::fetch(
            "SELECT COUNT(*) as total FROM invoices $whereClause AND status = 'pending'",
            $params
        )['total'] ?? 0;
        
        // Valor pendente
        $pendingValue = Database::fetch(
            "SELECT COALESCE(SUM(final_value), 0) as total 
             FROM invoices 
             $whereClause 
             AND status = 'pending'",
            $params
        )['total'] ?? 0;
        
        // Clientes que expiram HOJE
        $expiringClients = Database::fetch(
            "SELECT COUNT(*) as total 
             FROM clients 
             $whereClause 
             AND status = 'active' 
             AND DATE(renewal_date) = CURDATE()",
            $params
        )['total'] ?? 0;
        
        // Lista de clientes que expiram hoje
        $expiringClientsList = Database::fetchAll(
            "SELECT id, name, renewal_date, status 
             FROM clients 
             $whereClause 
             AND status = 'active' 
             AND DATE(renewal_date) = CURDATE()
             ORDER BY renewal_date ASC 
             LIMIT 5",
            $params
        );
        
        // Dados do gráfico de receitas (últimos 6 meses)
        $revenueChartData = [];
        $revenueChartLabels = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M', strtotime("-$i months"));
            
            $revenue = Database::fetch(
                "SELECT COALESCE(SUM(final_value), 0) as total FROM invoices WHERE reseller_id = ? AND status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?",
                [$userId, $month]
            )['total'] ?? 0;
            
            $revenueChartLabels[] = $monthName;
            $revenueChartData[] = (float)$revenue;
        }
        
        Response::json([
            'success' => true,
            'totalClients' => (int)$totalClients,
            'activeClients' => (int)$activeClients,
            'clientsGrowth' => (float)$clientsGrowth,
            'monthRevenue' => (float)$monthRevenue,
            'revenueGrowth' => (float)$revenueGrowth,
            'pendingInvoices' => (int)$pendingInvoices,
            'pendingValue' => (float)$pendingValue,
            'expiringClients' => (int)$expiringClients,
            'expiringClientsList' => $expiringClientsList,
            'revenueChart' => [
                'labels' => $revenueChartLabels,
                'data' => $revenueChartData
            ]
        ]);
        
    } catch (Exception $e) {
        logError('Dashboard metrics error: ' . $e->getMessage());
        Response::error('Erro ao carregar métricas', 500);
    }
}

// Executar a função
getMetrics();
