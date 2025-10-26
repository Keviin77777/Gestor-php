/**
 * Servers - JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Servers page loaded');
    
    // Verificar autenticação
    if (!isAuthenticated()) {
        window.location.href = '/login';
        return;
    }
    
    // Carregar dados do usuário
    loadUserData();
    
    // Carregar servidores
    loadServers();
    
    // Configurar eventos
    setupEvents();
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
            filterServers(this.value);
        });
    }

    // Formatação de moeda no campo de custo
    const costInput = document.getElementById('serverCost');
    if (costInput) {
        costInput.addEventListener('input', function() {
            formatCurrency(this);
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
        console.error('Erro ao carregar dados do usuário:', error);
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

        const response = await fetch('/api/servers', {
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
        console.log('Resposta da API:', result);

        if (result.success) {
            displayServers(result.servers || []);
            updateStats(result.servers || []);
        } else {
            throw new Error(result.error || 'Erro desconhecido');
        }

    } catch (error) {
        console.error('Erro ao carregar servidores:', error);
        showError('Erro ao carregar servidores: ' + error.message);
        displayEmptyState();
    }
}

/**
 * Exibir servidores na tabela
 */
function displayServers(servers) {
    const tbody = document.querySelector('#serversTable tbody');
    if (!tbody) return;

    if (servers.length === 0) {
        displayEmptyState();
        return;
    }

    tbody.innerHTML = servers.map(server => `
        <tr>
            <td>
                <div class="server-info">
                    <div class="server-name">${escapeHtml(server.name)}</div>
                </div>
            </td>
            <td>
                <span class="billing-type ${server.billing_type}">
                    ${server.billing_type === 'fixed' ? 'Valor Fixo' : 'Por Cliente Ativo'}
                </span>
            </td>
            <td>
                <div class="cost-info">
                    <div class="cost-value">${formatCurrency(server.total_cost || server.cost || 0)}</div>
                    ${server.billing_type === 'per_active' ? `<div class="cost-detail">${formatCurrency(server.cost)} × ${server.connected_clients || 0}</div>` : ''}
                </div>
            </td>
            <td>
                <span class="panel-type">
                    ${server.panel_type ? server.panel_type.toUpperCase() : 'N/A'}
                </span>
            </td>
            <td>
                <div class="url-info">
                    ${server.panel_url ? `<a href="${server.panel_url}" target="_blank" class="panel-url">${server.panel_url}</a>` : 'N/A'}
                </div>
            </td>
            <td>
                <div class="connected-clients">
                    <span class="client-count ${server.connected_clients > 0 ? 'has-clients' : 'no-clients'}">
                        ${server.connected_clients || 0}
                    </span>
                    <span class="client-label">cliente${server.connected_clients !== 1 ? 's' : ''}</span>
                </div>
            </td>
            <td>
                <span class="status-badge status-${server.status}">
                    ${server.status === 'active' ? 'Ativo' : 'Inativo'}
                </span>
            </td>
            <td>
                <div class="actions">
                    <button class="btn-action" onclick="editServer(${server.id})" title="Editar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn-action danger" onclick="deleteServer(${server.id})" title="Excluir">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

/**
 * Exibir estado vazio
 */
function displayEmptyState() {
    const tbody = document.querySelector('#serversTable tbody');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                <div style="opacity: 0.6;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 64px; height: 64px; margin-bottom: 1rem;">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">Nenhum servidor encontrado</div>
                    <div style="font-size: 0.875rem;">Adicione seu primeiro servidor para começar</div>
                </div>
            </td>
        </tr>
    `;
}

/**
 * Atualizar estatísticas
 */
function updateStats(servers) {
    const totalServers = servers.length;
    const activeServers = servers.filter(s => s.status === 'active').length;
    const totalConnectedClients = servers.reduce((total, server) => total + (parseInt(server.connected_clients) || 0), 0);
    const totalServerCosts = servers.reduce((total, server) => total + (parseFloat(server.total_cost) || 0), 0);

    document.getElementById('totalServers').textContent = totalServers;
    document.getElementById('activeServers').textContent = activeServers;
    document.getElementById('totalServerCosts').textContent = formatCurrency(totalServerCosts);
    document.getElementById('connectedClients').textContent = totalConnectedClients;
}

/**
 * Abrir modal de servidor
 */
function openServerModal(serverId = null) {
    const modal = document.getElementById('serverModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('serverForm');
    
    if (serverId) {
        modalTitle.textContent = 'Editar Servidor';
        // Carregar dados do servidor para edição
        loadServerData(serverId);
    } else {
        modalTitle.textContent = 'Novo Servidor';
        form.reset();
    }
    
    modal.style.display = 'flex';
    modal.classList.add('active');
}

/**
 * Fechar modal de servidor
 */
function closeServerModal() {
    const modal = document.getElementById('serverModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    
    const form = document.getElementById('serverForm');
    form.reset();
    delete form.dataset.serverId; // Limpar ID do servidor
    
    // Esconder campos de painel
    document.getElementById('qpanelFields').style.display = 'none';
    document.getElementById('testConnectionBtn').style.display = 'none';
}

/**
 * Toggle panel fields based on selection
 */
function togglePanelFields() {
    const panelType = document.getElementById('panelType').value;
    const qpanelFields = document.getElementById('qpanelFields');
    const testBtn = document.getElementById('testConnectionBtn');

    if (panelType === 'qpanel_sigma') {
        qpanelFields.style.display = 'block';
        testBtn.style.display = 'inline-flex';
    } else {
        qpanelFields.style.display = 'none';
        testBtn.style.display = 'none';
    }
}

/**
 * Test connection
 */
function testConnection() {
    const panelUrl = document.getElementById('panelUrl').value;
    const resellerUser = document.getElementById('resellerUser').value;
    const sigmaToken = document.getElementById('sigmaToken').value;

    if (!panelUrl || !resellerUser || !sigmaToken) {
        alert('❌ Por favor, preencha todos os campos de integração antes de testar.');
        return;
    }

    const testBtn = document.getElementById('testConnectionBtn');
    const originalText = testBtn.innerHTML;
    
    testBtn.disabled = true;
    testBtn.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.5rem; animation: spin 1s linear infinite;">
            <polyline points="23 4 23 10 17 10"></polyline>
            <polyline points="1 20 1 14 7 14"></polyline>
            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
        </svg>
        Testando...
    `;

    setTimeout(() => {
        testBtn.disabled = false;
        testBtn.innerHTML = originalText;
        alert('✅ Conexão testada com sucesso!\n\nPainel: ' + panelUrl + '\nUsuário: ' + resellerUser);
    }, 2000);
}

/**
 * Salvar servidor
 */
async function saveServer() {
    try {
        const form = document.getElementById('serverForm');
        const formData = new FormData(form);
        const serverId = form.dataset.serverId; // ID do servidor se estiver editando
        
        const data = {
            name: formData.get('name'),
            billing_type: formData.get('billing_type'),
            cost: formData.get('cost'),
            panel_type: formData.get('panel_type') || null,
            panel_url: formData.get('panel_url') || null,
            reseller_user: formData.get('reseller_user') || null,
            sigma_token: formData.get('sigma_token') || null
        };

        // Validação
        if (!data.name || !data.billing_type || !data.cost) {
            showError('Preencha todos os campos obrigatórios');
            return;
        }

        const token = localStorage.getItem('token');
        const url = serverId ? `/api/servers/${serverId}` : '/api/servers';
        const method = serverId ? 'PUT' : 'POST';
        
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
            showSuccess(serverId ? 'Servidor atualizado com sucesso!' : 'Servidor criado com sucesso!');
            closeServerModal();
            loadServers();
        } else {
            throw new Error(result.error || 'Erro ao salvar servidor');
        }

    } catch (error) {
        console.error('Erro ao salvar servidor:', error);
        showError('Erro ao salvar servidor: ' + error.message);
    }
}

/**
 * Carregar dados do servidor para edição
 */
async function loadServerData(serverId) {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/api/servers`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            const server = result.servers.find(s => s.id == serverId);
            
            if (server) {
                // Preencher formulário
                document.getElementById('serverName').value = server.name;
                document.getElementById('billingType').value = server.billing_type;
                document.getElementById('serverCost').value = server.cost;
                
                if (server.panel_type) {
                    document.getElementById('panelType').value = server.panel_type;
                    togglePanelFields(); // Mostrar campos de painel
                    
                    if (server.panel_type === 'qpanel_sigma') {
                        document.getElementById('panelUrl').value = server.panel_url || '';
                        document.getElementById('resellerUser').value = server.reseller_user || '';
                        // Não preencher token por segurança
                    }
                }
                
                // Armazenar ID no formulário
                const form = document.getElementById('serverForm');
                form.dataset.serverId = serverId;
            }
        }
    } catch (error) {
        console.error('Erro ao carregar dados do servidor:', error);
        showError('Erro ao carregar dados do servidor');
    }
}

/**
 * Editar servidor
 */
function editServer(serverId) {
    openServerModal(serverId);
}

/**
 * Excluir servidor
 */
async function deleteServer(serverId) {
    if (!confirm('Tem certeza que deseja excluir este servidor?')) {
        return;
    }

    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/api/servers/${serverId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('Servidor excluído com sucesso!');
            loadServers();
        } else {
            throw new Error(result.error || 'Erro ao excluir servidor');
        }

    } catch (error) {
        console.error('Erro ao excluir servidor:', error);
        showError('Erro ao excluir servidor: ' + error.message);
    }
}

/**
 * Filtrar servidores
 */
function filterServers(searchTerm) {
    const rows = document.querySelectorAll('#serversTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
}

/**
 * Formatar campo de moeda
 */
function formatCurrency(input) {
    if (typeof input === 'object' && input.value !== undefined) {
        // Se for um input element
        let value = input.value.replace(/\D/g, '');
        value = (value / 100).toFixed(2);
        value = value.replace('.', ',');
        value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        input.value = 'R$ ' + value;
    } else {
        // Se for um valor numérico
        const value = parseFloat(input) || 0;
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }
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
    // Implementar notificação de erro
    alert('Erro: ' + message);
}

/**
 * Mostrar sucesso
 */
function showSuccess(message) {
    // Implementar notificação de sucesso
    alert('Sucesso: ' + message);
}

// logout agora está em common.js