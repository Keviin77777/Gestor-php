<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos de Revendedores - UltraGestor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <style>
        /* Reseller Plans - Design Profissional Melhorado */
        body {
            background: var(--bg-secondary);
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .plans-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .plans-container {
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

        /* Grid de Planos Melhorado */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Card de Plano Melhorado */
        .plan-card {
            background: var(--bg-primary);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .plan-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .plan-card.trial::before {
            background: linear-gradient(90deg, var(--success) 0%, #34d399 100%);
        }

        .plan-card.popular {
            border-color: var(--primary);
            transform: scale(1.02);
        }

        .plan-card.popular::after {
            content: '⭐ Mais Popular';
            position: absolute;
            top: 1rem;
            right: -2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 0.375rem 2.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            transform: rotate(45deg);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: var(--shadow);
        }

        /* Header do Card Melhorado */
        .plan-header {
            padding: 1.5rem;
            text-align: center;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .plan-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            padding: 0.375rem 0.875rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            align-self: center;
        }

        .plan-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .plan-badge.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        .plan-badge.active::before { background: var(--success); }

        .plan-badge.inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        .plan-badge.inactive::before { background: var(--danger); }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .plan-price {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 0.25rem;
        }

        .plan-price .currency {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .plan-price .value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }

        .plan-price.free .value {
            color: var(--success);
        }

        /* Body do Card Melhorado */
        .plan-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .plan-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0 0 1.5rem 0;
            line-height: 1.4;
            text-align: center;
            font-weight: 500;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
            flex: 1;
        }

        .plan-features li {
            padding: 0.5rem 0;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .plan-features li i {
            color: var(--success);
            font-size: 1rem;
            margin-top: 0.125rem;
            flex-shrink: 0;
        }

        /* Stats do Plano Melhoradas */
        .plan-stats {
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
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
            color: var(--success);
        }

        /* Actions Melhoradas */
        .plan-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .plan-actions.three-buttons {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .btn {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-edit {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: translateY(-1px);
        }

        .btn-toggle {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .btn-toggle:hover {
            background: rgba(245, 158, 11, 0.2);
            transform: translateY(-1px);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-1px);
        }

        /* Empty State Melhorado */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-primary);
            border-radius: var(--radius);
            border: 2px dashed var(--border);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-secondary);
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin: 0 0 1.5rem 0;
            font-size: 0.875rem;
        }

        /* Loading Melhorado */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 4rem;
        }

        .spinner {
            border: 3px solid var(--border);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Modal Melhorado */
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
            padding: 1.25rem;
        }

        .modal.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--bg-primary);
            border-radius: var(--radius);
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            animation: slideUp 0.3s ease;
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

        /* Responsive Melhorado */
        @media (max-width: 1024px) {
            .plans-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .plans-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .plan-card.popular {
                transform: scale(1);
            }

            .plan-actions {
                grid-template-columns: 1fr;
            }

            .plan-actions.three-buttons {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.5rem;
            }

            .plan-price .value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="plans-container">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tags"></i>
                Planos de Revendedores
            </h1>
            <button class="btn-new" onclick="openModal()">
                <i class="fas fa-plus"></i>
                Novo Plano
            </button>
        </div>

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

    <script src="/assets/js/dashboard.js"></script>
    <script>
        let plans = [];
        let editingPlanId = null;

        // Função para gerar descrição simples do plano
        function getSimplePlanDescription(plan) {
            if (plan.is_trial) {
                return 'Período de teste gratuito';
            }
            
            // Gerar descrição baseada na duração
            if (plan.duration_days <= 7) {
                return 'Plano Semanal';
            } else if (plan.duration_days <= 31) {
                return 'Plano Mensal';
            } else if (plan.duration_days <= 93) {
                return 'Plano Trimestral';
            } else if (plan.duration_days <= 186) {
                return 'Plano Semestral';
            } else if (plan.duration_days <= 365) {
                return 'Plano Anual';
            } else {
                return 'Plano Personalizado';
            }
        }

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
                console.error('Erro:', error);
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
                        <div class="plan-price ${plan.price === 0 ? 'free' : ''}">
                            <span class="currency">R$</span>
                            <span class="value">${plan.price.toFixed(2).replace('.', ',')}</span>
                        </div>
                    </div>

                    <div class="plan-body">
                        <p class="plan-description">${getSimplePlanDescription(plan)} • ${plan.duration_days} dias</p>
                        
                        <ul class="plan-features">
                            <li><i class="fas fa-check-circle"></i> ${plan.duration_days} dias de acesso</li>
                            <li><i class="fas fa-check-circle"></i> Sistema completo</li>
                            <li><i class="fas fa-check-circle"></i> Suporte técnico</li>
                            <li><i class="fas fa-check-circle"></i> Relatórios</li>
                        </ul>

                        <div class="plan-stats">
                            <div class="stat-row">
                                <span class="stat-label">Usuários Ativos</span>
                                <span class="stat-value highlight">${plan.active_users || 0}</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Receita Gerada</span>
                                <span class="stat-value highlight">R$ ${(plan.total_revenue || 0).toFixed(2).replace('.', ',')}</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Taxa de Conversão</span>
                                <span class="stat-value">${plan.conversion_rate || '0'}%</span>
                            </div>
                        </div>

                        <div class="plan-actions ${!plan.is_trial ? 'three-buttons' : ''}">
                            <button class="btn btn-edit" onclick="editPlan('${plan.id}')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-toggle" onclick="togglePlan('${plan.id}', ${plan.is_active})">
                                <i class="fas fa-power-off"></i> ${plan.is_active ? 'Desativar' : 'Ativar'}
                            </button>
                            ${!plan.is_trial ? `
                                <button class="btn btn-delete" onclick="deletePlan('${plan.id}')">
                                    <i class="fas fa-trash"></i> Excluir
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
            const action = currentStatus ? 'deactivate' : 'activate';
            
            try {
                const response = await fetch(`/api-reseller-plans.php/${planId}/${action}`, {
                    method: 'PUT'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(`Plano ${currentStatus ? 'desativado' : 'ativado'}!`);
                    loadPlans();
                } else {
                    showError(data.error);
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
            }, 3000);
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
            }, 5000);
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
    </script>
</body>
</html>
