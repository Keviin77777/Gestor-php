# Implementation Plan - Sistema PHP Puro (Desenvolvimento Direto na VPS)

## Fase 1: Setup Inicial na VPS

- [ ] 1. Preparar ambiente VPS
  - Conectar via SSH na VPS
  - Verificar versões: PHP 8.0+, MySQL 5.7+, Apache/Nginx
  - Instalar extensões PHP necessárias (pdo_mysql, curl, json, mbstring, openssl)
  - Configurar timezone e locale
  - _Requirements: 12.1, 12.2_

- [ ] 1.1 Criar estrutura de diretórios do projeto
  - Criar `/var/www/ultragestor-php/` como raiz do projeto
  - Criar estrutura completa: `public/`, `app/`, `database/`, `storage/`, `scripts/`
  - Configurar permissões corretas (www-data para storage e cache)
  - Criar arquivo `.env` com variáveis de ambiente
  - _Requirements: 12.1_

- [ ] 1.2 Configurar servidor web (Apache ou Nginx)
  - Criar VirtualHost/Server Block para o domínio
  - Configurar DocumentRoot para `/var/www/ultragestor-php/public`
  - Habilitar mod_rewrite (Apache) ou configurar try_files (Nginx)
  - Criar arquivo `.htaccess` com regras de rewrite
  - Testar acesso ao domínio
  - _Requirements: 12.1_

- [ ] 1.3 Configurar banco de dados MySQL
  - Criar database `ultragestor_php`
  - Criar usuário com permissões adequadas
  - Importar schema base (tabelas principais)
  - Testar conexão via PHP
  - _Requirements: 12.2_

## Fase 2: Core do Sistema (Backend)

- [ ] 2. Implementar classes core fundamentais
  - Criar `app/core/Database.php` com PDO e prepared statements
  - Criar `app/core/Router.php` para gerenciamento de rotas
  - Criar `app/core/Request.php` para manipulação de requisições
  - Criar `app/core/Response.php` para respostas JSON/HTML
  - Testar cada classe isoladamente
  - _Requirements: 12.2, 12.3_

- [ ] 2.1 Implementar sistema de autenticação JWT
  - Criar `app/core/Auth.php` com geração e validação de tokens
  - Implementar hash de senhas com bcrypt/argon2
  - Criar middleware de autenticação
  - Criar sistema de sessões
  - Testar login/logout básico
  - _Requirements: 1.1, 1.3, 12.4_

- [ ] 2.2 Implementar validação e sanitização
  - Criar `app/core/Validator.php` com regras de validação
  - Criar `app/helpers/sanitize.php` com funções de limpeza
  - Implementar validação de email, telefone, CPF, datas
  - Testar com dados maliciosos (XSS, SQL Injection)
  - _Requirements: 12.1, 12.2_

- [ ] 2.3 Implementar sistema de cache
  - Criar `app/core/Cache.php` com cache em arquivo
  - Implementar get/set/delete com TTL
  - Criar limpeza automática de cache expirado
  - Testar performance com e sem cache
  - _Requirements: Performance_

## Fase 3: Autenticação e Usuários

- [ ] 3. Criar sistema de autenticação completo
  - Criar `app/controllers/AuthController.php`
  - Criar `app/models/User.php` com métodos CRUD
  - Implementar registro com trial de 3 dias
  - Implementar login com validação de credenciais
  - Implementar logout com invalidação de token
  - _Requirements: 1.1, 1.2, 1.3, 1.5_

- [ ] 3.1 Criar views de autenticação
  - Criar `app/views/auth/login.php` com formulário HTML
  - Criar `app/views/auth/register.php` com validação frontend
  - Criar `app/views/auth/forgot-password.php`
  - Criar `app/views/auth/reset-password.php`
  - Estilizar com CSS puro (sem frameworks)
  - _Requirements: 1.1, 1.4, 1.5_

- [ ] 3.2 Implementar recuperação de senha
  - Criar tabela `password_reset_tokens`
  - Implementar geração de token único com expiração
  - Integrar envio de email com PHPMailer
  - Criar fluxo completo: solicitar → email → resetar
  - Testar com email real
  - _Requirements: 1.4, 14.1, 14.2, 14.3_

