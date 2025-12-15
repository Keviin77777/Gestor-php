/**
 * Plan Add Page - JavaScript
 */

let isEditMode = false;
let planId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticação
    if (!isAuthenticated()) {
        window.location.href = '/login';
        return;
    }

    // Verificar se está editando
    const urlParams = new URLSearchParams(window.location.search);
    planId = urlParams.get('id');
    isEditMode = !!planId;

    // Atualizar título e botão se estiver editando
    if (isEditMode) {
        document.querySelector('.header-content h1').textContent = 'Editar Plano';
        document.querySelector('.header-content p').textContent = 'Atualize os detalhes do plano IPTV';
        document.getElementById('submitBtn').innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Atualizar Plano
        `;
    }

    // Carregar servidores
    loadServers();

    // Configurar formulário
    setupForm();

    // Se estiver editando, carregar dados do plano
    if (isEditMode) {
        loadPlanData(planId);
    }
});

/**
 * Carregar dados do plano para edição
 */
async function loadPlanData(id) {
    try {
        const token = localStorage.getItem('token');
        
        // Buscar todos os planos
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

        if (result.success && result.plans) {
            // Encontrar o plano pelo ID
            const plan = result.plans.find(p => p.id == id);
            
            if (!plan) {
                throw new Error('Plano não encontrado');
            }
            
            // Preencher formulário
            document.getElementById('planServer').value = plan.server_id || '';
            document.getElementById('planName').value = plan.name || '';
            
            // Formatar preço
            const price = parseFloat(plan.price || 0).toFixed(2).replace('.', ',');
            document.getElementById('planPrice').value = price;
            
            document.getElementById('planDuration').value = plan.duration_days || 30;
            document.getElementById('planScreens').value = plan.max_screens || 1;
            document.getElementById('planStatus').value = plan.status || 'active';
            document.getElementById('planDescription').value = plan.description || '';
        } else {
            throw new Error(result.error || 'Erro ao carregar planos');
        }
    } catch (error) {
        console.error('Erro ao carregar dados do plano:', error);
        showError('Erro ao carregar dados do plano: ' + error.message);
        setTimeout(() => {
            window.location.href = '/plans';
        }, 2000);
    }
}

/**
 * Carregar servidores
 */
async function loadServers() {
    try {
        const token = localStorage.getItem('token');
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
            const select = document.getElementById('planServer');
            select.innerHTML = '<option value="">Selecione um servidor</option>' +
                result.servers.map(server => 
                    `<option value="${server.id}">${escapeHtml(server.name)}</option>`
                ).join('');
        } else {
            throw new Error(result.error || 'Erro ao carregar servidores');
        }
    } catch (error) {
        console.error('Erro ao carregar servidores:', error);
        const select = document.getElementById('planServer');
        select.innerHTML = '<option value="">Erro ao carregar servidores</option>';
        showError('Erro ao carregar servidores: ' + error.message);
    }
}

/**
 * Configurar formulário
 */
function setupForm() {
    const form = document.getElementById('planForm');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        await savePlan();
    });

    // Máscara de valor monetário
    const priceInput = document.getElementById('planPrice');
    if (priceInput) {
        priceInput.addEventListener('input', function(e) {
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
        priceInput.addEventListener('blur', function(e) {
            if (!e.target.value) {
                e.target.value = '0,00';
            }
        });

        // Valor inicial
        if (!priceInput.value) {
            priceInput.value = '0,00';
        }
    }
}

/**
 * Salvar plano
 */
async function savePlan() {
    try {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Salvando...</span>';

        const formData = new FormData(document.getElementById('planForm'));
        const data = Object.fromEntries(formData.entries());

        // Converter valor formatado (0,00) para decimal (0.00)
        if (data.price) {
            data.price = data.price.replace(',', '.');
        }

        // Validações
        if (!data.name || !data.server_id || !data.price) {
            showError('Preencha todos os campos obrigatórios');
            submitBtn.disabled = false;
            submitBtn.innerHTML = isEditMode ? `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Atualizar Plano
            ` : `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Adicionar Plano
            `;
            return;
        }

        const token = localStorage.getItem('token');
        const url = isEditMode ? `/api-plans.php?id=${planId}` : '/api-plans.php';
        const method = isEditMode ? 'PUT' : 'POST';
        
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
            showSuccess(isEditMode ? 'Plano atualizado com sucesso!' : 'Plano criado com sucesso!');
            setTimeout(() => {
                window.location.href = '/plans';
            }, 1500);
        } else {
            throw new Error(result.error || (isEditMode ? 'Erro ao atualizar plano' : 'Erro ao criar plano'));
        }

    } catch (error) {
        console.error('Erro ao salvar plano:', error);
        showError((isEditMode ? 'Erro ao atualizar plano: ' : 'Erro ao criar plano: ') + error.message);
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = false;
        submitBtn.innerHTML = isEditMode ? `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Atualizar Plano
        ` : `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Adicionar Plano
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
