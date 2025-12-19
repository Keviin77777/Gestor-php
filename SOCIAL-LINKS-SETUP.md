# Configuração de Links Sociais

## Descrição
Sistema de links sociais configuráveis no header (Telegram e WhatsApp) que podem ser personalizados pelo administrador.

## Instalação

### 1. Executar Migração do Banco de Dados

Execute o script PHP para adicionar as colunas necessárias:

```bash
php database/add-social-links-columns.php
```

Ou execute manualmente o SQL:

```bash
php -r "require 'database/add-social-links-columns.php';"
```

### 2. Funcionalidades

#### Para Administradores:
- Acesse **Meu Perfil**
- Role até a seção "Links Sociais (Apenas Admin)"
- Configure:
  - **Link do Telegram**: URL completa do canal/grupo (ex: https://t.me/+jim14-gGOBFhNWMx)
  - **Número do WhatsApp**: Apenas números, sem formatação (ex: 14997349352)

#### Para Todos os Usuários:
- Os ícones de Telegram e WhatsApp aparecem no header
- Ao clicar, são redirecionados para os links configurados pelo admin
- Se não configurado, usa os valores padrão:
  - Telegram: https://t.me/+jim14-gGOBFhNWMx
  - WhatsApp: 14997349352

## Valores Padrão

Os valores padrão são definidos no banco de dados:
- `telegram_link`: 'https://t.me/+jim14-gGOBFhNWMx'
- `whatsapp_number`: '14997349352'

## Observações

- Apenas administradores podem editar os links sociais
- Os links são globais e aparecem para todos os usuários
- O WhatsApp abre automaticamente no formato: https://wa.me/{numero}
- Os ícones têm animação hover e cores características de cada plataforma
