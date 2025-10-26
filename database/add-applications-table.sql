-- Adicionar tabela de aplicativos ao banco existente
-- Execute este arquivo se você já tem o banco configurado

USE ultragestor_php;

-- Criar tabela de aplicativos se não existir
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir alguns aplicativos de exemplo
INSERT IGNORE INTO applications (user_id, name, description) VALUES
('admin-001', 'Netflix', 'Plataforma de streaming de filmes e séries'),
('admin-001', 'Spotify', 'Serviço de streaming de música'),
('admin-001', 'YouTube Premium', 'YouTube sem anúncios e recursos premium'),
('admin-001', 'Amazon Prime Video', 'Streaming de vídeos da Amazon'),
('admin-001', 'Disney+', 'Conteúdo Disney, Marvel, Star Wars e mais');

SELECT 'Tabela de aplicativos criada com sucesso!' as status;