# 🚀 Deploy UltraGestor via XAMPP na VPS

## 📋 Vantagens do XAMPP

- ✅ Instalação mais simples e rápida
- ✅ Apache, MySQL e PHP já configurados
- ✅ Interface gráfica para gerenciamento
- ✅ Ideal para quem não tem experiência com Linux
- ✅ Backup e restore mais fáceis

## 🖥️ Pré-requisitos na VPS

### Sistema Operacional
- **Ubuntu 20.04+** ou **CentOS 8+**
- **Mínimo:** 2GB RAM, 20GB HD
- **Recomendado:** 4GB RAM, 50GB HD

### Acesso
- Acesso SSH à VPS
- Usuário com privilégios sudo

## 📦 Instalação do XAMPP

### 1. Conectar na VPS
```bash
ssh root@SEU_IP_VPS
# ou
ssh usuario@SEU_IP_VPS
```

### 2. Atualizar Sistema
```bash
sudo apt update && sudo apt upgrade -y
# Para CentOS: sudo yum update -y
```

### 3. Baixar XAMPP
```bash
# Ir para diretório temporário
cd /tmp

# Baixar XAMPP (versão mais recente)
wget https://www.apachefriends.org/xampp-files/8.2.12/xampp-linux-x64-8.2.12-0-installer.run

# Dar permissão de execução
chmod +x xampp-linux-x64-8.2.12-0-installer.run
```

### 4. Instalar XAMPP
```bash
# Instalar (modo silencioso)
sudo ./xampp-linux-x64-8.2.12-0-installer.run --mode unattended

# Ou instalar interativo
sudo ./xampp-linux-x64-8.2.12-0-installer.run
```

### 5. Configurar Inicialização Automática
```bash
# Criar serviço systemd
sudo tee /etc/systemd/system/xampp.service > /dev/null <<EOF
[Unit]
Description=XAMPP
After=network.target

[Service]
Type=forking
ExecStart=/opt/lampp/lampp start
ExecStop=/opt/lampp/lampp stop
ExecReload=/opt/lampp/lampp reload
User=root
Group=root

[Install]
WantedBy=multi-user.target
EOF

# Ativar serviço
sudo systemctl daemon-reload
sudo systemctl enable xampp
sudo systemctl start xampp
```

## 🔧 Configuração do XAMPP

### 1. Verificar Status
```bash
sudo /opt/lampp/lampp status
```

### 2. Configurar Acesso Externo
```bash
# Editar configuração do Apache
sudo nano /opt/lampp/etc/httpd.conf
```

Encontre e altere:
```apache
# Linha ~200
Listen 80
# Para permitir acesso externo, adicione:
Listen 0.0.0.0:80

# Linha ~230
ServerName localhost:80
# Altere para:
ServerName SEU_DOMINIO_OU_IP:80
```

### 3. Configurar MySQL para Acesso Externo
```bash
# Editar configuração do MySQL
sudo nano /opt/lampp/etc/my.cnf
```

Adicione/altere:
```ini
[mysqld]
bind-address = 0.0.0.0
port = 3306
```

### 4. Configurar Segurança
```bash
# Executar script de segurança do XAMPP
sudo /opt/lampp/lampp security
```

Defina senhas para:
- MySQL root
- phpMyAdmin
- ProFTPD (opcional)

### 5. Reiniciar XAMPP
```bash
sudo /opt/lampp/lampp restart
```

## 📁 Deploy do UltraGestor

### 1. Baixar Projeto do GitHub
```bash
# Ir para diretório web do XAMPP
cd /opt/lampp/htdocs

# Clonar projeto
sudo git clone https://github.com/Keviin77777/Gestor-php.git ultragestor

# Definir permissões
sudo chown -R daemon:daemon ultragestor
sudo chmod -R 755 ultragestor
sudo chmod -R 777 ultragestor/logs
sudo chmod -R 777 ultragestor/uploads
```

### 2. Configurar .env
```bash
cd ultragestor
sudo cp .env.production .env
sudo nano .env
```

Configure:
```env
# Banco de Dados XAMPP
DB_HOST=localhost
DB_NAME=ultragestor
DB_USER=root
DB_PASS=SUA_SENHA_MYSQL

# URLs (substitua pelo seu IP/domínio)
BASE_URL=http://SEU_IP_VPS/ultragestor
API_BASE_URL=http://SEU_IP_VPS/ultragestor

# WhatsApp
WHATSAPP_SESSION_PATH=/opt/lampp/htdocs/ultragestor/whatsapp-sessions
WHATSAPP_WEBHOOK_URL=http://SEU_IP_VPS/ultragestor/api-whatsapp-webhook.php

# Segurança
JWT_SECRET=$(openssl rand -base64 32)
ENCRYPTION_KEY=$(openssl rand -base64 32)
```

