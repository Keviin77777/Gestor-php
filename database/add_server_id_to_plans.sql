-- Script para adicionar server_id à tabela subscription_plans
-- Execute este script se a tabela já existir

USE ultragestor_php;

-- Adicionar coluna server_id se não existir
ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS server_id INT AFTER reseller_id;

-- Adicionar foreign key constraint
ALTER TABLE subscription_plans 
ADD CONSTRAINT fk_plans_server_id 
FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL;

-- Adicionar índice
ALTER TABLE subscription_plans 
ADD INDEX IF NOT EXISTS idx_server (server_id);

-- Atualizar planos existentes para associar com o primeiro servidor disponível
UPDATE subscription_plans p
SET p.server_id = (
    SELECT s.id 
    FROM servers s 
    WHERE s.user_id = p.reseller_id 
    AND s.status = 'active' 
    ORDER BY s.id ASC 
    LIMIT 1
)
WHERE p.server_id IS NULL;