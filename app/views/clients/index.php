<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/clients-improved.css">
    <link rel="stylesheet" href="/assets/css/payment-history.css">
    <style>
        .field-error {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2) !important;
        }
        .field-error:focus {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.3) !important;
        }
    </style>
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
                <a href="#" class="nav-item has-submenu active" onclick="toggleSubmenu(event, 'clients-submenu')">
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
                    <a href="/clients" class="submenu-item active">
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
                <h2 class="page-title">Clientes</h2>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" placeholder="Buscar clientes..." id="searchInput">
                </div>

                <button class="btn btn-primary" id="newClientBtn" onclick="openClientModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; margin-right: 0.5rem;">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Novo Cliente
                </button>
            </div>
        </header>

        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-group">
                <select class="filter-select" id="statusFilter">
                    <option value="">Todos os status</option>
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                    <option value="suspended">Suspenso</option>
                </select>
                
                <select class="filter-select" id="planFilter">
                    <option value="">Todos os planos</option>
                    <option value="basic">Básico</option>
                    <option value="premium">Premium</option>
                    <option value="vip">VIP</option>
                </select>
                
                <input type="date" class="filter-input" id="dateFilter" placeholder="Data de vencimento">
            </div>
            
            <div class="filter-actions">
                <button class="btn btn-secondary" onclick="clearFilters()">Limpar</button>
                <button class="btn btn-secondary" onclick="exportClients()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.5rem;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Exportar
                </button>
            </div>
        </div>

        <!-- Clients Table -->
        <div class="card">
            <div class="card-body">
                <div class="scroll-hint" id="scrollHint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    <span>Arraste horizontalmente para ver todas as colunas</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </div>
                <div class="professional-table-container" id="tableContainer">
                        <table class="professional-table" id="clientsTable">
                        <thead>
                            <tr>
                                <th>CLIENTE</th>
                                <th>USUÁRIO IPTV</th>
                                <th>WHATSAPP</th>
                                <th>VENCIMENTO</th>
                                <th>SERVIDOR</th>
                                <th>MAC</th>
                                <th>NOTIFICAÇÕES</th>
                                <th>PLANO</th>
                                <th>VALOR</th>
                                <th>NÚMERO DE TELAS</th>
                                <th>AÇÕES</th>
                            </tr>
                        </thead>
                        <tbody id="clientsTableBody">
                            <!-- Dados serão carregados via JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="pagination" id="pagination">
                    <!-- Paginação será gerada via JavaScript -->
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Confirmação -->
    <div class="modern-modal" id="confirmModal">
        <div class="modern-modal-overlay" onclick="closeConfirmModal()"></div>
        <div class="modern-modal-content confirm-modal">
            <!-- Header -->
            <div class="modern-modal-header">
                <div class="modal-title-section">
                    <div class="confirm-icon" id="confirmIcon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"></path>
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                    </div>
                    <div>
                        <h2 id="confirmTitle">Confirmar Ação</h2>
                        <p class="modal-subtitle" id="confirmSubtitle">Deseja continuar?</p>
                    </div>
                </div>
                <button class="modern-modal-close" onclick="closeConfirmModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="modern-modal-body">
                <div class="confirm-content">
                    <div class="confirm-message" id="confirmMessage">
                        Tem certeza que deseja realizar esta ação?
                    </div>
                    
                    <div class="confirm-details" id="confirmDetails" style="display: none;">
                        <!-- Detalhes específicos serão inseridos aqui -->
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modern-modal-footer">
                <button type="button" class="btn-modern btn-secondary" onclick="closeConfirmModal()">Cancelar</button>
                <button type="button" class="btn-modern btn-primary" id="confirmActionBtn" onclick="executeConfirmAction()">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal Histórico de Pagamentos -->
    <div class="modern-modal" id="paymentHistoryModal">
        <div class="modern-modal-overlay" onclick="closePaymentHistoryModal()"></div>
        <div class="modern-modal-content payment-history-modal">
            <!-- Header -->
            <div class="modern-modal-header">
                <div class="modal-title-section">
                    <div class="payment-history-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <h2>Histórico de Pagamentos</h2>
                        <p class="modal-subtitle" id="paymentHistoryClientName">Cliente</p>
                    </div>
                </div>
                <button class="modern-modal-close" onclick="closePaymentHistoryModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="modern-modal-body">
                <!-- Estatísticas -->
                <div class="payment-stats-grid">
                    <div class="payment-stat-card total">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="totalInvoices">0</div>
                            <div class="stat-label">Total de Faturas</div>
                            <div class="stat-amount" id="totalAmount">R$ 0,00</div>
                        </div>
                    </div>

                    <div class="payment-stat-card paid">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="paidInvoices">0</div>
                            <div class="stat-label">Pagas</div>
                            <div class="stat-amount" id="paidAmount">R$ 0,00</div>
                        </div>
                    </div>

                    <div class="payment-stat-card pending">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="pendingInvoices">0</div>
                            <div class="stat-label">Pendentes</div>
                            <div class="stat-amount" id="pendingAmount">R$ 0,00</div>
                        </div>
                    </div>

                    <div class="payment-stat-card cancelled">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="cancelledInvoices">0</div>
                            <div class="stat-label">Canceladas</div>
                            <div class="stat-amount" id="cancelledAmount">R$ 0,00</div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="payment-filters">
                    <div class="filter-group">
                        <select class="filter-select" id="paymentStatusFilter">
                            <option value="">Todos os status</option>
                            <option value="paid">Pago</option>
                            <option value="pending">Pendente</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                        
                        <input type="month" class="filter-input" id="paymentMonthFilter" placeholder="Mês/Ano">
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn btn-secondary btn-sm" onclick="clearPaymentFilters()">Limpar</button>
                        <button class="btn btn-primary btn-sm" onclick="addPayment()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.5rem;">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Adicionar Pagamento
                        </button>
                    </div>
                </div>

                <!-- Lista de Pagamentos -->
                <div class="payment-history-container">
                    <div class="payment-history-table-container">
                        <table class="payment-history-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Método</th>
                                    <th>Referência</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="paymentHistoryTableBody">
                                <!-- Dados serão carregados via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Estado vazio -->
                    <div class="payment-empty-state" id="paymentEmptyState" style="display: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <h3>Nenhuma fatura encontrada</h3>
                        <p>Este cliente ainda não possui faturas registradas.</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modern-modal-footer">
                <button type="button" class="btn-modern btn-secondary" onclick="closePaymentHistoryModal()">Fechar</button>
                <button type="button" class="btn-modern btn-primary" onclick="exportPaymentHistory()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.5rem;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Exportar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar/Editar Cliente -->
    <div class="modern-modal" id="clientModal">
        <div class="modern-modal-overlay" onclick="closeClientModal()"></div>
        <div class="modern-modal-content">
            <!-- Header -->
            <div class="modern-modal-header">
                <div class="modal-title-section">
                    <h2 id="modalTitle">Adicionar Novo Cliente</h2>
                    <p class="modal-subtitle">Preencha os detalhes do novo cliente.</p>
                </div>
                <button class="modern-modal-close" onclick="closeClientModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="modern-modal-body">
                <form id="clientForm">
                    <div class="modern-form-grid">
                        <!-- Nome Sistema -->
                        <div class="modern-form-group">
                            <label for="clientName">Nome Sistema</label>
                            <input type="text" id="clientName" name="name" placeholder="Nome" required>
                        </div>

                        <!-- Usuário IPTV -->
                        <div class="modern-form-group">
                            <label for="clientUsername">Usuário IPTV</label>
                            <input type="text" id="clientUsername" name="username" placeholder="Opcional">
                        </div>

                        <!-- Senha IPTV -->
                        <div class="modern-form-group">
                            <label for="clientIptvPassword">Senha IPTV</label>
                            <div class="input-with-actions">
                                <input type="text" id="clientIptvPassword" name="iptv_password" placeholder="Opcional">
                                <button type="button" class="input-action-btn" onclick="generateIptvPassword()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- WhatsApp -->
                        <div class="modern-form-group">
                            <label for="clientPhone">WhatsApp</label>
                            <input type="tel" id="clientPhone" name="phone" placeholder="WhatsApp">
                        </div>

                        <!-- Vencimento -->
                        <div class="modern-form-group">
                            <label for="clientRenewalDate">Vencimento</label>
                            <input type="date" id="clientRenewalDate" name="renewal_date" placeholder="dd/mm/aaaa" required>
                        </div>

                        <!-- Servidor -->
                        <div class="modern-form-group">
                            <label for="clientServer">Servidor</label>
                            <select id="clientServer" name="server">
                                <option value="">Selecionar servidor</option>
                                <option value="server1">Servidor 1 - Principal</option>
                                <option value="server2">Servidor 2 - Backup</option>
                                <option value="server3">Servidor 3 - Premium</option>
                            </select>
                        </div>

                        <!-- MAC -->
                        <div class="modern-form-group">
                            <label for="clientMac">MAC</label>
                            <input type="text" id="clientMac" name="mac" placeholder="MAC">
                        </div>

                        <!-- Notificações -->
                        <div class="modern-form-group">
                            <label for="clientNotifications">Notificações</label>
                            <select id="clientNotifications" name="notifications">
                                <option value="sim">Sim</option>
                                <option value="nao">Não</option>
                            </select>
                        </div>

                        <!-- Plano -->
                        <div class="modern-form-group">
                            <label for="clientPlan">Plano</label>
                            <select id="clientPlan" name="plan">
                                <option value="">Selecionar plano</option>
                                <option value="basic">Básico - R$ 25,00</option>
                                <option value="premium">Premium - R$ 35,00</option>
                                <option value="vip">VIP - R$ 50,00</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>

                        <!-- Valor -->
                        <div class="modern-form-group">
                            <label for="clientValue">Valor Mensal</label>
                            <input type="number" id="clientValue" name="value" step="0.01" min="0" placeholder="0,00" required>
                        </div>

                        <!-- Email -->
                        <div class="modern-form-group">
                            <label for="clientEmail">Email</label>
                            <input type="email" id="clientEmail" name="email" placeholder="email@exemplo.com">
                        </div>
                    </div>

                    <!-- Número de Telas -->
                    <div class="modern-form-group full-width">
                        <label for="clientScreens">Número de Telas *</label>
                        <input type="number" id="clientScreens" name="screens" min="1" max="10" value="1" required placeholder="Número de Telas">
                    </div>

                    <!-- Notas -->
                    <div class="modern-form-group full-width">
                        <label for="clientNotes">Notas</label>
                        <textarea id="clientNotes" name="notes" rows="4" placeholder="Notas"></textarea>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modern-modal-footer">
                <button type="button" class="btn-modern btn-secondary" onclick="closeClientModal()">Cancelar</button>
                <button type="submit" form="clientForm" class="btn-modern btn-primary" id="submitBtn">Adicionar</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/common.js"></script>
    <!-- Modal WhatsApp -->
    <div class="modal" id="whatsappModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="whatsappModalTitle">Enviar WhatsApp</h3>
                <button class="modal-close" onclick="closeWhatsAppModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="whatsapp-client-info">
                    <div class="client-avatar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="client-details">
                        <div class="client-name" id="whatsappClientName">Nome do Cliente</div>
                        <div class="client-phone" id="whatsappClientPhone">(11) 99999-9999</div>
                    </div>
                </div>
                
                <div class="whatsapp-options">
                    <div class="option-tabs">
                        <button class="tab-btn active" onclick="switchWhatsAppTab('template')">Usar Template</button>
                        <button class="tab-btn" onclick="switchWhatsAppTab('custom')">Mensagem Personalizada</button>
                    </div>
                    
                    <div class="tab-content" id="templateTab">
                        <div class="form-group">
                            <label for="whatsappTemplateSelect">Selecionar Template:</label>
                            <select id="whatsappTemplateSelect" class="form-control">
                                <option value="">Escolha um template...</option>
                            </select>
                        </div>
                        <div class="template-preview" id="templatePreview" style="display: none;">
                            <h4>Prévia da Mensagem:</h4>
                            <div class="message-preview" id="messagePreview"></div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="customTab" style="display: none;">
                        <div class="form-group">
                            <label for="customMessage">Mensagem Personalizada:</label>
                            <textarea id="customMessage" class="form-control" rows="6" placeholder="Digite sua mensagem aqui..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeWhatsAppModal()">Cancelar</button>
                <button class="btn btn-primary" id="sendWhatsAppBtn" onclick="sendWhatsAppMessage()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 8px;">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945l-1.006 3.68 3.74-.982a9.86 9.86 0 005.26 1.51h.004c5.45 0 9.884-4.434 9.888-9.884.002-2.64-1.03-5.122-2.898-6.988a9.825 9.825 0 00-6.994-2.893z"/>
                    </svg>
                    Enviar WhatsApp
                </button>
            </div>
        </div>
    </div>

    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/theme-global.js"></script>
    <script src="/assets/js/clients.js"></script>
</body>
</html>