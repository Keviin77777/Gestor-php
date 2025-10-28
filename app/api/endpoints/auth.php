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
        $trialExpiry = date('Y-m-d H:i:s', strtotime('+3 days'));
        
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
            'message' => 'Olá {{cliente_nome}}! Seja bem-vindo(a) ao nosso serviço de IPTV! Seus dados de acesso: Usuario: {{cliente_usuario}} Senha: {{cliente_senha}} Servidor: {{cliente_servidor}} Plano: {{cliente_plano}} Vencimento: {{cliente_vencimento}} Valor: R$ {{cliente_valor}} Qualquer dúvida, estamos aqui para ajudar!',
            'variables' => '["cliente_nome", "cliente_usuario", "cliente_senha", "cliente_servidor", "cliente_plano", "cliente_vencimento", "cliente_valor"]'
        ],
        [
            'id' => 'tpl-invoice-' . substr($userId, 0, 8),
            'type' => 'invoice_generated',
            'name' => 'Fatura Gerada Padrão',
            'title' => 'Nova fatura disponível',
            'message' => 'Olá {{cliente_nome}}! Sua fatura foi gerada com sucesso! Detalhes da fatura: Valor: R$ {{fatura_valor}} Vencimento: {{fatura_vencimento}} Período: {{fatura_periodo}} Para efetuar o pagamento, entre em contato conosco. Obrigado pela preferência!',
            'variables' => '["cliente_nome", "fatura_valor", "fatura_vencimento", "fatura_periodo"]'
        ],
        [
            'id' => 'tpl-renewed-' . substr($userId, 0, 8),
            'type' => 'renewed',
            'name' => 'Renovado Padrão',
            'title' => 'Pagamento confirmado - Serviço renovado!',
            'message' => 'Olá {{cliente_nome}}! Pagamento confirmado! Seu serviço foi renovado com sucesso! Nova data de vencimento: {{cliente_vencimento}} Valor pago: R$ {{fatura_valor}} Seu acesso já está liberado e funcionando normalmente. Obrigado pela confiança!',
            'variables' => '["cliente_nome", "cliente_vencimento", "fatura_valor"]'
        ],
        [
            'id' => 'tpl-expires-3d-' . substr($userId, 0, 8),
            'type' => 'expires_3d',
            'name' => 'Vence em 3 dias Padrão',
            'title' => 'Seu serviço vence em 3 dias',
            'message' => 'Olá {{cliente_nome}}! Lembrete importante: Seu serviço vence em 3 dias ({{cliente_vencimento}}). Valor: R$ {{cliente_valor}} Plano: {{cliente_plano}} Para evitar a interrupção do serviço, efetue o pagamento o quanto antes. Entre em contato conosco para mais informações!',
            'variables' => '["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]'
        ],
        [
            'id' => 'tpl-expires-7d-' . substr($userId, 0, 8),
            'type' => 'expires_7d',
            'name' => 'Vence em 7 dias Padrão',
            'title' => 'Seu serviço vence em 7 dias',
            'message' => 'Olá {{cliente_nome}}! Lembrete: Seu serviço vence em 7 dias ({{cliente_vencimento}}). Valor: R$ {{cliente_valor}} Plano: {{cliente_plano}} Já pode ir se organizando para a renovação! Qualquer dúvida, estamos aqui!',
            'variables' => '["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]'
        ],
        [
            'id' => 'tpl-expires-today-' . substr($userId, 0, 8),
            'type' => 'expires_today',
            'name' => 'Vence hoje Padrão',
            'title' => 'Seu serviço vence hoje!',
            'message' => 'Olá {{cliente_nome}}! URGENTE: Seu serviço vence HOJE ({{cliente_vencimento}})! Valor: R$ {{cliente_valor}} Plano: {{cliente_plano}} Para evitar a suspensão do serviço, efetue o pagamento hoje mesmo. Entre em contato conosco AGORA!',
            'variables' => '["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]'
        ],
        [
            'id' => 'tpl-expired-1d-' . substr($userId, 0, 8),
            'type' => 'expired_1d',
            'name' => 'Venceu há 1 dia Padrão',
            'title' => 'Serviço vencido - Renove agora!',
            'message' => 'Olá {{cliente_nome}}! Serviço vencido: Seu serviço venceu ontem ({{cliente_vencimento}}). Valor: R$ {{cliente_valor}} Plano: {{cliente_plano}} O acesso pode ser suspenso a qualquer momento. Renove URGENTEMENTE para manter o serviço ativo!',
            'variables' => '["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]'
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
        logError('Error creating WhatsApp settings: ' . $e->getMessage());
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