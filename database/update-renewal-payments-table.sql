-- Atualizar tabela renewal_payments para incluir payment_provider
ALTER TABLE renewal_payments 
ADD COLUMN IF NOT EXISTS payment_provider VARCHAR(50) DEFAULT 'mercadopago' COMMENT 'Provedor de pagamento: mercadopago, asaas, efibank' 
AFTER qr_code;
