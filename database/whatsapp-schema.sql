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

-- Inserir templates padrão para admin-001 (executar após criar todas as tabelas)
INSERT IGNORE INTO whatsapp_templates (id, reseller_id, name, type, title, message, variables, is_default) VALUES
('tpl-welcome-default', 'admin-001', 'Boas Vindas Padrão', 'welcome', 'Bem-vindo ao nosso serviço!', 
'Olá {{cliente_nome}}! 👋\n\nSeja bem-vindo(a) ao nosso serviço de IPTV!\n\n📺 *Seus dados de acesso:*\n• Usuário: {{cliente_usuario}}\n• Senha: {{cliente_senha}}\n• Servidor: {{cliente_servidor}}\n• Plano: {{cliente_plano}}\n• Vencimento: {{cliente_vencimento}}\n\n💰 Valor: R$ {{cliente_valor}}\n\nQualquer dúvida, estamos aqui para ajudar! 😊',
'["cliente_nome", "cliente_usuario", "cliente_senha", "cliente_servidor", "cliente_plano", "cliente_vencimento", "cliente_valor"]', TRUE),

('tpl-invoice-default', 'admin-001', 'Fatura Gerada Padrão', 'invoice_generated', 'Nova fatura disponível', 
'Olá {{cliente_nome}}! 📄\n\nSua fatura foi gerada com sucesso!\n\n💰 *Detalhes da fatura:*\n• Valor: R$ {{fatura_valor}}\n• Vencimento: {{fatura_vencimento}}\n• Período: {{fatura_periodo}}\n\n💳 Para efetuar o pagamento, entre em contato conosco.\n\nObrigado pela preferência! 😊',
'["cliente_nome", "fatura_valor", "fatura_vencimento", "fatura_periodo"]', TRUE),

('tpl-renewed-default', 'admin-001', 'Renovado Padrão', 'renewed', 'Pagamento confirmado - Serviço renovado!', 
'Olá {{cliente_nome}}! ✅\n\n🎉 *Pagamento confirmado!*\n\nSeu serviço foi renovado com sucesso!\n\n📅 *Nova data de vencimento:* {{cliente_vencimento}}\n💰 *Valor pago:* R$ {{fatura_valor}}\n\nSeu acesso já está liberado e funcionando normalmente.\n\nObrigado pela confiança! 😊',
'["cliente_nome", "cliente_vencimento", "fatura_valor"]', TRUE),

('tpl-expires-3d-default', 'admin-001', 'Vence em 3 dias Padrão', 'expires_3d', 'Seu serviço vence em 3 dias', 
'Olá {{cliente_nome}}! ⏰\n\n⚠️ *Lembrete importante:*\n\nSeu serviço vence em *3 dias* ({{cliente_vencimento}}).\n\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\nPara evitar a interrupção do serviço, efetue o pagamento o quanto antes.\n\nEntre em contato conosco para mais informações! 😊',
'["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]', TRUE),

('tpl-expires-7d-default', 'admin-001', 'Vence em 7 dias Padrão', 'expires_7d', 'Seu serviço vence em 7 dias', 
'Olá {{cliente_nome}}! 📅\n\n🔔 *Lembrete:*\n\nSeu serviço vence em *7 dias* ({{cliente_vencimento}}).\n\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\nJá pode ir se organizando para a renovação!\n\nQualquer dúvida, estamos aqui! 😊',
'["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]', TRUE),

('tpl-expires-today-default', 'admin-001', 'Vence hoje Padrão', 'expires_today', 'Seu serviço vence hoje!', 
'Olá {{cliente_nome}}! 🚨\n\n⚠️ *URGENTE:*\n\nSeu serviço vence *HOJE* ({{cliente_vencimento}})!\n\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\nPara evitar a suspensão do serviço, efetue o pagamento hoje mesmo.\n\nEntre em contato conosco AGORA! 📞',
'["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]', TRUE),

('tpl-expired-1d-default', 'admin-001', 'Venceu há 1 dia Padrão', 'expired_1d', 'Serviço vencido - Renove agora!', 
'Olá {{cliente_nome}}! ❌\n\n🔴 *Serviço vencido:*\n\nSeu serviço venceu ontem ({{cliente_vencimento}}).\n\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\n⚠️ O acesso pode ser suspenso a qualquer momento.\n\nRenove URGENTEMENTE para manter o serviço ativo! 📞',
'["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]', TRUE),

('tpl-expired-3d-default', 'admin-001', 'Venceu há 3 dias Padrão', 'expired_3d', 'Serviço vencido há 3 dias', 
'Olá {{cliente_nome}}! 🔴\n\n❌ *Serviço vencido há 3 dias:*\n\nVencimento: {{cliente_vencimento}}\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\n🚫 Seu acesso será suspenso em breve se não renovar.\n\nRenove AGORA para evitar a suspensão! 📞\n\nÚltima chance!',
'["cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"]', TRUE);

-- Inserir configurações padrão para admin-001
INSERT IGNORE INTO whatsapp_settings (id, reseller_id, evolution_api_url, evolution_api_key, auto_send_welcome, auto_send_invoice, auto_send_renewal, auto_send_reminders, reminder_days) VALUES
('ws-admin-001', 'admin-001', 'http://localhost:8081', 'gestplay-whatsapp-2024', TRUE, TRUE, TRUE, TRUE, JSON_ARRAY(3, 7));