- [ ] 3.3 Criar JavaScript de autenticação
  - Criar `public/assets/js/auth.js` com gerenciamento de token
  - Implementar armazenamento de token no localStorage
  - Criar interceptor para adicionar token em requisições
  - Implementar redirecionamento automático se não autenticado
  - _Requirements: 1.1, 1.2_

## Fase 4: Dashboard e Métricas

- [ ] 4. Criar dashboard principal
  - Criar `app/controllers/DashboardController.php`
  - Implementar cálculo de métricas (clientes, receitas, lucros)
  - Criar queries otimizadas com índices
  - Implementar cache de métricas (5 minutos)
  - _Requirements: 2.1, 2.2, 2.3_

- [ ] 4.1 Criar view do dashboard
  - Criar `app/views/dashboard/index.php` com cards de métricas
  - Criar layout responsivo com CSS Grid
  - Implementar gráficos com Chart.js ou biblioteca leve
  - Criar lista de clientes a vencer
  - Estilizar para mobile e desktop
  - _Requirements: 2.1, 2.2, 2.3, 11.1, 11.2_

- [ ] 4.2 Implementar atualização automática de métricas
  - Criar `public/assets/js/pages/dashboard.js`
  - Implementar polling a cada 30 segundos
  - Atualizar métricas sem reload da página
  - Mostrar indicador de carregamento
  - _Requirements: 2.4_

- [ ] 4.3 Criar dashboard admin (se aplicável)
  - Criar view separada para admin com métricas globais
  - Implementar filtros por reseller
  - Mostrar estatísticas de assinaturas
  - Criar gráficos de crescimento
  - _Requirements: 2.5_

## Fase 5: Gestão de Clientes

- [ ] 5. Implementar CRUD de clientes
  - Criar `app/controllers/ClientController.php`
  - Criar `app/models/Client.php` com validações
  - Implementar listagem com paginação e filtros
  - Implementar criação com validação de dados
  - Implementar edição e exclusão
  - _Requirements: 3.1, 3.3, 3.4, 3.5_

- [ ] 5.1 Criar views de clientes
  - Criar `app/views/clients/index.php` com tabela responsiva
  - Criar `app/views/clients/create.php` com formulário
  - Criar `app/views/clients/edit.php` com dados preenchidos
  - Implementar modal de confirmação para exclusão
  - Estilizar tabela para mobile (scroll horizontal)
  - _Requirements: 3.1, 3.3, 3.4, 11.3_

- [ ] 5.2 Implementar busca e filtros de clientes
  - Criar barra de busca por nome, telefone, email
  - Implementar filtros por status (ativo, inativo, suspenso)
  - Implementar filtro por data de vencimento
  - Criar ordenação por colunas
  - Adicionar paginação (50 itens por página)
  - _Requirements: 3.5_

- [ ] 5.3 Criar JavaScript de gerenciamento de clientes
  - Criar `public/assets/js/pages/clients.js`
  - Implementar validação de formulário em tempo real
  - Criar máscaras para telefone e CPF
  - Implementar busca com debounce
  - Criar feedback visual para ações (sucesso/erro)
  - _Requirements: 3.1, 3.3, 11.4_

- [ ] 5.4 Integrar envio de WhatsApp ao criar cliente
  - Buscar template de boas-vindas ativo
  - Substituir variáveis do template (nome, usuário, senha, etc)
  - Enviar mensagem via Evolution API
  - Registrar log de envio
  - Mostrar feedback ao usuário
  - _Requirements: 3.2, 6.2_

## Fase 6: Sistema de Faturas

- [ ] 6. Implementar CRUD de faturas
  - Criar `app/controllers/InvoiceController.php`
  - Criar `app/models/Invoice.php` com cálculos
  - Implementar listagem com filtros por status e período
  - Implementar criação com validação
  - Implementar edição e exclusão
  - _Requirements: 4.1, 4.4, 4.5_

