<?php
/**
 * Script para corrigir sessão do admin
 * Acesse: https://ultragestor.site/fix-admin-session.php
 * REMOVA após usar!
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

require_once __DIR__ . '/../app/core/Database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin Session</title>
    <style>
        body { font-family: monospace; background: #1e293b; color: #f1f5f9; padding: 2rem; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        pre { background: #0f172a; padding: 1rem; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>🔧 Corrigir Sessão do Admin</h1>
    
    <?php
    echo "<h2>1️⃣ Sessão Atual:</h2>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    // Buscar dados corretos do banco
    $admin = Database::fetch(
        "SELECT * FROM users WHERE email = ?",
        ['admin@ultragestor.com']
    );
    
    if ($admin) {
        echo "<h2>2️⃣ Dados do Banco:</h2>";
        echo "<pre>";
        echo "ID: " . $admin['id'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Nome: " . $admin['name'] . "\n";
        echo "Role: " . ($admin['role'] ?? 'NULL') . "\n";
        echo "is_admin: " . ($admin['is_admin'] ?? 'NULL') . "\n";
        echo "</pre>";
        
        // Atualizar banco se necessário
        if ($admin['role'] !== 'admin' || $admin['is_admin'] != 1) {
            echo "<h2>3️⃣ Corrigindo Banco de Dados...</h2>";
            Database::query(
                "UPDATE users SET role = 'admin', is_admin = 1 WHERE email = ?",
                ['admin@ultragestor.com']
            );
            echo "<p class='success'>✅ Banco atualizado!</p>";
            
            // Buscar novamente
            $admin = Database::fetch(
                "SELECT * FROM users WHERE email = ?",
                ['admin@ultragestor.com']
            );
        }
        
        // Atualizar sessão
        echo "<h2>4️⃣ Atualizando Sessão...</h2>";
        $_SESSION['user'] = [
            'id' => $admin['id'],
            'email' => $admin['email'],
            'name' => $admin['name'],
            'role' => 'admin',
            'is_admin' => true,
            'account_status' => $admin['account_status'] ?? 'active'
        ];
        
        echo "<p class='success'>✅ Sessão atualizada!</p>";
        echo "<pre>" . print_r($_SESSION['user'], true) . "</pre>";
        
        echo "<h2>5️⃣ Próximos Passos:</h2>";
        echo "<ol>";
        echo "<li>Recarregue a página do dashboard</li>";
        echo "<li>O menu 'Administração' deve aparecer</li>";
        echo "<li>Deve mostrar 'Usuário: Administrador' embaixo</li>";
        echo "<li><strong>REMOVA este arquivo:</strong> <code>rm public/fix-admin-session.php</code></li>";
        echo "</ol>";
        
        echo "<p><a href='/dashboard' style='color: #6366f1; font-weight: bold;'>➡️ Ir para Dashboard</a></p>";
        
    } else {
        echo "<p class='error'>❌ Usuário admin@ultragestor.com não encontrado no banco!</p>";
    }
    ?>
</body>
</html>
