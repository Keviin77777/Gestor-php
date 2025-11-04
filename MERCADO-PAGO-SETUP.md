# 🔵 Configuração do Mercado Pago

Guia completo para configurar e usar a integração com Mercado Pago no UltraGestor.

## 📋 Índice

1. [Instalação](#instalação)
2. [Configuração](#configuração)
3. [Como Usar](#como-usar)
4. [API Reference](#api-reference)
5. [Exemplos](#exemplos)

---

## 🚀 Instalação

### 1. Criar Tabela no Banco de Dados

Execute o script de instalação:

```bash
php database/install-payment-methods-table.php
```

Ou execute manualmente o SQL:

```bash
mysql -u root -p ultragestor_php < database/create-payment-methods-table.sql
```

### 2. Verificar Instalação

Acesse o painel admin e vá em **Métodos de Pagamento** para verificar se a página carrega corretamente.

---

## ⚙️ Configuração

### 1. Obter Credenciais do Mercado Pago

1. Acesse: https://www.mercadopago.com.br/developers/panel/app
2. Crie uma nova aplicação ou selecione uma existente
3. Copie as credenciais:
   - **Public Key** (começa com `APP_USR-`)
   - **Access Token** (começa com `APP_USR-`)

### 2. Configurar no Sistema

1. Acesse: **Admin → Métodos de Pagamento**
2. Preencha os campos **na ordem correta**:
   - **Public Key** (primeiro campo)
   - **Access Token** (segundo campo)
3. Clique em **"Testar Conexão"** para validar
4. Marque **"Ativar Mercado Pago"**
5. Clique em **"Salvar Configurações"**

### 3. Credenciais de Teste vs Produção

**⚠️ IMPORTANTE:**

- **Teste**: Use para desenvolvimento (não processa pagamentos reais)
- **Produção**: Use para receber pagamentos reais

---

## 💻 Como Usar

### Criar Pagamento PIX

```php
<?php
require_once __DIR__ . '/app/helpers/MercadoPagoHelper.php';

$mp = new MercadoPagoHelper();

// Verificar se está ativo
if (!$mp->isEnabled()) {
    die('Mercado Pago não configurado');
}

// Criar pagamento
$result = $mp->createPixPayment([
    'amount' => 100.00,
    'description' => 'Pagamento de Fatura #123',
    'payer_email' => 'cliente@email.com',
    'payer_name' => 'João Silva',
    'payer_doc_type' => 'CPF',
    'payer_doc_number' => '12345678900',
    'external_reference' => 'INVOICE_123'
]);

if ($result['success']) {
    echo "QR Code: " . $result['qr_code'];
    echo "Payment ID: " . $result['payment_id'];
} else {
    echo "Erro: " . $result['error'];
}
```

### Consultar Status do Pagamento

```php
<?php
$mp = new MercadoPagoHelper();

$status = $mp->getPaymentStatus('1234567890');

if ($status['success']) {
    echo "Status: " . $status['status']; // approved, pending, rejected
    echo "Valor: R$ " . $status['amount'];
}
```

### Processar Webhook

```php
<?php
$mp = new MercadoPagoHelper();

// Receber dados do webhook
$data = json_decode(file_get_contents('php://input'), true);

$result = $mp->processWebhook($data);

if ($result['success'] && $result['status'] === 'approved') {
    // Pagamento aprovado - atualizar fatura
    echo "Pagamento aprovado!";
}
```

---

## 🔌 API Reference

### Endpoints Disponíveis

#### 1. Obter Configuração

```http
GET /api-payment-methods.php?method=mercadopago
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "config": {
    "public_key": "APP_USR-xxx",
    "access_token": "APP_USR-xxx",
    "enabled": true,
    "updated_at": "2024-01-01 12:00:00"
  }
}
```

#### 2. Salvar Configuração

```http
POST /api-payment-methods.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "method": "mercadopago",
  "config": {
    "public_key": "APP_USR-xxx",
    "access_token": "APP_USR-xxx",
    "enabled": true
  }
}
```

#### 3. Testar Conexão

```http
POST /api-payment-methods.php?action=test
Authorization: Bearer {token}
Content-Type: application/json

{
  "method": "mercadopago",
  "public_key": "APP_USR-xxx",
  "access_token": "APP_USR-xxx"
}
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Conexão testada com sucesso",
  "account_info": {
    "id": 123456789,
    "email": "conta@mercadopago.com",
    "nickname": "MINHACONTA",
    "country": "MLB"
  }
}
```

---

## 📝 Exemplos

### Exemplo 1: Gerar PIX para Fatura

```php
<?php
// Arquivo: public/api-generate-pix.php

require_once __DIR__ . '/../app/helpers/MercadoPagoHelper.php';

$invoiceId = $_GET['invoice_id'] ?? null;

if (!$invoiceId) {
    Response::json(['error' => 'Invoice ID obrigatório'], 400);
}

// Buscar dados da fatura
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    Response::json(['error' => 'Fatura não encontrada'], 404);
}

// Criar pagamento PIX
$mp = new MercadoPagoHelper();

$result = $mp->createPixPayment([
    'amount' => $invoice['amount'],
    'description' => "Fatura #{$invoice['id']} - {$invoice['description']}",
    'payer_email' => $invoice['client_email'],
    'payer_name' => $invoice['client_name'],
    'external_reference' => "INVOICE_{$invoice['id']}"
]);

if ($result['success']) {
    // Salvar payment_id na fatura
    $stmt = $db->prepare("
        UPDATE invoices 
        SET payment_id = ?, payment_qr_code = ? 
        WHERE id = ?
    ");
    $stmt->execute([
        $result['payment_id'],
        $result['qr_code'],
        $invoiceId
    ]);
    
    Response::json([
        'success' => true,
        'qr_code' => $result['qr_code'],
        'qr_code_base64' => $result['qr_code_base64'],
        'payment_id' => $result['payment_id']
    ]);
} else {
    Response::json(['error' => $result['error']], 400);
}
```

### Exemplo 2: Webhook para Atualizar Status

```php
<?php
// Arquivo: public/webhook-mercadopago.php

require_once __DIR__ . '/../app/helpers/MercadoPagoHelper.php';

$data = json_decode(file_get_contents('php://input'), true);

$mp = new MercadoPagoHelper();
$result = $mp->processWebhook($data);

if ($result['success']) {
    $paymentId = $result['payment_id'];
    $status = $result['status'];
    $externalRef = $result['external_reference'];
    
    // Extrair ID da fatura
    if (preg_match('/INVOICE_(\d+)/', $externalRef, $matches)) {
        $invoiceId = $matches[1];
        
        $db = Database::getInstance()->getConnection();
        
        if ($status === 'approved') {
            // Marcar fatura como paga
            $stmt = $db->prepare("
                UPDATE invoices 
                SET status = 'paid', 
                    paid_at = NOW(),
                    payment_method = 'pix_mercadopago'
                WHERE id = ?
            ");
            $stmt->execute([$invoiceId]);
            
            error_log("Fatura #{$invoiceId} paga via Mercado Pago");
        }
    }
}

http_response_code(200);
echo json_encode(['success' => true]);
```

---

## 🔒 Segurança

### Boas Práticas

1. **Nunca exponha o Access Token no frontend**
2. **Use HTTPS em produção**
3. **Valide webhooks** (verifique a origem)
4. **Armazene credenciais criptografadas** (considere usar encryption)
5. **Configure URL de webhook** no painel do Mercado Pago

### Validar Webhook (Recomendado)

```php
<?php
// Validar assinatura do webhook
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';

// Implementar validação conforme documentação MP
```

---

## 🐛 Troubleshooting

### Erro: "Credenciais inválidas"

- Verifique se copiou as credenciais corretas
- Confirme se está usando credenciais de **Produção** (não Teste)
- Teste a conexão antes de salvar

### Erro: "Mercado Pago não está configurado"

- Verifique se marcou **"Ativar Mercado Pago"**
- Confirme que salvou as configurações
- Execute o script de instalação da tabela

### QR Code não aparece

- Verifique se o valor é maior que R$ 0,01
- Confirme que o email do pagador é válido
- Veja os logs de erro no servidor

---

## 📚 Documentação Oficial

- [Mercado Pago Developers](https://www.mercadopago.com.br/developers)
- [API Reference](https://www.mercadopago.com.br/developers/pt/reference)
- [PIX Documentation](https://www.mercadopago.com.br/developers/pt/docs/checkout-api/integration-configuration/integrate-with-pix)

---

## ✅ Checklist de Implementação

- [ ] Tabela `payment_methods` criada
- [ ] Credenciais do Mercado Pago obtidas
- [ ] Configuração salva e testada
- [ ] Mercado Pago ativado
- [ ] Teste de criação de PIX realizado
- [ ] Webhook configurado (opcional)
- [ ] Integração com faturas implementada

---

**🎉 Pronto! Seu sistema está integrado com Mercado Pago.**
