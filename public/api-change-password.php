<?php
/**
 * API para alteração de senha
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Auth.php';

try {
    // Carregar variáveis de ambiente
    loadEnv(__DIR__ . '/../.env');
    
    // Iniciar sessão
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar autenticação
    $currentUser = Auth::user();
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Não autenticado']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos');
    }
    
    // Validar campos obrigatórios
    if (empty($input['current_password']) || empty($input['new_password'])) {
        throw new Exception('Senha atual e nova senha são obrigatórias');
    }
    
    // Validar nova senha
    if (!isValidPassword($input['new_password'])) {
        throw new Exception('A nova senha deve ter pelo menos 8 caracteres, incluindo maiúscula, minúscula e número');
    }
    
    // Buscar usuário atual
    $user = Database::fetch("
        SELECT id, password 
        FROM users 
        WHERE id = ?
    ", [$currentUser['id']]);
    
    if (!$user) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Verificar senha atual
    if (!password_verify($input['current_password'], $user['password'])) {
        throw new Exception('Senha atual incorreta');
    }
    
    // Gerar hash da nova senha
    $newPasswordHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
    
    // Atualizar senha
    $updated = Database::execute("
        UPDATE users SET 
            password = ?,
            updated_at = NOW()
        WHERE id = ?
    ", [$newPasswordHash, $currentUser['id']]);
    
    if (!$updated) {
        throw new Exception('Erro ao alterar senha');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Senha alterada com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao alterar senha: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Validar se a senha atende aos requisitos
 */
function isValidPassword($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/\d/', $password);
}
?>