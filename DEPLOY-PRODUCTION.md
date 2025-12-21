# 🚀 Guia de Deploy em Produção - UltraGestor

## 📋 Pré-requisitos

- Servidor com PHP 8.0+ e MySQL 8.0+
- Node.js 18+ e npm
- Git instalado
- Acesso SSH ao servidor
- Domínio configurado

---

## 🔧 Configuração Inicial no Servidor

### 1. Clonar o Repositório

```bash
cd /var/www/
git clone https://github.com/SEU-USUARIO/ultragestor.git
cd ultragestor
```

### 2. Configurar Permissões

```bash
# Dar permissão ao Apache/Nginx
sudo chown -R www-data:www-data /var/www/ultragestor
sudo chmod -R 755 /var/www/ultragestor

# Criar pastas necessárias
mkdir -p whatsapp-api/sessions
mkdir -p logs
chmod 777 whatsapp-api/sessions
chmod 777 logs
```

---

## ⚙️ Configuração do Backend (PHP)

### 1. Criar arquivo .env

```bash
cp .env.example .env
nano .env
```

**Configurar variáveis:**

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ultragestor_php
DB_USER=seu_usuario
DB_PASS=sua_senha_segura

# JWT
JWT_SECRET=sua_chave_secreta_muito_forte_aqui_min_32_chars

# URLs
APP_URL=https://seudominio.com
FRONTEND_URL=https://seudominio.com

# WhatsApp API
WHATSAPP_API_URL=http://localhost:3000
EVOLUTION_API_URL=http://localhost:8081
EVOLUTION_API_KEY=sua_chave_evolution

# Mercado Pago
MERCADOPAGO_ACCESS_TOKEN=seu_token
MERCADOPAGO_PUBLIC_KEY=sua_public_key

# Asaas
ASAAS_API_KEY=seu_token_asaas
ASAAS_WALLET_ID=seu_wallet_id

# EFI Bank
EFIBANK_CLIENT_ID=seu_client_id
EFIBANK_CLIENT_SECRET=seu_client_secret
EFIBANK_CERTIFICATE_PATH=/caminho/certificado.pem

# Ciabra
CIABRA_API_URL=https://api.ciabra.com
CIABRA_API_KEY=sua_chave_ciabra
```

### 2. Importar Database

```bash
mysql -u root -p
CREATE DATABASE ultragestor_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

mysql -u root -p ultragestor_php < database/schema-production.sql
```

### 3. Configurar Apache/Nginx

**Apache (.htaccess já configurado):**

```apache
<VirtualHost *:80>
    ServerName seudominio.com
    DocumentRoot /var/www/ultragestor/public
    
    <Directory /var/www/ultragestor/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ultragestor-error.log
    CustomLog ${APACHE_LOG_DIR}/ultragestor-access.log combined
