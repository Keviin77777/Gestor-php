<?php
/**
 * Migração: Adicionar coluna payment_provider à tabela invoice_payments
 * 
 * Esta coluna armazena qual provedor de pagamento foi usado:
 * - asaas
 * - mercadopago
 * - efibank
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';

try {
    echo "Iniciando migração...\n\n";
    
    // Verificar se a coluna já existe
    $columns = Database::fetchAll("SHOW COLUMNS FROM invoice_payments LIKE 'payment_provider'");
    
    if (!empty($columns)) {
        echo "✓ Coluna 'payment_provider' já existe na tabela invoice_payments\n";
        exit(0);
    }
    
    echo "Adicionando coluna 'payment_provider' à tabela invoice_payments...\n";
    
    // Adicionar coluna
    Database::query("
        ALTER TABLE invoice_payments 
        ADD COLUMN payment_provider VARCHAR(50) DEFAULT 'mercadopago' AFTER payment_method
    ");
    
    echo "✓ Coluna adicionada com sucesso\n\n";
    
    // Criar índice
    echo "Criando índice para melhor performance...\n";
    Database::query("CREATE INDEX idx_payment_provider ON invoice_payments(payment_provider)");
    echo "✓ Índice criado com sucesso\n\n";
    
    echo "✓ Migração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "✗ Erro na migração: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
