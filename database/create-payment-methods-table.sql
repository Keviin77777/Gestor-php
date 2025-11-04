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