</VirtualHost>
```

**Nginx:**

```nginx
server {
    listen 80;
    server_name seudominio.com;
    root /var/www/ultragestor/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## ⚛️ Configuração do Frontend (React)

### 1. Configurar .env do Frontend

```bash
cd frontend
cp .env.example .env
nano .env
```

**Configurar:**

```env
VITE_API_URL=https://seudominio.com
VITE_APP_NAME=UltraGestor
```

### 2. Instalar Dependências e Build

```bash
npm install
npm run build
```

### 3. Copiar Build para Public

```bash
# Criar pasta para o React
mkdir -p ../public/app

# Copiar build
cp -r dist/* ../public/app/

# Voltar para raiz
cd ..
```

### 4. Configurar Roteamento

Editar `public/.htaccess` para servir o React:

```apache
# Adicionar antes das regras existentes
RewriteCond %{REQUEST_URI} ^/app
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^app/(.*)$ /app/index.html [L]
```

---

## 📱 Configuração do WhatsApp API

### 1. Instalar Dependências

```bash
cd whatsapp-api
npm install
```

### 2. Configurar .env

```bash
cp .env.example .env
nano .env
```

```env
PORT=3000
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ultragestor_php
DB_USER=seu_usuario
DB_PASS=sua_senha
SESSION_PATH=./sessions
```

### 3. Configurar PM2 (Process Manager)

```bash
# Instalar PM2 globalmente
sudo npm install -g pm2

# Iniciar WhatsApp API
pm2 start src/server.js --name whatsapp-api

# Salvar configuração
pm2 save

# Configurar para iniciar no boot
pm2 startup
```

---

## 🔒 Configuração SSL (HTTPS)

### Usando Certbot (Let's Encrypt)

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-apache

# Obter certificado
sudo certbot --apache -d seudominio.com

# Renovação automática já está configurada
```

---

## 🔄 Script de Deploy Automático

Criar arquivo `deploy.sh`:

```bash
#!/bin/bash

echo "🚀 Iniciando deploy..."

# Atualizar código
git pull origin main

# Backend - nada a fazer (PHP não precisa build)

# Frontend - Build React
echo "📦 Building frontend..."
cd frontend
npm install
npm run build
rm -rf ../public/app/*
cp -r dist/* ../public/app/
cd ..

# WhatsApp API - Reiniciar
echo "📱 Reiniciando WhatsApp API..."
cd whatsapp-api
npm install
pm2 restart whatsapp-api
cd ..

# Limpar cache
echo "🧹 Limpando cache..."
rm -rf cache/*
rm -rf logs/*.log

# Permissões
echo "🔐 Ajustando permissões..."
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
chmod 777 whatsapp-api/sessions
chmod 777 logs

echo "✅ Deploy concluído!"
```

Dar permissão de execução:

```bash
chmod +x deploy.sh
```

---

## 📊 Configurar Cron Jobs

```bash
crontab -e
```

Adicionar:

```cron
# Processar fila de mensagens WhatsApp (a cada minuto)
* * * * * php /var/www/ultragestor/scripts/process-queue.php >> /var/www/ultragestor/logs/queue.log 2>&1

# Automação de faturas (todo dia às 9h)
0 9 * * * php /var/www/ultragestor/scripts/invoice-automation-cron.php >> /var/www/ultragestor/logs/invoices.log 2>&1

# Renovação de revendedores (todo dia às 10h)
0 10 * * * php /var/www/ultragestor/scripts/reseller-renewal-automation.php >> /var/www/ultragestor/logs/resellers.log 2>&1

# Processar mensagens pendentes (a cada 5 minutos)
*/5 * * * * php /var/www/ultragestor/scripts/process-pending-messages.php >> /var/www/ultragestor/logs/pending.log 2>&1
```

---

## 🔍 Monitoramento

### Verificar Logs

```bash
# Logs do Apache/Nginx
tail -f /var/log/apache2/ultragestor-error.log

# Logs do WhatsApp API
pm2 logs whatsapp-api

# Logs da aplicação
tail -f logs/*.log
```

### Status dos Serviços

```bash
# WhatsApp API
pm2 status

# Apache
sudo systemctl status apache2

# MySQL
sudo systemctl status mysql
```

---

## 🔄 Atualizações Futuras

Para atualizar o sistema:

```bash
cd /var/www/ultragestor
./deploy.sh
```

---

## 🆘 Troubleshooting

### Erro de Permissão

```bash
sudo chown -R www-data:www-data /var/www/ultragestor
sudo chmod -R 755 /var/www/ultragestor
chmod 777 whatsapp-api/sessions
chmod 777 logs
```

### WhatsApp API não inicia

```bash
pm2 delete whatsapp-api
cd whatsapp-api
pm2 start src/server.js --name whatsapp-api
pm2 save
```

### Erro de Database

```bash
# Verificar conexão
mysql -u seu_usuario -p ultragestor_php

# Reimportar schema se necessário
mysql -u root -p ultragestor_php < database/schema-production.sql
```

### React não carrega

```bash
cd frontend
rm -rf node_modules dist
npm install
npm run build
cp -r dist/* ../public/app/
```

---

## 📝 Checklist Final

- [ ] .env configurado com dados de produção
- [ ] Database importado
- [ ] Frontend buildado e copiado
- [ ] WhatsApp API rodando com PM2
- [ ] SSL configurado
- [ ] Cron jobs configurados
- [ ] Permissões corretas
- [ ] Logs funcionando
- [ ] Backup configurado

---

## 🎉 Pronto!

Seu sistema está rodando em produção!

Acesse: `https://seudominio.com`
