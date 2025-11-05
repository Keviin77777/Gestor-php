<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métodos de Pagamento - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <link rel="stylesheet" href="/assets/css/payment-methods.css">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header Menu -->
        <?php include __DIR__ . '/../components/header-menu.php'; ?>
        
        <!-- Content -->
        <div class="content-wrapper">
            <div class="payment-methods-container">
                
                <!-- Mercado Pago Card -->
                <div class="payment-method-card">
                    <div class="payment-method-header">
                        <div class="payment-method-logo">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <div class="payment-method-info">
                            <h3>Mercado Pago</h3>
                            <p>Configure sua integração com Mercado Pago para receber pagamentos via PIX</p>
                        </div>
                        <div class="payment-method-status" id="mpStatus">
                            <span class="status-badge status-inactive">Não Configurado</span>
                        </div>
                    </div>
                    
                    <div class="payment-method-body">
                        <form id="mercadoPagoForm" class="payment-form">
                            <div class="form-group">
                                <label for="mpPublicKey">Public Key *</label>
                                <input 
                                    type="text" 
                                    id="mpPublicKey" 
                                    name="public_key" 
                                    placeholder="APP_USR-XXXXXXXX-XXXXXX-XXXXXXXX"
                                    required
                                >
                                <small class="form-help">
                                    Public Key para validação de pagamentos no frontend
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="mpAccessToken">Access Token *</label>
                                <input 
                                    type="password" 
                                    id="mpAccessToken" 
                                    name="access_token" 
                                    placeholder="APP_USR-XXXXXXXX-XXXXXX-XXXXXXXX"
                                    required
                                >
                                <small class="form-help">
                                    Obtenha suas credenciais no 
                                    <a href="https://www.mercadopago.com.br/developers/panel/app" target="_blank">
                                        Painel de Desenvolvedores do Mercado Pago
                                    </a>
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="mpEnabled" name="enabled" checked>
                                    <span>Ativar Mercado Pago</span>
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="testMercadoPagoConnection()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                    </svg>
                                    Testar Conexão
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                        <polyline points="7 3 7 8 15 8"></polyline>
                                    </svg>
                                    Salvar Configurações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Info Box -->
                <div class="info-box">
                    <div class="info-box-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <h4>Como Configurar</h4>
                    </div>
                    <div class="info-box-content">
                        <ol>
                            <li>Acesse o <a href="https://www.mercadopago.com.br/developers/panel/app" target="_blank">Painel de Desenvolvedores</a> do Mercado Pago</li>
                            <li>Crie uma nova aplicação ou selecione uma existente</li>
                            <li>Copie o <strong>Access Token</strong> e <strong>Public Key</strong></li>
                            <li>Cole as credenciais nos campos acima</li>
                            <li>Clique em "Testar Conexão" para validar</li>
                            <li>Salve as configurações</li>
                        </ol>
                        
                        <div class="info-alert">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <p><strong>Importante:</strong> Use as credenciais de <strong>Produção</strong> para receber pagamentos reais.</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>

    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/payment-methods.js"></script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>
