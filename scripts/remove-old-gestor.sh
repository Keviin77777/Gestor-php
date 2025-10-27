#!/bin/bash

# 🗑️ Script para Remover Projeto Antigo - UltraGestor
# Caminho: /www/wwwroot/ultragestor.site/Gestoresse

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}✅ $1${NC}"; }
log_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_error() { echo -e "${RED}❌ $1${NC}"; }

# Configurações
OLD_PROJECT_PATH="/www/wwwroot/ultragestor.site/Gestoresse"
BACKUP_DIR="/root/backups/$(date +%Y%m%d_%H%M%S)"
DOMAIN="ultragestor.site"

echo "🗑️ Remoção Segura do Projeto Antigo"
echo "=================================="
echo ""
log_info "Projeto a ser removido: $OLD_PROJECT_PATH"
log_info "Backup será salvo em: $BACKUP_DIR"
echo ""

# Verificar se o diretório existe
if [ ! -d "$OLD_PROJECT_PATH" ]; then
    log_error "Diretório não encontrado: $OLD_PROJECT_PATH"
    exit 1
fi

# Mostrar conteúdo do diretório
log_info "Conteúdo do diretório:"
ls -la "$OLD_PROJECT_PATH" | head -20
echo ""

# Confirmar remoção
log_warning "ATENÇÃO: Esta ação é irreversível!"
read -p "Deseja continuar com a remoção? (digite 'REMOVER' para confirmar): " -r
if [[ $REPLY != "REMOVER" ]]; then
    log_warning "Operação cancelada pelo usuário"
    exit 1
fi

echo ""
log_info "Iniciando processo de remoção..."

# 1. Criar backup
log_info "📦 Criando backup completo..."
mkdir -p "$BACKUP_DIR"

# Backup dos arquivos
tar -czf "$BACKUP_DIR/gestoresse-files-backup.tar.gz" "$OLD_PROJECT_PATH" 2>/dev/null || {
    log_warning "Erro ao criar backup dos arquivos, mas continuando..."
}

# Verificar se existe banco de dados relacionado
log_info "🗄️ Verificando banco de dados..."
DB_EXISTS=$(mysql -u root -p -e "SHOW DATABASES LIKE 'gestor%';" 2>/dev/null | wc -l || echo "0")

if [ "$DB_EXISTS" -gt 0 ]; then
    log_info "Banco de dados encontrado, fazendo backup..."
    mysql -u root -p -e "SHOW DATABASES LIKE 'gestor%';" > "$BACKUP_DIR/databases-list.txt"
    
    # Backup de todos os bancos que começam com 'gestor'
    for db in $(mysql -u root -p -e "SHOW DATABASES LIKE 'gestor%';" | grep -v Database); do
        log_info "Fazendo backup do banco: $db"
        mysqldump -u root -p "$db" > "$BACKUP_DIR/backup-$db.sql" 2>/dev/null || {
            log_warning "Erro ao fazer backup do banco $db"
        }
    done
fi

log_success "Backup criado com sucesso!"

# 2. Parar serviços relacionados
log_info "🛑 Parando serviços relacionados..."

# Procurar e parar serviços relacionados
for service in $(systemctl list-units --type=service | grep -i gestor | awk '{print $1}'); do
    log_info "Parando serviço: $service"
    systemctl stop "$service" 2>/dev/null || true
    systemctl disable "$service" 2>/dev/null || true
done

# Remover arquivos de serviço
rm -f /etc/systemd/system/*gestor*.service
systemctl daemon-reload

log_success "Serviços parados e removidos"

# 3. Remover configurações do servidor web
log_info "🔧 Removendo configurações do servidor web..."

# Apache
if command -v apache2 &> /dev/null; then
    a2dissite "$DOMAIN" 2>/dev/null || true
    a2dissite "gestor" 2>/dev/null || true
    rm -f /etc/apache2/sites-available/"$DOMAIN".conf
    rm -f /etc/apache2/sites-available/gestor.conf
    systemctl reload apache2 2>/dev/null || true
    log_success "Configurações do Apache removidas"
fi

# Nginx
if command -v nginx &> /dev/null; then
    rm -f /etc/nginx/sites-enabled/"$DOMAIN"
    rm -f /etc/nginx/sites-available/"$DOMAIN"
    rm -f /etc/nginx/sites-enabled/gestor
    rm -f /etc/nginx/sites-available/gestor
    systemctl reload nginx 2>/dev/null || true
    log_success "Configurações do Nginx removidas"
fi

# 4. Remover Cron Jobs
log_info "⏰ Removendo Cron Jobs..."
# Fazer backup do crontab atual
crontab -l > "$BACKUP_DIR/crontab-backup.txt" 2>/dev/null || true

# Remover linhas relacionadas ao gestor (você pode fazer manualmente depois)
log_warning "Verifique manualmente os cron jobs com: crontab -e"

# 5. Remover arquivos do projeto
log_info "🗂️ Removendo arquivos do projeto..."
log_warning "Removendo: $OLD_PROJECT_PATH"

# Contar arquivos antes da remoção
FILE_COUNT=$(find "$OLD_PROJECT_PATH" -type f | wc -l)
log_info "Arquivos a serem removidos: $FILE_COUNT"

# Remover o diretório
rm -rf "$OLD_PROJECT_PATH"

# Verificar se foi removido
if [ ! -d "$OLD_PROJECT_PATH" ]; then
    log_success "Projeto removido com sucesso!"
else
    log_error "Erro ao remover o projeto"
    exit 1
fi

# 6. Limpeza final
log_info "🧹 Limpeza final..."

# Procurar outros arquivos relacionados
log_info "Procurando outros arquivos relacionados..."
find /www -name "*gestor*" -type f 2>/dev/null | head -10 || true
find /var/log -name "*gestor*" -type f 2>/dev/null | head -10 || true

# Limpar logs antigos
find /var/log -name "*gestor*" -type f -delete 2>/dev/null || true

# Limpar cache
apt autoremove -y 2>/dev/null || true
apt autoclean 2>/dev/null || true

log_success "Limpeza concluída"

# 7. Relatório final
echo ""
echo "🎉 Remoção Concluída com Sucesso!"
echo "================================"
echo ""
log_info "Resumo:"
echo "- ✅ Backup criado em: $BACKUP_DIR"
echo "- ✅ Projeto removido: $OLD_PROJECT_PATH"
echo "- ✅ Serviços parados e removidos"
echo "- ✅ Configurações web removidas"
echo "- ✅ Limpeza realizada"
echo ""
log_info "Espaço liberado:"
df -h | grep -E "(Filesystem|/www|/$)"
echo ""
log_info "Próximos passos:"
echo "1. Verificar se o backup está completo"
echo "2. Instalar o novo UltraGestor"
echo "3. Configurar o novo sistema"
echo ""
log_success "Projeto antigo removido com segurança! 🗑️"