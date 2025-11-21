#!/bin/bash

# Script para sincronizar arquivos CSS para produção
# Execute: bash scripts/sync-css-to-production.sh

echo "🚀 Sincronizando arquivos CSS para produção..."

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar se estamos no diretório correto
if [ ! -d "public/assets/css" ]; then
    echo -e "${RED}❌ Erro: Diretório public/assets/css não encontrado${NC}"
    echo "Execute este script da raiz do projeto"
    exit 1
fi

# Listar arquivos CSS que serão enviados
echo -e "${YELLOW}📋 Arquivos CSS a serem sincronizados:${NC}"
find public/assets/css -name "*.css" -type f

# Adicionar ao Git
echo -e "\n${YELLOW}📦 Adicionando arquivos ao Git...${NC}"
git add public/assets/css/*.css

# Verificar se há mudanças
if git diff --cached --quiet; then
    echo -e "${YELLOW}⚠️  Nenhuma mudança detectada nos arquivos CSS${NC}"
else
    # Commit
    echo -e "${YELLOW}💾 Fazendo commit...${NC}"
    git commit -m "Fix: Sincronizar arquivos CSS para produção"
    
    # Push
    echo -e "${YELLOW}🚀 Enviando para repositório...${NC}"
    git push
    
    echo -e "${GREEN}✅ Arquivos CSS enviados com sucesso!${NC}"
fi

echo -e "\n${YELLOW}📝 Próximos passos na VPS:${NC}"
echo "1. Conecte-se à VPS via SSH"
echo "2. Execute:"
echo "   cd /www/wwwroot/ultragestor.site/Gestor"
echo "   git pull"
echo "   ls -la public/assets/css/"
echo ""
echo -e "${GREEN}✅ Pronto!${NC}"
