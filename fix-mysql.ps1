# Script para corrigir conflito de porta MySQL
# Execute como Administrador

Write-Host "Corrigindo conflito de porta MySQL..." -ForegroundColor Yellow
Write-Host ""

# Verificar processos usando porta 3306
$processes = Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess -Unique

if ($processes) {
    Write-Host "Processos encontrados usando porta 3306:" -ForegroundColor Cyan
    foreach ($pid in $processes) {
        $proc = Get-Process -Id $pid -ErrorAction SilentlyContinue
        if ($proc) {
            Write-Host "  PID: $pid - Nome: $($proc.ProcessName) - Path: $($proc.Path)" -ForegroundColor White
        }
    }
    
    Write-Host ""
    Write-Host "Parando processos MySQL..." -ForegroundColor Yellow
    
    foreach ($pid in $processes) {
        try {
            Stop-Process -Id $pid -Force
            Write-Host "  [OK] Processo $pid parado" -ForegroundColor Green
        } catch {
            Write-Host "  [ERRO] Não foi possível parar processo $pid" -ForegroundColor Red
            Write-Host "  Execute este script como Administrador!" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "[OK] Nenhum processo usando porta 3306" -ForegroundColor Green
}

Write-Host ""
Write-Host "Verificando serviços MySQL do Windows..." -ForegroundColor Yellow

# Parar serviços MySQL do Windows
$services = Get-Service | Where-Object { $_.Name -like "*mysql*" -or $_.DisplayName -like "*mysql*" }

if ($services) {
    foreach ($service in $services) {
        if ($service.Status -eq "Running") {
            Write-Host "  Parando serviço: $($service.DisplayName)" -ForegroundColor Cyan
            try {
                Stop-Service -Name $service.Name -Force
                Set-Service -Name $service.Name -StartupType Disabled
                Write-Host "  [OK] Serviço parado e desabilitado" -ForegroundColor Green
            } catch {
                Write-Host "  [ERRO] Não foi possível parar serviço" -ForegroundColor Red
            }
        }
    }
} else {
    Write-Host "  [OK] Nenhum serviço MySQL encontrado" -ForegroundColor Green
}

Write-Host ""
Write-Host "Aguardando 3 segundos..." -ForegroundColor Gray
Start-Sleep -Seconds 3

Write-Host ""
Write-Host "Verificando se porta 3306 está livre..." -ForegroundColor Yellow
$portCheck = Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue

if ($portCheck) {
    Write-Host "[AVISO] Porta 3306 ainda está em uso!" -ForegroundColor Red
    Write-Host "Reinicie o computador e tente novamente." -ForegroundColor Yellow
} else {
    Write-Host "[OK] Porta 3306 está livre!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Agora você pode iniciar o MySQL no XAMPP Control Panel" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "Pressione qualquer tecla para fechar..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
