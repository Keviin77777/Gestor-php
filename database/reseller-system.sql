-- Sistema de Revendedores e Planos
-- Criação das tabelas necessárias para o sistema de revendas

-- Tabela de planos para revendedores
CREATE TABLE IF NOT EXISTS reseller_plans (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_trial BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_trial (is_trial)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir planos padrão
INSERT IGNORE INTO reseller_plans (id, name, description, price, duration_days, is_active, is_trial) VALUES
('plan-trial', 'Trial 3 Dias', 'Período de teste gratuito de 3 dias', 0.00, 3, TRUE, TRUE),
('plan-monthly', 'Mensal', 'Plano mensal para revendedores', 39.90, 30, TRUE, FALSE),
('plan-quarterly', 'Trimestral', 'Plano trimestral para revendedores', 120.00, 90, TRUE, FALSE),
('plan-biannual', 'Semestral', 'Plano semestral para revendedores', 180.00, 180, TRUE, FALSE),
('plan-annual', 'Anual', 'Plano anual para revendedores', 299.00, 365, TRUE, FALSE);

-- Adicionar colunas à tabela users para controle de planos
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS current_plan_id VARCHAR(50) DEFAULT 'plan-trial',
ADD COLUMN IF NOT EXISTS plan_expires_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS plan_status ENUM('active', 'expired', 'suspended') DEFAULT 'active',
ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD INDEX idx_plan_status (plan_status),
ADD INDEX idx_plan_expires (plan_expires_at),
ADD INDEX idx_is_admin (is_admin);

-- Atualizar usuário admin existente
UPDATE users 
SET is_admin = TRUE, 
    current_plan_id = NULL, 
    plan_expires_at = NULL, 
    plan_status = 'active'
WHERE email = 'admin@ultragestor.com';

-- Tabela de histórico de planos dos revendedores
CREATE TABLE IF NOT EXISTS reseller_plan_history (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    plan_id VARCHAR(50) NOT NULL,
    started_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    payment_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(50) NULL,
    payment_reference VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES reseller_plans(id) ON DELETE RESTRICT,
    INDEX idx_user (user_id),
    INDEX idx_plan (plan_id),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar histórico inicial para usuários existentes (exceto admin)
INSERT IGNORE INTO reseller_plan_history (id, user_id, plan_id, started_at, expires_at, status, payment_amount)
SELECT 
    CONCAT('hist-', u.id, '-initial'),
    u.id,
    'plan-trial',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 3 DAY),
    'active',
    0.00
FROM users u 
WHERE u.email != 'admin@ultragestor.com' 
AND u.id NOT IN (SELECT user_id FROM reseller_plan_history);

-- Atualizar usuários existentes com plano trial
UPDATE users u
SET 
    current_plan_id = 'plan-trial',
    plan_expires_at = DATE_ADD(NOW(), INTERVAL 3 DAY),
    plan_status = 'active'
WHERE u.email != 'admin@ultragestor.com' 
AND u.current_plan_id IS NULL;
