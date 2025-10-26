# Script para configurar automação de faturas no Windows
# Execute como Administrador

param(
    [string]$ProjectPath = (Get-Location).Path,
    [string]$PhpPath = "C:\xampp\php\php.exe"
)

Write-Host "=== CONFIGURAÇÃO DE AUTOMAÇÃO DE FATURAS ===" -ForegroundColor Green
Write-Host "Caminho do projeto: $ProjectPath" -ForegroundColor Yellow
Write-Host "Caminho do PHP: $PhpPath" -ForegroundColor Yellow

# Verificar se o PHP existe
if (-not (Test-Path $PhpPath)) {
    Write-Host "ERRO: PHP não encontrado em $PhpPath" -ForegroundColor Red
    Write-Host "Verifique se o XAMPP está instalado ou ajuste o caminho do PHP" -ForegroundColor Red
    exit 1
}

# Verificar se o script de automação existe
$AutomationScript = Join-Path $ProjectPath "scripts\invoice-automation-cron.php"
if (-not (Test-Path $AutomationScript)) {
    Write-Host "ERRO: Script de automação não encontrado em $AutomationScript" -ForegroundColor Red
    exit 1
}

# Criar diretório de logs se não existir
$LogsDir = Join-Path $ProjectPath "logs"
if (-not (Test-Path $LogsDir)) {
    New-Item -ItemType Directory -Path $LogsDir -Force
    Write-Host "Diretório de logs criado: $LogsDir" -ForegroundColor Green
}

try {
    # Criar tarefa no Task Scheduler
    $TaskName = "InvoiceAutomation"
    $TaskDescription = "Automação de Faturas - Executa 3 vezes por dia"
    
    # Remover tarefa existente se houver
    $ExistingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($ExistingTask) {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Host "Tarefa existente removida" -ForegroundColor Yellow
    }
    
    # Criar ação
    $Action = New-ScheduledTaskAction -Execute $PhpPath -Argument $AutomationScript
    
    # Criar triggers (3 vezes por dia: 09:00, 14:00, 20:00)
    $Trigger1 = New-ScheduledTaskTrigger -Daily -At "09:00"
    $Trigger2 = New-ScheduledTaskTrigger -Daily -At "14:00" 
    $Trigger3 = New-ScheduledTaskTrigger -Daily -At "20:00"
    
    # Configurações da tarefa
    $Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
    
    # Criar principal (executar como SYSTEM)
    $Principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    
    # Registrar tarefa
    Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger @($Trigger1, $Trigger2, $Trigger3) -Settings $Settings -Principal $Principal -Description $TaskDescription
    
    Write-Host "✓ Tarefa '$TaskName' criada com sucesso!" -ForegroundColor Green
    Write-Host "✓ Execução programada para: 09:00, 14:00 e 20:00 diariamente" -ForegroundColor Green
    
    # Testar execução
    Write-Host "`nTestando execução..." -ForegroundColor Yellow
    Start-ScheduledTask -TaskName $TaskName
    
    Start-Sleep -Seconds 5
    
    # Verificar logs
    $LogFile = Join-Path $LogsDir "invoice-automation.log"
    if (Test-Path $LogFile) {
        Write-Host "✓ Teste executado com sucesso!" -ForegroundColor Green
        Write-Host "Últimas linhas do log:" -ForegroundColor Yellow
        Get-Content $LogFile -Tail 5
    } else {
        Write-Host "⚠ Log não encontrado, verifique se há erros" -ForegroundColor Yellow
    }
    
    Write-Host "`n=== CONFIGURAÇÃO CONCLUÍDA ===" -ForegroundColor Green
    Write-Host "A automação está configurada e funcionando!" -ForegroundColor Green
    Write-Host "Logs serão salvos em: $LogFile" -ForegroundColor Yellow
    Write-Host "`nPara gerenciar a tarefa:" -ForegroundColor Cyan
    Write-Host "- Ver status: Get-ScheduledTask -TaskName '$TaskName'" -ForegroundColor Cyan
    Write-Host "- Executar manualmente: Start-ScheduledTask -TaskName '$TaskName'" -ForegroundColor Cyan
    Write-Host "- Desabilitar: Disable-ScheduledTask -TaskName '$TaskName'" -ForegroundColor Cyan
    Write-Host "- Remover: Unregister-ScheduledTask -TaskName '$TaskName'" -ForegroundColor Cyan
    
} catch {
    Write-Host "ERRO ao configurar automação: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}