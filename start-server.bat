@echo off
echo ========================================
echo  Iniciando Servidor PHP com CORS
echo ========================================
echo.
echo Servidor rodando em: http://localhost:8000
echo Pressione Ctrl+C para parar
echo.
echo IMPORTANTE: Certifique-se de fazer login novamente
echo para obter um token JWT valido!
echo.

cd public
php -S localhost:8000 router.php
