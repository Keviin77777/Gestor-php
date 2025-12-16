# 🚀 Instalação do Frontend

## Passos para configurar o frontend:

### 1. Instalar dependências

**Opção A - Windows (mais fácil):**
- Dê duplo clique no arquivo `install.bat` na pasta `frontend/`

**Opção B - Terminal:**
- Abra o terminal na pasta `frontend/`
- Execute: `npm install`

Isso vai instalar todas as dependências incluindo o `@types/node` que foi adicionado.

**⚠️ IMPORTANTE:** Aguarde a instalação terminar completamente antes de prosseguir!

### 2. Configurar variáveis de ambiente

O arquivo `.env` já está configurado com:

```
VITE_API_URL=http://localhost:8000
```

Se seu backend estiver em outra porta, edite este arquivo.

### 3. Iniciar o servidor de desenvolvimento

```bash
npm run dev
```

O frontend estará disponível em: **http://localhost:3000**

### 4. Fazer login

Use as credenciais de um usuário existente no banco de dados.

Se não tiver usuário, você pode criar um diretamente no banco ou usar o endpoint de registro.

## ✅ Pronto!

Após seguir esses passos, todos os erros de TypeScript devem desaparecer e o sistema estará funcionando corretamente.

## 🐛 Troubleshooting

Se ainda houver erros:

1. Delete a pasta `node_modules` e o arquivo `package-lock.json`
2. Execute `npm install` novamente
3. Reinicie o VS Code
4. Execute `npm run dev`
