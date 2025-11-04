# Configuração do Cron Job - WhatsApp Automation

## 🎯 Objetivo

Configurar o cron job para executar a automação de WhatsApp automaticamente no servidor.

## 📋 Pré-requisitos

- Acesso SSH ao servidor VPS
- PHP instalado no servidor
- Projeto já deployado na VPS

## 🚀 Configuração Passo a Passo

### 1. Conectar ao servidor via SSH

```bash
ssh usuario@seu-servidor.com
```

### 2. Navegar até o diretório do projeto

```bash
cd /var/www/seu-projeto
# ou
cd /home/usuario/seu-projeto
```

### 3. Testar o script manualmente

```bash
php scripts/whatsapp-automation-cron.php
```

Se funcionar, você verá os logs no terminal.

### 4. Verificar o caminho do PHP

```bash
which php
```

Resultado esperado: `/usr/bin/php` ou `/usr/local/bin/php`

### 5. Abrir o crontab

```bash
crontab -e
```

Se perguntar qual editor usar, escolha `nano` (mais fácil).

### 6. Adicionar a linha do cron

Escolha uma das opções:

#### Opção 1: A cada 15 minutos (RECOMENDADO)
```bash
*/15 * * * * /usr/bin/php /var/www/seu-projeto/scripts/whatsapp-automation-cron.php >> /var/www/seu-projeto/logs/cron.log 2>&1
```

#### Opção 2: A cada 30 minutos
```bash
*/30 * * * * /usr/bin/php /var/www/seu-projeto/scripts/whatsapp-automation-cron.php >> /var/www/seu-projeto/logs/cron.log 2>&1
```

#### Opção 3: A cada 1 hora
```bash
0 * * * * /usr/bin/php /var/www/seu-projeto/scripts/whatsapp-automation-cron.php >> /var/www/seu-projeto/logs/cron.log 2>&1
```

### 7. Salvar e sair

- No `nano`: Pressione `Ctrl + X`, depois `Y`, depois `Enter`
- No `vim`: Pressione `Esc`, digite `:wq`, pressione `Enter`

### 8. Verificar se o cron foi adicionado

```bash
crontab -l
```

Deve mostrar a linha que você adicionou.

### 9. Criar diretório de logs (se não existir)

```bash
mkdir -p /var/www/seu-projeto/logs
chmod 755 /var/www/seu-projeto/logs
```

### 10. Aguardar a primeira execução

O cron vai executar automaticamente no próximo intervalo configurado.

## 📊 Monitoramento

### Ver logs do cron

```bash
tail -f /var/www/seu-projeto/logs/cron.log
```

### Ver logs da automação

```bash
tail -f /var/www/seu-projeto/logs/whatsapp-automation.log
```

### Ver últimas execuções do cron do sistema

```bash
grep CRON /var/log/syslog | tail -20
```

## 🔧 Troubleshooting

### Cron não está executando?

1. **Verificar se o cron está rodando:**
   ```bash
   sudo service cron status
   ```

2. **Verificar permissões do script:**
   ```bash
   chmod +x /var/www/seu-projeto/scripts/whatsapp-automation-cron.php
   ```

3. **Verificar logs do sistema:**
   ```bash
   sudo tail -f /var/log/syslog | grep CRON
   ```

4. **Testar o comando completo:**
   ```bash
   /usr/bin/php /var/www/seu-projeto/scripts/whatsapp-automation-cron.php
   ```

### Cron executa mas não envia mensagens?

1. **Verificar se o WhatsApp está conectado:**
   - Acesse o painel web
   - Vá em WhatsApp > Parear
   - Verifique se está conectado

2. **Verificar logs:**
   ```bash
   cat /var/www/seu-projeto/logs/whatsapp-automation.log
   ```

3. **Verificar configurações:**
   ```bash
   php /var/www/seu-projeto/scripts/check-whatsapp-automation-config.php
   ```

## 📝 Sintaxe do Crontab

```
* * * * * comando
│ │ │ │ │
│ │ │ │ └─── Dia da semana (0-7, 0 e 7 = Domingo)
│ │ │ └───── Mês (1-12)
│ │ └─────── Dia do mês (1-31)
│ └───────── Hora (0-23)
└─────────── Minuto (0-59)
```

### Exemplos:

- `0 9 * * *` = Todos os dias às 09:00
- `*/15 * * * *` = A cada 15 minutos
- `0 */2 * * *` = A cada 2 horas
- `0 9-17 * * 1-5` = De segunda a sexta, das 9h às 17h (a cada hora)
- `30 8 * * 1` = Toda segunda-feira às 08:30

## 🎯 Recomendações

### Para Produção:

1. **Use a cada 15 minutos** para melhor responsividade
2. **Configure alertas** se o cron falhar
3. **Monitore os logs** regularmente
4. **Faça backup** do crontab: `crontab -l > crontab-backup.txt`

### Para Desenvolvimento:

1. **Teste manualmente** antes de configurar o cron
2. **Use logs detalhados** para debug
3. **Configure para intervalos maiores** (1 hora) para não sobrecarregar

## 🔐 Segurança

1. **Não exponha os logs publicamente:**
   ```bash
   chmod 600 /var/www/seu-projeto/logs/*.log
   ```

2. **Rotacione os logs** para não crescerem infinitamente:
   ```bash
   # Adicionar ao crontab
   0 0 * * 0 find /var/www/seu-projeto/logs -name "*.log" -mtime +30 -delete
   ```

3. **Use variáveis de ambiente** para credenciais sensíveis (já configurado no `.env`)

## ✅ Checklist de Deploy

- [ ] Script testado manualmente
- [ ] Caminho do PHP verificado
- [ ] Cron configurado
- [ ] Diretório de logs criado
- [ ] Permissões configuradas
- [ ] Primeira execução verificada
- [ ] Logs monitorados
- [ ] WhatsApp conectado
- [ ] Backup do crontab feito

---

**Última atualização:** 30/10/2025
