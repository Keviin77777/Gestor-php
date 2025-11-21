/**
 * Métodos de Pagamento - JavaScript
 */

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
    loadMercadoPagoConfig();
    loadEfiBankConfig();
    setupFormHandlers();
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
            // Preencher formulário (ordem: Public Key primeiro, depois Access Token)
            document.getElementById('mpPublicKey').value = result.config.public_key || '';
            document.getElementById('mpAccessToken').value = result.config.access_token || '';
            document.getElementById('mpEnabled').checked = result.config.enabled || false;
            
            // Atualizar status
            updateMercadoPagoStatus(result.config.enabled);
        }
    } catch (error) {
        }
}

/**
 * Atualizar status do Mercado Pago
 */
function updateMercadoPagoStatus(enabled) {
    const statusElement = document.getElementById('mpStatus');
    const badge = statusElement.querySelector('.status-badge');
    
    if (enabled) {
        badge.className = 'status-badge status-active';
        badge.textContent = 'Ativo';
    } else {
        badge.className = 'status-badge status-inactive';
        badge.textContent = 'Não Configurado';
    }
}

/**
 * Configurar handlers do formulário
 */
function setupFormHandlers() {
    const mpForm = document.getElementById('mercadoPagoForm');
    const efiForm = document.getElementById('efiBankForm');
    
    mpForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveMercadoPagoConfig();
    });
    
    efiForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveEfiBankConfig();
    });
}

/**
 * Salvar configuração do Mercado Pago
 */
async function saveMercadoPagoConfig() {
    const form = document.getElementById('mercadoPagoForm');
    const formData = new FormData(form);
    
    const config = {
        public_key: formData.get('public_key'),
        access_token: formData.get('access_token'),
        enabled: formData.get('enabled') === 'on'
    };
    
    try {
        const response = await fetch('/api-payment-methods.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                method: 'mercadopago',
                config: config
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Configuração salva com sucesso!');
            updateMercadoPagoStatus(config.enabled);
        } else {
            alert('❌ Erro ao salvar: ' + result.error);
        }
    } catch (error) {
        alert('❌ Erro ao salvar configuração');
    }
}

/**
 * Testar conexão com Mercado Pago
 */
async function testMercadoPagoConnection() {
    const publicKey = document.getElementById('mpPublicKey').value;
    const accessToken = document.getElementById('mpAccessToken').value;
    
    if (!publicKey || !accessToken) {
        alert('⚠️ Por favor, preencha a Public Key e o Access Token');
        return;
    }
    
    try {
        const response = await fetch('/api-payment-methods.php?action=test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                method: 'mercadopago',
                public_key: publicKey,
                access_token: accessToken
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const info = result.account_info;
            let message = '✅ Conexão testada com sucesso!\n\n';
            message += '📋 Informações da Conta Mercado Pago:\n\n';
            
            if (info.collector_id) {
                message += `🆔 Collector ID: ${info.collector_id}\n`;
            }
            
            if (info.currency) {
                message += `💰 Moeda: ${info.currency}\n`;
            }
            
            message += `✅ Status: Credenciais válidas\n`;
            
            if (info.test_payment_id) {
                message += `\n💡 Teste: Pagamento #${info.test_payment_id} criado\n`;
            }
            
            message += `\n${info.message || '🎉 Mercado Pago configurado e pronto para uso!'}`;
            
            alert(message);
        } else {
            // Log detalhado no console
            let errorMsg = '❌ Erro ao testar conexão:\n\n' + result.error;
            
            if (result.details) {
                errorMsg += '\n\nDetalhes: ' + JSON.stringify(result.details, null, 2);
            }
            
            alert(errorMsg);
        }
    } catch (error) {
        alert('❌ Erro ao testar conexão');
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
            // Preencher formulário
            document.getElementById('efiClientId').value = result.config.client_id || '';
            document.getElementById('efiClientSecret').value = result.config.client_secret || '';
            document.getElementById('efiPixKey').value = result.config.pix_key || '';
            document.getElementById('efiCertificate').value = result.config.certificate || '';
            document.getElementById('efiSandbox').checked = result.config.sandbox || false;
            document.getElementById('efiEnabled').checked = result.config.enabled || false;
            
            // Atualizar status
            updateEfiBankStatus(result.config.enabled);
        }
    } catch (error) {
        console.error('Erro ao carregar config EFI Bank:', error);
    }
}

