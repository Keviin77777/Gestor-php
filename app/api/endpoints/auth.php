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
        
        // Gerar token
        $token = Auth::generateToken([
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ]);
        
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
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'account_status' => $user['account_status']
            ]
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
            "INSERT INTO users (id, email, name, password_hash, role, account_status, subscription_expiry_date, whatsapp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $data['email'],
                $data['name'],
                Auth::hashPassword($data['password']),
                'reseller',
                'trial',
                $trialExpiry,
                $data['whatsapp'] ?? null
            ]
        );
        
        // Gerar token
        $token = Auth::generateToken([
            'id' => $userId,
            'email' => $data['email'],
            'name' => $data['name'],
            'role' => 'reseller'
        ]);
        
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
