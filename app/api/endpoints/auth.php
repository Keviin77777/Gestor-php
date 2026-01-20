<?php
/**
 * Endpoints de autenticação
 */

/**
 * Login
 */
function handleLogin() {
    $data = Request::all();
    
    // Validação
    if (empty($data['email']) || empty($data['password'])) {
        Response::error('Email e senha são obrigatórios', 400);
    }
    
    if (!isValidEmail($data['email'])) {
        Response::error('Email inválido', 400);
    }
    
    try {
        // Buscar usuário
        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$data['email']]
        );
        
        if (!$user) {
            Response::error('Credenciais inválidas', 401);
        }
        
        // Verificar senha
        if (!Auth::verifyPassword($data['password'], $user['password_hash'])) {
            Response::error('Credenciais inválidas', 401);
        }
        
        // Verificar status da conta
        if ($user['account_status'] === 'expired' && $user['role'] === 'reseller') {
            Response::error('Sua assinatura expirou. Renove para continuar.', 403);
        }
        
        // Dados do usuário para sessão
        $userData = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'account_status' => $user['account_status']
        ];
        
        // Limpar qualquer sessão anterior antes do login
        Auth::logout();
        
        // Fazer login (salvar na sessão)
        Auth::login($userData);
        
        // Gerar token para compatibilidade
        $token = Auth::generateToken($userData);
        
        // Registrar log de auditoria
        Database::query(
            "INSERT INTO audit_logs (id, user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [
                generateUuid(),
                $user['id'],
                'login',
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );
        
        Response::json([
            'success' => true,
            'token' => $token,
            'user' => $userData
        ]);
        
    } catch (Exception $e) {
        logError('Login error: ' . $e->getMessage());
        Response::error('Erro ao processar login', 500);
    }
}

/**
 * Registro
 */
function handleRegister() {
    $data = Request::all();
    
    // Validação
    $errors = [];
    
    if (empty($data['name'])) {
        $errors['name'] = 'Nome é obrigatório';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email é obrigatório';
    } elseif (!isValidEmail($data['email'])) {
        $errors['email'] = 'Email inválido';
    }
    
    if (empty($data['password'])) {
        $errors['password'] = 'Senha é obrigatória';
    } elseif (strlen($data['password']) < 6) {
        $errors['password'] = 'Senha deve ter no mínimo 6 caracteres';
    }
    
    if (!empty($errors)) {
        Response::json(['errors' => $errors], 422);
    }
    
    try {
        // Verificar se email já existe
        $existing = Database::fetch(
            "SELECT id FROM users WHERE email = ?",
            [$data['email']]
        );
        
        if ($existing) {
            Response::error('Email já cadastrado', 400);
        }
        
        // Criar usuário com trial de 3 dias
        $userId = generateUuid();
        // Definir expiração para o final do 3º dia (23:59:59)
        $trialExpiry = date('Y-m-d 23:59:59', strtotime('+3 days'));
        
        Database::query(
            "INSERT INTO users (id, email, name, password_hash, role, account_status, subscription_expiry_date, whatsapp, current_plan_id, plan_expires_at, plan_status, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $data['email'],
                $data['name'],
                Auth::hashPassword($data['password']),
                'reseller',
                'trial',
                $trialExpiry,
                $data['whatsapp'] ?? null,
                'plan-trial',
                $trialExpiry,
                'active',
                false
            ]
        );
        
        // Registrar no histórico de planos
        $historyId = 'hist-' . uniqid();
        Database::query(
            "INSERT INTO reseller_plan_history (id, user_id, plan_id, started_at, expires_at, status, payment_amount) 
             VALUES (?, ?, ?, NOW(), ?, 'active', 0.00)",
            [$historyId, $userId, 'plan-trial', $trialExpiry]
        );
        
        // Gerar token
        $token = Auth::generateToken([
            'id' => $userId,
            'email' => $data['email'],
            'name' => $data['name'],
            'role' => 'reseller'
        ]);
        
        // Criar templates padrão para o novo usuário
        createDefaultTemplates($userId);
        
        // Criar configurações WhatsApp padrão
        createDefaultWhatsAppSettings($userId);
        
        // Registrar log
        Database::query(
            "INSERT INTO audit_logs (id, user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [
                generateUuid(),
                $userId,
                'register',
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );
        
        // Enviar mensagem de boas-vindas ao revendedor (se tiver WhatsApp)
        if (!empty($data['whatsapp'])) {
            try {
                sendWelcomeMessageToReseller($userId, $data['name'], $data['whatsapp'], $data['email'], $trialExpiry);
            } catch (Exception $e) {
                // Log error mas não falha o registro
                logError('Erro ao enviar boas-vindas ao revendedor: ' . $e->getMessage());
            }
        }
        
        Response::json([
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
        ], 201);
        
    } catch (Exception $e) {
        logError('Register error: ' . $e->getMessage());
        Response::error('Erro ao criar conta', 500);
    }
}
/**
 * Criar templates padrão para novo usuário
 */
