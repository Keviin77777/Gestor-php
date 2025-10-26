# Script de Instalação Completa da Automação de Faturas
# Execute como Administrador

param(
    [string]$ProjectPath = (Get-Location).Path,
    [string]$PhpPath = "C:\xampp\php\php.exe",
    [switch]$ServiceMode = $false,
    [switch]$CronMode = $false,
    [switch]$Both = $true
)

Write-Host "=== INSTALAÇÃO DA AUTOMAÇÃO DE FATURAS ===" -ForegroundColor Green
Write-Host "Projeto: $ProjectPath" -ForegroundColor Yellow
Write-Host "PHP: $PhpPath" -ForegroundColor Yellow

# Verificar se o PHP existe
if (-not (Test-Path $PhpPath)) {
    Write-Host "❌ PHP não encontrado em $PhpPath" -ForegroundColor Red
    Write-Host "Instale o XAMPP ou ajuste o caminho do PHP" -ForegroundColor Red
    exit 1
}

# Verificar arquivos necessários
$RequiredFiles = @(
    "app\helpers\invoice-automation.php",
    "scripts\invoice-automation-cron.php",
    "scripts\invoice-automation-service.php",
    "scripts\service-control.ps1",
    "config\invoice-automation.php"
)

foreach ($file in $RequiredFiles) {
    $fullPath = Join-Path $ProjectPath $file
    if (-not (Test-Path $fullPath)) {
        Write-Host "❌ Arquivo necessário não encontrado: $file" -ForegroundColor Red
        exit 1
    }
}

Write-Host "✅ Todos os arquivos necessários encontrados" -ForegroundColor Green

# Criar diretórios necessários
$Directories = @("logs", "config")
foreach ($dir in $Directories) {
    $dirPath = Join-Path $ProjectPath $dir
    if (-not (Test-Path $dirPath)) {
        New-Item -ItemType Directory -Path $dirPath -Force | Out-Null
        Write-Host "✅ Diretório criado: $dir" -ForegroundColor Green
    }
}

# Testar conexão com banco de dados
Write-Host "`n🔍 Testando conexão com banco de dados..." -ForegroundColor Yellow
$TestScript = Join-Path $ProjectPath "scripts\test-database.php"

# Criar script de teste temporário
@"
<?php
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

try {
    `$clients = Database::fetchAll("SELECT COUNT(*) as total FROM clients LIMIT 1");
    echo "✅ Conexão com banco OK - " . `$clients[0]['total'] . " clientes encontrados\n";
} catch (Exception `$e) {
    echo "❌ Erro na conexão: " . `$e->getMessage() . "\n";
    exit(1);
}
?>
"@ | Set-Content $TestScript

$testResult = & $PhpPath $TestScript
Write-Host $testResult

Remove-Item $TestScript -Force

if ($testResult -like "*❌*") {
    Write-Host "❌ Falha na conexão com banco de dados" -ForegroundColor Red
    exit 1
}

# Testar automação
Write-Host "`n🧪 Testando sistema de automação..." -ForegroundColor Yellow
$TestAutomationScript = Join-Path $ProjectPath "scripts\test-automation.php"

@"
<?php
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/invoice-automation.php';

loadEnv(__DIR__ . '/../.env');

