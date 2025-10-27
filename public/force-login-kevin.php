<?php
// Forçar login como Kevin para teste
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Auth.php';

// Dados do Kevin
$kevinData = [
    'id' => '0a73a549-0596-41ad-87a3-7b0f4de9f592',
    'email' => 'souzaszkeviin@gmail.com',
    'name' => 'Kevin',
    'role' => 'reseller',
    'account_status' => 'trial'
];

// Fazer login como Kevin
Auth::logout(); // Limpar sessão anterior
Auth::login($kevinData);

echo "<h2>Login forçado como Kevin</h2>";
echo "<p>Agora você está logado como Kevin!</p>";
echo "<p><a href='/'>Ir para o sistema</a></p>";
echo "<p><a href='/debug-current-user.php'>Verificar usuário atual</a></p>";
?>