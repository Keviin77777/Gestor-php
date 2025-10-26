-- Inserir templates padrão para admin-001
INSERT IGNORE INTO whatsapp_templates (id, reseller_id, name, type, title, message, variables, is_default) VALUES
('tpl-welcome-default', 'admin-001', 'Boas Vindas Padrão', 'welcome', 'Bem-vindo ao nosso serviço!', 
'Olá {{cliente_nome}}! 👋\n\nSeja bem-vindo(a) ao nosso serviço de IPTV!\n\n📺 *Seus dados de acesso:*\n• Usuário: {{cliente_usuario}}\n• Senha: {{cliente_senha}}\n• Servidor: {{cliente_servidor}}\n• Plano: {{cliente_plano}}\n• Vencimento: {{cliente_vencimento}}\n\n💰 Valor: R$ {{cliente_valor}}\n\nQualquer dúvida, estamos aqui para ajudar! 😊',
JSON_ARRAY("cliente_nome", "cliente_usuario", "cliente_senha", "cliente_servidor", "cliente_plano", "cliente_vencimento", "cliente_valor"), TRUE),

('tpl-invoice-default', 'admin-001', 'Fatura Gerada Padrão', 'invoice_generated', 'Nova fatura disponível', 
'Olá {{cliente_nome}}! 📄\n\nSua fatura foi gerada com sucesso!\n\n💰 *Detalhes da fatura:*\n• Valor: R$ {{fatura_valor}}\n• Vencimento: {{fatura_vencimento}}\n• Período: {{fatura_periodo}}\n\n💳 Para efetuar o pagamento, entre em contato conosco.\n\nObrigado pela preferência! 😊',
JSON_ARRAY("cliente_nome", "fatura_valor", "fatura_vencimento", "fatura_periodo"), TRUE),

('tpl-renewed-default', 'admin-001', 'Renovado Padrão', 'renewed', 'Pagamento confirmado - Serviço renovado!', 
'Olá {{cliente_nome}}! ✅\n\n🎉 *Pagamento confirmado!*\n\nSeu serviço foi renovado com sucesso!\n\n📅 *Nova data de vencimento:* {{cliente_vencimento}}\n💰 *Valor pago:* R$ {{fatura_valor}}\n\nSeu acesso já está liberado e funcionando normalmente.\n\nObrigado pela confiança! 😊',
JSON_ARRAY("cliente_nome", "cliente_vencimento", "fatura_valor"), TRUE),

('tpl-expires-3d-default', 'admin-001', 'Vence em 3 dias Padrão', 'expires_3d', 'Seu serviço vence em 3 dias', 
'Olá {{cliente_nome}}! ⏰\n\n⚠️ *Lembrete importante:*\n\nSeu serviço vence em *3 dias* ({{cliente_vencimento}}).\n\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\nPara evitar a interrupção do serviço, efetue o pagamento o quanto antes.\n\nEntre em contato conosco para mais informações! 😊',
JSON_ARRAY("cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"), TRUE),

('tpl-expires-7d-default', 'admin-001', 'Vence em 7 dias Padrão', 'expires_7d', 'Seu serviço vence em 7 dias', 
'Olá {{cliente_nome}}! 📅\n\n🔔 *Lembrete:*\n\nSeu serviço vence em *7 dias* ({{cliente_vencimento}}).\n\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\nJá pode ir se organizando para a renovação!\n\nQualquer dúvida, estamos aqui! 😊',
JSON_ARRAY("cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"), TRUE),

('tpl-expires-today-default', 'admin-001', 'Vence hoje Padrão', 'expires_today', 'Seu serviço vence hoje!', 
'Olá {{cliente_nome}}! 🚨\n\n⚠️ *URGENTE:*\n\nSeu serviço vence *HOJE* ({{cliente_vencimento}})!\n\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\nPara evitar a suspensão do serviço, efetue o pagamento hoje mesmo.\n\nEntre em contato conosco AGORA! 📞',
JSON_ARRAY("cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"), TRUE),

('tpl-expired-1d-default', 'admin-001', 'Venceu há 1 dia Padrão', 'expired_1d', 'Serviço vencido - Renove agora!', 
'Olá {{cliente_nome}}! ❌\n\n🔴 *Serviço vencido:*\n\nSeu serviço venceu ontem ({{cliente_vencimento}}).\n\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\n⚠️ O acesso pode ser suspenso a qualquer momento.\n\nRenove URGENTEMENTE para manter o serviço ativo! 📞',
JSON_ARRAY("cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"), TRUE),

('tpl-expired-3d-default', 'admin-001', 'Venceu há 3 dias Padrão', 'expired_3d', 'Serviço vencido há 3 dias', 
'Olá {{cliente_nome}}! 🔴\n\n❌ *Serviço vencido há 3 dias:*\n\nVencimento: {{cliente_vencimento}}\n💰 Valor: R$ {{cliente_valor}}\n📺 Plano: {{cliente_plano}}\n\n🚫 Seu acesso será suspenso em breve se não renovar.\n\nRenove AGORA para evitar a suspensão! 📞\n\nÚltima chance!',
JSON_ARRAY("cliente_nome", "cliente_vencimento", "cliente_valor", "cliente_plano"), TRUE);

-- Inserir configurações padrão para admin-001
INSERT IGNORE INTO whatsapp_settings (id, reseller_id, evolution_api_url, auto_send_welcome, auto_send_invoice, auto_send_renewal, auto_send_reminders, reminder_days) VALUES
('ws-admin-001', 'admin-001', 'http://localhost:8081', TRUE, TRUE, TRUE, TRUE, JSON_ARRAY(3, 7));