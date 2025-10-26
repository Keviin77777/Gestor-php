<?php
/**
 * Atualizar configurações do WhatsApp com a API Key
 */

header('Content-Type: application/json');

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

try {
    $apiKey = getenv('EVOLUTION_API_KEY') ?: getenv('WHATSAPP_API_KEY') ?: 'gestplay-whatsapp-2024';
    $apiUrl = getenv('EVOLUTION_API_URL') ?: getenv('WHATSAPP_API_URL') ?: 'http://localhost:8081';
    
    // Atualizar todas as configurações existentes
    $updated = Database::query(
        "UPDATE whatsapp_settings SET 
         evolution_api_key = ?,
         evolution_api_url = ?
         WHERE evolution_api_key IS NULL OR evolution_api_key = ''",
        [$apiKey, $apiUrl]
    );
    
    // Verificar se existe configuração para admin-001
    $settings = Database::fetch(
        "SELECT * FROM whatsapp_settings WHERE reseller_id = 'admin-001'"
    );
    
    if (!$settings) {
        // Criar configuração padrão
        Database::query(
            "INSERT INTO whatsapp_settings (id, reseller_id, evolution_api_url, evolution_api_key, reminder_days) 
             VALUES ('ws-admin-001', 'admin-001', ?, ?, JSON_ARRAY(3, 7))",
            [$apiUrl, $apiKey]
        );
        
        $settings = Database::fetch(
            "SELECT * FROM whatsapp_settings WHERE reseller_id = 'admin-001'"
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Configurações atualizadas com sucesso',
        'api_url' => $apiUrl,
        'api_key_configured' => !empty($apiKey),
        'settings' => $settings
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>