-- Criar tabela de planos
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT DEFAULT 30,
    max_screens INT DEFAULT 1,
    features JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir planos de exemplo
INSERT INTO plans (name, description, price, duration_days, max_screens, features, status) VALUES
('Básico', 'Plano básico com canais essenciais', 25.00, 30, 1, '{"channels": 500, "quality": "HD", "support": "Email"}', 'active'),
('Premium', 'Plano premium com mais canais e qualidade', 35.00, 30, 2, '{"channels": 1000, "quality": "Full HD", "support": "WhatsApp", "vod": true}', 'active'),
('VIP', 'Plano VIP com todos os recursos', 50.00, 30, 4, '{"channels": 2000, "quality": "4K", "support": "24/7", "vod": true, "premium_channels": true}', 'active'),
('Família', 'Plano especial para famílias', 40.00, 30, 3, '{"channels": 1500, "quality": "Full HD", "support": "WhatsApp", "parental_control": true}', 'active');