-- Corrigir charset das tabelas WhatsApp para UTF-8

-- Alterar charset da tabela whatsapp_templates
ALTER TABLE whatsapp_templates CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Alterar charset da tabela whatsapp_sessions
ALTER TABLE whatsapp_sessions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Alterar charset da tabela whatsapp_messages
ALTER TABLE whatsapp_messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Alterar charset da tabela whatsapp_settings
ALTER TABLE whatsapp_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Atualizar os templates existentes com os textos corretos
UPDATE whatsapp_templates SET 
    name = 'Boas Vindas Padrão',
    title = 'Bem-vindo ao nosso serviço!'
WHERE id = 'tpl-welcome-default';

UPDATE whatsapp_templates SET 
    name = 'Fatura Gerada Padrão',
    title = 'Nova fatura disponível'
WHERE id = 'tpl-invoice-default';

UPDATE whatsapp_templates SET 
    name = 'Renovado Padrão',
    title = 'Pagamento confirmado - Serviço renovado!'
WHERE id = 'tpl-renewed-default';

UPDATE whatsapp_templates SET 
    name = 'Vence em 3 dias Padrão',
    title = 'Seu serviço vence em 3 dias'
WHERE id = 'tpl-expires-3d-default';

UPDATE whatsapp_templates SET 
    name = 'Vence em 7 dias Padrão',
    title = 'Seu serviço vence em 7 dias'
WHERE id = 'tpl-expires-7d-default';

UPDATE whatsapp_templates SET 
    name = 'Vence hoje Padrão',
    title = 'Seu serviço vence hoje!'
WHERE id = 'tpl-expires-today-default';

UPDATE whatsapp_templates SET 
    name = 'Venceu há 1 dia Padrão',
    title = 'Serviço vencido - Renove agora!'
WHERE id = 'tpl-expired-1d-default';

UPDATE whatsapp_templates SET 
    name = 'Venceu há 3 dias Padrão',
    title = 'Serviço vencido há 3 dias'
WHERE id = 'tpl-expired-3d-default';
