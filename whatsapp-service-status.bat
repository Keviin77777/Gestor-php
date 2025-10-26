@echo off
echo Status do Servico de Automacao WhatsApp...
cd /d "%~dp0"
php scripts\whatsapp-service-control.php status
pause
