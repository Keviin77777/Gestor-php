@echo off
echo ========================================
echo   UltraGestor - Servidor de Desenvolvimento
echo ========================================
echo.

REM Verificar se a porta 8000 está em uso
netstat -ano | findstr :8000 > nul
if %errorlevel% equ 0 (
    echo [AVISO] Porta 8000 ja esta em uso!
    echo.
    choice /C SN /M "Deseja parar o processo e continuar"
    if errorlevel 2 goto :end
    if errorlevel 1 (
        for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000') do (
            taskkill /F /PID %%a > nul 2>&1
        )
        timeout /t 2 > nul
    )
)

echo [INFO] Iniciando servidor PHP na porta 8000...
echo.
echo Servidor rodando em:
echo   - http://localhost:8000
echo   - http://127.0.0.1:8000
echo.
echo Pressione Ctrl+C para parar o servidor
echo ========================================
echo.

cd /d "%~dp0"
php -S localhost:8000 -t public

:end
