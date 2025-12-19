@echo off
echo Processando fila de mensagens WhatsApp...
php scripts/process-queue.php
echo.
echo Processamento concluido!
pause
