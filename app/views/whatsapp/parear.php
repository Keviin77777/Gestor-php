<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Parear WhatsApp - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <link rel="stylesheet" href="/assets/css/whatsapp.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header Menu -->
            <?php include __DIR__ . '/../components/header-menu.php'; ?>
            
            <!-- Content -->
            <div class="content">
                <!-- Connection Section - Primeiro -->
                <div class="connection-section">
                    <!-- API Provider Selection -->
                    <div class="provider-selection-card">
                        <div class="provider-header">
                            <h3>Escolha o Provedor de API</h3>
                            <p>Selecione qual API WhatsApp deseja utilizar</p>
                        </div>
                        
                        <div class="provider-options">
                            <label class="provider-option" for="providerNative">
                                <input type="radio" id="providerNative" name="apiProvider" value="native" checked>
                                <div class="provider-card">
                                    <div class="provider-badge recommended">Recomendado</div>
                                    <div class="provider-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                        </svg>
                                    </div>
                                    <h4>API Premium</h4>
                                    <p>Máxima performance e estabilidade</p>
                                    <ul class="provider-features">
                                        <li>✅ Conexão ultra estável</li>
                                        <li>✅ Reconexão automática inteligente</li>
                                        <li>✅ Suporte a múltiplas instâncias</li>
                                        <li>✅ Sistema de fila otimizado</li>
                                    </ul>
                                </div>
                            </label>
                            
                            <label class="provider-option" for="providerEvolution">
                                <input type="radio" id="providerEvolution" name="apiProvider" value="evolution">
                                <div class="provider-card">
                                    <div class="provider-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                        </svg>
                                    </div>
                                    <h4>API Básica</h4>
                                    <p>Solução simples e funcional</p>
                                    <ul class="provider-features">
                                        <li>⚠️ Requer configuração externa</li>
                                        <li>⚠️ Estabilidade moderada</li>
                                        <li>⚠️ Pode apresentar quedas</li>
                                    </ul>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="connection-card">
                        <div class="connection-status" id="connectionStatus">
                            <div class="status-icon" id="statusIcon">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                                </svg>
                            </div>
                            <div class="status-info">
                                <h3 id="statusTitle">WhatsApp Desconectado</h3>
                                <p id="statusDescription">Selecione uma API e clique em "Conectar"</p>
                                <span class="provider-badge-small" id="currentProvider">API Premium</span>
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

                <!-- Rate Limit Configuration -->
                <div class="rate-limit-section">
                    <div class="rate-limit-card">
                        <div class="rate-limit-header">
                            <div class="rate-limit-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="rate-limit-info">
                                <h3>⚙️ Configuração de Limites de Envio</h3>
                                <p>Configure os limites para evitar bloqueios do WhatsApp</p>
                            </div>
                        </div>
                        
                        <div class="rate-limit-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Mensagens por Minuto</label>
                                    <input type="number" id="messagesPerMinute" min="1" max="60" value="20">
                                    <small>Recomendado: 15-20 para evitar bloqueios</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Mensagens por Hora</label>
                                    <input type="number" id="messagesPerHour" min="10" max="500" value="100">
                                    <small>Recomendado: 80-100 para uso seguro</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Delay entre Mensagens (segundos)</label>
                                    <input type="number" id="delayBetween" min="1" max="60" value="3">
                                    <small>Recomendado: 3-5 segundos</small>
                                </div>
                            </div>
                            
                            <div class="alert-warning">
                                <strong>⚠️ Atenção:</strong> Configurações muito agressivas podem resultar em bloqueio da conta WhatsApp.
                            </div>
                            
                            <button class="btn-save-limits" onclick="saveRateLimits()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                Salvar Configuração
                            </button>
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
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/access-control.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/whatsapp-parear.js"></script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>