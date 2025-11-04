# Sistema de Checkout de Pagamento via PIX

Sistema completo para pagamento de faturas via PIX usando Mercado Pago.

## 📋 Funcionalidades

- ✅ Link de pagamento em templates de WhatsApp
- ✅ Página de checkout profissional
- ✅ Geração de PIX via Mercado Pago
- ✅ QR Code e código copia e cola
- ✅ Verificação automática de pagamento
- ✅ Atualização automática de fatura e renovação do cliente
- ✅ Totalmente responsivo (mobile-first)

## 🚀 Como Funciona

### 1. Variável `{payment_link}` nos Templates

A variável `{payment_link}` é automaticamente adicionada aos templates:
- **Fatura Gerada** (`invoice_generated`)
- **Vence Hoje** (`expires_today`)

Exemplo de uso no template:
```
Olá {cliente_nome}! 

Sua fatura no valor de R$ {cliente_valor} vence em {cliente_vencimento}.

Pague agora pelo link:
{payment_link}
```

### 2. Fluxo de Pagamento

1. **Cliente recebe WhatsApp** com link de pagamento
2. **Clica no link** → Abre página de checkout
3. **Visualiza detalhes** da fatura (cliente, valor, vencimento)
4. **Clica em "Pagar com PIX"** → Gera QR Code
5. **Escaneia QR Code** ou copia código
6. **Paga no banco** → Sistema verifica automaticamente
7. **Pagamento aprovado** → Fatura marcada como paga + Cliente renovado

### 3. Arquivos Criados

#### APIs
- `public/api-invoice-generate-pix.php` - Gera PIX para fatura
- Atualizado: `public/webhook-mercadopago.php` - Processa pagamentos

#### Views
- `public/checkout.php` - Página de checkout pública

#### Database
- `database/create-invoice-payments-table.sql` - Tabela de pagamentos

#### Helpers
- Atualizado: `app/helpers/whatsapp-automation.php` - Adiciona `{payment_link}`

## 📦 Instalação

### 1. Criar Tabela no Banco

```bash
mysql -u root -p ultragestor < database/create-invoice-payments-table.sql
```

Ou execute manualmente:

```sql
CREATE TABLE IF NOT EXISTS invoice_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    payment_id VARCHAR(255) NOT NULL UNIQUE,
    payment_method VARCHAR(50) DEFAULT 'pix',
    amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    qr_code TEXT,
    qr_code_base64 LONGTEXT,
    approved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_payment_id (payment_id),
    INDEX idx_status (status),
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Configurar Mercado Pago

O revendedor deve configurar suas credenciais em:
**Métodos de Pagamento** → Adicionar Mercado Pago

### 3. Usar nos Templates

Ao criar/editar templates de WhatsApp, use a variável:
```
{payment_link}
```

## 🎨 Design

### Página de Checkout
- Design moderno e profissional
- Cores do sistema (roxo/azul)
- Totalmente responsivo
- Animações suaves
- Ícones Font Awesome

### Modal PIX
- Mesmo estilo do modal de renovação
- QR Code grande e legível
- Botão de copiar código
- Instruções claras
- Verificação automática a cada 5 segundos

## 🔒 Segurança

- ✅ Validação de fatura existente
- ✅ Verificação de método de pagamento configurado
- ✅ Página pública (não requer login)
- ✅ IDs únicos de pagamento
- ✅ Webhook seguro do Mercado Pago
- ✅ Logs detalhados

## 📱 Responsividade

### Desktop
- Layout centralizado
- Card com largura máxima de 600px
- Espaçamento generoso

### Mobile
- Tela cheia
- Botões grandes (touch-friendly)
- Texto legível
- QR Code adaptável

## 🔄 Webhook

O webhook processa automaticamente:

1. **Pagamento Aprovado**
   - Marca fatura como paga
   - Renova acesso do cliente (+30 dias)
   - Ativa status do cliente

2. **Pagamento Rejeitado**
   - Registra tentativa
   - Mantém fatura pendente

3. **Logs Detalhados**
   - Todos os eventos são registrados
   - Arquivo: `logs/mercadopago-webhook.log`

## 🧪 Testes

### Testar Geração de Link

1. Crie uma fatura para um cliente
2. Acesse: `http://seu-dominio/checkout.php?invoice=ID_DA_FATURA`
3. Verifique se os dados aparecem corretamente

### Testar Pagamento

1. Configure Mercado Pago em modo sandbox
2. Gere PIX na página de checkout
3. Use credenciais de teste do Mercado Pago
4. Verifique se o webhook é chamado

### Testar Template

1. Configure um template com `{payment_link}`
2. Envie para um cliente de teste
3. Verifique se o link está correto no WhatsApp

## 📊 Monitoramento

### Logs
```bash
tail -f logs/mercadopago-webhook.log
```

### Verificar Pagamentos
```sql
SELECT * FROM invoice_payments ORDER BY created_at DESC LIMIT 10;
```

### Verificar Faturas Pagas
```sql
SELECT * FROM invoices WHERE status = 'paid' ORDER BY paid_at DESC LIMIT 10;
```

## 🐛 Troubleshooting

### Link não aparece no template
- Verifique se o template é do tipo `invoice_generated` ou `expires_today`
- Confirme que existe uma fatura pendente para o cliente
- Verifique logs do sistema

### PIX não é gerado
- Confirme configuração do Mercado Pago
- Verifique credenciais (Public Key e Access Token)
- Veja logs em `logs/mercadopago-webhook.log`

### Pagamento não atualiza
- Verifique se o webhook está configurado no Mercado Pago
- URL do webhook: `https://seu-dominio/webhook-mercadopago.php`
- Confirme que o servidor está acessível publicamente

### Cliente não é renovado
- Verifique se o webhook foi chamado
- Confirme que o pagamento foi aprovado
- Veja logs do webhook

## 🎯 Próximos Passos

- [ ] Adicionar notificação por email ao cliente
- [ ] Criar relatório de pagamentos via PIX
- [ ] Adicionar outros métodos de pagamento
- [ ] Implementar parcelamento
- [ ] Criar dashboard de conversão

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique os logs
2. Consulte a documentação do Mercado Pago
3. Entre em contato com o suporte técnico

---

**Desenvolvido para UltraGestor** 🚀
