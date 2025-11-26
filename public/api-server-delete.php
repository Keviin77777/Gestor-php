<?php
/**
 * API específica para deletar servidor
 * Endpoint alternativo para compatibilidade com Nginx
 */

// Log de debug
error_log("=== API SERVER DELETE ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("GET: " . json_encode($_GET));

// Limpar output
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Aceitar POST com _method=DELETE para compatibilidade com Nginx
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (isset($data['_method']) && $data['_method'] === 'DELETE') {
        $method = 'DELETE';
        error_log("DELETE - Método convertido de POST para DELETE");
    }
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Response.php';

try {
    if ($method !== 'DELETE') {
        error_log("DELETE - Método inválido: $method");
        Response::json(['success' => false, 'error' => 'Método não permitido: ' . $method], 405);
    }
    
    // Pegar ID do servidor (pode vir do GET ou do body)
    $serverId = $_GET['id'] ?? null;
    
    if (!$serverId && isset($data['id'])) {
        $serverId = $data['id'];
        error_log("DELETE - ID obtido do body: $serverId");
    }
    
    if (!$serverId) {
        error_log("DELETE - ID não fornecido");
        Response::json(['success' => false, 'error' => 'ID do servidor é obrigatório'], 400);
    }
    
    error_log("DELETE - Servidor ID: $serverId");
    
    // Verificar autenticação
    $user = Auth::user();
    if (!$user) {
        error_log("DELETE - Usuário não autenticado");
        Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
    }
    
    error_log("DELETE - Usuário autenticado: " . $user['id']);
    
    $db = Database::connect();
    
    // Verificar se o servidor existe
    $stmt = $db->prepare("SELECT id, name FROM servers WHERE id = ? AND user_id = ?");
    $stmt->execute([$serverId, $user['id']]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$server) {
        error_log("DELETE - Servidor não encontrado");
        Response::json(['success' => false, 'error' => 'Servidor não encontrado'], 404);
    }
    
    error_log("DELETE - Servidor encontrado: " . $server['name']);
    
    // Deletar
    $stmt = $db->prepare("DELETE FROM servers WHERE id = ? AND user_id = ?");
    $stmt->execute([$serverId, $user['id']]);
    
    error_log("DELETE - Servidor excluído. Rows: " . $stmt->rowCount());
    
    Response::json([
        'success' => true,
        'message' => 'Servidor excluído com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log("DELETE - Erro: " . $e->getMessage());
    
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao excluir servidor: ' . $e->getMessage()
    ]);
    exit;
}
