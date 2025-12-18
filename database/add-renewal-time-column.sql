-- Adicionar coluna de horário de vencimento na tabela clients
ALTER TABLE clients 
ADD COLUMN renewal_time TIME DEFAULT '23:59:00' AFTER renewal_date;

-- Atualizar clientes existentes com horário padrão
UPDATE clients 
SET renewal_time = '23:59:00' 
WHERE renewal_time IS NULL;
