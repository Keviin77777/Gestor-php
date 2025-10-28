<?php
/**
 * UltraGestor - Sistema de Gestão IPTV
 * Entry Point da Aplicação
 */

// Iniciar sessão
session_start();

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Carregar autoloader
require_once __DIR__ . '/../app/helpers/functions.php';

// Carregar variáveis de ambiente
loadEnv(__DIR__ . '/../.env');

// Configurações de erro baseadas no ambiente
if (env('APP_DEBUG', true)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Handler de erros global
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logError("PHP Error [$errno]: $errstr in $errfile on line $errline");
    if (env('APP_DEBUG', true)) {
        echo "<pre>Error [$errno]: $errstr\nFile: $errfile\nLine: $errline</pre>";
    }
});

// Handler de exceções global
set_exception_handler(function($e) {
    logError("Uncaught Exception: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    if (env('APP_DEBUG', true)) {
        echo "<pre>";
        echo "Uncaught Exception: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "Trace:\n" . $e->getTraceAsString();
        echo "</pre>";
    } else {
        http_response_code(500);
        echo "Internal Server Error";
    }
});

// Carregar classes core
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Request.php';
require_once __DIR__ . '/../app/core/Response.php';
require_once __DIR__ . '/../app/core/Auth.php';

// Inicializar router
$router = new Router();

// Obter método e URI
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Remover trailing slash
$uri = rtrim($uri, '/');
if (empty($uri)) $uri = '/';

// Rotas públicas (sem autenticação)
$router->get('/', function() {
    if (Auth::check()) {
        Response::redirect('/dashboard');
    }
    Response::redirect('/login');
});

$router->get('/install-db', function() {
    // Script de instalação da tabela servers
    require_once __DIR__ . '/../app/helpers/functions.php';
    loadEnv(__DIR__ . '/../.env');
    
    $host = env('DB_HOST', 'localhost');
    $dbname = env('DB_NAME', 'ultragestor_php');
    $username = env('DB_USER', 'root');
    $password = env('DB_PASS', '');
    
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        $sql = "
        CREATE TABLE IF NOT EXISTS servers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            name VARCHAR(255) NOT NULL,
            billing_type ENUM('fixed', 'per_active') NOT NULL DEFAULT 'fixed',
            cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            panel_type VARCHAR(50) NULL,
            panel_url VARCHAR(255) NULL,
            reseller_user VARCHAR(100) NULL,
            sigma_token VARCHAR(500) NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        
        echo '<html><head><title>Instalação Concluída</title><style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}h1{color:#6366f1;}pre{background:#fff;padding:15px;border-radius:8px;overflow-x:auto;}</style></head><body>';
        echo '<h1>✅ Tabela de Servidores Instalada com Sucesso!</h1>';
        echo '<p>Banco de dados: <strong>' . $dbname . '</strong></p>';
        
        $stmt = $pdo->query("DESCRIBE servers");
        $columns = $stmt->fetchAll();
        
        echo '<h2>Estrutura da Tabela:</h2>';
        echo '<table border="1" cellpadding="8" cellspacing="0" style="background:#fff;width:100%;">';
        echo '<tr style="background:#6366f1;color:#fff;"><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>';
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr";
        }
        echo '</table>';
        
        echo '<hr><h2>Próximos Passos:</h2>';
        echo '<ol><li><a href="/servidores">Acessar página de Servidores</a></li><li>Teste adicionar um servidor</li><li>Depois remova a rota /install-db do index.php</li></ol>';
        echo '</body></html>';
        
    } catch (PDOException $e) {
        echo '<html><head><title>Erro na Instalação</title><style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}h1{color:#ef4444;}</style></head><body>';
        echo '<h1>❌ Erro na Instalação</h1>';
        echo '<p style="color:#ef4444;font-weight:bold;">' . $e->getMessage() . '</p>';
        echo '<h2>Verifique:</h2><ul><li>MySQL está rodando</li><li>Banco ultragestor_php existe</li><li>Credenciais no .env estão corretas</li></ul>';
        echo '</body></html>';
    }
});

$router->get('/login', function() {
    require __DIR__ . '/../app/views/auth/login.php';
});

$router->get('/register', function() {
    require __DIR__ . '/../app/views/auth/register.php';
});

$router->post('/api/auth/login', function() {
    require __DIR__ . '/../app/api/endpoints/auth.php';
    handleLogin();
});

$router->post('/api/auth/register', function() {
    require __DIR__ . '/../app/api/endpoints/auth.php';
    handleRegister();
});

// Rotas protegidas (requerem autenticação)
$router->get('/dashboard', function() {
    // Para views HTML, não requer auth no servidor
    // A autenticação é verificada no JavaScript
    require __DIR__ . '/../app/views/dashboard/index.php';
});

$router->get('/clients', function() {
    require __DIR__ . '/../app/views/clients/index.php';
});

$router->get('/plans', function() {
    require __DIR__ . '/../app/views/plans/index.php';
});

$router->get('/applications', function() {
    require __DIR__ . '/../app/views/applications/index.php';
});

$router->get('/invoices', function() {
    require __DIR__ . '/../app/views/invoices/index.php';
});

$router->get('/servidores', function() {
    require __DIR__ . '/../app/views/servers/index.php';
});

$router->get('/whatsapp/parear', function() {
    require __DIR__ . '/../app/views/whatsapp/parear.php';
});

$router->get('/whatsapp/templates', function() {
    require __DIR__ . '/../app/views/whatsapp/templates.php';
});

