# 🤖 Automação de WhatsApp - Guia Completo

## 📋 Como Funciona

O sistema executa **automaticamente a cada hora** e verifica:
1. Se há templates agendados para aquele horário
2. Se há clientes que precisam receber lembretes de vencimento

## ⚙️ Configuração do CRON

### Windows (Task Scheduler)

1. Pressione `Windows + R` e digite: `taskschd.msc`
2. Clique em "Criar Tarefa Básica"
3. Configure:
   - **Nome**: WhatsApp Automação
   - **Gatilho**: Diariamente
   - **Hora de início**: 00:00
   - **Ação**: Iniciar um programa
   - **Programa**: `C:\php\php.exe`
   - **Argumentos**: `C:\caminho\completo\scripts\whatsapp-automation-cron.php`
4. Após criar, clique com botão direito → Propriedades
5. Vá em "Gatilhos" → Editar
6. Marque: **"Repetir tarefa a cada: 1 hora"**
7. Duração: **1 dia**
8. OK

### Linux/Mac (Crontab)

```bash
# Editar crontab
crontab -e

# Adicionar linha (executar a cada hora)
0 * * * * php /caminho/completo/scripts/whatsapp-automation-cron.php
```

## 🎯 Como Usar no Sistema

### 1. Configurar Horário Padrão (09:00)

Os lembretes de vencimento são enviados automaticamente quando o CRON executa.
Não precisa configurar nada, já funciona!

### 2. Mudar Horário para 08:00 ou 12:00

1. Acesse: **WhatsApp → Agendamentos**
2. Edite o template desejado
3. Configure:
   - **Dias da semana**: Selecione os dias
   - **Horário**: Digite 08:00 ou 12:00
   - **Ativo**: Marque como ativo
4. Salve

Pronto! O sistema vai enviar automaticamente no horário configurado.

## 📊 Verificar se Está Funcionando

### Ver Log
```bash
# Windows
type logs\whatsapp-automation.log

# Linux/Mac
cat logs/whatsapp-automation.log
```

### Testar Manualmente
```bash
php scripts/whatsapp-automation-cron.php
```

## 🔍 Entendendo o Log

```
[2025-10-29 08:00:00] === INICIANDO AUTOMAÇÃO WHATSAPP ===
[2025-10-29 08:00:00] Hora atual: 08:00 | Dia: tuesday
[2025-10-29 08:00:00] --- Verificando Templates Agendados ---
[2025-10-29 08:00:01] ✅ Templates agendados: 2 mensagens enviadas
[2025-10-29 08:00:01]   → Template ID 123 enviado para cliente 456
[2025-10-29 08:00:02] --- Verificando Lembretes de Vencimento ---
[2025-10-29 08:00:02] ✅ Lembretes de vencimento: 1 enviados
[2025-10-29 08:00:02]   → João Silva (expires_today) - 0 dias
[2025-10-29 08:00:03] --- Resumo ---
[2025-10-29 08:00:03] 📊 Total de mensagens enviadas: 3
[2025-10-29 08:00:03] 📊 Total de erros: 0
[2025-10-29 08:00:03] === AUTOMAÇÃO FINALIZADA ===
```

## ⏰ Tolerância de Horário

O sistema tem tolerância de **5 minutos**:

- Template agendado: **08:00**
- CRON executa: **08:03**
- Resultado: ✅ **Envia** (dentro da tolerância)

- Template agendado: **08:00**
- CRON executa: **08:10**
- Resultado: ❌ **Não envia** (fora da tolerância)

## 🎯 Exemplos de Uso

### Exemplo 1: Enviar às 08:00 todos os dias
1. Configure template com horário: **08:00**
2. Dias: Segunda a Domingo
3. O CRON vai executar às 08:00 e enviar

### Exemplo 2: Enviar às 12:00 apenas dias úteis
1. Configure template com horário: **12:00**
2. Dias: Segunda a Sexta
3. O CRON vai executar às 12:00 e enviar apenas nos dias úteis

### Exemplo 3: Múltiplos horários
1. Template 1: **08:00** (Bom dia)
2. Template 2: **12:00** (Lembrete meio-dia)
3. Template 3: **18:00** (Lembrete noite)
4. Todos funcionam automaticamente!

## 🚨 Solução de Problemas

### Mensagens não estão sendo enviadas

1. Verifique se o CRON está configurado corretamente
2. Verifique o log: `logs/whatsapp-automation.log`
3. Execute manualmente: `php scripts/whatsapp-automation-cron.php`
4. Verifique se o template está ativo
5. Verifique se o horário está correto

### Como saber se o CRON está rodando?

Verifique a data da última linha do log:
```bash
# Windows
type logs\whatsapp-automation.log | findstr "FINALIZADA"

# Linux/Mac
tail logs/whatsapp-automation.log | grep "FINALIZADA"
```

Se a última execução foi há mais de 1 hora, o CRON não está funcionando.

## 📝 Resumo

✅ **Configure o CRON para executar A CADA HORA**
✅ **Configure os horários no sistema (WhatsApp → Agendamentos)**
✅ **O sistema detecta automaticamente e envia no horário certo**
✅ **Verifique o log para confirmar**

Pronto! Seu sistema está configurado e funcionando! 🎉
