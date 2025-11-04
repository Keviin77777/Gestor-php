# Integração com Sigma - UltraGestor

## Visão Geral

A integração com Sigma permite sincronizar automaticamente clientes do UltraGestor com painéis Sigma, facilitando o gerenciamento de usuários IPTV.

## Configuração

### 1. Configurar Servidor Sigma

1. Acesse **Servidores** no menu lateral
2. Clique em **Adicionar Servidor**
3. Preencha os dados básicos:
   - **Nome do Servidor**: Nome identificador
   - **Tipo de Cobrança**: Fixo ou Por Ativo
   - **Valor Mensal**: Custo do servidor

4. Na seção **Integração com Painel**:
   - **Tipo de Painel**: Selecione "Sigma"
   - **URL do Painel**: URL base do painel (ex: https://cinepainel.site ou https://cinepainel.site/api)
   - **Usuário Revenda**: Seu username no painel Sigma
   - **Token do Sigma**: Token de autenticação fornecido pelo painel

5. Clique em **Testar Conexão** para verificar se a configuração está correta
6. Salve o servidor

### 2. Dados Necessários do Painel Sigma

Para configurar a integração, você precisará:

- **URL do Painel**: URL base do painel (ex: https://cinepainel.site)
  - O sistema detecta automaticamente se precisa adicionar `/api`
  - Funciona com URLs que terminam com `/api` ou sem
- **Token de Autenticação**: Token Bearer fornecido pelo painel
- **Username**: Seu nome de usuário no painel Sigma (não o ID)

## Funcionalidades

### Sincronização Automática

A integração Sigma funciona automaticamente nas seguintes situações:

#### **1. Criação de Cliente**
Quando um cliente é **criado** no UltraGestor:
- ✅ Cliente é criado automaticamente no Sigma
- ✅ Username e password são gerados se não fornecidos
- ✅ Credenciais são salvas no gestor

#### **2. Atualização de Cliente**
Quando um cliente é **editado** no UltraGestor:
- ⚠️ **Sincronização desabilitada** para evitar renovações indesejadas
- 💡 Mudanças de data no gestor NÃO afetam o Sigma
- ✅ Use o pagamento de fatura para renovar no Sigma

#### **3. Pagamento de Fatura**
Quando uma fatura é **marcada como paga**:
- ✅ Cliente é renovado automaticamente no gestor (+30 dias)
- ✅ Cliente é renovado automaticamente no Sigma
- ✅ Mensagem WhatsApp de renovação é enviada

#### **4. Sincronização Reversa (Sigma → Gestor)** 🆕
Quando você clica no botão **"Sincronizar Sigma"**:
- ✅ Busca as datas de vencimento de todos os clientes no Sigma
- ✅ Atualiza automaticamente as datas no gestor
- ✅ Mostra quantos clientes foram atualizados
- 💡 Use quando alterar datas diretamente no painel Sigma

### Status de Sincronização

O sistema mostra mensagens de status em diferentes situações:

#### **Criação/Edição de Cliente:**
- ✅ **"Cliente criado com sucesso - Sincronizado com Sigma"**
- ✅ **"Cliente atualizado com sucesso - Sincronizado com Sigma"**
- ❌ **"Erro na sincronização Sigma: [detalhes]"**

#### **Pagamento de Fatura:** 🆕
- ✅ **"Fatura marcada como paga com sucesso - Cliente renovado no Sigma"**
- ❌ **"Fatura marcada como paga com sucesso - Erro na sincronização Sigma: [detalhes]"**

#### **Sem Configuração:**
- ⚠️ **"Nenhum servidor Sigma configurado - sincronização ignorada"**

### Dados Sincronizados

Os seguintes dados são enviados para o Sigma:

- **Nome do cliente**
- **Email** (opcional)
- **WhatsApp** (formatado automaticamente para padrão internacional)
- **Username** (gerado automaticamente se não fornecido)
- **Password** (gerado automaticamente se não fornecido)
- **Observações** (como nota no Sigma)
- **Package ID** (primeiro package disponível no painel)

### Status de Sincronização

Após criar/atualizar um cliente, você verá mensagens indicando:

- ✅ **"Sincronizado com Sigma"**: Cliente sincronizado com sucesso
- ❌ **"Erro na sincronização Sigma"**: Falha na sincronização (verifique logs)

## API Endpoints Utilizados

A integração utiliza os seguintes endpoints do Sigma:

- `POST /webhook/customer/create` - Criar cliente
- `POST /webhook/customer/renew` - Renovar cliente
- `PUT /webhook/customer/status` - Atualizar status
- `GET /webhook/customer` - Buscar cliente
- `GET /webhook/package` - Listar pacotes
- `GET /webhook/user` - Listar usuários

## Troubleshooting

### Erro de Conexão

1. Verifique se a URL do painel está correta
2. Confirme se o token está válido
3. Teste a conexão usando o botão "Testar Conexão"

### Cliente não Sincronizado

1. Verifique se o servidor Sigma está ativo
2. Confirme se o packageId está configurado corretamente
3. Verifique os logs do sistema para detalhes do erro

### Formatação do WhatsApp

O sistema formata automaticamente números de telefone para o padrão internacional:
- `11999999999` → `55 11 99999 9999`
- Adiciona código do país (55) se não estiver presente

## Logs e Monitoramento

Erros de sincronização são registrados nos logs do PHP. Para debugar:

1. Verifique os logs do servidor web
2. Procure por mensagens contendo "Sigma" ou "syncClientWithSigma"
3. Analise as respostas da API para identificar problemas

## Limitações Atuais

- Suporte apenas para painéis Sigma
- Usa o primeiro package disponível no painel
- Sincronização na edição de clientes está desabilitada (para evitar renovações indesejadas)
- Username e password são gerados automaticamente se não fornecidos
- Sincronização reversa é manual (via botão)

## Próximas Funcionalidades

- [ ] Configuração de packageId por plano
- [ ] Sincronização bidirecional
- [ ] Suporte a múltiplos painéis Sigma
- [ ] Dashboard de status de sincronização
- [ ] Webhook para receber atualizações do Sigma