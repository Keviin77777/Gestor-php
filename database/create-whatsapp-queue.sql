-- Tabela de configurações de rate limiting do WhatsApp por revendedor
CREATE TABLE IF NOT EXISTS whatsapp_rate_limit_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id VARCHAR(50) NOT NULL,
    messages_per_minute INT DEFAULT 20 COMMENT 'Mensagens por minuto',
    messages_per_hour INT DEFAULT 100 COMMENT 'Mensagens por hora',
    delay_between_messages INT DEFAULT 3 COMMENT 'Delay em segundos entre mensagens',
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reseller (reseller_id),
    INDEX idx_reseller (reseller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de fila de mensagens WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_message_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    template_id INT NULL,
    client_id INT NULL,
    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    priority INT DEFAULT 0 COMMENT 'Maior = mais prioritário',
    scheduled_at TIMESTAMP NULL COMMENT 'Quando deve ser enviada',
    sent_at TIMESTAMP NULL,
    error_message TEXT NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reseller_status (reseller_id, status),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_status (status),
    INDEX idx_reseller (reseller_id),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações padrão serão criadas automaticamente quando o usuário acessar pela primeira vez
