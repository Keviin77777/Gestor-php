# Design Document - Sistema PHP Puro

## Overview

Migração completa do sistema UltraGestor de Next.js/React para arquitetura PHP puro com HTML/CSS/JavaScript vanilla, mantendo todas as funcionalidades e melhorando performance e simplicidade de deploy.

## Architecture

### Stack Tecnológica

**Backend:**
- PHP 8.0+ (puro, sem frameworks)
- MySQL 5.7+ / MariaDB 10.3+
- PDO para acesso ao banco
- JWT para autenticação
- Composer apenas para dependências essenciais (PHPMailer, etc)

**Frontend:**
- HTML5 semântico
- CSS3 puro (sem Tailwind, sem preprocessadores)
- JavaScript ES6+ vanilla (sem React, sem frameworks)
- Fetch API para requisições AJAX

**Processadores Background:**
- Node.js 18+ (manter scripts existentes)
- PM2 para gerenciamento de processos

### Estrutura de Diretórios

```
ultragestor-php/
├── public/                      # Raiz pública (DocumentRoot)
│   ├── index.php               # Router principal
│   ├── assets/
│   │   ├── css/
│   │   │   ├── main.css        # Estilos globais
│   │   │   ├── components.css  # Componentes reutilizáveis
│   │   │   ├── dashboard.css   # Estilos do dashboard
│   │   │   └── auth.css        # Estilos de autenticação
│   │   ├── js/
│   │   │   ├── app.js          # JavaScript principal
│   │   │   ├── api.js          # Cliente API
│   │   │   ├── auth.js         # Gerenciamento de autenticação
│   │   │   ├── components/     # Componentes JS
│   │   │   │   ├── modal.js
│   │   │   │   ├── table.js
│   │   │   │   ├── chart.js
│   │   │   │   └── form.js
│   │   │   └── pages/          # Lógica por página
│   │   │       ├── dashboard.js
│   │   │       ├── clients.js
│   │   │       ├── invoices.js
│   │   │       └── settings.js
│   │   └── images/
│   │       ├── logo.png
│   │       └── icons/
│   └── .htaccess               # Rewrite rules
├── app/                         # Código da aplicação
│   ├── config/
│   │   ├── database.php        # Configuração do banco
│   │   ├── app.php             # Configurações gerais
│   │   └── mail.php            # Configuração de email
│   ├── core/
│   │   ├── Router.php          # Sistema de rotas
│   │   ├── Request.php         # Manipulação de requisições
│   │   ├── Response.php        # Manipulação de respostas
│   │   ├── Database.php        # Conexão e queries
│   │   ├── Auth.php            # Autenticação JWT
│   │   ├── Validator.php       # Validação de dados
│   │   └── Session.php         # Gerenciamento de sessão
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ClientController.php
│   │   ├── InvoiceController.php
│   │   ├── PaymentController.php
│   │   ├── WhatsAppController.php
│   │   └── AdminController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Client.php
│   │   ├── Invoice.php
│   │   ├── PaymentMethod.php
│   │   ├── WhatsAppTemplate.php
│   │   └── Panel.php
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.php         # Layout principal
│   │   │   ├── auth.php        # Layout de autenticação
│   │   │   └── components/
│   │   │       ├── header.php
│   │   │       ├── sidebar.php
│   │   │       └── footer.php
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   ├── forgot-password.php
│   │   │   └── reset-password.php
│   │   ├── dashboard/
│   │   │   ├── index.php
│   │   │   └── admin.php
│   │   ├── clients/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   ├── invoices/
│   │   │   ├── index.php
│   │   │   └── create.php
│   │   ├── settings/
│   │   │   ├── profile.php
│   │   │   ├── payment-methods.php
│   │   │   └── whatsapp.php
│   │   └── checkout/
│   │       └── pix.php
│   ├── api/                     # API REST
│   │   ├── routes.php          # Definição de rotas API
│   │   ├── middleware/
│   │   │   ├── auth.php
│   │   │   └── ratelimit.php
│   │   └── endpoints/
│   │       ├── auth.php
│   │       ├── clients.php
│   │       ├── invoices.php
│   │       ├── payments.php
│   │       └── webhooks.php
│   ├── services/
│   │   ├── WhatsAppService.php
│   │   ├── PaymentService.php
│   │   ├── SigmaService.php
│   │   ├── EmailService.php
│   │   └── InvoiceService.php
│   └── helpers/
│       ├── functions.php       # Funções auxiliares
│       ├── constants.php       # Constantes do sistema
│       └── sanitize.php        # Sanitização de dados
├── database/
│   ├── migrations/             # Migrations SQL
│   └── seeds/                  # Dados iniciais
├── storage/
│   ├── logs/                   # Logs da aplicação
│   ├── cache/                  # Cache de dados
│   └── uploads/                # Arquivos enviados
├── scripts/                     # Processadores Node.js (manter)
│   ├── whatsapp-evolution-server.js
│   ├── reminder-processor.js
│   ├── invoice-processor.js
│   └── subscription-processor.js
├── vendor/                      # Dependências Composer
├── .env                         # Variáveis de ambiente
├── .htaccess                    # Apache config
├── composer.json
└── README.md
```