- [ ] 6.1 Criar views de faturas
  - Criar `app/views/invoices/index.php` com tabela
  - Criar `app/views/invoices/create.php` com formulário
  - Implementar cálculo automático de valor final (valor - desconto)
  - Criar badges de status (pendente, pago, vencido)
  - Estilizar para mobile
  - _Requirements: 4.1, 4.5_

- [ ] 6.2 Implementar geração automática de link de pagamento
  - Criar `app/services/PaymentService.php`
  - Integrar com Mercado Pago API para gerar PIX
  - Salvar transação na tabela `payment_transactions`
  - Gerar link de checkout personalizado
  - Atualizar fatura com link gerado
  - _Requirements: 4.1, 4.2, 13.1_

- [ ] 6.3 Integrar envio de WhatsApp com link de pagamento
  - Buscar template "fatura disponível" ativo
  - Substituir variáveis incluindo {{link_pagamento}}
  - Enviar mensagem via Evolution API
  - Registrar log de envio
  - _Requirements: 4.2, 6.2_

- [ ] 6.4 Criar JavaScript de gerenciamento de faturas
  - Criar `public/assets/js/pages/invoices.js`
  - Implementar validação de formulário
  - Criar cálculo automático de desconto
  - Implementar filtros e busca
  - Mostrar modal com QR Code do PIX
  - _Requirements: 4.1, 13.1_

## Fase 7: Métodos de Pagamento

- [ ] 7. Implementar gerenciamento de métodos de pagamento
  - Criar `app/controllers/PaymentMethodController.php`
  - Criar `app/models/PaymentMethod.php`
  - Implementar CRUD de métodos (Mercado Pago, Asaas, PIX Manual)
  - Implementar validação de credenciais
  - Implementar ativação/desativação e método padrão
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 7.1 Criar views de métodos de pagamento
  - Criar `app/views/settings/payment-methods.php`
  - Criar formulários para cada tipo de método
  - Implementar toggle de ativação
  - Mascarar tokens e chaves sensíveis
  - Criar botão de teste de conexão
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 7.2 Integrar Mercado Pago
  - Implementar geração de pagamento PIX
  - Capturar QR Code e código copia-e-cola
  - Implementar webhook para confirmação
  - Testar com credenciais de teste
  - Testar com credenciais de produção
  - _Requirements: 5.1, 13.1, 13.2_

- [ ] 7.3 Integrar Asaas
  - Implementar geração de cobrança PIX
  - Capturar dados de pagamento
  - Implementar webhook para confirmação
  - Testar integração
  - _Requirements: 5.2_

- [ ] 7.4 Implementar PIX Manual
  - Criar checkout com chave PIX do reseller
  - Gerar QR Code com biblioteca PHP
  - Implementar confirmação manual
  - _Requirements: 5.3_

## Fase 8: Checkout e Pagamento

- [ ] 8. Criar página de checkout PIX
  - Criar `app/views/checkout/pix.php`
  - Buscar dados da transação por ID
  - Exibir QR Code e código copia-e-cola
  - Implementar botão de copiar código
  - Criar contador de expiração (24h)
  - _Requirements: 13.1, 13.2, 13.3, 13.5_

- [ ] 8.1 Implementar verificação de pagamento em tempo real
  - Criar endpoint `/api/transactions/{id}/status`
  - Implementar polling a cada 5 segundos
  - Redirecionar para página de sucesso quando pago
  - Mostrar indicador de "Aguardando pagamento..."
  - _Requirements: 13.3_

- [ ] 8.2 Criar página de sucesso
  - Criar `app/views/checkout/success.php`
  - Exibir mensagem de confirmação
  - Mostrar detalhes do pagamento
  - Criar botão para voltar ao dashboard
  - _Requirements: 13.3_

- [ ] 8.3 Implementar webhooks de pagamento
  - Criar `app/api/endpoints/webhooks.php`
  - Implementar webhook Mercado Pago
  - Implementar webhook Asaas
  - Validar assinatura dos webhooks
  - Marcar fatura como paga
  - Disparar renovação no Sigma
  - _Requirements: 4.3, 7.4_

## Fase 9: Automação WhatsApp

