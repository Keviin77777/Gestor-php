# ✅ Checklist de Instalação - UltraGestor

Use este checklist para garantir que tudo está configurado corretamente.

## 📦 Pré-requisitos

- [x] XAMPP 8.2 instalado
- [x] Projeto criado em: `C:\Users\user\Documents\Projetos\Gestor-php`
- [x] Estrutura de arquivos criada
- [x] VirtualHost configurado

## 🚀 Instalação

### 1. Iniciar Serviços XAMPP

- [ ] Abrir XAMPP Control Panel (`C:\xampp\xampp-control.exe`)
- [ ] Clicar em **Start** no Apache
- [ ] Clicar em **Start** no MySQL
- [ ] Verificar se ambos estão com status **Running** (verde)

### 2. Criar Banco de Dados

- [ ] Acessar phpMyAdmin: http://localhost/phpmyadmin
- [ ] Clicar em **Novo** no menu lateral
- [ ] Digitar nome: `ultragestor_php`
- [ ] Selecionar Collation: `utf8mb4_unicode_ci`
- [ ] Clicar em **Criar**

### 3. Importar Schema

- [ ] No phpMyAdmin, selecionar banco `ultragestor_php`
- [ ] Clicar na aba **Importar**
- [ ] Clicar em **Escolher arquivo**
- [ ] Selecionar: `C:\Users\user\Documents\Projetos\Gestor-php\database\schema.sql`
- [ ] Clicar em **Executar**
- [ ] Verificar mensagem de sucesso

### 4. Configurar Hosts (Opcional)

- [ ] Abrir Bloco de Notas **como Administrador**
- [ ] Abrir arquivo: `C:\Windows\System32\drivers\etc\hosts`
- [ ] Adicionar linha: `127.0.0.1 ultragestor.local`
- [ ] Salvar arquivo

### 5. Reiniciar Apache

- [ ] No XAMPP Control Panel, clicar em **Stop** no Apache
- [ ] Aguardar parar completamente
- [ ] Clicar em **Start** no Apache
- [ ] Verificar se iniciou corretamente

## 🧪 Testes

### Teste 1: Verificar Apache

- [ ] Acessar: http://localhost
- [ ] Deve aparecer a página padrão do XAMPP

### Teste 2: Verificar MySQL

- [ ] Acessar: http://localhost/phpmyadmin
- [ ] Deve aparecer o phpMyAdmin
- [ ] Verificar se banco `ultragestor_php` aparece na lista

### Teste 3: Verificar Instalação do Projeto

- [ ] Acessar: http://localhost/Gestor-php/public/test.php
- [ ] Verificar se todos os testes estão **verdes** (success)
- [ ] Se houver erros vermelhos, corrija antes de continuar

### Teste 4: Acessar Tela de Login

**Com VirtualHost:**
- [ ] Acessar: http://ultragestor.local
- [ ] Deve aparecer a tela de login do UltraGestor

**Sem VirtualHost:**
- [ ] Acessar: http://localhost/Gestor-php/public
- [ ] Deve aparecer a tela de login do UltraGestor

### Teste 5: Fazer Login

- [ ] Digitar email: `admin@ultragestor.com`
- [ ] Digitar senha: `admin123`
- [ ] Clicar em **Entrar**
- [ ] Deve redirecionar para `/dashboard` (ainda não implementado, mas não deve dar erro 500)

### Teste 6: Criar Nova Conta

- [ ] Acessar: http://ultragestor.local/register
- [ ] Preencher formulário:
  - Nome: Seu Nome
  - Email: seu@email.com
  - WhatsApp: (opcional)
  - Senha: mínimo 6 caracteres
  - Confirmar Senha: mesma senha
- [ ] Clicar em **Criar Conta Grátis**
- [ ] Deve criar conta e fazer login automaticamente
- [ ] Verificar mensagem: "Trial de 3 dias ativado"

## 🔍 Verificações Adicionais

### Arquivos Essenciais

- [x] `.env` - Configurações
- [x] `public/index.php` - Router principal
- [x] `public/.htaccess` - Regras Apache
- [x] `app/core/Database.php` - Conexão banco
- [x] `app/core/Auth.php` - Autenticação
- [x] `database/schema.sql` - Schema do banco

### Diretórios com Permissão de Escrita

- [x] `storage/logs/` - Logs da aplicação
- [x] `storage/cache/` - Cache
- [x] `storage/uploads/` - Uploads

### Extensões PHP Necessárias

- [x] PDO
- [x] pdo_mysql
- [x] json
- [x] mbstring
- [x] curl

## ❌ Problemas Comuns

### Erro: "Erro ao conectar ao banco de dados"

**Causa:** MySQL não está rodando ou credenciais incorretas

**Solução:**
1. Verificar se MySQL está rodando no XAMPP
2. Verificar credenciais no arquivo `.env`:
   ```
   DB_HOST=localhost
   DB_NAME=ultragestor_php
   DB_USER=root
   DB_PASS=
   ```

### Erro: "Página não encontrada" (404)

**Causa:** Apache não está rodando ou .htaccess não está funcionando

**Solução:**
1. Verificar se Apache está rodando
2. Verificar se arquivo `.htaccess` existe em `public/`
3. Verificar se mod_rewrite está habilitado

### Erro: "Credenciais inválidas"

**Causa:** Banco não foi importado ou senha incorreta

**Solução:**
1. Verificar se schema foi importado
2. Usar credenciais padrão:
   - Email: `admin@ultragestor.com`
   - Senha: `admin123`

### Erro: "Cannot modify header information"

**Causa:** Output antes de headers

**Solução:**
1. Verificar se não há espaços antes de `<?php`
2. Verificar se não há `echo` antes de `Response::json()`

## 📊 Status da Implementação

### ✅ Implementado (Fase 1)

- [x] Sistema de autenticação JWT
- [x] Tela de login
- [x] Tela de registro
- [x] API de login/registro
- [x] Classes core (Database, Router, Auth, Request, Response)
- [x] Schema do banco de dados
- [x] Sistema de logs
- [x] Validação e sanitização

### ⏳ Próximas Fases

- [ ] Dashboard com métricas (Fase 4)
- [ ] CRUD de clientes (Fase 5)
- [ ] Sistema de faturas (Fase 6)
- [ ] Métodos de pagamento (Fase 7)
- [ ] Checkout PIX (Fase 8)
- [ ] Automação WhatsApp (Fase 9)
- [ ] Integração Painel Sigma (Fase 10)
- [ ] Sistema de assinaturas (Fase 11)
- [ ] Relatórios (Fase 12)
- [ ] Responsividade mobile (Fase 13)

## 🎯 Próximos Passos

Após completar este checklist:

1. **Testar autenticação** - Login e registro funcionando
2. **Consultar tasks.md** - Ver próximas tarefas
3. **Implementar dashboard** - Fase 4 do projeto
4. **Adicionar funcionalidades** - Seguir ordem das fases

## 📞 Suporte

Se encontrar problemas:

1. Consulte `INSTALACAO.md` para instruções detalhadas
2. Consulte `README.md` para documentação geral
3. Verifique logs em `storage/logs/`
4. Execute `test.php` para diagnóstico

---

**Desenvolvido com PHP puro + HTML/CSS + JavaScript vanilla**

**Sem frameworks, sem dependências, 100% profissional!**
