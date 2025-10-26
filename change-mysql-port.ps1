# Script para mudar porta do MySQL do XAMPP
# Alternativa: usar porta 3307 em vez de 3306

Write-Host "Configurando MySQL do XAMPP para usar porta 3307..." -ForegroundColor Yellow
Write-Host ""

$mysqlConfigFile = "C:\xampp\mysql\bin\my.ini"

if (Test-Path $mysqlConfigFile) {
    Write-Host "Fazendo backup do arquivo de configuração..." -ForegroundColor Cyan
    Copy-Item $mysqlConfigFile "$mysqlConfigFile.backup" -Force
    Write-Host "[OK] Backup criado: my.ini.backup" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "Alterando porta de 3306 para 3307..." -ForegroundColor Cyan
    
    $content = Get-Content $mysqlConfigFile -Raw
    $content = $content -replace 'port\s*=\s*3306', 'port=3307'
    $content = $content -replace 'port\s*=\s*33060', 'port=33070'
    Set-Content $mysqlConfigFile -Value $content
    
    Write-Host "[OK] Configuração alterada!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Agora você precisa:" -ForegroundColor Yellow
    Write-Host "1. Iniciar o MySQL no XAMPP Control Panel" -ForegroundColor White
    Write-Host "2. Atualizar o arquivo .env do projeto:" -ForegroundColor White
    Write-Host "   DB_PORT=3307" -ForegroundColor Cyan
    Write-Host ""
    
    # Atualizar .env automaticamente
    $envFile = "C:\Users\user\Documents\Projetos\Gestor-php\.env"
    if (Test-Path $envFile) {
        Write-Host "Atualizando arquivo .env do projeto..." -ForegroundColor Cyan
        $envContent = Get-Content $envFile -Raw
        $envContent = $envContent -replace 'DB_PORT=3306', 'DB_PORT=3307'
        Set-Content $envFile -Value $envContent
        Write-Host "[OK] Arquivo .env atualizado!" -ForegroundColor Green
    }
    
} else {
    Write-Host "[ERRO] Arquivo de configuração não encontrado!" -ForegroundColor Red
    Write-Host "Caminho esperado: $mysqlConfigFile" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Pressione qualquer tecla para fechar..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
