<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Pagamentos - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/admin-responsive.css">
    <style>
        .payment-history-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .page-title svg {
            width: 32px;
            height: 32px;
            color: var(--primary);
        }
        
        /* Filtros */
        .filters-card {
            background: var(--bg-primary);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.625rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-primary);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }
        
        .stat-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1.2;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .stat-icon.pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .stat-icon.approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .stat-icon.rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .stat-icon svg {
            width: 24px;
            height: 24px;
        }
        
        .stat-content {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }
        
        /* Tabela */
        .table-card {
            background: var(--bg-primary);
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: var(--bg-secondary);
        }
        
        th {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        td {
            padding: 1rem;
            border-top: 1px solid var(--border);
            font-size: 0.875rem;
            color: var(--text-primary);
        }
        
        td.empty-state {
            border-top: none !important;
            text-align: center !important;
            vertical-align: middle !important;
        }
        
        tbody tr:hover {
            background: var(--bg-secondary);
        }
        
        tbody tr:has(.empty-state):hover {
            background: transparent;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        
        .status-badge.pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .status-badge.pending::before {
            background: var(--warning);
        }
        
        .status-badge.approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .status-badge.approved::before {
            background: var(--success);
        }
        
        .status-badge.rejected,
        .status-badge.cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .status-badge.rejected::before,
        .status-badge.cancelled::before {
            background: var(--danger);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-icon svg {
            width: 16px;
            height: 16px;
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }
        
        .empty-state {
            text-align: center !important;
            padding: 4rem 2rem !important;
            color: var(--text-secondary) !important;
        }
        
        .empty-state-content {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 1rem !important;
            width: 100% !important;
            margin: 0 auto !important;
        }
        
        .empty-state-content svg {
            width: 64px !important;
            height: 64px !important;
            opacity: 0.5 !important;
            margin: 0 auto !important;
        }
        
        .empty-state-content p {
            margin: 0 !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            text-align: center !important;
        }
        
        @media (max-width: 768px) {
            .payment-history-container {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .table-wrapper {
                overflow-x: scroll;
            }
            
            table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h2 class="page-title">Histórico de Pagamentos</h2>
            </div>
        </header>
        
        <div class="payment-history-container">
            <!-- Stats -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Pendentes</span>
                        <div class="stat-icon pending">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="statPending">0</div>
                        <div class="stat-label">Aguardando pagamento</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Aprovados</span>
                        <div class="stat-icon approved">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="statApproved">0</div>
                        <div class="stat-label">Pagamentos confirmados</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Rejeitados</span>
                        <div class="stat-icon rejected">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="statRejected">0</div>
                        <div class="stat-label">Pagamentos não aprovados</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total</span>
                        <div class="stat-icon approved">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="statTotal">R$ 0,00</div>
                        <div class="stat-label">Valor total aprovado</div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filters-card">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Status</label>
                        <select id="filterStatus" onchange="loadPayments()">
                            <option value="">Todos</option>
                            <option value="pending">Pendente</option>
                            <option value="approved">Aprovado</option>
                            <option value="rejected">Rejeitado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Período</label>
                        <select id="filterPeriod" onchange="loadPayments()">
                            <option value="all">Todos</option>
                            <option value="today">Hoje</option>
                            <option value="week">Última semana</option>
                            <option value="month">Último mês</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Buscar</label>
                        <input type="text" id="filterSearch" placeholder="Email, Payment ID..." onkeyup="loadPayments()">
                    </div>
                </div>
            </div>
            
            <!-- Tabela -->
            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">Pagamentos</h3>
                </div>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuário</th>
                                <th>Plano</th>
                                <th>Valor</th>
                                <th>Payment ID</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="paymentsTable">
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <div class="empty-state-content">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                        </svg>
                                        <p>Carregando pagamentos...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/admin-payment-history.js"></script>
</body>
</html>
