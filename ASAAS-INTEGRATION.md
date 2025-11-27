# Integração Asaas - Documentação Completa

## 📋 Visão Geral

A integração com Asaas permite que revendedores recebam pagamentos via PIX de forma automática, com renovação automática de clientes no gestor e sincronização com Sigma.

## 🎯 Funcionalidades

### ✅ O que o Asaas faz (igual ao Mercado Pago):

1. **Geração de PIX com QR Code**
   - Cria cobrança PIX automaticamente
   - Gera QR Code e código copia-e-cola
   - Link de pagamento para compartilhar

2. **Renovações Automáticas**
   - Webhook recebe notificação de pagamento
   - Renova cliente automaticamente no gestor
   - Adiciona 30 dias à data de renovação
   - Sincroniza com Sigma automaticamente

3. **Checkout em Faturas**
   - Quando fatura tem payment_link configurado
   - Gera QR Code PIX do Asaas
   - Cliente paga e sistema reconhece automaticamente

4. **Notificações WhatsApp**
   - Envia mensagem quando pagamento confirmado
   - Notifica cliente sobre renovação

## 🚀 Como Configurar

### 1. Obter Credenciais Asaas

1. Acesse [Asaas](https://www.asaas.com)
2. Faça login na sua conta
3. Vá em **Configurações** → **Integrações** → **API**
4. Copie sua **API Key**

**Importante:**
- Use API Key de **Sandbox** para testes
- Use API Key de **Produção** para pagamentos reais

### 2. Configurar no Gestor

1. Acesse **Métodos de Pagamento** no menu
2. Clique em **Configurar** no card do Asaas
3. Cole sua **API Key**
4. Marque **Modo Sandbox** se for testar
5. Clique em **Testar Conexão** para validar
6. Marque **Ativar Asaas**
7. Clique em **Salvar**

### 3. Configurar Webhook no Asaas

Para receber notificações de pagamento automaticamente:

1. Acesse o painel Asaas
2. Vá em **Configurações** → **Integrações** → **Webhooks**
3. Adicione a URL do webhook:
   ```
   https://seudominio.com/webhook-asaas.php
   ```
4. Selecione os eventos:
   - ✅ PAYMENT_RECEIVED
   - ✅ PAYMENT_CONFIRMED
   - ✅ PAYMENT_OVERDUE
   - ✅ PAYMENT_DELETED
   - ✅ PAYMENT_REFUNDED

## 📊 Fluxo de Pagamento

### Pagamento de Fatura

```
1. Cliente recebe fatura com payment_link
2. Sistema gera PIX via Asaas
3. Cliente paga via QR Code ou copia-e-cola
4. Asaas envia webhook para o gestor
5. Sistema marca fatura como paga
6. Cliente é renovado automaticamente (+30 dias)
7. Sincroniza com Sigma
8. Envia notificação WhatsApp
```

### Renovação de Revendedor

```
1. Revendedor escolhe plano
2. Sistema gera PIX via Asaas
3. Revendedor paga
4. Webhook confirma pagamento
5. Plano do revendedor é renovado
6. Nova data de expiração calculada
```

## 🔧 Arquivos da Integração

### Backend

- `app/helpers/AsaasHelper.php` - Helper principal do Asaas
- `public/webhook-asaas.php` - Webhook para receber notificações
- `public/api-payment-methods.php` - API de configuração
- `database/add-asaas-to-payment-methods.sql` - Migration

### Frontend

- `app/views/payment-methods/index.php` - Página de configuração
- `public/assets/css/payment-methods.css` - Estilos
- `public/assets/js/payment-methods.js` - JavaScript

## 📝 Estrutura do Banco de Dados

### Tabela: payment_methods

```sql
INSERT INTO payment_methods (method_name, config_value, enabled) 
VALUES ('asaas', '{"api_key":"","sandbox":false}', 0);
```

### Tabela: invoice_payments

```sql
-- Armazena pagamentos de faturas
payment_id VARCHAR(255) -- ID do pagamento no Asaas
payment_method VARCHAR(50) -- 'pix_asaas'
status VARCHAR(50) -- 'pending', 'approved', 'cancelled'
```

### Tabela: renewal_payments

```sql
-- Armazena renovações de revendedores
payment_id VARCHAR(100) -- ID do pagamento no Asaas
payment_provider VARCHAR(50) -- 'asaas'
status VARCHAR(20) -- 'pending', 'approved', 'rejected'
```

## 🔐 Segurança

### Validação de Webhook

O webhook valida:
- ✅ JSON válido
- ✅ Evento suportado
- ✅ Payment ID presente
- ✅ Asaas está habilitado

### Logs

Todos os webhooks são registrados em:
```
logs/asaas-webhook.log
```

Formato:
```
[2024-01-15 10:30:45] POST Request
Headers: {...}
Body: {...}
Payment ID: pay_123456 | Status: approved | Event: PAYMENT_RECEIVED
✅ Fatura #123 marcada como PAGA
✅ Cliente #456 renovado até 2024-02-15
```

## 🧪 Testando a Integração

### 1. Teste de Conexão

No painel de Métodos de Pagamento:
1. Configure API Key
2. Marque "Modo Sandbox"
3. Clique em "Testar Conexão"
4. Deve retornar: ✅ Credenciais válidas

### 2. Teste de Pagamento

1. Crie uma fatura de teste
2. Gere PIX via Asaas
3. Use o ambiente Sandbox do Asaas para simular pagamento
4. Verifique se fatura foi marcada como paga
5. Verifique se cliente foi renovado

### 3. Teste de Webhook

1. Use ferramenta como [Webhook.site](https://webhook.site)
2. Configure URL temporária no Asaas
3. Faça pagamento de teste
4. Verifique payload recebido
5. Configure URL real do seu servidor

## 📚 API Asaas - Endpoints Utilizados

### 1. Criar Cliente
```
POST /v3/customers
{
  "name": "Nome do Cliente",
  "cpfCnpj": "12345678900",
  "email": "cliente@email.com",
  "phone": "11999999999"
}
```

### 2. Criar Cobrança PIX
```
POST /v3/payments
{
  "customer": "cus_123456",
  "billingType": "PIX",
  "value": 29.90,
  "dueDate": "2024-01-20",
  "description": "Renovação IPTV",
  "externalReference": "INVOICE_123"
}
```

### 3. Gerar QR Code PIX
```
GET /v3/payments/{paymentId}/pixQrCode
Response:
{
  "payload": "00020126580014br.gov.bcb.pix...",
  "encodedImage": "data:image/png;base64,iVBORw0KGgo..."
}
```

### 4. Consultar Pagamento
```
GET /v3/payments/{paymentId}
Response:
{
  "id": "pay_123456",
  "status": "RECEIVED",
  "value": 29.90,
  "confirmedDate": "2024-01-15"
}
```

## 🎨 Interface do Usuário

### Layout Moderno

- **Cards de Provedores**: Grid responsivo com cards para cada provedor
- **Modal de Configuração**: Modal centralizado para configurar cada provedor
- **Status Visual**: Badges coloridos indicando status (Ativo/Inativo)
- **Animações**: Hover effects e transições suaves

### Seleção de Provedores

Revendedores podem:
- ✅ Ver todos os provedores disponíveis
- ✅ Configurar múltiplos provedores
- ✅ Ativar/desativar cada um independentemente
- ✅ Testar conexão antes de salvar

## 🔄 Comparação: Asaas vs Mercado Pago

| Funcionalidade | Asaas | Mercado Pago |
|----------------|-------|--------------|
| PIX com QR Code | ✅ | ✅ |
| Renovação Automática | ✅ | ✅ |
| Webhook | ✅ | ✅ |
| Sincronização Sigma | ✅ | ✅ |
| WhatsApp | ✅ | ✅ |
| Boleto | ✅ | ❌ |
| Cartão de Crédito | ✅ | ✅ |
| Modo Sandbox | ✅ | ✅ |
| Taxas | Variável | Variável |

## 🐛 Troubleshooting

### Erro: "API Key inválida"
- Verifique se copiou a API Key completa
- Confirme se está usando a key correta (Sandbox/Produção)
- Verifique se a key não expirou

### Webhook não está funcionando
- Verifique se a URL está acessível publicamente
- Confirme se configurou os eventos corretos
- Verifique logs em `logs/asaas-webhook.log`
- Teste com Webhook.site primeiro

### Pagamento não renova cliente
- Verifique se webhook está configurado
- Confirme se external_reference está correto
- Verifique logs do webhook
- Confirme se Asaas está habilitado

### Cliente não sincroniza com Sigma
- Verifique configuração do Sigma
- Confirme credenciais do servidor
- Verifique logs de sincronização
- Teste sincronização manual

## 📞 Suporte

### Documentação Oficial
- [Asaas API Docs](https://docs.asaas.com/)
- [Asaas Webhooks](https://docs.asaas.com/reference/webhooks)

### Logs do Sistema
- Webhook: `logs/asaas-webhook.log`
- API: `logs/api.log`
- Erros: `logs/error.log`

## 🎉 Conclusão

A integração com Asaas está completa e funcional, oferecendo:
- ✅ Mesmas funcionalidades do Mercado Pago
- ✅ Interface moderna e intuitiva
- ✅ Renovações automáticas
- ✅ Sincronização com Sigma
- ✅ Notificações WhatsApp
- ✅ Logs detalhados
- ✅ Fácil configuração

Agora seus revendedores podem escolher entre Mercado Pago, Asaas ou EFI Bank para receber pagamentos!
