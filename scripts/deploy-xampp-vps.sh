#!/bin/bash

# 🚀 Script de Deploy Automático - UltraGestor via XAMPP
# Execute: bash scripts/deploy-xampp-vps.sh

set -e  # Parar em caso de erro

echo "🚀 Iniciando deploy do UltraGestor via XAMPP..."
echo "=============================================="

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para log colorido
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Verificar se está rodando como root
if [[ $EUID -eq 0 ]]; then
   log_error "Este script não deve ser executado como root"
   exit 1
fi

# Configurações (edite conforme necessário)
VPS_IP="SEU_IP_VPS"
DOMAIN="seudominio.com"
DB_NAME="ultragestor"
DB_USER="root"
DB_PASS="senha_mysql_segura"
XAMPP_PATH="/opt/lampp"
PROJECT_PATH="$XAMPP_PATH/htdocs/ultragestor"

log_info "Configurações:"
echo "  IP da VPS: $VPS_IP"
echo "  Domínio: $DOMAIN"
echo "  Banco: $DB_NAME"
echo "  Path XAMPP: $XAMPP_PATH"
echo "  Path Projeto: $PROJECT_PATH"
echo ""

read -p "Continuar com essas configurações? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log_warning "Deploy cancelado pelo usuário"
    exit 1
fi

# 1. Atualizar sistema
log_info "Atualizando sistema..."
sudo apt update && sudo apt upgrade -y
log_success "Sistema atualizado"

# 2. Baixar e instalar XAMPP
log_info "Baixando XAMPP..."
cd /tmp
if [ ! -f "xampp-linux-x64-8.2.12-0-installer.run" ]; then
    wget https://www.apachefriends.org/xampp-files/8.2.12/xampp-linux-x64-8.2.12-0-installer.run
fi
chmod +x xampp-linux-x64-8.2.12-0-installer.run

log_info "Instalando XAMPP..."
sudo ./xampp-linux-x64-8.2.12-0-installer.run --mode unattended
log_success "XAMPP instalado"

# 3. Configurar serviço XAMPP
log_info "Configurando serviço XAMPP..."
sudo tee /etc/systemd/system/xampp.service > /dev/null <<EOF
[Unit]
Description=XAMPP
After=network.target

[Service]
Type=forking
ExecStart=$XAMPP_PATH/lampp start
ExecStop=$XAMPP_PATH/lampp stop
ExecReload=$XAMPP_PATH/lampp reload
User=root
Group=root

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable xampp
sudo systemctl start xampp
log_success "Serviço XAMPP configurado"

# 4. Configurar segurança do XAMPP
log_info "Configurando segurança do XAMPP..."
# Configurar senha do MySQL automaticamente
sudo $XAMPP_PATH/bin/mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '$DB_PASS';"
log_success "Segurança configurada"

# 5. Instalar Git e Node.js
log_info "Instalando dependências..."
sudo apt install -y git curl
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
log_success "Dependências instaladas"

# 6. Clonar projeto do GitHub
log_info "Clonando projeto do GitHub..."
cd $XAMPP_PATH/htdocs
if [ -d "ultragestor" ]; then
    sudo rm -rf ultragestor
fi
sudo git clone https://github.com/Keviin77777/Gestor-php.git ultragestor
sudo chown -R daemon:daemon ultragestor
sudo chmod -R 755 ultragestor
sudo chmod -R 777 ultragestor/logs
sudo chmod -R 777 ultragestor/uploads
log_success "Projeto clonado"

# 7. Configurar .env
log_info "Configurando .env..."
cd $PROJECT_PATH
sudo cp .env.production .env

# Gerar chaves seguras
JWT_SECRET=$(openssl rand -base64 32)
ENCRYPTION_KEY=$(openssl rand -base64 32)

sudo tee .env > /dev/null <<EOF
# Banco de Dados XAMPP
DB_HOST=localhost
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS

# URLs
BASE_URL=http://$VPS_IP/ultragestor
API_BASE_URL=http://$VPS_IP/ultragestor

# WhatsApp
WHATSAPP_SESSION_PATH=$PROJECT_PATH/whatsapp-sessions
WHATSAPP_WEBHOOK_URL=http://$VPS_IP/ultragestor/api-whatsapp-webhook.php

# Segurança
JWT_SECRET=$JWT_SECRET
ENCRYPTION_KEY=$ENCRYPTION_KEY

# Configurações
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/Sao_Paulo
EOF

sudo chown daemon:daemon .env
log_success ".env configurado"

# 8. Configurar banco de dados
log_info "Configurando banco de dados..."
$XAMPP_PATH/bin/mysql -u root -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [ -f "database/schema.sql" ]; then
    $XAMPP_PATH/bin/mysql -u root -p$DB_PASS $DB_NAME < database/schema.sql
    log_success "Schema importado"
fi

if [ -f "database/initial-data.sql" ]; then
    $XAMPP_PATH/bin/mysql -u root -p$DB_PASS $DB_NAME < database/initial-data.sql
    log_success "Dados iniciais importados"
