# Servidor PHP Embutido - Alternativa ao Apache
# Execute este script para iniciar o servidor de desenvolvimento

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                                                            ║" -ForegroundColor Cyan
Write-Host "║          🚀 Iniciando Servidor PHP Embutido 🚀            ║" -ForegroundColor Cyan
Write-Host "║                                                            ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$projectPath = Get-Location
$publicPath = Join-Path $projectPath "public"

if (!(Test-Path $publicPath)) {
    Write-Host "[ERRO] Pasta public não encontrada!" -ForegroundColor Red
    Write-Host "Execute este script na raiz do projeto" -ForegroundColor Yellow
    exit 1
}

Write-Host "[INFO] Iniciando servidor em: $publicPath" -ForegroundColor Cyan
Write-Host ""
Write-Host "Servidor rodando em:" -ForegroundColor Green
Write-Host "   http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "Acesse:" -ForegroundColor Yellow
Write-Host "   http://localhost:8000/test.php - Teste de instalação" -ForegroundColor White
Write-Host "   http://localhost:8000/login - Tela de login" -ForegroundColor White
Write-Host "   http://localhost:8000/ - Sistema" -ForegroundColor White
Write-Host ""
Write-Host "Pressione Ctrl+C para parar o servidor" -ForegroundColor Gray
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Gray
Write-Host ""

# Iniciar servidor PHP
Set-Location $publicPath
php -S localhost:8000
