<?php
/**
 * Script para adicionar campo provider à tabela whatsapp_sessions
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = Database::connect();
    
    echo "Adicionando campo provider à tabela whatsapp_sessions...\n";
    
    // Adicionar campo provider
    $db->exec("
        ALTER TABLE whatsapp_sessions 
        ADD COLUMN provider ENUM('evolution', 'native') DEFAULT 'evolution' AFTER status
    ");
    
    echo "✓ Campo provider adicionado\n";
    
    // Atualizar sessões existentes baseado no instance_name
    $db->exec("
        UPDATE whatsapp_sessions 
        SET provider = 'native' 
        WHERE instance_name LIKE 'reseller_%' OR instance_name LIKE 'ultragestor-%'
    ");
    
    echo "✓ Sessões nativas atualizadas\n";
    
    $db->exec("
        UPDATE whatsapp_sessions 
        SET provider = 'evolution' 
        WHERE provider IS NULL OR provider = ''
    ");
    
    echo "✓ Sessões evolution atualizadas\n";
    
    echo "\n✅ Campo provider adicionado com sucesso!\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "⚠️  Campo provider já existe\n";
    } else {
        echo "❌ Erro: " . $e->getMessage() . "\n";
        exit(1);
    }
}
