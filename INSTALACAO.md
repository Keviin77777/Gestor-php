# 🚀 Guia de Instalação - UltraGestor

## ✅ Status Atual

- ✅ XAMPP 8.2 instalado
- ✅ Estrutura do projeto criada
- ✅ VirtualHost configurado
- ✅ Sistema de autenticação implementado
- ⏳ Banco de dados (precisa ser criado)

## 📝 Instalação Rápida

### Passo 1: Iniciar XAMPP

1. Abra o XAMPP Control Panel (já deve estar aberto)
2. Clique em **Start** no Apache
3. Clique em **Start** no MySQL

### Passo 2: Criar Banco de Dados

**Opção A - Via phpMyAdmin (Recomendado):**

1. Acesse: http://localhost/phpmyadmin
2. Clique em **Novo** (ou **New**) no menu lateral
3. Nome do banco: `ultragestor_php`
4. Collation: `utf8mb4_unicode_ci`
5. Clique em **Criar**
6. Clique na aba **Importar** (ou **Import**)
7. Escolha o arquivo: `database/schema.sql`
8. Clique em **Executar** (ou **Go**)

**Opção B - Via Linha de Comando:**

```bash
# Acessar MySQL
C:\xampp\mysql\bin\mysql.exe -u root

# Executar comandos
CREATE DATABASE ultragestor_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ultragestor_php;
SOURCE C:/Users/user/Documents/Projetos/Gestor-php/database/schema.sql;
EXIT;
```

### Passo 3: Configurar Hosts (Opcional)

Se o script não conseguiu adicionar automaticamente, faça manualmente:

1. Abra o Bloco de Notas **como Administrador**
2. Abra o arquivo: `C:\Windows\System32\drivers\etc\hosts`
3. Adicione no final:
   ```
   127.0.0.1 ultragestor.local
   ```
4. Salve o arquivo

### Passo 4: Reiniciar Apache

No XAMPP Control Panel:
1. Clique em **Stop** no Apache
2. Clique em **Start** no Apache

### Passo 5: Acessar o Sistema

Abra o navegador e acesse:

**Com VirtualHost:**
- http://ultragestor.local

**Sem VirtualHost:**
- http://localhost/Gestor-php/public

## 🔑 Credenciais Padrão

```
Email: admin@ultragestor.com
Senha: admin123
```

## 🎨 Estrutura Criada

```
Gestor-php/
├── .env                    ✅ Configurações
├── README.md              ✅ Documentação
├── INSTALACAO.md          ✅ Este arquivo
├── setup.ps1              ✅ Script de setup
├── public/
│   ├── index.php          ✅ Router principal
│   ├── .htaccess          ✅ Regras Apache
│   └── assets/
│       ├── css/
│       │   └── auth.css   ✅ Estilos de login
│       └── js/
│           └── auth.js    ✅ JavaScript de autenticação
├── app/
│   ├── core/
│   │   ├── Database.php   ✅ Conexão com banco
│   │   ├── Router.php     ✅ Sistema de rotas
│   │   ├── Request.php    ✅ Requisições HTTP
│   │   ├── Response.php   ✅ Respostas HTTP
│   │   └── Auth.php       ✅ Autenticação JWT
│   ├── api/
│   │   └── endpoints/
│   │       └── auth.php   ✅ API de autenticação
│   ├── views/
│   │   └── auth/
│   │       └── login.php  ✅ Tela de login
│   └── helpers/
│       └── functions.php  ✅ Funções auxiliares
├── database/
│   └── schema.sql         ✅ Schema do banco
└── storage/
    ├── logs/              ✅ Logs da aplicação
    ├── cache/             ✅ Cache
    └── uploads/           ✅ Uploads
```

## 🧪 Testar Instalação

### 1. Verificar Apache

Acesse: http://localhost

Deve aparecer a página padrão do XAMPP.

### 2. Verificar MySQL

Acesse: http://localhost/phpmyadmin

Deve aparecer o phpMyAdmin.

### 3. Verificar Projeto

Acesse: http://ultragestor.local (ou http://localhost/Gestor-php/public)

Deve aparecer a tela de login do UltraGestor.

### 4. Testar Login

1. Digite o email: `admin@ultragestor.com`
2. Digite a senha: `admin123`
3. Clique em **Entrar**

Se tudo estiver correto, você será redirecionado para o dashboard.

## ❌ Problemas Comuns

### Erro: "Erro ao conectar ao banco de dados"

**Solução:**
1. Verifique se o MySQL está rodando no XAMPP
2. Verifique as credenciais no arquivo `.env`
3. Verifique se o banco `ultragestor_php` foi criado

### Erro: "Página não encontrada" (404)

**Solução:**
1. Verifique se o Apache está rodando
2. Verifique se o arquivo `.htaccess` existe em `public/`
3. Verifique se o mod_rewrite está habilitado

### Erro: "Access Denied" no phpMyAdmin

**Solução:**
1. Use usuário: `root`
2. Deixe a senha em branco (padrão do XAMPP)

### Erro: "Cannot modify header information"

**Solução:**
1. Verifique se não há espaços ou BOM antes de `<?php`
2. Verifique se não há `echo` antes de `Response::json()`

## 📞 Próximos Passos

Após a instalação, você pode:

1. ✅ Fazer login no sistema
2. ⏳ Criar novos usuários (resellers)
3. ⏳ Adicionar clientes
4. ⏳ Gerar faturas
5. ⏳ Configurar métodos de pagamento
6. ⏳ Configurar WhatsApp
7. ⏳ Configurar Painel Sigma

## 🔧 Desenvolvimento

Para continuar o desenvolvimento, consulte:

- `README.md` - Documentação geral
- `.kiro/specs/php-pure-migration/requirements.md` - Requisitos
- `.kiro/specs/php-pure-migration/design.md` - Design
- `.kiro/specs/php-pure-migration/tasks.md` - Tarefas

## 📝 Notas

- O sistema está em **desenvolvimento**
- Apenas a autenticação está implementada
- Dashboard e outras funcionalidades serão implementadas nas próximas fases
- Consulte `tasks.md` para ver o progresso

---

**Desenvolvido com PHP puro + HTML/CSS + JavaScript vanilla**
