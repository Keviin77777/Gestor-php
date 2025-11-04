# 🌐 Configurar ngrok para Desenvolvimento

O ngrok permite expor seu servidor local para a internet, essencial para testar webhooks do Mercado Pago.

## 📥 Instalação do ngrok

### Windows

1. **Baixar ngrok:**
   - Acesse: https://ngrok.com/download
   - Baixe a versão para Windows
   - Extraia o arquivo `ngrok.exe`

2. **Criar conta (grátis):**
   - Acesse: https://dashboard.ngrok.com/signup
   - Copie seu authtoken

3. **Configurar authtoken:**
   ```bash
   ngrok config add-authtoken SEU_TOKEN_AQUI
   ```

## 🚀 Usar ngrok

### 1. Iniciar servidor PHP local

```bash
php -S localhost:8000 -t public
```

### 2. Em outro terminal, iniciar ngrok

```bash
ngrok http 8000
```

### 3. Você verá algo assim:

```
ngrok

Session Status                online
Account                       seu@email.com
Version                       3.x.x
Region                        South America (sa)
Latency                       -
Web Interface                 http://127.0.0.1:4040
Forwarding                    https://abc123.ngrok-free.app -> http://localhost:8000

Connections                   ttl     opn     rt1     rt5     p50     p90
                              0       0       0.00    0.00    0.00    0.00
```

### 4. Copie a URL HTTPS

Exemplo: `https://abc123.ngrok-free.app`

### 5. Atualize o .env

```env
APP_URL=https://abc123.ngrok-free.app
MERCADOPAGO_WEBHOOK_URL=https://abc123.ngrok-free.app/webhook-mercadopago.php
```

## ⚙️ Configurar Webhook no Mercado Pago

1. Acesse: https://www.mercadopago.com.br/developers/panel/app
2. Selecione sua aplicação
3. Vá em **Webhooks**
4. Configure a URL: `https://abc123.ngrok-free.app/webhook-mercadopago.php`
5. Selecione eventos: **Pagamentos**

## 🔍 Monitorar Requisições

Acesse: http://127.0.0.1:4040

Você verá todas as requisições HTTP em tempo real, incluindo webhooks do Mercado Pago.

## 💡 Dicas

### Usar domínio customizado (Plano Pago)

Se você tem plano pago do ngrok, pode usar:

```bash
ngrok http 8000 --domain=ultragestor.ngrok.app
```

### Manter URL fixa entre reinicializações

Com plano pago, você pode reservar um domínio fixo.

### Alternativas gratuitas ao ngrok

- **LocalTunnel**: https://localtunnel.github.io/www/
  ```bash
  npm install -g localtunnel
  lt --port 8000 --subdomain ultragestor
  ```

- **Serveo**: https://serveo.net/
  ```bash
  ssh -R 80:localhost:8000 serveo.net
  ```

## 🐛 Troubleshooting

### Erro: "ngrok não é reconhecido"

Adicione o ngrok ao PATH do Windows ou execute direto:
```bash
C:\caminho\para\ngrok.exe http 8000
```

### Webhook não chega

1. Verifique se o ngrok está rodando
2. Teste a URL no navegador
3. Veja os logs em http://127.0.0.1:4040
4. Confirme a URL no painel do Mercado Pago

### Erro 502 Bad Gateway

Certifique-se que o servidor PHP está rodando na porta correta.

## 📋 Workflow Completo

```bash
# Terminal 1 - Servidor PHP
php -S localhost:8000 -t public

# Terminal 2 - ngrok
ngrok http 8000

# Terminal 3 - Logs do webhook (opcional)
tail -f logs/mercadopago-webhook.log
```

## ✅ Checklist

- [ ] ngrok instalado
- [ ] Authtoken configurado
- [ ] Servidor PHP rodando
- [ ] ngrok expondo porta 8000
- [ ] URL HTTPS copiada
- [ ] .env atualizado com URL do ngrok
- [ ] Webhook configurado no Mercado Pago
- [ ] Teste de pagamento realizado
- [ ] Webhook recebido e processado

---

**🎉 Pronto! Agora você pode testar webhooks do Mercado Pago localmente.**
