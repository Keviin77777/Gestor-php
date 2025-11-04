<?php
/**
 * UltraGestor - Sistema de Gestão IPTV
 * Sistema de roteamento simples
 */

// Verificar se é uma requisição para arquivo PHP (API)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

// Se é um arquivo PHP que existe, não interceptar
if (preg_match('/\.php$/', $path)) {
    $filePath = __DIR__ . $path;
    if (file_exists($filePath) && $filePath !== __FILE__) {
        // Deixar o arquivo ser executado normalmente
        return;
    }
}

// Iniciar sessão
session_start();

// Remover barra final se existir (exceto para root)
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

// Verificar autenticação
function isAuthenticated() {
    $authenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    
    // Debug temporário
    error_log("DEBUG: isAuthenticated() = " . ($authenticated ? 'true' : 'false'));
    error_log("DEBUG: SESSION = " . print_r($_SESSION, true));
    
    return $authenticated;
}

// Debug temporário
error_log("DEBUG: Path = $path");
error_log("DEBUG: REQUEST_URI = " . $_SERVER['REQUEST_URI']);

// Roteamento
switch ($path) {
    case '/':
        // Página inicial - redirecionar baseado na autenticação
        error_log("DEBUG: Rota '/' - verificando autenticação");
        if (isAuthenticated()) {
            error_log("DEBUG: Usuário autenticado - redirecionando para /dashboard");
            header('Location: /dashboard');
        } else {
            error_log("DEBUG: Usuário não autenticado - redirecionando para /login");
            header('Location: /login');
        }
        exit;
        
    case '/login':
        // Página de login
        error_log("DEBUG: Rota '/login' - verificando autenticação");
        if (isAuthenticated()) {
            error_log("DEBUG: Usuário já autenticado - redirecionando para /dashboard");
            header('Location: /dashboard');
            exit;
        }
        error_log("DEBUG: Carregando página de login");
        include __DIR__ . '/../app/views/auth/login.php';
        break;
        
    case '/register':
        // Página de registro
        if (isAuthenticated()) {
            header('Location: /dashboard');
            exit;
        }
        include __DIR__ . '/../app/views/auth/register.php';
        break;
        
    case '/logout':
        // Logout direto via URL
        session_destroy();
        header('Location: /login');
        exit;
        
    case '/dashboard':
        // Dashboard principal
        error_log("DEBUG: Rota '/dashboard' - verificando autenticação");
        if (!isAuthenticated()) {
            error_log("DEBUG: Usuário não autenticado - redirecionando para /login");
            header('Location: /login');
            exit;
        }
        error_log("DEBUG: Carregando dashboard");
        include __DIR__ . '/../app/views/dashboard/index.php';
        break;
        
    case '/clients':
        // Página de clientes
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/../app/views/clients/index.php';
        break;
        
    case '/servers':
        // Página de servidores
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/../app/views/servers/index.php';
        break;
        
    case '/plans':
        // Página de planos
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/../app/views/plans/index.php';
        break;
        
    case '/applications':
        // Página de aplicações
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/../app/views/applications/index.php';
        break;
        
    case '/invoices':
        // Página de faturas
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/../app/views/invoices/index.php';
        break;
        
    case '/payment-methods':
        // Página de métodos de pagamento
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/../app/views/payment-methods/index.php';
        break;
        
    case '/whatsapp':
        // Página do WhatsApp - redirecionar para templates
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        header('Location: /whatsapp/templates');
        exit;
        
    case '/whatsapp/templates':
        // Templates do WhatsApp
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/../app/views/whatsapp/templates.php';
        break;
        
    case '/whatsapp/scheduling':
        // Agendamento do WhatsApp
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/../app/views/whatsapp/scheduling.php';
        break;
        
    default:
        // Verificar se é uma rota de API
        if (strpos($path, '/api') === 0 || strpos($path, '/api-') === 0) {
            // Deixar o .htaccess tratar as APIs - não interceptar
            return;
        }
        
        // Página não encontrada
        http_response_code(404);
        echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página não encontrada - UltraGestor</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
        p { color: #666; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>404 - Página não encontrada</h1>
    <p>A página que você está procurando não existe.</p>
    <p><a href="/">Voltar ao início</a></p>
</body>
</html>';
        break;
}
?>