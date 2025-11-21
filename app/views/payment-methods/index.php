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
                
                <!-- EFI Bank Card -->
                <div class="payment-method-card">
                    <div class="payment-method-header">
                        <div class="payment-method-logo">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                        <div class="payment-method-info">
                            <h3>EFI Bank</h3>
                            <p>Configure sua integração com EFI Bank (Gerencianet) para receber pagamentos via PIX</p>
                        </div>
                        <div class="payment-method-status" id="efiStatus">
                            <span class="status-badge status-inactive">Não Configurado</span>
                        </div>
                    </div>
                    
                    <div class="payment-method-body">
                        <form id="efiBankForm" class="payment-form">
                            <div class="form-group">
                                <label for="efiClientId">Client ID *</label>
                                <input 
                                    type="text" 
                                    id="efiClientId" 
                                    name="client_id" 
                                    placeholder="Client_Id_XXXXXXXXXXXXXXXX"
                                    required
                                >
                                <small class="form-help">
                                    Client ID da sua aplicação EFI Bank
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="efiClientSecret">Client Secret *</label>
                                <input 
                                    type="password" 
                                    id="efiClientSecret" 
                                    name="client_secret" 
                                    placeholder="Client_Secret_XXXXXXXXXXXXXXXX"
                                    required
                                >
                                <small class="form-help">
                                    Client Secret da sua aplicação EFI Bank
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="efiPixKey">Chave PIX *</label>
                                <input 
                                    type="text" 
                                    id="efiPixKey" 
                                    name="pix_key" 
                                    placeholder="sua@chave.pix"
                                    required
                                >
                                <small class="form-help">
                                    Chave PIX cadastrada na sua conta EFI Bank (email, telefone, CPF/CNPJ ou aleatória)
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="efiCertificate">Certificado SSL (Opcional)</label>
                                <input 
                                    type="text" 
                                    id="efiCertificate" 
                                    name="certificate" 
                                    placeholder="/caminho/para/certificado.pem"
                                >
                                <small class="form-help">
                                    Caminho completo para o arquivo de certificado .pem (necessário para produção)
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="efiSandbox" name="sandbox">
                                    <span>Modo Sandbox (Homologação)</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="efiEnabled" name="enabled" checked>
                                    <span>Ativar EFI Bank</span>
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="testEfiBankConnection()">
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
                
                <!-- Info Box EFI Bank -->
                <div class="info-box">
                    <div class="info-box-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <h4>Como Configurar EFI Bank</h4>
                    </div>
                    <div class="info-box-content">
                        <ol>
                            <li>Acesse o <a href="https://sejaefi.com.br" target="_blank">Portal EFI Bank</a></li>
                            <li>Vá em <strong>API</strong> → <strong>Aplicações</strong></li>
                            <li>Crie uma nova aplicação ou selecione uma existente</li>
                            <li>Copie o <strong>Client ID</strong> e <strong>Client Secret</strong></li>
                            <li>Configure uma <strong>Chave PIX</strong> na sua conta</li>
                            <li>Para produção, faça download do <strong>Certificado SSL</strong></li>
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
                            <p><strong>Importante:</strong> O certificado SSL é obrigatório para ambiente de <strong>Produção</strong>. Use o modo Sandbox para testes.</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>

    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/access-control.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/payment-methods.js"></script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>
