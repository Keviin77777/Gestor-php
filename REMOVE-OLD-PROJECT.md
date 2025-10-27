# 🗑️ Remover Projeto Antigo - Guia Seguro

## 📋 Informações do Projeto Antigo
- **Caminho:** `/www/wwwroot/ultragestor.site/Gestoresse`
- **Domínio:** ultragestor.site

## ⚠️ IMPORTANTE - Fazer Backup Antes!

### 1. Conectar na VPS
```bash
ssh root@ultragestor.site
# ou
ssh usuario@ultragestor.site
```

### 2. Verificar o que existe no diretório
```bash
# Ver conteúdo do projeto antigo
ls -la /www/wwwroot/ultragestor.site/Gestoresse

# Ver tamanho do diretório
du -sh /www/wwwroot/ultragestor.site/Gestoresse
```

### 3. Fazer Backup Completo (OBRIGATÓRIO)
```bash
# Criar diretório de backup
mkdir -p /root/backups/$(date +%Y%m%d)

# Backup dos arquivos
tar -czf /root/backups/$(date +%Y%m%d)/gestoresse-backup-$(date +%Y%m%d_%H%M%S).tar.gz /www/wwwroot/ultragestor.site/Gestoresse

# Verificar se o backup foi criado
ls -lh /root/backups/$(date +%Y%m%d)/
```

### 4. Backup do Banco de Dados (se houver)
```bash
# Listar bancos de dados
mysql -u root -p -e "SHOW DATABASES;"

# Se houver banco relacionado ao projeto antigo, fazer backup
# Substitua 'nome_do_banco' pelo nome real
mysqldump -u root -p nome_do_banco > /root/backups/$(date +%Y%m%d)/gestoresse-db-backup-$(date +%Y%m%d).sql
```

## 🔍 Verificar Serviços Relacionados

### 1. Verificar processos rodando
```bash
# Ver processos PHP relacionados
ps aux | grep -i gestor

# Ver serviços systemd
systemctl list-units | grep -i gestor
```

### 2. Verificar configurações do servidor web

#### Para Apache:
```bash
# Ver sites habilitados
ls -la /etc/apache2/sites-enabled/

# Ver configuração específica do domínio
cat /etc/apache2/sites-available/ultragestor.site.conf
```

#### Para Nginx:
```bash
# Ver sites habilitados
ls -la /etc/nginx/sites-enabled/

# Ver configuração específica do domínio
cat /etc/nginx/sites-available/ultragestor.site
```

### 3. Verificar Cron Jobs
```bash
# Ver cron jobs do root
crontab -l

# Ver cron jobs de outros usuários
cat /etc/crontab
ls -la /etc/cron.d/
```

## 🛑 Parar Serviços Relacionados

### 1. Parar serviços customizados (se houver)
```bash
# Exemplo de comandos (ajuste conforme necessário)
systemctl stop gestor-whatsapp
systemctl stop gestor-automation
systemctl disable gestor-whatsapp
systemctl disable gestor-automation
```

### 2. Remover serviços do systemd (se houver)
```bash
# Listar serviços relacionados
systemctl list-unit-files | grep -i gestor

# Remover arquivos de serviço (exemplo)
rm -f /etc/systemd/system/gestor-*.service
systemctl daemon-reload
```

## 🗂️ Remover Configurações

### 1. Remover configuração do Apache/Nginx
```bash
# Para Apache
a2dissite ultragestor.site
rm -f /etc/apache2/sites-available/ultragestor.site.conf
systemctl reload apache2

# Para Nginx
rm -f /etc/nginx/sites-enabled/ultragestor.site
rm -f /etc/nginx/sites-available/ultragestor.site
systemctl reload nginx
```

### 2. Remover Cron Jobs relacionados
```bash
# Editar crontab e remover linhas relacionadas ao projeto antigo
crontab -e

# Remover arquivos de cron específicos (se houver)
rm -f /etc/cron.d/gestor*
```

## 🗑️ Remover Arquivos do Projeto

### 1. Remover diretório principal
```bash
# ATENÇÃO: Certifique-se de ter feito o backup!
# Remover o diretório completo
rm -rf /www/wwwroot/ultragestor.site/Gestoresse

# Verificar se foi removido
ls -la /www/wwwroot/ultragestor.site/
```

### 2. Remover outros diretórios relacionados (se houver)
```bash
# Procurar por outros diretórios relacionados
find /www -name "*gestor*" -type d 2>/dev/null
find /opt -name "*gestor*" -type d 2>/dev/null
find /var -name "*gestor*" -type d 2>/dev/null

# Remover se encontrar (cuidado para não remover o novo projeto!)
```

## 🗄️ Limpar Banco de Dados

