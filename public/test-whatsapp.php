<?php
/**
 * Teste simples da API WhatsApp
 */

header('Content-Type: application/json');

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';

try {
    loadEnv(__DIR__ . '/../.env');
    
    // Testar conexão com banco
    require_once __DIR__ . '/../app/core/Database.php';
    
    $connection = Database::connect();
    
    // Verificar se as tabelas existem
    $tables = Database::fetchAll("SHOW TABLES LIKE 'whatsapp_%'");
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexão com banco OK',
        'tables_found' => count($tables),
        'tables' => array_column($tables, array_values($tables[0])[0] ?? 'Tables_in_database')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>