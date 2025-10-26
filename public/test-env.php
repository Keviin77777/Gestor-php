<?php
/**
 * Teste das variáveis de ambiente
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

echo "<h1>Teste das Variáveis de Ambiente</h1>";

echo "<h2>JWT_SECRET:</h2>";
echo "<pre>";
echo "Valor: " . (env('JWT_SECRET') ?: 'NÃO ENCONTRADO');
echo "\nTamanho: " . strlen(env('JWT_SECRET') ?: '');
echo "</pre>";

echo "<h2>Todas as variáveis ENV:</h2>";
echo "<pre>";
print_r($_ENV);
echo "</pre>";

echo "<h2>Teste de geração de token:</h2>";
require_once __DIR__ . '/../app/core/Auth.php';

try {
    $token = Auth::generateToken([
        'id' => 'test',
        'email' => 'test@test.com',
        'name' => 'Test User'
    ]);
    
    echo "<p>Token gerado: " . substr($token, 0, 50) . "...</p>";
    
    $validation = Auth::validateToken($token);
    echo "<p>Validação: " . ($validation ? 'SUCESSO' : 'FALHOU') . "</p>";
    
    if ($validation) {
        echo "<pre>" . print_r($validation, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>