/**
 * Atualizar status do EFI Bank
 */
function updateEfiBankStatus(enabled) {
    const statusElement = document.getElementById('efiStatus');
    const badge = statusElement.querySelector('.status-badge');
    
    if (enabled) {
        badge.className = 'status-badge status-active';
        badge.textContent = 'Ativo';
    } else {
        badge.className = 'status-badge status-inactive';
        badge.textContent = 'Não Configurado';
    }
}

/**
 * Salvar configuração do EFI Bank
 */
async function saveEfiBankConfig() {
    const form = document.getElementById('efiBankForm');
    const formData = new FormData(form);
    
    const config = {
        client_id: formData.get('client_id'),
        client_secret: formData.get('client_secret'),
        pix_key: formData.get('pix_key'),
        certificate: formData.get('certificate') || '',
        sandbox: formData.get('sandbox') === 'on',
        enabled: formData.get('enabled') === 'on'
    };
    
    try {
        const response = await fetch('/api-payment-methods.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                method: 'efibank',
                config: config
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Configuração salva com sucesso!');
            updateEfiBankStatus(config.enabled);
        } else {
            alert('❌ Erro ao salvar: ' + result.error);
        }
    } catch (error) {
        console.error('Erro ao salvar:', error);
        alert('❌ Erro ao salvar configuração');
    }
}

/**
 * Testar conexão com EFI Bank
 */
async function testEfiBankConnection() {
    const clientId = document.getElementById('efiClientId').value;
    const clientSecret = document.getElementById('efiClientSecret').value;
    const pixKey = document.getElementById('efiPixKey').value;
    const certificate = document.getElementById('efiCertificate').value;
    const sandbox = document.getElementById('efiSandbox').checked;
    
    if (!clientId || !clientSecret || !pixKey) {
        alert('⚠️ Por favor, preencha Client ID, Client Secret e Chave PIX');
        return;
    }
    
    try {
        const response = await fetch('/api-payment-methods.php?action=test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                method: 'efibank',
                client_id: clientId,
                client_secret: clientSecret,
                pix_key: pixKey,
                certificate: certificate,
                sandbox: sandbox
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const info = result.account_info;
            let message = '✅ Conexão testada com sucesso!\n\n';
            message += '📋 Informações da Conta EFI Bank:\n\n';
            
            if (info.status) {
                message += `${info.status}\n`;
            }
            
            if (info.token_type) {
                message += `🔑 Token Type: ${info.token_type}\n`;
            }
            
            if (info.environment) {
                message += `🌍 Ambiente: ${info.environment}\n`;
            }
            
            if (info.expires_in) {
                message += `⏱️ Token expira em: ${info.expires_in}s\n`;
            }
            
            message += `\n${info.message || '🎉 EFI Bank configurado e pronto para uso!'}`;
            message += '\n\n💾 Salvando configurações automaticamente...';
            
            alert(message);
            
            // Marcar como ativo e salvar automaticamente
            document.getElementById('efiEnabled').checked = true;
            await saveEfiBankConfig();
            
        } else {
            let errorMsg = '❌ Erro ao testar conexão:\n\n' + result.error;
            
            if (result.details) {
                console.error('Detalhes do erro:', result.details);
                errorMsg += '\n\nVerifique o console para mais detalhes.';
            }
            
            alert(errorMsg);
        }
    } catch (error) {
        console.error('Erro ao testar:', error);
        alert('❌ Erro ao testar conexão');
    }
}
