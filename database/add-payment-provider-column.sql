-- Adicionar coluna payment_provider na tabela renewal_payments
ALTER TABLE renewal_payments 
ADD COLUMN payment_provider VARCHAR(50) DEFAULT 'mercadopago' COMMENT 'Provedor de pagamento (mercadopago, efibank)' 
AFTER qr_code;

-- Adicionar índice para melhor performance
ALTER TABLE renewal_payments 
ADD INDEX idx_payment_provider (payment_provider);

-- Inserir registro padrão para EFI Bank na tabela payment_methods (se não existir)
INSERT INTO payment_methods (method_name, config_value, enabled) 
VALUES ('efibank', '{"client_id":"","client_secret":"","pix_key":"","certificate":"","sandbox":false}', 0)
ON DUPLICATE KEY UPDATE method_name = method_name;
