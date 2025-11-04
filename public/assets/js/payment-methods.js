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
    const form = document.getElementById('mercadoPagoForm');
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveMercadoPagoConfig();
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
