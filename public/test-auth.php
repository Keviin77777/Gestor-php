<?php
/**
 * Teste de autenticação
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir dependências
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Request.php';
require_once __DIR__ . '/../app/core/Auth.php';

echo "<h1>Teste de Autenticação</h1>";

echo "<h2>Headers recebidos:</h2>";
echo "<pre>";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        echo $key . ": " . $value . "\n";
    }
}
echo "</pre>";

echo "<h2>Token Bearer:</h2>";
$token = Request::bearerToken();
echo "<pre>";
echo "Token: " . ($token ? substr($token, 0, 50) . "..." : "não encontrado");
echo "</pre>";

echo "<h2>Usuário autenticado:</h2>";
$user = Auth::user();
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h2>LocalStorage (JavaScript):</h2>";
echo "<script>
document.write('<pre>');
document.write('Token: ' + (localStorage.getItem('token') ? localStorage.getItem('token').substring(0, 50) + '...' : 'não encontrado') + '\\n');
document.write('User: ' + (localStorage.getItem('user') || 'não encontrado'));
document.write('</pre>');
</script>";
?>