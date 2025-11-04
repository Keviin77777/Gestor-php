# 🧪 Guia de Testes - Checkout PIX

## Pré-requisitos

- ✅ Tabela `invoice_payments` criada
- ✅ Mercado Pago configurado (modo sandbox para testes)
- ✅ Webhook configurado no Mercado Pago
- ✅ Cliente com fatura pendente

## 1️⃣ Teste Manual - Página de Checkout

### Passo 1: Criar Fatura de Teste
```sql
-- Inserir fatura de teste
INSERT INTO invoices (client_id, value, due_date, status, created_at)
VALUES (1, 50.00, CURDATE(), 'pending', NOW());

-- Anotar o ID da fatura criada
SELECT LAST_INSERT_ID();
```

### Passo 2: Acessar Checkout
```
http://localhost:8000/checkout.php?invoice=ID_DA_FATURA
```

### Passo 3: Verificar Exibição
- [ ] Nome do cliente aparece
- [ ] Valor está correto
- [ ] Data de vencimento está correta
- [ ] Status é "Pendente"
- [ ] Botão "Pagar com PIX" está visível

### Passo 4: Gerar PIX
1. Clique em "Pagar com PIX"
2. Aguarde carregamento
3. Verifique se modal aparece

### Passo 5: Verificar Modal
- [ ] QR Code é exibido
- [ ] Código PIX está no textarea
- [ ] Botão "Copiar" funciona
- [ ] Instruções estão claras
- [ ] Status mostra "Aguardando pagamento..."

## 2️⃣ Teste de Integração - Template WhatsApp

### Passo 1: Criar Template
```sql
-- Criar template de teste
INSERT INTO whatsapp_templates (
    reseller_id, 
    name, 
    type, 
    message, 
    is_active
) VALUES (
    'admin-001',
    'Teste Payment Link',
    'expires_today',
    'Olá {cliente_nome}!\n\nSua fatura de R$ {cliente_valor} vence hoje.\n\nPague agora:\n{payment_link}',
    1
);
```

### Passo 2: Testar Variável
```php
// Executar em test-payment-link.php
<?php
require_once 'app/core/Database.php';
require_once 'app/helpers/whatsapp-automation.php';

$client = Database::fetch("SELECT * FROM clients WHERE id = 1");
$template = Database::fetch("SELECT * FROM whatsapp_templates WHERE type = 'expires_today' LIMIT 1");

$variables = prepareTemplateVariables($template, $client);

echo "Variáveis:\n";
print_r($variables);

echo "\n\nPayment Link: " . $variables['payment_link'];
?>
```

### Passo 3: Verificar Resultado
- [ ] `payment_link` está presente
- [ ] Link tem formato correto
- [ ] Link contém ID da fatura

## 3️⃣ Teste de Pagamento - Sandbox

### Passo 1: Configurar Sandbox
1. Acesse: https://www.mercadopago.com.br/developers/panel/app
2. Copie credenciais de teste
3. Configure no sistema

### Passo 2: Gerar PIX de Teste
1. Acesse checkout da fatura
2. Clique em "Pagar com PIX"
3. Copie o código PIX

### Passo 3: Simular Pagamento
```bash
# Usar API do Mercado Pago para simular aprovação
curl -X PUT \
  'https://api.mercadopago.com/v1/payments/PAYMENT_ID' \
  -H 'Authorization: Bearer TEST_ACCESS_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "status": "approved"
  }'
```

### Passo 4: Verificar Webhook
```bash
# Ver logs do webhook
tail -f logs/mercadopago-webhook.log
```

### Passo 5: Verificar Banco
```sql
-- Verificar pagamento
SELECT * FROM invoice_payments WHERE payment_id = 'PAYMENT_ID';

-- Verificar fatura
SELECT * FROM invoices WHERE id = INVOICE_ID;

-- Verificar cliente
SELECT id, name, renewal_date, status FROM clients WHERE id = CLIENT_ID;
```

## 4️⃣ Teste de Responsividade

### Desktop (> 768px)
- [ ] Card centralizado
- [ ] Largura máxima 600px
- [ ] Espaçamento adequado
- [ ] QR Code legível

### Tablet (768px)
- [ ] Layout se adapta
- [ ] Botões acessíveis
- [ ] Texto legível

