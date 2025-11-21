/**
 * Clientes - JavaScript
 */

let clients = [];
let currentPage = 1;
const itemsPerPage = 10;
let mercadoPagoConfigured = false;

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname === '/clients') {
        initClients();
    }
});

// Fallback - forçar inicialização após 1 segundo se necessário
setTimeout(() => {
    if (window.location.pathname === '/clients' && clients.length === 0) {
        initClients();
    }
}, 1000);

/**
 * Inicializar página de clientes
 */
function initClients() {
    loadUserInfo();
    loadTheme();
    setupEventListeners();
    loadClients();
    loadPlansAndServers(); // Carregar planos e servidores
    loadApplications(); // Carregar aplicativos
    loadWhatsAppTemplates(); // Carregar templates do WhatsApp
    checkSigmaServerAndShowButton(); // Verificar se tem servidor Sigma
}

/**
 * Carregar informações do usuário
 */
function loadUserInfo() {
    try {
        const userStr = localStorage.getItem('user');
        if (!userStr) return;

        const user = JSON.parse(userStr);

        const userName = document.getElementById('userName');
        const userEmail = document.getElementById('userEmail');
        const userAvatar = document.getElementById('userAvatar');

        if (userName) userName.textContent = user.name || 'Usuário';
        if (userEmail) userEmail.textContent = user.email || '';
        if (userAvatar) {
            const initials = (user.name || 'U').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            userAvatar.textContent = initials;
        }
    } catch (error) {
        // Erro ao carregar dados do usuário
    }
}

/**
 * Carregar tema salvo
 */
function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
}

/**
 * Carregar tema salvo
 */
function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
}

/**
 * Alternar tema
 */
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    // Mobile menu
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterClients, 300));
    }

    // Filters
    const statusFilter = document.getElementById('statusFilter');
    const planFilter = document.getElementById('planFilter');
    const dateFilter = document.getElementById('dateFilter');

    if (statusFilter) statusFilter.addEventListener('change', filterClients);
    if (planFilter) planFilter.addEventListener('change', filterClients);
    if (dateFilter) dateFilter.addEventListener('change', filterClients);

    // Form
    const clientForm = document.getElementById('clientForm');
    if (clientForm) {
        clientForm.addEventListener('submit', saveClient);
    }

    // Plan change
    const clientPlan = document.getElementById('clientPlan');
    if (clientPlan) {
        clientPlan.addEventListener('change', handlePlanChange);
    }
}

/**
 * Carregar clientes reais do banco de dados
 */
async function loadClients() {
    try {
        const response = await fetch('/api-clients.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            clients = result.clients || [];
            // Após carregar clientes, atualizar o filtro de planos para incluir planos dos clientes
            populatePlanFilter();
        } else {
            throw new Error('API retornou erro: ' + (result.error || 'Erro desconhecido'));
        }

    } catch (error) {
        clients = [];
    }

    renderClients();
}

/**
 * Renderizar tabela de clientes
 */
