-- Tabela de Aplicativos
-- Execute este SQL caso a tabela applications não exista

CREATE TABLE IF NOT EXISTS applications (
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

-- Inserir alguns aplicativos de exemplo
INSERT INTO applications (reseller_id, name, description) VALUES
('admin-001', 'Netflix', 'Serviço de streaming de filmes e séries'),
('admin-001', 'Spotify', 'Plataforma de streaming de música'),
('admin-001', 'YouTube Premium', 'Plataforma de vídeos sem anúncios')
ON DUPLICATE KEY UPDATE name=name;
