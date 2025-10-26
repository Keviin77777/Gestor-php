<?php
// API para estatísticas dos servidores
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Carregar Database
require_once __DIR__ . '/../app/core/Database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        getServersStats();
    } else {
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Buscar estatísticas dos servidores
 */
function getServersStats() {
    $resellerId = 'admin-001'; // Por enquanto fixo, depois pegar do token
    
    // Buscar todos os servidores com contagem de clientes e cálculo de despesas
    $serversQuery = "
        SELECT 
            s.id,
            s.name,
            s.status,
            s.cost,
            s.billing_type,
            COUNT(c.id) as client_count,
            COALESCE(SUM(c.value), 0) as total_revenue,
            CASE 
                WHEN s.billing_type = 'per_active' THEN s.cost * COUNT(c.id)
                WHEN s.billing_type = 'fixed' THEN s.cost
                ELSE s.cost
            END as total_cost
        FROM servers s
        LEFT JOIN clients c ON c.server = s.name AND c.reseller_id = s.user_id
        WHERE s.user_id = ?
        GROUP BY s.id, s.name, s.status, s.cost, s.billing_type
        ORDER BY client_count DESC, s.name ASC
    ";
    
    $servers = Database::fetchAll($serversQuery, [$resellerId]);
    
    // Se não há servidores no banco, criar dados de exemplo
    if (empty($servers)) {
        $servers = generateSampleServersData();
    }
    
    // Calcular estatísticas gerais
    $totalClients = array_sum(array_column($servers, 'client_count'));
    $totalRevenue = array_sum(array_column($servers, 'total_revenue'));
    $totalServerCosts = array_sum(array_column($servers, 'total_cost'));
    $activeServers = count(array_filter($servers, function($s) { return $s['status'] === 'active'; }));
    $totalServers = count($servers);
    $averageClients = $totalServers > 0 ? round($totalClients / $totalServers, 1) : 0;
    
    // Pegar apenas o top 5
    $topServers = array_slice($servers, 0, 5);
    
    // Calcular percentuais
    $topServersFormatted = array_map(function($server, $index) use ($totalClients, $totalRevenue) {
        $clientPercentage = $totalClients > 0 ? round(($server['client_count'] / $totalClients) * 100, 1) : 0;
        $revenuePercentage = $totalRevenue > 0 ? round(($server['total_revenue'] / $totalRevenue) * 100, 1) : 0;
        
        return [
            'id' => $server['id'],
            'name' => $server['name'],
            'status' => $server['status'],
            'cost' => (float)$server['cost'],
            'total_cost' => (float)$server['total_cost'],
            'billing_type' => $server['billing_type'],
            'client_count' => (int)$server['client_count'],
            'total_revenue' => (float)$server['total_revenue'],
            'client_percentage' => $clientPercentage,
            'revenue_percentage' => $revenuePercentage,
            'rank' => $index + 1,
            'color' => getServerColor($index)
        ];
    }, $topServers, array_keys($topServers));
    
    // Estatísticas do top 5
    $topStats = [
        'total_clients' => array_sum(array_column($topServersFormatted, 'client_count')),
        'total_revenue' => array_sum(array_column($topServersFormatted, 'total_revenue')),
        'total_costs' => array_sum(array_column($topServersFormatted, 'total_cost')),
        'active_servers' => count(array_filter($topServersFormatted, function($s) { return $s['status'] === 'active'; })),
        'total_servers_in_top' => count($topServersFormatted),
        'average_clients' => count($topServersFormatted) > 0 ? round(array_sum(array_column($topServersFormatted, 'client_count')) / count($topServersFormatted), 1) : 0
    ];
    
    echo json_encode([
        'success' => true,
        'servers' => $topServersFormatted,
        'stats' => [
            'total_clients' => $totalClients,
            'total_revenue' => $totalRevenue,
            'total_costs' => $totalServerCosts,
            'active_servers' => $activeServers,
            'total_servers' => $totalServers,
            'average_clients' => $averageClients,
            'top_stats' => $topStats
        ]
    ]);
}

/**
 * Gerar dados de exemplo para servidores
 */
function generateSampleServersData() {
    return [
        [
            'id' => 'srv-001',
            'name' => 'Servidor Principal',
            'status' => 'active',
            'cost' => 150.00,
            'billing_type' => 'fixed',
            'client_count' => 45,
            'total_revenue' => 1575.00,
            'total_cost' => 150.00
        ],
        [
            'id' => 'srv-002', 
            'name' => 'Servidor Backup',
            'status' => 'active',
            'cost' => 5.00,
            'billing_type' => 'per_active',
            'client_count' => 32,
            'total_revenue' => 1120.00,
            'total_cost' => 160.00 // 5.00 * 32 clientes
        ],
        [
            'id' => 'srv-003',
            'name' => 'Servidor Premium',
            'status' => 'active',
            'cost' => 200.00,
            'billing_type' => 'fixed',
            'client_count' => 28,
            'total_revenue' => 1400.00,
            'total_cost' => 200.00
        ],
        [
            'id' => 'srv-004',
            'name' => 'Servidor Regional',
            'status' => 'active',
            'cost' => 3.50,
            'billing_type' => 'per_active',
            'client_count' => 18,
            'total_revenue' => 630.00,
            'total_cost' => 63.00 // 3.50 * 18 clientes
        ],
        [
            'id' => 'srv-005',
            'name' => 'Servidor Teste',
            'status' => 'inactive',
            'cost' => 80.00,
            'billing_type' => 'fixed',
            'client_count' => 5,
            'total_revenue' => 175.00,
            'total_cost' => 80.00
        ]
    ];
}

/**
 * Obter cor para o servidor baseado no índice
 */
function getServerColor($index) {
    $colors = [
        '#6366f1', // Azul primário
        '#10b981', // Verde
        '#f59e0b', // Amarelo
        '#ef4444', // Vermelho
        '#8b5cf6', // Roxo
        '#06b6d4', // Ciano
        '#f97316', // Laranja
        '#84cc16', // Lima
        '#ec4899', // Rosa
        '#64748b'  // Cinza
    ];
    
    return $colors[$index % count($colors)];
}
?>