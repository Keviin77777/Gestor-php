@echo off
echo Parando Servico de Automacao WhatsApp...
cd /d "%~dp0"
php scripts\whatsapp-service-control.php stop
pause
