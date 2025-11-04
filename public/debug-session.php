<?php
session_start();

echo "<h1>Debug da Sessão</h1>";
echo "<h2>Dados da Sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Configuração da Sessão:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Cookie Params: ";
print_r(session_get_cookie_params());
echo "</pre>";

echo "<h2>Cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Teste de Autenticação:</h2>";
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

echo "isAuthenticated(): " . (isAuthenticated() ? 'true' : 'false') . "\n";
echo "user_id: " . ($_SESSION['user_id'] ?? 'não definido') . "\n";

// Teste de definir sessão
if (isset($_GET['set'])) {
    $_SESSION['user_id'] = 'test-123';
    $_SESSION['user_name'] = 'Teste';
    echo "<p style='color: green;'>Sessão definida! <a href='debug-session.php'>Recarregar</a></p>";
}

if (isset($_GET['clear'])) {
    session_destroy();
    echo "<p style='color: red;'>Sessão destruída! <a href='debug-session.php'>Recarregar</a></p>";
}

echo "<p><a href='debug-session.php?set=1'>Definir Sessão de Teste</a> | <a href='debug-session.php?clear=1'>Limpar Sessão</a></p>";
?>