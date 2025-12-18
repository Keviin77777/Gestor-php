<?php
// API para relatórios e estatísticas

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Auth.php';

loadEnv(__DIR__ . '/../.env');

// Verificar autenticação
$user = Auth::user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    $period = $_GET['period'] ?? 'month';
    $resellerId = $user['id'];
    
    // Calcular datas baseado no período
    $endDate = date('Y-m-d');
    switch ($period) {
        case 'week':
            // Últimos 7 dias
            $startDate = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'year':
            // Ano atual (Janeiro até hoje)
            $startDate = date('Y-01-01');
            break;
        case 'month':
        default:
            // Mês atual (dia 1 até hoje)
            $startDate = date('Y-m-01');
            break;
    }
    
    // Receita por período (baseado em faturas pagas)
    $revenueRaw = Database::fetchAll(
        "SELECT DATE(payment_date) as date, SUM(final_value) as total
         FROM invoices
         WHERE reseller_id = ? 
           AND status = 'paid' 
           AND DATE(payment_date) >= ? 
           AND DATE(payment_date) <= ?
         GROUP BY DATE(payment_date)
         ORDER BY date ASC",
        [$resellerId, $startDate, $endDate]
    );
    
    // Criar array com todos os dias do período (preenchido com zeros)
    $revenue = [];
    $currentDate = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    
    while ($currentDate <= $endDateTime) {
        $dateStr = $currentDate->format('Y-m-d');
        $value = 0;
        
        // Procurar se tem receita neste dia
        foreach ($revenueRaw as $item) {
            if ($item['date'] === $dateStr) {
                $value = (float)$item['total'];
                break;
            }
        }
        
        $revenue[] = [
            'date' => $currentDate->format('d/m'),
            'value' => $value
        ];
        
        $currentDate->modify('+1 day');
    }
    
    // Clientes novos por período
    $clientsRaw = Database::fetchAll(
        "SELECT DATE(created_at) as date, COUNT(*) as total
         FROM clients
         WHERE reseller_id = ? 
           AND DATE(created_at) >= ? 
           AND DATE(created_at) <= ?
         GROUP BY DATE(created_at)
         ORDER BY date ASC",
        [$resellerId, $startDate, $endDate]
    );
    
    // Criar array com todos os dias do período (preenchido com zeros)
    $clients = [];
    $currentDate = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    
    while ($currentDate <= $endDateTime) {
        $dateStr = $currentDate->format('Y-m-d');
        $count = 0;
        
        // Procurar se tem clientes neste dia
        foreach ($clientsRaw as $item) {
            if ($item['date'] === $dateStr) {
                $count = (int)$item['total'];
                break;
            }
        }
        
        $clients[] = [
            'date' => $currentDate->format('d/m'),
            'count' => $count
        ];
        
        $currentDate->modify('+1 day');
    }
    
    // Faturas por status (garantir que todos os status apareçam)
    $invoicesRaw = Database::fetchAll(
        "SELECT status, COUNT(*) as count, SUM(final_value) as total
         FROM invoices
         WHERE reseller_id = ?
         GROUP BY status",
        [$resellerId]
    );
    
    // Criar array com todos os status possíveis
    $allStatuses = ['pending' => 'Pendente', 'paid' => 'Pago', 'overdue' => 'Vencido', 'cancelled' => 'Cancelado'];
    $invoices = [];
    
    foreach ($allStatuses as $statusKey => $statusLabel) {
        $found = false;
        foreach ($invoicesRaw as $inv) {
            if ($inv['status'] === $statusKey) {
                $invoices[] = [
                    'status' => $statusLabel,
                    'count' => (int)$inv['count'],
                    'total' => (float)$inv['total']
                ];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $invoices[] = [
                'status' => $statusLabel,
                'count' => 0,
                'total' => 0
            ];
        }
    }
    
    // Planos mais populares
    $plansRaw = Database::fetchAll(
        "SELECT plan, COUNT(*) as count
         FROM clients
         WHERE reseller_id = ? AND status = 'active'
         GROUP BY plan
         ORDER BY count DESC
         LIMIT 5",
        [$resellerId]
    );
    
    // Formatar planos para o gráfico
    $plans = array_map(function($item) {
        return [
            'name' => $item['plan'],
            'value' => (int)$item['count']
        ];
    }, $plansRaw);
    
    // Calcular estatísticas resumidas
    // Receita total de TODAS as faturas pagas (não apenas do período)
    $totalRevenueResult = Database::fetch(
        "SELECT SUM(final_value) as total FROM invoices WHERE reseller_id = ? AND status = 'paid'",
        [$resellerId]
    );
    $totalRevenue = (float)($totalRevenueResult['total'] ?? 0);
    
    // Novos clientes do período
    $newClients = array_sum(array_column($clients, 'count'));
    
    // Faturas pagas e taxa de conversão
    $paidInvoices = 0;
    $totalInvoices = 0;
    
    foreach ($invoices as $inv) {
        $totalInvoices += $inv['count'];
        if ($inv['status'] === 'Pago') {
            $paidInvoices = $inv['count'];
        }
    }
    
    $conversionRate = $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 1) : 0;
    
    echo json_encode([
        'success' => true,
        'revenue' => $revenue,
        'clients' => $clients,
        'invoices' => $invoices,
        'plans' => $plans,
        'total_revenue' => $totalRevenue,
        'new_clients' => $newClients,
        'paid_invoices' => $paidInvoices,
        'conversion_rate' => $conversionRate,
        'period' => $period,
        'startDate' => $startDate,
        'endDate' => $endDate
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
