<?php
// API para gerenciar planos

// CORS - deve vir antes de qualquer output
require_once __DIR__ . '/../app/helpers/cors.php';

header('Content-Type: application/json');

// Iniciar sessão antes de qualquer coisa
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Carregar Database e Auth
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// Verificar autenticação
$user = Auth::user();
if (!$user && isset($_SESSION['user_id'])) {
    $user = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'reseller'
    ];
}

// TEMPORÁRIO: Para desenvolvimento, usar ID fixo se não houver usuário
// TODO: Remover isso em produção
if (!$user) {
    // Buscar primeiro usuário do banco para desenvolvimento
    $firstUser = Database::fetch("SELECT id FROM users LIMIT 1");
    if ($firstUser) {
        $resellerId = $firstUser['id'];
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Não autorizado']);
        exit;
    }
} else {
    $resellerId = $user['id'];
}

try {
    switch ($method) {
        case 'GET':
            // Verificar se é uma requisição para servidores
            if (isset($_GET['action']) && $_GET['action'] === 'servers') {
                // Buscar servidores reais da tabela servers
                $servers = Database::fetchAll(
                    "SELECT id, name, status FROM servers WHERE user_id = ? AND status = 'active' ORDER BY name ASC",
                    [$resellerId]
                );
                
                echo json_encode([
                    'success' => true,
                    'servers' => $servers
                ]);
                break;
            }
            
            // Buscar planos do reseller atual
            
            // Buscar planos com informações do servidor
            $plans = Database::fetchAll(
                "SELECT 
                    p.id, 
                    p.name, 
                    p.description, 
                    p.price, 
                    p.duration_days, 
                    p.max_screens, 
                    p.features, 
                    p.is_active as status, 
                    p.created_at,
                    p.server_id,
                    s.name as server_name
                 FROM subscription_plans p
                 LEFT JOIN servers s ON p.server_id = s.id
                 WHERE p.reseller_id = ?
                 ORDER BY s.name ASC, p.price ASC",
                [$resellerId]
            );
            
            // Se não há planos, buscar servidores para criar dados de exemplo
            if (empty($plans)) {
                $servers = Database::fetchAll(
                    "SELECT id, name FROM servers WHERE user_id = ? AND status = 'active' ORDER BY name ASC",
                    [$resellerId]
                );
                
                // Criar alguns planos de exemplo se há servidores
                if (!empty($servers)) {
                    $examplePlans = [];
                    foreach ($servers as $index => $server) {
                        $planId = 'plan-' . uniqid();
                        $planName = 'Plano ' . ($index === 0 ? 'Básico' : ($index === 1 ? 'Premium' : 'VIP'));
                        $price = ($index + 1) * 25.00;
                        
                        Database::query(
                            "INSERT INTO subscription_plans (id, reseller_id, server_id, name, description, price, duration_days, max_screens, features, is_active) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [
                                $planId,
                                $resellerId,
                                $server['id'],
                                $planName,
                                'Plano de exemplo para ' . $server['name'],
                                $price,
                                30,
                                1,
                                json_encode(['hd' => true, 'fullhd' => $index > 0]),
                                1
                            ]
                        );
                        
                        $examplePlans[] = [
                            'id' => $planId,
                            'name' => $planName,
                            'description' => 'Plano de exemplo para ' . $server['name'],
                            'price' => $price,
                            'duration_days' => 30,
                            'max_screens' => 1,
                            'features' => ['hd' => true, 'fullhd' => $index > 0],
                            'status' => 'active',
                            'server_id' => $server['id'],
                            'server_name' => $server['name'],
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    $plans = $examplePlans;
                }
            }
            
            // Formatar dados para o frontend
            $formattedPlans = array_map(function($plan) {
                return [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'price' => (float)$plan['price'],
                    'duration_days' => (int)$plan['duration_days'],
                    'max_screens' => (int)($plan['max_screens'] ?? 1),
                    'features' => is_string($plan['features']) ? json_decode($plan['features'], true) ?? [] : ($plan['features'] ?? []),
                    'status' => is_bool($plan['status']) ? ($plan['status'] ? 'active' : 'inactive') : ($plan['status'] ? 'active' : 'inactive'),
                    'server_id' => $plan['server_id'],
                    'server_name' => $plan['server_name'] ?? 'Servidor Desconhecido',
                    'created_at' => $plan['created_at']
                ];
            }, $plans);
            
            echo json_encode([
                'success' => true,
                'plans' => $formattedPlans,
                'total' => count($formattedPlans)
            ]);
            break;
            
        case 'POST':
            // Criar novo plano
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data['name'] || !$data['price'] || !$data['server_id']) {
                throw new Exception('Campos obrigatórios: name, price, server_id');
            }
            $features = json_encode($data['features'] ?? []);
            $planId = 'plan-' . uniqid();
            
            Database::query(
                "INSERT INTO subscription_plans (id, reseller_id, server_id, name, description, price, duration_days, max_screens, features, is_active) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $planId,
                    $resellerId,
                    $data['server_id'],
                    $data['name'],
                    $data['description'] ?? '',
                    $data['price'],
                    $data['duration_days'] ?? 30,
                    $data['max_screens'] ?? 1,
                    $features,
                    ($data['status'] ?? 'active') === 'active' ? 1 : 0
                ]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Plano criado com sucesso',
                'id' => $planId
            ]);
            break;
            
        case 'PUT':
            // Atualizar plano
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID do plano é obrigatório');
            }
            
            $features = json_encode($data['features'] ?? []);
            
            Database::query(
                "UPDATE subscription_plans 
                 SET server_id = ?, name = ?, description = ?, price = ?, duration_days = ?, 
                     max_screens = ?, features = ?, is_active = ?
                 WHERE id = ? AND reseller_id = ?",
                [
                    $data['server_id'],
                    $data['name'],
                    $data['description'] ?? '',
                    $data['price'],
                    $data['duration_days'] ?? 30,
                    $data['max_screens'] ?? 1,
                    $features,
                    ($data['status'] ?? 'active') === 'active' ? 1 : 0,
                    $id,
                    $resellerId
                ]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Plano atualizado com sucesso'
            ]);
            break;
            
        case 'PATCH':
            // Atualizar status do plano
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID do plano é obrigatório');
            }
            
            if (!isset($data['status'])) {
                throw new Exception('Status é obrigatório');
            }
            
            Database::query(
                "UPDATE subscription_plans 
                 SET is_active = ?
                 WHERE id = ? AND reseller_id = ?",
                [
                    ($data['status'] === 'active') ? 1 : 0,
                    $id,
                    $resellerId
                ]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Status do plano atualizado com sucesso'
            ]);
            break;
            
        case 'DELETE':
            // Deletar plano
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID do plano é obrigatório');
            }
            
            // Verificar se há clientes usando este plano específico
            // Buscar informações do plano incluindo servidor
            $planInfo = Database::fetch(
                "SELECT p.name, p.server_id, s.name as server_name 
                 FROM subscription_plans p 
                 LEFT JOIN servers s ON p.server_id = s.id 
                 WHERE p.id = ? AND p.reseller_id = ?",
                [$id, $resellerId]
            );
            
            if (!$planInfo) {
                throw new Exception('Plano não encontrado');
            }
            
            // Verificar clientes que usam este plano específico (por ID é mais preciso)
            $clientsCount = Database::fetch(
                "SELECT COUNT(*) as total FROM clients WHERE plan_id = ? AND reseller_id = ?",
                [$id, $resellerId]
            )['total'];
            
            // Se não encontrou por ID, verificar por nome + servidor (para clientes antigos)
            if ($clientsCount == 0) {
                $clientsCount = Database::fetch(
                    "SELECT COUNT(*) as total FROM clients c
                     WHERE c.reseller_id = ? 
                     AND c.plan_id IS NULL 
                     AND c.plan = ? 
                     AND c.server = ?",
                    [$resellerId, $planInfo['name'], $planInfo['server_name']]
                )['total'];
            }
            
            if ($clientsCount > 0) {
                $serverInfo = $planInfo['server_name'] ? " no servidor \"{$planInfo['server_name']}\"" : "";
                throw new Exception("Não é possível excluir o plano \"{$planInfo['name']}\"{$serverInfo}. Há {$clientsCount} cliente(s) usando este plano.");
            }
            
            Database::query("DELETE FROM subscription_plans WHERE id = ? AND reseller_id = ?", [$id, $resellerId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Plano deletado com sucesso'
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