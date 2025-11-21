# Configuração do EFI Bank (Gerencianet)

Este guia explica como configurar o EFI Bank como método de pagamento PIX no sistema.

## 📋 Pré-requisitos

1. Conta ativa no EFI Bank (Gerencianet)
2. Aplicação criada no painel de desenvolvedores
3. Chave PIX cadastrada na conta
4. Certificado SSL (para produção)

## 🔧 Passo a Passo

### 1. Atualizar Banco de Dados

Execute o script SQL para adicionar suporte ao EFI Bank:

```bash
mysql -u seu_usuario -p seu_banco < database/add-payment-provider-column.sql
```

### 2. Obter Credenciais no EFI Bank

1. Acesse: https://sejaefi.com.br
2. Faça login na sua conta
3. Vá em **API** → **Aplicações**
4. Crie uma nova aplicação ou selecione uma existente
5. Copie o **Client ID** e **Client Secret**

### 3. Configurar Chave PIX

1. No painel do EFI Bank, vá em **PIX** → **Minhas Chaves**
2. Cadastre uma chave PIX (email, telefone, CPF/CNPJ ou aleatória)
3. Anote a chave cadastrada

### 4. Certificado SSL (Produção)

Para ambiente de produção, você precisa do certificado SSL:

1. No painel do EFI Bank, vá em **API** → **Certificados**
2. Faça download do certificado (.pem)
3. Salve o arquivo em um local seguro no servidor
4. Anote o caminho completo do arquivo

**Exemplo:**
```
/var/www/gestor/certificates/efi-production.pem
```

### 5. Configurar no Sistema

1. Acesse o sistema como **Admin**
2. Vá em **Métodos de Pagamento**
3. Localize o card **EFI Bank**
4. Preencha os campos:
   - **Client ID**: Cole o Client ID obtido
   - **Client Secret**: Cole o Client Secret obtido
   - **Chave PIX**: Cole a chave PIX cadastrada
   - **Certificado SSL**: Caminho completo do certificado (apenas produção)
   - **Modo Sandbox**: Marque para testes, desmarque para produção
   - **Ativar EFI Bank**: Marque para habilitar

5. Clique em **Testar Conexão** para validar
6. Se o teste passar, clique em **Salvar Configurações**

## 🧪 Testando

### Modo Sandbox (Homologação)

1. Marque a opção **Modo Sandbox**
2. Use as credenciais de homologação
3. Não é necessário certificado SSL
4. Use a API de homologação: `https://api-pix-h.gerencianet.com.br`

### Modo Produção

1. Desmarque a opção **Modo Sandbox**
2. Use as credenciais de produção
3. Configure o certificado SSL
4. Use a API de produção: `https://api-pix.gerencianet.com.br`

## 🔄 Fluxo de Pagamento

### Para Revendedores

1. Revendedor acessa **Renovar Acesso**
2. Seleciona um plano ou clica em **Renovar Plano**
3. Sistema gera QR Code PIX via EFI Bank
4. Revendedor paga via PIX
5. Webhook recebe notificação
6. Sistema renova automaticamente por +30 dias (ou duração do plano)

### Para Clientes (Faturas)

1. Cliente recebe fatura com PIX
2. Sistema gera QR Code via EFI Bank
3. Cliente paga via PIX
4. Webhook recebe notificação
5. Sistema marca fatura como paga
6. Cliente é renovado automaticamente por +30 dias

## 🔔 Configurar Webhook

Para receber notificações automáticas de pagamento:

1. No painel do EFI Bank, vá em **API** → **Webhooks**
2. Configure a URL do webhook:
   ```
   https://seu-dominio.com/webhook-efibank.php
   ```
3. Selecione os eventos:
   - `pix` (Pagamento PIX recebido)

## 🔐 Segurança

### Certificado SSL

O certificado SSL é **obrigatório** para produção. Ele garante:
- Autenticação segura com a API
- Criptografia das comunicações
- Validação da identidade

### Permissões do Arquivo

```bash
chmod 600 /caminho/para/certificado.pem
chown www-data:www-data /caminho/para/certificado.pem
```

## 📊 Prioridade de Pagamento

O sistema verifica os métodos de pagamento nesta ordem:

1. **EFI Bank** (se configurado e ativo)
2. **Mercado Pago** (fallback)

Para usar apenas EFI Bank:
- Ative o EFI Bank
- Desative o Mercado Pago

Para usar apenas Mercado Pago:
- Desative o EFI Bank
- Ative o Mercado Pago

## 🐛 Troubleshooting

### Erro: "Credenciais inválidas"

- Verifique se o Client ID e Client Secret estão corretos
- Confirme se está usando credenciais do ambiente correto (sandbox/produção)

### Erro: "Chave PIX não encontrada"

- Verifique se a chave PIX está cadastrada no EFI Bank
- Confirme se a chave está ativa

### Erro: "Certificado SSL inválido"

- Verifique se o caminho do certificado está correto
- Confirme se o arquivo tem permissões de leitura
- Certifique-se de que o certificado não expirou

### Webhook não recebe notificações

- Verifique se a URL do webhook está correta
- Confirme se o webhook está ativo no painel do EFI Bank
- Verifique os logs em: `logs/efibank-webhook.log`

## 📝 Logs

Os logs do EFI Bank são salvos em:

- **Webhook**: `logs/efibank-webhook.log`
- **API**: Logs do PHP (error_log)

Para visualizar:

```bash
tail -f logs/efibank-webhook.log
```

## 🔗 Links Úteis

- **Portal EFI Bank**: https://sejaefi.com.br
- **Documentação API PIX**: https://dev.efipay.com.br/docs/api-pix
- **Suporte EFI Bank**: https://sejaefi.com.br/suporte

## ✅ Checklist de Configuração

- [ ] Conta EFI Bank criada
- [ ] Aplicação criada no painel
- [ ] Client ID e Client Secret obtidos
- [ ] Chave PIX cadastrada
- [ ] Certificado SSL baixado (produção)
- [ ] Banco de dados atualizado
- [ ] Credenciais configuradas no sistema
- [ ] Teste de conexão realizado com sucesso
- [ ] Webhook configurado
- [ ] Teste de pagamento realizado

## 🎉 Pronto!

Agora o EFI Bank está configurado e pronto para receber pagamentos PIX!

Os revendedores poderão renovar seus acessos e os clientes poderão pagar faturas via PIX usando o EFI Bank.
