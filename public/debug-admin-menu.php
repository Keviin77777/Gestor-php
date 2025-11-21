<?php
/**
 * Script de diagnóstico para verificar por que o menu de administração não aparece
 * Acesse: http://seu-dominio.com/debug-admin-menu.php
 */

// Carregar configurações
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Menu Administração</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e293b;
            color: #f1f5f9;
            padding: 2rem;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #0f172a;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        h1 {
            color: #6366f1;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        h2 {
            color: #818cf8;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .section {
            background: #1e293b;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #6366f1;
        }
        .success {
            color: #10b981;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .warning {
            color: #f59e0b;
            font-weight: bold;
        }
        .info {
            color: #3b82f6;
        }
        pre {
            background: #334155;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            color: #cbd5e1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        th {
            background: #334155;
            color: #818cf8;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .badge-success {
            background: #10b981;
            color: white;
        }
        .badge-error {
            background: #ef4444;
            color: white;
        }
        .badge-warning {
            background: #f59e0b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico - Menu de Administração</h1>
        
        <?php
        // 1. Verificar sessão
        echo '<div class="section">';
        echo '<h2>1️⃣ Verificação de Sessão</h2>';
        
        if (isset($_SESSION['user'])) {
            echo '<p class="success">✅ Sessão ativa encontrada</p>';
            echo '<pre>' . print_r($_SESSION['user'], true) . '</pre>';
        } else {
            echo '<p class="error">❌ Nenhuma sessão ativa encontrada</p>';
            echo '<p class="warning">⚠️ Você precisa estar logado para ver o menu de administração</p>';
        }
        echo '</div>';
        
        // 2. Verificar usuário autenticado
        echo '<div class="section">';
        echo '<h2>2️⃣ Verificação de Autenticação</h2>';
        
        try {
            $currentUser = Auth::user();
            
            if ($currentUser) {
                echo '<p class="success">✅ Usuário autenticado via Auth::user()</p>';
                echo '<pre>' . print_r($currentUser, true) . '</pre>';
            } else {
                echo '<p class="error">❌ Auth::user() retornou NULL</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Erro ao verificar autenticação: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
        
        // 3. Verificar dados do usuário no banco
        echo '<div class="section">';
        echo '<h2>3️⃣ Verificação no Banco de Dados</h2>';
        
        if (isset($currentUser) && $currentUser) {
            try {
                $userFromDB = Database::fetch(
                    "SELECT * FROM users WHERE id = ? OR email = ?",
                    [$currentUser['id'] ?? '', $currentUser['email'] ?? '']
                );
                
                if ($userFromDB) {
                    echo '<p class="success">✅ Usuário encontrado no banco de dados</p>';
                    
                    echo '<table>';
                    echo '<tr><th>Campo</th><th>Valor</th></tr>';
                    foreach ($userFromDB as $key => $value) {
                        if ($key !== 'password') {
                            echo '<tr>';
                            echo '<td><strong>' . htmlspecialchars($key) . '</strong></td>';
                            echo '<td>' . htmlspecialchars($value ?? 'NULL') . '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</table>';
                    
                    // Verificar especificamente os campos de admin
                    echo '<h3>🔑 Verificação de Permissões Admin</h3>';
                    
                    $role = strtolower(trim($userFromDB['role'] ?? ''));
                    $isAdminField = $userFromDB['is_admin'] ?? null;
                    
                    echo '<table>';
                    echo '<tr><th>Verificação</th><th>Valor</th><th>Status</th></tr>';
                    
                    // Verificar role
                    echo '<tr>';
                    echo '<td>Campo "role"</td>';
                    echo '<td>' . htmlspecialchars($userFromDB['role'] ?? 'NULL') . '</td>';
                    if ($role === 'admin') {
                        echo '<td><span class="badge badge-success">✅ É ADMIN</span></td>';
                    } else {
                        echo '<td><span class="badge badge-error">❌ NÃO é admin</span></td>';
                    }
                    echo '</tr>';
                    
                    // Verificar is_admin
                    echo '<tr>';
                    echo '<td>Campo "is_admin"</td>';
                    echo '<td>' . htmlspecialchars(var_export($isAdminField, true)) . '</td>';
                    if ($isAdminField === 1 || $isAdminField === true || $isAdminField === '1') {
                        echo '<td><span class="badge badge-success">✅ É ADMIN</span></td>';
                    } else {
                        echo '<td><span class="badge badge-error">❌ NÃO é admin</span></td>';
                    }
                    echo '</tr>';
                    
                    // Resultado final
                    $isAdmin = ($role === 'admin') || ($isAdminField === 1 || $isAdminField === true || $isAdminField === '1');
                    echo '<tr style="background: #334155;">';
                    echo '<td colspan="2"><strong>RESULTADO FINAL</strong></td>';
                    if ($isAdmin) {
                        echo '<td><span class="badge badge-success">✅ USUÁRIO É ADMIN</span></td>';
                    } else {
                        echo '<td><span class="badge badge-error">❌ USUÁRIO NÃO É ADMIN</span></td>';
                    }
                    echo '</tr>';
                    echo '</table>';
                    
                } else {
                    echo '<p class="error">❌ Usuário NÃO encontrado no banco de dados</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ Erro ao consultar banco: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p class="warning">⚠️ Nenhum usuário autenticado para verificar</p>';
        }
        echo '</div>';
        
        // 4. Verificar estrutura da tabela users
        echo '<div class="section">';
        echo '<h2>4️⃣ Estrutura da Tabela "users"</h2>';
        
        try {
            $columns = Database::query("DESCRIBE users");
            
            if ($columns) {
                echo '<p class="success">✅ Tabela "users" encontrada</p>';
                echo '<table>';
                echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                foreach ($columns as $col) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                // Verificar se os campos necessários existem
                $hasRole = false;
                $hasIsAdmin = false;
                foreach ($columns as $col) {
                    if ($col['Field'] === 'role') $hasRole = true;
                    if ($col['Field'] === 'is_admin') $hasIsAdmin = true;
                }
                
                echo '<h3>📋 Campos de Permissão</h3>';
                echo '<p>' . ($hasRole ? '<span class="success">✅ Campo "role" existe</span>' : '<span class="error">❌ Campo "role" NÃO existe</span>') . '</p>';
                echo '<p>' . ($hasIsAdmin ? '<span class="success">✅ Campo "is_admin" existe</span>' : '<span class="error">❌ Campo "is_admin" NÃO existe</span>') . '</p>';
                
            } else {
                echo '<p class="error">❌ Não foi possível obter estrutura da tabela</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Erro ao verificar estrutura: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
        
        // 5. Listar todos os usuários admin
        echo '<div class="section">';
        echo '<h2>5️⃣ Lista de Usuários Admin no Sistema</h2>';
        
        try {
            $admins = Database::query("SELECT id, name, email, role, is_admin FROM users WHERE role = 'admin' OR is_admin = 1");
            
            if ($admins && count($admins) > 0) {
                echo '<p class="success">✅ ' . count($admins) . ' administrador(es) encontrado(s)</p>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Nome</th><th>Email</th><th>Role</th><th>is_admin</th></tr>';
                foreach ($admins as $admin) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($admin['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($admin['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($admin['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($admin['role']) . '</td>';
                    echo '<td>' . htmlspecialchars(var_export($admin['is_admin'], true)) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="error">❌ Nenhum administrador encontrado no sistema!</p>';
                echo '<p class="warning">⚠️ Isso pode ser o problema. Você precisa ter pelo menos um usuário admin.</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Erro ao listar admins: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
        
        // 6. Verificar variáveis de ambiente
        echo '<div class="section">';
        echo '<h2>6️⃣ Variáveis de Ambiente</h2>';
        
        $envVars = [
            'DB_HOST' => getenv('DB_HOST'),
            'DB_NAME' => getenv('DB_NAME'),
            'DB_USER' => getenv('DB_USER'),
            'APP_ENV' => getenv('APP_ENV') ?: 'production',
        ];
        
        echo '<table>';
        echo '<tr><th>Variável</th><th>Valor</th></tr>';
        foreach ($envVars as $key => $value) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($key) . '</td>';
            echo '<td>' . ($value ? '<span class="success">' . htmlspecialchars($value) . '</span>' : '<span class="error">NÃO DEFINIDA</span>') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
        
        // 7. Recomendações
        echo '<div class="section">';
        echo '<h2>7️⃣ Recomendações</h2>';
        
        if (isset($isAdmin) && $isAdmin) {
            echo '<p class="success">✅ Seu usuário TEM permissões de admin no banco!</p>';
            echo '<p class="info">🔍 O problema pode ser:</p>';
            echo '<ul>';
            echo '<li><strong>Cache do navegador:</strong> Limpe o cache (Ctrl+Shift+Delete) ou teste em aba anônima</li>';
            echo '<li><strong>Cache do servidor:</strong> Reinicie o Apache/Nginx</li>';
            echo '<li><strong>Arquivos CSS/JS antigos:</strong> Adicione versão nos arquivos (ex: style.css?v=2)</li>';
            echo '<li><strong>Sessão antiga:</strong> Faça logout e login novamente</li>';
            echo '</ul>';
        } else {
            echo '<p class="error">❌ Seu usuário NÃO tem permissões de admin!</p>';
            echo '<p class="warning">📝 Para corrigir, execute este SQL no banco de dados:</p>';
            if (isset($currentUser['email'])) {
                echo '<pre>UPDATE users SET role = \'admin\', is_admin = 1 WHERE email = \'' . htmlspecialchars($currentUser['email']) . '\';</pre>';
            } else {
                echo '<pre>UPDATE users SET role = \'admin\', is_admin = 1 WHERE email = \'seu-email@exemplo.com\';</pre>';
            }
        }
        echo '</div>';
        
        // 8. Informações do sistema
        echo '<div class="section">';
        echo '<h2>8️⃣ Informações do Sistema</h2>';
        echo '<table>';
        echo '<tr><td><strong>PHP Version</strong></td><td>' . phpversion() . '</td></tr>';
        echo '<tr><td><strong>Server Software</strong></td><td>' . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . '</td></tr>';
        echo '<tr><td><strong>Document Root</strong></td><td>' . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . '</td></tr>';
        echo '<tr><td><strong>Script Filename</strong></td><td>' . __FILE__ . '</td></tr>';
        echo '</table>';
        echo '</div>';
        ?>
        
        <div class="section" style="border-left-color: #f59e0b;">
            <h2>⚠️ IMPORTANTE - Segurança</h2>
            <p class="warning">Este arquivo expõe informações sensíveis do sistema!</p>
            <p><strong>REMOVA este arquivo após o diagnóstico:</strong></p>
            <pre>rm public/debug-admin-menu.php</pre>
        </div>
    </div>
</body>
</html>
