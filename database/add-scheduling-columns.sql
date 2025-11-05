-- Adicionar colunas para agendamento na tabela whatsapp_templates

ALTER TABLE whatsapp_templates 
ADD COLUMN is_scheduled BOOLEAN DEFAULT FALSE AFTER is_default,
ADD COLUMN scheduled_days JSON NULL AFTER is_scheduled,
ADD COLUMN scheduled_time TIME NULL AFTER scheduled_days;

-- Adicionar índices para melhor performance
ALTER TABLE whatsapp_templates 
ADD INDEX idx_scheduled (is_scheduled),
ADD INDEX idx_scheduled_time (scheduled_time);

-- Comentários para documentação
ALTER TABLE whatsapp_templates 
MODIFY COLUMN is_scheduled BOOLEAN DEFAULT FALSE COMMENT 'Se o template está agendado para envio automático',
MODIFY COLUMN scheduled_days JSON NULL COMMENT 'Dias da semana para envio (JSON array)',
MODIFY COLUMN scheduled_time TIME NULL COMMENT 'Horário para envio automático';