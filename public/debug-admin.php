<?php
// Debug para verificar dados do admin
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';

echo "<h2>Debug - Dados do Admin</h2>";

try {
    // Verificar usuário admin
    $admin = Database::fetch("SELECT id, email, name, role FROM users WHERE role = 'admin'");
    echo "<h3>Usuário Admin:</h3>";
    echo "<pre>" . print_r($admin, true) . "</pre>";
    
    // Verificar clientes
    $clients = Database::fetchAll("SELECT id, name, reseller_id FROM clients LIMIT 5");
    echo "<h3>Clientes (primeiros 5):</h3>";
    echo "<pre>" . print_r($clients, true) . "</pre>";
    
    // Verificar templates
    $templates = Database::fetchAll("SELECT id, name, reseller_id FROM whatsapp_templates LIMIT 5");
    echo "<h3>Templates WhatsApp (primeiros 5):</h3>";
    echo "<pre>" . print_r($templates, true) . "</pre>";
    
    // Verificar planos
    $plans = Database::fetchAll("SELECT id, name, user_id FROM plans LIMIT 5");
    echo "<h3>Planos (primeiros 5):</h3>";
    echo "<pre>" . print_r($plans, true) . "</pre>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>