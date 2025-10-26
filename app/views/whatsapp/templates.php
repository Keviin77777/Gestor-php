<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates WhatsApp - UltraGestor</title>
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
                        <a href="/whatsapp/templates" class="submenu-item active">
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
                    <h2 class="page-title">Templates de Mensagens</h2>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openTemplateModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Novo Template
                    </button>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Templates List -->
                <div class="templates-list-container">
                    <!-- Table Header -->
                    <div class="templates-list-header">
                        <div class="header-col col-name">Nome</div>
                        <div class="header-col col-type">Tipo</div>
                        <div class="header-col col-media">Mídia</div>
                        <div class="header-col col-default">Padrão</div>
                        <div class="header-col col-status">Status</div>
                        <div class="header-col col-actions">Ações</div>
                    </div>
                    
                    <!-- Table Body -->
                    <div class="templates-list" id="templatesList">
                        <!-- Templates serão carregados aqui -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Template -->
    <div class="modal" id="templateModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="modalTitle">Novo Template</h3>
                <button class="modal-close" onclick="closeTemplateModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" id="templateId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="templateName">Nome do Template</label>
                            <input type="text" id="templateName" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="templateType">Tipo</label>
                            <select id="templateType" class="form-control" required>
                                <option value="">Selecione...</option>
                                <option value="welcome">Boas Vindas</option>
                                <option value="invoice_generated">Fatura Gerada</option>
                                <option value="renewed">Renovação Confirmada</option>
                                <option value="expires_3d">Vence em 3 dias</option>
                                <option value="expires_7d">Vence em 7 dias</option>
                                <option value="expires_today">Vence Hoje</option>
                                <option value="expired_1d">Vencido há 1 dia</option>
                                <option value="expired_3d">Vencido há 3 dias</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="templateTitle">Título</label>
                        <input type="text" id="templateTitle" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="templateMessage">Mensagem</label>
                        <textarea id="templateMessage" class="form-control" rows="10" required></textarea>
                        <small class="form-text">Use {{variavel}} para inserir variáveis dinâmicas</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Variáveis Disponíveis</label>
                        <div class="variables-list" id="variablesList">
                            <span class="variable-tag" onclick="insertVariable('cliente_nome')">{{cliente_nome}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_usuario')">{{cliente_usuario}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_senha')">{{cliente_senha}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_servidor')">{{cliente_servidor}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_plano')">{{cliente_plano}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_vencimento')">{{cliente_vencimento}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_valor')">{{cliente_valor}}</span>
                            <span class="variable-tag" onclick="insertVariable('fatura_valor')">{{fatura_valor}}</span>
                            <span class="variable-tag" onclick="insertVariable('fatura_vencimento')">{{fatura_vencimento}}</span>
                            <span class="variable-tag" onclick="insertVariable('fatura_periodo')">{{fatura_periodo}}</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="templateActive" checked>
                                <span>Template Ativo</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="templateDefault">
                                <span>Template Padrão</span>
                            </label>
                        </div>
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTemplateModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">Salvar Template</button>
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

    <!-- Modal Visualizar Template -->
    <div class="modal" id="viewTemplateModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Visualizar Template</h3>
                <button class="modal-close" onclick="closeViewModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="phone-preview-container">
                    <div class="view-message" id="viewMessage"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="editFromView()">Editar Template</button>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/whatsapp-templates.js"></script>
</body>
</html>
