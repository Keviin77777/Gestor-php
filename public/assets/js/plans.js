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
    // Menu mobile - removido daqui, agora está no common.js

    // Busca de planos
    const searchPlans = document.getElementById('searchPlans');
    if (searchPlans) {
        searchPlans.addEventListener('input', function() {
            renderPlans();
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
 * Limpar todos os filtros
 */
function clearFilters() {
    const searchPlans = document.getElementById('searchPlans');
    const serverFilter = document.getElementById('serverFilter');
    
    if (searchPlans) searchPlans.value = '';
    if (serverFilter) serverFilter.value = '';
    
    currentServerFilter = '';
    renderPlans();
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

    // Obter valor da pesquisa
    const searchInput = document.getElementById('searchPlans');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';

    // Filtrar planos por servidor se selecionado
    let filteredPlans = plansData;
    
    // Filtro por servidor
    if (currentServerFilter) {
        filteredPlans = filteredPlans.filter(plan => plan.server_id == currentServerFilter);
    }
    
    // Filtro por pesquisa
    if (searchTerm) {
        filteredPlans = filteredPlans.filter(plan => {
            const name = (plan.name || '').toLowerCase();
            const serverName = (plan.server_name || '').toLowerCase();
            const price = (plan.price || '').toString();
            const description = (plan.description || '').toLowerCase();
            
            return name.includes(searchTerm) || 
                   serverName.includes(searchTerm) || 
                   price.includes(searchTerm) ||
                   description.includes(searchTerm);
        });
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

    // Renderizar tabela única com todos os planos
    container.innerHTML = `
        <div class="plans-table">
            <div class="plans-table-header">
                <div class="table-col col-server">Servidor</div>
                <div class="table-col col-name">Nome</div>
                <div class="table-col col-value">Valor</div>
                <div class="table-col col-duration">Duração</div>
                <div class="table-col col-connections">Conexões</div>
                <div class="table-col col-status">Status</div>
                <div class="table-col col-actions">Ações</div>
            </div>
            <div class="plans-table-body">
                ${filteredPlans.map(plan => renderPlanCard(plan)).join('')}
            </div>
        </div>
    `;
    
    // Adicionar event listeners para os botões
    setupPlanActionListeners();
}

/**
 * Renderizar card de plano - Layout Horizontal
 */
function renderPlanCard(plan) {
    const statusClass = plan.status === 'active' ? 'active' : 'inactive';
    const statusText = plan.status === 'active' ? 'Ativo' : 'Inativo';
    
    const serverName = plan.server_name || 'Sem servidor';
    const serverColorClass = getServerColorClass(serverName);
    
    return `
        <div class="plan-table-row ${statusClass}" data-plan-id="${plan.id}">
            <div class="table-col col-server">
                <span class="server-badge ${serverColorClass}">${escapeHtml(serverName)}</span>
            </div>
            
            <div class="table-col col-name">
                <span class="plan-name">${escapeHtml(plan.name)}</span>
            </div>
            
            <div class="table-col col-value">
                <span class="plan-price">
                    <span class="currency">R$</span>
                    <span class="amount">${formatPrice(plan.price)}</span>
                    <span class="period">/mês</span>
                </span>
            </div>
            
            <div class="table-col col-duration">
                <svg class="col-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>${plan.duration_days} dias</span>
            </div>
            
            <div class="table-col col-connections">
                <svg class="col-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                <span>${plan.max_screens} ${plan.max_screens === 1 ? 'tela' : 'telas'}</span>
            </div>
            
            <div class="table-col col-status">
                <span class="status-badge status-${statusClass}">${statusText}</span>
            </div>
            
            <div class="table-col col-actions">
                <button class="plan-action-btn edit-btn" data-plan-id="${plan.id}" title="Editar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                <button class="plan-action-btn delete-btn" data-plan-id="${plan.id}" title="Excluir">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
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
    document.querySelectorAll('.edit-btn, .btn-edit').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const planId = this.getAttribute('data-plan-id');
            editPlan(planId);
        });
    });
    
    // Botões de excluir
    document.querySelectorAll('.delete-btn, .btn-delete').forEach(btn => {
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
    window.location.href = `/plans/add?id=${planId}`;
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
 * DESABILITADO - Agora usa página separada /plans/add
 */
window.openPlanModalGlobal = function() {
    window.location.href = '/plans/add';
};
window.closePlanModalGlobal = function() {
    window.location.href = '/plans';
};

/**
 * Formatar preço
 */
function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace('.', ',');
}

/**
 * Gerar classe de cor para servidor baseada no nome
 */
function getServerColorClass(serverName) {
    // Gerar hash simples do nome do servidor
    let hash = 0;
    for (let i = 0; i < serverName.length; i++) {
        hash = serverName.charCodeAt(i) + ((hash << 5) - hash);
    }
    
    // Array de cores visíveis em tema escuro
    const colors = [
        'server-blue',
        'server-green', 
        'server-purple',
        'server-orange',
        'server-cyan',
        'server-pink',
        'server-teal',
        'server-yellow'
    ];
    
    // Usar o hash para selecionar uma cor
    const index = Math.abs(hash) % colors.length;
    return colors[index];
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