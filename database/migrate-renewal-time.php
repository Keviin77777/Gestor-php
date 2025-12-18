<?php
/**
 * Script para adicionar coluna renewal_time na tabela clients
 * Execute este arquivo uma vez para atualizar o banco de dados
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';

try {
    echo "Iniciando migração: Adicionar coluna renewal_time...\n";
    
    // Verificar se a coluna já existe
    $columns = Database::fetchAll("SHOW COLUMNS FROM clients LIKE 'renewal_time'");
    
    if (count($columns) > 0) {
        echo "✓ Coluna renewal_time já existe!\n";
    } else {
        // Adicionar coluna renewal_time
        Database::query("ALTER TABLE clients ADD COLUMN renewal_time TIME DEFAULT '23:59:00' AFTER renewal_date");
        echo "✓ Coluna renewal_time adicionada com sucesso!\n";
        
        // Atualizar clientes existentes
        Database::query("UPDATE clients SET renewal_time = '23:59:00' WHERE renewal_time IS NULL");
        echo "✓ Clientes existentes atualizados com horário padrão!\n";
    }
    
    echo "\n✅ Migração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}
