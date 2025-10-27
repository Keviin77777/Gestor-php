<?php
// Teste de autenticação
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Auth.php';

echo "<h2>Teste de Autenticação</h2>";

// Verificar se há token no header ou cookie
$token = null;

// Verificar Authorization header
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    echo "<p>Token encontrado no header: " . substr($token, 0, 20) . "...</p>";
}

// Verificar cookie
if (!$token && isset($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];
    echo "<p>Token encontrado no cookie: " . substr($token, 0, 20) . "...</p>";
}

// Verificar localStorage via JavaScript
if (!$token) {
    echo "<p>Verificando localStorage...</p>";
    echo "<script>
        const token = localStorage.getItem('auth_token');
        if (token) {
            document.write('<p>Token encontrado no localStorage: ' + token.substring(0, 20) + '...</p>');
            // Fazer uma requisição com o token
            fetch('/test-auth.php', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({test: true})
            });
        } else {
            document.write('<p style=\"color: red;\">Nenhum token encontrado no localStorage!</p>');
        }
    </script>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        echo "<p>Token recebido via POST: " . substr($token, 0, 20) . "...</p>";
        
        try {
            $user = Auth::validateToken($token);
            echo "<h3>Usuário autenticado:</h3>";
            echo "<pre>" . print_r($user, true) . "</pre>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erro na validação do token: " . $e->getMessage() . "</p>";
        }
    }
}

if ($token) {
    try {
        $user = Auth::validateToken($token);
        echo "<h3>Usuário autenticado:</h3>";
        echo "<pre>" . print_r($user, true) . "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro na validação do token: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>Nenhum token de autenticação encontrado!</p>";
    echo "<p>Faça login primeiro em: <a href='/'>http://localhost/</a></p>";
}
?>