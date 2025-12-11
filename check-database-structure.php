<?php
/**
 * Script para verificar estrutura das tabelas do banco de dados
 */

require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = Database::connect();
    
    echo "=== VERIFICAÇÃO DE ESTRUTURA DO BANCO DE DADOS ===\n\n";
    
    // Tabelas para verificar
    $tables = ['payment_methods', 'invoice_payments', 'invoices', 'clients'];
    
    foreach ($tables as $table) {
        echo "📋 Tabela: $table\n";
        echo str_repeat("-", 80) . "\n";
        
        // Verificar se a tabela existe
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        
        if (!$exists) {
            echo "❌ Tabela não existe!\n\n";
            continue;
        }
        
        // Mostrar estrutura da tabela
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Colunas:\n";
        foreach ($columns as $column) {
            $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $column['Default'] !== null ? "DEFAULT '{$column['Default']}'" : '';
            $extra = $column['Extra'] ? "({$column['Extra']})" : '';
            
            echo sprintf(
                "  %-25s %-20s %-10s %-20s %s\n",
                $column['Field'],
                $column['Type'],
                $null,
                $default,
                $extra
            );
        }
        
        // Contar registros
        $stmt = $db->query("SELECT COUNT(*) as total FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\n📊 Total de registros: {$count['total']}\n";
        
        echo "\n";
    }
    
    // Verificar payment_methods especificamente
    echo "\n=== DADOS DA TABELA payment_methods ===\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt = $db->query("SELECT id, reseller_id, method_name, enabled, created_at FROM payment_methods ORDER BY id");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($methods)) {
        echo "⚠️ Nenhum método de pagamento configurado\n";
    } else {
        foreach ($methods as $method) {
            $status = $method['enabled'] ? '✅ ATIVO' : '❌ INATIVO';
            echo "ID: {$method['id']} | Reseller: {$method['reseller_id']} | Método: {$method['method_name']} | Status: $status\n";
        }
    }
    
    echo "\n✅ Verificação concluída!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
