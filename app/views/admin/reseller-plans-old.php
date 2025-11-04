<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos de Revendedores - UltraGestor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <style>
        .plans-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .plans-container {
                margin-left: 0;
            }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .plan-card {
            background: var(--bg-primary);
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.3s;
            position: relative;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .plan-card.trial {
            border: 2px solid #10b981;
        }

        .plan-card.popular {
            border: 2px solid #3b82f6;
            transform: scale(1.05);
        }

        .plan-card.popular::before {
            content: 'Mais Popular';
            position: absolute;
            top: 15px;
            right: -30px;
            background: #3b82f6;
            color: white;
            padding: 5px 40px;
            font-size: 12px;
            font-weight: 600;
            transform: rotate(45deg);
            text-transform: uppercase;
        }

        .plan-header {
            padding: 30px 25px 20px;
            text-align: center;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .plan-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 20px;
        }

        .plan-price {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .plan-price .currency {
            font-size: 18px;
            vertical-align: top;
        }

        .plan-price.free {
            color: #17a2b8;
        }

        .plan-duration {
            color: #6c757d;
            font-size: 14px;
        }

        .plan-body {
            padding: 25px;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 25px 0;
        }

        .plan-features li {
            padding: 8px 0;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan-features li i {
            color: #28a745;
            width: 16px;
        }

        .plan-stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .stat-row:last-child {
            margin-bottom: 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 13px;
        }

        .stat-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .plan-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-outline-primary {
            background: transparent;
            color: #007bff;
            border: 1px solid #007bff;
        }

        .btn-outline-primary:hover {
            background: #007bff;
            color: white;
        }

        .btn-outline-warning {
            background: transparent;
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        .btn-outline-warning:hover {
            background: #ffc107;
            color: #212529;
        }

        .btn-outline-danger {
            background: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: white;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input {
            width: auto;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: 1px solid #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .plans-container {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .plans-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .plan-card.popular {
                transform: none;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
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
            <button class="btn-primary" onclick="openPlanModal()">
                <i class="fas fa-plus"></i>
                Novo Plano
            </button>
        </div>

        <!-- Loading -->
        <div id="loadingSpinner" class="loading-spinner">
            <div class="spinner"></div>
        </div>

        <!-- Planos Grid -->
        <div id="plansGrid" class="plans-grid" style="display: none;">
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <i class="fas fa-tags"></i>
            <h3>Nenhum plano encontrado</h3>
            <p>Crie o primeiro plano para revendedores.</p>
        </div>
    </div>

    <!-- Modal de Plano -->
    <div id="planModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Novo Plano</h3>
                <button class="close" onclick="closePlanModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="planForm">
                    <input type="hidden" id="planId">
                    
                    <div class="form-group">
                        <label class="form-label" for="planName">Nome do Plano *</label>
                        <input type="text" class="form-control" id="planName" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="planDescription">Descrição</label>
                        <textarea class="form-control" id="planDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="planPrice">Preço (R$) *</label>
                        <input type="number" class="form-control" id="planPrice" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="planDuration">Duração (dias) *</label>
                        <input type="number" class="form-control" id="planDuration" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="planActive">
                            <label class="form-label" for="planActive">Plano Ativo</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="planTrial">
                            <label class="form-label" for="planTrial">Plano de Trial</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePlanModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="savePlan()">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/assets/js/dashboard.js"></script>
    <script>
        let plans = [];
        let editingPlanId = null;

        // Carregar dados ao inicializar
        document.addEventListener('DOMContentLoaded', function() {
            loadPlans();
        });

        // Carregar lista de planos
        async function loadPlans() {
            try {
                showLoading();
                
                const response = await fetch('/api-reseller-plans.php');
                const data = await response.json();
                
                if (data.success) {
                    plans = data.plans;
                    renderPlansGrid();
                } else {
                    throw new Error(data.error || 'Erro ao carregar planos');
                }
            } catch (error) {
                showError('Erro ao carregar planos: ' + error.message);
            } finally {
                hideLoading();
            }
        }

        // Renderizar grid de planos
        function renderPlansGrid() {
            const grid = document.getElementById('plansGrid');
            
            if (plans.length === 0) {
                document.getElementById('plansGrid').style.display = 'none';
                document.getElementById('emptyState').style.display = 'block';
                return;
            }
            
            document.getElementById('plansGrid').style.display = 'grid';
            document.getElementById('emptyState').style.display = 'none';
            
            grid.innerHTML = plans.map(plan => {
                const isPopular = plan.active_users > 0 && !plan.is_trial;
                const cardClass = plan.is_trial ? 'trial' : (isPopular ? 'popular' : '');
                
                return `
                    <div class="plan-card ${cardClass}">
                        <div class="status-badge ${plan.is_active ? 'status-active' : 'status-inactive'}">
                            ${plan.is_active ? 'Ativo' : 'Inativo'}
                        </div>
                        
                        <div class="plan-header">
                            <h3 class="plan-name">${plan.name}</h3>
                            <p class="plan-description">${plan.description || 'Sem descrição'}</p>
                            <div class="plan-price ${plan.price === 0 ? 'free' : ''}">
                                <span class="currency">R$</span>
                                ${plan.price.toFixed(2).replace('.', ',')}
                            </div>
                            <div class="plan-duration">${plan.duration_days} dias</div>
                        </div>
                        
                        <div class="plan-body">
                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Duração: ${plan.duration_days} dias</li>
                                <li><i class="fas fa-check"></i> ${plan.is_trial ? 'Plano de teste' : 'Plano pago'}</li>
                                <li><i class="fas fa-check"></i> Acesso completo ao sistema</li>
                                <li><i class="fas fa-check"></i> Suporte técnico</li>
                            </ul>
                            
                            <div class="plan-stats">
                                <div class="stat-row">
                                    <span class="stat-label">Usuários Ativos:</span>
                                    <span class="stat-value">${plan.active_users || 0}</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Receita Total:</span>
                                    <span class="stat-value">R$ ${(plan.total_revenue || 0).toFixed(2).replace('.', ',')}</span>
                                </div>
                            </div>
                            
                            <div class="plan-actions">
                                <button class="btn btn-outline-primary btn-sm" onclick="editPlan('${plan.id}')">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-outline-warning btn-sm" onclick="togglePlanStatus('${plan.id}', ${plan.is_active})">
                                    <i class="fas fa-${plan.is_active ? 'pause' : 'play'}"></i>
                                    ${plan.is_active ? 'Desativar' : 'Ativar'}
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deletePlan('${plan.id}')">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Modal functions
        function openPlanModal(planId = null) {
            editingPlanId = planId;
            const modal = document.getElementById('planModal');
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
                    document.getElementById('planActive').checked = plan.is_active;
                    document.getElementById('planTrial').checked = plan.is_trial;
                }
            } else {
                title.textContent = 'Novo Plano';
                document.getElementById('planForm').reset();
                document.getElementById('planActive').checked = true;
            }
            
            modal.style.display = 'block';
        }

        function closePlanModal() {
            document.getElementById('planModal').style.display = 'none';
            editingPlanId = null;
        }

        // Salvar plano
        async function savePlan() {
            const form = document.getElementById('planForm');
            const formData = new FormData(form);
            
            const planData = {
                name: document.getElementById('planName').value,
                description: document.getElementById('planDescription').value,
                price: parseFloat(document.getElementById('planPrice').value),
                duration_days: parseInt(document.getElementById('planDuration').value),
                is_active: document.getElementById('planActive').checked,
                is_trial: document.getElementById('planTrial').checked
            };
            
            // Validação
            if (!planData.name || planData.price < 0 || planData.duration_days <= 0) {
                showError('Por favor, preencha todos os campos obrigatórios corretamente.');
                return;
            }
            
            try {
                const url = editingPlanId 
                    ? `/api-reseller-plans.php/${editingPlanId}`
                    : '/api-reseller-plans.php';
                
                const method = editingPlanId ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(planData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(editingPlanId ? 'Plano atualizado com sucesso!' : 'Plano criado com sucesso!');
                    closePlanModal();
                    loadPlans();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showError('Erro ao salvar plano: ' + error.message);
            }
        }

        // Editar plano
        function editPlan(planId) {
            openPlanModal(planId);
        }

        // Alternar status do plano
        async function togglePlanStatus(planId, currentStatus) {
            const action = currentStatus ? 'desativar' : 'ativar';
            
            if (!confirm(`Tem certeza que deseja ${action} este plano?`)) return;
            
            try {
                const response = await fetch(`/api-reseller-plans.php/${planId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        is_active: !currentStatus
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(`Plano ${action}do com sucesso!`);
                    loadPlans();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showError(`Erro ao ${action} plano: ` + error.message);
            }
        }

        // Excluir plano
        async function deletePlan(planId) {
            if (!confirm('Tem certeza que deseja excluir este plano? Esta ação não pode ser desfeita.')) return;
            
            try {
                const response = await fetch(`/api-reseller-plans.php/${planId}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Plano excluído com sucesso!');
                    loadPlans();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                showError('Erro ao excluir plano: ' + error.message);
            }
        }

        // Funções de notificação
        function showSuccess(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                max-width: 400px;
                font-size: 14px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }
        
        function showError(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc3545;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                max-width: 400px;
                font-size: 14px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 7000);
        }

        // Utilitários
        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'flex';
            document.getElementById('plansGrid').style.display = 'none';
            document.getElementById('emptyState').style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('planModal');
            if (event.target === modal) {
                closePlanModal();
            }
        }
    </script>
</body>
</html>
