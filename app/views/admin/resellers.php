<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revendedores - UltraGestor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <style>
        /* Resellers - Design Profissional Melhorado */
        .resellers-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        
        @media (max-width: 768px) {
            .resellers-container {
                margin-left: 0;
                padding: 1rem;
            }
        }

        /* Header Melhorado */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .page-title i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-new {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .btn-new:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Stats Grid Melhorado */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-primary);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .stat-icon.total { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); }
        .stat-icon.active { background: linear-gradient(135deg, var(--success) 0%, #34d399 100%); }
        .stat-icon.trial { background: linear-gradient(135deg, var(--info) 0%, #60a5fa 100%); }
        .stat-icon.expired { background: linear-gradient(135deg, var(--warning) 0%, #fbbf24 100%); }
        .stat-icon.revenue { background: linear-gradient(135deg, var(--success) 0%, #34d399 100%); }

        .stat-info {
            flex: 1;
        }

        .stat-number {
            font-size: 1.875rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Filters Section Melhorada */
        .filters-section {
            background: var(--bg-primary);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .filters-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .filter-input {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-clear {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .btn-clear:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Table Container Melhorada */
        .resellers-table-container {
            background: var(--bg-primary);
            border-radius: var(--radius);
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
            font-weight: 600;
            color: var(--text-primary);
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-export {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-export:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Table Melhorada */
        .resellers-table {
            width: 100%;
            border-collapse: collapse;
        }

        .resellers-table th {
            padding: 1rem;
            text-align: left;
            background: var(--bg-secondary);
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }

        .resellers-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .resellers-table tbody tr {
            transition: all 0.2s ease;
        }

        .resellers-table tbody tr:hover {
            background: var(--bg-secondary);
        }

        /* User Info Cell */
        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.125rem;
        }

        .user-email {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Status Badges Melhoradas */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .status-active { 
            background: rgba(16, 185, 129, 0.1); 
            color: var(--success); 
        }
        .status-active::before { background: var(--success); }

        .status-expired { 
            background: rgba(239, 68, 68, 0.1); 
            color: var(--danger); 
        }
        .status-expired::before { background: var(--danger); }

        .status-trial { 
            background: rgba(59, 130, 246, 0.1); 
            color: var(--info); 
        }
        .status-trial::before { background: var(--info); }

        .status-suspended { 
            background: rgba(245, 158, 11, 0.1); 
            color: var(--warning); 
        }
        .status-suspended::before { background: var(--warning); }

        /* Plan Badge Melhorada */
        .plan-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            background: var(--bg-secondary);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .plan-badge.trial {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
            border-color: var(--info);
        }

        /* Actions Buttons - Estilo Horizontal como Clientes */
        .reseller-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pro-btn-action {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .pro-btn-action:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            transform: translateY(-1px);
        }

        .pro-btn-action svg {
            width: 16px;
            height: 16px;
        }

        .pro-btn-action.danger {
            color: var(--danger);
        }

        .pro-btn-action.danger:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .pro-btn-action.success {
            color: var(--success);
        }

        .pro-btn-action.success:hover {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .pro-btn-action.warning {
            color: var(--warning);
        }

        .pro-btn-action.warning:hover {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .pro-btn-action:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pro-btn-action:disabled:hover {
            transform: none;
            background: var(--bg-secondary);
        }

        /* Empty State Melhorada */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
            color: var(--text-tertiary);
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
        }

        .empty-state p {
            margin: 0 0 1.5rem 0;
            font-size: 0.875rem;
        }

        /* Loading Melhorado */
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsividade Melhorada */
        @media (max-width: 1024px) {
            .filters-row {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .filters-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .resellers-table-container {
                overflow-x: auto;
            }

            .resellers-table {
                min-width: 800px;
            }

            .user-info-cell {
                min-width: 200px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-content {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <div class="resellers-container">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-users"></i>
                Revendedores
            </h1>
            <div class="header-actions">
                <button class="btn-new" onclick="openNewResellerModal()">
                    <i class="fas fa-plus"></i>
                    Novo Revendedor
                </button>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon total">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="totalResellers">-</div>
                        <div class="stat-label">Total de Revendedores</div>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="activeResellers">-</div>
                        <div class="stat-label">Ativos</div>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon trial">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="trialResellers">-</div>
                        <div class="stat-label">Em Trial</div>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon expired">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="expiredResellers">-</div>
                        <div class="stat-label">Vencidos</div>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="monthlyRevenue">-</div>
                        <div class="stat-label">Receita Mensal</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <div class="filters-row">
                <div class="filter-group">
                    <label class="filter-label">Buscar Revendedor</label>
                    <input type="text" class="filter-input" id="searchInput" placeholder="Nome, email ou telefone...">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-input" id="statusFilter">
                        <option value="">Todos os Status</option>
                        <option value="active">Ativos</option>
                        <option value="expired">Vencidos</option>
                        <option value="suspended">Suspensos</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Tipo de Plano</label>
                    <select class="filter-input" id="planFilter">
                        <option value="">Todos os Planos</option>
                        <option value="trial">Trial</option>
                        <option value="paid">Pagos</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button class="btn-clear" onclick="clearFilters()">
                        <i class="fas fa-times"></i>
                        Limpar
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabela de Revendedores -->
        <div class="resellers-table-container">
            <div class="table-header">
                <h3 class="table-title">Lista de Revendedores</h3>
                <div class="table-actions">
                    <button class="btn-export" onclick="exportResellers()">
                        <i class="fas fa-download"></i>
                        Exportar
                    </button>
                </div>
            </div>
            
            <div id="loadingSpinner" class="loading-spinner">
                <div class="spinner"></div>
            </div>

            <div id="resellersTableContainer" style="display: none;">
                <table class="resellers-table">
                    <thead>
                        <tr>
                            <th>Revendedor</th>
                            <th>Plano Atual</th>
                            <th>Status</th>
                            <th>Vencimento</th>
                            <th>Clientes</th>
                            <th>Receita</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="resellersTableBody">
                    </tbody>
                </table>
            </div>

            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-users"></i>
                <h3>Nenhum revendedor encontrado</h3>
                <p>Não há revendedores cadastrados no sistema.</p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/assets/js/dashboard.js"></script>
    <script>
        let resellers = [];
        let filteredResellers = [];

        // Carregar dados ao inicializar
        document.addEventListener('DOMContentLoaded', function() {
            loadResellers();
            setupFilters();
        });

        // Carregar lista de revendedores
        async function loadResellers() {
            try {
                showLoading();
                
                const response = await fetch('/api-resellers.php');
                const data = await response.json();
                
                if (data.success) {
                    resellers = data.resellers;
                    filteredResellers = [...resellers];
                    
                    updateStats(data.stats);
                    renderResellersTable();
                } else {
                    throw new Error(data.error || 'Erro ao carregar revendedores');
                }
            } catch (error) {
                console.error('Erro:', error);
                showError('Erro ao carregar revendedores: ' + error.message);
            } finally {
                hideLoading();
            }
        }

        // Atualizar estatísticas
        function updateStats(stats) {
            document.getElementById('totalResellers').textContent = stats.total || 0;
            document.getElementById('activeResellers').textContent = stats.active || 0;
            document.getElementById('trialResellers').textContent = stats.trial || 0;
            document.getElementById('expiredResellers').textContent = stats.expired || 0;
            
            const revenue = stats.revenue_monthly || 0;
            document.getElementById('monthlyRevenue').textContent = 'R$ ' + revenue.toFixed(2).replace('.', ',');
        }

        // Renderizar tabela
        function renderResellersTable() {
            const tbody = document.getElementById('resellersTableBody');
            
            if (filteredResellers.length === 0) {
                document.getElementById('resellersTableContainer').style.display = 'none';
                document.getElementById('emptyState').style.display = 'block';
                return;
            }
            
            document.getElementById('resellersTableContainer').style.display = 'block';
            document.getElementById('emptyState').style.display = 'none';
            
            tbody.innerHTML = filteredResellers.map(reseller => `
                <tr>
                    <td>
                        <div class="user-info-cell">
                            <div class="user-avatar">
                                ${(reseller.name || reseller.email).charAt(0).toUpperCase()}
                            </div>
                            <div class="user-details">
                                <div class="user-name">${reseller.name || reseller.email}</div>
                                ${reseller.name ? `<div class="user-email">${reseller.email}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="plan-badge ${reseller.is_trial ? 'trial' : ''}">
                            ${reseller.plan_name || 'Sem plano'}
                        </span>
                        ${reseller.plan_price > 0 ? `<div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">R$ ${parseFloat(reseller.plan_price).toFixed(2).replace('.', ',')}</div>` : ''}
                    </td>
                    <td>
                        <span class="status-badge status-${reseller.current_status}">
                            ${getStatusLabel(reseller.current_status)}
                        </span>
                    </td>
                    <td>
                        <div style="color: var(--text-primary);">
                            ${reseller.plan_expires_at ? formatDate(reseller.plan_expires_at) : '-'}
                        </div>
                        ${reseller.days_remaining !== null && reseller.days_remaining !== undefined ? `<div style="font-size: 0.75rem; color: ${reseller.days_remaining <= 0 ? 'var(--danger)' : reseller.days_remaining <= 3 ? 'var(--warning)' : 'var(--success)'}; margin-top: 0.25rem;">${reseller.days_remaining} ${reseller.days_remaining === 1 ? 'dia' : 'dias'}</div>` : ''}
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-users" style="color: var(--text-secondary); font-size: 0.875rem;"></i>
                            <span id="clients-${reseller.id}" style="font-weight: 600;">-</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-dollar-sign" style="color: var(--success); font-size: 0.875rem;"></i>
                            <span id="revenue-${reseller.id}" style="font-weight: 600; color: var(--success);">-</span>
                        </div>
                    </td>
                    <td>
                        <div style="color: var(--text-secondary);">
                            ${formatDate(reseller.created_at)}
                        </div>
                    </td>
                    <td>
                        <div class="reseller-actions">
                            <button class="pro-btn-action" onclick="viewResellerDetails('${reseller.id}')" title="Ver Detalhes">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                            <button class="pro-btn-action" onclick="changePlan('${reseller.id}')" title="Alterar Plano">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            ${reseller.current_status === 'suspended' 
                                ? `<button class="pro-btn-action success" onclick="activateReseller('${reseller.id}')" title="Ativar Revendedor">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                   </button>`
                                : `<button class="pro-btn-action warning" onclick="suspendReseller('${reseller.id}')" title="Suspender Revendedor">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="6" y="4" width="4" height="16"></rect>
                                        <rect x="14" y="4" width="4" height="16"></rect>
                                    </svg>
                                   </button>`
                            }
                            <button class="pro-btn-action danger" onclick="deleteReseller('${reseller.id}')" title="Excluir Revendedor">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            // Carregar estatísticas individuais
            loadResellerStats();
        }

        // Carregar estatísticas individuais dos revendedores
        async function loadResellerStats() {
            for (const reseller of filteredResellers) {
                try {
                    const response = await fetch(`/api-resellers.php/${reseller.id}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        const clientsEl = document.getElementById(`clients-${reseller.id}`);
                        const revenueEl = document.getElementById(`revenue-${reseller.id}`);
                        
                        if (clientsEl) {
                            clientsEl.textContent = data.client_stats.total_clients;
                        }
                        
                        if (revenueEl) {
                            revenueEl.textContent = 'R$ ' + parseFloat(data.invoice_stats.total_revenue).toFixed(2).replace('.', ',');
                        }
                    }
                } catch (error) {
                    console.error(`Erro ao carregar stats do revendedor ${reseller.id}:`, error);
                }
            }
        }

        // Configurar filtros
        function setupFilters() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const planFilter = document.getElementById('planFilter');
            
            [searchInput, statusFilter, planFilter].forEach(input => {
                input.addEventListener('input', applyFilters);
            });
        }

        // Aplicar filtros
        function applyFilters() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const plan = document.getElementById('planFilter').value;
            
            filteredResellers = resellers.filter(reseller => {
                const matchesSearch = !search || 
                    reseller.name?.toLowerCase().includes(search) ||
                    reseller.email.toLowerCase().includes(search);
                
                const matchesStatus = !status || reseller.current_status === status;
                
                const matchesPlan = !plan || 
                    (plan === 'trial' && reseller.is_trial) ||
                    (plan === 'paid' && !reseller.is_trial);
                
                return matchesSearch && matchesStatus && matchesPlan;
            });
            
            renderResellersTable();
        }

        // Utilitários
        function getStatusLabel(status) {
            const labels = {
                'active': 'Ativo',
                'expired': 'Vencido',
                'suspended': 'Suspenso',
                'no_plan': 'Sem Plano'
            };
            return labels[status] || status;
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'flex';
            document.getElementById('resellersTableContainer').style.display = 'none';
            document.getElementById('emptyState').style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }

        // Funções de notificação melhoradas
        function showSuccess(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--success);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-sm);
                box-shadow: var(--shadow-lg);
                z-index: 9999;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                animation: slideInRight 0.3s ease;
                max-width: 400px;
            `;
            notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
        function showError(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--danger);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-sm);
                box-shadow: var(--shadow-lg);
                z-index: 9999;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                animation: slideInRight 0.3s ease;
                max-width: 400px;
            `;
            notification.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 6000);
        }

        // Adicionar animações CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes slideOutRight {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
        `;
        document.head.appendChild(style);



        // Funções adicionais
        function openNewResellerModal() {
            showInfo('Modal de novo revendedor será implementado em breve');
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('planFilter').value = '';
            applyFilters();
        }

        function exportResellers() {
            showInfo('Funcionalidade de exportação será implementada em breve');
        }

        // Ações dos revendedores
        function viewResellerDetails(resellerId) {
            // TODO: Implementar modal de detalhes
            showInfo(`Ver detalhes do revendedor ${resellerId}`);
        }

        function changePlan(resellerId) {
            // TODO: Implementar modal de alteração de plano
            showInfo(`Alterar plano do revendedor ${resellerId}`);
        }

        function showInfo(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--info);
                color: white;
                padding: 15px 20px;
                border-radius: var(--radius-sm);
                box-shadow: var(--shadow-lg);
                z-index: 9999;
                max-width: 400px;
                font-size: 14px;
                font-weight: 500;
            `;
            notification.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 4000);
        }

        async function suspendReseller(resellerId) {
            if (!confirm('Tem certeza que deseja suspender este revendedor?')) return;
            
            try {
                const response = await fetch(`/api-resellers.php/${resellerId}/suspend`, {
                    method: 'PUT'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Revendedor suspenso com sucesso');
                    loadResellers();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showError('Erro ao suspender revendedor: ' + error.message);
            }
        }

        async function activateReseller(resellerId) {
            try {
                const response = await fetch(`/api-resellers.php/${resellerId}/activate`, {
                    method: 'PUT'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Revendedor ativado com sucesso');
                    loadResellers();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showError('Erro ao ativar revendedor: ' + error.message);
            }
        }

        async function deleteReseller(resellerId) {
            if (!confirm('Tem certeza que deseja excluir este revendedor? Esta ação não pode ser desfeita.')) return;
            
            try {
                const response = await fetch(`/api-resellers.php/${resellerId}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Revendedor excluído com sucesso');
                    loadResellers();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showError('Erro ao excluir revendedor: ' + error.message);
            }
        }
    </script>
</body>
</html>
