# UltraGestor Frontend

Frontend React + TypeScript + Tailwind CSS para o sistema UltraGestor.

## 🚀 Tecnologias

- **React 18** - Biblioteca UI
- **TypeScript** - Tipagem estática
- **Tailwind CSS** - Framework CSS utility-first
- **Vite** - Build tool e dev server
- **React Router** - Roteamento
- **Zustand** - Gerenciamento de estado
- **Axios** - Cliente HTTP
- **React Hot Toast** - Notificações
- **Lucide React** - Ícones
- **date-fns** - Manipulação de datas
- **Recharts** - Gráficos

## 📦 Instalação

```bash
# Instalar dependências
npm install

# Iniciar servidor de desenvolvimento
npm run dev

# Build para produção
npm run build

# Preview do build
npm run preview
```

## 🔧 Configuração

O frontend está configurado para fazer proxy das requisições para o backend PHP em `http://localhost`.

Se seu backend PHP estiver em outra porta ou domínio, edite o arquivo `vite.config.ts`:

```typescript
server: {
  port: 3000,
  proxy: {
    '/api-clients.php': 'http://localhost:8080', // Altere aqui
    // ...
  },
}
```

## 📁 Estrutura de Pastas

```
frontend/
├── src/
│   ├── components/        # Componentes reutilizáveis
│   │   ├── layouts/       # Layouts da aplicação
│   │   ├── Header.tsx
│   │   └── Sidebar.tsx
│   ├── pages/             # Páginas da aplicação
│   │   ├── Dashboard.tsx
│   │   ├── Clients.tsx
│   │   ├── Invoices.tsx
│   │   ├── Servers.tsx
│   │   ├── PaymentMethods.tsx
│   │   ├── WhatsAppConnect.tsx
│   │   └── Login.tsx
│   ├── services/          # Serviços de API
│   │   ├── api.ts
│   │   ├── clientService.ts
│   │   ├── invoiceService.ts
│   │   ├── serverService.ts
│   │   ├── paymentMethodService.ts
│   │   └── whatsappService.ts
│   ├── stores/            # Stores Zustand
│   │   ├── useAuthStore.ts
│   │   └── useClientStore.ts
│   ├── types/             # Tipos TypeScript
│   │   └── index.ts
│   ├── App.tsx            # Componente principal
│   ├── main.tsx           # Entry point
│   └── index.css          # Estilos globais
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.ts
└── tailwind.config.js
```

## 🎨 Tema

O aplicativo suporta tema claro e escuro. O tema é salvo no localStorage e aplicado automaticamente.

## 🔐 Autenticação

O sistema usa JWT para autenticação. O token é armazenado no localStorage e enviado automaticamente em todas as requisições via interceptor do Axios.

## 🌐 Rotas

- `/` - Dashboard
- `/clients` - Gerenciamento de clientes
- `/invoices` - Gerenciamento de faturas
- `/servers` - Gerenciamento de servidores
- `/payment-methods` - Configuração de métodos de pagamento
- `/whatsapp` - Conexão WhatsApp
- `/login` - Página de login

## 📝 Notas

- O backend PHP deve estar rodando para o frontend funcionar corretamente
- As APIs PHP devem estar acessíveis nas rotas configuradas no proxy
- O sistema foi projetado para não modificar o backend existente
- Todas as chamadas de API são feitas através dos serviços em `src/services/`

## 🚀 Deploy

Para fazer deploy em produção:

1. Build do projeto:
```bash
npm run build
```

2. Os arquivos estarão na pasta `dist/`

3. Configure seu servidor web (Apache/Nginx) para servir os arquivos estáticos da pasta `dist/` e fazer proxy das requisições `/api-*.php` para o backend PHP

Exemplo de configuração Nginx:

```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    
    root /caminho/para/frontend/dist;
    index index.html;
    
    # Servir arquivos estáticos do React
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Proxy para APIs PHP
    location ~ ^/api-.*\.php$ {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```
