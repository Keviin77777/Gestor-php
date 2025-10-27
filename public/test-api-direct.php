<?php
// Teste direto da API sem autenticação
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';

echo "<h2>Teste Direto da API (sem autenticação)</h2>";

try {
    // Buscar clientes diretamente
    $clients = Database::fetchAll(
        "SELECT id, name, email, phone, reseller_id FROM clients WHERE reseller_id = 'admin-001' ORDER BY created_at DESC"
    );
    
    echo "<h3>Clientes encontrados (" . count($clients) . "):</h3>";
    echo "<pre>" . print_r($clients, true) . "</pre>";
    
    // Buscar templates diretamente
    $templates = Database::fetchAll(
        "SELECT id, name, type, reseller_id FROM whatsapp_templates WHERE reseller_id = 'admin-001' ORDER BY created_at DESC"
    );
    
    echo "<h3>Templates encontrados (" . count($templates) . "):</h3>";
    echo "<pre>" . print_r($templates, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>