fi

# 9. Instalar dependências Node.js
log_info "Instalando dependências Node.js..."
sudo -u daemon npm install whatsapp-web.js qrcode-terminal
log_success "Dependências Node.js instaladas"

# 10. Configurar serviço WhatsApp
log_info "Configurando serviço WhatsApp..."
sudo tee /etc/systemd/system/ultragestor-whatsapp.service > /dev/null <<EOF
[Unit]
Description=UltraGestor WhatsApp Automation
After=network.target xampp.service

[Service]
Type=simple
User=daemon
Group=daemon
WorkingDirectory=$PROJECT_PATH
ExecStart=$XAMPP_PATH/bin/php $PROJECT_PATH/scripts/whatsapp-automation-service.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable ultragestor-whatsapp
sudo systemctl start ultragestor-whatsapp
log_success "Serviço WhatsApp configurado"

# 11. Configurar Cron jobs
log_info "Configurando Cron jobs..."
(sudo crontab -l 2>/dev/null; echo "*/5 * * * * $XAMPP_PATH/bin/php $PROJECT_PATH/scripts/whatsapp-automation-cron.php >> $PROJECT_PATH/logs/cron.log 2>&1") | sudo crontab -
(sudo crontab -l 2>/dev/null; echo "0 2 * * 0 find $PROJECT_PATH/logs -name '*.log' -mtime +7 -delete") | sudo crontab -
(sudo crontab -l 2>/dev/null; echo "0 3 * * * $XAMPP_PATH/bin/mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $PROJECT_PATH/backups/backup-\$(date +%Y%m%d).sql") | sudo crontab -
log_success "Cron jobs configurados"

# 12. Configurar Firewall
log_info "Configurando Firewall..."
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
log_success "Firewall configurado"

# 13. Configurar Apache para acesso externo
log_info "Configurando Apache..."
sudo sed -i 's/Listen 80/Listen 0.0.0.0:80/' $XAMPP_PATH/etc/httpd.conf
sudo sed -i "s/ServerName localhost:80/ServerName $VPS_IP:80/" $XAMPP_PATH/etc/httpd.conf

# Adicionar configuração do projeto
sudo tee -a $XAMPP_PATH/etc/httpd.conf > /dev/null <<EOF

# UltraGestor Configuration
<Directory "$PROJECT_PATH/public">
    AllowOverride All
    Require all granted
    
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ /index.php [QSA,L]
</Directory>

Alias /ultragestor $PROJECT_PATH/public
EOF

log_success "Apache configurado"

# 14. Proteger arquivos sensíveis
log_info "Protegendo arquivos sensíveis..."
echo "deny from all" | sudo tee $PROJECT_PATH/.htaccess > /dev/null
echo "deny from all" | sudo tee $PROJECT_PATH/logs/.htaccess > /dev/null
echo "deny from all" | sudo tee $PROJECT_PATH/scripts/.htaccess > /dev/null
log_success "Arquivos protegidos"

# 15. Reiniciar XAMPP
log_info "Reiniciando XAMPP..."
sudo $XAMPP_PATH/lampp restart
log_success "XAMPP reiniciado"

# 16. Criar diretório de backups
log_info "Criando diretório de backups..."
sudo mkdir -p $PROJECT_PATH/backups
sudo chown daemon:daemon $PROJECT_PATH/backups
log_success "Diretório de backups criado"

# 17. Teste final
log_info "Executando testes..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost/ultragestor | grep -q "200\|301\|302"; then
    log_success "Servidor web respondendo"
else
    log_warning "Servidor web pode não estar respondendo corretamente"
fi

if sudo systemctl is-active --quiet ultragestor-whatsapp; then
    log_success "Serviço WhatsApp rodando"
else
    log_warning "Verifique o serviço WhatsApp"
fi

echo ""
echo "🎉 Deploy via XAMPP concluído com sucesso!"
echo "========================================"
echo ""
log_info "Informações de acesso:"
echo "- Site: http://$VPS_IP/ultragestor"
echo "- phpMyAdmin: http://$VPS_IP/phpmyadmin"
echo "- MySQL User: $DB_USER"
echo "- MySQL Pass: $DB_PASS"
echo ""
log_info "Próximos passos:"
echo "1. Acesse o site e faça o primeiro login"
echo "2. Configure o WhatsApp em: WhatsApp → Parear"
echo "3. Configure templates e agendamentos"
echo ""
log_info "Comandos úteis:"
echo "- Status XAMPP: sudo $XAMPP_PATH/lampp status"
echo "- Reiniciar XAMPP: sudo $XAMPP_PATH/lampp restart"
echo "- Logs WhatsApp: sudo journalctl -u ultragestor-whatsapp -f"
echo "- Testar automação: $XAMPP_PATH/bin/php $PROJECT_PATH/scripts/whatsapp-service-control.php run"
echo ""
log_success "Deploy finalizado! 🚀"