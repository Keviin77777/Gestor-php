# Requirements Document - Migração para PHP Puro

## Introduction

Este documento descreve os requisitos para migração do sistema UltraGestor/GestPlay de Next.js + React para uma arquitetura PHP puro + HTML/CSS + JavaScript vanilla, mantendo todas as funcionalidades existentes.

## Glossary

- **Sistema**: Aplicação web completa de gestão IPTV
- **Frontend**: Interface do usuário (HTML/CSS/JavaScript)
- **Backend**: Servidor e lógica de negócios (PHP)
- **Reseller**: Revendedor que gerencia clientes
- **Cliente**: Usuário final do serviço IPTV
- **Painel Sigma**: Sistema externo de gerenciamento IPTV
- **Evolution API**: Serviço de integração WhatsApp
- **Gateway**: Serviço de processamento de pagamentos

## Requirements

### Requirement 1: Autenticação e Autorização

**User Story:** Como um usuário do sistema, quero fazer login com email e senha, para que eu possa acessar minhas funcionalidades de forma segura

#### Acceptance Criteria

1. WHEN um usuário submete credenciais válidas, THE Sistema SHALL autenticar o usuário e gerar um token JWT
2. WHEN um usuário tenta acessar uma rota protegida sem autenticação, THE Sistema SHALL redirecionar para a página de login
3. WHEN um usuário faz logout, THE Sistema SHALL invalidar o token e limpar a sessão
4. WHERE um usuário esquece a senha, THE Sistema SHALL enviar email com link de recuperação
5. WHEN um novo usuário se registra, THE Sistema SHALL criar conta com período trial de 3 dias

### Requirement 2: Dashboard e Métricas

**User Story:** Como um reseller, quero visualizar métricas do meu negócio em tempo real, para que eu possa tomar decisões informadas

#### Acceptance Criteria

1. WHEN um reseller acessa o dashboard, THE Sistema SHALL exibir total de clientes ativos e inativos
2. WHEN o dashboard carrega, THE Sistema SHALL calcular e exibir receitas, despesas e lucros do mês
3. WHEN há clientes próximos ao vencimento, THE Sistema SHALL exibir lista de clientes a vencer
4. WHILE o usuário está no dashboard, THE Sistema SHALL atualizar métricas automaticamente a cada 30 segundos
5. WHERE o usuário é admin, THE Sistema SHALL exibir métricas globais de todos os resellers

### Requirement 3: Gestão de Clientes

**User Story:** Como um reseller, quero gerenciar meus clientes (criar, editar, excluir), para que eu possa controlar minha base de usuários

#### Acceptance Criteria

1. WHEN um reseller cria um cliente, THE Sistema SHALL validar dados obrigatórios (nome, telefone, plano, valor, vencimento)
2. WHEN um cliente é criado com telefone, THE Sistema SHALL enviar mensagem WhatsApp de boas-vindas automaticamente
3. WHEN um reseller edita um cliente, THE Sistema SHALL atualizar apenas os campos modificados
4. WHEN um reseller exclui um cliente, THE Sistema SHALL remover todos os dados relacionados (faturas, logs)
5. WHEN um reseller busca clientes, THE Sistema SHALL permitir filtros por status, nome, telefone e data de vencimento

### Requirement 4: Sistema de Faturas

**User Story:** Como um reseller, quero gerar e gerenciar faturas dos meus clientes, para que eu possa controlar pagamentos

#### Acceptance Criteria

1. WHEN uma fatura é criada, THE Sistema SHALL gerar automaticamente link de pagamento PIX
2. WHEN uma fatura é gerada, THE Sistema SHALL enviar WhatsApp com link de pagamento ao cliente
3. WHEN um pagamento é confirmado via webhook, THE Sistema SHALL marcar fatura como paga automaticamente
4. WHEN uma fatura é marcada como paga, THE Sistema SHALL renovar acesso do cliente no Painel Sigma
5. WHEN uma fatura vence, THE Sistema SHALL atualizar status para "overdue" automaticamente

