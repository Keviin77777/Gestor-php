<?php
/**
 * Migração para adicionar colunas de agendamento na tabela whatsapp_templates
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

try {
    echo "Iniciando migração para adicionar colunas de agendamento...\n";
    
    // Verificar se as colunas já existem
    $columns = Database::fetchAll("SHOW COLUMNS FROM whatsapp_templates LIKE 'is_scheduled'");
    
    if (empty($columns)) {
        echo "Adicionando colunas de agendamento...\n";
        
        // Adicionar colunas
        Database::query("ALTER TABLE whatsapp_templates 
            ADD COLUMN is_scheduled BOOLEAN DEFAULT FALSE AFTER is_default,
            ADD COLUMN scheduled_days JSON NULL AFTER is_scheduled,
            ADD COLUMN scheduled_time TIME NULL AFTER scheduled_days");
        
        echo "Colunas adicionadas com sucesso!\n";
        
        // Adicionar índices
        Database::query("ALTER TABLE whatsapp_templates 
            ADD INDEX idx_scheduled (is_scheduled),
            ADD INDEX idx_scheduled_time (scheduled_time)");
        
        echo "Índices adicionados com sucesso!\n";
        
    } else {
        echo "Colunas de agendamento já existem!\n";
    }
    
    echo "Migração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}