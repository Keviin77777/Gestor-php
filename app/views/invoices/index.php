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
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

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