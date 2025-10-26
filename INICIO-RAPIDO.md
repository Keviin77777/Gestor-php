# 🚀 Início Rápido - UltraGestor

## ✅ Sistema Funcionando!

O UltraGestor está rodando com sucesso usando o servidor PHP embutido!

## 🎯 Como Usar

### Iniciar o Servidor

O servidor já está rodando! Se precisar reiniciar:

```powershell
# Na pasta do projeto
php -S localhost:8000 -t public
```

Ou use o script:
```powershell
.\start-server.ps1
```

### Acessar o Sistema

**Tela de Login:**
```
http://localhost:8000/login
```

**Criar Nova Conta:**
```
http://localhost:8000/register
```

**Teste de Instalação:**
```
http://localhost:8000/test.php
```

### Credenciais Padrão

```
Email: admin@ultragestor.com
Senha: admin123
```

## 🎨 Funcionalidades Implementadas

### ✅ Fase 1 - Autenticação (COMPLETO)

- [x] Sistema de login com JWT
- [x] Registro de novos usuários
- [x] Trial de 3 dias automático
- [x] Validação de formulários
- [x] Design profissional (CSS puro)
- [x] JavaScript vanilla (sem frameworks)
- [x] Banco de dados MySQL configurado
- [x] 10 tabelas criadas

## 📊 Banco de Dados

**Status:** ✅ Conectado e funcionando

**Banco:** ultragestor_php  
**Usuário:** root  
**Senha:** (vazio)  
**Porta:** 3306

**Tabelas criadas:**
- users (usuários/resellers)
- clients (clientes IPTV)
- invoices (faturas)
- payment_methods (métodos de pagamento)
- payment_transactions (transações PIX)
- whatsapp_templates (templates de mensagens)
- whatsapp_logs (logs de envio)
- panels (painéis Sigma)
- subscription_plans (planos de assinatura)
- audit_logs (logs de auditoria)

## 🧪 Testar o Sistema

### 1. Teste de Instalação

Acesse: http://localhost:8000/test.php

Deve mostrar todos os testes em verde ✅

### 2. Criar Nova Conta

1. Acesse: http://localhost:8000/register
2. Preencha:
   - Nome: Seu Nome
   - Email: seu@email.com
   - Senha: mínimo 6 caracteres
3. Clique em "Criar Conta Grátis"
4. Você será logado automaticamente
5. Trial de 3 dias será ativado

### 3. Fazer Login

1. Acesse: http://localhost:8000/login
2. Digite email e senha
3. Clique em "Entrar"
4. Será redirecionado para /dashboard (ainda não implementado)

## 🔄 Próximas Fases

### ⏳ Fase 4 - Dashboard (Próxima)

- [ ] Dashboard com métricas
- [ ] Cards de totais (clientes, receitas, lucros)
- [ ] Gráficos de desempenho
- [ ] Lista de clientes a vencer
- [ ] Atualização automática a cada 30s

### ⏳ Fase 5 - Gestão de Clientes

- [ ] Listar clientes
- [ ] Criar novo cliente
- [ ] Editar cliente
- [ ] Excluir cliente
- [ ] Busca e filtros
- [ ] Envio automático de WhatsApp

### ⏳ Fase 6 - Sistema de Faturas

- [ ] Listar faturas
- [ ] Gerar fatura
- [ ] Link de pagamento PIX
- [ ] Envio automático por WhatsApp
- [ ] Confirmação via webhook

## 💡 Dicas

### Parar o Servidor

Pressione `Ctrl + C` no terminal onde o servidor está rodando.

### Ver Logs

Os logs são salvos em:
```
storage/logs/error-YYYY-MM-DD.log
```

### Limpar Cache

```powershell
Remove-Item storage/cache/* -Force
```

### Backup do Banco

```bash
mysqldump -u root ultragestor_php > backup.sql
```

## 🐛 Problemas Comuns

### Erro: "Rota não encontrada"

**Solução:** Certifique-se de acessar com a barra final:
- ✅ http://localhost:8000/login
- ❌ http://localhost:8000login

### Erro: "Erro ao conectar ao banco"

**Solução:** Verifique se o MySQL está rodando:
```bash
mysql -u root -e "SELECT 'OK' as status;"
```

### CSS não carrega

**Solução:** Limpe o cache do navegador (Ctrl + Shift + R)

## 📚 Documentação

- **README.md** - Documentação completa
- **INSTALACAO.md** - Guia de instalação
- **CHECKLIST.md** - Checklist passo a passo
- **SOLUCAO-MYSQL.md** - Solução do problema MySQL
- **.kiro/specs/** - Requisitos e design completo

## 🚀 Deploy para VPS

Quando estiver pronto para produção:

1. Configure Apache/Nginx na VPS
2. Copie os arquivos do projeto
3. Configure o banco de dados
4. Atualize o arquivo `.env`
5. Configure SSL (Let's Encrypt)
6. Inicie os processadores Node.js (PM2)

Consulte **README.md** para instruções detalhadas de deploy.

## 🎉 Pronto!

O sistema está funcionando perfeitamente em modo de desenvolvimento!

Agora você pode:
1. ✅ Fazer login
2. ✅ Criar novas contas
3. ✅ Testar a autenticação
4. ⏳ Aguardar implementação do dashboard

**Próximo passo:** Implementar o Dashboard (Fase 4)

---

**Desenvolvido com PHP puro + HTML/CSS + JavaScript vanilla**

**Sem frameworks, sem dependências, 100% profissional!**
