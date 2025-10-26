#!/bin/bash

# 🚀 Script de Deploy Automático - UltraGestor
# Execute: bash scripts/deploy-production.sh

set -e  # Parar em caso de erro

echo "🚀 Iniciando deploy do UltraGestor para produção..."
echo "=================================================="

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
DOMAIN="seudominio.com"
DB_NAME="ultragestor_prod"
DB_USER="gestor_user"
DB_PASS="senha_super_segura_123"
DEPLOY_PATH="/var/www/html/gestor"
BACKUP_PATH="/var/backups/gestor"

log_info "Configurações:"
echo "  Domínio: $DOMAIN"
echo "  Banco: $DB_NAME"
echo "  Usuário DB: $DB_USER"
echo "  Path: $DEPLOY_PATH"
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

# 2. Instalar dependências
log_info "Instalando dependências..."
sudo apt install -y apache2 php8.1 php8.1-mysql php8.1-curl php8.1-json php8.1-mbstring php8.1-xml mysql-server nodejs npm git curl unzip
log_success "Dependências instaladas"

# 3. Configurar MySQL
log_info "Configurando MySQL..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
log_success "MySQL configurado"

# 4. Criar diretórios
log_info "Criando diretórios..."
sudo mkdir -p $DEPLOY_PATH
sudo mkdir -p $BACKUP_PATH
sudo mkdir -p /var/log/gestor
log_success "Diretórios criados"

# 5. Copiar arquivos (assumindo que estamos no diretório do projeto)
log_info "Copiando arquivos..."
sudo cp -r ./* $DEPLOY_PATH/
sudo chown -R www-data:www-data $DEPLOY_PATH
sudo chmod -R 755 $DEPLOY_PATH
sudo chmod -R 777 $DEPLOY_PATH/logs
sudo chmod -R 777 $DEPLOY_PATH/uploads
log_success "Arquivos copiados e permissões configuradas"

# 6. Configurar .env
log_info "Configurando .env..."
sudo tee $DEPLOY_PATH/.env > /dev/null <<EOF
# Banco de Dados
DB_HOST=localhost
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS

# WhatsApp
WHATSAPP_SESSION_PATH=$DEPLOY_PATH/whatsapp-sessions
WHATSAPP_WEBHOOK_URL=https://$DOMAIN/api-whatsapp-webhook.php

# URLs
BASE_URL=https://$DOMAIN
API_BASE_URL=https://$DOMAIN

# Segurança
JWT_SECRET=$(openssl rand -base64 32)
ENCRYPTION_KEY=$(openssl rand -base64 32)

# Email (configure conforme necessário)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
EOF
sudo chown www-data:www-data $DEPLOY_PATH/.env
log_success ".env configurado"

# 7. Importar banco de dados
log_info "Importando estrutura do banco..."
if [ -f "database/schema.sql" ]; then
    mysql -u $DB_USER -p$DB_PASS $DB_NAME < database/schema.sql
    log_success "Schema importado"
fi

if [ -f "database/initial-data.sql" ]; then
    mysql -u $DB_USER -p$DB_PASS $DB_NAME < database/initial-data.sql
    log_success "Dados iniciais importados"
fi

# 8. Configurar Apache
log_info "Configurando Apache..."
sudo tee /etc/apache2/sites-available/gestor.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $DEPLOY_PATH/public
    
    <Directory $DEPLOY_PATH/public>
        AllowOverride All
        Require all granted
        
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ /index.php [QSA,L]
    </Directory>
    
    ErrorLog /var/log/gestor/error.log
    CustomLog /var/log/gestor/access.log combined
</VirtualHost>
EOF

sudo a2ensite gestor.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
log_success "Apache configurado"

# 9. Instalar dependências Node.js
log_info "Instalando dependências Node.js..."
cd $DEPLOY_PATH
sudo -u www-data npm install whatsapp-web.js qrcode-terminal
log_success "Dependências Node.js instaladas"

# 10. Configurar serviço WhatsApp
log_info "Configurando serviço WhatsApp..."
sudo tee /etc/systemd/system/whatsapp-gestor.service > /dev/null <<EOF
[Unit]
Description=WhatsApp Gestor Automation Service
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$DEPLOY_PATH
ExecStart=/usr/bin/php $DEPLOY_PATH/scripts/whatsapp-automation-service.php
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable whatsapp-gestor
log_success "Serviço WhatsApp configurado"

# 11. Configurar Cron
log_info "Configurando Cron jobs..."
(sudo crontab -l 2>/dev/null; echo "*/5 * * * * /usr/bin/php $DEPLOY_PATH/scripts/whatsapp-automation-cron.php >> $DEPLOY_PATH/logs/cron.log 2>&1") | sudo crontab -
(sudo crontab -l 2>/dev/null; echo "0 2 * * 0 find $DEPLOY_PATH/logs -name '*.log' -mtime +7 -delete") | sudo crontab -
(sudo crontab -l 2>/dev/null; echo "0 3 * * * mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_PATH/gestor-\$(date +%Y%m%d).sql") | sudo crontab -
log_success "Cron jobs configurados"

# 12. Configurar Firewall
log_info "Configurando Firewall..."
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
log_success "Firewall configurado"

# 13. Instalar SSL (Let's Encrypt)
log_info "Instalando SSL..."
sudo apt install -y certbot python3-certbot-apache
log_warning "Execute manualmente: sudo certbot --apache -d $DOMAIN -d www.$DOMAIN"

# 14. Proteger arquivos sensíveis
log_info "Protegendo arquivos sensíveis..."
echo "deny from all" | sudo tee $DEPLOY_PATH/.htaccess > /dev/null
echo "deny from all" | sudo tee $DEPLOY_PATH/logs/.htaccess > /dev/null
echo "deny from all" | sudo tee $DEPLOY_PATH/scripts/.htaccess > /dev/null
log_success "Arquivos protegidos"

# 15. Teste final
log_info "Executando testes..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200\|301\|302"; then
    log_success "Servidor web respondendo"
else
    log_warning "Servidor web pode não estar respondendo corretamente"
fi

if php $DEPLOY_PATH/scripts/whatsapp-service-control.php status > /dev/null 2>&1; then
    log_success "Scripts PHP funcionando"
else
    log_warning "Verifique os scripts PHP"
fi

echo ""
echo "🎉 Deploy concluído com sucesso!"
echo "================================"
echo ""
log_info "Próximos passos:"
echo "1. Configure o SSL: sudo certbot --apache -d $DOMAIN -d www.$DOMAIN"
echo "2. Inicie o serviço WhatsApp: sudo systemctl start whatsapp-gestor"
echo "3. Acesse: http://$DOMAIN"
echo "4. Faça login e configure o WhatsApp"
echo ""
log_info "Comandos úteis:"
echo "- Ver logs: sudo journalctl -u whatsapp-gestor -f"
echo "- Status: php $DEPLOY_PATH/scripts/whatsapp-service-control.php status"
echo "- Testar: php $DEPLOY_PATH/scripts/whatsapp-service-control.php run"
echo ""
log_success "Deploy finalizado! 🚀"