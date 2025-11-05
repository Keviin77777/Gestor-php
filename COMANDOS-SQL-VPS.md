# Comandos SQL para Executar na VPS

Execute estes comandos SQL na VPS para atualizar o banco de dados `ultragestor_php` com as novas funcionalidades:

## 1. Conectar ao MySQL na VPS

```bash
mysql -u root -p
USE ultragestor_php;
```

## 2. Criar Tabela de Métodos de Pagamento

```sql
-- Tabela para armazenar configurações de métodos de pagamento
DROP TABLE IF EXISTS payment_methods;

CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nome do método (mercadopago, pix, etc)',
    config_value TEXT NOT NULL COMMENT 'Configurações em JSON',
    enabled TINYINT(1) DEFAULT 0 COMMENT 'Se o método está ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_method_name (method_name),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir registro padrão para Mercado Pago (desabilitado)
INSERT INTO payment_methods (method_name, config_value, enabled) 
VALUES ('mercadopago', '{"public_key":"","access_token":""}', 0)
ON DUPLICATE KEY UPDATE method_name = method_name;
```

## 3. Adicionar Colunas à Tabela de Métodos de Pagamento

```sql
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
```

## 4. Criar Tabela de Pagamentos de Faturas

```sql
-- Tabela para armazenar pagamentos de faturas via PIX
CREATE TABLE IF NOT EXISTS invoice_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id VARCHAR(36) NOT NULL,
    payment_id VARCHAR(255) NOT NULL UNIQUE,
    payment_method VARCHAR(50) DEFAULT 'pix',
    amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    qr_code TEXT,
    qr_code_base64 LONGTEXT,
    approved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_payment_id (payment_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 5. Criar Tabela de Pagamentos de Renovação

```sql
-- Tabela para armazenar pagamentos de renovação
DROP TABLE IF EXISTS renewal_payments;

