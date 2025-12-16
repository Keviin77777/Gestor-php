/**
 * Cliente Add Page Mobile - JavaScript
 * REMOVIDO - Agora sempre usa página separada /clients/add
 * Este arquivo é mantido apenas para compatibilidade, mas não é mais usado
 */

// Função para abrir modal/página de adicionar cliente
// REMOVIDO - Agora sempre redireciona para página separada
function openClientModal() {
    // Sempre redirecionar para página separada
    window.location.href = '/clients/add';
}

// Abrir página mobile
function openClientPageMobile() {
    // Criar estrutura da página se não existir
    if (!document.querySelector('.client-add-page')) {
        createClientAddPage();
    }
    
    // Resetar formulário
    const form = document.getElementById('clientFormMobile');
    if (form) {
        form.reset();
        delete form.dataset.editing;
        
        // Definir data de vencimento padrão (30 dias)
        const nextMonth = new Date();
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        const renewalDateInput = form.querySelector('[name="renewal_date"]');
        if (renewalDateInput) {
            renewalDateInput.value = nextMonth.toISOString().split('T')[0];
        }
        
        // Definir número de telas padrão
        const screensInput = form.querySelector('[name="screens"]');
        if (screensInput) {
            screensInput.value = '1';
        }
    }
    
    // Preencher dropdowns
    populatePlansDropdownMobile();
    populateServersDropdownMobile();
    populateApplicationsDropdownMobile();
    
    // Configurar validação de senha após preencher dropdowns
    setTimeout(() => {
        if (typeof setupPasswordValidation === 'function') {
            setupPasswordValidation();
        }
    }, 100);
    
    // Atualizar título
    const titleElement = document.querySelector('.client-add-title-section h1');
    if (titleElement) {
        titleElement.textContent = 'Novo Cliente';
    }
    
    const subtitleElement = document.querySelector('.client-add-subtitle');
    if (subtitleElement) {
        subtitleElement.textContent = 'Preencha os detalhes do novo cliente';
    }
    
    // Ativar modo página
    document.body.classList.add('client-add-mode');
    document.body.style.overflow = 'hidden';
}

// Abrir modal desktop (função original)
// REMOVIDO - Agora sempre usa página separada
function openClientModalDesktop() {
    // Sempre redirecionar para página separada
    window.location.href = '/clients/add';
}

// Fechar página mobile
function closeClientPageMobile() {
    document.body.classList.remove('client-add-mode');
    document.body.style.overflow = '';
}

