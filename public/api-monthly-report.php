<?php
// API para relatório mensal detalhado

require_once __DIR__ . '/../app/helpers/cors.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

$user = Auth::user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    $year = $_GET['year'] ?? date('Y');
    $resellerId = $user['id'];
    
    // Meses em português completo
    $monthNames = [
        '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
        '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
        '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
    ];
    
    $months = [];
    
    // Gerar dados detalhados para cada mês
    for ($month = 1; $month <= 12; $month++) {
        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
        $startDate = "$year-$monthStr-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Vendas (faturas pagas)
        $vendas = Database::fetch(
            "SELECT COALESCE(SUM(final_value), 0) as total 
             FROM invoices 
             WHERE reseller_id = ? 
             AND status = 'paid' 
             AND payment_date BETWEEN ? AND ?",
            [$resellerId, $startDate, $endDate]
        );
        
        // Entradas (todas as receitas)
        $entradas = $vendas['total'];
        
        // Saídas (despesas - se tiver tabela de despesas)
        $saidas = 0;
        
        // Custos de servidores - APENAS servidores que já existiam neste mês
        // Verifica se o servidor foi criado ANTES ou DURANTE este mês
        $custos = Database::fetch(
            "SELECT COALESCE(SUM(cost), 0) as total 
             FROM servers 
             WHERE user_id = ? 
             AND status = 'active'
             AND (YEAR(created_at) < ? 
             OR (YEAR(created_at) = ? AND MONTH(created_at) <= ?))",
            [$resellerId, $year, $year, $month]
        );
        
        $vendasValue = (float)$vendas['total'];
        $entradasValue = (float)$entradas;
        $saidasValue = (float)$saidas;
        $custosValue = (float)$custos['total'];
        $saldoValue = $entradasValue - $saidasValue - $custosValue;
        
        $months[] = [
            'month' => $monthStr,
            'monthName' => $monthNames[$monthStr],
            'vendas' => $vendasValue,
            'entradas' => $entradasValue,
            'saidas' => $saidasValue,
            'custos' => $custosValue,
            'saldo' => $saldoValue
        ];
    }
    
    echo json_encode([
        'success' => true,
        'months' => $months
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
