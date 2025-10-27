<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Parear WhatsApp - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/whatsapp.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h2>UltraGestor</h2>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="/dashboard" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
                
                <div class="nav-group">
                    <a href="#" class="nav-item has-submenu" onclick="toggleSubmenu(event, 'clients-submenu')">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span class="nav-text">Clientes</span>
                        <svg class="submenu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </a>
                    <div class="submenu" id="clients-submenu">
                        <a href="/clients" class="submenu-item">
                            <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                            </svg>
                            <span>Lista de Clientes</span>
                        </a>
                        <a href="/plans" class="submenu-item">
                            <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span>Planos</span>
                        </a>
                        <a href="/applications" class="submenu-item">
                            <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                            <span>Aplicativos</span>
                        </a>
                    </div>
                </div>
                
                <a href="/invoices" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Faturas</span>
                </a>
                
                <a href="/servidores" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                    </svg>
                    <span>Servidores</span>
                </a>
                
                <div class="nav-group">
                    <a href="#" class="nav-item has-submenu active" onclick="toggleSubmenu(event, 'whatsapp-submenu')">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                        <span class="nav-text">WhatsApp</span>
                        <svg class="submenu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </a>
                    <div class="submenu expanded" id="whatsapp-submenu">
                        <a href="/whatsapp/parear" class="submenu-item active">
                            <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                            <span>Parear WhatsApp</span>
                        </a>
                        <a href="/whatsapp/templates" class="submenu-item">
                            <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span>Templates</span>
                        </a>
                        <a href="/whatsapp/scheduling" class="submenu-item">
                            <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                <path d="M8 14h.01M12 14h.01M16 14h.01"></path>
                            </svg>
                            <span>Agendamentos</span>
                        </a>
                    </div>
                </div>
                
                <a href="/settings" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m5.2-13.2l-4.2 4.2m0 6l4.2 4.2M23 12h-6m-6 0H1m18.2 5.2l-4.2-4.2m-6 0l-4.2 4.2"></path>
                    </svg>
                    <span>Configurações</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar" id="userAvatar"></div>
                    <div class="user-details">
                        <div class="user-name" id="userName">Carregando...</div>
                        <div class="user-email" id="userEmail"></div>
                    </div>
                </div>
                <button class="btn-logout" onclick="logout()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Sair
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <h2 class="page-title">Parear WhatsApp</h2>
                </div>
                <div class="header-right">
                    <div class="search-box" id="searchBox">
                        <input type="text" placeholder="Buscar..." id="searchInput">
                        <svg class="search-icon" id="searchIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </div>
                    
                    <button class="notification-btn" id="notificationBtn" type="button" aria-label="Notificações">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span class="notification-badge">3</span>
                    </button>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Connection Section - Primeiro -->
                <div class="connection-section">
                    <div class="connection-card">
                        <div class="connection-status" id="connectionStatus">
                            <div class="status-icon" id="statusIcon">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                                </svg>
                            </div>
                            <div class="status-info">
                                <h3 id="statusTitle">WhatsApp Desconectado</h3>
                                <p id="statusDescription">Clique em "Conectar" para iniciar o pareamento</p>
                            </div>
                        </div>
                        
                        <div class="connection-actions">
                            <button class="btn btn-primary" id="connectBtn" onclick="connectWhatsApp()">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8.5 12.5l2 2 5-5M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
                                </svg>
                                Conectar WhatsApp
                            </button>
                            
                            <button class="btn btn-danger" id="disconnectBtn" onclick="disconnectWhatsApp()" style="display: none;">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/>
                                </svg>
                                Desconectar
                            </button>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="qr-code-card" id="qrCodeCard" style="display: none;">
                        <div class="qr-header">
                            <h3>Escaneie o QR Code</h3>
                            <p>Abra o WhatsApp no seu celular e escaneie o código abaixo</p>
                        </div>
                        
                        <div class="qr-code-container">
                            <div class="qr-code" id="qrCodeImage">
                                <div class="qr-loading">
                                    <div class="spinner"></div>
                                    <p>Gerando QR Code...</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="qr-instructions">
                            <div class="instruction-step">
                                <span class="step-number">1</span>
                                <p>Abra o WhatsApp no seu celular</p>
                            </div>
                            <div class="instruction-step">
                                <span class="step-number">2</span>
                                <p>Toque em "Mais opções" ou "Configurações"</p>
                            </div>
                            <div class="instruction-step">
                                <span class="step-number">3</span>
                                <p>Toque em "Aparelhos conectados"</p>
                            </div>
                            <div class="instruction-step">
                                <span class="step-number">4</span>
                                <p>Toque em "Conectar um aparelho"</p>
                            </div>
                            <div class="instruction-step">
                                <span class="step-number">5</span>
                                <p>Aponte a câmera para este código</p>
                            </div>
                        </div>
                    </div>

                    <!-- Account Info -->
                    <div class="account-info-card" id="accountInfoCard" style="display: none;">
                        <div class="account-header">
                            <div class="account-avatar" id="accountAvatar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div class="account-details">
                                <h3 id="accountName">Nome da Conta</h3>
                                <p id="accountPhone">+55 11 99999-9999</p>
                            </div>
                        </div>
                        
                        <div class="account-stats">
                            <div class="stat-item">
                                <span class="stat-label">Status</span>
                                <span class="stat-value connected" id="connectionStatusValue">Conectado</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Conectado em</span>
                                <span class="stat-value" id="connectedAt">--</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Última atividade</span>
                                <span class="stat-value" id="lastSeen">--</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Cards -->
                <div class="features-section">
                    <div class="features-grid">
                        <!-- Envio Automático -->
                        <div class="feature-card">
                            <div class="feature-header">
                                <div class="feature-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 11H1l8-8 8 8h-8v8z"></path>
                                    </svg>
                                </div>
                                <div class="feature-info">
                                    <h3>Envio Automático</h3>
                                    <p>Envie cobranças automaticamente para clientes</p>
                                </div>
                            </div>
                            
                            <div class="feature-benefits">
                                <div class="benefit-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 12l2 2 4-4"></path>
                                        <circle cx="12" cy="12" r="10"></circle>
                                    </svg>
                                    <span>Cobrança de renovação</span>
                                </div>
                                <div class="benefit-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 12l2 2 4-4"></path>
                                        <circle cx="12" cy="12" r="10"></circle>
                                    </svg>
                                    <span>Lembretes de vencimento</span>
                                </div>
                                <div class="benefit-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 12l2 2 4-4"></path>
                                        <circle cx="12" cy="12" r="10"></circle>
                                    </svg>
                                    <span>Confirmação de pagamento</span>
                                </div>
                                <div class="benefit-item warning">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <span>Avisos de suspensão</span>
                                </div>
                            </div>
                        </div>

                        <!-- Gestão de Clientes -->
                        <div class="feature-card">
                            <div class="feature-header">
                                <div class="feature-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                <div class="feature-info">
                                    <h3>Gestão de Clientes</h3>
                                    <p>Integração com sua base de clientes</p>
                                </div>
                            </div>
                            
                            <div class="feature-benefits">
                                <div class="benefit-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 12l2 2 4-4"></path>
                                        <circle cx="12" cy="12" r="10"></circle>
                                    </svg>
                                    <span>Sincronização automática</span>
                                </div>
                                <div class="benefit-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 12l2 2 4-4"></path>
                                        <circle cx="12" cy="12" r="10"></circle>
                                    </svg>
                                    <span>Números validados</span>
                                </div>
                                <div class="benefit-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 12l2 2 4-4"></path>
                                        <circle cx="12" cy="12" r="10"></circle>
                                    </svg>
                                    <span>Histórico de mensagens</span>
                                </div>
                                <div class="benefit-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 12l2 2 4-4"></path>
                                        <circle cx="12" cy="12" r="10"></circle>
                                    </svg>
                                    <span>Status de entrega</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- How it Works -->
                <div class="how-it-works-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                        <h2>Como Funciona</h2>
                    </div>

                    <div class="steps-container">
                        <div class="steps-grid">
                            <!-- Configuração Inicial -->
                            <div class="step-card">
                                <div class="step-header">
                                    <div class="step-number">1</div>
                                    <h3>Configuração Inicial</h3>
                                </div>
                                <div class="step-content">
                                    <div class="step-item">
                                        <span class="step-bullet">1</span>
                                        <span>Clique em "Conectar WhatsApp"</span>
                                    </div>
                                    <div class="step-item">
                                        <span class="step-bullet">2</span>
                                        <span>Escaneie o QR Code com seu celular</span>
                                    </div>
                                    <div class="step-item">
                                        <span class="step-bullet">3</span>
                                        <span>Aguarde a confirmação da conexão</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Envio Automático -->
                            <div class="step-card">
                                <div class="step-header">
                                    <div class="step-number">2</div>
                                    <h3>Envio Automático</h3>
                                </div>
                                <div class="step-content">
                                    <div class="step-item">
                                        <span class="step-bullet">1</span>
                                        <span>Configure os números dos clientes</span>
                                    </div>
                                    <div class="step-item">
                                        <span class="step-bullet">2</span>
                                        <span>Gere as cobranças normalmente</span>
                                    </div>
                                    <div class="step-item">
                                        <span class="step-bullet">3</span>
                                        <span>As mensagens serão enviadas automaticamente</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Loading Manager -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p id="loadingText">Carregando...</p>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/whatsapp-parear.js"></script>
</body>
</html>