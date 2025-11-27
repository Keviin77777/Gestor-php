/**
 * Métodos de Pagamento - JavaScript Moderno
 */

let currentProvider = null;

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname === '/payment-methods') {
        initPaymentMethods();
    }
});

/**
 * Inicializar página de métodos de pagamento
 */
function initPaymentMethods() {
    loadAllProviders();
}

/**
 * Carregar status de todos os provedores
 */
async function loadAllProviders() {
    await loadMercadoPagoConfig();
    await loadAsaasConfig();
    
    // Carregar EFI Bank apenas se o card existir (apenas para admin)
    const efiCard = document.querySelector('[data-provider="efibank"]');
    if (efiCard) {
        await loadEfiBankConfig();
    }
}

/**
 * Carregar configuração do Mercado Pago
 */
async function loadMercadoPagoConfig() {
    try {
        const response = await fetch('/api-payment-methods.php?method=mercadopago', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.config) {
            updateProviderStatus('mercadopago', result.config.enabled);
        }
    } catch (error) {
        console.error('Erro ao carregar Mercado Pago:', error);
    }
}

/**
 * Carregar configuração do Asaas
 */
async function loadAsaasConfig() {
    try {
        const response = await fetch('/api-payment-methods.php?method=asaas', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.config) {
            updateProviderStatus('asaas', result.config.enabled);
        }
    } catch (error) {
        console.error('Erro ao carregar Asaas:', error);
    }
}

/**
 * Carregar configuração do EFI Bank
 */
async function loadEfiBankConfig() {
    try {
        const response = await fetch('/api-payment-methods.php?method=efibank', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.config) {
            updateProviderStatus('efibank', result.config.enabled);
        }
    } catch (error) {
        console.error('Erro ao carregar EFI Bank:', error);
    }
}

/**
 * Atualizar status visual do provedor
 */
function updateProviderStatus(provider, enabled) {
    const statusElement = document.getElementById(`${provider === 'mercadopago' ? 'mp' : provider}Status`);
    if (!statusElement) return;
    
    const badge = statusElement.querySelector('.status-badge');
    if (!badge) return;
    
    if (enabled) {
        badge.className = 'status-badge status-active';
        badge.textContent = 'Ativo';
    } else {
        badge.className = 'status-badge status-inactive';
        badge.textContent = 'Inativo';
    }
}

/**
 * Abrir modal de configuração
 */
