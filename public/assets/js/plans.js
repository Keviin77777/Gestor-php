/**
 * Plans - JavaScript
 */

let plansData = [];
let serversData = [];
let currentServerFilter = '';

document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticação
    if (!isAuthenticated()) {
        window.location.href = '/login';
        return;
    }
    
    // Carregar dados do usuário
    loadUserData();
    
    // Carregar dados
    loadServers();
    loadPlans();
    
    // Configurar eventos
    setupEvents();
    
    // Auto-expandir submenu de clientes
    setupSubmenu();
});

/**
 * Configurar eventos
 */
function setupEvents() {
    // Menu mobile
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Busca
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterPlans(this.value);
        });
    }

    // Filtro de servidor
    const serverFilter = document.getElementById('serverFilter');
    if (serverFilter) {
        serverFilter.addEventListener('change', function() {
            currentServerFilter = this.value;
            renderPlans();
        });
    }

    // Formulário
    const planForm = document.getElementById('planForm');
    if (planForm) {
        planForm.addEventListener('submit', function(e) {
            e.preventDefault();
            savePlan();
        });
    }
}

/**
 * Carregar dados do usuário
 */
function loadUserData() {
    try {
        const userStr = localStorage.getItem('user');
        if (!userStr) return;

        const user = JSON.parse(userStr);
        
        const userName = document.getElementById('userName');
        if (userName) {
            userName.textContent = user.name || 'Usuário';
        }

        const userEmail = document.getElementById('userEmail');
        if (userEmail) {
            userEmail.textContent = user.email || '';
        }

        const userAvatar = document.getElementById('userAvatar');
        if (userAvatar) {
            const initials = (user.name || 'U').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            userAvatar.textContent = initials;
        }
    } catch (error) {
        }
}

/**
 * Carregar servidores
 */
async function loadServers() {
    try {
        const token = localStorage.getItem('token');
        if (!token) {
            throw new Error('Token não encontrado');
        }

        const response = await fetch('/api-plans.php?action=servers', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            serversData = result.servers || [];
            populateServerSelects();
        } else {
            throw new Error(result.error || 'Erro desconhecido');
        }

    } catch (error) {
        showError('Erro ao carregar servidores: ' + error.message);
    }
}

/**
 * Carregar planos
 */
async function loadPlans() {
    try {
        const token = localStorage.getItem('token');
        if (!token) {
            throw new Error('Token não encontrado');
        }

        const response = await fetch('/api-plans.php', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            plansData = result.plans || [];
            renderPlans();
        } else {
            throw new Error(result.error || 'Erro desconhecido');
        }

    } catch (error) {
        showError('Erro ao carregar planos: ' + error.message);
        renderEmptyState();
    }
}

/**
 * Popular selects de servidor
 */
function populateServerSelects() {
    const serverFilter = document.getElementById('serverFilter');
    const planServer = document.getElementById('planServer');

    if (serverFilter) {
        serverFilter.innerHTML = '<option value="">Todos os servidores</option>' +
            serversData.map(server => 
                `<option value="${server.id}">${escapeHtml(server.name)}</option>`
            ).join('');
    }

    if (planServer) {
        planServer.innerHTML = '<option value="">Selecione um servidor...</option>' +
            serversData.map(server => 
                `<option value="${server.id}">${escapeHtml(server.name)}</option>`
            ).join('');
    }
}

/**
 * Renderizar planos agrupados por servidor
 */
