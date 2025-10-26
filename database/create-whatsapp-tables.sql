-- Tabelas para sistema WhatsApp com Evolution API

-- Sessões WhatsApp por reseller
CREATE TABLE IF NOT EXISTS whatsapp_sessions (
    id VARCHAR(50) PRIMARY KEY,
    reseller_id VARCHAR(50) NOT NULL,
    session_name VARCHAR(100) NOT NULL,
    instance_name VARCHAR(100) NOT NULL,
    status ENUM('disconnected', 'connecting', 'connected', 'error') DEFAULT 'disconnected',
    qr_code TEXT NULL,
    phone_number VARCHAR(20) NULL,
    profile_name VARCHAR(100) NULL,
    profile_picture TEXT NULL,
    webhook_url VARCHAR(255) NULL,
    api_key VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    connected_at TIMESTAMP NULL,
    last_seen TIMESTAMP NULL,
    INDEX idx_reseller (reseller_id),
    INDEX idx_status (status)
);

-- Templates de mensagem
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id VARCHAR(50) PRIMARY KEY,
    reseller_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('welcome', 'invoice_generated', 'renewed', 'expires_3d', 'expires_7d', 'expires_today', 'expired_1d', 'expired_3d', 'custom') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    variables JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reseller (reseller_id),
    INDEX idx_type (type),
    INDEX idx_active (is_active)
);

-- Configurações WhatsApp por reseller
CREATE TABLE IF NOT EXISTS whatsapp_settings (
    id VARCHAR(50) PRIMARY KEY,
    reseller_id VARCHAR(50) NOT NULL UNIQUE,
    evolution_api_url VARCHAR(255) NOT NULL DEFAULT 'http://localhost:8081',
    evolution_api_key VARCHAR(100) NULL,
    auto_send_welcome BOOLEAN DEFAULT TRUE,
    auto_send_invoice BOOLEAN DEFAULT TRUE,
    auto_send_renewal BOOLEAN DEFAULT TRUE,
    auto_send_reminders BOOLEAN DEFAULT TRUE,
    reminder_days JSON NULL,
    business_hours_start TIME DEFAULT '08:00:00',
    business_hours_end TIME DEFAULT '18:00:00',
    send_only_business_hours BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reseller (reseller_id)
);

-- Log de mensagens enviadas
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id VARCHAR(50) PRIMARY KEY,
    reseller_id VARCHAR(50) NOT NULL,
    session_id VARCHAR(50) NOT NULL,
    template_id VARCHAR(50) NULL,
    client_id VARCHAR(50) NULL,
    invoice_id VARCHAR(50) NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'image', 'document', 'audio', 'video') DEFAULT 'text',
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed') DEFAULT 'pending',
    evolution_message_id VARCHAR(100) NULL,
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reseller (reseller_id),
    INDEX idx_session (session_id),
    INDEX idx_client (client_id),
    INDEX idx_phone (phone_number),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    FOREIGN KEY (session_id) REFERENCES whatsapp_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES whatsapp_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
);