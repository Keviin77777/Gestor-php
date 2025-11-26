<?php
/**
 * API pública para servidores
 */

// Limpar qualquer output anterior
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Suprimir warnings e notices em produção
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Request.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Response.php';
require_once __DIR__ . '/../app/helpers/sigma-integration.php';

// Incluir funções do endpoint
require_once __DIR__ . '/../app/api/endpoints/servers.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Parse da URL para obter parâmetros
$urlParts = parse_url($path);
$pathParts = explode('/', trim($urlParts['path'], '/'));

try {
    switch ($method) {
        case 'GET':
            if (isset($pathParts[2]) && $pathParts[2] === 'test-sigma') {
                // GET /api-servers.php/test-sigma
                testSigmaConnection();
            } elseif (isset($pathParts[2]) && is_numeric($pathParts[2]) && isset($pathParts[3]) && $pathParts[3] === 'packages') {
                // GET /api-servers.php/{id}/packages
                getSigmaPackages($pathParts[2]);
            } else {
                // GET /api-servers.php
                getServers();
            }
            break;
            
        case 'POST':
            if (isset($pathParts[2]) && $pathParts[2] === 'test-sigma') {
                // POST /api-servers.php/test-sigma
                testSigmaConnection();
            } else {
                // POST /api-servers.php
                createServer();
            }
            break;
            
        case 'PUT':
            // Procurar pelo ID do servidor nos path parts
            $serverId = null;
            foreach ($pathParts as $part) {
                if (is_numeric($part)) {
                    $serverId = $part;
                    break;
                }
            }
            
            if ($serverId) {
                // PUT /api-servers.php/{id}
                updateServer($serverId);
            } else {
                error_log("PUT - Servidor ID não encontrado. Path parts: " . json_encode($pathParts));
                Response::json(['success' => false, 'error' => 'ID do servidor é obrigatório'], 400);
            }
            break;
            
        case 'DELETE':
            // Procurar pelo ID do servidor nos path parts
            $serverId = null;
            foreach ($pathParts as $part) {
                if (is_numeric($part)) {
                    $serverId = $part;
                    break;
                }
            }
            
            if ($serverId) {
                // DELETE /api-servers.php/{id}
                deleteServer($serverId);
            } else {
                error_log("DELETE - Servidor ID não encontrado. Path parts: " . json_encode($pathParts));
                Response::json(['success' => false, 'error' => 'ID do servidor é obrigatório'], 400);
            }
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
} catch (Exception $e) {
    error_log("API Servers error: " . $e->getMessage());
    
    // Limpar qualquer output que possa ter sido gerado
    if (ob_get_length()) {
        ob_clean();
    }
    
    Response::json([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ], 500);
}

// Garantir que o buffer seja enviado
if (ob_get_length()) {
    ob_end_flush();
}