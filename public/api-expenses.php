<?php
// API para gerenciar despesas
header('Content-Type: application/json');

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Carregar Database
require_once __DIR__ . '/../app/core/Database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Listar despesas
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $offset = ($page - 1) * $limit;
            
            $expenses = Database::fetchAll(
                "SELECT * FROM expenses 
                 ORDER BY expense_date DESC, created_at DESC 
                 LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
            
            $total = Database::fetch("SELECT COUNT(*) as total FROM expenses")['total'];
            
            echo json_encode([
                'success' => true,
                'expenses' => $expenses,
                'total' => (int)$total,
                'page' => (int)$page,
                'limit' => (int)$limit,
                'pages' => ceil($total / $limit)
            ]);
            break;
            
        case 'POST':
            // Criar nova despesa
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data['description'] || !$data['amount'] || !$data['expense_date']) {
                throw new Exception('Campos obrigatórios: description, amount, expense_date');
            }
            
            $id = Database::insert(
                "INSERT INTO expenses (description, category, amount, expense_date, payment_method, notes) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $data['description'],
                    $data['category'] ?? 'Geral',
                    $data['amount'],
                    $data['expense_date'],
                    $data['payment_method'] ?? 'money',
                    $data['notes'] ?? ''
                ]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Despesa criada com sucesso',
                'id' => $id
            ]);
            break;
            
        case 'PUT':
            // Atualizar despesa
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID da despesa é obrigatório');
            }
            
            Database::execute(
                "UPDATE expenses 
                 SET description = ?, category = ?, amount = ?, expense_date = ?, 
                     payment_method = ?, notes = ?, updated_at = NOW()
                 WHERE id = ?",
                [
                    $data['description'],
                    $data['category'],
                    $data['amount'],
                    $data['expense_date'],
                    $data['payment_method'],
                    $data['notes'],
                    $id
                ]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Despesa atualizada com sucesso'
            ]);
            break;
            
        case 'DELETE':
            // Deletar despesa
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID da despesa é obrigatório');
            }
            
            Database::execute("DELETE FROM expenses WHERE id = ?", [$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Despesa deletada com sucesso'
            ]);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>