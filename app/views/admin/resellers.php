<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revendedores - UltraGestor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <link href="/assets/css/admin-responsive.css" rel="stylesheet">
    <style>
        /* Resellers - Design Profissional Melhorado */
        .resellers-container {
            padding: 0;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Responsividade do botão */
        .btn-text {
            display: inline;
        }
        
        @media (max-width: 480px) {
            .btn-text {
                display: none;
            }
            
            .btn-new {
                padding: 0.75rem;
                min-width: 44px;
                justify-content: center;
            }
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8) !important;
            backdrop-filter: blur(4px);
            z-index: 99999 !important;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            pointer-events: auto !important;
        }

        .modal.show {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--bg-primary) !important;
            border-radius: var(--radius);
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5) !important;
            border: 3px solid #6366f1 !important;
            animation: slideUp 0.3s ease;
            position: relative;
            z-index: 100000 !important;
            pointer-events: auto !important;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .btn-close {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
            font-size: 1.5rem;
            line-height: 1;
        }

        .btn-close:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: var(--bg-tertiary);
        }

        /* Responsividade Melhorada */
        @media (max-width: 1024px) {
            .filters-row {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
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
                -webkit-overflow-scrolling: touch;
            }

            .resellers-table {
                min-width: 800px;
            }

            .user-info-cell {
                min-width: 200px;
            }
            
            .filters-section {
                padding: 1rem;
            }
            
            .resellers-container {
                padding: 0.75rem;
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
            
            .resellers-container {
                padding: 0.5rem;
            }
            
            .filters-section {
                padding: 0.75rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-icon {
                width: 48px;
                height: 48px;
                font-size: 1.25rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }

            .modal-content {
                margin: 0.5rem;
                width: calc(100% - 1rem);
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header Mobile -->
        <header class="header">
            <div class="header-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h2 class="page-title">Revendedores</h2>
            </div>
            <div class="header-right">
                <button class="btn-new" onclick="openNewResellerModal()">
                    <i class="fas fa-plus"></i>
                    <span class="btn-text">Novo</span>
                </button>
            </div>
        </header>

        <div class="resellers-container">

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
    </main>

    <!-- Modal Editar Revendedor -->
    <div class="modal" id="editResellerModal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3>Editar Revendedor</h3>
                <button class="btn-close" onclick="closeEditResellerModal()">&times;</button>
            </div>
            <div class="modal-body" id="editResellerModalBody">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Plano -->
    <div class="modal" id="changePlanModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Alterar Plano do Revendedor</h3>
                <button class="btn-close" onclick="closeChangePlanModal()">&times;</button>
            </div>
            <form id="changePlanForm" onsubmit="saveChangePlan(event)">
                <div class="modal-body">
                    <input type="hidden" id="changePlanResellerId">
                    
                    <div class="form-group">
                        <label>Revendedor</label>
                        <input type="text" id="changePlanResellerName" readonly style="background: var(--bg-tertiary); cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label for="newPlanId">Novo Plano *</label>
                        <select id="newPlanId" required class="filter-input">
                            <option value="">Carregando planos...</option>
                        </select>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="paymentAmount">Valor Pago (R$)</label>
                            <input type="number" id="paymentAmount" step="0.01" class="filter-input" placeholder="0,00">
                        </div>

                        <div class="form-group">
                            <label for="paymentMethod">Método de Pagamento</label>
                            <select id="paymentMethod" class="filter-input">
                                <option value="admin_change">Alteração Admin</option>
                                <option value="pix">PIX</option>
                                <option value="credit_card">Cartão de Crédito</option>
                                <option value="bank_transfer">Transferência</option>
                                <option value="boleto">Boleto</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="planNotes">Observações</label>
                        <textarea id="planNotes" class="filter-input" rows="3" placeholder="Observações sobre a alteração do plano..."></textarea>
                    </div>

                    <div id="planPreview" style="display: none; background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); margin-top: 1rem; border: 1px solid var(--border);">
                        <h4 style="margin: 0 0 0.75rem 0; font-size: 0.875rem; color: var(--text-secondary); text-transform: uppercase;">Preview do Novo Plano</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Preço</div>
                                <div style="font-weight: 600; color: var(--text-primary);" id="previewPrice">-</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Duração</div>
                                <div style="font-weight: 600; color: var(--text-primary);" id="previewDuration">-</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Vencimento Atual</div>
                                <div style="font-weight: 600; color: var(--text-primary);" id="previewCurrentExpiry">-</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Novo Vencimento</div>
                                <div style="font-weight: 600; color: var(--success);" id="previewNewExpiry">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeChangePlanModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Alterar Plano</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/admin-common.js"></script>
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
                            <button class="pro-btn-action" onclick="editReseller('${reseller.id}')" title="Editar Revendedor">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="pro-btn-action" onclick="changePlan('${reseller.id}')" title="Alterar Plano">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3h18v18H3V3z"></path>
                                    <path d="M3 9h18M9 21V9"></path>
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
        async function editReseller(resellerId) {
            const modal = document.getElementById('editResellerModal');
            const modalBody = document.getElementById('editResellerModalBody');
            
            modal.classList.add('show');
            modal.style.display = 'flex';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
            modal.style.zIndex = '99999';
            
            modalBody.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
            
            try {
                const response = await fetch(`/api-resellers.php/${resellerId}`);
                const data = await response.json();
                
                if (data.success) {
                    renderEditResellerForm(data);
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--danger);">
                        <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Erro ao carregar dados: ${error.message}</p>
                    </div>
                `;
            }
        }

        function renderEditResellerForm(data) {
            const reseller = data.reseller;
            const clientStats = data.client_stats;
            const invoiceStats = data.invoice_stats;
            const planHistory = data.plan_history || [];
            
            const modalBody = document.getElementById('editResellerModalBody');
            modalBody.innerHTML = `
                <form id="editResellerForm" onsubmit="saveResellerDetails(event)">
                    <input type="hidden" id="editResellerId" value="${reseller.id}">
                    
                    <div style="display: grid; gap: 1.5rem;">
                        <!-- Informações Básicas -->
                        <div>
                            <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--text-primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                                <i class="fas fa-user"></i> Informações Básicas
                            </h4>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.5rem; display: block;">Nome</label>
                                    <input type="text" id="editName" value="${reseller.name || ''}" class="filter-input" placeholder="Nome do revendedor">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.5rem; display: block;">Email</label>
                                    <input type="email" id="editEmail" value="${reseller.email}" class="filter-input" placeholder="email@exemplo.com" required>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.5rem; display: block;">WhatsApp</label>
                                    <input type="text" id="editWhatsapp" value="${reseller.whatsapp || ''}" class="filter-input" placeholder="(00) 00000-0000" maxlength="15">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.5rem; display: block;">Nova Senha (deixe vazio para não alterar)</label>
                                    <input type="password" id="editPassword" class="filter-input" placeholder="••••••••">
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Cadastro</div>
                                    <div style="font-weight: 600;">${formatDate(reseller.created_at)}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Status</div>
                                    <div><span class="status-badge status-${reseller.plan_status || 'no_plan'}">${getStatusLabel(reseller.plan_status || 'no_plan')}</span></div>
                                </div>
                            </div>
                        </div>

                    <!-- Plano Atual -->
                    <div>
                        <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--text-primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                            <i class="fas fa-tag"></i> Plano Atual
                        </h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Plano</div>
                                <div style="font-weight: 600;">${reseller.plan_name || 'Sem plano'}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Preço</div>
                                <div style="font-weight: 600;">R$ ${parseFloat(reseller.plan_price || 0).toFixed(2).replace('.', ',')}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Vencimento</div>
                                <div style="font-weight: 600;">${reseller.plan_expires_at ? formatDate(reseller.plan_expires_at) : '-'}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Duração</div>
                                <div style="font-weight: 600;">${reseller.plan_duration || '-'} dias</div>
                            </div>
                        </div>
                    </div>

                    <!-- Estatísticas -->
                    <div>
                        <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--text-primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                            <i class="fas fa-chart-bar"></i> Estatísticas
                        </h4>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">${clientStats.total_clients}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">Total Clientes</div>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">${clientStats.active_clients}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">Clientes Ativos</div>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger);">${clientStats.inactive_clients}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">Clientes Inativos</div>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem;">
                            <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--info);">${invoiceStats.total_invoices}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">Total Faturas</div>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">${invoiceStats.paid_invoices}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">Faturas Pagas</div>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">R$ ${parseFloat(invoiceStats.total_revenue).toFixed(2).replace('.', ',')}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">Receita Total</div>
                            </div>
                        </div>
                    </div>

                    <!-- Histórico de Planos -->
                    ${planHistory.length > 0 ? `
                    <div>
                        <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--text-primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                            <i class="fas fa-history"></i> Histórico de Planos
                        </h4>
                        <div style="max-height: 200px; overflow-y: auto;">
                            <table style="width: 100%; font-size: 0.875rem;">
                                <thead style="background: var(--bg-secondary); position: sticky; top: 0;">
                                    <tr>
                                        <th style="padding: 0.5rem; text-align: left;">Plano</th>
                                        <th style="padding: 0.5rem; text-align: left;">Início</th>
                                        <th style="padding: 0.5rem; text-align: left;">Vencimento</th>
                                        <th style="padding: 0.5rem; text-align: right;">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${planHistory.map(h => `
                                        <tr style="border-bottom: 1px solid var(--border);">
                                            <td style="padding: 0.5rem;">${h.plan_name}</td>
                                            <td style="padding: 0.5rem;">${formatDate(h.started_at)}</td>
                                            <td style="padding: 0.5rem;">${formatDate(h.expires_at)}</td>
                                            <td style="padding: 0.5rem; text-align: right;">R$ ${parseFloat(h.plan_price).toFixed(2).replace('.', ',')}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Botões de Ação -->
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid var(--border);">
                        <button type="button" class="btn-secondary" onclick="closeEditResellerModal()">Cancelar</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </div>
                </form>
            `;
        }

        async function saveResellerDetails(event) {
            event.preventDefault();
            
            const resellerId = document.getElementById('editResellerId').value;
            const name = document.getElementById('editName').value;
            const email = document.getElementById('editEmail').value;
            const whatsapp = document.getElementById('editWhatsapp').value;
            const password = document.getElementById('editPassword').value;
            
            const updateData = {
                name: name,
                email: email,
                whatsapp: whatsapp
            };
            
            // Só incluir senha se foi preenchida
            if (password && password.trim() !== '') {
                updateData.password = password;
            }
            
            try {
                const response = await fetch(`/api-resellers.php/${resellerId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updateData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Dados atualizados com sucesso!');
                    closeEditResellerModal();
                    loadResellers(); // Recarregar lista
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showError('Erro ao atualizar dados: ' + error.message);
            }
        }

        // Formatar WhatsApp automaticamente
        function formatWhatsApp(input) {
            let value = input.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                // Formato: (00) 00000-0000
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            }
            
            input.value = value;
        }

        // Adicionar evento de formatação ao campo WhatsApp quando o modal abrir
        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'editWhatsapp') {
                formatWhatsApp(e.target);
            }
        });

        function closeEditResellerModal() {
            const modal = document.getElementById('editResellerModal');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                modal.style.visibility = 'hidden';
                modal.style.opacity = '0';
            }
        }

        let availablePlans = [];
        let currentResellerData = null;

        async function changePlan(resellerId) {
            try {
                const modal = document.getElementById('changePlanModal');
                if (!modal) {
                    showError('Erro: Modal não encontrado');
                    return;
                }
                
                const reseller = resellers.find(r => r.id === resellerId);
                
                if (!reseller) {
                    showError('Revendedor não encontrado');
                    return;
                }

                currentResellerData = reseller;
                
                // Preencher campos do formulário
                const resellerIdInput = document.getElementById('changePlanResellerId');
                const resellerNameInput = document.getElementById('changePlanResellerName');
                const paymentAmountInput = document.getElementById('paymentAmount');
                const paymentMethodSelect = document.getElementById('paymentMethod');
                const planNotesTextarea = document.getElementById('planNotes');
                const planPreviewDiv = document.getElementById('planPreview');
                
                if (resellerIdInput) resellerIdInput.value = resellerId;
                if (resellerNameInput) resellerNameInput.value = reseller.name || reseller.email;
                if (paymentAmountInput) paymentAmountInput.value = '';
                if (paymentMethodSelect) paymentMethodSelect.value = 'admin_change';
                if (planNotesTextarea) planNotesTextarea.value = '';
                if (planPreviewDiv) planPreviewDiv.style.display = 'none';
                
                // Carregar planos disponíveis
                await loadAvailablePlans();
                
                modal.classList.add('show');
                
                // Forçar estilos inline para sobrescrever qualquer CSS externo
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
                modal.style.zIndex = '99999';
                
                } catch (error) {
                showError('Erro ao abrir modal: ' + error.message);
            }
        }

        async function loadAvailablePlans() {
            try {
                const response = await fetch('/api-reseller-plans.php');
                const data = await response.json();
                
                if (data.success) {
                    availablePlans = data.plans.filter(p => p.is_active);
                    
                    const select = document.getElementById('newPlanId');
                    select.innerHTML = '<option value="">Selecione um plano</option>' + 
                        availablePlans.map(plan => `
                            <option value="${plan.id}" data-price="${plan.price}" data-duration="${plan.duration_days}">
                                ${plan.name} - R$ ${parseFloat(plan.price).toFixed(2).replace('.', ',')} (${plan.duration_days} dias)
                            </option>
                        `).join('');
                    
                    // Evento para atualizar preview
                    select.addEventListener('change', updatePlanPreview);
                } else {
                    showError('Erro ao carregar planos');
                }
            } catch (error) {
                showError('Erro ao carregar planos: ' + error.message);
            }
        }

        function updatePlanPreview() {
            const select = document.getElementById('newPlanId');
            const selectedOption = select.options[select.selectedIndex];
            const preview = document.getElementById('planPreview');
            
            if (!select.value) {
                preview.style.display = 'none';
                return;
            }
            
            const price = selectedOption.getAttribute('data-price');
            const duration = selectedOption.getAttribute('data-duration');
            
            // Calcular novo vencimento
            const today = new Date();
            const newExpiry = new Date(today.getTime() + (duration * 24 * 60 * 60 * 1000));
            
            document.getElementById('previewPrice').textContent = 'R$ ' + parseFloat(price).toFixed(2).replace('.', ',');
            document.getElementById('previewDuration').textContent = duration + ' dias';
            document.getElementById('previewCurrentExpiry').textContent = currentResellerData.plan_expires_at ? formatDate(currentResellerData.plan_expires_at) : 'Sem plano';
            document.getElementById('previewNewExpiry').textContent = newExpiry.toLocaleDateString('pt-BR');
            
            // Preencher valor de pagamento automaticamente
            document.getElementById('paymentAmount').value = price;
            
            preview.style.display = 'block';
        }

        async function saveChangePlan(event) {
            event.preventDefault();
            
            const resellerId = document.getElementById('changePlanResellerId').value;
            const planId = document.getElementById('newPlanId').value;
            const paymentAmount = document.getElementById('paymentAmount').value;
            const paymentMethod = document.getElementById('paymentMethod').value;
            const notes = document.getElementById('planNotes').value;
            
            if (!planId) {
                showError('Selecione um plano');
                return;
            }
            
            try {
                const response = await fetch(`/api-resellers.php/${resellerId}/change-plan`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        plan_id: planId,
                        payment_amount: paymentAmount || null,
                        payment_method: paymentMethod,
                        notes: notes
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Plano alterado com sucesso!');
                    closeChangePlanModal();
                    loadResellers();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showError('Erro ao alterar plano: ' + error.message);
            }
        }

        function closeChangePlanModal() {
            const modal = document.getElementById('changePlanModal');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                modal.style.visibility = 'hidden';
                modal.style.opacity = '0';
            }
            const form = document.getElementById('changePlanForm');
            if (form) {
                form.reset();
            }
            currentResellerData = null;
        }

        // Fechar modal ao clicar fora
        document.addEventListener('click', function(e) {
            const changePlanModal = document.getElementById('changePlanModal');
            const editResellerModal = document.getElementById('editResellerModal');
            
            if (changePlanModal && e.target === changePlanModal) {
                closeChangePlanModal();
            }
            
            if (editResellerModal && e.target === editResellerModal) {
                closeEditResellerModal();
            }
        });



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
                const response = await fetch(`/api-resellers.php?id=${resellerId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        _method: 'DELETE'
                    })
                });
                
                const text = await response.text();
                console.log('Response:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
                }
                
                if (data.success) {
                    showSuccess('Revendedor excluído com sucesso');
                    loadResellers();
                } else {
                    throw new Error(data.error || 'Erro desconhecido');
                }
            } catch (error) {
                console.error('Erro completo:', error);
                showError('Erro ao excluir revendedor: ' + error.message);
            }
        }
    </script>
</body>
</html>