async function openProviderModal(provider) {
    currentProvider = provider;
    const modal = document.getElementById('providerModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    
    // Definir título
    const titles = {
        'mercadopago': 'Configurar Mercado Pago',
        'asaas': 'Configurar Asaas',
        'efibank': 'Configurar EFI Bank'
    };
    modalTitle.textContent = titles[provider] || 'Configurar Provedor';
    
    // Carregar formulário
    modalBody.innerHTML = '<div style="text-align: center; padding: 2rem;">Carregando...</div>';
    modal.classList.add('active');
    
    // Buscar configuração atual
    try {
        const response = await fetch(`/api-payment-methods.php?method=${provider}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        const config = result.config || {};
        
        // Renderizar formulário específico
        if (provider === 'mercadopago') {
            modalBody.innerHTML = getMercadoPagoForm(config);
        } else if (provider === 'asaas') {
            modalBody.innerHTML = getAsaasForm(config);
        } else if (provider === 'efibank') {
            modalBody.innerHTML = getEfiBankForm(config);
        }
        
        // Configurar handler do formulário
        setupModalFormHandler();
        
    } catch (error) {
        modalBody.innerHTML = '<div style="color: red; padding: 2rem;">Erro ao carregar configuração</div>';
    }
}

/**
 * Fechar modal
 */
function closeProviderModal() {
    const modal = document.getElementById('providerModal');
    modal.classList.remove('active');
    currentProvider = null;
}

/**
 * Formulário Mercado Pago
 */
function getMercadoPagoForm(config) {
    return `
        <form id="providerForm" class="payment-form">
            <div class="form-group">
                <label for="publicKey">Public Key *</label>
                <input 
                    type="text" 
                    id="publicKey" 
                    name="public_key" 
                    placeholder="APP_USR-XXXXXXXX-XXXXXX-XXXXXXXX"
                    value="${config.public_key || ''}"
                    required
                >
                <small class="form-help">
                    Public Key para validação de pagamentos no frontend
                </small>
            </div>
            
            <div class="form-group">
                <label for="accessToken">Access Token *</label>
                <input 
                    type="password" 
                    id="accessToken" 
                    name="access_token" 
                    placeholder="APP_USR-XXXXXXXX-XXXXXX-XXXXXXXX"
                    value="${config.access_token || ''}"
                    required
                >
                <small class="form-help">
                    Obtenha suas credenciais no 
                    <a href="https://www.mercadopago.com.br/developers/panel/app" target="_blank">
                        Painel de Desenvolvedores do Mercado Pago
                    </a>
                </small>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enabled" ${config.enabled ? 'checked' : ''}>
                    <span>Ativar Mercado Pago</span>
                </label>
            </div>
            
            <div class="info-alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <p><strong>Importante:</strong> Use as credenciais de <strong>Produção</strong> para receber pagamentos reais.</p>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="testProviderConnection()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    Testar Conexão
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar
                </button>
            </div>
        </form>
    `;
}

/**
 * Formulário Asaas
 */
function getAsaasForm(config) {
    return `
        <form id="providerForm" class="payment-form">
            <div class="form-group">
                <label for="apiKey">API Key *</label>
                <input 
                    type="password" 
                    id="apiKey" 
                    name="api_key" 
                    placeholder="$aact_YTU5YTE0M2M2N2I4MTliNzk0YTI5N2U5MzdjNWZmNDQ6OjAwMDAwMDAwMDAwMDAwMDAwMDA6OiRhYWNoXzRlNTU="
                    value="${config.api_key || ''}"
                    required
                >
                <small class="form-help">
                    Obtenha sua API Key no 
                    <a href="https://www.asaas.com/config/api" target="_blank">
                        Painel Asaas → Integrações → API
                    </a>
                </small>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="sandbox" ${config.sandbox ? 'checked' : ''}>
                    <span>Modo Sandbox (Homologação)</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enabled" ${config.enabled ? 'checked' : ''}>
                    <span>Ativar Asaas</span>
                </label>
            </div>
            
            <div class="info-alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <p><strong>Importante:</strong> Use o modo Sandbox para testes. Em produção, desmarque esta opção e use a API Key de produção.</p>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="testProviderConnection()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    Testar Conexão
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar
                </button>
            </div>
        </form>
    `;
}

/**
 * Formulário EFI Bank
 */
function getEfiBankForm(config) {
    return `
        <form id="providerForm" class="payment-form">
            <div class="form-group">
                <label for="clientId">Client ID *</label>
                <input 
                    type="text" 
                    id="clientId" 
                    name="client_id" 
                    placeholder="Client_Id_XXXXXXXXXXXXXXXX"
                    value="${config.client_id || ''}"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="clientSecret">Client Secret *</label>
                <input 
                    type="password" 
                    id="clientSecret" 
                    name="client_secret" 
                    placeholder="Client_Secret_XXXXXXXXXXXXXXXX"
                    value="${config.client_secret || ''}"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="pixKey">Chave PIX *</label>
                <input 
                    type="text" 
                    id="pixKey" 
                    name="pix_key" 
                    placeholder="sua@chave.pix"
                    value="${config.pix_key || ''}"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="certificate">Certificado SSL (Opcional)</label>
                <input 
                    type="text" 
                    id="certificate" 
                    name="certificate" 
                    placeholder="/caminho/para/certificado.pem"
                    value="${config.certificate || ''}"
                >
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="sandbox" ${config.sandbox ? 'checked' : ''}>
                    <span>Modo Sandbox (Homologação)</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enabled" ${config.enabled ? 'checked' : ''}>
                    <span>Ativar EFI Bank</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="testProviderConnection()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    Testar Conexão
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar
                </button>
            </div>
        </form>
    `;
}

/**
 * Configurar handler do formulário no modal
 */
function setupModalFormHandler() {
    const form = document.getElementById('providerForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await saveProviderConfig();
        });
    }
}

/**
 * Salvar configuração do provedor
 */
async function saveProviderConfig() {
    const form = document.getElementById('providerForm');
    const formData = new FormData(form);
    
    const config = {};
    for (let [key, value] of formData.entries()) {
        if (key === 'enabled' || key === 'sandbox') {
            config[key] = formData.get(key) === 'on';
        } else {
            config[key] = value;
        }
    }
    
    try {
        const response = await fetch('/api-payment-methods.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                method: currentProvider,
                config: config
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Configuração salva com sucesso!');
            updateProviderStatus(currentProvider, config.enabled);
            closeProviderModal();
        } else {
            alert('❌ Erro ao salvar: ' + result.error);
        }
    } catch (error) {
        alert('❌ Erro ao salvar configuração');
    }
}

/**
 * Testar conexão com provedor
 */
async function testProviderConnection() {
    const form = document.getElementById('providerForm');
    const formData = new FormData(form);
    
    const testData = {
        method: currentProvider
    };
    
    for (let [key, value] of formData.entries()) {
        if (key === 'sandbox') {
            testData[key] = formData.get(key) === 'on';
        } else {
            testData[key] = value;
        }
    }
    
    try {
        const response = await fetch('/api-payment-methods.php?action=test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify(testData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            const info = result.account_info;
            let message = '✅ Conexão testada com sucesso!\n\n';
            message += '📋 Informações da Conta:\n\n';
            
            for (let key in info) {
                if (key !== 'message') {
                    message += `${key}: ${info[key]}\n`;
                }
            }
            
            if (info.message) {
                message += `\n${info.message}`;
            }
            
            alert(message);
        } else {
            alert('❌ Erro ao testar conexão:\n\n' + result.error);
        }
    } catch (error) {
        alert('❌ Erro ao testar conexão');
    }
}

// Fechar modal ao clicar fora
document.addEventListener('click', (e) => {
    const modal = document.getElementById('providerModal');
    if (e.target === modal) {
        closeProviderModal();
    }
});