- [ ] 9. Implementar gerenciamento de templates WhatsApp
  - Criar `app/controllers/WhatsAppController.php`
  - Criar `app/models/WhatsAppTemplate.php`
  - Implementar CRUD de templates
  - Implementar ativação/desativação por evento
  - Criar preview de template com variáveis
  - _Requirements: 6.1, 6.2_

- [ ] 9.1 Criar views de templates WhatsApp
  - Criar `app/views/settings/whatsapp.php`
  - Criar editor de template com textarea
  - Listar variáveis disponíveis
  - Implementar preview em tempo real
  - Criar toggle de ativação por evento
  - _Requirements: 6.1, 6.2_

- [ ] 9.2 Implementar serviço de envio WhatsApp
  - Criar `app/services/WhatsAppService.php`
  - Implementar conexão com Evolution API
  - Criar método de substituição de variáveis
  - Implementar formatação de telefone (55 + DDD + número)
  - Registrar logs de envio
  - _Requirements: 6.2, 6.4_

- [ ] 9.3 Criar processador de lembretes (manter Node.js)
  - Verificar se `scripts/reminder-processor.js` existe
  - Adaptar para buscar templates da nova estrutura
  - Implementar lógica de dias até vencimento
  - Evitar duplicatas (1 mensagem por dia por tipo)
  - Configurar PM2 para rodar em background
  - _Requirements: 6.3, 6.4, 9.1_

- [ ] 9.4 Criar processador de faturas (manter Node.js)
  - Verificar se `scripts/invoice-processor.js` existe
  - Adaptar para gerar faturas 10 dias antes do vencimento
  - Enviar WhatsApp com link de pagamento
  - Evitar duplicatas
  - Configurar PM2
  - _Requirements: 4.2, 6.3, 9.1_

## Fase 10: Integração Painel Sigma

- [ ] 10. Implementar gerenciamento de painéis Sigma
  - Criar `app/controllers/PanelController.php`
  - Criar `app/models/Panel.php`
  - Implementar CRUD de painéis
  - Implementar teste de conexão
  - Validar credenciais (URL, token, username)
  - _Requirements: 7.1_

- [ ] 10.1 Implementar serviço de renovação Sigma
  - Criar `app/services/SigmaService.php`
  - Implementar chamada à API Sigma para renovar cliente
  - Buscar painel associado ao cliente
  - Registrar logs detalhados (sucesso/erro)
  - Implementar retry em caso de falha
  - _Requirements: 7.2, 7.3, 7.5_

- [ ] 10.2 Integrar renovação Sigma com pagamento
  - Modificar webhook para chamar SigmaService após marcar fatura como paga
  - Validar se cliente tem username Sigma
  - Validar se painel está configurado
  - Enviar WhatsApp de confirmação após renovação
  - _Requirements: 7.2, 7.4_

## Fase 11: Sistema de Assinaturas (Resellers)

- [ ] 11. Implementar gerenciamento de planos de assinatura
  - Criar `app/controllers/SubscriptionPlanController.php`
  - Criar `app/models/SubscriptionPlan.php`
  - Implementar CRUD de planos (admin only)
  - Criar plano trial padrão (3 dias)
  - _Requirements: 8.1, 15.1_

- [ ] 11.1 Implementar sistema de assinaturas de resellers
  - Criar `app/models/ResellerSubscription.php`
  - Implementar ativação de trial no registro
  - Implementar verificação de vencimento
  - Criar middleware de bloqueio para resellers inadimplentes
  - _Requirements: 8.1, 8.2, 8.5_

- [ ] 11.2 Criar processador de assinaturas (manter Node.js)
  - Verificar se `scripts/subscription-processor.js` existe
  - Adaptar para verificar vencimentos de resellers
  - Bloquear acesso de inadimplentes
  - Enviar notificações de vencimento
  - Configurar PM2
  - _Requirements: 8.2, 8.4, 9.3_

- [ ] 11.3 Criar views de assinatura
  - Criar `app/views/subscription/index.php`
  - Exibir plano atual e data de vencimento
  - Criar botão de renovação
  - Mostrar histórico de pagamentos
  - _Requirements: 8.3_

## Fase 12: Relatórios e Exportação

