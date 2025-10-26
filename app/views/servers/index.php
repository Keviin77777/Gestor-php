<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servidores - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1 class="logo">UltraGestor</h1>
            <button class="sidebar-toggle" id="sidebarToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
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
            
            <a href="/servidores" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
                <span>Servidores</span>
            </a>
            
            <div class="nav-group">
                <a href="#" class="nav-item has-submenu" onclick="toggleSubmenu(event, 'whatsapp-submenu')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                    <span class="nav-text">WhatsApp</span>
                    <svg class="submenu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </a>
                <div class="submenu" id="whatsapp-submenu">
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
                <h2 class="page-title">Servidores</h2>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" placeholder="Buscar servidores..." id="searchInput">
                </div>
                <button class="btn btn-primary" id="newServerBtn" onclick="openServerModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; margin-right: 0.5rem;">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Adicionar Servidor
                </button>
            </div>
        </header>

        <!-- Estatísticas -->
        <div class="statistics-section">
            <div class="stats-grid-modern">
                <div class="modern-stat-card servers-card">
                    <div class="stat-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <div class="stat-content-modern">
                        <div class="stat-value-modern" id="totalServers">0</div>
                        <div class="stat-label-modern">Total de Servidores</div>
                    </div>
                </div>

                <div class="modern-stat-card active-servers-card">
                    <div class="stat-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="10 8 14 12 10 16"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content-modern">
                        <div class="stat-value-modern" id="activeServers">0</div>
                        <div class="stat-label-modern">Servidores Ativos</div>
                    </div>
                </div>

                <div class="modern-stat-card server-costs-card">
                    <div class="stat-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-content-modern">
                        <div class="stat-value-modern" id="totalServerCosts">R$ 0,00</div>
                        <div class="stat-label-modern">Despesas de Servidores</div>
                    </div>
                </div>

                <div class="modern-stat-card connected-clients-card">
                    <div class="stat-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-content-modern">
                        <div class="stat-value-modern" id="connectedClients">0</div>
                        <div class="stat-label-modern">Clientes Conectados</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Servers Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-container">
                    <table class="table" id="serversTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo de Cobrança</th>
                                <th>Custo</th>
                                <th>Painel</th>
                                <th>URL</th>
                                <th>Clientes Conectados</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                    <div class="loading-state">
                                        <div class="loading-spinner">
                                            <div class="spinner-ring"></div>
                                            <div class="spinner-ring"></div>
                                            <div class="spinner-ring"></div>
                                        </div>
                                        <p>Carregando servidores...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Servidor -->
    <div class="modal" id="serverModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Novo Servidor</h3>
                <button class="modal-close" onclick="closeServerModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="serverForm">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="serverName">Nome do Servidor *</label>
                            <input type="text" id="serverName" name="name" placeholder="Ex: Servidor Principal" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="billingType">Tipo de Cobrança *</label>
                            <select id="billingType" name="billing_type" required>
                                <option value="">Selecione...</option>
                                <option value="fixed">Valor Fixo</option>
                                <option value="per_active">Por Ativo</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="serverCost">Valor Mensal *</label>
                            <input type="text" id="serverCost" name="cost" placeholder="R$ 0,00" required>
                        </div>
                    </div>

                    <!-- Integração com Painel -->
                    <div style="margin-top: 2rem;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                            Integração com Painel
                        </h3>

                        <div class="form-grid">
                            <!-- Tipo de Painel -->
                            <div class="form-group full-width">
                                <label for="panelType">Tipo de Painel</label>
                                <select id="panelType" name="panel_type" onchange="togglePanelFields()">
                                    <option value="">Selecione o tipo de painel...</option>
                                    <option value="qpanel_sigma">Qpanel/Sigma</option>
                                </select>
                            </div>
                        </div>

                        <!-- Campos Qpanel/Sigma -->
                        <div id="qpanelFields" style="display: none; margin-top: 1.5rem;">
                            <div class="form-grid">
                                <!-- URL do Painel -->
                                <div class="form-group full-width">
                                    <label for="panelUrl">URL do Painel *</label>
                                    <input type="url" id="panelUrl" name="panel_url" placeholder="https://seu-painel.com.br">
                                    <small style="display: block; margin-top: 0.5rem; color: var(--text-tertiary); font-size: 0.75rem;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 0.25rem;">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="16" x2="12" y2="12"></line>
                                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                        </svg>
                                        Especifique o https:// se necessário
                                    </small>
                                </div>

                                <!-- Usuário Revenda -->
                                <div class="form-group">
                                    <label for="resellerUser">Usuário Revenda *</label>
                                    <input type="text" id="resellerUser" name="reseller_user" placeholder="seu_usuario">
                                </div>

                                <!-- Token do Sigma -->
                                <div class="form-group">
                                    <label for="sigmaToken">Token do Sigma *</label>
                                    <input type="password" id="sigmaToken" name="sigma_token" placeholder="••••••••••••">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeServerModal()">Cancelar</button>
                <button type="button" class="btn btn-secondary" id="testConnectionBtn" onclick="testConnection()" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.5rem;">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    Testar Conexão
                </button>
                <button type="submit" class="btn btn-primary" onclick="saveServer()">Salvar</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/servers.js"></script>
</body>
</html>