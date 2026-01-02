# Sistema de Bloqueio por Plano Vencido

## 📋 Resumo
Sistema implementado para bloquear acesso de revendedores com plano vencido, impedindo uso das funcionalidades e envio de mensagens WhatsApp até que renovem.

## 🔒 Componentes Implementados

### 1. Backend - Guard de Plano (`app/helpers/plan-guard.php`)

**Funções principais:**
- `checkResellerPlanActive($userId)` - Verifica se o plano está ativo
- `requireActivePlan()` - Middleware para proteger APIs
- `canSendWhatsAppMessages($resellerId)` - Verifica se pode enviar WhatsApp

**Regras:**
- ✅ Admin sempre tem acesso
- ❌ Revendedor com `days_remaining < 0` é bloqueado
- ⚠️ Retorna erro 403 com mensagem clara

### 2. Frontend - Proteção de Rotas (`frontend/src/App.tsx`)

**Componente `PlanGuard`:**
- Verifica status do plano ao carregar
- Redireciona para `/renew-access` se vencido
- Permite acesso apenas à página de renovação

**Fluxo:**
1. Usuário faz login
2. `PlanGuard` verifica plano via API
3. Se vencido → redireciona para renovação
4. Se ativo → permite navegação normal

### 3. APIs Protegidas

**APIs com verificação de plano:**
- ✅ `public/api-clients.php` - Bloqueio em POST/PUT/DELETE (GET permitido para visualizar)
- ✅ `public/api-whatsapp-send.php` - Bloqueio total
- ✅ `public/api-invoices.php` - Adicionar `requireActivePlan()`
- ✅ `public/api-whatsapp-templates.php` - Adicionar `requireActivePlan()`
- ✅ `public/api-whatsapp-scheduling.php` - Adicionar `requireActivePlan()`

**Como adicionar proteção em outras APIs:**
```php
// No início da API, após autenticação
require_once __DIR__ . '/../app/helpers/plan-guard.php';
requireActivePlan(); // Bloqueia se plano vencido
```

### 4. Automações WhatsApp Protegidas

**Arquivos modificados:**
- ✅ `app/helpers/whatsapp-automation.php`
  - `runScheduledTemplates()` - Verifica plano antes de enviar
  - `runWhatsAppReminderAutomation()` - Verifica plano por cliente
  
- ✅ `scripts/reseller-renewal-automation.php`
  - Envia lembretes de renovação para revendedores

**Comportamento:**
- Automações verificam plano antes de cada envio
- Clientes de revendedores com plano vencido não recebem mensagens
- Logs registram quando envio é bloqueado por plano vencido

## 🔄 Fluxo de Renovação

### Quando o plano vence:

1. **Frontend:**
   - Usuário é redirecionado para `/renew-access`
   - Não consegue acessar outras páginas
   - Vê planos disponíveis e pode gerar PIX

2. **Backend:**
   - APIs retornam erro 403 com `plan_expired: true`
   - Mensagem: "Seu plano expirou. Renove para continuar usando o sistema."
   - Automações param de enviar mensagens

3. **Após pagamento PIX:**
   - Webhook atualiza `plan_expires_at` e `plan_status`
   - Sistema calcula nova data de vencimento
   - Usuário volta a ter acesso imediato

## 📝 Checklist de Implementação

### Backend
- [x] Criar `plan-guard.php`
- [x] Adicionar proteção em `api-clients.php`
- [x] Adicionar proteção em `api-whatsapp-send.php`
- [ ] Adicionar proteção em `api-invoices.php`
- [ ] Adicionar proteção em `api-whatsapp-templates.php`
- [ ] Adicionar proteção em `api-whatsapp-scheduling.php`
- [ ] Adicionar proteção em `api-servers.php`
- [ ] Adicionar proteção em `api-payment-methods.php`
- [x] Proteger automações WhatsApp

### Frontend
- [x] Criar componente `PlanGuard` no App.tsx
- [x] Verificar plano ao carregar aplicação
- [x] Redirecionar para `/renew-access` se vencido
- [x] Permitir acesso apenas à página de renovação

### Testes
- [ ] Testar login com plano vencido
- [ ] Testar tentativa de criar cliente com plano vencido
- [ ] Testar envio de WhatsApp com plano vencido
- [ ] Testar renovação via PIX
- [ ] Testar acesso após renovação
- [ ] Testar automações com plano vencido

## 🚀 Como Testar

### 1. Simular plano vencido:
```sql
-- Vencer plano de um revendedor
UPDATE users 
SET plan_expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE id = 'seu-reseller-id';
```

### 2. Testar bloqueio:
- Fazer login com o revendedor
- Tentar acessar qualquer página → deve redirecionar para `/renew-access`
- Tentar criar cliente via API → deve retornar erro 403
- Verificar logs de automação → deve pular clientes deste revendedor

### 3. Testar renovação:
- Na página `/renew-access`, selecionar um plano
- Gerar PIX
- Simular pagamento no banco:
```sql
UPDATE renewal_payments 
SET status = 'approved' 
WHERE payment_id = 'payment-id-gerado';
```
- Executar webhook manualmente ou aguardar cron
- Verificar se `plan_expires_at` foi atualizado
- Fazer logout e login novamente
- Verificar se tem acesso normal

## 📊 Monitoramento

### Logs importantes:
- `logs/whatsapp-automation-*.log` - Automações bloqueadas
- `logs/php-errors.log` - Erros de plano vencido
- `logs/reseller-automation-*.log` - Lembretes de renovação

### Queries úteis:
```sql
-- Revendedores com plano vencido
SELECT id, name, email, plan_expires_at, 
       DATEDIFF(CURDATE(), DATE(plan_expires_at)) as days_expired
FROM users 
WHERE role = 'reseller' 
AND plan_expires_at < NOW()
ORDER BY plan_expires_at DESC;

-- Revendedores que vencem em 7 dias
SELECT id, name, email, plan_expires_at,
       DATEDIFF(DATE(plan_expires_at), CURDATE()) as days_remaining
FROM users 
WHERE role = 'reseller' 
AND DATEDIFF(DATE(plan_expires_at), CURDATE()) BETWEEN 0 AND 7
ORDER BY plan_expires_at ASC;
```

## ⚠️ Observações Importantes

1. **Admin sempre tem acesso** - Não é bloqueado mesmo com plano vencido
2. **GET permitido em clientes** - Revendedor pode visualizar clientes mesmo com plano vencido
3. **Página de renovação sempre acessível** - `/renew-access` nunca é bloqueada
4. **Automações param completamente** - Nenhuma mensagem é enviada para clientes de revendedores com plano vencido
5. **Webhooks continuam funcionando** - Renovações via PIX são processadas normalmente

## 🔧 Manutenção

### Adicionar proteção em nova API:
```php
<?php
// Após autenticação
require_once __DIR__ . '/../app/helpers/plan-guard.php';

// Para bloquear completamente
requireActivePlan();

// OU para verificar manualmente
$planCheck = checkResellerPlanActive($userId, false);
if (!$planCheck['has_access']) {
    // Tratar plano vencido
}
```

### Adicionar proteção em nova automação:
```php
require_once __DIR__ . '/plan-guard.php';
if (!canSendWhatsAppMessages($resellerId)) {
    // Pular envio
    return;
}
```