$router->get('/whatsapp/scheduling', function() {
    require __DIR__ . '/../app/views/whatsapp/scheduling.php';
});

$router->get('/settings', function() {
    echo '<h1 style="padding: 2rem; color: #64748b;">Configurações - Em desenvolvimento</h1>';
});



// API Routes - Dashboard
$router->get('/api/dashboard/metrics', function() {
    require __DIR__ . '/../app/api/endpoints/dashboard.php';
    getMetrics();
});

// API Routes - Servers
$router->get('/api/servers', function() {
    require __DIR__ . '/../app/api/endpoints/servers.php';
    getServers();
});

$router->post('/api/servers', function() {
    require __DIR__ . '/../app/api/endpoints/servers.php';
    createServer();
});

$router->put('/api/servers/{id}', function() {
    require __DIR__ . '/../app/api/endpoints/servers.php';
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($uri, '/'));
    $serverId = end($parts);
    updateServer($serverId);
});

$router->delete('/api/servers/{id}', function() {
    require __DIR__ . '/../app/api/endpoints/servers.php';
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($uri, '/'));
    $serverId = end($parts);
    deleteServer($serverId);
});

// API Routes - Plans
$router->get('/api/plans', function() {
    require __DIR__ . '/../app/api/endpoints/plans.php';
    getPlans();
});

$router->get('/api/plans/servers', function() {
    require __DIR__ . '/../app/api/endpoints/plans.php';
    getServers();
});

$router->post('/api/plans', function() {
    require __DIR__ . '/../app/api/endpoints/plans.php';
    createPlan();
});

$router->put('/api/plans/{id}', function() {
    require __DIR__ . '/../app/api/endpoints/plans.php';
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($uri, '/'));
    $planId = end($parts);
    updatePlan($planId);
});

$router->delete('/api/plans/{id}', function() {
    require __DIR__ . '/../app/api/endpoints/plans.php';
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($uri, '/'));
    $planId = end($parts);
    deletePlan($planId);
});

// API Routes - Applications
$router->get('/api/applications', function() {
    require __DIR__ . '/../public/api-applications.php';
});

$router->post('/api/applications', function() {
    require __DIR__ . '/../public/api-applications.php';
});

$router->get('/api/applications/{id}', function() {
    require __DIR__ . '/../public/api-applications.php';
});

$router->put('/api/applications/{id}', function() {
    require __DIR__ . '/../public/api-applications.php';
});

$router->delete('/api/applications/{id}', function() {
    require __DIR__ . '/../public/api-applications.php';
});

// API Routes - Invoices
$router->get('/api/invoices', function() {
    require __DIR__ . '/../public/api-invoices.php';
});

$router->post('/api/invoices', function() {
    require __DIR__ . '/../public/api-invoices.php';
});

$router->get('/api/invoices/{id}', function() {
    require __DIR__ . '/../public/api-invoices.php';
});

$router->put('/api/invoices/{id}', function() {
    require __DIR__ . '/../public/api-invoices.php';
});

$router->put('/api/invoices/{id}/mark-paid', function() {
    require __DIR__ . '/../public/api-invoices.php';
});

$router->delete('/api/invoices/{id}', function() {
    require __DIR__ . '/../public/api-invoices.php';
});

// API Routes - Resellers (Admin only)
$router->get('/api-resellers.php', function() {
    require __DIR__ . '/../public/api-resellers.php';
});

$router->get('/api-resellers.php/{id}', function() {
    require __DIR__ . '/../public/api-resellers.php';
});

$router->put('/api-resellers.php/{id}/suspend', function() {
    require __DIR__ . '/../public/api-resellers.php';
});

$router->put('/api-resellers.php/{id}/activate', function() {
    require __DIR__ . '/../public/api-resellers.php';
});

$router->put('/api-resellers.php/{id}/change-plan', function() {
    require __DIR__ . '/../public/api-resellers.php';
});

$router->delete('/api-resellers.php/{id}', function() {
    require __DIR__ . '/../public/api-resellers.php';
});

// API Routes - Reseller Plans
$router->get('/api-reseller-plans.php', function() {
    require __DIR__ . '/../public/api-reseller-plans.php';
});

$router->post('/api-reseller-plans.php', function() {
    require __DIR__ . '/../public/api-reseller-plans.php';
});

$router->get('/api-reseller-plans.php/{id}', function() {
    require __DIR__ . '/../public/api-reseller-plans.php';
});

$router->put('/api-reseller-plans.php/{id}', function() {
    require __DIR__ . '/../public/api-reseller-plans.php';
});

$router->delete('/api-reseller-plans.php/{id}', function() {
    require __DIR__ . '/../public/api-reseller-plans.php';
});

// API Route - Auth Me
$router->get('/api/auth/me', function() {
    require __DIR__ . '/../public/api-auth-me.php';
});

// Admin Pages
$router->get('/admin/resellers', function() {
    require __DIR__ . '/../app/views/admin/resellers.php';
});

$router->get('/admin/reseller-plans', function() {
    require __DIR__ . '/../app/views/admin/reseller-plans.php';
});

// Reseller Pages
$router->get('/renew-access', function() {
    require __DIR__ . '/../app/views/reseller/renew-access.php';
});

// Logout
$router->post('/api/auth/logout', function() {
    Auth::logout();
    Response::json(['success' => true]);
});

// Dispatch da rota
try {
    $router->dispatch($method, $uri);
} catch (Exception $e) {
    if (env('APP_DEBUG', false)) {
        Response::json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
    } else {
        Response::json(['error' => 'Erro interno do servidor'], 500);
    }
}
