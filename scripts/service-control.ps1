# Script de Controle do Serviço de Automação de Faturas
param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("start", "stop", "status", "restart", "logs")]
    [string]$Action,
    
    [string]$ProjectPath = (Get-Location).Path,
    [string]$PhpPath = "C:\xampp\php\php.exe"
)

$ServiceScript = Join-Path $ProjectPath "scripts\invoice-automation-service.php"
$LogsDir = Join-Path $ProjectPath "logs"
$ServiceLog = Join-Path $LogsDir "invoice-automation-service.log"
$PidFile = Join-Path $LogsDir "service.pid"
$ControlFile = Join-Path $LogsDir "service-control.json"

# Criar diretório de logs se não existir
if (-not (Test-Path $LogsDir)) {
    New-Item -ItemType Directory -Path $LogsDir -Force | Out-Null
}

function Get-ServiceStatus {
    if (Test-Path $PidFile) {
        $pid = Get-Content $PidFile -ErrorAction SilentlyContinue
        if ($pid) {
            $process = Get-Process -Id $pid -ErrorAction SilentlyContinue
            if ($process) {
                return @{
                    Running = $true
                    PID = $pid
                    StartTime = $process.StartTime
                    ProcessName = $process.ProcessName
                }
            }
        }
    }
    
    return @{
        Running = $false
        PID = $null
    }
}

function Start-AutomationService {
    $status = Get-ServiceStatus
    
    if ($status.Running) {
        Write-Host "⚠ Serviço já está executando (PID: $($status.PID))" -ForegroundColor Yellow
        return
    }
    
    Write-Host "🚀 Iniciando serviço de automação..." -ForegroundColor Green
    
    # Remover arquivo de controle anterior
    if (Test-Path $ControlFile) {
        Remove-Item $ControlFile -Force
    }
    
    # Iniciar processo em background
    $processInfo = New-Object System.Diagnostics.ProcessStartInfo
    $processInfo.FileName = $PhpPath
    $processInfo.Arguments = $ServiceScript
    $processInfo.UseShellExecute = $false
    $processInfo.CreateNoWindow = $true
    $processInfo.RedirectStandardOutput = $false
    $processInfo.RedirectStandardError = $false
    
    $process = [System.Diagnostics.Process]::Start($processInfo)
    
    Start-Sleep -Seconds 2
    
    $newStatus = Get-ServiceStatus
    if ($newStatus.Running) {
        Write-Host "✅ Serviço iniciado com sucesso!" -ForegroundColor Green
        Write-Host "   PID: $($newStatus.PID)" -ForegroundColor Cyan
        Write-Host "   Log: $ServiceLog" -ForegroundColor Cyan
    } else {
        Write-Host "❌ Falha ao iniciar o serviço" -ForegroundColor Red
        if (Test-Path $ServiceLog) {
            Write-Host "Últimas linhas do log:" -ForegroundColor Yellow
            Get-Content $ServiceLog -Tail 5
        }
    }
}

function Stop-AutomationService {
    $status = Get-ServiceStatus
    
    if (-not $status.Running) {
        Write-Host "⚠ Serviço não está executando" -ForegroundColor Yellow
        return
    }
    
    Write-Host "🛑 Parando serviço de automação..." -ForegroundColor Yellow
    
    # Criar comando de parada
    $stopCommand = @{
        stop = $true
        requested_at = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    }
    
    $stopCommand | ConvertTo-Json | Set-Content $ControlFile
    
    # Aguardar parada graceful
    $timeout = 30
    $waited = 0
    
    while ($waited -lt $timeout) {
        Start-Sleep -Seconds 1
        $waited++
        
        $currentStatus = Get-ServiceStatus
        if (-not $currentStatus.Running) {
            Write-Host "✅ Serviço parado com sucesso!" -ForegroundColor Green
            return
        }
    }
    
    # Se não parou gracefully, forçar
    Write-Host "⚠ Forçando parada do processo..." -ForegroundColor Yellow
    try {
        Stop-Process -Id $status.PID -Force
        Write-Host "✅ Processo finalizado" -ForegroundColor Green
    } catch {
        Write-Host "❌ Erro ao finalizar processo: $($_.Exception.Message)" -ForegroundColor Red
    }
}

function Show-ServiceStatus {
    $status = Get-ServiceStatus
    
    Write-Host "=== STATUS DO SERVIÇO DE AUTOMAÇÃO ===" -ForegroundColor Cyan
    
    if ($status.Running) {
        Write-Host "Status: 🟢 EXECUTANDO" -ForegroundColor Green
        Write-Host "PID: $($status.PID)" -ForegroundColor White
        Write-Host "Iniciado em: $($status.StartTime)" -ForegroundColor White
        
        # Mostrar informações do arquivo de controle
        if (Test-Path $ControlFile) {
            try {
                $controlData = Get-Content $ControlFile | ConvertFrom-Json
                Write-Host "Última atualização: $($controlData.last_update)" -ForegroundColor White
                Write-Host "Uso de memória: $([math]::Round($controlData.memory_usage / 1MB, 2)) MB" -ForegroundColor White
                
                if ($controlData.execution_count) {
                    Write-Host "Execuções realizadas: $($controlData.execution_count)" -ForegroundColor White
                }
                
                if ($controlData.last_execution) {
                    Write-Host "Última execução: $($controlData.last_execution)" -ForegroundColor White
                }
            } catch {
                Write-Host "Erro ao ler dados de controle" -ForegroundColor Yellow
            }
        }
    } else {
        Write-Host "Status: 🔴 PARADO" -ForegroundColor Red
    }
    
    # Mostrar informações do log
    if (Test-Path $ServiceLog) {
        $logInfo = Get-Item $ServiceLog
        Write-Host "Log: $ServiceLog" -ForegroundColor Cyan
        Write-Host "Tamanho do log: $([math]::Round($logInfo.Length / 1KB, 2)) KB" -ForegroundColor White
        Write-Host "Última modificação: $($logInfo.LastWriteTime)" -ForegroundColor White
    }
}

function Show-ServiceLogs {
    if (-not (Test-Path $ServiceLog)) {
        Write-Host "❌ Arquivo de log não encontrado: $ServiceLog" -ForegroundColor Red
        return
    }
    
    Write-Host "=== ÚLTIMAS 20 LINHAS DO LOG ===" -ForegroundColor Cyan
    Get-Content $ServiceLog -Tail 20
    
    Write-Host "`n=== COMANDOS ÚTEIS ===" -ForegroundColor Yellow
    Write-Host "Ver log completo: Get-Content '$ServiceLog'" -ForegroundColor White
    Write-Host "Acompanhar log: Get-Content '$ServiceLog' -Wait -Tail 10" -ForegroundColor White
}

# Executar ação solicitada
switch ($Action.ToLower()) {
    "start" { Start-AutomationService }
    "stop" { Stop-AutomationService }
    "status" { Show-ServiceStatus }
    "restart" { 
        Stop-AutomationService
        Start-Sleep -Seconds 3
        Start-AutomationService
    }
    "logs" { Show-ServiceLogs }
}