## Components and Interfaces

### 1. Sistema de Rotas (Router)

**Arquivo:** `app/core/Router.php`

```php
class Router {
    private array $routes = [];
    
    public function get(string $path, callable $handler): void
    public function post(string $path, callable $handler): void
    public function put(string $path, callable $handler): void
    public function delete(string $path, callable $handler): void
    public function dispatch(string $method, string $uri): void
}
```

**Uso:**
```php
$router = new Router();
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->post('/api/clients', [ClientController::class, 'store']);
```

### 2. Autenticação JWT

**Arquivo:** `app/core/Auth.php`

```php
class Auth {
    public static function generateToken(array $payload): string
    public static function validateToken(string $token): array|false
    public static function requireAuth(): array
    public static function checkPermission(string $role): bool
    public static function hashPassword(string $password): string
    public static function verifyPassword(string $password, string $hash): bool
}
```

### 3. Database Layer

**Arquivo:** `app/core/Database.php`

```php
class Database {
    private static ?PDO $connection = null;
    
    public static function connect(): PDO
    public static function query(string $sql, array $params = []): PDOStatement
    public static function fetch(string $sql, array $params = []): array|false
    public static function fetchAll(string $sql, array $params = []): array
    public static function insert(string $table, array $data): string
    public static function update(string $table, array $data, string $where, array $params): bool
    public static function delete(string $table, string $where, array $params): bool
    public static function beginTransaction(): void
    public static function commit(): void
    public static function rollback(): void
}
```

### 4. Frontend - API Client

**Arquivo:** `public/assets/js/api.js`

```javascript
class API {
    constructor(baseURL) {
        this.baseURL = baseURL;
        this.token = localStorage.getItem('token');
    }
    
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        const response = await fetch(url, {
            ...options,
            headers
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return response.json();
    }
    
    get(endpoint) { return this.request(endpoint, { method: 'GET' }); }
    post(endpoint, data) { return this.request(endpoint, { method: 'POST', body: JSON.stringify(data) }); }
    put(endpoint, data) { return this.request(endpoint, { method: 'PUT', body: JSON.stringify(data) }); }
    delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }
}
```

### 5. Frontend - Componentes Reutilizáveis

**Modal Component:**
```javascript
class Modal {
    constructor(id) {
        this.modal = document.getElementById(id);
        this.setupEventListeners();
    }
    
    open() { this.modal.classList.add('active'); }
    close() { this.modal.classList.remove('active'); }
    setContent(html) { this.modal.querySelector('.modal-body').innerHTML = html; }
}
```

**Table Component:**
```javascript
class DataTable {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = options;
        this.data = [];
    }
    
    render(data) {
        this.data = data;
        const html = this.generateTableHTML();
        this.container.innerHTML = html;
        this.attachEventListeners();
    }
    
    generateTableHTML() { /* ... */ }
    attachEventListeners() { /* ... */ }
}
```

## Data Models

### User Model

```php
class User {
    public string $id;
    public string $email;
    public string $name;
    public string $password_hash;
    public string $role; // 'admin' | 'reseller'
    public bool $is_active;
    public ?string $whatsapp;
    public ?string $subscription_plan_id;
    public ?DateTime $subscription_expiry_date;
    public string $account_status; // 'active' | 'trial' | 'expired'
    
    public static function find(string $id): ?User
    public static function findByEmail(string $email): ?User
    public function save(): bool
    public function delete(): bool
    public function hasActiveSubscription(): bool
}
```

### Client Model

```php
class Client {
    public string $id;
    public string $reseller_id;
    public string $name;
    public ?string $email;
    public ?string $phone;
    public ?string $username;
    public ?string $password;
    public ?string $plan_id;
    public ?string $panel_id;
    public DateTime $start_date;
    public DateTime $renewal_date;
    public string $status; // 'active' | 'inactive' | 'suspended'
    public float $value;
    public ?string $notes;
    
    public static function findByReseller(string $reseller_id): array
    public static function find(string $id): ?Client
    public function save(): bool
    public function delete(): bool
    public function getDaysUntilRenewal(): int
    public function isExpired(): bool
}
```

