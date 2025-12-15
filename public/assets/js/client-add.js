/**
 * Client Add Page - JavaScript
 */

let plansData = [];

document.addEventListener('DOMContentLoaded', async function() {
    // Verificar autenticação
    if (!isAuthenticated()) {
        window.location.href = '/login';
        return;
    }

    // Carregar dados necessários
    await loadServers();
    await loadApplications();
    await loadPlans();

    // Verificar se está em modo de edição
    const form = document.getElementById('clientForm');
    const clientId = form.dataset.clientId;
    
    if (clientId) {
        // Modo de edição - carregar dados do cliente
        await loadClientData(clientId);
    }

    // Configurar formulário
    setupForm();
});

/**
 * Carregar servidores
 */
async function loadServers() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/api-servers.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        const result = await response.json();
        
        if (result.success && result.servers) {
            const select = document.getElementById('clientServer');
            select.innerHTML = '<option value="">Selecione um servidor</option>' +
                result.servers.map(server => 
                    `<option value="${escapeHtml(server.name)}" data-server-id="${server.id}">${escapeHtml(server.name)}</option>`
                ).join('');
        }
    } catch (error) {
        showError('Erro ao carregar servidores');
    }
}

/**
 * Carregar aplicativos
 */
async function loadApplications() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/api-applications.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        const result = await response.json();
        
        if (result.success && result.applications) {
            const select = document.getElementById('clientApplication');
            select.innerHTML = '<option value="">Selecione um aplicativo</option>' +
                result.applications.map(app => 
                    `<option value="${app.id}">${escapeHtml(app.name)}</option>`
                ).join('');
        }
    } catch (error) {
        // Erro ao carregar aplicativos
    }
}

/**
 * Carregar planos
 */
async function loadPlans() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/api-plans.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        const result = await response.json();
        
        if (result.success && result.plans) {
            plansData = result.plans;
            updatePlansList();
        }
    } catch (error) {
        const select = document.getElementById('clientPlan');
        select.innerHTML = '<option value="">Erro ao carregar planos</option>';
    }
}

/**
 * Atualizar lista de planos baseado no servidor selecionado
 */
function updatePlansList() {
    const serverSelect = document.getElementById('clientServer');
    const planSelect = document.getElementById('clientPlan');
    
    if (!serverSelect || !planSelect) return;
    
    const selectedOption = serverSelect.options[serverSelect.selectedIndex];
    const selectedServerId = selectedOption ? selectedOption.getAttribute('data-server-id') : null;
    
    // Se nenhum servidor foi selecionado
    if (!selectedServerId) {
        planSelect.innerHTML = '<option value="">⚠️ Selecione um servidor primeiro</option>';
        planSelect.disabled = true;
        return;
    }
    
    // Filtrar planos pelo servidor selecionado
    const filteredPlans = plansData.filter(plan => plan.server_id == selectedServerId);
    
    // Se o servidor não tem planos
    if (filteredPlans.length === 0) {
        planSelect.innerHTML = '<option value="">⚠️ Este servidor não possui planos cadastrados</option>';
        planSelect.disabled = true;
        return;
    }
    
    // Servidor tem planos - listar normalmente
    planSelect.disabled = false;
    planSelect.innerHTML = '<option value="">Selecione um plano</option>' +
        filteredPlans.map(plan => 
            `<option value="${escapeHtml(plan.name)}" data-price="${plan.price}" data-plan-id="${plan.id}">${escapeHtml(plan.name)} - R$ ${formatPrice(plan.price)}</option>`
        ).join('');
}

/**
 * Formatar preço
 */
function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace('.', ',');
}

/**
 * Configurar formulário
 */
function setupForm() {
    const form = document.getElementById('clientForm');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveClient();
    });

    // Quando selecionar um servidor, atualizar lista de planos
    const serverSelect = document.getElementById('clientServer');
    if (serverSelect) {
        serverSelect.addEventListener('change', function() {
            updatePlansList();
        });
    }

    // Quando selecionar um plano, preencher o valor automaticamente
    const planSelect = document.getElementById('clientPlan');
    if (planSelect) {
        planSelect.addEventListener('change', function(e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            
            if (price) {
                const valueInput = document.getElementById('clientValue');
                valueInput.value = formatPrice(price);
            }
        });
    }

    // Máscara de valor monetário
    const valueInput = document.getElementById('clientValue');
    if (valueInput) {
        valueInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            // Converter para centavos
            value = (parseInt(value) || 0).toString();
            
            // Adicionar zeros à esquerda se necessário
            while (value.length < 3) {
                value = '0' + value;
            }
            
            // Separar reais e centavos
            const reais = value.slice(0, -2);
            const centavos = value.slice(-2);
            
            // Formatar com vírgula
            e.target.value = `${parseInt(reais)},${centavos}`;
        });

        // Formatar ao sair do campo
        valueInput.addEventListener('blur', function(e) {
            if (!e.target.value) {
                e.target.value = '0,00';
            }
        });

        // Valor inicial
        if (!valueInput.value) {
            valueInput.value = '0,00';
        }
    }

    // Máscara de telefone
    const phoneInput = document.getElementById('clientPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
            } else if (value.length > 0) {
                value = value.replace(/^(\d*)/, '($1');
            }
            
            e.target.value = value;
        });
    }

    // Máscara de MAC
    const macInput = document.getElementById('clientMac');
    if (macInput) {
        macInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9A-Fa-f]/g, '').toUpperCase();
            if (value.length > 12) value = value.slice(0, 12);
            
            value = value.match(/.{1,2}/g)?.join(':') || value;
            e.target.value = value;
        });
    }
}

