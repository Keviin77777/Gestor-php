<?php
/**
 * API para testar conexão com Sigma
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/helpers/sigma-integration.php';

$method = $_SERVER['REQUEST_METHOD'];

// Verificar autenticação
$user = Auth::user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Se usar token salvo, buscar do banco de dados
        if (!empty($data['use_saved_token']) && !empty($data['server_id'])) {
            require_once __DIR__ . '/../app/core/Database.php';
            
            $db = Database::connect();
            
            // Buscar dados do servidor
            $stmt = $db->prepare("SELECT panel_url, sigma_token, reseller_user FROM servers WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['server_id'], $user['id']]);
            $server = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$server) {
                throw new Exception('Servidor não encontrado');
            }
            
            // Usar dados do banco, mas permitir override da URL e usuário se fornecidos
            $panelUrl = !empty($data['panel_url']) ? $data['panel_url'] : $server['panel_url'];
            $resellerUser = !empty($data['reseller_user']) ? $data['reseller_user'] : $server['reseller_user'];
            $sigmaToken = $server['sigma_token'];
            
            if (empty($panelUrl) || empty($sigmaToken) || empty($resellerUser)) {
                throw new Exception('Dados de integração incompletos no servidor salvo');
            }
            
        } else {
            // Validação normal
            if (!$data || !isset($data['panel_url']) || !isset($data['sigma_token']) || !isset($data['reseller_user'])) {
                throw new Exception('Dados obrigatórios: panel_url, sigma_token, reseller_user');
            }
            
            $panelUrl = $data['panel_url'];
            $sigmaToken = $data['sigma_token'];
            $resellerUser = $data['reseller_user'];
        }
        
        $result = testSigmaConnectionHelper($panelUrl, $sigmaToken, $resellerUser);
        
        echo json_encode($result);
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