CREATE TABLE renewal_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL COMMENT 'ID do usuário que está renovando (UUID)',
    plan_id VARCHAR(50) NOT NULL COMMENT 'ID do plano escolhido',
    payment_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'ID do pagamento no Mercado Pago',
    amount DECIMAL(10,2) NOT NULL COMMENT 'Valor do pagamento',
    status VARCHAR(20) DEFAULT 'pending' COMMENT 'Status: pending, approved, rejected, cancelled',
    qr_code TEXT COMMENT 'Código PIX',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_payment_id (payment_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 6. Atualizar Servidores para Suporte Sigma

```sql
-- Atualização para suportar integração Sigma
-- Atualizar coluna panel_type para suportar apenas Sigma
ALTER TABLE servers MODIFY COLUMN panel_type ENUM('sigma') NULL;

-- Adicionar índice para melhor performance
ALTER TABLE servers ADD INDEX idx_panel_type (panel_type);
```

## 7. Adicionar Payment Link aos Templates WhatsApp

```sql
-- Atualizar template de fatura gerada para incluir {payment_link}
UPDATE whatsapp_templates 
SET message = CONCAT(
    message,
    '\n\n💳 Pague agora pelo link:\n{payment_link}'
)
WHERE type = 'invoice_generated'
AND message NOT LIKE '%{payment_link}%';

-- Atualizar template de vence hoje para incluir {payment_link}
UPDATE whatsapp_templates 
SET message = CONCAT(
    message,
    '\n\n💳 Pague agora pelo link:\n{payment_link}'
)
WHERE type = 'expires_today'
AND message NOT LIKE '%{payment_link}%';
```

## 8. Verificar se Tudo Foi Criado Corretamente

```sql
-- Verificar tabelas criadas
SHOW TABLES LIKE '%payment%';
SHOW TABLES LIKE '%renewal%';

-- Verificar estrutura das tabelas
DESCRIBE payment_methods;
DESCRIBE invoice_payments;
DESCRIBE renewal_payments;

-- Verificar templates atualizados
SELECT id, name, type, 
       SUBSTRING(message, 1, 100) as message_preview,
       CASE 
           WHEN message LIKE '%{payment_link}%' THEN 'SIM'
           ELSE 'NÃO'
       END as tem_payment_link
FROM whatsapp_templates
WHERE type IN ('invoice_generated', 'expires_today');

-- Verificar métodos de pagamento
SELECT * FROM payment_methods;
```

## 9. Comandos de Linha Única (Para Copiar e Colar)

Se preferir executar tudo de uma vez, você pode usar estes comandos:

```bash
# Conectar e executar todos os SQLs
mysql -u root -p ultragestor_php << 'EOF'

-- 1. Criar tabela payment_methods
DROP TABLE IF EXISTS payment_methods;
CREATE TABLE payment_methods (id INT AUTO_INCREMENT PRIMARY KEY, method_name VARCHAR(50) NOT NULL UNIQUE, config_value TEXT NOT NULL, enabled TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_method_name (method_name), INDEX idx_enabled (enabled)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO payment_methods (method_name, config_value, enabled) VALUES ('mercadopago', '{"public_key":"","access_token":""}', 0) ON DUPLICATE KEY UPDATE method_name = method_name;

-- 2. Adicionar colunas
ALTER TABLE payment_methods ADD COLUMN reseller_id VARCHAR(36) NULL AFTER id, ADD COLUMN provider VARCHAR(50) NULL AFTER method_name, ADD COLUMN public_key TEXT NULL AFTER provider, ADD COLUMN access_token TEXT NULL AFTER public_key, ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER access_token;
UPDATE payment_methods SET reseller_id = 'admin-001', provider = method_name, public_key = JSON_UNQUOTE(JSON_EXTRACT(config_value, '$.public_key')), access_token = JSON_UNQUOTE(JSON_EXTRACT(config_value, '$.access_token')), is_active = enabled WHERE reseller_id IS NULL;
ALTER TABLE payment_methods ADD INDEX idx_reseller_id (reseller_id), ADD INDEX idx_provider (provider), ADD INDEX idx_is_active (is_active);

-- 3. Criar tabela invoice_payments
CREATE TABLE IF NOT EXISTS invoice_payments (id INT AUTO_INCREMENT PRIMARY KEY, invoice_id VARCHAR(36) NOT NULL, payment_id VARCHAR(255) NOT NULL UNIQUE, payment_method VARCHAR(50) DEFAULT 'pix', amount DECIMAL(10, 2) NOT NULL, status VARCHAR(50) DEFAULT 'pending', qr_code TEXT, qr_code_base64 LONGTEXT, approved_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_invoice_id (invoice_id), INDEX idx_payment_id (payment_id), INDEX idx_status (status), INDEX idx_created_at (created_at), FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Criar tabela renewal_payments
DROP TABLE IF EXISTS renewal_payments;
CREATE TABLE renewal_payments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id VARCHAR(36) NOT NULL, plan_id VARCHAR(50) NOT NULL, payment_id VARCHAR(100) NOT NULL UNIQUE, amount DECIMAL(10,2) NOT NULL, status VARCHAR(20) DEFAULT 'pending', qr_code TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_user_id (user_id), INDEX idx_payment_id (payment_id), INDEX idx_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Atualizar servidores
ALTER TABLE servers MODIFY COLUMN panel_type ENUM('sigma') NULL;
ALTER TABLE servers ADD INDEX idx_panel_type (panel_type);

-- 6. Atualizar templates
UPDATE whatsapp_templates SET message = CONCAT(message, '\n\n💳 Pague agora pelo link:\n{payment_link}') WHERE type = 'invoice_generated' AND message NOT LIKE '%{payment_link}%';
UPDATE whatsapp_templates SET message = CONCAT(message, '\n\n💳 Pague agora pelo link:\n{payment_link}') WHERE type = 'expires_today' AND message NOT LIKE '%{payment_link}%';

EOF
```

## ⚠️ Importante

1. **Backup**: Faça backup do banco antes de executar os comandos
2. **Teste**: Execute primeiro em ambiente de teste se possível
3. **Verificação**: Execute os comandos de verificação no final
4. **Permissões**: Certifique-se de ter permissões adequadas no MySQL

## 📝 Notas

- Estes comandos adicionam as funcionalidades de pagamento PIX via Mercado Pago
- Suporte para renovação de assinaturas
- Integração com Sigma IPTV
- Templates WhatsApp com links de pagamento
- Todas as tabelas usam charset utf8mb4 para suporte completo a emojis