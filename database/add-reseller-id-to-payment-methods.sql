-- Adicionar coluna reseller_id à tabela payment_methods
ALTER TABLE payment_methods 
ADD COLUMN reseller_id VARCHAR(36) NULL AFTER id,
ADD COLUMN provider VARCHAR(50) NULL AFTER method_name,
ADD COLUMN public_key TEXT NULL AFTER provider,
ADD COLUMN access_token TEXT NULL AFTER public_key,
ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER access_token;

-- Migrar dados existentes
UPDATE payment_methods 
SET reseller_id = 'admin-001',
    provider = method_name,
    public_key = JSON_UNQUOTE(JSON_EXTRACT(config_value, '$.public_key')),
    access_token = JSON_UNQUOTE(JSON_EXTRACT(config_value, '$.access_token')),
    is_active = enabled
WHERE reseller_id IS NULL;

-- Adicionar índices
ALTER TABLE payment_methods
ADD INDEX idx_reseller_id (reseller_id),
ADD INDEX idx_provider (provider),
ADD INDEX idx_is_active (is_active);

-- Verificar resultado
SELECT id, reseller_id, provider, 
       SUBSTRING(public_key, 1, 20) as public_key_preview,
       SUBSTRING(access_token, 1, 20) as access_token_preview,
       is_active
FROM payment_methods;
