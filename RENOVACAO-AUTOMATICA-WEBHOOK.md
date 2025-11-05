# Renovação Automática via Webhook - Mercado Pago

## Visão Geral

O sistema agora possui renovação automática completa quando um cliente paga uma fatura via PIX através do checkout. O processo é totalmente automatizado e funciona da seguinte forma:

## Fluxo Completo de Renovação

### 1. Cliente Acessa o Checkout
- Cliente recebe link da fatura: `https://seudominio.com/checkout.php?invoice=ID_DA_FATURA`
- Página mostra detalhes da fatura e botão "Pagar com PIX"

### 2. Geração do PIX
- Cliente clica em "Pagar com PIX"
- Sistema chama `/api-invoice-generate-pix.php`
- Cria pagamento no Mercado Pago com:
  - `external_reference`: `INVOICE_{ID_FATURA}_CLIENT_{ID_CLIENTE}`
  - `notification_url`: `https://seudominio.com/webhook-mercadopago.php`
- Salva dados do pagamento na tabela `invoice_payments`
- Exibe QR Code para o cliente

### 3. Cliente Paga o PIX
- Cliente escaneia QR Code ou copia código PIX
- Realiza pagamento no app do banco
- Mercado Pago processa o pagamento

### 4. Webhook Automático (RENOVAÇÃO)
Quando o pagamento é aprovado, o Mercado Pago envia webhook para `/webhook-mercadopago.php`:

#### ✅ **Fatura Marcada como Paga**
```sql
UPDATE invoices SET 
    status = 'paid',
    paid_at = NOW(),
    payment_method = 'pix_mercadopago'
WHERE id = ?
```

#### ✅ **Cliente Renovado Automaticamente (+30 dias)**
```sql
UPDATE clients SET 
    renewal_date = DATE_ADD(renewal_date, INTERVAL 30 DAY),
    status = 'active'
WHERE id = ?
```

#### ✅ **Sincronização com Sigma (se configurado)**
- Busca servidor Sigma configurado para o revendedor
- Renova cliente no painel Sigma automaticamente
- Atualiza status no Sigma para ACTIVE

#### ✅ **Envio Automático de WhatsApp (se configurado)**
- Busca template "Renovado Padrão" ativo
- Envia mensagem automática de renovação confirmada
- Inclui nova data de vencimento e valor pago
- Registra mensagem no histórico

## Configurações Necessárias

### 1. Variável de Ambiente
No arquivo `.env`, configure:
```env
APP_URL=https://seudominio.com
```
⚠️ **Importante**: Não use `localhost` em produção, pois o Mercado Pago não consegue acessar.

### 2. Webhook do Mercado Pago
O webhook é configurado automaticamente quando o PIX é gerado:
- **URL**: `https://seudominio.com/webhook-mercadopago.php`
- **Eventos**: `payment` (aprovado, rejeitado, cancelado)

### 3. Configuração do Sigma (Opcional)
Se você tem servidor Sigma configurado:
1. Acesse **Servidores** no admin
2. Configure servidor com tipo "Sigma"
3. Preencha URL, token e usuário do painel
4. A renovação no Sigma será automática

### 4. Configuração do WhatsApp (Opcional)
Para envio automático de mensagens de renovação:
1. Acesse **Configurações > WhatsApp**
2. Conecte uma sessão WhatsApp
3. Habilite "Envio automático de renovação"
4. Crie template "Renovado Padrão" em **Templates WhatsApp**
5. A mensagem será enviada automaticamente

## Logs e Monitoramento

### Arquivo de Log
Todos os webhooks são registrados em:
```
/logs/mercadopago-webhook.log
```

### Mensagens de Log
```
[2024-11-04 10:30:15] POST Request
Payment ID: 123456789 | Status: approved | Ref: INVOICE_123_CLIENT_456
✅ Fatura #123 marcada como PAGA
✅ Cliente #456 renovado no gestor até 2024-12-04
✅ Cliente sincronizado com Sigma: Cliente renovado no Sigma
✅ Mensagem WhatsApp de renovação enviada
```

