# 🚀 Comandos Rápidos - UltraGestor

## Iniciar o Projeto

### Windows (Desenvolvimento)

#### Terminal 1 - Servidor PHP
```cmd
# Na raiz do projeto
php -S localhost:8000 -t public
```

#### Terminal 2 - API WhatsApp
```cmd
# Na pasta whatsapp-api
cd whatsapp-api
npm start
```

### Linux/VPS (Produção)

```bash
# Instalar PM2 (apenas uma vez)
npm install -g pm2

# Iniciar API WhatsApp
cd whatsapp-api
pm2 start server.js --name whatsapp-api
pm2 save
pm2 startup

# Configurar Apache/Nginx para servir o PHP
# Ver DEPLOY-PRODUCTION.md
```

## Gerenciar API WhatsApp

### Ver Status
```bash
pm2 status
pm2 logs whatsapp-api
```

### Reiniciar
```bash
pm2 restart whatsapp-api
```

### Parar
```bash
pm2 stop whatsapp-api
```

### Ver Logs em Tempo Real
```bash
pm2 logs whatsapp-api --lines 50
```

## Solução de Problemas

### Erro ao Reconectar WhatsApp (Windows)

Se após desconectar você não conseguir reconectar:

#### Opção 1: Limpar Sessões (API Rodando)
```cmd
cd whatsapp-api
npm run clean
```

#### Opção 2: Limpar Tudo (API Parada)
```cmd
# Parar a API primeiro
# Ctrl+C no terminal ou pm2 stop whatsapp-api

cd whatsapp-api

# Limpar sessões
rmdir /s /q sessions
rmdir /s /q .wwebjs_cache
rmdir /s /q .wwebjs_auth

# Reiniciar
npm start
```

### Porta 3000 Ocupada

```cmd
# Windows - Ver o que está usando a porta
netstat -ano | findstr :3000

# Matar processo (substitua PID pelo número encontrado)
taskkill /PID <PID> /F

# Linux/Mac
lsof -ti:3000 | xargs kill -9
```

### Porta 8000 Ocupada (PHP)

```cmd
# Usar outra porta
php -S localhost:8080 -t public

# Atualizar .env
APP_URL=http://localhost:8080
```

## Acessar o Sistema

- **Aplicação:** http://localhost:8000
- **API WhatsApp Health:** http://localhost:3000/health
- **Login Padrão:** admin@ultragestor.com / admin123

## Banco de Dados

### Importar Schema
```bash
mysql -u root -p < database/schema.sql
```

### Backup
```bash
mysqldump -u root -p ultragestor_php > backup.sql
```

### Restaurar
```bash
mysql -u root -p ultragestor_php < backup.sql
```

## Desenvolvimento

### Instalar Dependências
```bash
# API WhatsApp
cd whatsapp-api
npm install

# Se precisar do Composer (opcional)
composer install
```

### Atualizar Dependências
```bash
cd whatsapp-api
npm update
```

## Monitoramento

### Ver Todas as Instâncias PM2
```bash
pm2 list
```

### Ver Uso de Recursos
```bash
pm2 monit
```

### Salvar Configuração PM2
```bash
pm2 save
```

### Configurar Inicialização Automática
```bash
pm2 startup
# Copiar e executar o comando que aparecer
```

## Logs

### Logs da API
```bash
pm2 logs whatsapp-api
pm2 logs whatsapp-api --err  # Apenas erros
```

### Limpar Logs
```bash
pm2 flush
```

## Dicas

### Desenvolvimento Rápido
```cmd
# Terminal 1
php -S localhost:8000 -t public

# Terminal 2
cd whatsapp-api && npm start
```

### Produção com PM2
```bash
cd whatsapp-api
pm2 start server.js --name whatsapp-api --watch
pm2 save
```

### Verificar Saúde da API
```bash
curl http://localhost:3000/health
```

Resposta esperada:
```json
{
  "success": true,
  "status": "online",
  "instances": {
    "total": 0,
    "connected": 0
  }
}
```
