-- Atualizar tabela de planos para incluir servidor
-- Primeiro, adicionar as colunas sem constraints
ALTER TABLE plans 
ADD COLUMN server_id INT NULL AFTER id,
ADD COLUMN user_id VARCHAR(36) NULL AFTER server_id;

-- Atualizar planos existentes para associar ao primeiro usuário e servidor (se existir)
UPDATE plans p 
SET user_id = (SELECT id FROM users LIMIT 1)
WHERE user_id IS NULL;

UPDATE plans p 
SET server_id = (SELECT id FROM servers WHERE user_id = p.user_id LIMIT 1)
WHERE server_id IS NULL AND user_id IS NOT NULL;

-- Agora adicionar as constraints e índices
ALTER TABLE plans 
ADD FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
ADD INDEX idx_server_id (server_id),
ADD INDEX idx_user_id (user_id);

-- Tornar user_id NOT NULL após popular os dados
ALTER TABLE plans MODIFY COLUMN user_id VARCHAR(36) NOT NULL;