### Requirement 5: Métodos de Pagamento

**User Story:** Como um reseller, quero configurar métodos de pagamento (Mercado Pago, Asaas, PIX Manual), para que meus clientes possam pagar

#### Acceptance Criteria

1. WHEN um reseller configura Mercado Pago, THE Sistema SHALL validar credenciais via API
2. WHEN um reseller configura Asaas, THE Sistema SHALL validar API key e chave PIX
3. WHEN um reseller ativa PIX Manual, THE Sistema SHALL permitir configurar chave PIX própria
4. WHEN um método é definido como padrão, THE Sistema SHALL desativar outros métodos padrão do mesmo tipo
5. WHEN uma fatura é gerada, THE Sistema SHALL usar o método de pagamento padrão ativo

### Requirement 6: Automação WhatsApp

**User Story:** Como um reseller, quero automatizar mensagens WhatsApp para meus clientes, para que eu economize tempo e melhore comunicação

#### Acceptance Criteria

1. WHEN um reseller cria templates, THE Sistema SHALL permitir 8 tipos de eventos (boas-vindas, fatura disponível, lembretes, confirmação)
2. WHEN um template usa variáveis, THE Sistema SHALL substituir por dados reais do cliente/fatura
3. WHEN um lembrete é agendado, THE Sistema SHALL enviar no horário configurado (padrão 9h)
4. WHEN uma mensagem é enviada, THE Sistema SHALL registrar log com status (enviado/falhou)
5. WHEN um cliente não tem telefone, THE Sistema SHALL pular envio e registrar motivo no log

### Requirement 7: Integração Painel Sigma

**User Story:** Como um reseller, quero integrar com Painel Sigma, para que renovações sejam automáticas após pagamento

#### Acceptance Criteria

1. WHEN um reseller configura Sigma, THE Sistema SHALL validar URL, token e username
2. WHEN um pagamento é confirmado, THE Sistema SHALL chamar API Sigma para renovar cliente
3. WHEN a renovação Sigma falha, THE Sistema SHALL registrar erro detalhado no log
4. WHEN um cliente não tem username Sigma, THE Sistema SHALL pular renovação e notificar reseller
5. WHEN múltiplos painéis estão configurados, THE Sistema SHALL usar o painel associado ao cliente

### Requirement 8: Sistema de Assinaturas (Resellers)

**User Story:** Como admin, quero gerenciar assinaturas de resellers, para que eu possa monetizar o sistema

#### Acceptance Criteria

1. WHEN um reseller se registra, THE Sistema SHALL ativar trial de 3 dias automaticamente
2. WHEN o trial expira, THE Sistema SHALL bloquear acesso do reseller até pagamento
3. WHEN um reseller paga assinatura, THE Sistema SHALL reativar acesso imediatamente
4. WHEN uma assinatura vence, THE Sistema SHALL enviar notificações 7, 3 e 1 dia antes
5. WHERE um reseller está bloqueado, THE Sistema SHALL permitir apenas acesso à página de pagamento

### Requirement 9: Processadores Background

**User Story:** Como sistema, quero executar tarefas automáticas em background, para que processos críticos funcionem sem intervenção

#### Acceptance Criteria

1. WHEN o processor de lembretes executa, THE Sistema SHALL verificar clientes a cada 1 minuto
2. WHEN o processor de faturas executa, THE Sistema SHALL gerar faturas 10 dias antes do vencimento
3. WHEN o processor de assinaturas executa, THE Sistema SHALL verificar vencimentos de resellers
4. WHEN um processor falha, THE Sistema SHALL registrar erro e continuar execução
5. WHEN um processor reinicia, THE Sistema SHALL retomar do último ponto processado

### Requirement 10: Relatórios e Exportação

**User Story:** Como um reseller, quero gerar relatórios de receitas e clientes, para que eu possa analisar meu negócio

