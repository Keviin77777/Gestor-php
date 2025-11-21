<?php
/**
 * API de Autenticação
 */

// Desabilitar exibição de erros para evitar HTML na resposta JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Iniciar output buffering para capturar qualquer output inesperado
ob_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit();
}

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth-helper.php';
require_once __DIR__ . '/../app/core/Database.php';
loadEnv(__DIR__ . '/../.env');

// Limpar qualquer output que possa ter sido gerado antes
ob_clean();

// Obter método e ação
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Parse action from URI
$action = '';

// Tentar obter da query string primeiro
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

// Se não encontrou, tentar do padrão de rota
if (empty($action) && preg_match('#/api/auth/(\w+)#', $uri, $matches)) {
    $action = $matches[1];
}

// Obter dados do request
$data = json_decode(file_get_contents('php://input'), true) ?? [];

// Se não há ação na URL, tentar obter do JSON
if (empty($action) && isset($data['action'])) {
    $action = $data['action'];
}

try {
    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                ob_clean();
                http_response_code(405);
                echo json_encode(['error' => 'Método não permitido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Validação
            if (empty($data['email']) || empty($data['password'])) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Email e senha são obrigatórios'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Buscar usuário
            $user = Database::fetch(
                "SELECT * FROM users WHERE email = ? AND is_active = 1",
                [$data['email']]
            );
            
            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                ob_clean();
                http_response_code(401);
                echo json_encode(['error' => 'Credenciais inválidas'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Verificar status da conta
            if ($user['account_status'] === 'expired' && $user['role'] === 'reseller') {
                ob_clean();
                http_response_code(403);
                echo json_encode(['error' => 'Sua assinatura expirou. Renove para continuar.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Iniciar sessão
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Também salvar em $_SESSION['user'] para compatibilidade com Auth::user()
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'is_admin' => ($user['role'] === 'admin'), // Adicionar flag is_admin
                'account_status' => $user['account_status'] ?? 'active'
            ];
            
            // Gerar token
            $token = base64_encode(json_encode([
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'exp' => time() + (7 * 24 * 60 * 60)
            ]));
            
            // Registrar log (não crítico)
            try {
                Database::query(
                    "INSERT INTO audit_logs (id, user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
                    [
                        uniqid('log-', true),
                        $user['id'],
                        'login',
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]
                );
            } catch (Exception $e) {
                error_log('Audit log error: ' . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'account_status' => $user['account_status']
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case 'register':
            if ($method !== 'POST') {
                ob_clean();
                http_response_code(405);
                echo json_encode(['error' => 'Método não permitido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Validação
            if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['whatsapp'])) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Nome, email, WhatsApp e senha são obrigatórios'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            if (strlen($data['password']) < 6) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Senha deve ter no mínimo 6 caracteres'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Verificar se email já existe
            $existing = Database::fetch(
                "SELECT id FROM users WHERE email = ?",
                [$data['email']]
            );
            
            if ($existing) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Email já cadastrado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Criar usuário com trial de 3 dias
            $userId = uniqid('usr-', true);
            // Definir expiração para o final do 3º dia (23:59:59)
            $trialExpiry = date('Y-m-d 23:59:59', strtotime('+3 days'));
            
            Database::query(
                "INSERT INTO users (id, email, name, password_hash, role, account_status, subscription_expiry_date, whatsapp, current_plan_id, plan_expires_at, plan_status, is_admin, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $data['email'],
                    $data['name'],
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    'reseller',
                    'trial',
                    $trialExpiry,
                    $data['whatsapp'] ?? null,
                    'plan-trial',
                    $trialExpiry,
                    'active',
                    0,
                    1
                ]
            );
            
            // Registrar no histórico de planos
            $historyId = uniqid('hist-', true);
            Database::query(
                "INSERT INTO reseller_plan_history (id, user_id, plan_id, started_at, expires_at, status, payment_amount) 
                 VALUES (?, ?, ?, NOW(), ?, 'active', 0.00)",
                [$historyId, $userId, 'plan-trial', $trialExpiry]
            );
            
            // Criar templates padrão para o novo usuário
            createDefaultTemplates($userId);
            
            // Criar configurações WhatsApp padrão
            createDefaultWhatsAppSettings($userId);
            
            // Iniciar sessão
            session_start();
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $data['email'];
            $_SESSION['user_name'] = $data['name'];
            $_SESSION['user_role'] = 'reseller';
            
            // Também salvar em $_SESSION['user'] para compatibilidade com Auth::user()
            $_SESSION['user'] = [
                'id' => $userId,
                'email' => $data['email'],
                'name' => $data['name'],
                'role' => 'reseller',
                'account_status' => 'trial'
            ];
            
            // Gerar token
            $token = base64_encode(json_encode([
                'id' => $userId,
                'email' => $data['email'],
                'name' => $data['name'],
                'role' => 'reseller',
                'exp' => time() + (7 * 24 * 60 * 60)
            ]));
            
            // Registrar log (não crítico)
            try {
                Database::query(
                    "INSERT INTO audit_logs (id, user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
                    [
                        uniqid('log-', true),
                        $userId,
                        'register',
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]
                );
            } catch (Exception $e) {
                error_log('Audit log error: ' . $e->getMessage());
            }
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Conta criada com sucesso! Trial de 3 dias ativado.',
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'role' => 'reseller',
                    'account_status' => 'trial'
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case 'check_plan':
            if ($method !== 'GET') {
                ob_clean();
                http_response_code(405);
                echo json_encode(['error' => 'Método não permitido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            session_start();
            
            if (!isset($_SESSION['user_id'])) {
                ob_clean();
                http_response_code(401);
                echo json_encode(['error' => 'Não autenticado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Buscar informações do plano do usuário
            $userPlan = Database::fetchAll(
                "SELECT 
                    u.id,
                    u.role,
                    u.is_admin,
                    u.plan_expires_at,
                    rp.name as plan_name,
                    rp.price as plan_price,
                    CASE 
                        WHEN u.plan_expires_at IS NULL THEN -999
                        ELSE DATEDIFF(DATE(u.plan_expires_at), CURDATE())
                    END as days_remaining
                FROM users u
                LEFT JOIN reseller_plans rp ON u.current_plan_id = rp.id
                WHERE u.id = ?",
                [$_SESSION['user_id']]
            );
            
            if (empty($userPlan)) {
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'plan' => [
                        'name' => 'Sem plano',
                        'days_remaining' => 0,
                        'is_expired' => true,
                        'is_trial' => false
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $plan = $userPlan[0];
            $daysRemaining = (int)$plan['days_remaining'];
            $isAdmin = ($plan['role'] === 'admin' || $plan['is_admin'] == 1);
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'plan' => [
                    'name' => $plan['plan_name'] ?? 'Sem plano',
                    'days_remaining' => $daysRemaining,
                    'is_expired' => $daysRemaining <= 0,
                    'is_admin' => $isAdmin,
                    'is_trial' => false,
                    'expires_at' => $plan['plan_expires_at']
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case 'logout':
            if ($method !== 'POST') {
                ob_clean();
                http_response_code(405);
                echo json_encode(['error' => 'Método não permitido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            session_start();
            
            // Registrar log se houver usuário
            if (isset($_SESSION['user_id'])) {
                Database::query(
                    "INSERT INTO audit_logs (id, user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
                    [
                        uniqid('log-', true),
                        $_SESSION['user_id'],
                        'logout',
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]
                );
            }
            
            session_destroy();
            
            echo json_encode([
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        default:
            ob_clean();
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint não encontrado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
    }
    
} catch (Exception $e) {
    error_log('API Auth error: ' . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Error $e) {
    error_log('API Auth fatal error: ' . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Criar templates padrão para novo usuário
 */
function createDefaultTemplates($userId) {
    error_log("Criando templates padrão para usuário: $userId");
    
    $templates = [
        [
            'id' => 'tpl-welcome-' . substr($userId, 0, 8),
            'type' => 'welcome',
            'name' => 'Boas Vindas Padrão',
            'title' => 'Bem-vindo ao nosso serviço!',
            'message' => "Olá {{cliente_nome}}! 🎉\n\nSeja bem-vindo(a) ao nosso serviço de IPTV!\n\n📺 *Seus dados de acesso:*\n👤 Usuário: {{cliente_usuario}}\n🔐 Senha: {{cliente_senha}}\n🌐 Servidor: {{cliente_servidor}}\n📋 Plano: {{cliente_plano}}\n📅 Vencimento: {{cliente_vencimento}}\n💰 Valor: R$ {{cliente_valor}}\n\nQualquer dúvida, estamos aqui para ajudar! 😊",
            'variables' => '["cliente_nome", "cliente_usuario", "cliente_senha", "cliente_servidor", "cliente_plano", "cliente_vencimento", "cliente_valor"]'
        ],
        [
            'id' => 'tpl-invoice-' . substr($userId, 0, 8),
            'type' => 'invoice_generated',
            'name' => 'Fatura Gerada Padrão',
            'title' => 'Nova fatura disponível',
            'message' => "Olá {{cliente_nome}}! 📄\n\nSua fatura foi gerada com sucesso!\n\n💳 *Detalhes da fatura:*\n💰 Valor: R$ {{fatura_valor}}\n📅 Vencimento: {{fatura_vencimento}}\n📋 Período: {{fatura_periodo}}\n\n💳 *Pague agora pelo link:*\n{{payment_link}}\n\nObrigado pela preferência! 🙏",
            'variables' => '["cliente_nome", "fatura_valor", "fatura_vencimento", "fatura_periodo", "payment_link"]'
        ],
        [
            'id' => 'tpl-renewed-' . substr($userId, 0, 8),
            'type' => 'renewed',
            'name' => 'Renovado Padrão',
            'title' => 'Pagamento confirmado - Serviço renovado!',
            'message' => "Olá {{cliente_nome}}! ✅\n\n*Pagamento confirmado!*\nSeu serviço foi renovado com sucesso! 🎉\n\n📅 Nova data de vencimento: {{cliente_vencimento}}\n💰 Valor pago: R$ {{fatura_valor}}\n\nSeu acesso já está liberado e funcionando normalmente.\n\nObrigado pela confiança! 🙏",
            'variables' => '["cliente_nome", "cliente_vencimento", "fatura_valor"]'
        ],
        [
            'id' => 'tpl-expires-3d-' . substr($userId, 0, 8),
            'type' => 'expires_3d',
            'name' => 'Vence em 3 dias Padrão',
            'title' => 'Seu serviço vence em 3 dias',
            'message' => "Olá {{cliente_nome}}! ⚠️\n\n*Lembrete importante:*\nSeu serviço vence em *3 dias* ({{cliente_vencimento}})\n\n💰 Valor: R$ {{cliente_valor}}\n📋 Plano: {{cliente_plano}}\n\nPara evitar a interrupção do serviço, efetue o pagamento o quanto antes.\n\n💳 *Pague agora pelo link:*\n{{payment_link}}\n\nEntre em contato conosco se precisar de ajuda! 📞",
            'variables' => '["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano", "payment_link"]'
        ],
        [
            'id' => 'tpl-expires-7d-' . substr($userId, 0, 8),
            'type' => 'expires_7d',
            'name' => 'Vence em 7 dias Padrão',
            'title' => 'Seu serviço vence em 7 dias',
            'message' => "Olá {{cliente_nome}}! 📅\n\n*Lembrete:*\nSeu serviço vence em *7 dias* ({{cliente_vencimento}})\n\n💰 Valor: R$ {{cliente_valor}}\n📋 Plano: {{cliente_plano}}\n\nJá pode ir se organizando para a renovação!\n\n💳 *Pague agora pelo link:*\n{{payment_link}}\n\nQualquer dúvida, estamos aqui! 😊",
            'variables' => '["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano", "payment_link"]'
        ],
        [
            'id' => 'tpl-expires-today-' . substr($userId, 0, 8),
            'type' => 'expires_today',
            'name' => 'Vence hoje Padrão',
            'title' => 'Seu serviço vence hoje!',
            'message' => "Olá {{cliente_nome}}! 🚨\n\n*URGENTE:*\nSeu serviço vence *HOJE* ({{cliente_vencimento}})!\n\n💰 Valor: R$ {{cliente_valor}}\n📋 Plano: {{cliente_plano}}\n\nPara evitar a suspensão do serviço, efetue o pagamento hoje mesmo.\n\n💳 *Pague agora pelo link:*\n{{payment_link}}\n\n📞 Entre em contato conosco se precisar de ajuda!",
            'variables' => '["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano", "payment_link"]'
        ],
        [
            'id' => 'tpl-expired-1d-' . substr($userId, 0, 8),
            'type' => 'expired_1d',
            'name' => 'Venceu há 1 dia Padrão',
            'title' => 'Serviço vencido - Renove agora!',
            'message' => "Olá {{cliente_nome}}! ❌\n\n*Serviço vencido:*\nSeu serviço venceu ontem ({{cliente_vencimento}})\n\n💰 Valor: R$ {{cliente_valor}}\n📋 Plano: {{cliente_plano}}\n\nO acesso pode ser suspenso a qualquer momento.\n\n⚡ Renove *URGENTEMENTE* para manter o serviço ativo!",
            'variables' => '["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]'
        ]
    ];
    
    foreach ($templates as $template) {
        try {
            error_log("Criando template: " . $template['name'] . " para usuário: $userId");
            
            Database::query(
                "INSERT INTO whatsapp_templates (id, reseller_id, name, type, title, message, variables, is_active, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)",
                [
                    $template['id'],
                    $userId,
                    $template['name'],
                    $template['type'],
                    $template['title'],
                    $template['message'],
                    $template['variables']
                ]
            );
            
            error_log("Template criado com sucesso: " . $template['name']);
        } catch (Exception $e) {
            // Log error but continue with other templates
            error_log('Error creating template ' . $template['name'] . ': ' . $e->getMessage());
        }
    }
    
    error_log("Finalizada criação de templates para usuário: $userId");
}

/**
 * Criar configurações WhatsApp padrão
 */
function createDefaultWhatsAppSettings($userId) {
    try {
        Database::query(
            "INSERT INTO whatsapp_settings (id, reseller_id, evolution_api_url, evolution_api_key, auto_send_welcome, auto_send_invoice, auto_send_renewal, auto_send_reminders, reminder_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                'ws-' . substr($userId, 0, 8),
                $userId,
                'http://localhost:8081',
                'gestplay-whatsapp-2024',
                true,
                true,
                true,
                true,
                json_encode([3, 7])
            ]
        );
    } catch (Exception $e) {
        error_log('Error creating WhatsApp settings: ' . $e->getMessage());
    }
}

// Garantir que não há output extra
ob_end_flush();
