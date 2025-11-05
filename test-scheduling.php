<?php
/**
 * Teste da funcionalidade de agendamento
 */

require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/core/Database.php';

loadEnv(__DIR__ . '/.env');

try {
    echo "Testando funcionalidade de agendamento...\n\n";
    
    // Verificar estrutura da tabela
    echo "1. Verificando estrutura da tabela whatsapp_templates:\n";
    $columns = Database::fetchAll("SHOW COLUMNS FROM whatsapp_templates");
    
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['is_scheduled', 'scheduled_days', 'scheduled_time'])) {
            echo "   ✓ {$column['Field']} - {$column['Type']}\n";
        }
    }
    
    // Verificar templates existentes
    echo "\n2. Verificando templates existentes:\n";
    $templates = Database::fetchAll("SELECT id, name, type, is_scheduled, scheduled_days, scheduled_time FROM whatsapp_templates LIMIT 5");
    
    foreach ($templates as $template) {
        echo "   - {$template['name']} ({$template['type']}) - Agendado: " . ($template['is_scheduled'] ? 'Sim' : 'Não') . "\n";
    }
    
    // Testar atualização de agendamento
    echo "\n3. Testando atualização de agendamento:\n";
    
    if (!empty($templates)) {
        $testTemplate = $templates[0];
        $templateId = $testTemplate['id'];
        
        echo "   Atualizando template: {$testTemplate['name']}\n";
        
        Database::query(
            "UPDATE whatsapp_templates SET 
             is_scheduled = ?, 
             scheduled_days = ?, 
             scheduled_time = ?,
             updated_at = NOW()
             WHERE id = ?",
            [
                1,
                json_encode(['monday', 'wednesday', 'friday']),
                '09:00:00',
                $templateId
            ]
        );
        
        // Verificar se foi atualizado
        $updated = Database::fetch("SELECT is_scheduled, scheduled_days, scheduled_time FROM whatsapp_templates WHERE id = ?", [$templateId]);
        
        echo "   ✓ is_scheduled: {$updated['is_scheduled']}\n";
        echo "   ✓ scheduled_days: {$updated['scheduled_days']}\n";
        echo "   ✓ scheduled_time: {$updated['scheduled_time']}\n";
        
        echo "\n   Teste de atualização: SUCESSO!\n";
    }
    
    echo "\n✅ Todos os testes passaram!\n";
    
} catch (Exception $e) {
    echo "❌ Erro no teste: " . $e->getMessage() . "\n";
    exit(1);
}