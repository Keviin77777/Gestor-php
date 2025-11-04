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
