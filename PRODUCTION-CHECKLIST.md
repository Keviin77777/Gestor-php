# ✅ Checklist de Deploy para Produção

## 🔧 Pré-Deploy (Local)

- [ ] Todos os testes passando
- [ ] Código commitado no Git
- [ ] Arquivo .env.production configurado
- [ ] Backup do banco de desenvolvimento
- [ ] Documentação atualizada

## 🚀 Deploy Automático

### Opção 1: Script Automático
```bash
# Torna o script executável
chmod +x scripts/deploy-production.sh

# Executa o deploy
bash scripts/deploy-production.sh
```

### Opção 2: Deploy Manual
Siga o arquivo `DEPLOY-PRODUCTION.md`

## 🔍 Verificações Pós-Deploy

### 1. Servidor Web
- [ ] Apache/Nginx rodando: `sudo systemctl status apache2`
- [ ] Site acessível: `curl -I https://seudominio.com`
- [ ] SSL funcionando: `curl -I https://seudominio.com`
- [ ] Redirecionamento HTTP→HTTPS funcionando

### 2. Banco de Dados
- [ ] MySQL rodando: `sudo systemctl status mysql`
- [ ] Conexão funcionando: `mysql -u gestor_user -p ultragestor_prod`
- [ ] Tabelas criadas: `SHOW TABLES;`
- [ ] Dados iniciais importados

### 3. PHP e Aplicação
- [ ] PHP funcionando: `php -v`
- [ ] Extensões instaladas: `php -m | grep -E "(mysql|curl|json|mbstring)"`
- [ ] Permissões corretas: `ls -la /var/www/html/gestor`
- [ ] .env configurado: `cat /var/www/html/gestor/.env`

### 4. WhatsApp
- [ ] Node.js instalado: `node -v && npm -v`
- [ ] Dependências instaladas: `ls /var/www/html/gestor/node_modules`
- [ ] Serviço configurado: `sudo systemctl status whatsapp-gestor`
- [ ] Scripts funcionando: `php /var/www/html/gestor/scripts/whatsapp-service-control.php status`

### 5. Automação
- [ ] Cron configurado: `sudo crontab -l`
- [ ] Logs sendo gerados: `ls -la /var/www/html/gestor/logs/`
- [ ] Backup funcionando: `ls -la /var/backups/`

### 6. Segurança
- [ ] Firewall ativo: `sudo ufw status`
- [ ] Arquivos protegidos: `.htaccess` nos diretórios sensíveis
- [ ] Senhas seguras no .env
- [ ] SSL válido e funcionando

## 🧪 Testes Funcionais

### 1. Login e Autenticação
- [ ] Página de login carrega
- [ ] Login com credenciais corretas funciona
- [ ] Logout funciona
- [ ] Redirecionamento de páginas protegidas funciona

### 2. Funcionalidades Principais
- [ ] Dashboard carrega com dados
- [ ] CRUD de clientes funciona
- [ ] Geração de faturas funciona
- [ ] Upload de arquivos funciona

### 3. WhatsApp
- [ ] Página de pareamento carrega
- [ ] QR Code é gerado
- [ ] Templates são listados
- [ ] Agendamentos funcionam
- [ ] Envio manual de mensagens funciona

### 4. APIs
- [ ] `/api-clients.php` responde
- [ ] `/api-whatsapp-status.php` responde
- [ ] `/api-whatsapp-templates.php` responde
- [ ] Autenticação de API funciona

## 🔧 Configurações Finais

### 1. Monitoramento
```bash
# Configurar logrotate
sudo nano /etc/logrotate.d/gestor
```

```
/var/www/html/gestor/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    copytruncate
}
```

### 2. Backup Automático
```bash
# Script de backup completo
sudo nano /usr/local/bin/backup-gestor.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/gestor"
mkdir -p $BACKUP_DIR

# Backup do banco
mysqldump -u gestor_user -psenha ultragestor_prod > $BACKUP_DIR/db_$DATE.sql

# Backup dos arquivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/gestor

# Manter apenas 7 dias de backup
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

### 3. Monitoramento de Saúde
```bash
# Script de health check
sudo nano /usr/local/bin/health-check-gestor.sh
```

```bash
#!/bin/bash
# Verificar se os serviços estão rodando
systemctl is-active --quiet apache2 || echo "Apache DOWN"
systemctl is-active --quiet mysql || echo "MySQL DOWN"
systemctl is-active --quiet whatsapp-gestor || echo "WhatsApp Service DOWN"

# Verificar se o site responde
curl -f -s https://seudominio.com > /dev/null || echo "Website DOWN"

# Verificar espaço em disco
df -h | awk '$5 > 80 {print "Disk space warning: " $0}'
```

## 📊 Métricas e Logs

### Logs Importantes
```bash
# Logs do Apache
tail -f /var/log/apache2/error.log

# Logs da aplicação
tail -f /var/www/html/gestor/logs/app.log

# Logs do WhatsApp
sudo journalctl -u whatsapp-gestor -f

# Logs do sistema
tail -f /var/log/syslog
```

### Comandos de Monitoramento
```bash
# Status geral
php /var/www/html/gestor/scripts/whatsapp-service-control.php status

# Testar automação
php /var/www/html/gestor/scripts/whatsapp-service-control.php run

# Ver processos
ps aux | grep -E "(apache|mysql|node|php)"

# Ver conexões
netstat -tulpn | grep -E "(80|443|3306)"
```

## 🆘 Troubleshooting

### Problemas Comuns

**Site não carrega:**
- Verificar Apache: `sudo systemctl status apache2`
- Verificar logs: `sudo tail /var/log/apache2/error.log`
- Verificar permissões: `ls -la /var/www/html/gestor`

**Banco não conecta:**
- Verificar MySQL: `sudo systemctl status mysql`
- Testar conexão: `mysql -u gestor_user -p`
- Verificar .env: `cat /var/www/html/gestor/.env`

**WhatsApp não funciona:**
- Verificar serviço: `sudo systemctl status whatsapp-gestor`
- Ver logs: `sudo journalctl -u whatsapp-gestor -f`
- Verificar Node.js: `node -v`

## 🎉 Deploy Concluído!

Quando todos os itens estiverem ✅, seu UltraGestor estará rodando em produção!

### Informações Importantes:
- **URL:** https://seudominio.com
- **Admin:** Configure o primeiro usuário
- **Logs:** `/var/www/html/gestor/logs/`
- **Backups:** `/var/backups/gestor/`

### Próximos Passos:
1. Configurar monitoramento (Uptime Robot, etc.)
2. Configurar alertas por email
3. Treinar usuários
4. Documentar processos específicos da empresa