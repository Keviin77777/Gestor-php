# 🎯 UltraGestor - Sistema de Gestão IPTV

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white)
![React](https://img.shields.io/badge/React-18+-61DAFB?style=flat&logo=react&logoColor=black)
![TypeScript](https://img.shields.io/badge/TypeScript-5+-3178C6?style=flat&logo=typescript&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![WhatsApp](https://img.shields.io/badge/WhatsApp-Business-25D366?style=flat&logo=whatsapp&logoColor=white)

Sistema completo de gestão para revendedores IPTV com frontend React moderno e backend PHP robusto.

---

## 🚀 Tecnologias

### Frontend
- **React 18** com TypeScript
- **Vite** para build ultrarrápido
- **TailwindCSS** para estilização
- **Zustand** para gerenciamento de estado
- **React Router** para navegação
- **Axios** para requisições HTTP

### Backend
- **PHP 8+** com arquitetura MVC
- **MySQL 8+** para banco de dados
- **JWT** para autenticação
- **PDO** com prepared statements

### Integrações
- **WhatsApp API** (Node.js + whatsapp-web.js)
- **Mercado Pago** para pagamentos
- **Asaas** gateway de pagamento
- **EFI Bank** (Gerencianet)
- **Ciabra** para PIX
- **Sigma IPTV** sincronização

---

## ✨ Funcionalidades

### 👥 Gestão de Clientes
- ✅ Cadastro completo de clientes
- ✅ Importação em massa (Excel/CSV)
- ✅ Sincronização automática com Sigma IPTV
- ✅ Controle de status e renovações
- ✅ Histórico de pagamentos

### 💰 Financeiro Completo
- ✅ Geração automática de faturas
- ✅ Múltiplos métodos de pagamento (PIX, Boleto, Cartão)
- ✅ Relatórios financeiros detalhados
- ✅ Gráficos de receita e despesas
- ✅ Controle de inadimplência
- ✅ Análise de crescimento mensal/anual

### 📱 WhatsApp Automático
- ✅ Envio automático de credenciais
- ✅ Lembretes de vencimento personalizáveis
- ✅ Templates de mensagens
- ✅ Fila de mensagens inteligente
- ✅ Agendamento de envios
- ✅ Histórico completo

### 📊 Dashboard e Relatórios
- ✅ Métricas em tempo real
- ✅ Gráficos interativos
- ✅ Relatórios mensais detalhados
- ✅ Análise de crescimento
- ✅ Clientes expirando
- ✅ Inadimplência

### 🔐 Sistema de Revendas (Admin)
- ✅ Gestão de revendedores
- ✅ Planos de assinatura
- ✅ Renovação automática
- ✅ Notificações WhatsApp
- ✅ Histórico de pagamentos
- ✅ Controle de acesso

### 🎨 Interface Moderna
- ✅ Design responsivo (Mobile/Desktop)
- ✅ Modo escuro/claro
- ✅ Animações suaves
- ✅ UX otimizada
- ✅ Performance otimizada

---

## 📋 Pré-requisitos

- PHP 8.0 ou superior
- MySQL 8.0 ou superior
- Node.js 18 ou superior
- npm ou yarn
- Apache ou Nginx
- Git

---

## 🛠️ Instalação Local (Desenvolvimento)

### 1. Clonar Repositório

```bash
git clone https://github.com/SEU-USUARIO/ultragestor.git
cd ultragestor
```

### 2. Configurar Backend (PHP)

```bash
# Copiar arquivo de configuração
cp .env.example .env

# Editar .env com suas configurações
nano .env

# Criar database
mysql -u root -p
CREATE DATABASE ultragestor_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Importar schema
mysql -u root -p ultragestor_php < database/schema.sql
```

### 3. Configurar Frontend (React)

```bash
cd frontend

# Copiar configuração
cp .env.example .env

# Instalar dependências
npm install

# Iniciar servidor de desenvolvimento
npm run dev
```

Acesse: `http://localhost:5173`

### 4. Configurar WhatsApp API

```bash
cd whatsapp-api

# Copiar configuração
cp .env.example .env

# Instalar dependências
npm install

# Iniciar API
npm start
```

API rodando em: `http://localhost:3000`

---

## 🚀 Deploy em Produção

### Guia Completo

Consulte o guia detalhado: **[DEPLOY-PRODUCTION.md](DEPLOY-PRODUCTION.md)**

### Deploy Rápido

```bash
# 1. Clonar no servidor
git clone https://github.com/SEU-USUARIO/ultragestor.git
cd ultragestor

# 2. Configurar .env
cp .env.example .env
nano .env

# 3. Importar database
mysql -u root -p ultragestor_php < database/schema-production.sql

# 4. Tornar script executável
chmod +x deploy.sh

# 5. Executar deploy
./deploy.sh
```

---

## 📁 Estrutura do Projeto

```
ultragestor/
├── 📂 app/                     # Backend PHP
│   ├── api/                   # Endpoints da API
│   ├── core/                  # Classes principais (Auth, Database, etc)
│   ├── helpers/               # Funções auxiliares
│   └── views/                 # Views PHP (sistema legado)
│
├── 📂 frontend/                # Frontend React + TypeScript
│   ├── src/
│   │   ├── components/       # Componentes reutilizáveis
│   │   ├── pages/            # Páginas da aplicação
│   │   ├── services/         # Serviços de API
│   │   ├── stores/           # Gerenciamento de estado (Zustand)
│   │   ├── hooks/            # Custom hooks
│   │   └── types/            # TypeScript types
│   ├── dist/                 # Build de produção (gerado)
│   └── .env                  # Configurações do frontend
│
├── 📂 public/                  # Arquivos públicos
│   ├── api-*.php             # APIs PHP
│   ├── assets/               # CSS/JS do sistema legado
│   ├── app/                  # Build React (produção)
│   └── .htaccess             # Configuração Apache
│
├── 📂 whatsapp-api/            # API WhatsApp (Node.js)
│   ├── src/                  # Código fonte
│   ├── sessions/             # Sessões WhatsApp
│   └── .env                  # Configurações da API
│
├── 📂 database/                # Schemas e migrações
│   ├── schema.sql            # Schema desenvolvimento
│   ├── schema-production.sql # Schema produção
│   └── complete-schema.sql   # Schema completo
│
├── 📂 scripts/                 # Scripts de automação
│   ├── process-queue.php     # Processar fila WhatsApp
│   ├── invoice-automation-cron.php
│   └── reseller-renewal-automation.php
│
├── 📂 logs/                    # Logs do sistema
│
├── 📄 .env                     # Configurações backend
├── 📄 .env.example             # Exemplo de configurações
├── 📄 deploy.sh                # Script de deploy
├── 📄 DEPLOY-PRODUCTION.md     # Guia de deploy
└── 📄 README.md                # Este arquivo
```

---

## 🔒 Segurança

### Implementações de Segurança

- ✅ **Autenticação JWT** com tokens seguros
- ✅ **Prepared Statements** (proteção contra SQL Injection)
- ✅ **CORS** configurado corretamente
- ✅ **Validação de Roles** (Admin/Reseller)
- ✅ **Sanitização de Inputs** em todas as entradas
- ✅ **HTTPS** obrigatório em produção
- ✅ **Rate Limiting** nas APIs
- ✅ **Logs de Auditoria**
- ✅ **Senhas Hasheadas** (bcrypt)
- ✅ **Proteção CSRF**

### Arquivos Removidos (Segurança)

Durante a auditoria, foram removidos 20 arquivos perigosos:
- phpinfo.php (expunha configurações)
- force-login.php (bypass de autenticação)
- decode-token.php (token hardcoded)
- Arquivos de teste e debug
- Scripts de migração em produção

---

## 📝 Variáveis de Ambiente

### Backend (.env)

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ultragestor_php
DB_USER=root
DB_PASS=senha_segura

# JWT
JWT_SECRET=chave_secreta_muito_forte_minimo_32_caracteres

# URLs
APP_URL=https://seudominio.com
FRONTEND_URL=https://seudominio.com

# WhatsApp
EVOLUTION_API_URL=http://localhost:8081
EVOLUTION_API_KEY=sua_chave

# Pagamentos
MERCADOPAGO_ACCESS_TOKEN=seu_token
ASAAS_API_KEY=seu_token
EFIBANK_CLIENT_ID=seu_client_id
CIABRA_API_KEY=sua_chave
```

### Frontend (frontend/.env)

```env
VITE_API_URL=https://seudominio.com
VITE_APP_NAME=UltraGestor
```

### WhatsApp API (whatsapp-api/.env)

```env
PORT=3000
DB_HOST=localhost
DB_NAME=ultragestor_php
DB_USER=root
DB_PASS=senha
SESSION_PATH=./sessions
```

---

## �G Atualizações

### Atualizar Sistema

```bash
cd /var/www/ultragestor
git pull origin main
./deploy.sh
```

### Atualizar Apenas Frontend

```bash
cd frontend
npm run build
cp -r dist/* ../public/app/
```

### Atualizar Apenas WhatsApp API

```bash
cd whatsapp-api
npm install
pm2 restart whatsapp-api
```

---

## 📊 Monitoramento

### Verificar Logs

```bash
# Logs da aplicação
tail -f logs/*.log

# Logs do WhatsApp API
pm2 logs whatsapp-api

# Logs do Apache
tail -f /var/log/apache2/error.log

# Logs do Nginx
tail -f /var/log/nginx/error.log
```

### Status dos Serviços

```bash
# WhatsApp API
pm2 status

# Apache
sudo systemctl status apache2

# Nginx
sudo systemctl status nginx

# MySQL
sudo systemctl status mysql
```

---

## 🔧 Cron Jobs

Configurar no servidor:

```bash
crontab -e
```

Adicionar:

```cron
# Processar fila WhatsApp (a cada minuto)
* * * * * php /var/www/ultragestor/scripts/process-queue.php >> /var/www/ultragestor/logs/queue.log 2>&1

# Automação de faturas (todo dia às 9h)
0 9 * * * php /var/www/ultragestor/scripts/invoice-automation-cron.php >> /var/www/ultragestor/logs/invoices.log 2>&1

# Renovação de revendedores (todo dia às 10h)
0 10 * * * php /var/www/ultragestor/scripts/reseller-renewal-automation.php >> /var/www/ultragestor/logs/resellers.log 2>&1

# Processar mensagens pendentes (a cada 5 minutos)
*/5 * * * * php /var/www/ultragestor/scripts/process-pending-messages.php >> /var/www/ultragestor/logs/pending.log 2>&1
```

---

## 🆘 Troubleshooting

### Erro de Permissão

```bash
sudo chown -R www-data:www-data /var/www/ultragestor
sudo chmod -R 755 /var/www/ultragestor
chmod 777 whatsapp-api/sessions
chmod 777 logs
```

### WhatsApp API não inicia

```bash
pm2 delete whatsapp-api
cd whatsapp-api
pm2 start src/server.js --name whatsapp-api
pm2 save
```

### Erro de Database

```bash
mysql -u root -p ultragestor_php < database/schema-production.sql
```

### React não carrega

```bash
cd frontend
rm -rf node_modules dist
npm install
npm run build
cp -r dist/* ../public/app/
```

---

## 📚 Documentação Adicional

- [Guia de Deploy](DEPLOY-PRODUCTION.md)
- [Configuração de Pagamentos](docs/PAYMENTS.md) *(em breve)*
- [API Documentation](docs/API.md) *(em breve)*
- [WhatsApp Integration](docs/WHATSAPP.md) *(em breve)*

---

## 🤝 Contribuindo

Este é um projeto proprietário. Para contribuições, entre em contato.

---

## 📄 Licença

Proprietário - Todos os direitos reservados © 2024

---

## 👨‍💻 Desenvolvedor

**Kevin Souza**
- 📧 Email: souzaszkeviin@gmail.com
- 💼 GitHub: [@kevinsouza](https://github.com/kevinsouza)
- 📱 WhatsApp: +55 14 99734-9352

---

## 🎉 Agradecimentos

Obrigado por usar o UltraGestor! 

Para suporte, abra uma issue ou entre em contato.

---

**Versão:** 2.0.0 (React + PHP)  
**Última atualização:** Dezembro 2025
