# 🚀 Início Rápido - Mercado Pago

## ✅ Configuração Completa

Tudo já está instalado e pronto! Siga estes passos:

### 1️⃣ Iniciar Servidor (escolha um)

**Opção A - Script automático:**
```bash
start-dev.bat
```

**Opção B - Manual:**
```bash
php -S localhost:8000 -t public
```

### 2️⃣ Acessar o Sistema

Abra no navegador:
```
http://localhost:8000
```

### 3️⃣ Fazer Login como Admin

Use suas credenciais de administrador.

### 4️⃣ Configurar Mercado Pago

1. Acesse: **http://localhost:8000/payment-methods**

2. Obtenha suas credenciais:
   - Vá em: https://www.mercadopago.com.br/developers/panel/app
   - Copie a **Public Key** (começa com APP_USR-)
   - Copie o **Access Token** (começa com APP_USR-)

3. Cole no formulário **NA ORDEM**:
   - **Public Key** (primeiro campo)
   - **Access Token** (segundo campo)

4. Clique em **"Testar Conexão"**
   - Deve mostrar: ✅ Conexão testada com sucesso!

5. Marque **"Ativar Mercado Pago"**

6. Clique em **"Salvar Configurações"**

### 5️⃣ Testar Criação de PIX

Você pode testar de duas formas:

**Teste via código PHP:**
```php
<?php
require_once 'app/helpers/MercadoPagoHelper.php';

$mp = new MercadoPagoHelper();

$result = $mp->createPixPayment([
    'amount' => 10.00,
    'description' => 'Teste de pagamento',
    'payer_email' => 'teste@email.com',
    'payer_name' => 'João Teste'
]);

print_r($result);
```

**Teste via API:**
```bash
# Criar arquivo test-pix.php na raiz
php test-pix.php
```

---

## 📋 Arquivos Criados

✅ **API Principal:**
- `public/api-payment-methods.php` - Gerenciar configurações
- `public/api-generate-pix.php` - Gerar PIX para faturas
- `public/webhook-mercadopago.php` - Receber notificações

✅ **Helper:**
- `app/helpers/MercadoPagoHelper.php` - Classe para usar MP

✅ **Interface:**
- `app/views/payment-methods/index.php` - Página admin
- `public/assets/js/payment-methods.js` - JavaScript
- `public/assets/css/payment-methods.css` - Estilos

✅ **Banco de Dados:**
- `database/create-payment-methods-table.sql` - Schema
- Tabela `payment_methods` já criada ✅

---

## 🎯 Próximos Passos

### Integrar com Faturas

Adicione botão "Gerar PIX" nas faturas:

```javascript
// No JavaScript de faturas
async function gerarPix(invoiceId) {
    const response = await fetch('/api-generate-pix.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({ invoice_id: invoiceId })
    });
    
    const result = await response.json();
    
    if (result.success) {
        // Mostrar QR Code
        mostrarQRCode(result.qr_code_base64);
    }
}
```

### Testar Webhook (Produção)

Para testar webhooks, você precisa de uma URL pública. Opções:

1. **Deploy na VPS** (recomendado)
2. **Usar ngrok** (ver DESENVOLVIMENTO-NGROK.md)

---

## ⚠️ Importante

### Desenvolvimento vs Produção

**Desenvolvimento (agora):**
- URL: `http://localhost:8000`
- Credenciais: **Teste** do Mercado Pago
- Webhooks: Não funcionam (precisa URL pública)

**Produção (VPS):**
- URL: `https://ultragestor.site`
- Credenciais: **Produção** do Mercado Pago
- Webhooks: Funcionam normalmente

### Credenciais de Teste

Use credenciais de **TESTE** para desenvolvimento:
- Não processam pagamentos reais
- Não cobram nada
- Perfeito para testar a integração

### Quando usar Produção

Só use credenciais de **PRODUÇÃO** quando:
- Estiver na VPS (https://ultragestor.site)
- Tudo testado e funcionando
- Pronto para receber pagamentos reais

---

## 🐛 Problemas Comuns

### "Mercado Pago não está configurado"

- Verifique se marcou "Ativar Mercado Pago"
- Confirme que salvou as configurações

### "Credenciais inválidas"

- Verifique se copiou as credenciais corretas
- Confirme que não tem espaços extras
- Teste a conexão antes de salvar

### "Acesso negado"

- Faça login como **admin**
- Apenas admin pode configurar pagamentos

---

## � Doclumentação Completa

Ver: `MERCADO-PAGO-SETUP.md`

---

**✅ Tudo pronto! Comece configurando em: http://localhost:8000/payment-methods**
