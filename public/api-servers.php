<?php
/**
 * API pública para servidores
 */

// Log de debug
error_log("=== API SERVERS DEBUG ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("URI: " . $_SERVER['REQUEST_URI']);
error_log("Query String: " . ($_SERVER['QUERY_STRING'] ?? 'empty'));
error_log("GET params: " . json_encode($_GET));

// Limpar qualquer output anterior
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Suprimir warnings e notices em produção
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// Garantir que não há espaços em branco antes do JSON
ob_clean();

header('Content-Type: application/json; charset=utf-8');
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
            // Tentar pegar ID do query parameter primeiro (compatibilidade com Nginx)
            $serverId = $_GET['id'] ?? null;
            
            // Se não encontrou no query, procurar no path
            if (!$serverId) {
                foreach ($pathParts as $part) {
                    if (is_numeric($part)) {
                        $serverId = $part;
                        break;
                    }
                }
            }
            
            if ($serverId) {
                // PUT /api-servers.php?id={id} ou /api-servers.php/{id}
                updateServer($serverId);
            } else {
                error_log("PUT - Servidor ID não encontrado. Query: " . json_encode($_GET) . ", Path parts: " . json_encode($pathParts));
                Response::json(['success' => false, 'error' => 'ID do servidor é obrigatório'], 400);
            }
            break;
            
        case 'DELETE':
            // Tentar pegar ID do query parameter primeiro (compatibilidade com Nginx)
            $serverId = $_GET['id'] ?? null;
            
            // Se não encontrou no query, procurar no path
            if (!$serverId) {
                foreach ($pathParts as $part) {
                    if (is_numeric($part)) {
                        $serverId = $part;
                        break;
                    }
                }
            }
            
            if ($serverId) {
                // DELETE /api-servers.php?id={id} ou /api-servers.php/{id}
                deleteServer($serverId);
            } else {
                error_log("DELETE - Servidor ID não encontrado. Query: " . json_encode($_GET) . ", Path parts: " . json_encode($pathParts));
                Response::json(['success' => false, 'error' => 'ID do servidor é obrigatório'], 400);
            }
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
} catch (Exception $e) {
    error_log("API Servers error: " . $e->getMessage());
    error_log("API Servers stack trace: " . $e->getTraceAsString());
    
    // Limpar qualquer output que possa ter sido gerado
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Garantir que apenas JSON seja retornado
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Garantir que o buffer seja enviado corretamente
$output = ob_get_clean();
if ($output) {
    echo $output;
}