function renderClients() {
    const tbody = document.getElementById('clientsTableBody');
    if (!tbody) {
        return;
    }

    const filteredClients = getFilteredClients();
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageClients = filteredClients.slice(startIndex, endIndex);

    if (pageClients.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="12" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <div>Nenhum cliente encontrado</div>
                    <div style="font-size: 0.875rem; margin-top: 0.5rem;">Adicione seu primeiro cliente clicando em "Novo Cliente"</div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = pageClients.map(client => {
        try {
            const daysUntil = calculateDaysUntil(client.renewal_date);
            const statusInfo = getStatusInfo(client.status, daysUntil);

            return `
                <tr>
                    <td class="col-client">
                        <div class="pro-client-info">
                            <div class="pro-client-name">${client.name}</div>
                            <div class="pro-client-username">${client.username || 'Não definido'}</div>
                        </div>
                    </td>
                    <td class="col-iptv-user">
                        <div class="col-iptv-user">${client.username || 'N/A'}</div>
                    </td>
                    <td class="col-whatsapp">
                        <div class="pro-whatsapp">${client.phone || 'N/A'}</div>
                    </td>
                    <td class="col-expiry">
                        <div class="pro-expiry-info">
                            <div class="pro-expiry-date">${formatDate(client.renewal_date)}</div>
                            <div class="pro-days-badge ${daysUntil <= 3 ? 'expires-soon' : daysUntil <= 7 ? 'expires-warning' : 'expires-ok'}">
                                ${getDaysUntilText(daysUntil)}
                            </div>
                        </div>
                    </td>
                    <td class="col-server">
                        <span class="pro-server-badge">${client.server || 'Principal'}</span>
                    </td>
                    <td class="col-application">
                        <span class="pro-application-badge">${client.application_name || 'N/A'}</span>
                    </td>
                    <td class="col-mac">
                        <div class="pro-mac">${client.mac || 'N/A'}</div>
                    </td>
                    <td class="col-notifications">
                        <span class="pro-notification-toggle ${client.notifications === 'sim' ? 'enabled' : 'disabled'}">
                            ${client.notifications === 'sim' ? 'Sim' : 'Não'}
                        </span>
                    </td>
                    <td class="col-plan">
                        <span class="pro-plan-badge">${client.plan || 'Personalizado'}</span>
                    </td>
                    <td class="col-value">
                        <div class="pro-value">${formatMoney(client.value)}</div>
                    </td>
                    <td class="col-screens">
                        <div class="pro-screens">${client.screens || '1'}</div>
                    </td>
                    <td class="col-actions">
                        <div class="pro-actions">
                            <button class="pro-btn-action" onclick="editClient('${client.id}')" title="Editar Cliente">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="pro-btn-action" onclick="generateInvoice('${client.id}')" title="Gerar Fatura">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                </svg>
                            </button>
                            <button class="pro-btn-action" onclick="openPaymentHistory('${client.id}', '${client.name}')" title="Histórico de Pagamentos">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </button>
                            <button class="pro-btn-action ${client.phone ? 'whatsapp-enabled' : 'whatsapp-disabled'}" 
                                    onclick="${client.phone ? `openWhatsAppModal('${client.id}', '${client.name}', '${client.phone}')` : ''}" 
                                    title="${client.phone ? 'Enviar WhatsApp' : 'Número não cadastrado'}"
                                    ${!client.phone ? 'disabled' : ''}>
                                <svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945l-1.006 3.68 3.74-.982a9.86 9.86 0 005.26 1.51h.004c5.45 0 9.884-4.434 9.888-9.884.002-2.64-1.03-5.122-2.898-6.988a9.825 9.825 0 00-6.994-2.893z"/>
                                </svg>
                            </button>
                            <button class="pro-btn-action danger" onclick="deleteClient('${client.id}')" title="Excluir Cliente">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        } catch (error) {
            return `
                <tr>
                    <td colspan="12" style="color: red;">Erro ao carregar cliente: ${client.name}</td>
                </tr>
            `;
        }
    }).join('');

    renderPagination(filteredClients.length);
}

/**
 * Obter clientes filtrados
 */
function getFilteredClients() {
    let filtered = [...clients];

    // Filtro de busca
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter(client =>
            client.name.toLowerCase().includes(searchTerm) ||
            client.email?.toLowerCase().includes(searchTerm) ||
            client.phone?.includes(searchTerm) ||
            client.username?.toLowerCase().includes(searchTerm)
        );
    }

    // Filtro de status
    const statusFilter = document.getElementById('statusFilter')?.value;
    if (statusFilter) {
        filtered = filtered.filter(client => client.status === statusFilter);
    }

    // Filtro de plano
    const planFilter = document.getElementById('planFilter')?.value;
    if (planFilter) {
        filtered = filtered.filter(client => {
            const clientPlan = client.plan ? client.plan.trim() : '';
            return clientPlan.toLowerCase() === planFilter.toLowerCase();
        });
    }

    return filtered;
}

/**
 * Renderizar paginação
 */
function renderPagination(totalItems) {
    const pagination = document.getElementById('pagination');
    if (!pagination) return;

    const totalPages = Math.ceil(totalItems / itemsPerPage);

    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }

    let html = '<div class="pagination-info">Página ' + currentPage + ' de ' + totalPages + '</div>';
    html += '<div class="pagination-buttons">';

    // Botão anterior
    if (currentPage > 1) {
        html += `<button class="btn-pagination" onclick="changePage(${currentPage - 1})">Anterior</button>`;
    }

    // Números das páginas
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += `<button class="btn-pagination active">${i}</button>`;
        } else if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            html += `<button class="btn-pagination" onclick="changePage(${i})">${i}</button>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            html += '<span class="pagination-dots">...</span>';
        }
    }

    // Botão próximo
    if (currentPage < totalPages) {
        html += `<button class="btn-pagination" onclick="changePage(${currentPage + 1})">Próximo</button>`;
    }

    html += '</div>';
    pagination.innerHTML = html;
}

/**
 * Mudar página
 */
function changePage(page) {
    currentPage = page;
    renderClients();
}

/**
 * Filtrar clientes
 */
function filterClients() {
    currentPage = 1;
    renderClients();
}

/**
 * Limpar filtros
 */
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('planFilter').value = '';
    document.getElementById('dateFilter').value = '';
    filterClients();
}

/**
 * Carregar planos e servidores para os dropdowns
 */
let availablePlans = [];
let availableServers = [];
let availableApplications = [];

async function loadPlansAndServers() {
    try {
        // Obter token de autenticação
        const token = localStorage.getItem('token');

        // Carregar planos
        const plansResponse = await fetch('/api-plans.php');
        const plansResult = await plansResponse.json();

        if (plansResult.success && plansResult.plans) {
            availablePlans = plansResult.plans || [];
            // Preencher filtro de planos após carregar
            populatePlanFilter();
        } else {
            console.error('Erro ao carregar planos:', plansResult.error || 'Resposta inválida');
            availablePlans = [];
            // Mesmo com erro, tentar preencher o filtro com planos vazios
            populatePlanFilter();
        }

        // Carregar servidores - usar a rota correta
        const serversResponse = await fetch('/api-servers.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (!serversResponse.ok) {
            throw new Error(`HTTP ${serversResponse.status}: ${serversResponse.statusText}`);
        }

        const serversResult = await serversResponse.json();

        if (serversResult.success) {
            availableServers = serversResult.servers || [];
        } else {
            console.error('Erro ao carregar servidores:', serversResult.error);
            availableServers = [];
        }
    } catch (error) {
        console.error('Erro ao carregar planos/servidores:', error);
        availableServers = [];
        availablePlans = [];
        // Mesmo com erro, tentar preencher o filtro
        populatePlanFilter();
    }
}

/**
 * Preencher dropdown de planos no modal
 */
function populatePlansDropdown() {
    const planSelect = document.getElementById('clientPlan');
    if (!planSelect) return;

    // Limpar opções existentes
    planSelect.innerHTML = '<option value="">Selecionar plano</option>';

    // Adicionar planos do banco de dados
    availablePlans.forEach(plan => {
        if (plan.status === 'active') {
            const option = document.createElement('option');
            option.value = plan.name;
            option.textContent = `${plan.name} - R$ ${plan.price.toFixed(2)}`;
            option.dataset.price = plan.price;
            planSelect.appendChild(option);
        }
    });

    // Adicionar opção personalizada
    const customOption = document.createElement('option');
    customOption.value = 'Personalizado';
    customOption.textContent = 'Personalizado';
    planSelect.appendChild(customOption);
}

/**
 * Preencher filtro de planos na barra de filtros
 */
function populatePlanFilter() {
    const planFilter = document.getElementById('planFilter');
    if (!planFilter) return;

    // Limpar opções existentes, mantendo apenas "Todos os planos"
    planFilter.innerHTML = '<option value="">Todos os planos</option>';

    // Coletar planos únicos de duas fontes:
    // 1. Planos disponíveis na API
    const planNamesFromAPI = availablePlans && availablePlans.length > 0 
        ? [...new Set(availablePlans.map(plan => plan.name).filter(name => name))] 
        : [];
    
    // 2. Planos já usados pelos clientes (para incluir planos antigos que podem não estar mais na lista)
    const planNamesFromClients = clients && clients.length > 0
        ? [...new Set(clients.map(client => client.plan).filter(plan => plan && plan.trim() !== ''))]
        : [];
    
    // Combinar e remover duplicatas
    const allUniquePlanNames = [...new Set([...planNamesFromAPI, ...planNamesFromClients])];
    
    // Ordenar alfabeticamente
    allUniquePlanNames.sort();
    
    // Adicionar ao filtro
    allUniquePlanNames.forEach(planName => {
        const option = document.createElement('option');
        option.value = planName;
        option.textContent = planName;
        planFilter.appendChild(option);
    });
}

/**
 * Preencher dropdown de servidores
 */
async function populateServersDropdown() {
    const serverSelect = document.getElementById('clientServer');
    if (!serverSelect) return;

    // Limpar opções existentes
    serverSelect.innerHTML = '<option value="">Carregando servidores...</option>';

    // Se não há servidores carregados, tentar carregar novamente
    if (!availableServers || availableServers.length === 0) {
        await loadPlansAndServers();
    }

    // Limpar e adicionar opção padrão
    serverSelect.innerHTML = '<option value="">Selecionar servidor</option>';

    // Adicionar servidores do banco de dados
    if (availableServers && availableServers.length > 0) {
        availableServers.forEach(server => {
            if (server.status === 'active') {
                const option = document.createElement('option');
                option.value = server.name;
                option.textContent = server.name;
                serverSelect.appendChild(option);
            }
        });
    } else {
        // Se ainda não há servidores, mostrar mensagem
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Nenhum servidor disponível';
        option.disabled = true;
        serverSelect.appendChild(option);
    }
}

/**
 * Carregar aplicativos do banco de dados
 */
async function loadApplications() {
    try {
        const token = localStorage.getItem('token');
        
        const response = await fetch('/api-applications.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            availableApplications = result.applications || [];
        } else {
            console.error('Erro ao carregar aplicativos:', result.error);
            availableApplications = [];
        }
    } catch (error) {
        console.error('Erro ao carregar aplicativos:', error);
        availableApplications = [];
    }
}

/**
 * Preencher dropdown de aplicativos
 */
async function populateApplicationsDropdown() {
    const applicationSelect = document.getElementById('clientApplication');
    if (!applicationSelect) return;

    // Limpar opções existentes
    applicationSelect.innerHTML = '<option value="">Carregando aplicativos...</option>';

    // Se não há aplicativos carregados, tentar carregar novamente
    if (!availableApplications || availableApplications.length === 0) {
        await loadApplications();
    }

    // Limpar e adicionar opção padrão
    applicationSelect.innerHTML = '<option value="">Selecionar aplicativo</option>';

    // Adicionar aplicativos do banco de dados
    if (availableApplications && availableApplications.length > 0) {
        availableApplications.forEach(application => {
            const option = document.createElement('option');
            option.value = application.id;
            option.textContent = application.name;
            applicationSelect.appendChild(option);
        });
    } else {
        // Se ainda não há aplicativos, mostrar mensagem
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Nenhum aplicativo disponível';
        option.disabled = true;
        applicationSelect.appendChild(option);
    }
}

/**
 * Abrir modal de adicionar cliente
 */
async function openAddClientModal() {
    document.getElementById('modalTitle').textContent = 'Novo Cliente';
    document.getElementById('clientForm').reset();

    // Definir data de vencimento padrão (30 dias)
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('clientRenewalDate').value = nextMonth.toISOString().split('T')[0];

    // Definir número de telas padrão
    document.getElementById('clientScreens').value = '1';

    // Preencher dropdowns com dados reais
    populatePlansDropdown();
    await populateServersDropdown();
    await populateApplicationsDropdown();

    // Inicializar validação de senha após preencher dropdowns
    setTimeout(() => {
        if (typeof setupPasswordValidation === 'function') {
            setupPasswordValidation();
        }
    }, 100);

    document.getElementById('clientModal').classList.add('active');
}

/**
 * Fechar modal
 */
function closeClientModal() {
    document.getElementById('clientModal').classList.remove('active');
}

/**
 * Manipular mudança de plano
 */
function handlePlanChange() {
    const planSelect = document.getElementById('clientPlan');
    const valueInput = document.getElementById('clientValue');

    if (!planSelect || !valueInput) return;

    const selectedOption = planSelect.selectedOptions[0];

    // Se tiver preço definido no dataset, usar ele
    if (selectedOption && selectedOption.dataset.price) {
        valueInput.value = parseFloat(selectedOption.dataset.price).toFixed(2);
    } else if (planSelect.value === 'Personalizado') {
        // Limpar valor para plano personalizado
        valueInput.value = '';
        valueInput.focus();
    }
}


/**
 * Editar cliente
 */
async function editClient(clientId) {
    const client = clients.find(c => c.id == clientId);
    if (!client) {
        alert('Cliente não encontrado!');
        return;
    }

    // Marcar como edição ANTES de preencher campos
    const form = document.getElementById('clientForm');
    form.dataset.editing = String(clientId); // Converter para string sempre

    // Preencher dropdowns com dados reais primeiro
    populatePlansDropdown();
    await populateServersDropdown();
    await populateApplicationsDropdown();

    // Usar setTimeout para garantir que os dropdowns foram preenchidos
    setTimeout(() => {
        // Preencher o modal com os dados do cliente
        document.getElementById('clientName').value = client.name || '';
        document.getElementById('clientEmail').value = client.email || '';
        document.getElementById('clientPhone').value = client.phone || '';
        document.getElementById('clientUsername').value = client.username || '';
        document.getElementById('clientIptvPassword').value = client.iptv_password || '';

        // Preencher plano, servidor e aplicativo
        const planSelect = document.getElementById('clientPlan');
        const serverSelect = document.getElementById('clientServer');
        const applicationSelect = document.getElementById('clientApplication');

        if (planSelect) {
            planSelect.value = client.plan || '';
        }

        if (serverSelect) {
            serverSelect.value = client.server || '';
        }

        if (applicationSelect) {
            // O client.application_id pode estar no formato numérico ou string
            applicationSelect.value = client.application_id || '';
        }

        document.getElementById('clientValue').value = client.value || '';
        document.getElementById('clientRenewalDate').value = client.renewal_date || '';
        document.getElementById('clientMac').value = client.mac || '';
        document.getElementById('clientNotifications').value = client.notifications || 'sim';
        document.getElementById('clientScreens').value = client.screens || '1';
        document.getElementById('clientNotes').value = client.notes || '';
    }, 200);

    // Alterar título do modal
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    if (modalTitle) {
        modalTitle.textContent = 'Editar Cliente';
    }
    if (submitBtn) {
        submitBtn.textContent = 'Salvar Alterações';
    }

    // Abrir modal
    const modal = document.getElementById('clientModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
    }
}





/**
 * Exportar clientes
 */
function exportClients() {
    const csv = generateCSV(getFilteredClients());
    downloadCSV(csv, 'clientes.csv');
    showNotification('Clientes exportados com sucesso!', 'success');
}

/**
 * Gerar CSV
 */
function generateCSV(data) {
    const headers = ['Nome', 'Email', 'Telefone', 'Usuário', 'Plano', 'Valor', 'Vencimento', 'Status'];
    const rows = data.map(client => [
        client.name,
        client.email || '',
        client.phone || '',
        client.username || '',
        client.plan,
        client.value,
        client.renewal_date,
        client.status
    ]);

    return [headers, ...rows].map(row => row.join(',')).join('\n');
}

/**
 * Download CSV
 */
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

/**
 * Obter informações de status
 */
function getStatusInfo(status, daysUntil) {
    if (status === 'active') {
        if (daysUntil < 0) return { class: 'expired', text: 'Vencido' };
        if (daysUntil <= 3) return { class: 'warning', text: 'A vencer' };
        return { class: 'active', text: 'Ativo' };
    }

    const statusMap = {
        'inactive': { class: 'inactive', text: 'Inativo' },
        'suspended': { class: 'suspended', text: 'Suspenso' }
    };

    return statusMap[status] || { class: 'unknown', text: 'Desconhecido' };
}

/**
 * Obter texto de dias até vencimento
 */
function getDaysUntilText(days) {
    if (days < 0) return `Venceu há ${Math.abs(days)} dias`;
    if (days === 0) return 'Vence hoje';
    if (days === 1) return 'Vence amanhã';
    return `${days} dias`;
}

/**
 * Formatar valor monetário
 */
function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

/**
 * Formatar data
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    
    try {
        // Se a data já está no formato brasileiro (dd/mm/yyyy), retornar como está
        if (dateString.includes('/')) {
            return dateString;
        }
        
        // Se está no formato ISO (yyyy-mm-dd), converter para brasileiro
        if (dateString.includes('-')) {
            const [year, month, day] = dateString.split(' ')[0].split('-');
            return `${day}/${month}/${year}`;
        }
        
        // Fallback para new Date
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return dateString;
        }
        
        return date.toLocaleDateString('pt-BR');
    } catch (error) {
        return dateString;
    }
}

/**
 * Calcular dias até data
 */
function calculateDaysUntil(dateString) {
    // Criar data de hoje sem hora
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Parsear a data de vencimento
    let targetDate;
    if (dateString.includes('/')) {
        // Formato DD/MM/YYYY
        const [day, month, year] = dateString.split('/');
        targetDate = new Date(year, month - 1, day);
    } else if (dateString.includes('-')) {
        // Formato YYYY-MM-DD
        const [year, month, day] = dateString.split('-');
        targetDate = new Date(year, month - 1, day);
    } else {
        // Fallback
        targetDate = new Date(dateString);
    }
    targetDate.setHours(0, 0, 0, 0);

    // Calcular diferença em dias
    const diffTime = targetDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    return diffDays;
}

/**
 * Obter texto de dias até vencimento
 */
function getDaysUntilText(days) {
    if (days < 0) return `Venceu há ${Math.abs(days)} dias`;
    if (days === 0) return 'Vence hoje';
    if (days === 1) return 'Vence amanhã';
    return `${days} dias`;
}

/**
 * Obter informações de status
 */
function getStatusInfo(status, daysUntil) {
    if (status === 'active') {
        if (daysUntil < 0) return { class: 'expired', text: 'Vencido' };
        if (daysUntil <= 3) return { class: 'warning', text: 'A vencer' };
        return { class: 'active', text: 'Ativo' };
    }

    const statusMap = {
        'inactive': { class: 'inactive', text: 'Inativo' },
        'suspended': { class: 'suspended', text: 'Suspenso' }
    };

    return statusMap[status] || { class: 'unknown', text: 'Desconhecido' };
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Mostrar notificação
 */
function showNotification(message, type = 'info') {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    // Adicionar ao DOM
    document.body.appendChild(notification);

    // Mostrar com animação
    setTimeout(() => notification.classList.add('show'), 100);

    // Remover após 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}/**

 * Editar cliente
 */
async function editClient(clientId) {
    const client = clients.find(c => c.id == clientId);
    if (!client) {
        alert('Cliente não encontrado!');
        return;
    }

    // Marcar como edição ANTES de preencher campos
    const form = document.getElementById('clientForm');
    form.dataset.editing = String(clientId); // Converter para string sempre

    // Preencher dropdowns com dados reais primeiro
    populatePlansDropdown();
    await populateServersDropdown();
    await populateApplicationsDropdown();

    // Usar setTimeout para garantir que os dropdowns foram preenchidos
    setTimeout(() => {
        // Preencher o modal com os dados do cliente
        document.getElementById('clientName').value = client.name || '';
        document.getElementById('clientEmail').value = client.email || '';
        document.getElementById('clientPhone').value = client.phone || '';
        document.getElementById('clientUsername').value = client.username || '';
        document.getElementById('clientIptvPassword').value = client.iptv_password || '';

        // Preencher plano, servidor e aplicativo
        const planSelect = document.getElementById('clientPlan');
        const serverSelect = document.getElementById('clientServer');
        const applicationSelect = document.getElementById('clientApplication');

        if (planSelect) {
            planSelect.value = client.plan || '';
        }

        if (serverSelect) {
            serverSelect.value = client.server || '';
        }

        if (applicationSelect) {
            // O client.application_id pode estar no formato numérico ou string
            applicationSelect.value = client.application_id || '';
        }

        document.getElementById('clientValue').value = client.value || '';
        document.getElementById('clientRenewalDate').value = client.renewal_date || '';
        document.getElementById('clientMac').value = client.mac || '';
        document.getElementById('clientNotifications').value = client.notifications || 'sim';
        document.getElementById('clientScreens').value = client.screens || '1';
        document.getElementById('clientNotes').value = client.notes || '';
    }, 200);

    // Alterar título do modal
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    if (modalTitle) {
        modalTitle.textContent = 'Editar Cliente';
    }
    if (submitBtn) {
        submitBtn.textContent = 'Salvar Alterações';
    }

    // Abrir modal
    const modal = document.getElementById('clientModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
    }
}

/**
 * Gerar fatura para cliente
 */
async function generateInvoice(clientId) {
    const client = clients.find(c => c.id == clientId);
    if (!client) {
        showNotification('Cliente não encontrado!', 'error');
        return;
    }

    // Confirmar geração da fatura com modal personalizado
    const today = new Date();
    const dueDate = new Date(today.getTime() + (5 * 24 * 60 * 60 * 1000)); // 5 dias

    // Calcular período da fatura baseado no vencimento do cliente
    const clientRenewalDate = new Date(client.renewal_date);
    const renewalMonth = clientRenewalDate.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });

    showConfirmModal({
        title: 'Gerar Fatura',
        subtitle: `Cliente: ${client.name}`,
        message: `Confirma a geração da fatura para o período de ${renewalMonth}?`,
        type: 'info',
        confirmText: 'Gerar Fatura',
        cancelText: 'Cancelar',
        details: [
            { label: 'Cliente', value: client.name },
            { label: 'Período', value: renewalMonth, highlight: true },
            { label: 'Valor', value: formatMoney(client.value), highlight: true },
            { label: 'Vencimento', value: clientRenewalDate.toLocaleDateString('pt-BR') },
            { label: 'Plano', value: client.plan || 'Não definido' },
            { label: 'Status', value: 'Pendente' }
        ],
        onConfirm: async () => {
            await processInvoiceGeneration(clientId, client);
        }
    });
}

/**
 * Processar geração da fatura (separado para usar no modal)
 */
async function processInvoiceGeneration(clientId, client) {

    try {
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch('/api-generate-invoice.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    client_id: clientId
                })
            });

            const data = await response.json();

            if (data.success) {
                const invoice = data.invoice;
                const dueDate = new Date(invoice.due_date).toLocaleDateString('pt-BR');
                showNotification(
                    `✅ Fatura gerada com sucesso!\n\n` +
                    `Cliente: ${client.name}\n` +
                    `Valor: ${formatMoney(invoice.value)}\n` +
                    `Vencimento: ${dueDate}`,
                    'success'
                );

                // Se o modal de histórico estiver aberto para este cliente, recarregar
                const modal = document.getElementById('paymentHistoryModal');
                if (modal && modal.classList.contains('active') && getCurrentClientId() === clientId) {
                    await loadPaymentHistory(clientId);
                }

                // A data de vencimento do cliente só será atualizada quando a fatura for paga
                // Não atualizamos aqui para manter a lógica correta
            } else {
                throw new Error(data.error || 'Erro ao gerar fatura');
            }
        }, {
            type: 'global',
            id: 'generate-invoice'
        });

    } catch (error) {
        showNotification('Erro ao gerar fatura: ' + error.message, 'error');
    }
}

