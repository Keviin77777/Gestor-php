# Correção de Charset UTF-8

Os textos estão aparecendo com "????" porque o banco de dados não está configurado com charset UTF-8.

## Solução Rápida

Execute o script de correção:

```bash
php database/fix-charset.php
```

## Solução Manual (via phpMyAdmin ou MySQL)

1. Abra o phpMyAdmin ou conecte-se ao MySQL
2. Selecione o banco de dados `ultragestor_php`
3. Execute o arquivo SQL: `database/fix-charset.sql`

Ou execute os comandos diretamente:

```sql
-- Corrigir charset das tabelas
ALTER TABLE whatsapp_templates CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE whatsapp_sessions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE whatsapp_messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE whatsapp_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Atualizar templates com textos corretos
UPDATE whatsapp_templates SET name = 'Boas Vindas Padrão' WHERE id = 'tpl-welcome-default';
UPDATE whatsapp_templates SET name = 'Fatura Gerada Padrão' WHERE id = 'tpl-invoice-default';
UPDATE whatsapp_templates SET name = 'Renovado Padrão' WHERE id = 'tpl-renewed-default';
UPDATE whatsapp_templates SET name = 'Vence em 3 dias Padrão' WHERE id = 'tpl-expires-3d-default';
UPDATE whatsapp_templates SET name = 'Vence em 7 dias Padrão' WHERE id = 'tpl-expires-7d-default';
UPDATE whatsapp_templates SET name = 'Vence hoje Padrão' WHERE id = 'tpl-expires-today-default';
UPDATE whatsapp_templates SET name = 'Venceu há 1 dia Padrão' WHERE id = 'tpl-expired-1d-default';
UPDATE whatsapp_templates SET name = 'Venceu há 3 dias Padrão' WHERE id = 'tpl-expired-3d-default';
```

## Após a Correção

1. Atualize a página no navegador (F5)
2. Os textos devem aparecer corretamente: "Padrão" ao invés de "Padr??o"

## Prevenção Futura

O arquivo `app/core/Database.php` já foi atualizado para sempre usar UTF-8 em novas conexões.
