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
    id INT AUTO_INCREMENT PRIMARY KEY,
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

-- Tabela de templates WhatsApp
CREATE TABLE whatsapp_templates (
    id VARCHAR(36) PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL,
    event_type ENUM('welcome', 'invoice_available', 'reminder_7days', 'reminder_3days', 'reminder_1day', 'payment_confirmed', 'renewal_success', 'custom') NOT NULL,
    name VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reseller (reseller_id),
    INDEX idx_event_type (event_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de WhatsApp
CREATE TABLE whatsapp_logs (
    id VARCHAR(36) PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL,
    client_id VARCHAR(36),
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed', 'skipped') NOT NULL,
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    INDEX idx_reseller (reseller_id),
    INDEX idx_client (client_id),
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

-- Inserir plano trial padrão
INSERT INTO subscription_plans (id, reseller_id, name, description, price, duration_days, max_screens, is_active) VALUES
('plan-trial', 'admin-001', 'Trial Gratuito', 'Período de teste de 3 dias', 0.00, 3, 1, TRUE),
('plan-monthly', 'admin-001', 'Mensal', 'Plano mensal completo', 29.90, 30, 1, TRUE),
('plan-yearly', 'admin-001', 'Anual', 'Plano anual com desconto', 299.00, 365, 1, TRUE);

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