/**
 * Excluir cliente
 */
async function deleteClient(clientId) {
    const client = clients.find(c => c.id == clientId);
    if (!client) {
        alert('Cliente não encontrado!');
        return;
    }

    // Confirmar exclusão com modal personalizado
    showConfirmModal({
        title: 'Excluir Cliente',
        subtitle: 'Ação Irreversível',
        message: `Tem certeza que deseja excluir o cliente "${client.name}"?`,
        type: 'danger',
        confirmText: 'Excluir Cliente',
        cancelText: 'Cancelar',
        details: [
            { label: 'Cliente', value: client.name },
            { label: 'Plano', value: client.plan || 'Não definido' },
            { label: 'Valor', value: formatMoney(client.value) },
            { label: 'Atenção', value: 'Esta ação não pode ser desfeita', highlight: true }
        ],
        onConfirm: async () => {
            await processClientDeletion(clientId, client);
        }
    });
}

/**
 * Processar exclusão do cliente (separado para usar no modal)
 */
async function processClientDeletion(clientId, client) {
    // Fazer chamada para a API
    try {
        const response = await fetch(`/api-clients.php?id=${clientId}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            // Recarregar lista do banco de dados ao invés de remover localmente
            await loadClients();

            // Mostrar mensagem de sucesso
            showNotification(`Cliente ${client.name} excluído com sucesso!`, 'success');
        } else {
            showNotification('Erro ao excluir cliente: ' + result.error, 'error');
        }

    } catch (error) {
        showNotification('Erro ao excluir cliente. Tente novamente.', 'error');
    }
}

/**
 * Abrir modal de novo cliente
 */
async function openClientModal() {
    // Limpar formulário
    const form = document.getElementById('clientForm');
    if (form) {
        form.reset();
        delete form.dataset.editing;
    }

    // Alterar título do modal
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    if (modalTitle) {
        modalTitle.textContent = 'Adicionar Novo Cliente';
    }
    if (submitBtn) {
        submitBtn.textContent = 'Adicionar';
    }

    // Definir data padrão (30 dias a partir de hoje)
    const renewalDate = document.getElementById('clientRenewalDate');
    if (renewalDate) {
        const today = new Date();
        today.setDate(today.getDate() + 30);
        renewalDate.value = today.toISOString().split('T')[0];
    }

    // Definir número de telas padrão
    const screensField = document.getElementById('clientScreens');
    if (screensField) {
        screensField.value = '1';
    }

    // Preencher dropdowns com dados reais
    populatePlansDropdown();
    await populateServersDropdown();
    await populateApplicationsDropdown();

    // Abrir modal
    const modal = document.getElementById('clientModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
    }
}

/**
 * Fechar modal
 */
function closeClientModal() {
    const modal = document.getElementById('clientModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
            // Limpar formulário DEPOIS de fechar completamente
            const form = document.getElementById('clientForm');
            if (form) {
                form.reset();
                delete form.dataset.editing;
            }
        }, 300);
    }
}

/**
 * Salvar cliente (novo ou editado)
 */
async function saveClient(event) {
    event.preventDefault();

    const form = document.getElementById('clientForm');
    const formData = new FormData(form);
    const clientData = Object.fromEntries(formData);

    // Verificar se está editando - usar undefined check ao invés de !!valor
    const isEditing = form.dataset.editing !== undefined && form.dataset.editing !== '';

    // Validar dados obrigatórios APENAS ao criar novo cliente
    if (!isEditing) {
        // Validar campos obrigatórios básicos
        if (!clientData.name || !clientData.value || !clientData.renewal_date) {
            alert('Por favor, preencha todos os campos obrigatórios (Nome, Valor e Data de Vencimento).');
            return;
        }

        // Validar número de telas
        if (!clientData.screens || clientData.screens === '' || parseInt(clientData.screens) < 1) {
            alert('Por favor, preencha o número de telas (mínimo 1).');
            const screensField = document.getElementById('clientScreens');
            if (screensField) {
                screensField.focus();
                screensField.classList.add('field-error');
                setTimeout(() => {
                    screensField.classList.remove('field-error');
                }, 3000);
            }
            return;
        }

        // Validar valor monetário
        if (isNaN(parseFloat(clientData.value)) || parseFloat(clientData.value) <= 0) {
            alert('Por favor, insira um valor válido maior que zero.');
            const valueField = document.getElementById('clientValue');
            if (valueField) {
                valueField.focus();
                valueField.classList.add('field-error');
                setTimeout(() => {
                    valueField.classList.remove('field-error');
                }, 3000);
            }
            return;
        }
    }
    
    // Validar senha IPTV se servidor tiver Sigma configurado
    if (typeof validatePasswordBeforeSaveDesktop === 'function') {
        if (!validatePasswordBeforeSaveDesktop()) {
            return;
        }
    }

    try {
        if (isEditing) {
            // Editar cliente existente
            const clientId = form.dataset.editing;

            const response = await fetch(`/api-clients.php?id=${clientId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(clientData)
            });

            const result = await response.json();

            if (result.success) {
                // Recarregar lista de clientes do banco de dados ANTES de fechar
                await loadClients();

                // Fechar modal DEPOIS de salvar
                closeClientModal();

                // Mostrar mensagem de sucesso
                let message = 'Cliente atualizado com sucesso!';
                if (result.invoice_generated) {
                    message += ' Fatura gerada automaticamente devido à renovação próxima.';
                }
                showNotification(message, 'success');
            } else {
                alert('Erro: ' + result.error);
                return;
            }
        } else {
            // Criar novo cliente
            const response = await fetch('/api-clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(clientData)
            });

            const result = await response.json();

            if (result.success) {
                // Recarregar lista de clientes ANTES de fechar
                await loadClients();

                // Fechar modal DEPOIS de salvar
                closeClientModal();

                // Mostrar mensagem de sucesso
                let message = 'Cliente criado com sucesso!';
                if (result.invoice_generated) {
                    message += ' Fatura gerada automaticamente devido à renovação próxima.';
                }
                showNotification(message, 'success');
            } else {
                alert('Erro: ' + result.error);
                return;
            }
        }

    } catch (error) {
        alert('Erro ao salvar cliente. Tente novamente.');
    }
}

