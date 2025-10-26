@echo off
echo Iniciando Servico de Automacao WhatsApp...
cd /d "%~dp0"
php scripts\whatsapp-service-control.php start
pause
