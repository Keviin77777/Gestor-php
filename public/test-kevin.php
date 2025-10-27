<?php
// Teste específico para o usuário Kevin
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';

echo "<h2>Teste - Dados do Kevin</h2>";

$kevinId = '0a73a549-0596-41ad-87a3-7b0f4de9f592';

try {
    // Clientes do Kevin
    $clients = Database::fetchAll(
        "SELECT id, name, email, phone FROM clients WHERE reseller_id = ?",
        [$kevinId]
    );
    
    echo "<h3>Clientes do Kevin (" . count($clients) . "):</h3>";
    if (empty($clients)) {
        echo "<p>Nenhum cliente encontrado - isso está correto para um usuário novo!</p>";
    } else {
        echo "<pre>" . print_r($clients, true) . "</pre>";
    }
    
    // Templates do Kevin
    $templates = Database::fetchAll(
        "SELECT id, name, type FROM whatsapp_templates WHERE reseller_id = ?",
        [$kevinId]
    );
    
    echo "<h3>Templates do Kevin (" . count($templates) . "):</h3>";
    echo "<pre>" . print_r($templates, true) . "</pre>";
    
    // Servidores do Kevin
    $servers = Database::fetchAll(
        "SELECT id, name, status FROM servers WHERE user_id = ?",
        [$kevinId]
    );
    
    echo "<h3>Servidores do Kevin (" . count($servers) . "):</h3>";
    if (empty($servers)) {
        echo "<p>Nenhum servidor encontrado - isso está correto para um usuário novo!</p>";
    } else {
        echo "<pre>" . print_r($servers, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Conclusão:</strong> Kevin deve ter 7 templates e 0 clientes/servidores (usuário novo).</p>";
echo "<p>Se Kevin estiver vendo dados do admin, há um problema na autenticação.</p>";
?>