// Configurar event listeners para o modal
document.addEventListener('DOMContentLoaded', function () {
    // Event listener para o formulário
    const clientForm = document.getElementById('clientForm');
    if (clientForm) {
        clientForm.addEventListener('submit', saveClient);
    }

    // Event listener para fechar modal clicando fora
    const modal = document.getElementById('clientModal');
    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeClientModal();
            }
        });
    }

    // Event listener para botão de novo cliente
    const newClientBtn = document.getElementById('newClientBtn');
    if (newClientBtn) {
        newClientBtn.addEventListener('click', openClientModal);
    }
});

/**
 * Gerar senha aleatória
 */
function generatePassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let password = '';
    for (let i = 0; i < 8; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }

    const passwordField = document.getElementById('clientPassword');
    if (passwordField) {
        passwordField.value = password;
    }
}

/**
 * Gerar senha IPTV aleatória
 */
function generateIptvPassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let password = '';
    for (let i = 0; i < 6; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }

    const passwordField = document.getElementById('clientIptvPassword');
    if (passwordField) {
        passwordField.value = password;
    }
}

/**
 * Alternar visibilidade da senha
 */
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.type = field.type === 'password' ? 'text' : 'password';
    }
}

/**
 * Atualizar campos baseado no plano selecionado
 */
