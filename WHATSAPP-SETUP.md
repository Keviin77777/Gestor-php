# 🚀 Configuração do WhatsApp com Evolution API

## 📋 Pré-requisitos

1. **Evolution API rodando** na porta `8081`
2. **API Key configurada** na Evolution API: `gestplay-whatsapp-2024`

## ⚙️ Configuração

### 1. Arquivo `.env`

Certifique-se de que o arquivo `.env` contém:

```env
# WhatsApp Evolution API
WHATSAPP_API_URL=http://localhost:8081
WHATSAPP_API_KEY=gestplay-whatsapp-2024
EVOLUTION_API_URL=http://localhost:8081
EVOLUTION_API_KEY=gestplay-whatsapp-2024
```

### 2. Inicializar Tabelas

Acesse no navegador:
```
http://localhost:8000/init-whatsapp-tables.php
```

### 3. Atualizar Configurações

Acesse no navegador:
```
http://localhost:8000/update-whatsapp-config.php
```

### 4. Testar Evolution API

Acesse no navegador:
```
http://localhost:8000/test-evolution-api.php
```

Deve retornar:
```json
{
  "api_running": true,
  "message": "Evolution API está rodando"
}
```

## 🔧 Verificação

### Testar Conexão com Banco
```
http://localhost:8000/test-whatsapp.php
```

### Logs do PHP
Os logs aparecem no terminal onde o servidor PHP está rodando:
```bash
php -S localhost:8000 -t public
```

## 📱 Usar o Sistema

1. Acesse: `http://localhost:8000/whatsapp/parear`
2. Clique em **"Conectar WhatsApp"**
3. Escaneie o QR Code com seu celular
4. Aguarde a confirmação de conexão

## ❌ Problemas Comuns

### Erro: "Missing global api key"
- **Solução**: Configure a API Key no `.env` e execute `update-whatsapp-config.php`

### Erro: "Evolution API não está acessível"
- **Solução**: Verifique se a Evolution API está rodando na porta 8081
- Teste: `curl http://localhost:8081/manager/fetchInstances`

### Erro: "Cannot set properties of null"
- **Solução**: Já corrigido! Recarregue a página

### Texto borrado no card
- **Solução**: Já corrigido no CSS! Recarregue a página

## 🎯 Endpoints da API

- **Conectar**: `POST /api-whatsapp-connect.php`
- **Status**: `GET /api-whatsapp-status.php`
- **QR Code**: `GET /api-whatsapp-qr.php`
- **Desconectar**: `POST /api-whatsapp-disconnect.php`

## 📊 Estrutura do Banco

Tabelas criadas:
- `whatsapp_sessions` - Sessões ativas
- `whatsapp_settings` - Configurações por reseller
- `whatsapp_templates` - Templates de mensagens
- `whatsapp_messages` - Log de mensagens enviadas

## 🔐 Segurança

A API Key é armazenada de forma segura:
- No arquivo `.env` (não versionado)
- No banco de dados criptografado
- Logs não expõem a chave completa

## 📞 Suporte

Se precisar de ajuda, verifique os logs do PHP e do navegador (Console).