function createDefaultTemplates($userId) {
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
            'message' => "Olá {{cliente_nome}}! ❌\n\n*Serviço vencido:*\nSeu serviço venceu ontem ({{cliente_vencimento}})\n\n💰 Valor: R$ {{cliente_valor}}\n📋 Plano: {{cliente_plano}}\n\nO acesso pode ser suspenso a qualquer momento.\n\n💳 *Pague agora pelo link:*\n{{payment_link}}\n\n⚡ Renove *URGENTEMENTE* para manter o serviço ativo!",
            'variables' => '["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano", "payment_link"]'
        ]
    ];
    
    foreach ($templates as $template) {
        try {
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
        } catch (Exception $e) {
            // Log error but continue with other templates
            logError('Error creating template: ' . $e->getMessage());
        }
    }
}

/**
 * Criar configurações WhatsApp padrão
 */
function createDefaultWhatsAppSettings($userId) {
    try {
        // Gerar ID único para evitar colisões
        $settingsId = 'ws-' . uniqid();
        
        Database::query(
            "INSERT INTO whatsapp_settings (id, reseller_id, evolution_api_url, evolution_api_key, auto_send_welcome, auto_send_invoice, auto_send_renewal, auto_send_reminders, reminder_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $settingsId,
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
        logError('Error creating WhatsApp settings: ' . $e->getMessage());
    }
}

/**
 * Enviar mensagem de boas-vindas ao novo revendedor
 */
function sendWelcomeMessageToReseller($resellerId, $resellerName, $resellerPhone, $resellerEmail, $trialExpiry) {
    try {
        require_once __DIR__ . '/../../helpers/queue-helper.php';
        
        // Admin que vai enviar as mensagens
        $adminResellerId = 'admin-001';
        
        // Buscar template de boas-vindas para revendedor do admin
        $template = Database::fetch(
            "SELECT * FROM whatsapp_templates 
             WHERE reseller_id = ? 
             AND type = 'reseller_welcome' 
             AND is_active = 1 
             ORDER BY is_default DESC, created_at DESC 
             LIMIT 1",
            [$adminResellerId]
        );
        
        if (!$template) {
            logError('Template reseller_welcome não encontrado para admin-001');
            return;
        }
        
        // Processar variáveis do template
        $variables = [
            'revendedor_nome' => $resellerName,
            'revendedor_email' => $resellerEmail,
            'revendedor_trial_expira' => date('d/m/Y H:i', strtotime($trialExpiry)),
            'link_painel' => env('APP_URL', 'http://localhost:8000')
        ];
        
        $message = $template['message'];
        foreach ($variables as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $value, $message);
        }
        
        // Adicionar à fila com prioridade alta
        $result = addMessageToQueue(
            $adminResellerId,
            $resellerPhone,
            $message,
            $template['id'],
            null, // Não é cliente
            2, // Prioridade alta
            null // Enviar imediatamente
        );
        
        if ($result['success']) {
            logError("Mensagem de boas-vindas adicionada à fila para revendedor: {$resellerName}");
            
            // Registrar no log
            Database::query(
                "INSERT INTO whatsapp_messages_log 
                (recipient_id, recipient_phone, message, message_type, sent_at, status)
                VALUES (?, ?, ?, ?, NOW(), 'queued')",
                [
                    $resellerId,
                    $resellerPhone,
                    $message,
                    'reseller_welcome'
                ]
            );
        } else {
            logError("Erro ao adicionar mensagem de boas-vindas à fila: " . ($result['error'] ?? 'Erro desconhecido'));
        }
        
    } catch (Exception $e) {
        logError('Erro em sendWelcomeMessageToReseller: ' . $e->getMessage());
    }
}/*
*
 * Logout
 */
function handleLogout() {
    try {
        $user = Auth::user();
        
        if ($user) {
            // Registrar log de logout
            Database::query(
                "INSERT INTO audit_logs (id, user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
                [
                    generateUuid(),
                    $user['id'],
                    'logout',
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]
            );
        }
        
        // Fazer logout
        Auth::logout();
        
        Response::json([
            'success' => true,
            'message' => 'Logout realizado com sucesso'
        ]);
        
    } catch (Exception $e) {
        logError('Logout error: ' . $e->getMessage());
        Response::error('Erro ao fazer logout', 500);
    }
}