### 3. Configurar Banco de Dados

#### Via phpMyAdmin (Mais Fácil)
1. Acesse: `http://SEU_IP_VPS/phpmyadmin`
2. Login: `root` / `SUA_SENHA`
3. Criar banco: `ultragestor`
4. Importar: `database/schema.sql`

#### Via Linha de Comando
```bash
# Conectar ao MySQL
/opt/lampp/bin/mysql -u root -p

# Criar banco
CREATE DATABASE ultragestor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# Importar estrutura
/opt/lampp/bin/mysql -u root -p ultragestor < database/schema.sql
```

### 4. Instalar Node.js (para WhatsApp)
```bash
# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Instalar dependências do projeto
cd /opt/lampp/htdocs/ultragestor
sudo npm install whatsapp-web.js qrcode-terminal
```

### 5. Configurar Automação WhatsApp
```bash
# Criar serviço para automação
sudo tee /etc/systemd/system/ultragestor-whatsapp.service > /dev/null <<EOF
[Unit]
Description=UltraGestor WhatsApp Automation
After=network.target xampp.service

[Service]
Type=simple
User=daemon
Group=daemon
WorkingDirectory=/opt/lampp/htdocs/ultragestor
ExecStart=/opt/lampp/bin/php /opt/lampp/htdocs/ultragestor/scripts/whatsapp-automation-service.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Ativar serviço
sudo systemctl daemon-reload
sudo systemctl enable ultragestor-whatsapp
sudo systemctl start ultragestor-whatsapp
```

### 6. Configurar Cron Jobs
```bash
# Editar crontab
sudo crontab -e

# Adicionar linhas:
*/5 * * * * /opt/lampp/bin/php /opt/lampp/htdocs/ultragestor/scripts/whatsapp-automation-cron.php >> /opt/lampp/htdocs/ultragestor/logs/cron.log 2>&1
0 2 * * 0 find /opt/lampp/htdocs/ultragestor/logs -name "*.log" -mtime +7 -delete
0 3 * * * /opt/lampp/bin/mysqldump -u root -pSUA_SENHA ultragestor > /opt/lampp/htdocs/ultragestor/backups/backup-$(date +%Y%m%d).sql
```

## 🔒 Configuração de Segurança

### 1. Firewall
```bash
# Instalar UFW
sudo apt install ufw

# Configurar regras
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw allow 3306/tcp  # MySQL (opcional)
sudo ufw enable
```

### 2. SSL com Let's Encrypt
```bash
# Instalar Certbot
sudo apt install certbot

# Gerar certificado (substitua pelo seu domínio)
sudo certbot certonly --standalone -d seudominio.com

# Configurar Apache para SSL
sudo nano /opt/lampp/etc/httpd.conf
```

Adicione no final:
```apache
# SSL Configuration
LoadModule ssl_module modules/mod_ssl.so
Include etc/extra/httpd-ssl.conf

<VirtualHost *:443>
    ServerName seudominio.com
    DocumentRoot /opt/lampp/htdocs/ultragestor/public
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/seudominio.com/cert.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/seudominio.com/privkey.pem
    SSLCertificateChainFile /etc/letsencrypt/live/seudominio.com/chain.pem
    
    <Directory /opt/lampp/htdocs/ultragestor/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 3. Proteger Arquivos Sensíveis
```bash
# Criar .htaccess para proteger diretórios
echo "deny from all" | sudo tee /opt/lampp/htdocs/ultragestor/.htaccess
echo "deny from all" | sudo tee /opt/lampp/htdocs/ultragestor/logs/.htaccess
echo "deny from all" | sudo tee /opt/lampp/htdocs/ultragestor/scripts/.htaccess
```

## 🧪 Testes

### 1. Verificar Serviços
```bash
# Status do XAMPP
sudo /opt/lampp/lampp status

# Status do WhatsApp
sudo systemctl status ultragestor-whatsapp

# Testar site
curl -I http://SEU_IP_VPS/ultragestor
```

### 2. Testar Funcionalidades
1. **Acesse:** `http://SEU_IP_VPS/ultragestor`
2. **Login:** Crie primeiro usuário
3. **WhatsApp:** Vá em WhatsApp → Parear
4. **Templates:** Configure templates
5. **Agendamentos:** Configure automação

