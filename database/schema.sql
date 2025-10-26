-- UltraGestor - Schema do Banco de Dados
-- Execute este arquivo no phpMyAdmin ou MySQL CLI

CREATE DATABASE IF NOT EXISTS ultragestor_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ultragestor_php;

-- Tabela de usuários (resellers e admins)
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'reseller') DEFAULT 'reseller',
    is_active BOOLEAN DEFAULT TRUE,
    whatsapp VARCHAR(20),
    subscription_plan_id VARCHAR(36),
    subscription_expiry_date DATETIME,
    account_status ENUM('active', 'trial', 'expired') DEFAULT 'trial',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_account_status (account_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de clientes
CREATE TABLE clients (
    id VARCHAR(36) PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    username VARCHAR(100),
    password VARCHAR(100),
    iptv_password VARCHAR(100),
    plan VARCHAR(100) DEFAULT 'Personalizado',
    plan_id VARCHAR(36),
    panel_id VARCHAR(36),
    start_date DATE,
    renewal_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    value DECIMAL(10,2) NOT NULL,
    server VARCHAR(100),
    mac VARCHAR(17),
    notifications ENUM('sim', 'nao') DEFAULT 'sim',
    screens INT DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reseller (reseller_id),
    INDEX idx_status (status),
    INDEX idx_renewal_date (renewal_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de faturas
CREATE TABLE invoices (
    id VARCHAR(36) PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL,
    client_id VARCHAR(36) NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    final_value DECIMAL(10,2) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    payment_method_id VARCHAR(36),
    payment_link TEXT,
    payment_date DATETIME,
    transaction_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_reseller (reseller_id),
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de métodos de pagamento
CREATE TABLE payment_methods (
    id VARCHAR(36) PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL,
    type ENUM('mercadopago', 'asaas', 'pix_manual') NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    credentials JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reseller (reseller_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de transações de pagamento
CREATE TABLE payment_transactions (
    id VARCHAR(36) PRIMARY KEY,
    invoice_id VARCHAR(36) NOT NULL,
    payment_method_id VARCHAR(36),
    external_id VARCHAR(255),
    qr_code TEXT,
    qr_code_base64 TEXT,
    pix_code TEXT,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'expired', 'cancelled') DEFAULT 'pending',
    expires_at DATETIME,
    paid_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice (invoice_id),
    INDEX idx_external_id (external_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessões WhatsApp por reseller
CREATE TABLE whatsapp_sessions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates de mensagem WhatsApp (com campos de agendamento)
CREATE TABLE whatsapp_templates (
    id VARCHAR(50) PRIMARY KEY,
    reseller_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('welcome', 'invoice_generated', 'renewed', 'expires_3d', 'expires_7d', 'expires_today', 'expired_1d', 'expired_3d', 'custom') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    variables JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    is_scheduled BOOLEAN DEFAULT FALSE,
    scheduled_days JSON NULL,
    scheduled_time TIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reseller (reseller_id),
    INDEX idx_type (type),
    INDEX idx_active (is_active),
    INDEX idx_scheduled (is_scheduled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log de mensagens enviadas
CREATE TABLE whatsapp_messages (
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
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações WhatsApp por reseller
CREATE TABLE whatsapp_settings (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de planos (usada pela API)
CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    server_id INT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT DEFAULT 30,
    max_screens INT DEFAULT 1,
    features JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_server_id (server_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de painéis Sigma
CREATE TABLE panels (
    id VARCHAR(36) PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reseller (reseller_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Servidores IPTV
CREATE TABLE servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    billing_type ENUM('fixed', 'per_active') NOT NULL DEFAULT 'fixed',
    cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    panel_type VARCHAR(50) NULL,
    panel_url VARCHAR(255) NULL,
    reseller_user VARCHAR(100) NULL,
    sigma_token VARCHAR(500) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de planos de assinatura
CREATE TABLE subscription_plans (
    id VARCHAR(36) PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL,
    server_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL,
    max_screens INT DEFAULT 1,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    INDEX idx_reseller (reseller_id),
    INDEX idx_server (server_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de aplicativos
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reseller_id (reseller_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de auditoria
CREATE TABLE audit_logs (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36),
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id VARCHAR(36),
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir usuário admin padrão (senha: admin123)
INSERT INTO users (id, email, name, password_hash, role, account_status) VALUES
('admin-001', 'admin@ultragestor.com', 'Administrador', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Inserir planos de assinatura padrão
INSERT INTO subscription_plans (id, reseller_id, name, description, price, duration_days, max_screens, is_active) VALUES
('plan-trial', 'admin-001', 'Trial Gratuito', 'Período de teste de 3 dias', 0.00, 3, 1, TRUE),
('plan-monthly', 'admin-001', 'Mensal', 'Plano mensal completo', 29.90, 30, 1, TRUE),
('plan-yearly', 'admin-001', 'Anual', 'Plano anual com desconto', 299.00, 365, 1, TRUE);

-- Inserir planos de exemplo
INSERT INTO plans (user_id, name, description, price, duration_days, max_screens, features, status) VALUES
('admin-001', 'Básico', 'Plano básico com canais essenciais', 25.00, 30, 1, '{"channels": 500, "quality": "HD", "support": "Email"}', 'active'),
('admin-001', 'Premium', 'Plano premium com mais canais e qualidade', 35.00, 30, 2, '{"channels": 1000, "quality": "Full HD", "support": "WhatsApp", "vod": true}', 'active'),
('admin-001', 'VIP', 'Plano VIP com todos os recursos', 50.00, 30, 4, '{"channels": 2000, "quality": "4K", "support": "24/7", "vod": true, "premium_channels": true}', 'active'),
('admin-001', 'Família', 'Plano especial para famílias', 40.00, 30, 3, '{"channels": 1500, "quality": "Full HD", "support": "WhatsApp", "parental_control": true}', 'active');

-- Inserir templates WhatsApp padrão
INSERT INTO whatsapp_templates (id, reseller_id, name, type, title, message, variables, is_default) VALUES
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

-- Inserir configurações WhatsApp padrão
INSERT INTO whatsapp_settings (id, reseller_id, evolution_api_url, evolution_api_key, auto_send_welcome, auto_send_invoice, auto_send_renewal, auto_send_reminders, reminder_days) VALUES
('ws-admin-001', 'admin-001', 'http://localhost:8081', 'gestplay-whatsapp-2024', TRUE, TRUE, TRUE, TRUE, JSON_ARRAY(3, 7));

-- Inserir faturas de exemplo (se houver clientes)
INSERT INTO invoices (id, reseller_id, client_id, value, discount, final_value, issue_date, due_date, status, payment_date) VALUES
('inv-001', 'admin-001', 'client-001', 35.00, 0.00, 35.00, '2024-10-01', '2024-10-05', 'paid', '2024-10-03'),
('inv-002', 'admin-001', 'client-001', 35.00, 5.00, 30.00, '2024-11-01', '2024-11-05', 'pending', NULL),
('inv-003', 'admin-001', 'client-002', 25.00, 0.00, 25.00, '2024-10-15', '2024-10-20', 'paid', '2024-10-18'),
('inv-004', 'admin-001', 'client-002', 25.00, 0.00, 25.00, '2024-11-15', '2024-11-20', 'overdue', NULL);

-- Tabela de Despesas
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'money',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir algumas despesas de exemplo
INSERT INTO expenses (description, category, amount, expense_date, payment_method, notes) VALUES
('Servidor VPS', 'Infraestrutura', 89.90, '2024-10-01', 'credit_card', 'Servidor principal para IPTV'),
('Licença de Software', 'Software', 150.00, '2024-10-05', 'pix', 'Licença anual do painel'),
('Internet Dedicada', 'Infraestrutura', 299.90, '2024-10-10', 'bank_transfer', 'Link dedicado 500MB'),
('Marketing Digital', 'Marketing', 200.00, '2024-10-15', 'credit_card', 'Anúncios Facebook e Google'),
('Suporte Técnico', 'Serviços', 120.00, '2024-10-20', 'pix', 'Suporte técnico terceirizado'),
('Backup Cloud', 'Infraestrutura', 45.90, '2024-09-01', 'credit_card', 'Backup automático'),
('Domínio e SSL', 'Infraestrutura', 89.90, '2024-09-15', 'pix', 'Renovação domínio e certificado'),
('Energia Elétrica', 'Operacional', 180.50, '2024-09-20', 'bank_transfer', 'Conta de luz do escritório'),
('Internet Fibra', 'Operacional', 99.90, '2024-09-25', 'bank_transfer', 'Internet do escritório');