try {
    `$report = runInvoiceAutomation();
    echo "✅ Sistema de automação OK\n";
    echo "Clientes verificados: " . `$report['total_clients_checked'] . "\n";
    echo "Faturas geradas: " . `$report['invoices_generated'] . "\n";
} catch (Exception `$e) {
    echo "❌ Erro na automação: " . `$e->getMessage() . "\n";
    exit(1);
}
?>
"@ | Set-Content $TestAutomationScript

$automationResult = & $PhpPath $TestAutomationScript
Write-Host $automationResult

Remove-Item $TestAutomationScript -Force

if ($automationResult -like "*❌*") {
    Write-Host "❌ Falha no sistema de automação" -ForegroundColor Red
    exit 1
}

# Configurar Task Scheduler (Cron Mode)
if ($CronMode -or $Both) {
    Write-Host "`n⏰ Configurando Task Scheduler..." -ForegroundColor Yellow
    
    try {
        & (Join-Path $ProjectPath "scripts\setup-windows-automation.ps1") -ProjectPath $ProjectPath -PhpPath $PhpPath
        Write-Host "✅ Task Scheduler configurado" -ForegroundColor Green
    } catch {
        Write-Host "⚠ Erro ao configurar Task Scheduler: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

# Configurar Serviço (Service Mode)
if ($ServiceMode -or $Both) {
    Write-Host "`n🔧 Configurando serviço de background..." -ForegroundColor Yellow
    
    # Criar script de inicialização automática
    $StartupScript = Join-Path $ProjectPath "scripts\start-automation-service.ps1"
    
    @"
# Script de inicialização automática do serviço
`$ProjectPath = "$ProjectPath"
`$ServiceControl = Join-Path `$ProjectPath "scripts\service-control.ps1"

# Iniciar serviço se não estiver rodando
& `$ServiceControl -Action status | Out-Null
if (`$LASTEXITCODE -ne 0) {
    Write-Host "Iniciando serviço de automação..." -ForegroundColor Green
    & `$ServiceControl -Action start -ProjectPath `$ProjectPath -PhpPath "$PhpPath"
}
"@ | Set-Content $StartupScript
    
    Write-Host "✅ Script de inicialização criado: $StartupScript" -ForegroundColor Green
    
    # Criar atalho na pasta de inicialização do Windows
    $StartupFolder = [Environment]::GetFolderPath("Startup")
    $ShortcutPath = Join-Path $StartupFolder "UltraGestor-Automation.lnk"
    
    try {
        $WshShell = New-Object -ComObject WScript.Shell
        $Shortcut = $WshShell.CreateShortcut($ShortcutPath)
        $Shortcut.TargetPath = "powershell.exe"
        $Shortcut.Arguments = "-WindowStyle Hidden -ExecutionPolicy Bypass -File `"$StartupScript`""
        $Shortcut.WorkingDirectory = $ProjectPath
        $Shortcut.Description = "UltraGestor - Automação de Faturas"
        $Shortcut.Save()
        
        Write-Host "✅ Atalho de inicialização criado" -ForegroundColor Green
    } catch {
        Write-Host "⚠ Não foi possível criar atalho de inicialização: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

# Criar documentação
$DocsPath = Join-Path $ProjectPath "docs\AUTOMACAO-FATURAS.md"
$DocsDir = Split-Path $DocsPath -Parent

if (-not (Test-Path $DocsDir)) {
    New-Item -ItemType Directory -Path $DocsDir -Force | Out-Null
}

@"
# Automação de Faturas - UltraGestor

## Visão Geral

O sistema de automação de faturas gera automaticamente faturas para clientes com renovação próxima (10 dias ou menos).

## Funcionamento

- **Verificação**: 3 vezes por dia (09:00, 14:00, 20:00)
- **Critério**: Clientes com renovação em 10 dias ou menos
- **Prevenção**: Não gera fatura duplicada no mesmo mês
- **Integração**: Automática ao criar/editar clientes

## Configuração

### Arquivo .env
```
INVOICE_AUTOMATION_ENABLED=true
INVOICE_AUTOMATION_DAYS=10
INVOICE_AUTOMATION_MAX_PER_RUN=50
INVOICE_DAYS_TO_PAY=5
ADMIN_EMAIL=admin@ultragestor.com
NOTIFY_ON_INVOICE_GENERATION=true
NOTIFY_ON_ERROR=true
```

## Comandos

### Controle do Serviço
```powershell
# Status
.\scripts\service-control.ps1 -Action status

# Iniciar
.\scripts\service-control.ps1 -Action start

# Parar
.\scripts\service-control.ps1 -Action stop

# Reiniciar
.\scripts\service-control.ps1 -Action restart

# Ver logs
.\scripts\service-control.ps1 -Action logs
```

### Task Scheduler
```powershell
# Ver tarefa
Get-ScheduledTask -TaskName "InvoiceAutomation"

# Executar manualmente
Start-ScheduledTask -TaskName "InvoiceAutomation"

# Desabilitar
Disable-ScheduledTask -TaskName "InvoiceAutomation"
```

## Logs

- **Serviço**: `logs/invoice-automation-service.log`
- **Cron**: `logs/invoice-automation.log`
- **Status**: `logs/last-automation-status.json`

## Arquivos Importantes

- `app/helpers/invoice-automation.php` - Lógica principal
- `scripts/invoice-automation-cron.php` - Execução via cron
- `scripts/invoice-automation-service.php` - Serviço de background
- `config/invoice-automation.php` - Configurações

## Solução de Problemas

1. **Verificar logs** em `logs/`
2. **Testar conexão** com banco de dados
3. **Verificar configurações** no `.env`
4. **Reiniciar serviços** se necessário

## Suporte

Para suporte, verifique os logs e entre em contato com o administrador do sistema.
"@ | Set-Content $DocsPath

Write-Host "✅ Documentação criada: $DocsPath" -ForegroundColor Green

# Resumo final
Write-Host "`n=== INSTALAÇÃO CONCLUÍDA ===" -ForegroundColor Green
Write-Host "✅ Sistema de automação instalado e configurado" -ForegroundColor Green
Write-Host "✅ Logs configurados em: logs/" -ForegroundColor Green
Write-Host "✅ Documentação criada em: $DocsPath" -ForegroundColor Green

if ($CronMode -or $Both) {
    Write-Host "✅ Task Scheduler configurado (3x por dia)" -ForegroundColor Green
}

if ($ServiceMode -or $Both) {
    Write-Host "✅ Serviço de background configurado" -ForegroundColor Green
    Write-Host "✅ Inicialização automática configurada" -ForegroundColor Green
}

Write-Host "`n🎯 PRÓXIMOS PASSOS:" -ForegroundColor Cyan
Write-Host "1. Verificar configurações no arquivo .env" -ForegroundColor White
Write-Host "2. Testar execução manual:" -ForegroundColor White
Write-Host "   .\scripts\service-control.ps1 -Action start" -ForegroundColor Yellow
Write-Host "3. Monitorar logs em logs/" -ForegroundColor White
Write-Host "4. Verificar faturas geradas no sistema" -ForegroundColor White

Write-Host "`n🔧 COMANDOS ÚTEIS:" -ForegroundColor Cyan
Write-Host "Status: .\scripts\service-control.ps1 -Action status" -ForegroundColor Yellow
Write-Host "Logs: .\scripts\service-control.ps1 -Action logs" -ForegroundColor Yellow
Write-Host "Docs: Get-Content docs\AUTOMACAO-FATURAS.md" -ForegroundColor Yellow