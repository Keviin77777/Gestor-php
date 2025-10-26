# 🔧 Configuração Git - UltraGestor

## 📋 Comandos para Conectar ao GitHub

### 1. Inicializar Git (se ainda não foi feito)
```bash
git init
```

### 2. Configurar usuário Git (primeira vez)
```bash
git config --global user.name "Seu Nome"
git config --global user.email "seu-email@gmail.com"
```

### 3. Adicionar arquivos ao Git
```bash
# Adicionar todos os arquivos
git add .

# Ou adicionar arquivos específicos
git add README.md
git add .gitignore
git add "app/"
```

### 4. Fazer o primeiro commit
```bash
git commit -m "🚀 Initial commit - UltraGestor v2.0"
```

### 5. Conectar ao repositório GitHub
```bash
git remote add origin https://github.com/Keviin77777/Gestor-php.git
```

### 6. Verificar se o remote foi adicionado
```bash
git remote -v
```

### 7. Fazer push para o GitHub
```bash
# Primeira vez (criar branch main)
git branch -M main
git push -u origin main

# Próximas vezes
git push
```

## 🔄 Comandos Úteis para o Dia a Dia

### Verificar status
```bash
git status
```

### Ver diferenças
```bash
git diff
```

### Ver histórico
```bash
git log --oneline
```

### Adicionar e commitar em um comando
```bash
git commit -am "📝 Mensagem do commit"
```

### Criar nova branch
```bash
git checkout -b feature/nova-funcionalidade
```

### Trocar de branch
```bash
git checkout main
git checkout feature/nova-funcionalidade
```

### Merge de branch
```bash
git checkout main
git merge feature/nova-funcionalidade
```

### Atualizar do GitHub
```bash
git pull origin main
```

## 📝 Padrões de Commit

Use emojis para deixar os commits mais organizados:

- 🚀 `:rocket:` - Deploy/Release
- ✨ `:sparkles:` - Nova funcionalidade
- 🐛 `:bug:` - Correção de bug
- 📝 `:memo:` - Documentação
- 🎨 `:art:` - Melhorias de UI/UX
- ⚡ `:zap:` - Performance
- 🔧 `:wrench:` - Configuração
- 🔒 `:lock:` - Segurança
- 🗃️ `:card_file_box:` - Banco de dados
- 📱 `:iphone:` - WhatsApp/Mobile

### Exemplos:
```bash
git commit -m "✨ Adiciona sistema de agendamento WhatsApp"
git commit -m "🐛 Corrige cálculo de dias de vencimento"
git commit -m "🎨 Melhora UI do modal de agendamento"
git commit -m "📝 Atualiza documentação de deploy"
```

## 🚨 Problemas Comuns

### Erro de autenticação
Se der erro de autenticação, use token pessoal:

1. Vá em GitHub → Settings → Developer settings → Personal access tokens
2. Gere um novo token com permissões de repo
3. Use o token como senha:

```bash
git remote set-url origin https://SEU_USERNAME:SEU_TOKEN@github.com/Keviin77777/Gestor-php.git
```

### Arquivo muito grande
Se algum arquivo for muito grande (>100MB):

```bash
# Ver arquivos grandes
find . -size +50M -type f

# Remover do Git
git rm --cached arquivo-grande.zip
echo "arquivo-grande.zip" >> .gitignore
git commit -m "🗑️ Remove arquivo grande"
```

### Conflitos de merge
```bash
# Ver arquivos com conflito
git status

# Editar arquivos manualmente ou usar ferramenta
git mergetool

# Após resolver conflitos
git add .
git commit -m "🔀 Resolve conflitos de merge"
```

## 📂 Estrutura de Branches

### Branch Principal
- `main` - Código em produção

### Branches de Desenvolvimento
- `develop` - Desenvolvimento ativo
- `feature/nome-da-funcionalidade` - Novas funcionalidades
- `bugfix/nome-do-bug` - Correções
- `hotfix/nome-do-hotfix` - Correções urgentes

### Exemplo de Workflow
```bash
# Criar nova funcionalidade
git checkout main
git pull origin main
git checkout -b feature/whatsapp-agendamento

# Desenvolver...
git add .
git commit -m "✨ Adiciona agendamento de mensagens"

# Finalizar
git checkout main
git merge feature/whatsapp-agendamento
git push origin main
git branch -d feature/whatsapp-agendamento
```

## 🎯 Checklist de Push

Antes de fazer push, verifique:

- [ ] Código testado localmente
- [ ] Sem arquivos sensíveis (.env, senhas)
- [ ] .gitignore atualizado
- [ ] Commit com mensagem clara
- [ ] Sem conflitos
- [ ] Documentação atualizada se necessário

## 🔄 Comandos Completos para Este Projeto

Execute na ordem:

```bash
# 1. Configurar Git (primeira vez)
git config --global user.name "Kevin"
git config --global user.email "seu-email@gmail.com"

# 2. Inicializar (se necessário)
git init

# 3. Adicionar arquivos
git add .

# 4. Primeiro commit
git commit -m "🚀 UltraGestor v2.0 - Sistema completo com WhatsApp"

# 5. Conectar ao GitHub
git remote add origin https://github.com/Keviin77777/Gestor-php.git

# 6. Push inicial
git branch -M main
git push -u origin main
```

Pronto! Seu projeto estará no GitHub! 🎉