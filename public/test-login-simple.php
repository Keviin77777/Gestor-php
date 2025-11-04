<?php
session_start();

echo "<h1>Teste de Login Simples</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Dados recebidos:</h2>";
    echo "Email: $email<br>";
    echo "Password: $password<br>";
    
    // Simular login bem-sucedido
    if ($email === 'admin@ultragestor.com' && $password === 'admin123') {
        $_SESSION['user_id'] = 'admin-001';
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = 'Administrador';
        $_SESSION['user_role'] = 'admin';
        
        echo "<p style='color: green;'>Login simulado com sucesso!</p>";
        echo "<p><a href='/dashboard'>Ir para Dashboard</a></p>";
        echo "<p><a href='debug-session.php'>Ver Sessão</a></p>";
    } else {
        echo "<p style='color: red;'>Credenciais inválidas!</p>";
    }
}

echo "<h2>Estado atual da sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

echo "<p>isAuthenticated(): " . (isAuthenticated() ? 'true' : 'false') . "</p>";
?>

<form method="POST">
    <h2>Fazer Login:</h2>
    <p>Email: <input type="email" name="email" value="admin@ultragestor.com" required></p>
    <p>Senha: <input type="password" name="password" value="admin123" required></p>
    <p><button type="submit">Login</button></p>
</form>

<p><a href="debug-session.php">Debug da Sessão</a></p>
<p><a href="/">Ir para Home</a></p>