/**
 * Gerar senha IPTV
 */
function generateIptvPassword() {
    const length = 10;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let password = '';
    
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    document.getElementById('clientIptvPassword').value = password;
}

/**
 * Salvar cliente
 */
async function saveClient() {
    try {
        const form = document.getElementById('clientForm');
        const clientId = form.dataset.clientId;
        const isEditing = clientId && clientId !== '';
        
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Salvando...</span>';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Converter valor formatado (0,00) para decimal (0.00)
        if (data.value) {
            data.value = data.value.replace(',', '.');
        }

        // Validações
        if (!data.name || !data.phone || !data.username || !data.iptv_password || 
            !data.plan || !data.renewal_date || !data.value) {
            showError('Preencha todos os campos obrigatórios');
            submitBtn.disabled = false;
            submitBtn.innerHTML = isEditing ? 'Salvar Alterações' : `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Adicionar Cliente
            `;
            return;
        }

        const token = localStorage.getItem('token');
        
        // Usar PUT para edição e POST para criação
        const url = isEditing ? `/api-clients.php?id=${clientId}` : '/api-clients.php';
        const method = isEditing ? 'PUT' : 'POST';
        
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
            showSuccess(isEditing ? 'Cliente atualizado com sucesso!' : 'Cliente adicionado com sucesso!');
            setTimeout(() => {
                window.location.href = '/clients';
            }, 1500);
        } else {
            throw new Error(result.error || (isEditing ? 'Erro ao atualizar cliente' : 'Erro ao adicionar cliente'));
        }

    } catch (error) {
        const form = document.getElementById('clientForm');
        const clientId = form.dataset.clientId;
        const isEditing = clientId && clientId !== '';
        
        showError((isEditing ? 'Erro ao atualizar cliente: ' : 'Erro ao adicionar cliente: ') + error.message);
        
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = false;
        submitBtn.innerHTML = isEditing ? 'Salvar Alterações' : `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Adicionar Cliente
        `;
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
    return String(text).replace(/[&<>"']/g, m => map[m]);
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
 * Carregar dados do cliente para edição
 */
async function loadClientData(clientId) {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/api-clients.php', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        const result = await response.json();
        
        if (result.success && result.clients) {
            const client = result.clients.find(c => c.id == clientId);
            
            if (client) {
                // Preencher os campos do formulário
                document.getElementById('clientName').value = client.name || '';
                document.getElementById('clientEmail').value = client.email || '';
                document.getElementById('clientPhone').value = client.phone || '';
                document.getElementById('clientUsername').value = client.username || '';
                document.getElementById('clientIptvPassword').value = client.iptv_password || '';
                
                // Preencher servidor primeiro
                const serverSelect = document.getElementById('clientServer');
                if (serverSelect && client.server) {
                    serverSelect.value = client.server;
                    // Atualizar lista de planos baseado no servidor selecionado
                    updatePlansList();
                }
                
                // Aguardar um momento para os planos serem carregados, então preencher o plano
                setTimeout(() => {
                    const planSelect = document.getElementById('clientPlan');
                    if (planSelect && client.plan) {
                        planSelect.value = client.plan;
                    }
                }, 100);
                
                const applicationSelect = document.getElementById('clientApplication');
                if (applicationSelect && client.application_id) {
                    applicationSelect.value = client.application_id;
                }
                
                document.getElementById('clientValue').value = client.value || '';
                document.getElementById('clientRenewalDate').value = client.renewal_date || '';
                document.getElementById('clientMac').value = client.mac || '';
                
                const notificationsSelect = document.getElementById('clientNotifications');
                if (notificationsSelect) {
                    notificationsSelect.value = client.notifications || 'sim';
                }
                
                const screensSelect = document.getElementById('clientScreens');
                if (screensSelect) {
                    screensSelect.value = client.screens || '1';
                }
                
                document.getElementById('clientNotes').value = client.notes || '';
                
                // Atualizar o botão de submit
                const form = document.getElementById('clientForm');
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.textContent = 'Salvar Alterações';
                }
            } else {
                showError('Cliente não encontrado');
                setTimeout(() => {
                    window.location.href = '/clients';
                }, 2000);
            }
        }
    } catch (error) {
        showError('Erro ao carregar dados do cliente');
    }
}