### 1. Remover banco de dados antigo (CUIDADO!)
```bash
# Conectar ao MySQL
mysql -u root -p

# Listar bancos
SHOW DATABASES;

# Remover banco antigo (substitua pelo nome correto)
DROP DATABASE nome_do_banco_antigo;

# Sair
exit
```

### 2. Remover usuário do banco (se houver usuário específico)
```bash
mysql -u root -p -e "DROP USER 'usuario_antigo'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"
```

## 🧹 Limpeza Final

### 1. Limpar logs antigos
```bash
# Procurar logs relacionados
find /var/log -name "*gestor*" 2>/dev/null

# Remover logs antigos (se houver)
rm -f /var/log/*gestor*
```

### 2. Limpar cache e temporários
```bash
# Limpar cache do sistema
apt autoremove -y
apt autoclean

# Limpar logs antigos do sistema
journalctl --vacuum-time=7d
```

### 3. Verificar espaço liberado
```bash
# Ver espaço em disco
df -h

# Ver diretórios que mais ocupam espaço
du -sh /www/wwwroot/* | sort -hr
```

## ✅ Script Automatizado de Remoção

Aqui está um script que faz tudo automaticamente:

```bash
#!/bin/bash

# Script de remoção segura do projeto antigo
PROJECT_PATH="/www/wwwroot/ultragestor.site/Gestoresse"
BACKUP_DIR="/root/backups/$(date +%Y%m%d)"
DOMAIN="ultragestor.site"

echo "🗑️ Iniciando remoção segura do projeto antigo..."

# 1. Criar backup
echo "📦 Fazendo backup..."
mkdir -p $BACKUP_DIR
tar -czf $BACKUP_DIR/gestoresse-backup-$(date +%Y%m%d_%H%M%S).tar.gz $PROJECT_PATH
echo "✅ Backup criado em: $BACKUP_DIR"

# 2. Parar serviços (ajuste conforme necessário)
echo "🛑 Parando serviços..."
systemctl stop gestor-* 2>/dev/null || true
systemctl disable gestor-* 2>/dev/null || true

# 3. Remover configuração web
echo "🔧 Removendo configurações..."
a2dissite $DOMAIN 2>/dev/null || true
rm -f /etc/apache2/sites-available/$DOMAIN.conf
rm -f /etc/nginx/sites-enabled/$DOMAIN
rm -f /etc/nginx/sites-available/$DOMAIN
systemctl reload apache2 2>/dev/null || systemctl reload nginx 2>/dev/null || true

# 4. Remover arquivos
echo "🗂️ Removendo arquivos..."
rm -rf $PROJECT_PATH

# 5. Limpar serviços systemd
echo "🧹 Limpando serviços..."
rm -f /etc/systemd/system/gestor-*.service
systemctl daemon-reload

echo "✅ Remoção concluída!"
echo "📦 Backup salvo em: $BACKUP_DIR"
echo "🔍 Verifique se tudo foi removido corretamente"
```

## 🚨 Comandos Diretos (Use com Cuidado!)

Se você tem certeza e quer remover rapidamente:

```bash
# BACKUP PRIMEIRO!
tar -czf /root/backup-gestoresse-$(date +%Y%m%d).tar.gz /www/wwwroot/ultragestor.site/Gestoresse

# REMOVER PROJETO
rm -rf /www/wwwroot/ultragestor.site/Gestoresse

# REMOVER CONFIGURAÇÃO WEB
a2dissite ultragestor.site 2>/dev/null || true
rm -f /etc/apache2/sites-available/ultragestor.site.conf
rm -f /etc/nginx/sites-enabled/ultragestor.site
rm -f /etc/nginx/sites-available/ultragestor.site

# RECARREGAR SERVIDOR WEB
systemctl reload apache2 2>/dev/null || systemctl reload nginx 2>/dev/null

echo "✅ Projeto antigo removido!"
```

## 📋 Checklist de Verificação

Após a remoção, verifique:

- [ ] Backup foi criado com sucesso
- [ ] Diretório `/www/wwwroot/ultragestor.site/Gestoresse` foi removido
- [ ] Configuração do servidor web foi removida
- [ ] Serviços relacionados foram parados e removidos
- [ ] Cron jobs foram removidos
- [ ] Banco de dados antigo foi removido (se aplicável)
- [ ] Espaço em disco foi liberado
- [ ] Nenhum processo relacionado está rodando

## ⚠️ Importante

- **SEMPRE faça backup antes de remover**
- **Verifique se não há dados importantes no projeto antigo**
- **Confirme que o domínio não está sendo usado**
- **Teste se outros sites não foram afetados**

---

**🎯 Após remover o projeto antigo, você pode instalar o novo UltraGestor no mesmo domínio!**