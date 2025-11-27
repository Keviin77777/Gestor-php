<?php
/**
 * UltraGestor - Sistema de Gestão IPTV
 * Sistema de roteamento corrigido
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
    // Verificar ambas as formas de autenticação para compatibilidade
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return true;
    }
    
    // Verificar se existe $_SESSION['user'] (formato usado por Auth::user())
    if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
        return true;
    }
    
    return false;
}

// Função para verificar se usuário tem permissão para a rota
function hasPermission($path) {
    if (!isAuthenticated()) {
        return false;
    }
    
    // Verificar se existe $_SESSION['user'] para obter role
    $userRole = $_SESSION['user_role'] ?? 'reseller';
    if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['role'])) {
        $userRole = $_SESSION['user']['role'];
    }
    
    // Rotas que requerem admin
    $adminRoutes = ['/admin', '/resellers', '/payment-history'];
    
    foreach ($adminRoutes as $adminRoute) {
        if (strpos($path, $adminRoute) === 0 && $userRole !== 'admin') {
            return false;
        }
    }
    
    return true;
}

// Roteamento
switch ($path) {
    case '/':
        // Página inicial - mostrar landing page
        $landingFile = __DIR__ . '/landing.php';
        if (file_exists($landingFile)) {
            include $landingFile;
        } else {
            // Fallback para redirecionamento baseado na autenticação
            if (isAuthenticated()) {
                header('Location: /dashboard');
            } else {
                header('Location: /login');
            }
        }
        exit;
        
    case '/login':
        // Página de login - se já está logado, vai para dashboard
        if (isAuthenticated()) {
            header('Location: /dashboard');
            exit;
        }
        
        // Verificar se o arquivo de login existe
        $loginFile = __DIR__ . '/../app/views/auth/login.php';
        if (file_exists($loginFile)) {
            include $loginFile;
        } else {
            http_response_code(500);
            echo "Erro: Arquivo de login não encontrado.";
        }
        break;
        
    case '/register':
        // Página de registro
        if (isAuthenticated()) {
            header('Location: /dashboard');
            exit;
        }
        
        $registerFile = __DIR__ . '/../app/views/auth/register.php';
        if (file_exists($registerFile)) {
            include $registerFile;
        } else {
            header('Location: /login');
            exit;
        }
        break;
        
    case '/logout':
        // Logout direto via URL
        session_destroy();
        header('Location: /login');
        exit;
        
    case '/dashboard':
        // Dashboard principal
        if (!isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        
        $dashboardFile = __DIR__ . '/../app/views/dashboard/index.php';
        if (file_exists($dashboardFile)) {
            include $dashboardFile;
        } else {
            http_response_code(500);
            echo "Erro: Dashboard não encontrado.";
        }
        break;
        
    case '/clients':
        // Página de clientes
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $clientsFile = __DIR__ . '/../app/views/clients/index.php';
        if (file_exists($clientsFile)) {
            include $clientsFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/servers':
    case '/servidores':
        // Página de servidores (suporta ambas as rotas)
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $serversFile = __DIR__ . '/../app/views/servers/index.php';
        if (file_exists($serversFile)) {
            include $serversFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/plans':
        // Página de planos
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $plansFile = __DIR__ . '/../app/views/plans/index.php';
        if (file_exists($plansFile)) {
            include $plansFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/applications':
        // Página de aplicações
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $applicationsFile = __DIR__ . '/../app/views/applications/index.php';
        if (file_exists($applicationsFile)) {
            include $applicationsFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/invoices':
        // Página de faturas
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $invoicesFile = __DIR__ . '/../app/views/invoices/index.php';
        if (file_exists($invoicesFile)) {
            include $invoicesFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/payment-methods':
        // Página de métodos de pagamento
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $paymentMethodsFile = __DIR__ . '/../app/views/payment-methods/index.php';
        if (file_exists($paymentMethodsFile)) {
            include $paymentMethodsFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/whatsapp':
        // Página do WhatsApp - redirecionar para templates
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        header('Location: /whatsapp/templates');
        exit;
        
    case '/whatsapp/templates':
        // Templates do WhatsApp
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $templatesFile = __DIR__ . '/../app/views/whatsapp/templates.php';
        if (file_exists($templatesFile)) {
            include $templatesFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/whatsapp/parear':
        // Pareamento do WhatsApp
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $parearFile = __DIR__ . '/../app/views/whatsapp/parear.php';
        if (file_exists($parearFile)) {
            include $parearFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/whatsapp/scheduling':
        // Agendamento do WhatsApp
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $schedulingFile = __DIR__ . '/../app/views/whatsapp/scheduling.php';
        if (file_exists($schedulingFile)) {
            include $schedulingFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/whatsapp/queue':
        // Fila de mensagens WhatsApp
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $queueFile = __DIR__ . '/../app/views/whatsapp/queue.php';
        if (file_exists($queueFile)) {
            include $queueFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/admin/resellers':
        // Página de revendedores (admin)
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $resellersFile = __DIR__ . '/../app/views/admin/resellers.php';
        if (file_exists($resellersFile)) {
            include $resellersFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/admin/reseller-plans':
        // Página de planos de revendedores (admin)
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $resellerPlansFile = __DIR__ . '/../app/views/admin/reseller-plans.php';
        if (file_exists($resellerPlansFile)) {
            include $resellerPlansFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/admin/payment-history':
        // Histórico de pagamentos (admin)
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $paymentHistoryFile = __DIR__ . '/../app/views/admin/payment-history.php';
        if (file_exists($paymentHistoryFile)) {
            include $paymentHistoryFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/renew-access':
        // Página de renovação de acesso
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $renewAccessFile = __DIR__ . '/../app/views/reseller/renew-access.php';
        if (file_exists($renewAccessFile)) {
            include $renewAccessFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    case '/profile':
        // Página de perfil do usuário
        if (!hasPermission($path)) {
            header('Location: /login');
            exit;
        }
        
        $profileFile = __DIR__ . '/../app/views/profile/index.php';
        if (file_exists($profileFile)) {
            include $profileFile;
        } else {
            http_response_code(404);
            echo "Página não encontrada.";
        }
        break;
        
    default:
        // Verificar se é uma rota de API que começa com /api/invoices ou /api/applications
        if (strpos($path, '/api/invoices') === 0) {
            // Roteamento para API de faturas (incluindo rotas dinâmicas)
            if (file_exists(__DIR__ . '/api-invoices.php')) {
                include __DIR__ . '/api-invoices.php';
            } else {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'API não encontrada']);
            }
            break;
        }
        
        if (strpos($path, '/api/applications') === 0) {
            // Roteamento para API de aplicações (incluindo rotas dinâmicas)
            if (file_exists(__DIR__ . '/api-applications.php')) {
                include __DIR__ . '/api-applications.php';
            } else {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'API não encontrada']);
            }
            break;
        }
        
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