function updatePlanValue() {
    const planSelect = document.getElementById('clientPlan');
    const valueField = document.getElementById('clientValue');

    if (planSelect && valueField) {
        const planValues = {
            'basic': 25.00,
            'premium': 35.00,
            'vip': 50.00
        };

        const selectedPlan = planSelect.value;
        if (planValues[selectedPlan]) {
            valueField.value = planValues[selectedPlan].toFixed(2);
        }
    }
}

// Event listener para mudança de plano
document.addEventListener('DOMContentLoaded', function () {
    const planSelect = document.getElementById('clientPlan');
    if (planSelect) {
        planSelect.addEventListener('change', updatePlanValue);
    }
});

/**
 * Configurar indicadores de scroll horizontal
 */
function setupScrollIndicators() {
    const container = document.getElementById('tableContainer');
    if (!container) return;

    function updateScrollIndicators() {
        const { scrollLeft, scrollWidth, clientWidth } = container;

        // Remover classes existentes
        container.classList.remove('scrolled-left', 'scrolled-right');

        // Adicionar classes baseadas na posição do scroll
        if (scrollLeft > 10) {
            container.classList.add('scrolled-left');
        }

        if (scrollLeft < scrollWidth - clientWidth - 10) {
            container.classList.add('scrolled-right');
        }
    }

    // Configurar listener de scroll
    container.addEventListener('scroll', updateScrollIndicators);

    // Configurar indicadores iniciais
    setTimeout(updateScrollIndicators, 100);

    // Atualizar quando a janela redimensionar
    window.addEventListener('resize', updateScrollIndicators);
}

// Configurar indicadores quando o DOM carregar
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(setupScrollIndicators, 500);
});

/**
 * Configurar indicadores de scroll horizontal limpos
 */
function setupCleanScrollIndicators() {
    const container = document.getElementById('tableContainer');
    const scrollHint = document.getElementById('scrollHint');

    if (!container || !scrollHint) return;

    function checkScrollNeed() {
        const { scrollWidth, clientWidth } = container;
        const needsScroll = scrollWidth > clientWidth + 50; // margem de 50px

        if (needsScroll) {
            scrollHint.classList.remove('hidden');
        } else {
            scrollHint.classList.add('hidden');
        }
    }

    // Verificar se precisa de scroll
    setTimeout(checkScrollNeed, 200);

    // Atualizar quando a janela redimensionar
    window.addEventListener('resize', checkScrollNeed);

    // Esconder o hint após o primeiro scroll
    let hasScrolled = false;
    container.addEventListener('scroll', function () {
        if (!hasScrolled) {
            hasScrolled = true;
            setTimeout(() => {
                scrollHint.classList.add('hidden');
            }, 3000); // Esconder após 3 segundos
        }
    });
}

// Configurar indicadores limpos quando o DOM carregar
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(setupCleanScrollIndicators, 600);
});

// toggleSubmenu agora está em common.js

// Auto-expandir submenu se estiver em uma página do submenu
document.addEventListener('DOMContentLoaded', function () {
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
});

/**
 * Abrir modal de histórico de pagamentos
 */
async function openPaymentHistory(clientId, clientName) {
    const modal = document.getElementById('paymentHistoryModal');
    const clientNameElement = document.getElementById('paymentHistoryClientName');

    if (!modal) return;

    // Armazenar ID do cliente atual
    setCurrentClientId(clientId);

    // Definir nome do cliente
    if (clientNameElement) {
        clientNameElement.textContent = clientName;
    }

    // Mostrar modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Verificar status do Mercado Pago e atualizar botão
    await checkMercadoPagoStatus();

    // Carregar dados do histórico
    await loadPaymentHistory(clientId);
}

/**
 * Fechar modal de histórico de pagamentos
 */
function closePaymentHistoryModal() {
    const modal = document.getElementById('paymentHistoryModal');
    if (!modal) return;

    modal.classList.remove('active');
    document.body.style.overflow = '';

    // Limpar ID do cliente atual
    setCurrentClientId(null);
}

/**
 * Carregar histórico de pagamentos
 */
