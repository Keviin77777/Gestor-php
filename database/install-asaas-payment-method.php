<?php
/**
 * Script para adicionar Asaas à tabela payment_methods
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';

try {
    echo "🔧 Adicionando Asaas aos métodos de pagamento...\n\n";
    
    $db = Database::connect();
    
    // Inserir registro padrão para Asaas
    $sql = "INSERT INTO payment_methods (method_name, config_value, enabled) 
            VALUES ('asaas', '{\"api_key\":\"\",\"sandbox\":false}', 0)
            ON DUPLICATE KEY UPDATE method_name = method_name";
    
    $db->exec($sql);
    
    echo "✅ Asaas adicionado com sucesso!\n\n";
    
    // Verificar se foi criado
    $stmt = $db->query("SELECT * FROM payment_methods WHERE method_name = 'asaas'");
    $asaas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($asaas) {
        echo "✅ Verificação: Registro existe no banco de dados\n";
        echo "📋 Detalhes:\n";
        echo "   - ID: {$asaas['id']}\n";
        echo "   - Method: {$asaas['method_name']}\n";
        echo "   - Enabled: " . ($asaas['enabled'] ? 'Sim' : 'Não') . "\n";
        echo "   - Config: {$asaas['config_value']}\n";
    } else {
        echo "❌ Erro: Registro não encontrado\n";
    }
    
    echo "\n✅ Instalação concluída!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