## Testando a Renovação

### Teste Manual
Execute o arquivo de teste:
```bash
php test-webhook-renovation.php
```

Este teste:
- ✅ Cria/busca uma fatura pendente
- ✅ Simula webhook de pagamento aprovado
- ✅ Testa renovação no gestor (+30 dias)
- ✅ Testa sincronização com Sigma
- ✅ Verifica resultado final

### Teste Real
1. Crie uma fatura para um cliente
2. Acesse o checkout da fatura
3. Gere um PIX (pode usar valor baixo para teste)
4. Pague o PIX
5. Verifique os logs em `/logs/mercadopago-webhook.log`
6. Confirme que o cliente foi renovado

## Funcionalidades Implementadas

### ✅ **Renovação no Gestor**
- Adiciona 30 dias à data de renovação atual
- Se cliente já venceu, renova a partir de hoje
- Ativa status do cliente automaticamente

### ✅ **Sincronização com Sigma**
- Detecta servidor Sigma configurado
- Renova cliente no painel automaticamente
- Atualiza status para ACTIVE
- Funciona com a mesma lógica do histórico de pagamentos

### ✅ **Envio Automático de WhatsApp**
- Detecta configurações WhatsApp ativas
- Busca template "Renovado Padrão" configurado
- Envia mensagem personalizada com dados do cliente
- Inclui nova data de vencimento e valor pago
- Registra mensagem no histórico para controle

### ✅ **Logs Detalhados**
- Registra todos os webhooks recebidos
- Log de cada etapa do processamento
- Mensagens de erro e sucesso
- Facilita debugging e monitoramento

### ✅ **Tratamento de Erros**
- Webhook sempre retorna 200 (evita reenvios)
- Erros são logados mas não interrompem processo
- Sincronização Sigma é opcional (não quebra se falhar)

## Compatibilidade

### ✅ **Histórico de Pagamentos**
A renovação automática via webhook funciona igual ao botão "Marcar como Pago" no histórico:
- Mesma lógica de renovação (+30 dias)
- Mesma sincronização com Sigma
- Mesmos logs e tratamento de erros

### ✅ **Múltiplos Revendedores**
- Cada revendedor pode ter seu próprio Mercado Pago
- Cada revendedor pode ter seu próprio servidor Sigma
- Cada revendedor pode ter suas próprias configurações WhatsApp
- Renovações são isoladas por revendedor
- Webhook identifica automaticamente o revendedor correto
- Credenciais específicas são usadas para cada revendedor

## Próximos Passos

### Melhorias Futuras
- [ ] Configurar período de renovação por plano (não fixo 30 dias)
- [ ] Envio de email/WhatsApp de confirmação
- [ ] Dashboard de pagamentos em tempo real
- [ ] Webhook para outros eventos (estorno, chargeback)
- [ ] Integração com outros painéis IPTV

### Monitoramento Recomendado
- [ ] Configurar alertas para falhas de webhook
- [ ] Monitorar logs diariamente
- [ ] Backup regular da tabela de pagamentos
- [ ] Teste mensal da renovação automática

## Conclusão

✅ **Sistema Completo Implementado**

O sistema agora funciona exatamente como você solicitou:

1. **Cliente paga via PIX no checkout** → Mercado Pago processa
2. **Webhook identifica pagamento aprovado** → Sistema recebe notificação
3. **Fatura marcada como paga automaticamente** → Status atualizado
4. **Cliente renovado por +30 dias no gestor** → Data de renovação estendida
5. **Se Sigma configurado, renova no painel também** → Sincronização automática
6. **Se WhatsApp configurado, envia mensagem "Renovado Padrão"** → Notificação automática

A mesma lógica do "Marcar como Pago" no histórico agora funciona automaticamente via webhook, incluindo o envio de WhatsApp! 🎯