async function loadPaymentHistory(clientId) {
    try {
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch(`/api-invoices.php?client_id=${clientId}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // Calcular estatísticas das faturas
                const invoices = data.invoices || [];
                const stats = {
                    total: { count: invoices.length, amount: 0 },
                    paid: { count: 0, amount: 0 },
                    pending: { count: 0, amount: 0 },
                    cancelled: { count: 0, amount: 0 }
                };
                
                invoices.forEach(invoice => {
                    const value = parseFloat(invoice.value || 0);
                    stats.total.amount += value;
                    
                    if (invoice.status === 'paid') {
                        stats.paid.count++;
                        stats.paid.amount += value;
                    } else if (invoice.status === 'pending') {
                        stats.pending.count++;
                        stats.pending.amount += value;
                    } else if (invoice.status === 'cancelled') {
                        stats.cancelled.count++;
                        stats.cancelled.amount += value;
                    }
                });
                
                updatePaymentStats(stats);
                renderPaymentHistory(invoices);
            } else {
                throw new Error(data.error || 'Erro ao carregar histórico');
            }
        }, {
            type: 'global',
            id: 'load-payment-history'
        });

    } catch (error) {
        showNotification('Erro ao carregar histórico de pagamentos: ' + error.message, 'error');
        renderEmptyPaymentHistory();
    }
}

/**
 * Atualizar estatísticas de pagamento
 */
function updatePaymentStats(stats) {
    // Validar se stats existe e tem a estrutura esperada
    if (!stats || typeof stats !== 'object') {
        stats = {
            total: { count: 0, amount: 0 },
            paid: { count: 0, amount: 0 },
            pending: { count: 0, amount: 0 },
            cancelled: { count: 0, amount: 0 }
        };
    }
    
    // Garantir que cada propriedade existe
    stats.total = stats.total || { count: 0, amount: 0 };
    stats.paid = stats.paid || { count: 0, amount: 0 };
    stats.pending = stats.pending || { count: 0, amount: 0 };
    stats.cancelled = stats.cancelled || { count: 0, amount: 0 };
    
    // Total
    const totalInvoices = document.getElementById('totalInvoices');
    const totalAmount = document.getElementById('totalAmount');
    if (totalInvoices) totalInvoices.textContent = stats.total.count || 0;
    if (totalAmount) totalAmount.textContent = formatMoney(stats.total.amount || 0);

    // Pagas
    const paidInvoices = document.getElementById('paidInvoices');
    const paidAmount = document.getElementById('paidAmount');
    if (paidInvoices) paidInvoices.textContent = stats.paid.count || 0;
    if (paidAmount) paidAmount.textContent = formatMoney(stats.paid.amount || 0);

    // Pendentes
    const pendingInvoices = document.getElementById('pendingInvoices');
    const pendingAmount = document.getElementById('pendingAmount');
    if (pendingInvoices) pendingInvoices.textContent = stats.pending.count || 0;
    if (pendingAmount) pendingAmount.textContent = formatMoney(stats.pending.amount || 0);

    // Canceladas
    const cancelledInvoices = document.getElementById('cancelledInvoices');
    const cancelledAmount = document.getElementById('cancelledAmount');
    if (cancelledInvoices) cancelledInvoices.textContent = stats.cancelled.count || 0;
    if (cancelledAmount) cancelledAmount.textContent = formatMoney(stats.cancelled.amount || 0);
}

/**
 * Renderizar histórico de pagamentos
 */
function renderPaymentHistory(invoices) {
    const tbody = document.getElementById('paymentHistoryTableBody');
    const emptyState = document.getElementById('paymentEmptyState');

    if (!tbody) return;

    if (invoices.length === 0) {
        renderEmptyPaymentHistory();
        return;
    }

    // Esconder estado vazio
    if (emptyState) {
        emptyState.style.display = 'none';
    }

    tbody.innerHTML = invoices.map(invoice => `
        <tr>
            <td>
                <div class="payment-date">${formatPaymentDate(invoice.issue_date)}</div>
                ${invoice.due_date !== invoice.issue_date ?
            `<div style="font-size: 0.75rem; color: var(--text-tertiary);">Venc: ${formatPaymentDate(invoice.due_date)}</div>` :
            ''
        }
            </td>
            <td>
                <div class="payment-value">${formatMoney(invoice.value)}</div>
                ${invoice.discount > 0 ?
            `<div style="font-size: 0.75rem; color: var(--text-tertiary);">Desc: ${formatMoney(invoice.discount)}</div>` :
            ''
        }
            </td>
            <td>
                <span class="payment-status ${invoice.status}">
                    <div class="payment-status-icon"></div>
                    ${getStatusText(invoice.status)}
                </span>
            </td>
            <td>
                <div class="payment-method">
                    <svg class="payment-method-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        ${getPaymentMethodIcon(invoice.payment_method_type || invoice.payment_method || (mercadoPagoConfigured ? 'mercadopago' : null))}
                    </svg>
                    ${invoice.payment_method || invoice.payment_method_type || (mercadoPagoConfigured ? 'Mercado Pago' : 'Não definido')}
                </div>
            </td>
            <td>
                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                    ${getInvoiceReference(invoice)}
                </div>
            </td>
            <td>
                <div class="payment-actions">
                    <button class="payment-action-btn" onclick="editPayment('${invoice.id}')" title="Editar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    ${invoice.status === 'pending' ? `
                        <button class="payment-action-btn" onclick="markAsPaid('${invoice.id}')" title="Marcar como Pago">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                        </button>
                    ` : ''}
                    <button class="payment-action-btn danger" onclick="deletePayment('${invoice.id}')" title="Excluir">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

/**
 * Renderizar estado vazio
 */
function renderEmptyPaymentHistory() {
    const tbody = document.getElementById('paymentHistoryTableBody');
    const emptyState = document.getElementById('paymentEmptyState');

    if (tbody) {
        tbody.innerHTML = '';
    }

    if (emptyState) {
        emptyState.style.display = 'block';
    }

    // Zerar estatísticas
    updatePaymentStats({
        total: { count: 0, amount: 0 },
        paid: { count: 0, amount: 0 },
        pending: { count: 0, amount: 0 },
        cancelled: { count: 0, amount: 0 }
    });
}

/**
 * Obter texto do status
 */
function getStatusText(status) {
    const statusMap = {
        'paid': 'Pago',
        'pending': 'Pendente',
        'overdue': 'Vencido',
        'cancelled': 'Cancelado'
    };
    return statusMap[status] || status;
}

/**
 * Obter ícone do método de pagamento
 */
function getPaymentMethodIcon(type) {
    switch (type) {
        case 'pix':
        case 'pix_manual':
            return '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>';
        case 'mercadopago':
        case 'asaas':
            return '<circle cx="12" cy="12" r="10"></circle><polyline points="16 12 12 8 8 12"></polyline><line x1="12" y1="16" x2="12" y2="8"></line>';
        default:
            return '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>';
    }
}

/**
 * Formatar data para pagamentos
 */
function formatPaymentDate(dateString) {
    if (!dateString) return '-';
    
    try {
        // Se a data já está no formato brasileiro (dd/mm/yyyy), retornar como está
        if (dateString.includes('/')) {
            return dateString;
        }
        
        // Se está no formato ISO (yyyy-mm-dd), converter para brasileiro
        if (dateString.includes('-')) {
            const [year, month, day] = dateString.split(' ')[0].split('-');
            return `${day}/${month}/${year}`;
        }
        
        // Fallback para new Date
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return dateString; // Retorna a string original se não conseguir converter
        }
        
        return date.toLocaleDateString('pt-BR');
    } catch (error) {
        return dateString; // Retorna a string original em caso de erro
    }
}

/**
 * Gerar referência da fatura com mês por extenso
 */
function getInvoiceReference(invoice) {
    // Se já tem transaction_id ou external_id, usar
    if (invoice.transaction_id || invoice.external_id) {
        return invoice.transaction_id || invoice.external_id;
    }
    
    // Gerar referência com mês por extenso
    if (invoice.due_date || invoice.issue_date) {
        try {
            const dateStr = invoice.due_date || invoice.issue_date;
            let date;
            
            // Converter data para objeto Date
            if (dateStr.includes('/')) {
                const [day, month, year] = dateStr.split('/');
                date = new Date(year, month - 1, day);
            } else if (dateStr.includes('-')) {
                date = new Date(dateStr);
            } else {
                return '-';
            }
            
            // Meses por extenso
            const meses = [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];
            
            const mes = meses[date.getMonth()];
            const ano = date.getFullYear();
            
            return `${mes}/${ano}`;
        } catch (error) {
            return '-';
        }
    }
    
    return '-';
}

/**
 * Verificar status do Mercado Pago e atualizar botão
 */
async function checkMercadoPagoStatus() {
    try {
        // Verificar se o usuário é admin antes de fazer a requisição
        // Resellers não têm acesso à API de métodos de pagamento
        const userStr = localStorage.getItem('user');
        if (userStr) {
            const user = JSON.parse(userStr);
            const isAdmin = user.role === 'admin' || user.is_admin === true;
            
            if (!isAdmin) {
                // Se não for admin, apenas definir como não configurado
                mercadoPagoConfigured = false;
                updateAddPaymentButton();
                return;
            }
        }
        
        const response = await fetch('/api-payment-methods.php?method=mercadopago', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        // Se retornar 403, significa que não é admin ou não tem permissão
        if (response.status === 403) {
            mercadoPagoConfigured = false;
            updateAddPaymentButton();
            return;
        }
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        mercadoPagoConfigured = result.success && result.config && result.config.enabled;
        updateAddPaymentButton();
    } catch (error) {
        // Tratar erro de forma silenciosa - apenas definir como não configurado
        console.log('Não foi possível verificar status do Mercado Pago:', error.message);
        mercadoPagoConfigured = false;
        updateAddPaymentButton();
    }
}

/**
 * Atualizar botão de adicionar pagamento
 */
function updateAddPaymentButton() {
    const addButton = document.querySelector('[onclick="addPayment()"]');
    
    if (!addButton) return;
    
    if (mercadoPagoConfigured) {
        // Mercado Pago configurado - botão verde com check
        addButton.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            Pagamento Configurado
        `;
        addButton.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        addButton.style.cursor = 'default';
        addButton.title = 'Mercado Pago está configurado e ativo';
    } else {
        // Mercado Pago não configurado - botão normal
        addButton.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Adicionar Pagamento
        `;
        addButton.style.background = '';
        addButton.style.cursor = 'pointer';
        addButton.title = 'Configurar método de pagamento';
    }
}

/**
 * Adicionar novo pagamento
 */
async function addPayment() {
    if (mercadoPagoConfigured) {
        // Mercado Pago está configurado - apenas mostrar mensagem
        showNotification('✅ Mercado Pago configurado e ativo', 'success');
        return;
    }
    
    // Mercado Pago não está configurado - perguntar se quer configurar
    if (confirm('Mercado Pago não está configurado.\n\nDeseja ir para a página de configuração?')) {
        window.location.href = '/payment-methods';
    }
}

/**
 * Editar pagamento
 */
function editPayment(paymentId) {
    // TODO: Implementar modal para editar pagamento
    showNotification('Funcionalidade em desenvolvimento', 'info');
}

/**
 * Marcar como pago
 */
async function markAsPaid(paymentId) {
    showConfirmModal({
        title: 'Marcar como Pago',
        subtitle: 'Confirmar Pagamento',
        message: 'Marcar este pagamento como pago?',
        type: 'success',
        confirmText: 'Marcar como Pago',
        cancelText: 'Cancelar',
        details: [
            { label: 'Status Atual', value: 'Pendente' },
            { label: 'Novo Status', value: 'Pago', highlight: true },
            { label: 'Data do Pagamento', value: new Date().toLocaleDateString('pt-BR') }
        ],
        onConfirm: async () => {
            await processMarkAsPaid(paymentId);
        }
    });
}

/**
 * Processar marcação como pago
 */
async function processMarkAsPaid(paymentId) {

    try {
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch(`/api-payment-history.php?id=${paymentId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    status: 'paid',
                    payment_date: new Date().toISOString().split('T')[0]
                })
            });

            const data = await response.json();

            if (data.success) {
                let message = 'Pagamento marcado como pago!';
                if (data.client_renewed) {
                    message += '\n\n✅ Cliente renovado automaticamente por +30 dias!';
                }
                showNotification(message, 'success');

                // Recarregar histórico
                const clientId = getCurrentClientId();
                if (clientId) {
                    await loadPaymentHistory(clientId);
                }

                // Se o cliente foi renovado, recarregar a tabela de clientes também
                if (data.client_renewed) {
                    await loadClients();
                }
            } else {
                throw new Error(data.error || 'Erro ao atualizar pagamento');
            }
        }, {
            type: 'global',
            id: 'mark-paid'
        });

    } catch (error) {
        showNotification('Erro ao marcar como pago: ' + error.message, 'error');
    }
}

