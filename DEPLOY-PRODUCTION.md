# 🚀 Deploy para Produção - UltraGestor

## 📋 Pré-requisitos na VPS

### 1. Servidor Web
```bash
# Apache ou Nginx
sudo apt update
sudo apt install apache2 php8.1 php8.1-mysql php8.1-curl php8.1-json php8.1-mbstring
```

### 2. MySQL/MariaDB
```bash
sudo apt install mysql-server
sudo mysql_secure_installation
```

### 3. Composer (se necessário)
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 4. Node.js (para WhatsApp)
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs
```

## 📁 Estrutura de Deploy

### 1. Upload dos Arquivos
```bash
# Via SCP/SFTP ou Git
scp -r ./projeto-gestor user@sua-vps:/var/www/html/gestor
# OU
git clone https://github.com/seu-repo/gestor.git /var/www/html/gestor
```

### 2. Permissões
```bash
sudo chown -R www-data:www-data /var/www/html/gestor
sudo chmod -R 755 /var/www/html/gestor
sudo chmod -R 777 /var/www/html/gestor/logs
sudo chmod -R 777 /var/www/html/gestor/uploads
```

## 🔧 Configuração

### 1. Arquivo .env
```bash
cd /var/www/html/gestor
cp .env.example .env
nano .env
```

```env
# Banco de Dados
DB_HOST=localhost
DB_NAME=ultragestor_prod
DB_USER=gestor_user
DB_PASS=senha_super_segura_123

# WhatsApp
WHATSAPP_SESSION_PATH=/var/www/html/gestor/whatsapp-sessions
WHATSAPP_WEBHOOK_URL=https://seudominio.com/api-whatsapp-webhook.php

# URLs
BASE_URL=https://seudominio.com
API_BASE_URL=https://seudominio.com

# Segurança
JWT_SECRET=chave_jwt_super_segura_production_2024
ENCRYPTION_KEY=chave_criptografia_32_caracteres_min

# Email (opcional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu-email@gmail.com
SMTP_PASS=sua-senha-app
```

### 2. Banco de Dados
```sql
-- Criar banco e usuário
CREATE DATABASE ultragestor_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gestor_user'@'localhost' IDENTIFIED BY 'senha_super_segura_123';
GRANT ALL PRIVILEGES ON ultragestor_prod.* TO 'gestor_user'@'localhost';
FLUSH PRIVILEGES;
```

```bash
# Importar estrutura
mysql -u gestor_user -p ultragestor_prod < database/schema.sql
mysql -u gestor_user -p ultragestor_prod < database/initial-data.sql
```

### 3. Apache Virtual Host
```bash
sudo nano /etc/apache2/sites-available/gestor.conf
```

```apache
<VirtualHost *:80>
    ServerName seudominio.com
    ServerAlias www.seudominio.com
    DocumentRoot /var/www/html/gestor/public
    
    <Directory /var/www/html/gestor/public>
        AllowOverride All
        Require all granted
        
        # Rewrite rules
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ /index.php [QSA,L]
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/gestor_error.log
    CustomLog ${APACHE_LOG_DIR}/gestor_access.log combined
</VirtualHost>
```

```bash
# Ativar site e módulos
sudo a2ensite gestor.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### 4. SSL com Let's Encrypt
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d seudominio.com -d www.seudominio.com
```

## 🤖 Automação WhatsApp

### 1. Instalar Dependências Node.js
```bash
cd /var/www/html/gestor
npm install whatsapp-web.js qrcode-terminal
```

### 2. Configurar Serviço Systemd
```bash
sudo nano /etc/systemd/system/whatsapp-gestor.service
```

```ini
[Unit]
Description=WhatsApp Gestor Automation Service
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/html/gestor
ExecStart=/usr/bin/php /var/www/html/gestor/scripts/whatsapp-automation-service.php
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

```bash
# Ativar serviço
sudo systemctl daemon-reload
sudo systemctl enable whatsapp-gestor
sudo systemctl start whatsapp-gestor
```

### 3. Cron para Automação
```bash
sudo crontab -e
```

```cron
# Automação WhatsApp a cada 5 minutos
*/5 * * * * /usr/bin/php /var/www/html/gestor/scripts/whatsapp-automation-cron.php >> /var/www/html/gestor/logs/cron.log 2>&1

# Limpeza de logs semanalmente
0 2 * * 0 find /var/www/html/gestor/logs -name "*.log" -mtime +7 -delete

# Backup diário do banco
0 3 * * * mysqldump -u gestor_user -psenha_super_segura_123 ultragestor_prod > /var/backups/gestor-$(date +\%Y\%m\%d).sql
```

## 🔒 Segurança

### 1. Firewall
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 2. Fail2Ban
```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 3. Proteção de Arquivos Sensíveis
```bash
# .htaccess na raiz
echo "deny from all" | sudo tee /var/www/html/gestor/.htaccess

# Proteger logs
echo "deny from all" | sudo tee /var/www/html/gestor/logs/.htaccess

# Proteger scripts
echo "deny from all" | sudo tee /var/www/html/gestor/scripts/.htaccess
```

## 📊 Monitoramento

### 1. Logs do Sistema
```bash
# Ver logs do WhatsApp
sudo journalctl -u whatsapp-gestor -f

# Ver logs do Apache
sudo tail -f /var/log/apache2/gestor_error.log

# Ver logs da aplicação
tail -f /var/www/html/gestor/logs/whatsapp-automation-service.log
```

### 2. Status dos Serviços
```bash
# Verificar serviços
sudo systemctl status whatsapp-gestor
sudo systemctl status apache2
sudo systemctl status mysql

# Verificar automação
php /var/www/html/gestor/scripts/whatsapp-service-control.php status
```

## 🚀 Deploy Checklist

- [ ] Servidor configurado (Apache/Nginx + PHP + MySQL)
- [ ] Arquivos enviados para VPS
- [ ] Permissões configuradas
- [ ] Arquivo .env configurado
- [ ] Banco de dados criado e importado
- [ ] Virtual Host configurado
- [ ] SSL configurado
- [ ] Node.js e dependências instaladas
- [ ] Serviço WhatsApp configurado
- [ ] Cron jobs configurados
- [ ] Firewall configurado
- [ ] Backup configurado
- [ ] Testes realizados

## 🧪 Testes Pós-Deploy

### 1. Teste Básico
```bash
curl -I https://seudominio.com
# Deve retornar 200 OK
```

### 2. Teste WhatsApp
```bash
# Verificar se o serviço está rodando
php /var/www/html/gestor/scripts/whatsapp-service-control.php status

# Testar automação
php /var/www/html/gestor/scripts/whatsapp-service-control.php run
```

### 3. Teste de Login
- Acesse https://seudominio.com
- Faça login com usuário admin
- Verifique todas as funcionalidades

## 📞 Suporte

Em caso de problemas:

1. Verificar logs: `sudo journalctl -u whatsapp-gestor -f`
2. Verificar permissões: `ls -la /var/www/html/gestor`
3. Verificar .env: `cat /var/www/html/gestor/.env`
4. Testar conexão DB: `mysql -u gestor_user -p ultragestor_prod`

---

**🎉 Parabéns! Seu UltraGestor está em produção!**