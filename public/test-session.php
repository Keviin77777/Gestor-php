<?php
// Teste de sessão
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Auth.php';

echo "<h2>Teste de Sessão</h2>";

// Verificar usuário autenticado
$user = Auth::user();

if ($user) {
    echo "<h3>Usuário autenticado via sessão:</h3>";
    echo "<pre>" . print_r($user, true) . "</pre>";
    
    echo "<p><a href='/test-session.php?action=logout'>Fazer Logout</a></p>";
} else {
    echo "<p style='color: red;'>Nenhum usuário autenticado na sessão!</p>";
    
    // Formulário de login simples para teste
    if (isset($_POST['email']) && isset($_POST['password'])) {
        require_once __DIR__ . '/../app/core/Database.php';
        
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Buscar usuário
        $dbUser = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if ($dbUser && Auth::verifyPassword($password, $dbUser['password_hash'])) {
            $userData = [
                'id' => $dbUser['id'],
                'email' => $dbUser['email'],
                'name' => $dbUser['name'],
                'role' => $dbUser['role'],
                'account_status' => $dbUser['account_status']
            ];
            
            Auth::login($userData);
            
            echo "<p style='color: green;'>Login realizado com sucesso!</p>";
            echo "<script>window.location.reload();</script>";
        } else {
            echo "<p style='color: red;'>Credenciais inválidas!</p>";
        }
    }
    
    echo "<h3>Fazer Login:</h3>";
    echo "<form method='POST'>
        <p>Email: <input type='email' name='email' value='admin@ultragestor.com' required></p>
        <p>Senha: <input type='password' name='password' value='admin123' required></p>
        <p><button type='submit'>Login</button></p>
    </form>";
}

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    Auth::logout();
    echo "<p style='color: green;'>Logout realizado!</p>";
    echo "<script>window.location.href = '/test-session.php';</script>";
}

echo "<hr>";
echo "<p><a href='/'>Voltar ao sistema</a></p>";
?>