#### Acceptance Criteria

1. WHEN um reseller solicita relatório, THE Sistema SHALL permitir filtro por período (data início/fim)
2. WHEN um relatório é gerado, THE Sistema SHALL calcular totais de receitas, despesas e lucros
3. WHEN um reseller exporta dados, THE Sistema SHALL gerar arquivo CSV com dados filtrados
4. WHEN um relatório inclui gráficos, THE Sistema SHALL renderizar gráficos de linha e barra
5. WHEN dados são insuficientes, THE Sistema SHALL exibir mensagem informativa

### Requirement 11: Responsividade Mobile

**User Story:** Como um usuário mobile, quero acessar o sistema pelo celular, para que eu possa gerenciar de qualquer lugar

#### Acceptance Criteria

1. WHEN um usuário acessa pelo mobile, THE Sistema SHALL adaptar layout para telas pequenas
2. WHEN um menu é aberto no mobile, THE Sistema SHALL exibir menu lateral deslizante
3. WHEN tabelas são exibidas no mobile, THE Sistema SHALL permitir scroll horizontal
4. WHEN formulários são preenchidos no mobile, THE Sistema SHALL ajustar campos para toque
5. WHEN gráficos são renderizados no mobile, THE Sistema SHALL redimensionar automaticamente

### Requirement 12: Segurança e Validação

**User Story:** Como sistema, quero validar e sanitizar todas as entradas, para que eu previna ataques e erros

#### Acceptance Criteria

1. WHEN dados são recebidos do frontend, THE Sistema SHALL sanitizar strings removendo tags HTML
2. WHEN queries SQL são executadas, THE Sistema SHALL usar prepared statements
3. WHEN senhas são armazenadas, THE Sistema SHALL usar hash bcrypt ou argon2
4. WHEN tokens JWT são gerados, THE Sistema SHALL incluir expiração de 7 dias
5. WHEN requisições são recebidas, THE Sistema SHALL aplicar rate limiting (100 req/min)

### Requirement 13: Checkout e Pagamento PIX

**User Story:** Como um cliente, quero pagar minha fatura via PIX, para que eu renove meu acesso rapidamente

#### Acceptance Criteria

1. WHEN um cliente acessa link de pagamento, THE Sistema SHALL exibir QR Code PIX
2. WHEN o QR Code é exibido, THE Sistema SHALL mostrar código PIX copia-e-cola
3. WHEN o pagamento é confirmado, THE Sistema SHALL redirecionar para página de sucesso
4. WHEN o pagamento expira (24h), THE Sistema SHALL invalidar transação
5. WHEN o cliente copia código PIX, THE Sistema SHALL exibir feedback visual

### Requirement 14: Recuperação de Senha

**User Story:** Como um usuário, quero recuperar minha senha via email, para que eu possa acessar minha conta novamente

#### Acceptance Criteria

1. WHEN um usuário solicita recuperação, THE Sistema SHALL enviar email com link único
2. WHEN o link é acessado, THE Sistema SHALL validar token e expiração (1 hora)
3. WHEN uma nova senha é definida, THE Sistema SHALL invalidar token usado
4. WHEN o token expira, THE Sistema SHALL exibir mensagem de erro
5. WHEN a senha é alterada, THE Sistema SHALL enviar email de confirmação

### Requirement 15: Planos e Preços

**User Story:** Como um reseller, quero criar planos personalizados, para que eu possa oferecer diferentes opções aos clientes

#### Acceptance Criteria

1. WHEN um reseller cria um plano, THE Sistema SHALL validar nome, preço e duração
2. WHEN um plano é editado, THE Sistema SHALL atualizar apenas clientes novos
3. WHEN um plano é desativado, THE Sistema SHALL manter clientes existentes ativos
4. WHEN um cliente é criado, THE Sistema SHALL permitir selecionar plano ou valor customizado
5. WHEN um plano tem desconto, THE Sistema SHALL calcular valor final automaticamente
