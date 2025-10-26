<?php
/**
 * Inicializar tabelas do WhatsApp
 */

header('Content-Type: application/json');

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

try {
    // Ler e executar o schema
    $schemaFile = __DIR__ . '/../database/whatsapp-schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception('Arquivo de schema não encontrado');
    }
    
    $schema = file_get_contents($schemaFile);
    
    if (!$schema) {
        throw new Exception('Erro ao ler arquivo de schema');
    }
    
    // Dividir em comandos individuais
    $commands = array_filter(array_map('trim', explode(';', $schema)));
    $executed = 0;
    $errors = [];
    
    foreach ($commands as $command) {
        if (!empty($command) && strpos($command, '--') !== 0) {
            try {
                Database::query($command);
                $executed++;
            } catch (Exception $e) {
                $errors[] = [
                    'command' => substr($command, 0, 100) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    // Verificar se as tabelas foram criadas
    $tables = Database::fetchAll("SHOW TABLES LIKE 'whatsapp_%'");
    
    echo json_encode([
        'success' => true,
        'message' => 'Tabelas inicializadas com sucesso',
        'commands_executed' => $executed,
        'tables_created' => count($tables),
        'tables' => array_column($tables, array_values($tables[0] ?? [])[0] ?? 'table'),
        'errors' => $errors
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