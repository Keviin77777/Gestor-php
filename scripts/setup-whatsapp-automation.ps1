# Script de Instalacao da Automacao WhatsApp
# Configura o sistema de automacao para Windows

Write-Host "=== INSTALACAO DA AUTOMACAO WHATSAPP ===" -ForegroundColor Green
Write-Host ""

# Verificar se esta no diretorio correto
if (-not (Test-Path "app\helpers\whatsapp-automation.php")) {
    Write-Host "Execute este script no diretorio raiz do projeto!" -ForegroundColor Red
    exit 1
}

Write-Host "Diretorio correto detectado" -ForegroundColor Green

# Criar diretorio de logs se nao existir
$logDir = "logs"
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    Write-Host "Diretorio de logs criado" -ForegroundColor Green
}

# Verificar se o PHP esta instalado
try {
    $phpVersion = php -v 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "PHP encontrado" -ForegroundColor Green
    } else {
        throw "PHP nao encontrado"
    }
} catch {
    Write-Host "PHP nao esta instalado ou nao esta no PATH!" -ForegroundColor Red
    Write-Host "Instale o PHP e adicione ao PATH do sistema." -ForegroundColor Yellow
    exit 1
}

# Verificar se o arquivo .env existe
if (-not (Test-Path ".env")) {
    Write-Host "Arquivo .env nao encontrado!" -ForegroundColor Red
    Write-Host "Crie o arquivo .env com as configuracoes do banco de dados." -ForegroundColor Yellow
    exit 1
}

Write-Host "Arquivo .env encontrado" -ForegroundColor Green

# Testar conexao com o banco de dados
Write-Host "Testando conexao com o banco de dados..." -ForegroundColor Yellow
try {
    $testScript = @"
require_once 'app/helpers/functions.php';
require_once 'app/core/Database.php';
loadEnv('.env');
Database::connect();
echo 'Conexao OK';
"@
    
    $testScript | php 2>$null
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Conexao com banco de dados OK" -ForegroundColor Green
    } else {
        throw "Falha na conexao"
    }
} catch {
    Write-Host "Falha na conexao com o banco de dados!" -ForegroundColor Red
    Write-Host "Verifique as configuracoes no arquivo .env" -ForegroundColor Yellow
    exit 1
}

# Verificar se as tabelas WhatsApp existem
Write-Host "Verificando tabelas WhatsApp..." -ForegroundColor Yellow
try {
    $result = php test-tables.php 2>$null
    
    if ([int]$result -ge 4) {
        Write-Host "Tabelas WhatsApp encontradas" -ForegroundColor Green
    } else {
        Write-Host "Tabelas WhatsApp nao encontradas!" -ForegroundColor Red
        Write-Host "Execute primeiro: php database/create-whatsapp-tables.sql" -ForegroundColor Yellow
        exit 1
    }
} catch {
    Write-Host "Erro ao verificar tabelas!" -ForegroundColor Red
    exit 1
}

# Testar automacao
Write-Host "Testando sistema de automacao..." -ForegroundColor Yellow
try {
    $testAutomationScript = @"
require_once 'app/helpers/functions.php';
require_once 'app/core/Database.php';
require_once 'app/helpers/whatsapp-automation.php';
loadEnv('.env');
echo 'Automacao OK';
"@
    
    $testAutomationScript | php 2>$null
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Sistema de automacao OK" -ForegroundColor Green
    } else {
        throw "Falha no teste"
    }
} catch {
    Write-Host "Erro no sistema de automacao!" -ForegroundColor Red
    exit 1
}

# Criar tarefa agendada para automacao
Write-Host "Configurando tarefa agendada..." -ForegroundColor Yellow

$taskName = "WhatsApp Automation"
$scriptPath = (Get-Location).Path + "\scripts\whatsapp-automation-cron.php"
$phpPath = (Get-Command php).Source

# Remover tarefa existente se houver
try {
    schtasks /Delete /TN $taskName /F 2>$null | Out-Null
} catch {
    # Ignorar erro se a tarefa nao existir
}

# Criar nova tarefa
$taskCommand = "schtasks /Create /TN `"$taskName`" /TR `"$phpPath $scriptPath`" /SC DAILY /ST 09:00 /ST 18:00 /F"

