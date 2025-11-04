<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Dashboard - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/top-servers.css">
    <link rel="stylesheet" href="/assets/css/metric-cards.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header Menu -->
        <?php include __DIR__ . '/../components/header-menu.php'; ?>

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
                            <optgroup label="📅 Períodos Rápidos">
                                <option value="today">Hoje</option>
                                <option value="yesterday">Ontem</option>
                                <option value="this-week">Esta Semana</option>
                                <option value="last-week">Semana Passada</option>
                                <option value="this-month" selected><?php
                                    $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                    echo $meses[date('n') - 1] . ' ' . date('Y');
                                ?></option>
                                <option value="last-month">Mês Passado</option>
                            </optgroup>
                            <optgroup label="🏳️ Por Quantidade de Dias">
                                <option value="7">Últimos 7 Dias</option>
                                <option value="15">Últimos 15 Dias</option>
                                <option value="30">Últimos 30 Dias</option>
                                <option value="60">Últimos 60 Dias</option>
                                <option value="90">Últimos 90 Dias</option>
                                <option value="180">Últimos 6 Meses</option>
                            </optgroup>
                            <optgroup label="📊 Períodos Longos">
                                <option value="this-quarter">Trimestre Atual</option>
                                <option value="last-quarter">Trimestre Passado</option>
                                <option value="this-year">Ano Atual</option>
                                <option value="last-year">Ano Passado</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
                
                <div class="analytics-metrics-grid">
                    <div class="metric-card metric-card-green">
                        <div class="metric-card-value" id="totalNewClients">0</div>
                        <div class="metric-card-label">Total - Mês Atual</div>
                    </div>
                    
                    <div class="metric-card metric-card-blue">
                        <div class="metric-card-value" id="avgNewClients">0.0</div>
                        <div class="metric-card-label">Média por Dia</div>
                    </div>
                    
                    <div class="metric-card metric-card-purple">
                        <div class="metric-card-value" id="bestDayClients">0</div>
                        <div class="metric-card-label">Melhor Dia</div>
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
                            <optgroup label="📅 Períodos Rápidos">
                                <option value="today">Hoje</option>
                                <option value="yesterday">Ontem</option>
                                <option value="this-week">Esta Semana</option>
                                <option value="last-week">Semana Passada</option>
                                <option value="this-month" selected><?php
                                    $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                    echo $meses[date('n') - 1] . ' ' . date('Y');
                                ?></option>
                                <option value="last-month">Mês Passado</option>
                            </optgroup>
                            <optgroup label="🏳️ Por Quantidade de Dias">
                                <option value="7">Últimos 7 Dias</option>
                                <option value="15">Últimos 15 Dias</option>
                                <option value="30">Últimos 30 Dias</option>
                                <option value="60">Últimos 60 Dias</option>
                                <option value="90">Últimos 90 Dias</option>
                                <option value="180">Últimos 6 Meses</option>
                            </optgroup>
                            <optgroup label="📊 Períodos Longos">
                                <option value="this-quarter">Trimestre Atual</option>
                                <option value="last-quarter">Trimestre Passado</option>
                                <option value="this-year">Ano Atual</option>
                                <option value="last-year">Ano Passado</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
                
                <div class="analytics-metrics-grid">
                    <div class="metric-card metric-card-yellow">
                        <div class="metric-card-value" id="totalPayments">R$ 0</div>
                        <div class="metric-card-label">Total - Mês Atual</div>
                    </div>
                    
                    <div class="metric-card metric-card-cyan">
                        <div class="metric-card-value" id="avgPayments">R$ 0</div>
                        <div class="metric-card-label">Média por Dia</div>
                    </div>
                    
                    <div class="metric-card metric-card-pink">
                        <div class="metric-card-value" id="bestDayPayments">R$ 0</div>
                        <div class="metric-card-label">Melhor Dia</div>
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
                    <div>
                        <h3>Clientes a Vencer</h3>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.25rem;">Próximos 7 dias</p>
                    </div>
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

    <!-- Garantir que sidebar está fechada antes de carregar qualquer script -->
    <script>
        (function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            
            if (sidebar) {
                sidebar.classList.remove('active');
                // Forçar inline para garantir que funciona no mobile
                if (window.innerWidth <= 768) {
                    sidebar.style.transform = 'translateX(-100%)';
                }
            }
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('active');
            }
            if (mobileMenuBtn) {
                mobileMenuBtn.classList.remove('active');
            }
            if (document.body) {
                document.body.classList.remove('sidebar-open');
            }
        })();
    </script>
    
    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/theme-global.js"></script>
    <script src="/assets/js/dashboard-periods.js"></script>
    <script src="/assets/js/dashboard-charts.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    
    <script>
        // Inicialização completa do dashboard mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile Menu Toggle
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function openSidebar() {
                if (sidebar) sidebar.classList.add('active');
                if (sidebarOverlay) sidebarOverlay.classList.add('active');
                if (mobileMenuBtn) mobileMenuBtn.classList.add('active');
                document.body.classList.add('sidebar-open');
            }
            
            function closeSidebar() {
                if (sidebar) sidebar.classList.remove('active');
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                if (mobileMenuBtn) mobileMenuBtn.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
            
            function toggleSidebar() {
                if (sidebar && sidebar.classList.contains('active')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }
            
            // Event listeners para mobile menu
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
                
                // Touch events para melhor responsividade
                mobileMenuBtn.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            
            // Fechar sidebar ao clicar no overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
                sidebarOverlay.addEventListener('touchend', closeSidebar);
            }
            
            // Fechar sidebar ao pressionar ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });
            
            // Fechar sidebar ao clicar em links da navegação (mobile) - APENAS links que navegam
            if (sidebar) {
                // Apenas links diretos que realmente navegam (não têm submenu e têm href válido)
                const directNavLinks = sidebar.querySelectorAll('a.nav-item:not(.has-submenu)[href]:not([href="#"])');
                directNavLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth <= 768) {
                            setTimeout(closeSidebar, 100);
                        }
                    });
                });

                // Links de submenu que realmente navegam
                const submenuLinks = sidebar.querySelectorAll('a.submenu-item[href]:not([href="#"])');
                submenuLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth <= 768) {
                            setTimeout(closeSidebar, 100);
                        }
                    });
                });
            }
            
            // Search Box Mobile
            const searchBox = document.getElementById('searchBox');
            const searchIcon = document.getElementById('searchIcon');
            const searchInput = document.getElementById('searchInput');
            
            function setupSearchBox() {
                if (!searchIcon || !searchBox || !searchInput) return;
                
                function performSearch() {
                    if (searchInput.value.trim()) {
                        window.location.href = '/clients?search=' + encodeURIComponent(searchInput.value);
                    } else {
                        searchInput.focus();
                    }
                }
                
                searchIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    performSearch();
                });
                
                searchIcon.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    performSearch();
                });
                
                // Eventos do input
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        performSearch();
                    }
                });
                
                searchInput.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            setupSearchBox();
            

            
            // Notification Button
            const notificationBtn = document.getElementById('notificationBtn');
            if (notificationBtn) {
                notificationBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Implementar modal de notificações ou redirecionamento
                    alert('Você tem 3 notificações pendentes!\n\n• Cliente João Silva vence hoje\n• Pagamento recebido: R$ 150,00\n• Novo cliente cadastrado');
                });
            }
            
            // Garantir que sidebar inicie fechada no mobile
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
            
            // Redimensionamento da janela
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    // Mobile: garantir que sidebar esteja fechada
                    closeSidebar();
                    
                    // Reconfigurar search box
                    if (searchBox && !searchBox.classList.contains('expanded')) {
                        searchBox.classList.remove('expanded');
                    }
                } else {
                    // Desktop: remover classes mobile
                    if (sidebar) sidebar.classList.remove('active');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                    if (mobileMenuBtn) mobileMenuBtn.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                    
                    // Resetar search box
                    if (searchBox) searchBox.classList.remove('expanded');
                }
            });
            
            });
        
        // Forçar inicialização do dashboard se necessário
        setTimeout(function() {
            if (typeof initializeDashboard === 'function') {
                initializeDashboard();
            } else {
                // Carregar dados básicos se a função principal não estiver disponível
                if (typeof loadUserData === 'function') {
                    loadUserData();
                }
            }
        }, 500);
    </script>
</body>
</html>
