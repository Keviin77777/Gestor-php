<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos WhatsApp - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/whatsapp.css">
</head>
<body>
    <div class="app-container">
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
                        <a href="/whatsapp/parear" class="submenu-item">
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
                        <a href="/whatsapp/scheduling" class="submenu-item active">
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
                    <h2 class="page-title">Agendamentos WhatsApp</h2>
                </div>
                <div class="header-right">
                    <button class="btn btn-secondary" onclick="refreshScheduling()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                        </svg>
                        Atualizar
                    </button>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Scheduling List -->
                <div class="templates-list-container">
                    <!-- Table Header -->
                    <div class="templates-list-header">
                        <div class="header-col col-name">Template</div>
                        <div class="header-col col-type">Tipo</div>
                        <div class="header-col col-days">Dias da Semana</div>
                        <div class="header-col col-time">Horário</div>
                        <div class="header-col col-status">Status</div>
                        <div class="header-col col-actions">Ações</div>
                    </div>
                    
                    <!-- Table Body -->
                    <div class="templates-list" id="schedulingList">
                        <!-- Agendamentos serão carregados aqui -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Editar Agendamento -->
    <div class="modal" id="schedulingModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="schedulingModalTitle">Configurar Agendamento</h3>
                <button class="modal-close" onclick="closeSchedulingModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="schedulingForm">
                    <input type="hidden" id="schedulingTemplateId">
                    
                    <div class="form-group">
                        <label>Template Selecionado</label>
                        <div class="template-info-card" id="templateInfo">
                            <!-- Informações do template serão preenchidas aqui -->
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label checkbox-switch">
                            <input type="checkbox" id="schedulingEnabled" onchange="toggleSchedulingOptions()">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Ativar Agendamento Automático</span>
                        </label>
                    </div>
                    
                    <div id="schedulingOptions" class="scheduling-options" style="display: none;">
                        <div class="form-group">
                            <label>Dias da Semana</label>
                            <div class="days-selector">
                                <label class="day-checkbox">
                                    <input type="checkbox" value="sunday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Dom</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="monday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Seg</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="tuesday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Ter</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="wednesday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Qua</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="thursday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Qui</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="friday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Sex</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="saturday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Sáb</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="schedulingTime">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Horário de Envio
                            </label>
                            <input type="time" id="schedulingTime" class="form-control" value="09:00">
                        </div>
                        
                        <div class="info-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <div>
                                <strong>Como funciona:</strong>
                                <p>O template será enviado automaticamente nos dias e horário selecionados para clientes que atendam aos critérios do tipo de template.</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSchedulingModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveScheduling()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar Agendamento
                </button>
            </div>
        </div>
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
    <script src="/assets/js/whatsapp-scheduling.js"></script>
</body>
</html>
