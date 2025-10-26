<?php
/**
 * Corrigir tabela whatsapp_sessions
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

try {
    // Verificar se a coluna connected_at existe
    $columns = Database::fetchAll("SHOW COLUMNS FROM whatsapp_sessions LIKE 'connected_at'");
    
    if (empty($columns)) {
        // Adicionar coluna connected_at
        Database::query("ALTER TABLE whatsapp_sessions ADD COLUMN connected_at TIMESTAMP NULL AFTER updated_at");
        echo json_encode([
            'success' => true,
            'message' => 'Coluna connected_at adicionada com sucesso'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Coluna connected_at já existe'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>