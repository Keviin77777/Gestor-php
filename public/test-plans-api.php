<?php
/**
 * Teste da API de Planos
 */

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

// Simular usuário logado para teste
session_start();
$_SESSION['user_id'] = 'test-user-id';

echo "<h1>Teste da API de Planos</h1>";

try {
    // Testar conexão com banco
    $db = Database::connect();
    echo "<p>✅ Conexão com banco de dados: OK</p>";
    
    // Verificar se a tabela plans foi atualizada
    $stmt = $db->query("DESCRIBE plans");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Colunas da tabela plans:</h2>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}</li>";
    }
    echo "</ul>";
    
    // Verificar se existem servidores
    $servers = Database::fetchAll("SELECT id, name, status FROM servers LIMIT 5");
    echo "<h2>Servidores disponíveis:</h2>";
    if (empty($servers)) {
        echo "<p>❌ Nenhum servidor encontrado</p>";
    } else {
        echo "<ul>";
        foreach ($servers as $server) {
            echo "<li>ID: {$server['id']} - Nome: {$server['name']} - Status: {$server['status']}</li>";
        }
        echo "</ul>";
    }
    
    // Verificar planos existentes
    $plans = Database::fetchAll("SELECT id, name, server_id, user_id FROM plans LIMIT 5");
    echo "<h2>Planos existentes:</h2>";
    if (empty($plans)) {
        echo "<p>ℹ️ Nenhum plano encontrado</p>";
    } else {
        echo "<ul>";
        foreach ($plans as $plan) {
            echo "<li>ID: {$plan['id']} - Nome: {$plan['name']} - Server ID: {$plan['server_id']} - User ID: {$plan['user_id']}</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>✅ Teste concluído com sucesso!</h2>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>