function renderPlans() {
    const container = document.getElementById('plansContainer');
    if (!container) return;

    // Filtrar planos por servidor se selecionado
    let filteredPlans = plansData;
    if (currentServerFilter) {
        filteredPlans = plansData.filter(plan => plan.server_id == currentServerFilter);
    }

    // Agrupar planos por servidor
    const groupedPlans = {};
    filteredPlans.forEach(plan => {
        const serverId = plan.server_id || 'no-server';
        const serverName = plan.server_name || 'Sem servidor';
        
        if (!groupedPlans[serverId]) {
            groupedPlans[serverId] = {
                serverName: serverName,
                serverId: serverId,
                plans: []
            };
        }
        groupedPlans[serverId].plans.push(plan);
    });

    if (Object.keys(groupedPlans).length === 0) {
        renderEmptyState();
        return;
    }

    // Renderizar grupos
    container.innerHTML = Object.values(groupedPlans).map(group => `
        <div class="server-group">
            <div class="server-group-header">
                <div class="server-info">
                    <div class="server-details">
                        <h3 class="server-name">${escapeHtml(group.serverName)}</h3>
                        <div class="server-stats">
                            <span class="server-plan-count">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.25rem;">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                ${group.plans.length} plano${group.plans.length !== 1 ? 's' : ''}
                            </span>
                            <span class="server-status active">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.25rem;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="16 12 12 8 8 12"></polyline>
                                </svg>
                                Online
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="plans-grid">
                ${group.plans.map(plan => renderPlanCard(plan)).join('')}
            </div>
        </div>
    `).join('');
    
    // Adicionar event listeners para os botões
    setupPlanActionListeners();
}

/**
 * Renderizar card de plano
 */
function renderPlanCard(plan) {
    const statusClass = plan.status === 'active' ? 'active' : 'inactive';
    const statusText = plan.status === 'active' ? 'Ativo' : 'Inativo';
    
    return `
        <div class="plan-card ${statusClass}" data-plan-id="${plan.id}">
            <div class="plan-header">
                <h4 class="plan-name">${escapeHtml(plan.name)}</h4>
                <span class="plan-status status-${statusClass}">${statusText}</span>
            </div>
            
            <div class="plan-price">
                <span class="price-currency">R$</span>
                <span class="price-value">${formatPrice(plan.price)}</span>
                <span class="price-period">/mês</span>
            </div>
            
            <div class="plan-details">
                <div class="plan-detail">
                    <svg class="detail-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>${plan.duration_days} dias</span>
                </div>
                <div class="plan-detail">
                    <svg class="detail-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <span>${plan.max_screens} tela${plan.max_screens !== 1 ? 's' : ''}</span>
                </div>
            </div>
            
            ${plan.description && plan.description.trim() ? `<p class="plan-description">${escapeHtml(plan.description)}</p>` : ''}
            
            <div class="plan-actions">
                <button class="btn-action btn-edit" data-plan-id="${plan.id}" title="Editar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                <button class="btn-action btn-delete danger" data-plan-id="${plan.id}" title="Excluir">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3,6 5,6 21,6"></polyline>
                        <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
}

/**
 * Renderizar estado vazio
 */
function renderEmptyState() {
    const container = document.getElementById('plansContainer');
    if (!container) return;

    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <h3>Nenhum plano encontrado</h3>
            <p>${currentServerFilter ? 'Este servidor não possui planos cadastrados.' : 'Use o botão "Novo Plano" no canto superior direito para criar seu primeiro plano.'}</p>
        </div>
    `;
}

/**
 * Configurar event listeners para os botões de ação dos planos
 */
function setupPlanActionListeners() {
    // Botões de editar
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const planId = this.getAttribute('data-plan-id');
            editPlan(planId);
        });
    });
    
    // Botões de excluir
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const planId = this.getAttribute('data-plan-id');
            deletePlan(planId);
        });
    });
}

/**
 * Filtrar planos
 */
function filterPlans(searchTerm) {
    const planCards = document.querySelectorAll('.plan-card');
    
    planCards.forEach(card => {
        const text = card.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        card.style.display = matches ? '' : 'none';
    });
}

/**
 * Limpar filtro de servidor
 */
function clearServerFilter() {
    currentServerFilter = '';
    document.getElementById('serverFilter').value = '';
    renderPlans();
}

/**
 * Adicionar plano para servidor específico
 */
function addPlanForServer(serverId) {
    const planServer = document.getElementById('planServer');
    if (planServer) {
        planServer.value = serverId;
    }
    openPlanModal();
}

/**
 * Abrir modal de plano
 */
