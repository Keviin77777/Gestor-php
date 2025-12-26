<?php
/**
 * Script para listar todas as tabelas do banco de dados
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

try {
    $db = Database::connect();
    
    echo "=== TABELAS DO BANCO DE DADOS ===\n\n";
    
    // Listar todas as tabelas
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Total de tabelas: " . count($tables) . "\n\n";
    
    foreach ($tables as $table) {
        echo "📋 $table\n";
        
        // Mostrar estrutura da tabela
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "   - {$column['Field']} ({$column['Type']})";
            if ($column['Key'] === 'PRI') echo " [PRIMARY KEY]";
            if ($column['Key'] === 'MUL') echo " [FOREIGN KEY]";
            echo "\n";
        }
        
        echo "\n";
    }
    
    echo "=== FIM ===\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
