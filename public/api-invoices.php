<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../app/core/Database.php';
    require_once __DIR__ . '/../app/helpers/functions.php';
    
    loadEnv(__DIR__ . '/../.env');
    
    $pdo = Database::connect();
    
    // Usuário padrão
    $user = ['id' => 'admin-001'];
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    
    // Extrair ID e ação
    $invoiceId = null;
    $action = null;
    
    if (count($pathParts) > 1) {
        $lastPart = $pathParts[count($pathParts) - 1];
        $secondLastPart = count($pathParts) > 2 ? $pathParts[count($pathParts) - 2] : null;
        
        if ($lastPart === 'mark-paid' && $secondLastPart) {
            $invoiceId = $secondLastPart;
            $action = 'mark-paid';
        } elseif ($lastPart !== 'invoices') {
            $invoiceId = $lastPart;
        }
    }
    
    if ($method === 'PUT' && $action === 'mark-paid') {
        // Marcar como paga
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET status = 'paid', payment_date = NOW(), updated_at = NOW() 
            WHERE id = ? AND reseller_id = ?
        ");
        
        if ($stmt->execute([$invoiceId, $user['id']])) {
            echo json_encode([
                'success' => true,
                'message' => 'Fatura marcada como paga com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao atualizar fatura');
        }
        exit;
    }
    
    if ($method === 'DELETE' && $invoiceId) {
        // Excluir fatura
        $stmt = $pdo->prepare("
            DELETE FROM invoices 
            WHERE id = ? AND reseller_id = ?
        ");
        
        if ($stmt->execute([$invoiceId, $user['id']])) {
            echo json_encode([
                'success' => true,
                'message' => 'Fatura excluída com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao excluir fatura');
        }
        exit;
    }
    
    // Listar faturas (GET)
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as client_name 
        FROM invoices i
        LEFT JOIN clients c ON i.client_id = c.id
        WHERE i.reseller_id = ? 
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Resumo simples
    $summary = [
        'pending' => ['count' => 0, 'amount' => 0],
        'paid' => ['count' => 0, 'amount' => 0],
        'overdue' => ['count' => 0, 'amount' => 0],
        'total' => ['count' => count($invoices), 'amount' => 0]
    ];
    
    foreach ($invoices as $invoice) {
        $summary['total']['amount'] += $invoice['final_value'];
        
        if ($invoice['status'] === 'paid') {
            $summary['paid']['count']++;
            $summary['paid']['amount'] += $invoice['final_value'];
        } else {
            $summary['pending']['count']++;
            $summary['pending']['amount'] += $invoice['final_value'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'invoices' => $invoices,
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>