# Configuração do Webhook Asaas

## ✅ Status da Integração

O Asaas está **100% integrado** e funciona exatamente como o Mercado Pago:

### Funcionalidades Implementadas

1. ✅ **Geração de PIX** via Asaas
2. ✅ **Webhook configurado** para receber notificações
3. ✅ **Renovação automática** de clientes no gestor
4. ✅ **Sincronização com Sigma** (se configurado)
5. ✅ **Envio de WhatsApp** após pagamento
6. ✅ **Suporte a renovação de revendedores**
7. ✅ **Modo Sandbox** para testes

---

## 🔧 Como Configurar o Webhook no Asaas

### 1. Acesse o Painel do Asaas

**Produção:**
- URL: https://www.asaas.com/config/api
- Vá em: **Configurações → Integrações → Webhooks**

**Sandbox (Testes):**
- URL: https://sandbox.asaas.com/customerConfigIntegrations/index
- Vá em: **Configurações → Integrações → Webhooks**

### 2. Configure a URL do Webhook

Adicione a seguinte URL:

```
https://SEU_DOMINIO.com/webhook-asaas.php
```

**Exemplo:**
```
https://ultragestor.site/webhook-asaas.php
```

### 3. Selecione os Eventos

Marque os seguintes eventos para receber notificações:

- ✅ **PAYMENT_RECEIVED** - Pagamento recebido
- ✅ **PAYMENT_CONFIRMED** - Pagamento confirmado
- ✅ **PAYMENT_OVERDUE** - Pagamento vencido
- ✅ **PAYMENT_DELETED** - Pagamento deletado
- ✅ **PAYMENT_REFUNDED** - Pagamento reembolsado

### 4. Salve a Configuração

Clique em **Salvar** e o webhook estará ativo.

---

## 🧪 Como Testar

### 1. Modo Sandbox

1. Crie uma conta no sandbox: https://sandbox.asaas.com/signup
2. Configure a API Key no gestor (marque "Modo Sandbox")
3. Gere um PIX de teste
4. Use o simulador do Asaas para aprovar o pagamento

### 2. Verificar Logs

Os logs do webhook ficam em:
```
logs/asaas-webhook.log
```

Você pode acompanhar em tempo real:
```bash
tail -f logs/asaas-webhook.log
```

---

## 📋 Fluxo de Pagamento

### Pagamento de Fatura

1. Cliente gera PIX via gestor
2. Sistema cria cobrança no Asaas
3. Cliente paga o PIX
4. Asaas envia webhook para: `/webhook-asaas.php`
5. Sistema processa:
   - ✅ Marca fatura como paga
   - ✅ Renova cliente no gestor (+30 dias)
   - ✅ Sincroniza com Sigma (se ativo)
   - ✅ Envia mensagem WhatsApp

### Renovação de Revendedor

1. Revendedor escolhe plano
2. Sistema gera PIX via Asaas
3. Revendedor paga
4. Asaas envia webhook
5. Sistema renova plano do revendedor

---

## 🔍 Diferenças entre Provedores

| Recurso | Mercado Pago | Asaas | EFI Bank |
|---------|--------------|-------|----------|
| PIX | ✅ | ✅ | ✅ |
| Webhook | ✅ | ✅ | ✅ |
| Sandbox | ✅ | ✅ | ✅ |
| Renovação Auto | ✅ | ✅ | ✅ |
| Sync Sigma | ✅ | ✅ | ✅ |
| WhatsApp | ✅ | ✅ | ✅ |

---

## 🎯 Prioridade de Uso

Quando múltiplos métodos estão ativos, a ordem de prioridade é:

1. **Asaas** (primeiro)
2. **EFI Bank** (segundo)
3. **Mercado Pago** (terceiro)

---

## 📝 Notas Importantes

### API Key

- **Produção**: Começa com `$aact_`
- **Sandbox**: Formato UUID simples

### External Reference

O sistema usa o campo `external_reference` para identificar o tipo de pagamento:

- `INVOICE_{id}` - Pagamento de fatura
- `RENEW_USER_{id}_PLAN_{id}` - Renovação de revendedor

### Webhook URL

⚠️ **Importante**: A URL do webhook deve ser **pública** e **acessível pela internet**.

Não funciona com:
- `localhost`
- `127.0.0.1`
- IPs privados

---

## 🐛 Troubleshooting

### Webhook não está sendo chamado

1. Verifique se a URL está correta no painel do Asaas
2. Confirme que a URL é pública (não localhost)
3. Verifique os logs: `logs/asaas-webhook.log`
4. Teste manualmente: `curl -X POST https://seu-dominio.com/webhook-asaas.php`

### Pagamento não renova cliente

1. Verifique se o webhook foi recebido (logs)
2. Confirme que o `external_reference` está correto
3. Verifique se a fatura existe no banco
4. Confira os logs de erro do PHP

### Erro de autenticação

1. Verifique se a API Key está correta
2. Confirme o ambiente (sandbox vs produção)
3. Teste a conexão em "Métodos de Pagamento"

---

## ✅ Checklist de Configuração

- [ ] API Key configurada no gestor
- [ ] Webhook configurado no painel Asaas
- [ ] URL do webhook é pública
- [ ] Eventos selecionados no webhook
- [ ] Teste realizado com sucesso
- [ ] Logs sendo gerados corretamente

---

## 📞 Suporte

Em caso de dúvidas:

1. Verifique os logs: `logs/asaas-webhook.log`
2. Consulte a documentação: https://docs.asaas.com/
3. Entre em contato com o suporte do Asaas

---

**Última atualização:** 27/11/2025
