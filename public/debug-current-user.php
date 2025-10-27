<?php
// Debug do usuário atual
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "<h2>Debug - Usuário Atual</h2>";

$user = Auth::user();

if ($user) {
    echo "<h3>Usuário Logado:</h3>";
    echo "<pre>" . print_r($user, true) . "</pre>";
    
    $userId = $user['id'];
    
    // Verificar clientes deste usuário
    $clients = Database::fetchAll(
        "SELECT id, name, reseller_id FROM clients WHERE reseller_id = ?",
        [$userId]
    );
    
    echo "<h3>Clientes deste usuário (" . count($clients) . "):</h3>";
    echo "<pre>" . print_r($clients, true) . "</pre>";
    
    // Verificar templates deste usuário
    $templates = Database::fetchAll(
        "SELECT id, name, reseller_id FROM whatsapp_templates WHERE reseller_id = ?",
        [$userId]
    );
    
    echo "<h3>Templates deste usuário (" . count($templates) . "):</h3>";
    echo "<pre>" . print_r($templates, true) . "</pre>";
    
    // Verificar todos os usuários
    $allUsers = Database::fetchAll("SELECT id, email, name, role FROM users");
    echo "<h3>Todos os usuários:</h3>";
    echo "<pre>" . print_r($allUsers, true) . "</pre>";
    
} else {
    echo "<p style='color: red;'>Nenhum usuário logado!</p>";
}
?>