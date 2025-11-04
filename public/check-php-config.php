<?php
echo "<h1>Configurações PHP</h1>";

echo "<h2>Configurações de Sessão:</h2>";
echo "<pre>";
echo "session.save_handler: " . ini_get('session.save_handler') . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";
echo "</pre>";

echo "<h2>Informações do Servidor:</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "</pre>";

echo "<h2>Teste de Sessão:</h2>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;

echo "Contador de visitas: " . $_SESSION['test_counter'] . "<br>";
echo "<a href='check-php-config.php'>Recarregar para testar sessão</a>";
?>