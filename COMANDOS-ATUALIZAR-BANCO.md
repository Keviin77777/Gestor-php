# Comandos para Atualizar Banco de Dados em Produção

## Informações do Banco
- **Database:** ultragestor_php
- **Usuário:** ultragestor_php
- **Senha:** (sua senha do banco)

## Opção 1: Executar via MySQL CLI (Recomendado)

```bash
# Conectar ao MySQL
mysql -u ultragestor_php -p ultragestor_php

# Depois de conectado, executar o script
source /caminho/completo/para/database/update-production-database.sql

# Ou copiar e colar o conteúdo do arquivo SQL diretamente
```

## Opção 2: Executar via arquivo único

```bash
# Executar o script SQL diretamente
mysql -u ultragestor_php -p ultragestor_php < database/update-production-database.sql
```

## Opção 3: Via phpMyAdmin

1. Acesse o phpMyAdmin
2. Selecione o banco `ultragestor_php`
3. Vá na aba "SQL"
4. Copie e cole o conteúdo do arquivo `database/update-production-database.sql`
5. Clique em "Executar"

## O que será criado/atualizado:

### 1. Nova Tabela: `renewal_payments`
- Armazena pagamentos de renovação de planos
- Campos: id, user_id, plan_id, payment_id, amount, status, qr_code, timestamps

### 2. Novas Colunas na Tabela `users`:
- `phone` - Telefone do usuário
- `company` - Nome da empresa
- `is_admin` - Flag de administrador
- `current_plan_id` - ID do plano atual
- `plan_expires_at` - Data de expiração do plano

### 3. Novas Colunas na Tabela `whatsapp_templates`:
- `is_scheduled` - Se o template está agendado
- `scheduled_days` - Dias da semana para envio (JSON)
- `scheduled_time` - Horário para envio automático

## Verificação Pós-Execução

```sql
-- Verificar se a tabela renewal_payments foi criada
SHOW TABLES LIKE 'renewal_payments';

-- Verificar colunas da tabela users
DESCRIBE users;

-- Verificar colunas da tabela whatsapp_templates
DESCRIBE whatsapp_templates;

-- Contar registros (deve ser 0 inicialmente)
SELECT COUNT(*) FROM renewal_payments;
```

## Notas Importantes

- ✅ O script é **seguro** para executar múltiplas vezes
- ✅ Verifica se as colunas já existem antes de criar
- ✅ Não sobrescreve dados existentes
- ✅ Usa prepared statements para segurança
- ⚠️ Faça backup do banco antes de executar (recomendado)

## Backup Rápido (Opcional mas Recomendado)

```bash
# Criar backup antes de atualizar
mysqldump -u ultragestor_php -p ultragestor_php > backup_antes_update_$(date +%Y%m%d_%H%M%S).sql
```

## Em caso de erro

Se algo der errado, você pode restaurar o backup:

```bash
mysql -u ultragestor_php -p ultragestor_php < backup_antes_update_XXXXXXXX_XXXXXX.sql
```
