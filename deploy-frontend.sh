#!/bin/bash

# 🚀 Script de Deploy Automático do Frontend React

echo "=================================="
echo "🚀 Deploy Frontend React"
echo "=================================="
echo ""

# Cores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

# 1. Atualizar código
echo -e "${BLUE}[1/5]${NC} Atualizando código do GitHub..."
git pull origin main

# 2. Build do React
echo -e "${BLUE}[2/5]${NC} Building React..."
cd frontend
npm run build

# 3. Remover arquivos antigos
echo -e "${BLUE}[3/5]${NC} Removendo arquivos antigos..."
rm -rf ../public/assets ../public/index.html

# 4. Copiar novo build
echo -e "${BLUE}[4/5]${NC} Copiando novo build..."
cp -r dist/* ../public/

# 5. Ajustar permissões
echo -e "${BLUE}[5/5]${NC} Ajustando permissões..."
chmod 644 ../public/index.html
chmod -R 755 ../public/assets
chown -R www-data:www-data ../public/index.html ../public/assets

cd ..

echo ""
echo -e "${GREEN}✅ Deploy concluído com sucesso!${NC}"
echo ""
echo "Acesse: https://ultragestor.site"
