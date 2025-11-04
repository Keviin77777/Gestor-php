-- Atualizar template de fatura gerada para incluir {payment_link}
UPDATE whatsapp_templates 
SET message = CONCAT(
    message,
    '\n\n💳 Pague agora pelo link:\n{payment_link}'
)
WHERE type = 'invoice_generated'
AND message NOT LIKE '%{payment_link}%';

-- Atualizar template de vence hoje para incluir {payment_link}
UPDATE whatsapp_templates 
SET message = CONCAT(
    message,
    '\n\n💳 Pague agora pelo link:\n{payment_link}'
)
WHERE type = 'expires_today'
AND message NOT LIKE '%{payment_link}%';

-- Verificar templates atualizados
SELECT id, name, type, 
       SUBSTRING(message, 1, 100) as message_preview,
       CASE 
           WHEN message LIKE '%{payment_link}%' THEN 'SIM'
           ELSE 'NÃO'
       END as tem_payment_link
FROM whatsapp_templates
WHERE type IN ('invoice_generated', 'expires_today');
