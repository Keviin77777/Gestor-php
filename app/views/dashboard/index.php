<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/top-servers.css">
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
            <a href="/dashboard" class="nav-item active">
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
                <h2 class="page-title">Dashboard</h2>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" placeholder="Buscar...">
                </div>

                
                <button class="notification-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notification-badge">3</span>
                </button>
            </div>
        </header>

        <!-- Estatísticas -->
        <div class="statistics-section">
            <h2 class="section-title">Estatísticas</h2>
            
            <div class="stats-grid-modern">
                <div class="modern-stat-card clients-card">
                    <div class="stat-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-content-modern">
                        <div class="stat-value-modern" id="totalClients">0</div>
                        <div class="stat-label-modern">Total de clientes</div>
                    </div>
                </div>

                <div class="modern-stat-card pending-card">
                    <div class="stat-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-content-modern">
                        <div class="stat-value-modern" id="monthRevenue">R$ 0</div>
                        <div class="stat-label-modern">Receita do mês</div>
                    </div>
                </div>

                <div class="modern-stat-card revenue-card">
                    <div class="stat-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 12l2 2 4-4"></path>
                        </svg>
                    </div>
                    <div class="stat-content-modern">
                        <div class="stat-value-modern" id="inadimplentesValue">R$ 0</div>
                        <div class="stat-label-modern" id="inadimplentesLabel">Inadimplentes</div>
                    </div>
                </div>

                <div class="modern-stat-card expiring-card">
                    <div class="stat-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-content-modern">
                        <div class="stat-value-modern" id="expiringClients">0</div>
                        <div class="stat-label-modern">Expiram hoje</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Saldo Líquido -->
        <div class="balance-section">
            <div class="balance-grid-modern">
                <div class="modern-balance-card monthly-balance">
                    <div class="balance-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="balance-content-modern">
                        <div class="balance-header-modern">
                            <h3>Saldo Líquido Mensal</h3>
                            <div class="balance-value-modern" id="monthlyBalance">R$ 0,00</div>
                        </div>
                        <div class="balance-details-modern">
                            <div class="balance-item-modern">
                                <span class="balance-label-modern">Receitas</span>
                                <span class="balance-amount-modern positive" id="monthlyRevenue">R$ 0,00</span>
                            </div>
                            <div class="balance-item-modern">
                                <span class="balance-label-modern">Despesas</span>
                                <span class="balance-amount-modern negative" id="monthlyExpenses">R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modern-balance-card annual-balance">
                    <div class="balance-icon-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                            <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"></path>
                        </svg>
                    </div>
                    <div class="balance-content-modern">
                        <div class="balance-header-modern">
                            <h3>Saldo Líquido Anual</h3>
                            <div class="balance-value-modern" id="annualBalance">R$ 0,00</div>
                        </div>
                        <div class="balance-details-modern">
                            <div class="balance-item-modern">
                                <span class="balance-label-modern">Receitas</span>
                                <span class="balance-amount-modern positive" id="annualRevenue">R$ 0,00</span>
                            </div>
                            <div class="balance-item-modern">
                                <span class="balance-label-modern">Despesas</span>
                                <span class="balance-amount-modern negative" id="annualExpenses">R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Cards - Clientes Novos e Pagamentos -->
        <div class="analytics-grid">
            <!-- Clientes Novos Por Dia -->
            <div class="analytics-card clients-card">
                <div class="analytics-header">
                    <div class="analytics-icon clients-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                    </div>
                    <div class="analytics-info">
                        <h3>Clientes Novos Por Dia</h3>
                        <p>Cadastros diários do período selecionado</p>
                    </div>
                    <div class="analytics-period">
                        <select id="clientsPeriod" class="period-select">
                            <option value="7">Últimos 7 dias</option>
                            <option value="15">Últimos 15 dias</option>
                            <option value="30" selected>Outubro 2025</option>
                        </select>
                    </div>
                </div>
                
                <div class="analytics-metrics">
                    <div class="metric-item">
                        <div class="metric-label">Total</div>
                        <div class="metric-value" id="totalNewClients">1</div>
                        <div class="metric-subtitle">clientes</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Média/Dia</div>
                        <div class="metric-value" id="avgNewClients">0.0</div>
                        <div class="metric-subtitle">últimos 7 dias</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Hoje</div>
                        <div class="metric-value" id="todayNewClients">0</div>
                        <div class="metric-subtitle">100.0%</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Melhor Dia</div>
                        <div class="metric-value" id="bestDayClients">1</div>
                        <div class="metric-subtitle">1 de outubro</div>
                    </div>
                </div>
                
                <div class="analytics-chart">
                    <canvas id="clientsChart" width="500" height="200"></canvas>
                </div>
            </div>

            <!-- Pagamentos Por Dia -->
            <div class="analytics-card payments-card">
                <div class="analytics-header">
                    <div class="analytics-icon payments-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                            <path d="M8 14h.01M12 14h.01"></path>
                        </svg>
                    </div>
                    <div class="analytics-info">
                        <h3>Pagamentos Por Dia</h3>
                        <p>Faturamento diário do período selecionado</p>
                    </div>
                    <div class="analytics-period">
                        <select id="paymentsPeriod" class="period-select">
                            <option value="7">Últimos 7 dias</option>
                            <option value="15">Últimos 15 dias</option>
                            <option value="30" selected>Outubro 2025</option>
                        </select>
                    </div>
                </div>
                
                <div class="analytics-metrics">
                    <div class="metric-item">
                        <div class="metric-label">Total</div>
                        <div class="metric-value" id="totalPayments">R$ 0</div>
                        <div class="metric-subtitle">recebido</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Média/Dia</div>
                        <div class="metric-value" id="avgPayments">R$ 0</div>
                        <div class="metric-subtitle">últimos 7 dias</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Hoje</div>
                        <div class="metric-value" id="todayPayments">0</div>
                        <div class="metric-subtitle">0.0%</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Melhor Dia</div>
                        <div class="metric-value" id="bestDayPayments">R$ 0</div>
                        <div class="metric-subtitle">N/A</div>
                    </div>
                </div>
                
                <div class="analytics-chart">
                    <canvas id="paymentsChart" width="500" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Top 5 Servidores -->
        <div class="top-servers-section">
            <div class="top-servers-card">
                <div class="top-servers-header">
                    <div class="header-left">
                        <div class="servers-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                                <line x1="6" y1="6" x2="6.01" y2="6"></line>
                                <line x1="6" y1="18" x2="6.01" y2="18"></line>
                            </svg>
                        </div>
                        <div class="header-info">
                            <h3>Top 5 Servidores</h3>
                            <p>Servidores com mais clientes no período</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <button class="ranking-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3v18h18"></path>
                                <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"></path>
                            </svg>
                            Ranking
                        </button>
                    </div>
                </div>

                <div class="top-servers-content">
                    <!-- Estatísticas Resumo -->
                    <div class="servers-stats">
                        <div class="stat-item total-clients">
                            <div class="stat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" id="totalClientsInTop">0</div>
                                <div class="stat-label">Total Clientes</div>
                                <div class="stat-sublabel">no top 5</div>
                            </div>
                        </div>

                        <div class="stat-item total-revenue">
                            <div class="stat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" id="totalRevenueInTop">R$ 0,00</div>
                                <div class="stat-label">Receita Total</div>
                                <div class="stat-sublabel">mensal</div>
                            </div>
                        </div>

                        <div class="stat-item server-costs">
                            <div class="stat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" id="totalServerCosts">R$ 0,00</div>
                                <div class="stat-label">Despesas</div>
                                <div class="stat-sublabel">de servidores</div>
                            </div>
                        </div>

                        <div class="stat-item average-clients">
                            <div class="stat-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value" id="averageClientsPerServer">0</div>
                                <div class="stat-label">Média</div>
                                <div class="stat-sublabel">clientes/servidor</div>
                            </div>
                        </div>
                    </div>

                    <!-- Visualização e Gráfico -->
                    <div class="servers-visualization">
                        <!-- Controles de Visualização -->
                        <div class="visualization-controls">
                            <div class="view-tabs">
                                <button class="view-tab active" data-view="chart">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                    </svg>
                                    Pizza
                                </button>
                                <button class="view-tab" data-view="bars">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="20" x2="12" y2="10"></line>
                                        <line x1="18" y1="20" x2="18" y2="4"></line>
                                        <line x1="6" y1="20" x2="6" y2="16"></line>
                                    </svg>
                                    Barras
                                </button>
                            </div>
                        </div>

                        <!-- Área do Gráfico Centralizada -->
                        <div class="chart-section">
                            <div class="chart-container">
                                <canvas id="serversChart" width="400" height="400"></canvas>
                                
                                <!-- Tooltip personalizado -->
                                <div class="chart-tooltip" id="serversTooltip">
                                    <div class="tooltip-header">
                                        <div class="server-color"></div>
                                        <div class="server-name"></div>
                                    </div>
                                    <div class="tooltip-stats">
                                        <div class="tooltip-stat">
                                            <span class="stat-label">Clientes:</span>
                                            <span class="stat-value clients-count"></span>
                                        </div>
                                        <div class="tooltip-stat">
                                            <span class="stat-label">Receita:</span>
                                            <span class="stat-value revenue-value"></span>
                                        </div>
                                        <div class="tooltip-stat">
                                            <span class="stat-label">% Clientes:</span>
                                            <span class="stat-value clients-percentage"></span>
                                        </div>
                                        <div class="tooltip-stat">
                                            <span class="stat-label">% Receita:</span>
                                            <span class="stat-value revenue-percentage"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Servidores -->
                        <div class="servers-list">
                            <div class="servers-list-header">
                                <h4>Ranking Detalhado</h4>
                            </div>
                            <div class="servers-list-content" id="serversListContent">
                                <!-- Lista será preenchida via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Tables -->
        <div class="content-grid">
            <!-- Revenue Chart -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>Receitas dos Últimos 6 Meses</h3>
                    <div class="card-actions">
                        <button class="btn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="1"></circle>
                                <circle cx="12" cy="5" r="1"></circle>
                                <circle cx="12" cy="19" r="1"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" width="800" height="300"></canvas>
                </div>
            </div>

            <!-- Recent Clients -->
            <div class="card">
                <div class="card-header">
                    <h3>Clientes a Vencer</h3>
                    <a href="/clients" class="btn-link">Ver todos</a>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table" id="expiringClientsTable">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                        Carregando clientes...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/theme-global.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    
    <script>
        // Forçar inicialização se necessário
        setTimeout(function() {
            console.log('Verificando inicialização...');
            if (typeof initializeDashboard === 'function') {
                console.log('Função encontrada, inicializando...');
                initializeDashboard();
            } else {
                console.error('Função initializeDashboard não encontrada!');
            }
        }, 500);
    </script>
</body>
</html>
