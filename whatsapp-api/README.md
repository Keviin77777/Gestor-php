# UltraGestor WhatsApp API

API própria para WhatsApp com multi-instâncias usando whatsapp-web.js

## Instalação

```bash
cd whatsapp-api
npm install
```

## Configuração

1. Copie o arquivo `.env.example` para `.env`:
```bash
cp .env.example .env
```

2. Configure as variáveis no `.env`:
```env
PORT=3000
API_KEY=sua-chave-secreta-aqui
DB_HOST=localhost
DB_NAME=ultragestor_php
DB_USER=root
DB_PASS=
```

## Executar

### Desenvolvimento
```bash
npm run dev
```

### Produção
```bash
npm start
```

## Endpoints

### Conectar Instância
```
POST /api/instance/connect
Body: { "reseller_id": "admin-001" }
```

### Obter QR Code
```
GET /api/instance/qrcode/:reseller_id
```

### Status da Instância
```
GET /api/instance/status/:reseller_id
```

### Desconectar
```
POST /api/instance/disconnect
Body: { "reseller_id": "admin-001" }
```

### Enviar Mensagem
```
POST /api/message/send
Body: {
  "reseller_id": "admin-001",
  "phone_number": "5511999999999",
  "message": "Olá!",
  "template_id": null,
  "client_id": null,
  "invoice_id": null
}
```

### Enviar em Massa
```
POST /api/message/send-bulk
Body: {
  "reseller_id": "admin-001",
  "messages": [
    { "phone_number": "5511999999999", "message": "Olá 1!" },
    { "phone_number": "5511888888888", "message": "Olá 2!" }
  ]
}
```

## Recursos

- ✅ Multi-instâncias (uma por reseller)
- ✅ QR Code automático
- ✅ Reconexão automática
- ✅ Fila de mensagens
- ✅ Envio em massa com delay
- ✅ Confirmação de leitura
- ✅ Integração com banco de dados
- ✅ API REST simples
