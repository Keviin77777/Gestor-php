# 🚀 UltraGestor - Sistema de Gestão IPTV

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript&logoColor=black)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![WhatsApp](https://img.shields.io/badge/WhatsApp-Business-25D366?style=flat&logo=whatsapp&logoColor=white)

Sistema completo de gestão para provedores IPTV com integração WhatsApp, automação de lembretes e interface moderna.

## ✨ Funcionalidades

### 📊 Dashboard
- Visão geral de clientes, faturas e vencimentos
- Gráficos e estatísticas em tempo real
- Interface responsiva e moderna

### 👥 Gestão de Clientes
- CRUD completo de clientes
- Controle de planos e servidores
- Histórico de pagamentos
- Geração automática de credenciais

### 💰 Faturamento
- Geração automática de faturas
- Controle de vencimentos
- Histórico de pagamentos
- Relatórios financeiros

### 📱 WhatsApp Business
- **Pareamento automático** com QR Code
- **Templates personalizáveis** para diferentes situações
- **Agendamento inteligente** de mensagens
- **Automação completa** de lembretes

#### 🤖 Automação WhatsApp
- ✅ Lembretes de vencimento (7 dias, 3 dias)
- ✅ Notificações de vencimento (hoje)
- ✅ Cobrança pós-vencimento (1 dia, 3 dias)
- ✅ Confirmação de renovação
- ✅ Boas-vindas para novos clientes
- ✅ Agendamento por dias da semana e horários

## 🛠️ Tecnologias

- **Backend:** PHP 8.1+ (Vanilla)
- **Frontend:** HTML5, CSS3, JavaScript ES6+
- **Banco de Dados:** MySQL 8.0+
- **WhatsApp:** whatsapp-web.js (Node.js)
- **Servidor Web:** Apache/Nginx
- **Automação:** Cron Jobs + Systemd Services

## 🚀 Instalação

### Pré-requisitos
- PHP 8.1+ com extensões: mysql, curl, json, mbstring
- MySQL 8.0+
- Node.js 18+
- Apache/Nginx
- Composer (opcional)

### Deploy Automático
```bash
# Clone o repositório
git clone https://github.com/Keviin77777/Gestor-php.git
cd Gestor-php

# Execute o script de deploy
chmod +x scripts/deploy-production.sh
bash scripts/deploy-production.sh
```

### Instalação Manual
Consulte o arquivo [`DEPLOY-PRODUCTION.md`](DEPLOY-PRODUCTION.md) para instruções detalhadas.

## ⚙️ Configuração

### 1. Banco de Dados
```sql
CREATE DATABASE ultragestor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gestor_user'@'localhost' IDENTIFIED BY 'sua_senha';
GRANT ALL PRIVILEGES ON ultragestor.* TO 'gestor_user'@'localhost';
```

### 2. Arquivo .env
```env
# Banco de Dados
DB_HOST=localhost
DB_NAME=ultragestor
DB_USER=gestor_user
DB_PASS=sua_senha

# WhatsApp
WHATSAPP_SESSION_PATH=/caminho/para/sessions
WHATSAPP_WEBHOOK_URL=https://seudominio.com/api-whatsapp-webhook.php

# URLs
BASE_URL=https://seudominio.com
API_BASE_URL=https://seudominio.com
```

### 3. WhatsApp
```bash
# Instalar dependências
npm install whatsapp-web.js qrcode-terminal

# Configurar serviço
sudo systemctl enable whatsapp-gestor
sudo systemctl start whatsapp-gestor
```

## 📱 Configuração WhatsApp

### 1. Pareamento
1. Acesse **WhatsApp → Parear WhatsApp**
2. Escaneie o QR Code com seu WhatsApp Business
3. Aguarde a confirmação de conexão

### 2. Templates
1. Acesse **WhatsApp → Templates**
2. Configure os templates para cada situação
3. Personalize as mensagens com variáveis

### 3. Agendamentos
1. Acesse **WhatsApp → Agendamentos**
2. Configure dias da semana e horários
3. Ative os templates desejados

## 🔧 Uso

### Dashboard
- Visualize estatísticas gerais
- Monitore vencimentos próximos
- Acompanhe faturamento

### Clientes
- **Adicionar:** Botão "Novo Cliente"
- **Editar:** Clique no ícone de edição
- **Faturar:** Botão "Gerar Fatura"
- **WhatsApp:** Envio manual de mensagens

### WhatsApp
- **Automático:** Mensagens enviadas conforme agendamento
- **Manual:** Envio direto pela interface
- **Monitoramento:** Logs detalhados de envios

## 📊 Monitoramento

### Logs
```bash
# Logs da aplicação
tail -f logs/app.log

# Logs do WhatsApp
sudo journalctl -u whatsapp-gestor -f

# Status dos serviços
php scripts/whatsapp-service-control.php status
```

### Comandos Úteis
```bash
# Testar automação
php scripts/whatsapp-service-control.php run

# Reiniciar WhatsApp
sudo systemctl restart whatsapp-gestor

# Verificar conexão
curl -I https://seudominio.com
```

## 🔒 Segurança

- ✅ Autenticação JWT
- ✅ Proteção CSRF
- ✅ Sanitização de dados
- ✅ Arquivos sensíveis protegidos
- ✅ SSL/HTTPS obrigatório
- ✅ Firewall configurado

## 📋 Estrutura do Projeto

```
Gestor-php/
├── app/
│   ├── controllers/     # Controladores
│   ├── models/         # Modelos de dados
│   ├── views/          # Views/Templates
│   ├── helpers/        # Funções auxiliares
│   └── core/           # Classes principais
├── public/
│   ├── assets/         # CSS, JS, imagens
│   ├── api-*.php       # APIs REST
│   └── index.php       # Ponto de entrada
├── database/
│   ├── schema.sql      # Estrutura do banco
│   └── migrations/     # Migrações
├── scripts/
│   ├── deploy-production.sh
│   └── whatsapp-*.php  # Scripts de automação
├── logs/               # Arquivos de log
└── docs/               # Documentação
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch: `git checkout -b feature/nova-funcionalidade`
3. Commit suas mudanças: `git commit -m 'Adiciona nova funcionalidade'`
4. Push para a branch: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

## 📝 Changelog

### v2.0.0 (2024-10-25)
- ✨ Sistema completo de agendamento WhatsApp
- 🎨 Interface moderna e responsiva
- 🤖 Automação inteligente de lembretes
- 📱 Templates personalizáveis
- 🔧 Deploy automatizado

### v1.0.0 (2024-10-01)
- 🚀 Versão inicial
- 👥 Gestão de clientes
- 💰 Sistema de faturamento
- 📱 Integração WhatsApp básica

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 🆘 Suporte

- 📧 **Email:** suporte@ultragestor.com
- 💬 **WhatsApp:** +55 (11) 99999-9999
- 🐛 **Issues:** [GitHub Issues](https://github.com/Keviin77777/Gestor-php/issues)
- 📖 **Docs:** [Documentação Completa](docs/)

## 🏆 Créditos

Desenvolvido com ❤️ por [Kevin](https://github.com/Keviin77777)

---

⭐ **Se este projeto te ajudou, deixe uma estrela!** ⭐