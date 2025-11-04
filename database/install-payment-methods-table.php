<?php
/**
 * Script para criar a tabela de métodos de pagamento
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = Database::connect();
    
    echo "🔧 Criando tabela payment_methods...\n\n";
    
    // Ler o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/create-payment-methods-table.sql');
    
    // Executar
    $db->exec($sql);
    
    echo "✅ Tabela payment_methods criada com sucesso!\n\n";
    
    // Verificar se foi criada
    $stmt = $db->query("SHOW TABLES LIKE 'payment_methods'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Verificação: Tabela existe no banco de dados\n\n";
        
        // Mostrar estrutura
        echo "📋 Estrutura da tabela:\n";
        $stmt = $db->query("DESCRIBE payment_methods");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        
        echo "\n✅ Instalação concluída com sucesso!\n";
    } else {
        echo "❌ Erro: Tabela não foi criada\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
    exit(1);
}