function openPlanModal(planId = null) {
    const modal = document.getElementById('planModal');
    const modalTitle = document.getElementById('planModalTitle');
    const form = document.getElementById('planForm');
    const submitBtn = document.getElementById('planSubmitBtn');
    
    if (planId) {
        modalTitle.textContent = 'Editar Plano';
        submitBtn.textContent = 'Atualizar Plano';
        loadPlanData(planId);
    } else {
        modalTitle.textContent = 'Adicionar Novo Plano';
        submitBtn.textContent = 'Adicionar Plano';
        form.reset();
    }
    
    modal.style.display = 'flex';
    modal.classList.add('active');
}

/**
 * Fechar modal de plano
 */
function closePlanModal() {
    const modal = document.getElementById('planModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    
    const form = document.getElementById('planForm');
    form.reset();
    delete form.dataset.planId;
}

/**
 * Carregar dados do plano para edição
 */
async function loadPlanData(planId) {
    try {
        const plan = plansData.find(p => p.id == planId);
        if (!plan) return;

        // Preencher formulário
        document.getElementById('planServer').value = plan.server_id || '';
        document.getElementById('planName').value = plan.name || '';
        document.getElementById('planPrice').value = plan.price || '';
        document.getElementById('planDuration').value = plan.duration_days || 30;
        document.getElementById('planScreens').value = plan.max_screens || 1;
        document.getElementById('planStatus').value = plan.status || 'active';
        document.getElementById('planDescription').value = plan.description || '';

        // Armazenar ID no formulário
        const form = document.getElementById('planForm');
        form.dataset.planId = planId;

    } catch (error) {
        showError('Erro ao carregar dados do plano');
    }
}

/**
 * Salvar plano
 */
async function savePlan() {
    try {
        const form = document.getElementById('planForm');
        const formData = new FormData(form);
        const planId = form.dataset.planId;
        
        const data = {
            server_id: formData.get('server_id'),
            name: formData.get('name'),
            price: parseFloat(formData.get('price')),
            duration_days: parseInt(formData.get('duration_days')),
            max_screens: parseInt(formData.get('max_screens')),
            status: formData.get('status'),
            description: formData.get('description')
        };

        // Validação
        if (!data.name || !data.price || !data.server_id) {
            showError('Preencha todos os campos obrigatórios');
            return;
        }

        const token = localStorage.getItem('token');
        const url = planId ? `/api-plans.php?id=${planId}` : '/api-plans.php';
        const method = planId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showSuccess(planId ? 'Plano atualizado com sucesso!' : 'Plano criado com sucesso!');
            closePlanModal();
            loadPlans();
        } else {
            throw new Error(result.error || 'Erro ao salvar plano');
        }

    } catch (error) {
        showError('Erro ao salvar plano: ' + error.message);
    }
}

/**
 * Editar plano
 */
function editPlan(planId) {
    openPlanModal(planId);
}

/**
 * Excluir plano
 */
async function deletePlan(planId) {
    if (!confirm('Tem certeza que deseja excluir este plano?')) {
        return;
    }

    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/api-plans.php?id=${planId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('Plano excluído com sucesso!');
            loadPlans();
        } else {
            throw new Error(result.error || 'Erro ao excluir plano');
        }

    } catch (error) {
        showError('Erro ao excluir plano: ' + error.message);
    }
}

/**
 * Funções globais para compatibilidade
 */
window.openPlanModalGlobal = openPlanModal;
window.closePlanModalGlobal = closePlanModal;

/**
 * Formatar preço
 */
function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace('.', ',');
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Mostrar erro
 */
function showError(message) {
    alert('Erro: ' + message);
}

/**
 * Mostrar sucesso
 */
function showSuccess(message) {
    alert('Sucesso: ' + message);
}

/**
 * Configurar submenu de navegação
 */
function setupSubmenu() {
    const currentPath = window.location.pathname;
    const clientsPaths = ['/clients', '/plans', '/applications'];

    if (clientsPaths.includes(currentPath)) {
        const submenu = document.getElementById('clients-submenu');
        const navItem = document.querySelector('.nav-item.has-submenu');

        if (submenu && navItem) {
            navItem.classList.add('expanded');
            submenu.classList.add('expanded');
        }
    }
}

// toggleSubmenu e logout agora estão em common.js