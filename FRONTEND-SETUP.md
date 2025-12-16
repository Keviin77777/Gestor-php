# 🚀 Guia de Instalação do Frontend React

Este guia explica como configurar e executar o novo frontend React + TypeScript + Tailwind CSS do UltraGestor.

## ✅ O que foi criado

Um frontend moderno e completo que consome suas APIs PHP existentes:

### 📦 Tecnologias
- **React 18** com TypeScript
- **Tailwind CSS** para estilização
- **Vite** como build tool (super rápido!)
- **Zustand** para gerenciamento de estado
- **React Router** para navegação
- **Axios** para chamadas HTTP
- **React Hot Toast** para notificações

### 📄 Páginas Implementadas
- ✅ **Dashboard** - Estatísticas e gráficos interativos
- ✅ **Clientes** - CRUD completo com modal e filtros
- ✅ **Importar Clientes** - Upload de CSV em massa
- ✅ **Planos** - Gerenciamento de planos de assinatura
- ✅ **Aplicativos** - Gerenciamento de aplicações
- ✅ **Faturas** - Listagem e gerenciamento de faturas
- ✅ **Servidores** - Configuração de servidores
- ✅ **Métodos de Pagamento** - Configuração Asaas, Mercado Pago, etc
- ✅ **WhatsApp Parear** - Conexão via QR Code
- ✅ **WhatsApp Templates** - Gerenciamento de templates de mensagens
- ✅ **WhatsApp Agendamento** - Agendar envio de mensagens
- ✅ **WhatsApp Fila** - Monitoramento de fila de envio
- ✅ **Perfil** - Edição de dados do usuário
- ✅ **Relatórios** - Gráficos e análises de desempenho
- ✅ **Login/Autenticação** - Sistema completo de autenticação

### 🔌 Integração com Backend
- Todas as APIs PHP existentes estão integradas
- Nenhuma modificação no backend foi necessária
- Sistema de proxy configurado para desenvolvimento
- Autenticação via JWT (localStorage)

## 📋 Pré-requisitos

- Node.js 18+ instalado
- Backend PHP rodando (seu sistema atual)

## 🛠️ Instalação

### 1. Navegue até a pasta do frontend

```bash
cd frontend
```

### 2. Instale as dependências

```bash
npm install
```

Isso vai instalar todas as bibliotecas necessárias (~2-3 minutos).

### 3. Configure o ambiente (opcional)

Copie o arquivo de exemplo:

```bash
copy .env.example .env
```

O arquivo `.env` já está configurado para funcionar com seu backend PHP local.

### 4. Inicie o servidor de desenvolvimento

```bash
npm run dev
```

O frontend estará disponível em: **http://localhost:3000**

## 🎯 Como Usar

### Desenvolvimento

1. **Backend PHP**: Certifique-se de que seu backend PHP está rodando (normalmente em `http://localhost` ou `http://localhost:80`)

2. **Frontend React**: Execute `npm run dev` na pasta `frontend/`

3. **Acesse**: Abra `http://localhost:3000` no navegador

### Login

Por enquanto, o login está com dados mockados para você testar. Para integrar com seu sistema de autenticação real:

1. Edite `frontend/src/pages/Login.tsx`
2. Substitua a lógica de login mockada pela chamada real à sua API de autenticação

### Tema Claro/Escuro

O sistema suporta tema claro e escuro automaticamente. Clique no ícone de lua/sol no header para alternar.

## 🏗️ Build para Produção

### 1. Gerar build otimizado

```bash
npm run build
```

Isso cria uma pasta `dist/` com os arquivos otimizados.

### 2. Testar o build localmente

```bash
npm run preview
```

### 3. Deploy

Você tem duas opções:

#### Opção A: Servir do mesmo domínio do PHP

Copie o conteúdo da pasta `dist/` para uma pasta no seu servidor web (ex: `public/app/`) e configure o Apache/Nginx para servir esses arquivos.

**Exemplo Apache (.htaccess):**

```apache
# Servir React App
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /app/
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /app/index.html [L]
</IfModule>
```

#### Opção B: Servir em domínio separado

Configure um servidor web separado (Nginx recomendado) para servir o frontend e fazer proxy para as APIs PHP.

**Exemplo Nginx:**