### Mobile (< 480px)
- [ ] Tela cheia
- [ ] Botões grandes (44px)
- [ ] QR Code adaptável
- [ ] Scroll suave

## 5️⃣ Teste de Fluxo Completo

### Cenário: Cliente Paga Fatura

1. **Criar Cliente e Fatura**
```sql
-- Cliente de teste
INSERT INTO clients (reseller_id, name, email, phone, value, renewal_date, status)
VALUES ('admin-001', 'João Teste', 'joao@teste.com', '11999999999', 50.00, CURDATE(), 'active');

-- Fatura de teste
INSERT INTO invoices (client_id, value, due_date, status)
VALUES (LAST_INSERT_ID(), 50.00, CURDATE(), 'pending');
```

2. **Enviar Template**
   - Usar função de envio manual
   - Verificar se link aparece no WhatsApp

3. **Cliente Acessa Link**
   - Clicar no link recebido
   - Verificar página de checkout

4. **Cliente Gera PIX**
   - Clicar em "Pagar com PIX"
   - Verificar modal com QR Code

5. **Cliente Paga**
   - Simular pagamento (sandbox)
   - Aguardar webhook

6. **Verificar Atualização**
```sql
-- Fatura deve estar paga
SELECT status, paid_at FROM invoices WHERE id = INVOICE_ID;

-- Cliente deve estar renovado
SELECT renewal_date, status FROM clients WHERE id = CLIENT_ID;
```

## 6️⃣ Teste de Erros

### Fatura Não Encontrada
```
http://localhost:8000/checkout.php?invoice=99999
```
- [ ] Mensagem de erro aparece

### Fatura Já Paga
```sql
UPDATE invoices SET status = 'paid' WHERE id = 1;
```
- [ ] Mensagem "Fatura já paga" aparece
- [ ] Botão de pagamento não aparece

### Mercado Pago Não Configurado
```sql
UPDATE payment_methods SET is_active = 0 WHERE provider = 'mercadopago';
```
- [ ] Mensagem de indisponibilidade aparece

### Credenciais Inválidas
- Configurar credenciais erradas
- [ ] Erro ao gerar PIX
- [ ] Mensagem de erro clara

## 7️⃣ Teste de Performance

### Tempo de Carregamento
- [ ] Checkout carrega em < 2s
- [ ] PIX gera em < 3s
- [ ] Modal abre instantaneamente

### Verificação Automática
- [ ] Verifica a cada 5 segundos
- [ ] Para após 10 minutos
- [ ] Não trava a página

## 8️⃣ Checklist Final

### Funcionalidades
- [ ] Link de pagamento é gerado
- [ ] Checkout exibe dados corretos
- [ ] PIX é gerado com sucesso
- [ ] QR Code é exibido
- [ ] Código pode ser copiado
- [ ] Verificação automática funciona
- [ ] Webhook processa pagamento
- [ ] Fatura é marcada como paga
- [ ] Cliente é renovado

### Design
- [ ] Layout profissional
- [ ] Cores consistentes
- [ ] Ícones carregam
- [ ] Animações suaves
- [ ] Responsivo em todos os tamanhos

### Segurança
- [ ] Validações funcionam
- [ ] Erros são tratados
- [ ] Logs são gerados
- [ ] Dados sensíveis protegidos

## 🐛 Problemas Comuns

### "Fatura não encontrada"
- Verificar se ID está correto
- Confirmar que fatura existe no banco

### "Método de pagamento não configurado"
- Verificar credenciais do Mercado Pago
- Confirmar que método está ativo

### "Erro ao gerar PIX"
- Ver logs: `logs/mercadopago-webhook.log`
- Verificar credenciais
- Testar em modo sandbox

### Webhook não é chamado
- Verificar URL configurada no Mercado Pago
- Confirmar que servidor está acessível
- Testar manualmente: `curl https://seu-dominio/webhook-mercadopago.php`

### Cliente não é renovado
- Verificar logs do webhook
- Confirmar que pagamento foi aprovado
- Ver tabela `invoice_payments`

## 📊 Métricas de Sucesso

- ✅ 100% das faturas geram link
- ✅ 100% dos PIX são gerados
- ✅ 100% dos pagamentos são processados
- ✅ 0 erros em produção
- ✅ < 3s para gerar PIX
- ✅ 100% de renovações automáticas

---

**Testes concluídos?** Sistema pronto para produção! 🚀
