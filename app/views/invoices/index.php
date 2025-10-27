<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Faturas - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/invoices.css">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
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
            
            <a href="/invoices" class="nav-item active">
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
        <div class="header">
            <div class="header-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1 class="page-title">Faturas</h1>
            </div>
            <div class="header-right">
                <div class="search-box" id="searchBox">
                    <input type="text" placeholder="Buscar faturas..." id="searchInput">
                    <svg class="search-icon" id="searchIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="M21 21l-4.35-4.35"></path>
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
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons-bar">
            <button class="btn btn-secondary" onclick="runInvoiceAutomation()" title="Gerar faturas automáticas para clientes com renovação próxima">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                </svg>
                Automação
            </button>
            
            <button class="btn btn-secondary" onclick="exportInvoices()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Exportar
            </button>
        </div>

        <!-- Resumo das Faturas -->
        <div class="invoices-summary">
            <div class="summary-card">
                <div class="summary-icon pending">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="9" stroke-width="2"/>
                        <path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="1" fill="currentColor"/>
                    </svg>
                </div>
                <div class="summary-content">
                    <h3>💰 Faturas Pendentes</h3>
                    <div class="summary-value" id="pendingCount">-</div>
                    <div class="summary-amount" id="pendingAmount">R$ -</div>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon paid">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="9" stroke-width="2"/>
                        <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="summary-content">
                    <h3>✅ Faturas Pagas</h3>
                    <div class="summary-value" id="paidCount">-</div>
                    <div class="summary-amount" id="paidAmount">R$ -</div>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon overdue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="9" stroke-width="2"/>
                        <path d="M12 8v4" stroke-linecap="round"/>
                        <path d="M12 16h.01" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="summary-content">
                    <h3>⚠️ Faturas Vencidas</h3>
                    <div class="summary-value" id="overdueCount">-</div>
                    <div class="summary-amount" id="overdueAmount">R$ -</div>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon total">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="9" stroke-width="2"/>
                        <path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 15h8" stroke-linecap="round"/>
                        <path d="M10 18h4" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="summary-content">
                    <h3>📊 Receita Total</h3>
                    <div class="summary-value" id="totalCount">-</div>
                    <div class="summary-amount" id="totalAmount">R$ -</div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <div class="filters-group">
                <button class="filter-btn active" data-status="all">Todas</button>
                <button class="filter-btn" data-status="pending">Em Aberto</button>
                <button class="filter-btn" data-status="paid">Pagas</button>
                <button class="filter-btn" data-status="overdue">Vencidas</button>
            </div>
            <div class="actions-group">
                <button class="btn btn-primary" onclick="openInvoiceModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nova Fatura
                </button>
            </div>
        </div>

        <!-- Histórico de Faturas -->
        <div class="invoices-history">
            <div class="card">
                <div class="card-header">
                    <h3>Histórico de Faturas</h3>
                    <div class="card-actions">
                        <button class="btn-icon" title="Atualizar" onclick="loadInvoices()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="card-body" id="invoicesContainer">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Carregando faturas...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/dashboard-theme.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/invoices.js"></script>
    <script>
        // Função para controlar submenu
        function toggleSubmenu(event, submenuId) {
            event.preventDefault();
            event.stopPropagation();
            
            const submenu = document.getElementById(submenuId);
            const navItem = event.currentTarget;
            
            if (!submenu || !navItem) return;
            
            // Toggle classes
            navItem.classList.toggle('expanded');
            submenu.classList.toggle('expanded');
        }

        // Função de logout
        function logout() {
            if (confirm('Tem certeza que deseja sair?')) {
                fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '/login';
                    }
                })
                .catch(error => {
                    console.error('Erro ao fazer logout:', error);
                    window.location.href = '/login';
                });
            }
        }
    </script>
</body>
</html>