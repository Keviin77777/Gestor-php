<?php
/**
 * API para gerenciamento de aplicativos
 */

// Desabilitar exibição de erros para evitar HTML na resposta JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../app/core/Database.php';
require_once '../app/helpers/functions.php';

try {
    // Verificar autenticação - método compatível com todos os servidores
    $authHeader = '';
    
    // Tentar diferentes métodos para obter o header Authorization
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
    } else {
        // Fallback para servidores que não suportam getallheaders()
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    }
    
    // Se ainda não encontrou, tentar com header em minúsculas
    if (empty($authHeader)) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    }
    
    // Verificar autenticação usando o helper
    require_once __DIR__ . '/../app/helpers/auth-helper.php';
    $user = getAuthenticatedUser();

    $pdo = Database::connect();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    
    // Extrair ID se presente na URL
    $applicationId = null;
    if (count($pathParts) > 1 && is_numeric(end($pathParts))) {
        $applicationId = (int) end($pathParts);
    }

    switch ($method) {
        case 'GET':
            if ($applicationId) {
                // Buscar aplicativo específico
                $stmt = $pdo->prepare("
                    SELECT id, name, description, created_at, updated_at 
                    FROM applications 
                    WHERE id = ? AND reseller_id = ?
                ");
                $stmt->execute([$applicationId, $user['id']]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$application) {
                    throw new Exception('Aplicativo não encontrado');
                }
                
                echo json_encode([
                    'success' => true,
                    'application' => $application
                ]);
            } else {
                // Listar todos os aplicativos
                $stmt = $pdo->prepare("
                    SELECT id, name, description, created_at, updated_at 
                    FROM applications 
                    WHERE reseller_id = ? 
                    ORDER BY name ASC
                ");
                $stmt->execute([$user['id']]);
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'applications' => $applications
                ]);
            }
            break;

        case 'POST':
            // Criar novo aplicativo
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Dados inválidos');
            }
            
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');
            
            if (empty($name)) {
                throw new Exception('Nome do aplicativo é obrigatório');
            }
            
            // Verificar se já existe um aplicativo com o mesmo nome para este reseller
            $stmt = $pdo->prepare("
                SELECT id FROM applications 
                WHERE name = ? AND reseller_id = ?
            ");
            $stmt->execute([$name, $user['id']]);
            
            if ($stmt->fetch()) {
                throw new Exception('Já existe um aplicativo com este nome');
            }
            
            // Inserir novo aplicativo
            $stmt = $pdo->prepare("
                INSERT INTO applications (reseller_id, name, description, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            
            if ($stmt->execute([$user['id'], $name, $description])) {
                $applicationId = $pdo->lastInsertId();
                
                // Buscar o aplicativo criado
                $stmt = $pdo->prepare("
                    SELECT id, name, description, created_at, updated_at 
                    FROM applications 
                    WHERE id = ?
                ");
                $stmt->execute([$applicationId]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Aplicativo criado com sucesso',
                    'application' => $application
                ]);
            } else {
                throw new Exception('Erro ao criar aplicativo');
            }
            break;

        case 'PUT':
            // Atualizar aplicativo
            if (!$applicationId) {
                throw new Exception('ID do aplicativo não fornecido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Dados inválidos');
            }
            
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');
            
            if (empty($name)) {
                throw new Exception('Nome do aplicativo é obrigatório');
            }
            
            // Verificar se o aplicativo existe e pertence ao reseller
            $stmt = $pdo->prepare("
                SELECT id FROM applications 
                WHERE id = ? AND reseller_id = ?
            ");
            $stmt->execute([$applicationId, $user['id']]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Aplicativo não encontrado');
            }
            
            // Verificar se já existe outro aplicativo com o mesmo nome para este reseller
            $stmt = $pdo->prepare("
                SELECT id FROM applications 
                WHERE name = ? AND reseller_id = ? AND id != ?
            ");
            $stmt->execute([$name, $user['id'], $applicationId]);
            
            if ($stmt->fetch()) {
                throw new Exception('Já existe um aplicativo com este nome');
            }
            
            // Atualizar aplicativo
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET name = ?, description = ?, updated_at = NOW() 
                WHERE id = ? AND reseller_id = ?
            ");
            
            if ($stmt->execute([$name, $description, $applicationId, $user['id']])) {
                // Buscar o aplicativo atualizado
                $stmt = $pdo->prepare("
                    SELECT id, name, description, created_at, updated_at 
                    FROM applications 
                    WHERE id = ?
                ");
                $stmt->execute([$applicationId]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Aplicativo atualizado com sucesso',
                    'application' => $application
                ]);
            } else {
                throw new Exception('Erro ao atualizar aplicativo');
            }
            break;

        case 'DELETE':
            // Excluir aplicativo
            if (!$applicationId) {
                throw new Exception('ID do aplicativo não fornecido');
            }
            
            // Verificar se o aplicativo existe e pertence ao reseller
            $stmt = $pdo->prepare("
                SELECT id FROM applications 
                WHERE id = ? AND reseller_id = ?
            ");
            $stmt->execute([$applicationId, $user['id']]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Aplicativo não encontrado');
            }
            
            // Excluir aplicativo
            $stmt = $pdo->prepare("
                DELETE FROM applications 
                WHERE id = ? AND reseller_id = ?
            ");
            
            if ($stmt->execute([$applicationId, $user['id']])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Aplicativo excluído com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao excluir aplicativo');
            }
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