## 📊 Monitoramento

### 1. Logs Importantes
```bash
# Logs do Apache
tail -f /opt/lampp/logs/error_log

# Logs da aplicação
tail -f /opt/lampp/htdocs/ultragestor/logs/app.log

# Logs do WhatsApp
sudo journalctl -u ultragestor-whatsapp -f
```

### 2. Comandos Úteis
```bash
# Reiniciar XAMPP
sudo /opt/lampp/lampp restart

# Reiniciar só Apache
sudo /opt/lampp/lampp reloadapache

# Reiniciar só MySQL
sudo /opt/lampp/lampp reloadmysql

# Status completo
sudo /opt/lampp/lampp status

# Testar automação
/opt/lampp/bin/php /opt/lampp/htdocs/ultragestor/scripts/whatsapp-service-control.php run
```

## 🔧 Manutenção

### 1. Backup Automático
```bash
# Script de backup completo
sudo tee /usr/local/bin/backup-ultragestor.sh > /dev/null <<'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/lampp/htdocs/ultragestor/backups"
mkdir -p $BACKUP_DIR

# Backup do banco
/opt/lampp/bin/mysqldump -u root -pSUA_SENHA ultragestor > $BACKUP_DIR/db_$DATE.sql

# Backup dos arquivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /opt/lampp/htdocs/ultragestor --exclude=/opt/lampp/htdocs/ultragestor/backups

# Manter apenas 7 dias
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup concluído: $DATE"
EOF

sudo chmod +x /usr/local/bin/backup-ultragestor.sh

# Adicionar ao cron (diário às 3h)
echo "0 3 * * * /usr/local/bin/backup-ultragestor.sh" | sudo crontab -
```

### 2. Atualização do Sistema
```bash
# Atualizar código do GitHub
cd /opt/lampp/htdocs/ultragestor
sudo git pull origin main

# Reiniciar serviços
sudo systemctl restart ultragestor-whatsapp
sudo /opt/lampp/lampp restart
```

## 🆘 Troubleshooting

### Problemas Comuns

**XAMPP não inicia:**
```bash
sudo /opt/lampp/lampp stop
sudo killall -9 httpd mysql
sudo /opt/lampp/lampp start
```

**MySQL não conecta:**
```bash
# Verificar se está rodando
sudo /opt/lampp/lampp status

# Resetar senha do MySQL
sudo /opt/lampp/lampp security
```

**Site não carrega:**
```bash
# Verificar permissões
sudo chown -R daemon:daemon /opt/lampp/htdocs/ultragestor
sudo chmod -R 755 /opt/lampp/htdocs/ultragestor

# Verificar logs
tail -f /opt/lampp/logs/error_log
```

**WhatsApp não funciona:**
```bash
# Verificar Node.js
node -v && npm -v

# Verificar serviço
sudo systemctl status ultragestor-whatsapp

# Reinstalar dependências
cd /opt/lampp/htdocs/ultragestor
sudo npm install
```

## 🎉 Vantagens do XAMPP

✅ **Instalação Simples:** Um comando instala tudo
✅ **Interface Gráfica:** phpMyAdmin para gerenciar banco
✅ **Backup Fácil:** Copiar pasta htdocs
✅ **Portabilidade:** Funciona igual no Windows/Linux
✅ **Manutenção:** Comandos simples para reiniciar
✅ **Desenvolvimento:** Fácil para testar mudanças

## 📋 Checklist Final

- [ ] XAMPP instalado e rodando
- [ ] Projeto clonado do GitHub
- [ ] .env configurado
- [ ] Banco de dados criado e importado
- [ ] Node.js e dependências instaladas
- [ ] Serviço WhatsApp rodando
- [ ] Cron jobs configurados
- [ ] Firewall configurado
- [ ] SSL configurado (opcional)
- [ ] Backups funcionando
- [ ] Site acessível e funcionando

## 🚀 Acesso Final

Após completar todos os passos:

- **Site:** `http://SEU_IP_VPS/ultragestor`
- **phpMyAdmin:** `http://SEU_IP_VPS/phpmyadmin`
- **Logs:** `/opt/lampp/htdocs/ultragestor/logs/`
- **Backups:** `/opt/lampp/htdocs/ultragestor/backups/`

---

**🎊 Parabéns! Seu UltraGestor está rodando via XAMPP na VPS!**