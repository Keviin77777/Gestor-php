/**
 * Plan Add Page Mobile - JavaScript
 * Gerencia a transição entre modal (desktop) e página completa (mobile)
 */

// Sobrescrever função global para abrir modal/página de plano
window.openPlanModalGlobal = function(planId = null) {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        openPlanPageMobile(planId);
    } else {
        openPlanModal(planId);
    }
};

// Abrir página mobile
function openPlanPageMobile(planId = null) {
    // Criar estrutura da página se não existir
    if (!document.querySelector('.plan-add-page')) {
        createPlanAddPage();
    }
    
    // Resetar ou carregar dados do formulário
    const form = document.getElementById('planFormMobile');
    if (form) {
        if (planId) {
            loadPlanDataMobile(planId);
            
            // Atualizar título
            const titleElement = document.querySelector('.plan-add-title-section h1');
            if (titleElement) {
                titleElement.textContent = 'Editar Plano';
            }
            
            const subtitleElement = document.querySelector('.plan-add-subtitle');
            if (subtitleElement) {
                subtitleElement.textContent = 'Atualize os detalhes do plano';
            }
            
            const submitBtn = document.getElementById('submitBtnPlanMobile');
            if (submitBtn) {
                submitBtn.textContent = 'Atualizar Plano';
            }
        } else {
            form.reset();
            delete form.dataset.planId;
            
            // Atualizar título
            const titleElement = document.querySelector('.plan-add-title-section h1');
            if (titleElement) {
                titleElement.textContent = 'Novo Plano';
            }
            
            const subtitleElement = document.querySelector('.plan-add-subtitle');
            if (subtitleElement) {
                subtitleElement.textContent = 'Configure os detalhes do plano IPTV';
            }
            
            const submitBtn = document.getElementById('submitBtnPlanMobile');
            if (submitBtn) {
                submitBtn.textContent = 'Adicionar Plano';
            }
            
            // Valores padrão
            const durationInput = form.querySelector('[name="duration_days"]');
            if (durationInput) {
                durationInput.value = '30';
            }
            
            const screensInput = form.querySelector('[name="max_screens"]');
            if (screensInput) {
                screensInput.value = '1';
            }
            
            const statusInput = form.querySelector('[name="status"]');
            if (statusInput) {
                statusInput.value = 'active';
            }
        }
    }
    
    // Preencher dropdown de servidores
    populateServerSelectMobile();
    
    // Ativar modo página
    document.body.classList.add('plan-add-mode');
    document.body.style.overflow = 'hidden';
}

// Fechar página mobile
function closePlanPageMobile() {
    document.body.classList.remove('plan-add-mode');
    document.body.style.overflow = '';
}

// Criar estrutura da página mobile
function createPlanAddPage() {
    const pageHTML = `
        <div class="plan-add-page">
            <div class="plan-add-header">
                <button class="plan-add-back-btn" onclick="closePlanPageMobile()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </button>
                <div class="plan-add-title-section">
                    <h1>Novo Plano</h1>
                    <p class="plan-add-subtitle">Configure os detalhes do plano IPTV</p>
                </div>
            </div>
            
            <div class="plan-add-body">
                <form id="planFormMobile" class="plan-add-form">
                    <div class="modern-form-grid">
                        <div class="modern-form-group">
                            <label for="planServerMobile">Servidor *</label>
                            <select id="planServerMobile" name="server_id" required>
                                <option value="">Selecione um servidor...</option>
                            </select>
                            <small class="form-hint">Escolha o servidor onde este plano será aplicado</small>
                        </div>

                        <div class="modern-form-group">
                            <label for="planNameMobile">Nome do Plano *</label>
                            <input type="text" id="planNameMobile" name="name" placeholder="Ex: Premium Plus" required>
                        </div>

                        <div class="modern-form-group">
                            <label for="planPriceMobile">Preço Mensal *</label>
                            <input type="number" id="planPriceMobile" name="price" step="0.01" min="0" placeholder="35.00" required>
                        </div>

                        <div class="modern-form-group">
                            <label for="planDurationMobile">Duração (dias)</label>
                            <input type="number" id="planDurationMobile" name="duration_days" min="1" placeholder="30" value="30">
                        </div>

                        <div class="modern-form-group">
                            <label for="planScreensMobile">Máximo de Telas</label>
                            <input type="number" id="planScreensMobile" name="max_screens" min="1" max="10" placeholder="2" value="1">
                        </div>

                        <div class="modern-form-group">
                            <label for="planStatusMobile">Status</label>
                            <select id="planStatusMobile" name="status">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>

                        <div class="modern-form-group">
                            <label for="planDescriptionMobile">Descrição</label>
                            <textarea id="planDescriptionMobile" name="description" rows="3" placeholder="Descreva os benefícios do plano..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="plan-add-footer">
                <button type="button" class="btn-modern btn-secondary" onclick="closePlanPageMobile()">Cancelar</button>
                <button type="submit" form="planFormMobile" class="btn-modern btn-primary" id="submitBtnPlanMobile">Adicionar Plano</button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', pageHTML);
    
    // Adicionar event listener ao formulário
    const form = document.getElementById('planFormMobile');
    if (form) {
        form.addEventListener('submit', savePlanMobile);
    }
}

// Preencher dropdown de servidores mobile
function populateServerSelectMobile() {
    const serverSelect = document.getElementById('planServerMobile');
    if (!serverSelect) return;
    
    serverSelect.innerHTML = '<option value="">Selecione um servidor...</option>';
    
    if (serversData && serversData.length > 0) {
        serversData.forEach(server => {
            const option = document.createElement('option');
            option.value = server.id;
            option.textContent = server.name;
            serverSelect.appendChild(option);
        });
    }
}

// Carregar dados do plano para edição mobile
function loadPlanDataMobile(planId) {
    const plan = plansData.find(p => p.id == planId);
    if (!plan) return;
    
    const form = document.getElementById('planFormMobile');
    if (!form) return;
    
    // Preencher formulário
    document.getElementById('planServerMobile').value = plan.server_id || '';
    document.getElementById('planNameMobile').value = plan.name || '';
    document.getElementById('planPriceMobile').value = plan.price || '';
    document.getElementById('planDurationMobile').value = plan.duration_days || 30;
    document.getElementById('planScreensMobile').value = plan.max_screens || 1;
    document.getElementById('planStatusMobile').value = plan.status || 'active';
    document.getElementById('planDescriptionMobile').value = plan.description || '';
    
    // Armazenar ID no formulário
    form.dataset.planId = planId;
}

// Salvar plano mobile
async function savePlanMobile(e) {
    e.preventDefault();
    
    const form = document.getElementById('planFormMobile');
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
        alert('Preencha todos os campos obrigatórios');
        return;
    }
    
    try {
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
            alert(planId ? 'Plano atualizado com sucesso!' : 'Plano criado com sucesso!');
            closePlanPageMobile();
            loadPlans();
        } else {
            throw new Error(result.error || 'Erro ao salvar plano');
        }
    } catch (error) {
        alert('Erro ao salvar plano: ' + error.message);
    }
}

// Detectar mudança de tamanho da tela
window.addEventListener('resize', function() {
    if (window.innerWidth > 768 && document.body.classList.contains('plan-add-mode')) {
        closePlanPageMobile();
    }
});

// Sobrescrever função de fechar modal para mobile
window.closePlanModalGlobal = function() {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile && document.body.classList.contains('plan-add-mode')) {
        closePlanPageMobile();
    } else {
        closePlanModal();
    }
};
