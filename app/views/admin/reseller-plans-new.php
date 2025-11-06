<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos de Revendedores - UltraGestor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <link href="/assets/css/admin-responsive.css" rel="stylesheet">
    <link href="/assets/css/modal-responsive.css" rel="stylesheet">
    <style>
        body {
            background: var(--bg-secondary);
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .plans-container {
            padding: 0;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .page-title i {
            color: #3b82f6;
        }

        .btn-new {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        /* Grid de Planos */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        /* Card de Plano */
        .plan-card {
            background: var(--bg-primary);
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 2px solid transparent;
            overflow: hidden;
            transition: all 0.3s;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .plan-card.trial {
            border-color: #10b981;
        }

        .plan-card.popular {
            border-color: #3b82f6;
            transform: scale(1.02);
        }

        .plan-card.popular::before {
            content: '⭐ Mais Popular';
            position: absolute;
            top: 16px;
            right: -35px;
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white;
            padding: 6px 45px;
            font-size: 11px;
            font-weight: 700;
            transform: rotate(45deg);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }

        /* Header do Card */
        .plan-header {
            padding: 32px 24px 24px;
            text-align: center;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            position: relative;
        }

        .plan-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .plan-badge.active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .plan-badge.inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 8px 0;
        }

        .plan-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0 0 24px 0;
            line-height: 1.5;
        }

        .plan-price {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 4px;
            margin-bottom: 8px;
        }

        .plan-price .currency {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .plan-price .value {
            font-size: 3rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }

        .plan-price.free .value {
            color: #10b981;
        }

        .plan-duration {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Body do Card */
        .plan-body {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 24px 0;
            flex: 1;
        }

        .plan-features li {
            padding: 10px 0;
            color: var(--text-secondary);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.875rem;
        }

        .plan-features li i {
            color: #10b981;
            font-size: 16px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* Stats do Plano */
        .plan-stats {
            background: var(--bg-secondary);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .stat-row:not(:last-child) {
            border-bottom: 1px solid var(--border);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.813rem;
            font-weight: 500;
        }

        .stat-value {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 0.938rem;
        }

        .stat-value.highlight {
            color: #10b981;
        }

        /* Actions */
        .plan-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-edit {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .btn-toggle {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .btn-toggle:hover {
            background: rgba(245, 158, 11, 0.2);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--bg-primary);
            border-radius: 16px;
            border: 2px dashed var(--border);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-secondary);
            opacity: 0.5;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin: 0 0 8px 0;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin: 0 0 24px 0;
        }

        /* Loading */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px;
        }

        .spinner {
            border: 3px solid var(--border);
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--bg-primary);
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 24px;
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
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-close:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 0.938rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .modal-footer {
            padding: 24px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 12px 24px;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .plans-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .plan-card.popular {
                transform: scale(1);
            }
        }
        
        /* Correções específicas para modal de planos em mobile */
        @media (max-width: 768px) {
            #planModal {
                align-items: flex-start !important;
                padding: 0 !important;
            }
            
            #planModal .modal-content {
                width: 100% !important;
                max-width: 100% !important;
                height: 100vh !important;
                max-height: 100vh !important;
                border-radius: 0 !important;
                margin: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
            }
            
            #planModal .modal-header {
                position: sticky !important;
                top: 0 !important;
                background: var(--bg-primary) !important;
                z-index: 10 !important;
                border-bottom: 1px solid var(--border) !important;
                padding: 1rem !important;
                flex-shrink: 0 !important;
            }
            
            #planModal .modal-body {
                padding: 1rem !important;
                padding-top: 1rem !important;
                padding-bottom: 8rem !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                flex: 1 !important;
                max-height: calc(100vh - 140px) !important;
            }
            
            #planModal .modal-footer {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                background: var(--bg-primary) !important;
                border-top: 1px solid var(--border) !important;
                z-index: 1001 !important;
                padding: 1rem !important;
                box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.3) !important;
                display: flex !important;
                gap: 0.75rem !important;
                flex-shrink: 0 !important;
            }
            
            #planModal .modal-footer .btn-primary,
            #planModal .modal-footer .btn-secondary {
                flex: 1 !important;
                min-height: 48px !important;
                padding: 0.875rem 1rem !important;
                font-size: 1rem !important;
            }
            
            #planModal .form-row {
                flex-direction: column !important;
                gap: 1rem !important;
            }
            
            #planModal .form-group {
                margin-bottom: 1rem !important;
            }
            
            #planModal .form-group input,
            #planModal .form-group select,
            #planModal .form-group textarea {
                padding: 1rem !important;
                font-size: 16px !important; /* Previne zoom no iOS */
                min-height: 48px !important;
                border-radius: 8px !important;
            }
            
            #planModal .form-group label {
                font-size: 0.875rem !important;
                font-weight: 600 !important;
                margin-bottom: 0.5rem !important;
            }
            
            #planModal .btn-close {
                min-width: 44px !important;
                min-height: 44px !important;
                padding: 0.75rem !important;
                font-size: 1.25rem !important;
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
                <h2 class="page-title">Planos de Revendedores</h2>
            </div>
            <div class="header-right">
                <button class="btn-new" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span class="btn-text">Novo Plano</span>
                </button>
            </div>
        </header>

        <div class="plans-container">

        <!-- Loading -->
        <div class="loading" id="loadingSpinner">
            <div class="spinner"></div>
        </div>

        <!-- Plans Grid -->
        <div class="plans-grid" id="plansGrid" style="display: none;"></div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState" style="display: none;">
            <i class="fas fa-tags"></i>
            <h3>Nenhum plano cadastrado</h3>
            <p>Crie seu primeiro plano para começar</p>
            <button class="btn-new" onclick="openModal()">
                <i class="fas fa-plus"></i>
                Criar Primeiro Plano
            </button>
        </div>
        </div>
    </main>

    <!-- Modal Plano -->
    <div class="modal" id="planModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Novo Plano</h3>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="planForm" onsubmit="savePlan(event)">
                <div class="modal-body">
                    <input type="hidden" id="planId">
                    
                    <div class="form-group">
                        <label for="planName">Nome do Plano *</label>
                        <input type="text" id="planName" required placeholder="Ex: Mensal">
                    </div>

                    <div class="form-group">
                        <label for="planDescription">Descrição</label>
                        <textarea id="planDescription" placeholder="Descrição do plano"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="planPrice">Preço (R$) *</label>
                            <input type="number" id="planPrice" step="0.01" required placeholder="0,00">
                        </div>

                        <div class="form-group">
                            <label for="planDuration">Duração (dias) *</label>
                            <input type="number" id="planDuration" required placeholder="30">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="planStatus">Status</label>
                        <select id="planStatus">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar Plano</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/admin-common.js"></script>
    <script>
        let plans = [];
        let editingPlanId = null;

        // Carregar planos
        document.addEventListener('DOMContentLoaded', loadPlans);

        async function loadPlans() {
            try {
                showLoading();
                
                const response = await fetch('/api-reseller-plans.php');
                const data = await response.json();
                
                if (data.success) {
                    plans = data.plans;
                    renderPlans();
                } else {
                    showError(data.error || 'Erro ao carregar planos');
                }
            } catch (error) {
                showError('Erro ao carregar planos: ' + error.message);
            } finally {
                hideLoading();
            }
        }

        function renderPlans() {
            const grid = document.getElementById('plansGrid');
            const emptyState = document.getElementById('emptyState');
            
            if (plans.length === 0) {
                grid.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }
            
            grid.style.display = 'grid';
            emptyState.style.display = 'none';
            
            grid.innerHTML = plans.map(plan => `
                <div class="plan-card ${plan.is_trial ? 'trial' : ''} ${plan.id === 'plan-monthly' ? 'popular' : ''}">
                    <div class="plan-header">
                        <span class="plan-badge ${plan.is_active ? 'active' : 'inactive'}">
                            ${plan.is_active ? 'Ativo' : 'Inativo'}
                        </span>
                        <h3 class="plan-name">${plan.name}</h3>
                        <p class="plan-description">${plan.description || 'Plano para revendedores'}</p>
                        <div class="plan-price ${plan.price === 0 ? 'free' : ''}">
                            <span class="currency">R$</span>
                            <span class="value">${plan.price.toFixed(2).replace('.', ',')}</span>
                        </div>
                        <div class="plan-duration">${plan.duration_days} dias</div>
                    </div>

                    <div class="plan-body">
                        <ul class="plan-features">
                            <li><i class="fas fa-check-circle"></i> Duração: ${plan.duration_days} dias</li>
                            <li><i class="fas fa-check-circle"></i> ${plan.is_trial ? 'Plano de teste' : 'Plano pago'}</li>
                            <li><i class="fas fa-check-circle"></i> Acesso completo ao sistema</li>
                            <li><i class="fas fa-check-circle"></i> Suporte técnico</li>
                        </ul>

                        <div class="plan-stats">
                            <div class="stat-row">
                                <span class="stat-label">Usuários Ativos</span>
                                <span class="stat-value highlight">${plan.active_users || 0}</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Receita Total</span>
                                <span class="stat-value highlight">R$ ${(plan.total_revenue || 0).toFixed(2).replace('.', ',')}</span>
                            </div>
                        </div>

                        <div class="plan-actions">
                            <button class="btn btn-edit" onclick="editPlan('${plan.id}')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-toggle" onclick="togglePlan('${plan.id}', ${plan.is_active})">
                                <i class="fas fa-power-off"></i> ${plan.is_active ? 'Desativar' : 'Ativar'}
                            </button>
                            ${!plan.is_trial ? `
                                <button class="btn btn-delete" onclick="deletePlan('${plan.id}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function openModal(planId = null) {
            editingPlanId = planId;
            const modal = document.getElementById('planModal');
            const form = document.getElementById('planForm');
            const title = document.getElementById('modalTitle');
            
            if (planId) {
                const plan = plans.find(p => p.id === planId);
                if (plan) {
                    title.textContent = 'Editar Plano';
                    document.getElementById('planId').value = plan.id;
                    document.getElementById('planName').value = plan.name;
                    document.getElementById('planDescription').value = plan.description || '';
                    document.getElementById('planPrice').value = plan.price;
                    document.getElementById('planDuration').value = plan.duration_days;
                    document.getElementById('planStatus').value = plan.is_active ? '1' : '0';
                }
            } else {
                title.textContent = 'Novo Plano';
                form.reset();
            }
            
            modal.classList.add('show');
        }

        function closeModal() {
            document.getElementById('planModal').classList.remove('show');
            document.getElementById('planForm').reset();
            editingPlanId = null;
        }

        async function savePlan(event) {
            event.preventDefault();
            
            const planData = {
                name: document.getElementById('planName').value,
                description: document.getElementById('planDescription').value,
                price: parseFloat(document.getElementById('planPrice').value),
                duration_days: parseInt(document.getElementById('planDuration').value),
                is_active: document.getElementById('planStatus').value === '1'
            };

            try {
                const url = editingPlanId 
                    ? `/api-reseller-plans.php/${editingPlanId}`
                    : '/api-reseller-plans.php';
                
                const method = editingPlanId ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(planData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(editingPlanId ? 'Plano atualizado!' : 'Plano criado!');
                    closeModal();
                    loadPlans();
                } else {
                    showError(data.error || 'Erro ao salvar plano');
                }
            } catch (error) {
                showError('Erro ao salvar plano: ' + error.message);
            }
        }

        function editPlan(planId) {
            openModal(planId);
        }

        async function togglePlan(planId, currentStatus) {
            const newStatus = !currentStatus;
            
            try {
                const response = await fetch(`/api-reseller-plans.php/${planId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ is_active: newStatus })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(`Plano ${newStatus ? 'ativado' : 'desativado'}!`);
                    loadPlans();
                } else {
                    showError(data.error || 'Erro ao alterar status do plano');
                }
            } catch (error) {
                showError('Erro: ' + error.message);
            }
        }

        async function deletePlan(planId) {
            if (!confirm('Tem certeza que deseja excluir este plano?')) return;
            
            try {
                const response = await fetch(`/api-reseller-plans.php/${planId}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Plano excluído!');
                    loadPlans();
                } else {
                    showError(data.error);
                }
            } catch (error) {
                showError('Erro: ' + error.message);
            }
        }

        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'flex';
            document.getElementById('plansGrid').style.display = 'none';
            document.getElementById('emptyState').style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }
    </script>
</body>
</html>