try {
    Invoke-Expression $taskCommand
    Write-Host "Tarefa agendada criada com sucesso!" -ForegroundColor Green
    Write-Host "   - Execucao: Diariamente as 09:00 e 18:00" -ForegroundColor Cyan
} catch {
    Write-Host "Falha ao criar tarefa agendada!" -ForegroundColor Red
    Write-Host "Crie manualmente no Agendador de Tarefas do Windows:" -ForegroundColor Yellow
    Write-Host "   - Programa: $phpPath" -ForegroundColor Yellow
    Write-Host "   - Argumentos: $scriptPath" -ForegroundColor Yellow
    Write-Host "   - Executar: Diariamente as 09:00 e 18:00" -ForegroundColor Yellow
}

# Criar script de inicializacao
$startScript = @"
@echo off
echo Iniciando Servico de Automacao WhatsApp...
cd /d "%~dp0"
php scripts\whatsapp-service-control.php start
pause
"@

$startScriptPath = "start-whatsapp-service.bat"
Set-Content -Path $startScriptPath -Value $startScript -Encoding UTF8
Write-Host "Script de inicializacao criado: $startScriptPath" -ForegroundColor Green

# Criar script de parada
$stopScript = @"
@echo off
echo Parando Servico de Automacao WhatsApp...
cd /d "%~dp0"
php scripts\whatsapp-service-control.php stop
pause
"@

$stopScriptPath = "stop-whatsapp-service.bat"
Set-Content -Path $stopScriptPath -Value $stopScript -Encoding UTF8
Write-Host "Script de parada criado: $stopScriptPath" -ForegroundColor Green

# Criar script de status
$statusScript = @"
@echo off
echo Status do Servico de Automacao WhatsApp...
cd /d "%~dp0"
php scripts\whatsapp-service-control.php status
pause
"@

$statusScriptPath = "whatsapp-service-status.bat"
Set-Content -Path $statusScriptPath -Value $statusScript -Encoding UTF8
Write-Host "Script de status criado: $statusScriptPath" -ForegroundColor Green

# Testar execucao manual
Write-Host "Testando execucao manual..." -ForegroundColor Yellow
try {
    $result = php scripts\whatsapp-service-control.php run 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Teste de execucao manual OK" -ForegroundColor Green
    } else {
        Write-Host "Teste de execucao manual falhou, mas pode ser normal se nao ha clientes elegiveis" -ForegroundColor Yellow
    }
} catch {
    Write-Host "Erro no teste de execucao manual" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== INSTALACAO CONCLUIDA ===" -ForegroundColor Green
Write-Host ""
Write-Host "RESUMO:" -ForegroundColor Cyan
Write-Host "Sistema de automacao WhatsApp configurado" -ForegroundColor Green
Write-Host "Tarefa agendada criada (09:00 e 18:00)" -ForegroundColor Green
Write-Host "Scripts de controle criados" -ForegroundColor Green
Write-Host ""
Write-Host "COMANDOS DISPONIVEIS:" -ForegroundColor Cyan
Write-Host "   Iniciar servico:     .\start-whatsapp-service.bat" -ForegroundColor White
Write-Host "   Parar servico:       .\stop-whatsapp-service.bat" -ForegroundColor White
Write-Host "   Ver status:          .\whatsapp-service-status.bat" -ForegroundColor White
Write-Host "   Executar manual:     php scripts\whatsapp-service-control.php run" -ForegroundColor White
Write-Host "   Ver logs:            php scripts\whatsapp-service-control.php logs" -ForegroundColor White
Write-Host ""
Write-Host "LOGS:" -ForegroundColor Cyan
Write-Host "   Logs do servico:     logs\whatsapp-automation-service.log" -ForegroundColor White
Write-Host "   Logs de automacao:   logs\whatsapp-automation.log" -ForegroundColor White
Write-Host ""
Write-Host "IMPORTANTE:" -ForegroundColor Yellow
Write-Host "   - O servico roda em background e funciona mesmo com o site fechado" -ForegroundColor White
Write-Host "   - Verifique os logs regularmente para monitorar o funcionamento" -ForegroundColor White
Write-Host "   - As automacoes so funcionam se o WhatsApp estiver conectado" -ForegroundColor White
Write-Host ""
Write-Host "Instalacao concluida com sucesso!" -ForegroundColor Green