// Criar estrutura da página mobile
function createClientAddPage() {
    const pageHTML = `
        <div class="client-add-page">
            <div class="client-add-header">
                <button class="client-add-back-btn" onclick="closeClientPageMobile()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </button>
                <div class="client-add-title-section">
                    <h1>Novo Cliente</h1>
                    <p class="client-add-subtitle">Preencha os detalhes do novo cliente</p>
                </div>
            </div>
            
            <div class="client-add-body">
                <form id="clientFormMobile" class="client-add-form">
                    <div class="modern-form-grid">
                        <div class="modern-form-group">
                            <label for="clientNameMobile">Nome Sistema *</label>
                            <input type="text" id="clientNameMobile" name="name" placeholder="Nome" required>
                        </div>

                        <div class="modern-form-group">
                            <label for="clientUsernameMobile">Usuário IPTV</label>
                            <input type="text" id="clientUsernameMobile" name="username" placeholder="Opcional">
                        </div>

                        <div class="modern-form-group">
                            <label for="clientIptvPasswordMobile">Senha IPTV</label>
                            <div class="input-with-actions">
                                <input type="text" id="clientIptvPasswordMobile" name="iptv_password" placeholder="Opcional">
                                <button type="button" class="input-action-btn" onclick="generateIptvPasswordMobile()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="modern-form-group">
                            <label for="clientPhoneMobile">WhatsApp</label>
                            <input type="tel" id="clientPhoneMobile" name="phone" placeholder="WhatsApp">
                        </div>

                        <div class="modern-form-group">
                            <label for="clientRenewalDateMobile">Vencimento *</label>
                            <input type="date" id="clientRenewalDateMobile" name="renewal_date" required>
                        </div>

                        <div class="modern-form-group">
                            <label for="clientServerMobile">Servidor</label>
                            <select id="clientServerMobile" name="server">
                                <option value="">Carregando servidores...</option>
                            </select>
                        </div>

                        <div class="modern-form-group">
                            <label for="clientApplicationMobile">Aplicativo</label>
                            <select id="clientApplicationMobile" name="application_id">
                                <option value="">Carregando aplicativos...</option>
                            </select>
                        </div>

                        <div class="modern-form-group">
                            <label for="clientMacMobile">MAC</label>
                            <input type="text" id="clientMacMobile" name="mac" placeholder="MAC">
                        </div>

                        <div class="modern-form-group">
                            <label for="clientNotificationsMobile">Notificações</label>
                            <select id="clientNotificationsMobile" name="notifications">
                                <option value="sim">Sim</option>
                                <option value="nao">Não</option>
                            </select>
                        </div>

                        <div class="modern-form-group">
                            <label for="clientPlanMobile">Plano</label>
                            <select id="clientPlanMobile" name="plan" onchange="handlePlanChangeMobile()">
                                <option value="">Selecionar plano</option>
                            </select>
                        </div>

                        <div class="modern-form-group">
                            <label for="clientValueMobile">Valor Mensal *</label>
                            <input type="number" id="clientValueMobile" name="value" step="0.01" min="0" placeholder="0,00" required>
                        </div>

                        <div class="modern-form-group">
                            <label for="clientEmailMobile">Email</label>
                            <input type="email" id="clientEmailMobile" name="email" placeholder="email@exemplo.com">
                        </div>

                        <div class="modern-form-group">
                            <label for="clientScreensMobile">Número de Telas *</label>
                            <input type="number" id="clientScreensMobile" name="screens" min="1" max="10" value="1" required>
                        </div>

                        <div class="modern-form-group">
                            <label for="clientNotesMobile">Notas</label>
                            <textarea id="clientNotesMobile" name="notes" rows="4" placeholder="Notas"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="client-add-footer">
                <button type="button" class="btn-modern btn-secondary" onclick="closeClientPageMobile()">Cancelar</button>
                <button type="submit" form="clientFormMobile" class="btn-modern btn-primary" id="submitBtnMobile">Adicionar</button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', pageHTML);
    
    // Adicionar event listener ao formulário
    const form = document.getElementById('clientFormMobile');
    if (form) {
        form.addEventListener('submit', saveClientMobile);
    }
}

// Preencher dropdowns mobile
async function populatePlansDropdownMobile() {
    const planSelect = document.getElementById('clientPlanMobile');
    if (!planSelect) return;
    
    planSelect.innerHTML = '<option value="">Selecionar plano</option>';
    
    if (availablePlans && availablePlans.length > 0) {
        availablePlans.forEach(plan => {
            if (plan.status === 'active') {
                const option = document.createElement('option');
                option.value = plan.name;
                option.textContent = `${plan.name} - R$ ${plan.price.toFixed(2)}`;
                option.dataset.price = plan.price;
                planSelect.appendChild(option);
            }
        });
    }
    
    const customOption = document.createElement('option');
    customOption.value = 'Personalizado';
    customOption.textContent = 'Personalizado';
    planSelect.appendChild(customOption);
}

async function populateServersDropdownMobile() {
    const serverSelect = document.getElementById('clientServerMobile');
    if (!serverSelect) return;
    
    serverSelect.innerHTML = '<option value="">Carregando servidores...</option>';
    
    if (!availableServers || availableServers.length === 0) {
        await loadPlansAndServers();
    }
    
    serverSelect.innerHTML = '<option value="">Selecionar servidor</option>';
    
    if (availableServers && availableServers.length > 0) {
        availableServers.forEach(server => {
            if (server.status === 'active') {
                const option = document.createElement('option');
                option.value = server.name;
                option.textContent = server.name;
                serverSelect.appendChild(option);
            }
        });
    }
}

async function populateApplicationsDropdownMobile() {
    const applicationSelect = document.getElementById('clientApplicationMobile');
    if (!applicationSelect) return;
    
    applicationSelect.innerHTML = '<option value="">Carregando aplicativos...</option>';
    
    if (!availableApplications || availableApplications.length === 0) {
        await loadApplications();
    }
    
    applicationSelect.innerHTML = '<option value="">Selecionar aplicativo</option>';
    
    if (availableApplications && availableApplications.length > 0) {
        availableApplications.forEach(application => {
            const option = document.createElement('option');
            option.value = application.id;
            option.textContent = application.name;
            applicationSelect.appendChild(option);
        });
    }
}

// Manipular mudança de plano mobile
function handlePlanChangeMobile() {
    const planSelect = document.getElementById('clientPlanMobile');
    const valueInput = document.getElementById('clientValueMobile');
    
    if (!planSelect || !valueInput) return;
    
    const selectedOption = planSelect.selectedOptions[0];
    
    if (selectedOption && selectedOption.dataset.price) {
        valueInput.value = parseFloat(selectedOption.dataset.price).toFixed(2);
    } else if (planSelect.value === 'Personalizado') {
        valueInput.value = '';
        valueInput.focus();
    }
}

// Gerar senha IPTV mobile
function generateIptvPasswordMobile() {
    const passwordInput = document.getElementById('clientIptvPasswordMobile');
    if (passwordInput) {
        const password = Math.random().toString(36).substring(2, 10);
        passwordInput.value = password;
    }
}

// Salvar cliente mobile
async function saveClientMobile(e) {
    e.preventDefault();
    
    const form = document.getElementById('clientFormMobile');
    const formData = new FormData(form);
    const clientId = form.dataset.editing;
    
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        username: formData.get('username'),
        iptv_password: formData.get('iptv_password'),
        server: formData.get('server'),
        application_id: formData.get('application_id'),
        mac: formData.get('mac'),
        notifications: formData.get('notifications'),
        plan: formData.get('plan'),
        value: parseFloat(formData.get('value')),
        renewal_date: formData.get('renewal_date'),
        screens: parseInt(formData.get('screens')),
        notes: formData.get('notes')
    };
    
    // Validação
    if (!data.name || !data.value || !data.renewal_date) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }
    
    // Validar senha IPTV se servidor tiver Sigma configurado
    if (typeof validatePasswordBeforeSaveMobile === 'function') {
        if (!validatePasswordBeforeSaveMobile()) {
            return;
        }
    }
    
    try {
        const token = localStorage.getItem('token');
        const url = clientId ? `/api-clients.php?id=${clientId}` : '/api-clients.php';
        const method = clientId ? 'PUT' : 'POST';
        
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
            alert(clientId ? 'Cliente atualizado com sucesso!' : 'Cliente adicionado com sucesso!');
            closeClientPageMobile();
            loadClients();
        } else {
            throw new Error(result.error || 'Erro ao salvar cliente');
        }
    } catch (error) {
        alert('Erro ao salvar cliente: ' + error.message);
    }
}

// Detectar mudança de tamanho da tela
window.addEventListener('resize', function() {
    if (window.innerWidth > 768 && document.body.classList.contains('client-add-mode')) {
        closeClientPageMobile();
    }
});
