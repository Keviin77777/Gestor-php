# Deploy da Integração EFI Bank na VPS

## 📦 Passo 1: Atualizar o Código na VPS

```bash
# Conectar na VPS via SSH
ssh seu_usuario@seu_servidor

# Navegar até o diretório do projeto
cd /var/www/gestor

# Fazer backup antes de atualizar
cp -r . ../gestor-backup-$(date +%Y%m%d-%H%M%S)

# Atualizar código do GitHub
git pull origin main
```

## 🗄️ Passo 2: Atualizar Banco de Dados

```bash
# Executar script SQL para adicionar suporte ao EFI Bank
mysql -u seu_usuario -p seu_banco < database/add-payment-provider-column.sql
```

Ou execute manualmente no MySQL:

```sql
-- Adicionar coluna payment_provider
ALTER TABLE renewal_payments 
ADD COLUMN payment_provider VARCHAR(50) DEFAULT 'mercadopago' 
COMMENT 'Provedor de pagamento (mercadopago, efibank)' 
AFTER qr_code;

-- Adicionar índice
ALTER TABLE renewal_payments 
ADD INDEX idx_payment_provider (payment_provider);

-- Inserir registro EFI Bank
INSERT INTO payment_methods (method_name, config_value, enabled) 
VALUES ('efibank', '{"client_id":"","client_secret":"","pix_key":"","certificate":"","sandbox":false}', 0)
ON DUPLICATE KEY UPDATE method_name = method_name;
```

## 🔧 Passo 3: Configurar EFI Bank no Sistema

1. Acesse o sistema como **Admin**
2. Vá em **Métodos de Pagamento**
3. Localize o card **EFI Bank**
4. Preencha:
   - **Client ID**: Suas credenciais EFI
   - **Client Secret**: Suas credenciais EFI
   - **Chave PIX**: Sua chave PIX cadastrada
   - **Certificado SSL**: `/var/www/gestor/certificates/efi-production.pem` (se tiver)
   - **Modo Sandbox**: Desmarque (para produção)
   - **Ativar EFI Bank**: Marque

5. Clique em **Testar Conexão**
6. Se passar, clique em **Salvar Configurações**

## 📁 Passo 4: Configurar Certificado SSL (Opcional mas Recomendado)

Se você tiver o certificado EFI Bank:

```bash
# Criar diretório para certificados
mkdir -p /var/www/gestor/certificates

# Fazer upload do certificado .pem
# Use SCP ou SFTP para enviar o arquivo

# Ajustar permissões
chmod 600 /var/www/gestor/certificates/efi-production.pem
chown www-data:www-data /var/www/gestor/certificates/efi-production.pem
```

## 🔔 Passo 5: Configurar Webhook EFI Bank

No painel do EFI Bank:

1. Vá em **API** → **Webhooks**
2. Configure a URL:
   ```
   https://seu-dominio.com/webhook-efibank.php
   ```
3. Selecione eventos: `pix`
4. Salve

## ✅ Passo 6: Testar

### Teste 1: Verificar se EFI Bank está ativo

```bash
# Ver logs do Apache/Nginx
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log
```

### Teste 2: Gerar um PIX de teste

1. Como revendedor, acesse **Renovar Acesso**
2. Selecione um plano
3. Clique em **Renovar Plano**
4. Verifique se o QR Code é gerado

### Teste 3: Verificar logs

```bash
# Ver logs do webhook EFI Bank
tail -f /var/www/gestor/logs/efibank-webhook.log
```

## 🔄 Prioridade de Pagamento

O sistema usa esta ordem:

1. **EFI Bank** (se ativo)
2. **Mercado Pago** (fallback)

Para usar apenas EFI Bank:
- Ative EFI Bank
- Desative Mercado Pago em Métodos de Pagamento

## 🐛 Troubleshooting

### Erro: "Connection was reset"

Isso é normal em localhost. Na VPS com certificado SSL válido, deve funcionar.

### Erro: "Certificado SSL inválido"

```bash
# Verificar se o arquivo existe
ls -la /var/www/gestor/certificates/efi-production.pem

# Verificar permissões
chmod 600 /var/www/gestor/certificates/efi-production.pem
chown www-data:www-data /var/www/gestor/certificates/efi-production.pem
```

### Webhook não recebe notificações

```bash
# Verificar se o arquivo existe
ls -la /var/www/gestor/public/webhook-efibank.php

# Verificar permissões
chmod 644 /var/www/gestor/public/webhook-efibank.php

# Testar manualmente
curl -X POST https://seu-dominio.com/webhook-efibank.php \
  -H "Content-Type: application/json" \
  -d '{"pix":[{"txid":"TEST123"}]}'
```

## 📊 Verificar Funcionamento

### Verificar tabela renewal_payments

```sql
SELECT * FROM renewal_payments 
WHERE payment_provider = 'efibank' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Verificar configuração EFI Bank

```sql
SELECT * FROM payment_methods 
WHERE method_name = 'efibank';
```

## 🎉 Pronto!

Agora o EFI Bank está configurado e funcionando!

Os revendedores podem renovar usando PIX via EFI Bank, e o sistema renova automaticamente por +30 dias após pagamento aprovado.

## 📝 Notas Importantes

1. **Certificado SSL**: Recomendado para produção
2. **Webhook**: Configure no painel EFI Bank
3. **Logs**: Monitore `/var/www/gestor/logs/efibank-webhook.log`
4. **Backup**: Sempre faça backup antes de atualizar
5. **Teste**: Teste com um pagamento real pequeno primeiro

## 🔗 Links Úteis

- **Documentação Completa**: `EFI-BANK-SETUP.md`
- **Portal EFI Bank**: https://sejaefi.com.br
- **Suporte**: https://sejaefi.com.br/suporte
