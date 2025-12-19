-- Adicionar colunas de links sociais na tabela users
ALTER TABLE users 
ADD COLUMN telegram_link VARCHAR(500) DEFAULT 'https://t.me/+jim14-gGOBFhNWMx',
ADD COLUMN whatsapp_number VARCHAR(20) DEFAULT '14997349352';
