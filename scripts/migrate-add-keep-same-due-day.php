<?php
/**
 * Migration Script: Adicionar campo keep_same_due_day
 * Execute este script para adicionar a funcionalidade de manter o mesmo dia de vencimento
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';

echo "===========================================\n";
echo "Migration: Adicionar keep_same_due_day\n";
echo "===========================================\n\n";

try {
    $db = Database::connect();
    
    echo "✓ Conectado ao banco de dados\n\n";
    
    // Verificar se a coluna já existe
    echo "Verificando se a coluna já existe...\n";
    $stmt = $db->query("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'clients' 
          AND COLUMN_NAME = 'keep_same_due_day'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "⚠ A coluna 'keep_same_due_day' já existe na tabela 'clients'\n";
        echo "✓ Migration já foi executada anteriormente\n\n";
    } else {
        echo "→ Adicionando coluna 'keep_same_due_day'...\n";
        
        // Adicionar a coluna
        $db->exec("
            ALTER TABLE clients 
            ADD COLUMN keep_same_due_day BOOLEAN DEFAULT FALSE 
            AFTER notes
        ");
        
        echo "✓ Coluna 'keep_same_due_day' adicionada com sucesso!\n\n";
    }
    
    // Verificar a estrutura final
    echo "Verificando estrutura da coluna...\n";
    $stmt = $db->query("
        SELECT 
            COLUMN_NAME, 
            DATA_TYPE, 
            COLUMN_DEFAULT,
            IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'clients' 
          AND COLUMN_NAME = 'keep_same_due_day'
    ");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "✓ Estrutura da coluna:\n";
        echo "  - Nome: {$column['COLUMN_NAME']}\n";
        echo "  - Tipo: {$column['DATA_TYPE']}\n";
        echo "  - Padrão: " . ($column['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
        echo "  - Nullable: {$column['IS_NULLABLE']}\n\n";
    }
    
    // Estatísticas
    $stmt = $db->query("SELECT COUNT(*) as total FROM clients");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "===========================================\n";
    echo "✓ Migration concluída com sucesso!\n";
    echo "===========================================\n\n";
    echo "Total de clientes no sistema: {$stats['total']}\n";
    echo "Todos os clientes foram configurados com keep_same_due_day = FALSE (padrão)\n\n";
    echo "Para ativar esta funcionalidade em um cliente:\n";
    echo "1. Acesse a página de Clientes\n";
    echo "2. Edite o cliente desejado\n";
    echo "3. Marque a opção 'Manter o mesmo dia de vencimento'\n";
    echo "4. Salve as alterações\n\n";
    echo "Exemplo de uso:\n";
    echo "- Cliente com vencimento em 20/12/2025\n";
    echo "- Ao renovar por 30 dias: 20/01/2026\n";
    echo "- Ao renovar por 60 dias: 20/02/2026\n";
    echo "- Mesmo que o mês tenha menos dias, ajusta automaticamente\n\n";
    
} catch (PDOException $e) {
    echo "✗ ERRO ao executar migration:\n";
    echo "  " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ ERRO:\n";
    echo "  " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "Pressione ENTER para sair...";
if (php_sapi_name() === 'cli') {
    fgets(STDIN);
}