/**
 * Deletar pagamento
 */
async function deletePayment(paymentId) {
    showConfirmModal({
        title: 'Excluir Pagamento',
        subtitle: 'Ação Irreversível',
        message: 'Tem certeza que deseja excluir este pagamento?',
        type: 'danger',
        confirmText: 'Excluir Pagamento',
        cancelText: 'Cancelar',
        details: [
            { label: 'Ação', value: 'Exclusão de pagamento' },
            { label: 'Atenção', value: 'Esta ação não pode ser desfeita', highlight: true }
        ],
        onConfirm: async () => {
            await processDeletePayment(paymentId);
        }
    });
}

/**
 * Processar exclusão de pagamento
 */
async function processDeletePayment(paymentId) {

    try {
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch(`/api-invoices.php/${paymentId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Pagamento excluído com sucesso!', 'success');
                // Recarregar histórico
                const clientId = getCurrentClientId();
                if (clientId) {
                    await loadPaymentHistory(clientId);
                }
            } else {
                throw new Error(data.error || 'Erro ao excluir pagamento');
            }
        }, {
            type: 'global',
            id: 'delete-payment'
        });

    } catch (error) {
        showNotification('Erro ao excluir pagamento: ' + error.message, 'error');
    }
}

/**
 * Limpar filtros de pagamento
 */
function clearPaymentFilters() {
    const statusFilter = document.getElementById('paymentStatusFilter');
    const monthFilter = document.getElementById('paymentMonthFilter');

    if (statusFilter) statusFilter.value = '';
    if (monthFilter) monthFilter.value = '';

    // TODO: Recarregar dados com filtros limpos
}

/**
 * Exportar histórico de pagamentos
 */
function exportPaymentHistory() {
    // TODO: Implementar exportação
    showNotification('Funcionalidade em desenvolvimento', 'info');
}

// Variável global para armazenar o ID do cliente atual
let currentPaymentClientId = null;

/**
 * Obter ID do cliente atual
 */
function getCurrentClientId() {
    return currentPaymentClientId;
}

/**
 * Definir ID do cliente atual
 */
function setCurrentClientId(clientId) {
    currentPaymentClientId = clientId;
}

// Variável global para armazenar a ação de confirmação
let confirmAction = null;

/**
 * Mostrar modal de confirmação personalizado
 */
function showConfirmModal(options) {
    const {
        title = 'Confirmar Ação',
        subtitle = 'Deseja continuar?',
        message = 'Tem certeza que deseja realizar esta ação?',
        details = null,
        type = 'info', // success, warning, danger, info
        confirmText = 'Confirmar',
        cancelText = 'Cancelar',
        onConfirm = null
    } = options;

    // Elementos do modal
    const modal = document.getElementById('confirmModal');
    const titleElement = document.getElementById('confirmTitle');
    const subtitleElement = document.getElementById('confirmSubtitle');
    const messageElement = document.getElementById('confirmMessage');
    const detailsElement = document.getElementById('confirmDetails');
    const iconElement = document.getElementById('confirmIcon');
    const confirmBtn = document.getElementById('confirmActionBtn');

    if (!modal) return;

    // Configurar conteúdo
    if (titleElement) titleElement.textContent = title;
    if (subtitleElement) subtitleElement.textContent = subtitle;
    if (messageElement) messageElement.textContent = message;
    if (confirmBtn) confirmBtn.textContent = confirmText;

    // Configurar ícone baseado no tipo
    if (iconElement) {
        iconElement.className = `confirm-icon ${type}`;

        let iconSvg = '';
        switch (type) {
            case 'success':
                iconSvg = '<path d="M9 12l2 2 4-4"></path><circle cx="12" cy="12" r="10"></circle>';
                break;
            case 'warning':
                iconSvg = '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>';
                break;
            case 'danger':
                iconSvg = '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>';
                break;
            default: // info
                iconSvg = '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>';
        }

        iconElement.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${iconSvg}</svg>`;
    }

    // Configurar detalhes se fornecidos
    if (detailsElement) {
        if (details && details.length > 0) {
            detailsElement.style.display = 'block';
            detailsElement.innerHTML = details.map(detail => `
                <div class="confirm-detail-item">
                    <span class="confirm-detail-label">${detail.label}</span>
                    <span class="confirm-detail-value ${detail.highlight ? 'confirm-detail-highlight' : ''}">${detail.value}</span>
                </div>
            `).join('');
        } else {
            detailsElement.style.display = 'none';
        }
    }

    // Armazenar ação de confirmação
    confirmAction = onConfirm;

    // Verificar se há outro modal aberto e adicionar classe de sobreposição
    const paymentHistoryModal = document.getElementById('paymentHistoryModal');
    if (paymentHistoryModal && paymentHistoryModal.classList.contains('active')) {
        modal.classList.add('modal-overlay');
    }

    // Mostrar modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Fechar modal de confirmação
 */
function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (!modal) return;

    modal.classList.remove('active', 'modal-overlay');

    // Verificar se ainda há outros modais abertos
    const paymentHistoryModal = document.getElementById('paymentHistoryModal');
    if (!paymentHistoryModal || !paymentHistoryModal.classList.contains('active')) {
        document.body.style.overflow = '';
    }

    // Limpar ação de confirmação
    confirmAction = null;
}

/**
 * Executar ação de confirmação
 */
function executeConfirmAction() {
    if (confirmAction && typeof confirmAction === 'function') {
        confirmAction();
    }
    closeConfirmModal();
}

// ===== FUNÇÕES WHATSAPP =====

let whatsappTemplates = [];
let currentWhatsAppClient = null;

// Tornar funções globais imediatamente
window.openWhatsAppModal = async function(clientId, clientName, clientPhone) {
    // Buscar dados completos do cliente
    const fullClient = clients.find(c => c.id == clientId);
    currentWhatsAppClient = fullClient ? {
        id: clientId,
        name: clientName,
        phone: clientPhone,
        plan: fullClient.plan,
        value: fullClient.value,
        renewal_date: fullClient.renewal_date
    } : { id: clientId, name: clientName, phone: clientPhone };
    
    // Preencher informações do cliente
    const clientNameEl = document.getElementById('whatsappClientName');
    const clientPhoneEl = document.getElementById('whatsappClientPhone');
    
    if (clientNameEl) clientNameEl.textContent = clientName;
    if (clientPhoneEl) clientPhoneEl.textContent = clientPhone;
    
    // Carregar templates se ainda não foram carregados
    if (whatsappTemplates.length === 0) {
        await loadWhatsAppTemplates();
    }
    
    // Carregar templates no select
    const templateSelect = document.getElementById('whatsappTemplateSelect');
    if (templateSelect) {
        templateSelect.innerHTML = '<option value="">Escolha um template...</option>';
        
        whatsappTemplates.forEach(template => {
            const option = document.createElement('option');
            option.value = template.id;
            option.textContent = `${template.title} (${template.type})`;
            templateSelect.appendChild(option);
        });
    }
    
    // Resetar formulário
    const customMessage = document.getElementById('customMessage');
    if (customMessage) customMessage.value = '';
    
    const templatePreview = document.getElementById('templatePreview');
    if (templatePreview) templatePreview.style.display = 'none';
    
    switchWhatsAppTab('template');
    
    // Mostrar modal
    const modal = document.getElementById('whatsappModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
    }
};

window.closeWhatsAppModal = function() {
    const modal = document.getElementById('whatsappModal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentWhatsAppClient = null;
};

window.switchWhatsAppTab = function(tab) {
    // Atualizar botões das abas
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.querySelector(`[onclick="switchWhatsAppTab('${tab}')"]`);
    if (activeBtn) activeBtn.classList.add('active');
    
    // Mostrar/ocultar conteúdo das abas
    const templateTab = document.getElementById('templateTab');
    const customTab = document.getElementById('customTab');
    
    if (templateTab) templateTab.style.display = tab === 'template' ? 'block' : 'none';
    if (customTab) customTab.style.display = tab === 'custom' ? 'block' : 'none';
    
    // Ocultar prévia se mudou para custom
    if (tab === 'custom') {
        const templatePreview = document.getElementById('templatePreview');
        if (templatePreview) templatePreview.style.display = 'none';
    }
};

window.sendWhatsAppMessage = async function() {
    if (!currentWhatsAppClient) {
        showNotification('Erro: Cliente não encontrado', 'error');
        return;
    }
    
    const activeTab = document.querySelector('.tab-btn.active').textContent;
    let message = '';
    let templateId = null;
    
    if (activeTab === 'Usar Template') {
        const templateSelect = document.getElementById('whatsappTemplateSelect');
        templateId = templateSelect.value;
        
        if (!templateId) {
            showNotification('Selecione um template', 'error');
            return;
        }
        
        const template = whatsappTemplates.find(t => t.id === templateId);
        if (template) {
            message = template.message;
            
            // Substituir variáveis com dados do cliente
            const clientData = {
                'cliente_nome': currentWhatsAppClient.name,
                'cliente_telefone': currentWhatsAppClient.phone,
                'cliente_plano': currentWhatsAppClient.plan || 'Personalizado',
                'cliente_valor': currentWhatsAppClient.value ? formatMoney(currentWhatsAppClient.value) : 'N/A',
                'cliente_vencimento': currentWhatsAppClient.renewal_date ? formatDate(currentWhatsAppClient.renewal_date) : 'N/A',
                'empresa_nome': 'UltraGestor'
            };
            
            Object.keys(clientData).forEach(variable => {
                const regex = new RegExp(`\\{\\{${variable}\\}\\}`, 'g');
                message = message.replace(regex, clientData[variable]);
            });
        }
    } else {
        const customMessage = document.getElementById('customMessage');
        message = customMessage.value.trim();
        
        if (!message) {
            showNotification('Digite uma mensagem personalizada', 'error');
            return;
        }
    }
    
    try {
        await window.LoadingManager.withLoading(async () => {
            // Primeiro verificar status do WhatsApp
            const statusResponse = await fetch('/api-whatsapp-check.php');
            const statusData = await statusResponse.json();
            
            if (!statusData.success) {
                throw new Error('Erro ao verificar status do WhatsApp');
            }
            
            if (!statusData.status.has_session) {
                throw new Error('WhatsApp não está conectado. Conecte primeiro na aba WhatsApp.');
            }
            
            if (!statusData.status.has_settings) {
                throw new Error('Configurações do WhatsApp não encontradas.');
            }
            
            // Agora enviar a mensagem
            const response = await fetch('/api-whatsapp-send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: currentWhatsAppClient.phone,
                    message: message,
                    template_id: templateId,
                    client_id: currentWhatsAppClient.id
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Erro ao enviar mensagem');
            }
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Mensagem enviada com sucesso!', 'success');
                closeWhatsAppModal();
            } else {
                throw new Error(data.message || 'Erro ao enviar mensagem');
            }
        }, {
            type: 'global',
            id: 'send-whatsapp'
        });
    } catch (error) {
        showNotification('Erro ao enviar mensagem: ' + error.message, 'error');
        }
};

/**
 * Carregar templates do WhatsApp
 */
async function loadWhatsAppTemplates() {
    try {
        const response = await fetch('/api-whatsapp-templates.php');
        const data = await response.json();
        
        if (data.success) {
            whatsappTemplates = data.templates.filter(template => template.is_active == 1);
        } else {
        }
    } catch (error) {
    }
}

/**
 * Selecionar template
 */
function selectWhatsAppTemplate() {
    const templateId = document.getElementById('whatsappTemplateSelect').value;
    
    if (!templateId) {
        document.getElementById('templatePreview').style.display = 'none';
        return;
    }
    
    const template = whatsappTemplates.find(t => t.id === templateId);
    if (template) {
        // Processar mensagem com dados do cliente
        let message = template.message;
        
        // Substituir variáveis com dados do cliente
        const clientData = {
            'cliente_nome': currentWhatsAppClient.name,
            'cliente_telefone': currentWhatsAppClient.phone,
            'cliente_plano': currentWhatsAppClient.plan || 'Personalizado',
            'cliente_valor': currentWhatsAppClient.value ? formatMoney(currentWhatsAppClient.value) : 'N/A',
            'cliente_vencimento': currentWhatsAppClient.renewal_date ? formatDate(currentWhatsAppClient.renewal_date) : 'N/A',
            'empresa_nome': 'UltraGestor'
        };
        
        Object.keys(clientData).forEach(variable => {
            const regex = new RegExp(`\\{\\{${variable}\\}\\}`, 'g');
            message = message.replace(regex, clientData[variable]);
        });
        
        // Destacar texto em negrito
        message = message.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
        
        // Substituir quebras de linha
        message = message.replace(/\n/g, '<br>');
        
        // Mostrar prévia
        document.getElementById('messagePreview').innerHTML = message;
        document.getElementById('templatePreview').style.display = 'block';
    }
}

// Adicionar event listener para seleção de template
document.addEventListener('DOMContentLoaded', function() {
    const templateSelect = document.getElementById('whatsappTemplateSelect');
    if (templateSelect) {
        templateSelect.addEventListener('change', selectWhatsAppTemplate);
    }
});

/**
 * Verificar se há servidor Sigma configurado e mostrar/ocultar botão
 */
async function checkSigmaServerAndShowButton() {
    try {
        const response = await fetch('/api-sigma-sync.php?action=check-sigma-server', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        const syncBtn = document.getElementById('syncSigmaBtn');
        if (syncBtn) {
            if (result.has_sigma_server) {
                syncBtn.style.display = 'flex'; // Mostrar botão
            } else {
                syncBtn.style.display = 'none'; // Ocultar botão
            }
        }
    } catch (error) {
        // Em caso de erro, ocultar o botão
        const syncBtn = document.getElementById('syncSigmaBtn');
        if (syncBtn) {
            syncBtn.style.display = 'none';
        }
    }
}

/**
 * 
Sincronizar datas de todos os clientes do Sigma
 */
async function syncAllSigmaDates() {
    const btn = document.getElementById('syncSigmaBtn');
    const originalText = btn.innerHTML;
    
    try {
        // Desabilitar botão e mostrar loading
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            Sincronizando...
        `;
        
        const response = await fetch('/api-sigma-sync.php?action=sync-all-dates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            const stats = result.results;
            let message = `Sincronização concluída!\n\n`;
            message += `✅ ${stats.synced} cliente(s) atualizado(s)\n`;
            message += `ℹ️ ${stats.unchanged} cliente(s) já sincronizado(s)\n`;
            
            if (stats.errors > 0) {
                message += `❌ ${stats.errors} erro(s)\n`;
            }
            
            alert(message);
            
            // Recarregar lista de clientes se houve atualizações
            if (stats.synced > 0) {
                loadClients();
            }
        } else {
            alert('Erro na sincronização: ' + result.message);
        }
        
    } catch (error) {
        alert('Erro ao sincronizar com Sigma: ' + error.message);
    } finally {
        // Restaurar botão
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

/**
 * Sincronizar data de um cliente específico do Sigma
 */
async function syncClientSigmaDate(clientId) {
    try {
        const response = await fetch('/api-sigma-sync.php?action=sync-date', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({ client_id: clientId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (result.date_changed) {
                alert(`Data sincronizada!\n\nData antiga: ${result.old_date}\nData nova: ${result.new_date}`);
                loadClients(); // Recarregar lista
            } else {
                alert('Data já está sincronizada com o Sigma');
            }
        } else {
            alert('Erro na sincronização: ' + result.message);
        }
        
    } catch (error) {
        alert('Erro ao sincronizar com Sigma: ' + error.message);
    }
}
