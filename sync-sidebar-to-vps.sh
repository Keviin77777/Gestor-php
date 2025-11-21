#!/bin/bash

# Script para sincronizar sidebar para VPS

echo "=== Sincronizando Sidebar para VPS ==="

# Fazer commit das mudanças
git add app/views/components/sidebar.php
git commit -m "Fix: Ensure admin menu only shows for admin users"
git push origin main

echo ""
echo "✅ Código enviado para o repositório"
echo ""
echo "Agora na VPS, execute:"
echo ""
echo "cd /www/wwwroot/ultragestor.site/Gestor"
echo "git pull origin main"
echo "php -r \"if(function_exists('opcache_reset')) opcache_reset();\""
echo "systemctl restart php8.1-fpm"
echo ""
