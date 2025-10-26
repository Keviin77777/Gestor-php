# Script de Setup Automático - UltraGestor
# Execute como Administrador

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "  UltraGestor - Setup Automático  " -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

# Verificar se XAMPP está instalado
$xamppPath = "C:\xampp"
if (Test-Path $xamppPath) {
    Write-Host "[OK] XAMPP encontrado em $xamppPath" -ForegroundColor Green
} else {
    Write-Host "[ERRO] XAMPP não encontrado!" -ForegroundColor Red
    Write-Host "Instale o XAMPP primeiro: winget install ApacheFriends.Xampp.8.2" -ForegroundColor Yellow
    exit 1
}

# Obter caminho atual do projeto
$projectPath = Get-Location
Write-Host "[INFO] Projeto localizado em: $projectPath" -ForegroundColor Cyan

# Criar diretórios necessários
$directories = @("storage/logs", "storage/cache", "storage/uploads")
foreach ($dir in $directories) {
    $fullPath = Join-Path $projectPath $dir
    if (!(Test-Path $fullPath)) {
        New-Item -ItemType Directory -Path $fullPath -Force | Out-Null
        Write-Host "[OK] Diretório criado: $dir" -ForegroundColor Green
    }
}

# Verificar arquivo .env
$envFile = Join-Path $projectPath ".env"
if (Test-Path $envFile) {
    Write-Host "[OK] Arquivo .env encontrado" -ForegroundColor Green
} else {
    Write-Host "[AVISO] Arquivo .env não encontrado!" -ForegroundColor Yellow
}

# Configurar VirtualHost
Write-Host ""
Write-Host "Configurando VirtualHost..." -ForegroundColor Yellow

$vhostConfig = @"

# UltraGestor VirtualHost
<VirtualHost *:80>
    ServerName ultragestor.local
    DocumentRoot "$projectPath/public"
    
    <Directory "$projectPath/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/ultragestor-error.log"
    CustomLog "logs/ultragestor-access.log" combined
</VirtualHost>
"@

$vhostFile = "C:\xampp\apache\conf\extra\httpd-vhosts.conf"

try {
    # Verificar se já existe configuração
    $existingContent = Get-Content $vhostFile -Raw -ErrorAction SilentlyContinue
    if ($existingContent -notmatch "ultragestor.local") {
        Add-Content -Path $vhostFile -Value $vhostConfig
        Write-Host "[OK] VirtualHost adicionado ao Apache" -ForegroundColor Green
    } else {
        Write-Host "[INFO] VirtualHost já configurado" -ForegroundColor Cyan
    }
} catch {
    Write-Host "[ERRO] Não foi possível configurar VirtualHost" -ForegroundColor Red
    Write-Host "Execute este script como Administrador" -ForegroundColor Yellow
}

# Configurar hosts file
Write-Host ""
Write-Host "Configurando arquivo hosts..." -ForegroundColor Yellow

$hostsFile = "C:\Windows\System32\drivers\etc\hosts"
$hostsEntry = "127.0.0.1 ultragestor.local"

try {
    $hostsContent = Get-Content $hostsFile -Raw -ErrorAction SilentlyContinue
    if ($hostsContent -notmatch "ultragestor.local") {
        Add-Content -Path $hostsFile -Value "`n$hostsEntry"
        Write-Host "[OK] Entrada adicionada ao arquivo hosts" -ForegroundColor Green
    } else {
        Write-Host "[INFO] Entrada já existe no arquivo hosts" -ForegroundColor Cyan
    }
} catch {
    Write-Host "[ERRO] Não foi possível modificar arquivo hosts" -ForegroundColor Red
    Write-Host "Execute este script como Administrador" -ForegroundColor Yellow
}

# Instruções finais
Write-Host ""
Write-Host "==================================" -ForegroundColor Cyan
Write-Host "  Setup Concluído!  " -ForegroundColor Green
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Abra o XAMPP Control Panel:" -ForegroundColor White
Write-Host "   C:\xampp\xampp-control.exe" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Inicie os serviços Apache e MySQL" -ForegroundColor White
Write-Host ""
Write-Host "3. Acesse o phpMyAdmin:" -ForegroundColor White
Write-Host "   http://localhost/phpmyadmin" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Crie o banco de dados:" -ForegroundColor White
Write-Host "   - Nome: ultragestor_php" -ForegroundColor Gray
Write-Host "   - Collation: utf8mb4_unicode_ci" -ForegroundColor Gray
Write-Host ""
Write-Host "5. Importe o schema:" -ForegroundColor White
Write-Host "   - Arquivo: database/schema.sql" -ForegroundColor Gray
Write-Host ""
Write-Host "6. Acesse o sistema:" -ForegroundColor White
Write-Host "   http://ultragestor.local" -ForegroundColor Cyan
Write-Host ""
Write-Host "Credenciais padrão:" -ForegroundColor Yellow
Write-Host "   Email: admin@ultragestor.com" -ForegroundColor Gray
Write-Host "   Senha: admin123" -ForegroundColor Gray
Write-Host ""
Write-Host "Pressione qualquer tecla para abrir o XAMPP Control Panel..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

Start-Process "C:\xampp\xampp-control.exe"