### Invoice Model

```php
class Invoice {
    public string $id;
    public string $reseller_id;
    public string $client_id;
    public float $value;
    public float $discount;
    public float $final_value;
    public DateTime $issue_date;
    public DateTime $due_date;
    public string $status; // 'pending' | 'paid' | 'overdue' | 'cancelled'
    public ?string $payment_method_id;
    public ?string $payment_link;
    public ?DateTime $payment_date;
    
    public static function findByReseller(string $reseller_id): array
    public static function find(string $id): ?Invoice
    public function save(): bool
    public function markAsPaid(string $payment_method, ?string $transaction_id = null): bool
    public function generatePaymentLink(): string
    public function sendWhatsAppNotification(): bool
}
```

## Error Handling

### Estratégia de Tratamento de Erros

**1. Erros de Validação:**
```php
class ValidationException extends Exception {
    private array $errors;
    
    public function __construct(array $errors) {
        $this->errors = $errors;
        parent::__construct('Validation failed');
    }
    
    public function getErrors(): array {
        return $this->errors;
    }
}
```

**2. Erros de Autenticação:**
```php
class AuthException extends Exception {
    public function __construct(string $message = 'Unauthorized') {
        parent::__construct($message, 401);
    }
}
```

**3. Erros de Banco de Dados:**
```php
class DatabaseException extends Exception {
    public function __construct(string $message, PDOException $previous) {
        error_log("Database Error: " . $message . " - " . $previous->getMessage());
        parent::__construct('Database error occurred', 500, $previous);
    }
}
```

**4. Handler Global:**
```php
set_exception_handler(function(Throwable $e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    
    if ($e instanceof ValidationException) {
        Response::json(['errors' => $e->getErrors()], 422);
    } elseif ($e instanceof AuthException) {
        Response::json(['error' => $e->getMessage()], 401);
    } else {
        Response::json(['error' => 'Internal server error'], 500);
    }
});
```

## Testing Strategy

### 1. Testes Manuais

**Checklist de Funcionalidades:**
- [ ] Login/Logout
- [ ] Registro com trial
- [ ] CRUD de clientes
- [ ] Geração de faturas
- [ ] Pagamento PIX
- [ ] Webhooks de pagamento
- [ ] Envio de WhatsApp
- [ ] Renovação Sigma
- [ ] Relatórios
- [ ] Responsividade mobile

### 2. Testes de Integração

**Endpoints Críticos:**
```bash
# Autenticação
curl -X POST /api/auth/login -d '{"email":"test@test.com","password":"123456"}'

# Criar cliente
curl -X POST /api/clients -H "Authorization: Bearer TOKEN" -d '{...}'

# Gerar fatura
curl -X POST /api/invoices -H "Authorization: Bearer TOKEN" -d '{...}'

# Webhook Mercado Pago
curl -X POST /api/webhooks/mercadopago -d '{...}'
```

### 3. Testes de Performance

**Métricas Alvo:**
- Tempo de resposta API: < 200ms
- Tempo de carregamento página: < 1s
- Queries SQL: < 50ms
- Envio WhatsApp: < 3s

### 4. Testes de Segurança

**Verificações:**
- [ ] SQL Injection (prepared statements)
- [ ] XSS (sanitização de output)
- [ ] CSRF (tokens)
- [ ] Rate limiting
- [ ] Validação de JWT
- [ ] Permissões de acesso

## Design Patterns

### 1. MVC (Model-View-Controller)

- **Models:** Representam dados e lógica de negócio
- **Views:** Templates PHP para renderização HTML
- **Controllers:** Processam requisições e coordenam Models/Views

### 2. Repository Pattern

```php
interface ClientRepositoryInterface {
    public function find(string $id): ?Client;
    public function findByReseller(string $reseller_id): array;
    public function save(Client $client): bool;
    public function delete(string $id): bool;
}

class ClientRepository implements ClientRepositoryInterface {
    // Implementação usando Database
}
```

### 3. Service Layer

```php
class InvoiceService {
    public function __construct(
        private InvoiceRepository $invoices,
        private PaymentService $payments,
        private WhatsAppService $whatsapp
    ) {}
    
    public function createInvoice(array $data): Invoice {
        $invoice = new Invoice($data);
        $this->invoices->save($invoice);
        
        // Gerar link de pagamento
        $link = $this->payments->generatePaymentLink($invoice);
        $invoice->payment_link = $link;
        $this->invoices->save($invoice);
        
        // Enviar WhatsApp
        $this->whatsapp->sendInvoiceNotification($invoice);
        
        return $invoice;
    }
}
```

