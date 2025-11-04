-- Atualização para suportar integração Sigma
-- Execute este arquivo se você já tem servidores cadastrados

USE ultragestor_php;

-- Atualizar coluna panel_type para suportar apenas Sigma
ALTER TABLE servers MODIFY COLUMN panel_type ENUM('sigma') NULL;

-- Adicionar índice para melhor performance
ALTER TABLE servers ADD INDEX idx_panel_type (panel_type);

-- Atualizar servidores existentes que podem ser Sigma
-- (Opcional: ajuste conforme sua necessidade)
-- UPDATE servers SET panel_type = 'sigma' WHERE panel_type IS NULL AND panel_url LIKE '%sigma%';