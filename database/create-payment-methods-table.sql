-- Tabela para armazenar configurações de métodos de pagamento
DROP TABLE IF EXISTS payment_methods;

CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL COMMENT 'ID do revendedor',
    method_name VARCHAR(50) NOT NULL COMMENT 'Nome do método (mercadopago, asaas, efibank)',
    config_value TEXT NOT NULL COMMENT 'Configurações em JSON',
    enabled TINYINT(1) DEFAULT 0 COMMENT 'Se o método está ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reseller_method (reseller_id, method_name),
    INDEX idx_reseller_id (reseller_id),
    INDEX idx_method_name (method_name),
    INDEX idx_enabled (enabled),
    FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