```nginx
server {
    listen 80;
    server_name app.ultragestor.site;
    
    root /var/www/ultragestor/frontend/dist;
    index index.html;
    
    # Servir arquivos estáticos do React
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Proxy para APIs PHP
    location ~ ^/api-.*\.php$ {
        proxy_pass http://ultragestor.site;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

## 📁 Estrutura do Projeto

```
frontend/
├── src/
│   ├── components/          # Componentes reutilizáveis
│   │   ├── layouts/         # Layouts (Dashboard, etc)
│   │   ├── Header.tsx       # Cabeçalho com tema e perfil
│   │   └── Sidebar.tsx      # Menu lateral
│   ├── pages/               # Páginas da aplicação
│   │   ├── Dashboard.tsx    # Dashboard principal
│   │   ├── Clients.tsx      # Gerenciamento de clientes
│   │   ├── Invoices.tsx     # Gerenciamento de faturas
│   │   ├── Servers.tsx      # Gerenciamento de servidores
│   │   ├── PaymentMethods.tsx  # Métodos de pagamento
│   │   ├── WhatsAppConnect.tsx # Conexão WhatsApp
│   │   └── Login.tsx        # Página de login
│   ├── services/            # Serviços de API
│   │   ├── api.ts           # Configuração Axios
│   │   ├── clientService.ts # API de clientes
│   │   ├── invoiceService.ts # API de faturas
│   │   ├── serverService.ts  # API de servidores
│   │   ├── paymentMethodService.ts # API de pagamentos
│   │   └── whatsappService.ts # API do WhatsApp
│   ├── stores/              # Gerenciamento de estado (Zustand)
│   │   ├── useAuthStore.ts  # Store de autenticação
│   │   └── useClientStore.ts # Store de clientes
│   ├── types/               # Tipos TypeScript
│   │   └── index.ts         # Interfaces e tipos
│   ├── App.tsx              # Componente raiz
│   ├── main.tsx             # Entry point
│   └── index.css            # Estilos globais + Tailwind
├── index.html               # HTML base
├── package.json             # Dependências
├── tsconfig.json            # Config TypeScript
├── vite.config.ts           # Config Vite (proxy, etc)
├── tailwind.config.js       # Config Tailwind
└── README.md                # Documentação
```

## 🔧 Configurações Importantes

### Proxy de Desenvolvimento

O arquivo `vite.config.ts` está configurado para fazer proxy das requisições para o backend PHP:

```typescript
server: {
  port: 3000,
  proxy: {
    '/api-clients.php': 'http://localhost',
    '/api-invoices.php': 'http://localhost',
    // ... outras APIs
  },
}
```

Se seu backend PHP estiver em outra porta, altere `http://localhost` para `http://localhost:8080` (por exemplo).

### CORS

Se você tiver problemas de CORS em produção, adicione os headers no seu backend PHP:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

## 🎨 Personalização

### Cores

Edite `tailwind.config.js` para mudar as cores primárias:

```javascript
theme: {
  extend: {
    colors: {
      primary: {
        50: '#f0f9ff',
        // ... suas cores
        900: '#0c4a6e',
      },
    },
  },
}
```

### Logo

Substitua o texto "UltraGestor" em `src/components/Sidebar.tsx` por uma imagem:

```tsx
<img src="/logo.png" alt="UltraGestor" className="h-8" />
```

## 🐛 Troubleshooting

### Erro: "Cannot find module"

```bash
rm -rf node_modules package-lock.json
npm install
```

### Erro: "Port 3000 is already in use"

Altere a porta em `vite.config.ts`:

```typescript
server: {
  port: 3001, // ou outra porta
}
```

### APIs não funcionam

1. Verifique se o backend PHP está rodando
2. Verifique a configuração do proxy em `vite.config.ts`
3. Abra o console do navegador (F12) para ver os erros

### Build falha

```bash
npm run build -- --debug
```

## 📚 Próximos Passos

1. **Integrar autenticação real**: Edite `src/pages/Login.tsx` para usar sua API de login
2. **Adicionar mais funcionalidades**: Crie novos componentes em `src/components/`
3. **Melhorar UI**: Adicione mais animações e transições
4. **Testes**: Adicione testes com Vitest ou Jest
5. **PWA**: Transforme em Progressive Web App

## 💡 Dicas

- Use `Ctrl+Shift+P` no VS Code e digite "TypeScript: Restart TS Server" se o IntelliSense parar de funcionar
- Instale a extensão "Tailwind CSS IntelliSense" no VS Code para autocompletar classes
- Use `console.log()` nos serviços para debugar chamadas de API
- O React DevTools é muito útil para debugar componentes

## 🤝 Suporte

Se tiver dúvidas ou problemas:

1. Verifique os logs do console do navegador (F12)
2. Verifique os logs do terminal onde o `npm run dev` está rodando
3. Leia a documentação das bibliotecas usadas

## 🎉 Pronto!

Seu frontend React está configurado e funcionando! Agora você tem uma interface moderna que consome suas APIs PHP existentes sem modificar nada no backend.

**Comandos principais:**

```bash
npm run dev      # Desenvolvimento
npm run build    # Build para produção
npm run preview  # Testar build localmente
```

Aproveite! 🚀