- [ ] 12. Implementar sistema de relatórios
  - Criar `app/controllers/ReportController.php`
  - Implementar filtros por período (data início/fim)
  - Calcular totais de receitas, despesas, lucros
  - Implementar queries otimizadas com GROUP BY
  - _Requirements: 10.1, 10.2_

- [ ] 12.1 Criar views de relatórios
  - Criar `app/views/reports/index.php`
  - Criar filtros de data com date picker
  - Exibir cards com totais
  - Criar gráficos de linha (receitas ao longo do tempo)
  - Criar gráficos de barra (top clientes)
  - _Requirements: 10.1, 10.4_

- [ ] 12.2 Implementar exportação CSV
  - Criar endpoint `/api/reports/export`
  - Gerar CSV com dados filtrados
  - Implementar download automático
  - Incluir cabeçalhos corretos
  - _Requirements: 10.3_

## Fase 13: Responsividade e UX Mobile

- [ ] 13. Implementar layout responsivo
  - Criar CSS com media queries para mobile
  - Implementar menu lateral deslizante (hamburger)
  - Adaptar tabelas para scroll horizontal
  - Ajustar formulários para toque
  - Testar em dispositivos reais (iPhone, Android)
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [ ] 13.1 Otimizar gráficos para mobile
  - Redimensionar gráficos automaticamente
  - Ajustar legendas e labels
  - Implementar touch gestures
  - _Requirements: 11.5_

- [ ] 13.2 Criar componentes mobile-friendly
  - Criar modais fullscreen no mobile
  - Implementar bottom sheets para ações
  - Criar cards expansíveis
  - Ajustar espaçamentos e tamanhos de fonte
  - _Requirements: 11.1, 11.4_

## Fase 14: Segurança e Validação

- [ ] 14. Implementar medidas de segurança
  - Validar e sanitizar todas as entradas
  - Implementar prepared statements em todas as queries
  - Criar sistema de rate limiting
  - Implementar CSRF tokens em formulários
  - Configurar headers de segurança (CSP, X-Frame-Options)
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

- [ ] 14.1 Implementar auditoria e logs
  - Criar tabela `audit_logs`
  - Registrar ações críticas (login, criação, edição, exclusão)
  - Implementar rotação de logs
  - Criar visualização de logs (admin only)
  - _Requirements: 12.1_

## Fase 15: Testes e Deploy Final

- [ ] 15. Realizar testes completos
  - Testar todos os fluxos de autenticação
  - Testar CRUD de todas as entidades
  - Testar integrações (WhatsApp, Sigma, Pagamentos)
  - Testar webhooks com ferramentas (ngrok, webhook.site)
  - Testar responsividade em múltiplos dispositivos
  - _Requirements: Todos_

- [ ] 15.1 Otimizar performance
  - Implementar cache em queries pesadas
  - Minificar CSS e JavaScript
  - Habilitar compressão gzip
  - Otimizar imagens
  - Configurar cache de navegador
  - _Requirements: Performance_

- [ ] 15.2 Configurar SSL e domínio
  - Instalar certificado SSL (Let's Encrypt)
  - Configurar redirecionamento HTTP → HTTPS
  - Configurar domínio definitivo
  - Testar acesso seguro
  - _Requirements: 12.1_

- [ ] 15.3 Configurar backup automático
  - Criar script de backup do banco de dados
  - Configurar cron job diário
  - Testar restauração de backup
  - Configurar backup de arquivos (storage/)
  - _Requirements: Manutenção_

- [ ] 15.4 Documentar sistema
  - Criar README.md com instruções de instalação
  - Documentar endpoints da API
  - Criar guia de uso para resellers
  - Documentar variáveis de ambiente
  - Criar troubleshooting guide
  - _Requirements: Documentação_

## Notas Importantes

- **Desenvolvimento Direto na VPS:** Todo código será escrito e testado diretamente no servidor de produção
- **Versionamento:** Usar Git para controle de versão, mesmo em produção
- **Backups:** Fazer backup do banco antes de mudanças críticas
- **Testes:** Testar cada funcionalidade imediatamente após implementação
- **Logs:** Monitorar logs constantemente durante desenvolvimento
- **Segurança:** Nunca commitar credenciais ou chaves no Git