## CSS Architecture

### Estrutura de Estilos

**1. Reset e Base:**
```css
/* main.css */
* { margin: 0; padding: 0; box-sizing: border-box; }
:root {
    --primary: #3b82f6;
    --secondary: #64748b;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border: #e2e8f0;
    --radius: 8px;
    --shadow: 0 1px 3px rgba(0,0,0,0.1);
}
```

**2. Componentes:**
```css
/* components.css */
.btn {
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.card {
    background: var(--bg-primary);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.5rem;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--border);
}
```

**3. Layout:**
```css
/* dashboard.css */
.dashboard-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    min-height: 100vh;
}

.sidebar {
    background: var(--bg-secondary);
    padding: 1.5rem;
}

.main-content {
    padding: 2rem;
}

@media (max-width: 768px) {
    .dashboard-layout {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
        position: fixed;
        left: -250px;
        transition: left 0.3s;
    }
    
    .sidebar.active {
        left: 0;
    }
}
```

## JavaScript Architecture

### Módulos e Organização

**1. Inicialização:**
```javascript
// app.js
document.addEventListener('DOMContentLoaded', () => {
    const app = new App();
    app.init();
});

class App {
    constructor() {
        this.api = new API('/api');
        this.auth = new AuthManager(this.api);
        this.router = new Router();
    }
    
    init() {
        this.setupRouter();
        this.checkAuth();
        this.attachGlobalListeners();
    }
}
```

**2. Gerenciamento de Estado:**
```javascript
// state.js
class StateManager {
    constructor() {
        this.state = {};
        this.listeners = {};
    }
    
    set(key, value) {
        this.state[key] = value;
        this.notify(key, value);
    }
    
    get(key) {
        return this.state[key];
    }
    
    subscribe(key, callback) {
        if (!this.listeners[key]) {
            this.listeners[key] = [];
        }
        this.listeners[key].push(callback);
    }
    
    notify(key, value) {
        if (this.listeners[key]) {
            this.listeners[key].forEach(cb => cb(value));
        }
    }
}
```

## Performance Optimization

### 1. Caching

**Arquivo:** `app/core/Cache.php`

```php
class Cache {
    private static string $cacheDir = __DIR__ . '/../../storage/cache/';
    
    public static function get(string $key): mixed {
        $file = self::$cacheDir . md5($key) . '.cache';
        if (!file_exists($file)) return null;
        
        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    public static function set(string $key, mixed $value, int $ttl = 3600): void {
        $file = self::$cacheDir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        file_put_contents($file, serialize($data));
    }
}
```

### 2. Query Optimization

- Usar índices em colunas de busca frequente
- Limitar resultados com LIMIT
- Usar JOINs em vez de múltiplas queries
- Cache de queries pesadas

### 3. Asset Optimization

- Minificar CSS/JS em produção
- Usar compressão gzip
- Lazy loading de imagens
- CDN para assets estáticos

## Deployment

### Requisitos do Servidor

- PHP 8.0+ com extensões: pdo_mysql, curl, json, mbstring, openssl
- MySQL 5.7+ ou MariaDB 10.3+
- Apache 2.4+ ou Nginx 1.18+
- Node.js 18+ (para processadores)
- PM2 (gerenciamento de processos)
- Certificado SSL (Let's Encrypt)

### Configuração Apache

```apache
<VirtualHost *:80>
    ServerName ultragestor.com
    DocumentRoot /var/www/ultragestor/public
    
    <Directory /var/www/ultragestor/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ultragestor-error.log
    CustomLog ${APACHE_LOG_DIR}/ultragestor-access.log combined
</VirtualHost>
```

### Configuração Nginx

```nginx
server {
    listen 80;
    server_name ultragestor.com;
    root /var/www/ultragestor/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### Variáveis de Ambiente

```env
# .env
APP_ENV=production
APP_URL=https://ultragestor.com
APP_DEBUG=false

DB_HOST=localhost
DB_PORT=3306
DB_NAME=ultragestor_db
DB_USER=ultragestor_user
DB_PASS=senha_segura_aqui

JWT_SECRET=chave_secreta_jwt_64_caracteres
ENCRYPTION_KEY=chave_criptografia_32_caracteres

WHATSAPP_API_URL=http://localhost:8081
WHATSAPP_API_KEY=sua_api_key

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu_email@gmail.com
SMTP_PASS=sua_senha_app
```
