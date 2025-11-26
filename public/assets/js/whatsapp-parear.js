/**
 * WhatsApp Parear - JavaScript
 */

let connectionStatus = 'disconnected';
let qrCheckInterval = null;
let statusCheckInterval = null;
let currentQRCode = null;
let isManuallyDisconnecting = false;
let selectedProvider = null; // 'native' ou 'evolution'

// Inicializar página
document.addEventListener('DOMContentLoaded', function () {
    loadUserProfile();
    loadSettings();
    checkConnectionStatus();

    // Verificar status a cada 5 segundos (só se não estiver desconectando)
    statusCheckInterval = setInterval(() => {
        if (!isManuallyDisconnecting) {
            checkConnectionStatus();
        }
    }, 5000);

    // Listener para mudança de provedor
    const providerNative = document.getElementById('providerNative');
    const providerEvolution = document.getElementById('providerEvolution');
    const currentProviderBadge = document.getElementById('currentProvider');

    if (providerNative && providerEvolution && currentProviderBadge) {
        providerNative.addEventListener('change', function() {
            if (this.checked) {
                selectedProvider = 'native';
                currentProviderBadge.textContent = 'API Premium';
                currentProviderBadge.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                // Limpar QR Code ao trocar de API
                hideQRCode();
                currentQRCode = null;
                // Parar verificações
                if (qrCheckInterval) {
                    clearInterval(qrCheckInterval);
                    qrCheckInterval = null;
                }
                showNotification('API Premium selecionada', 'info');
            }
        });

        providerEvolution.addEventListener('change', function() {
            if (this.checked) {
                selectedProvider = 'evolution';
                currentProviderBadge.textContent = 'API Básica';
                currentProviderBadge.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
                // Limpar QR Code ao trocar de API
                hideQRCode();
                currentQRCode = null;
                // Parar verificações
                if (qrCheckInterval) {
                    clearInterval(qrCheckInterval);
                    qrCheckInterval = null;
                }
                showNotification('API Básica selecionada', 'info');
            }
        });
        
        // Definir provider inicial
        if (providerNative.checked) {
            selectedProvider = 'native';
        } else if (providerEvolution.checked) {
            selectedProvider = 'evolution';
        }
    }
});

/**
 * Carregar perfil do usuário
 */
function loadUserProfile() {
    try {
        const userStr = localStorage.getItem('user');
        if (!userStr) return;

        const user = JSON.parse(userStr);

        // Nome do usuário
        const userName = document.getElementById('userName');
        if (userName) {
            userName.textContent = user.name || 'Usuário';
        }

        // Email do usuário
        const userEmail = document.getElementById('userEmail');
        if (userEmail) {
            userEmail.textContent = user.email || '';
        }

        // Avatar do usuário
        const userAvatar = document.getElementById('userAvatar');
        if (userAvatar) {
            const initials = (user.name || 'U').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            userAvatar.textContent = initials;
        }

    } catch (error) {
        // Erro ao carregar dados do usuário
    }
}

/**
 * Carregar configurações salvas
 */
async function loadSettings() {
    try {
        const response = await fetch('/api-whatsapp-settings.php');
        const data = await response.json();

        if (data.success && data.settings) {
            // Verificar se os elementos existem antes de tentar definir valores
            const apiUrlElement = document.getElementById('apiUrl');
            const apiKeyElement = document.getElementById('apiKey');
            const instanceNameElement = document.getElementById('instanceName');
            
            if (apiUrlElement) {
                apiUrlElement.value = data.settings.evolution_api_url || 'http://localhost:8081';
            }
            if (apiKeyElement) {
                apiKeyElement.value = data.settings.evolution_api_key || '';
            }
            if (instanceNameElement) {
                instanceNameElement.value = data.settings.instance_name || 'ultragestor-admin';
            }
        }
    } catch (error) {
        }
}

/**
 * Salvar configurações
 */
async function saveSettings() {
    const settings = {
        evolution_api_url: document.getElementById('apiUrl').value,
        evolution_api_key: document.getElementById('apiKey').value,
        instance_name: document.getElementById('instanceName').value
    };

    try {
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch('/api-whatsapp-settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(settings)
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Configurações salvas com sucesso!', 'success');
            } else {
                throw new Error(data.error || 'Erro ao salvar configurações');
            }
        }, {
            type: 'global',
            id: 'save-settings'
        });
    } catch (error) {
        showNotification('Erro ao salvar configurações: ' + error.message, 'error');
    }
}

/**
 * Testar conexão com a API
 */
async function testConnection() {
    const apiUrl = document.getElementById('apiUrl').value;
    const apiKey = document.getElementById('apiKey').value;

    if (!apiUrl) {
        showNotification('Por favor, informe a URL da API', 'warning');
        return;
    }

    try {
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch('/api-whatsapp-test.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    api_url: apiUrl,
                    api_key: apiKey
                })
            });

            const data = await response.json();

            if (data.success) {
                showNotification('✅ Conexão com a API estabelecida com sucesso!', 'success');
            } else {
                throw new Error(data.error || 'Erro na conexão');
            }
        }, {
            type: 'global',
            id: 'test-connection'
        });
    } catch (error) {
        showNotification('❌ Erro na conexão: ' + error.message, 'error');
    }
}

/**
 * Conectar WhatsApp
 */
async function connectWhatsApp() {
    try {
        // Limpar estado anterior
        isManuallyDisconnecting = false;
        currentQRCode = null;
        hideQRCode();
        
        // Obter dados do usuário logado
        const userStr = localStorage.getItem('user');
        if (!userStr) {
            showNotification('❌ Erro: Usuário não encontrado. Faça login novamente.', 'error');
            return;
        }

        const user = JSON.parse(userStr);
        const resellerId = user.id || user.reseller_id || 'admin-001';
        const instanceName = `ultragestor-${resellerId}`;

        // Detectar qual API foi escolhida
        const providerNative = document.getElementById('providerNative');
        const providerEvolution = document.getElementById('providerEvolution');
        const useNativeApi = providerNative && providerNative.checked;
        
        // Atualizar provider selecionado
        selectedProvider = useNativeApi ? 'native' : 'evolution';

        await window.LoadingManager.withLoading(async () => {
            updateConnectionStatus('connecting');
            
            if (useNativeApi) {
                showNotification('🔄 Conectando via API Premium...', 'info');
                
                // Usar API Premium
                const response = await fetch('/api-whatsapp-native-connect.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        instance_name: instanceName,
                        reseller_id: resellerId
                    })
                });

                if (!response.ok) {
                    throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (data.success) {
                    showNotification('✅ Instância criada com sucesso!', 'success');

                    // Mostrar QR Code se disponível
                    if (data.qr_code) {
                        showQRCode(data.qr_code);
                        showNotification('📱 Escaneie o QR Code com seu WhatsApp', 'info');
                        startQRCheck();
                    } else {
                        showNotification('🔍 Buscando QR Code...', 'info');
                        startQRCheck();
                    }
                } else {
                    throw new Error(data.error || 'Erro ao criar instância');
                }
            } else {
                showNotification('🔄 Conectando via API Básica...', 'info');
                
                // Usar API Básica (Evolution)
                const response = await fetch('/api-whatsapp-connect.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        instance_name: instanceName,
                        reseller_id: resellerId
                    })
                });

                if (!response.ok) {
                    throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (data.success) {
                    showNotification('✅ Instância criada com sucesso!', 'success');

                    // Mostrar QR Code se disponível
                    if (data.qr_code) {
                        showQRCode(data.qr_code);
                        showNotification('📱 Escaneie o QR Code com seu WhatsApp', 'info');
                        startQRCheck();
                    } else {
                        showNotification('🔍 Buscando QR Code...', 'info');
                        startQRCheck();
                    }
                } else {
                    const errorMsg = data.error || 'Erro ao criar instância';
                    
                    // Verificar se é erro de API Key
                    if (errorMsg.includes('api key') || errorMsg.includes('Forbidden')) {
                        throw new Error('⚠️ Evolution API Key não configurada. Configure a API Key no arquivo .env');
                    } else if (errorMsg.includes('não está acessível')) {
                        throw new Error('⚠️ Evolution API não está rodando. Inicie a Evolution API na porta 8081');
                    } else {
                        throw new Error(errorMsg);
                    }
                }
            }
        }, {
            type: 'global',
            id: 'connect-whatsapp'
        });
    } catch (error) {
        updateConnectionStatus('error');
        showNotification('❌ Erro ao conectar: ' + error.message, 'error');
        }
}

/**
 * Desconectar WhatsApp
 */
async function disconnectWhatsApp() {
    try {
        // Marcar que estamos desconectando manualmente
        isManuallyDisconnecting = true;
        
        // Parar todas as verificações
        if (qrCheckInterval) {
            clearInterval(qrCheckInterval);
            qrCheckInterval = null;
        }
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
            statusCheckInterval = null;
        }
        
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch('/api-whatsapp-disconnect.php', {
                method: 'POST'
            });

            const data = await response.json();

            if (data.success) {
                updateConnectionStatus('disconnected');
                hideQRCode();
                hideAccountInfo();
                currentQRCode = null;
                showNotification('WhatsApp desconectado com sucesso!', 'success');
            } else {
                throw new Error(data.error || 'Erro ao desconectar');
            }
        }, {
            type: 'global',
            id: 'disconnect-whatsapp'
        });
        
        // Aguardar 2 segundos antes de permitir reconexão automática
        setTimeout(() => {
            isManuallyDisconnecting = false;
        }, 2000);
        
    } catch (error) {
        isManuallyDisconnecting = false;
        showNotification('Erro ao desconectar: ' + error.message, 'error');
    }
}

/**
 * Verificar status da conexão
 */
async function checkConnectionStatus() {
    // Não verificar se estiver desconectando manualmente
    if (isManuallyDisconnecting) {
        return;
    }
    
    try {
        const response = await fetch('/api-whatsapp-status.php');
        const data = await response.json();

        if (data.success && data.session) {
            const session = data.session;
            updateConnectionStatus(session.status);

            switch (session.status) {
                case 'connected':
                    if (session.profile_name) {
                        showAccountInfo(session);
                        hideQRCode();
                        currentQRCode = null;
                        // Parar verificação de QR Code se estiver rodando
                        if (qrCheckInterval) {
                            clearInterval(qrCheckInterval);
                            qrCheckInterval = null;
                        }
                    }
                    break;
                    
                case 'connecting':
                case 'qr_code':
                    // Só mostrar QR Code se estiver em processo de conexão ativo
                    if (session.qr_code && qrCheckInterval !== null) {
                        showQRCode(session.qr_code);
                        hideAccountInfo();
                    }
                    break;
                    
                default:
                    // Não fazer nada se estiver desconectado
                    // Não limpar QR Code automaticamente
                    break;
            }
        } else {
            // Só atualizar para desconectado se não estiver em processo de conexão
            if (qrCheckInterval === null && !isManuallyDisconnecting) {
                updateConnectionStatus('disconnected');
                hideQRCode();
                hideAccountInfo();
                currentQRCode = null;
            }
        }
    } catch (error) {
        // Em caso de erro, não alterar o status atual para evitar flickering
    }
}

/**
 * Iniciar verificação do QR Code
 */
function startQRCheck() {
    // Não iniciar se estiver desconectando
    if (isManuallyDisconnecting) {
        return;
    }
    
    if (qrCheckInterval) {
        clearInterval(qrCheckInterval);
    }

    let attempts = 0;
    const maxAttempts = 100; // 5 minutos (100 * 3s)
    let lastMessage = '';

    qrCheckInterval = setInterval(async () => {
        // Parar se estiver desconectando
        if (isManuallyDisconnecting) {
            clearInterval(qrCheckInterval);
            qrCheckInterval = null;
            return;
        }
        
        try {
            attempts++;
            
            const response = await fetch('/api-whatsapp-qr.php');
            
            if (!response.ok) {
                // Não lançar erro, apenas continuar tentando
                return;
            }

            const data = await response.json();

            if (data.success) {
                if (data.connected) {
                    // WhatsApp conectado com sucesso
                    clearInterval(qrCheckInterval);
                    qrCheckInterval = null;
                    hideQRCode();
                    updateConnectionStatus('connected');
                    showNotification('🎉 WhatsApp conectado com sucesso!', 'success');
                    checkConnectionStatus(); // Atualizar informações da conta
                } else if (data.qr_code) {
                    // Novo QR Code disponível - só mostrar se for da API selecionada
                    showQRCode(data.qr_code);
                    attempts = 0; // Resetar tentativas quando receber QR Code
                    if (lastMessage !== 'qr_ready') {
                        showNotification('📱 QR Code pronto! Escaneie com seu WhatsApp', 'success');
                        lastMessage = 'qr_ready';
                    }
                } else if (data.message) {
                    // Mostrar mensagem de progresso
                    if (lastMessage !== data.message) {
                        lastMessage = data.message;
                        
                        // Mostrar mensagem apenas a cada 10 tentativas para não poluir
                        if (attempts % 10 === 0) {
                            showNotification('🔄 ' + data.message, 'info');
                        }
                    }
                }
            }

            // Timeout após tentativas máximas
            if (attempts >= maxAttempts) {
                clearInterval(qrCheckInterval);
                qrCheckInterval = null;
                hideQRCode();
                updateConnectionStatus('error');
                showNotification('⏰ Tempo limite excedido. Tente conectar novamente.', 'warning');
            }
        } catch (error) {
            // Não incrementar attempts em caso de erro de parsing
        }
    }, 3000);

    // Mostrar progresso para o usuário
    showNotification('🔍 Iniciando conexão...', 'info');
}

/**
 * Mostrar QR Code
 */
function showQRCode(qrCode) {
    // Evitar mostrar o mesmo QR Code duas vezes
    if (currentQRCode === qrCode) {
        return;
    }
    
    // Não mostrar QR Code se estiver desconectando
    if (isManuallyDisconnecting) {
        return;
    }
    
    const qrCodeCard = document.getElementById('qrCodeCard');
    const qrCodeImage = document.getElementById('qrCodeImage');

    if (qrCode) {
        currentQRCode = qrCode;
        
        // Verificar se o QR Code já tem o prefixo data:image
        const qrSrc = qrCode.startsWith('data:image') ? qrCode : `data:image/png;base64,${qrCode}`;
        
        qrCodeImage.innerHTML = `
            <div class="qr-code-wrapper">
                <img src="${qrSrc}" alt="QR Code WhatsApp" class="qr-image" style="max-width: 100%; height: auto;" onerror="">
                <div class="qr-overlay">
                    <div class="qr-scanner-line"></div>
                </div>
            </div>
        `;
    } else {
        qrCodeImage.innerHTML = `
            <div class="qr-loading">
                <div class="spinner"></div>
                <p>Gerando QR Code...</p>
            </div>
        `;
    }
    
    qrCodeCard.style.display = 'block';
    
    // Scroll suave para o QR Code
    setTimeout(() => {
        qrCodeCard.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
    }, 100);
}

/**
 * Esconder QR Code
 */
function hideQRCode() {
    const qrCodeCard = document.getElementById('qrCodeCard');
    qrCodeCard.style.display = 'none';
    currentQRCode = null;

    if (qrCheckInterval) {
        clearInterval(qrCheckInterval);
        qrCheckInterval = null;
    }
}

/**
 * Mostrar informações da conta
 */
function showAccountInfo(session) {
    const accountInfoCard = document.getElementById('accountInfoCard');
    const accountName = document.getElementById('accountName');
    const accountPhone = document.getElementById('accountPhone');
    const accountAvatar = document.getElementById('accountAvatar');
    const connectedAt = document.getElementById('connectedAt');
    const lastSeen = document.getElementById('lastSeen');

    accountName.textContent = session.profile_name || 'Nome não disponível';
    accountPhone.textContent = session.phone_number || 'Número não disponível';

    if (session.profile_picture) {
        accountAvatar.innerHTML = `<img src="${session.profile_picture}" alt="Avatar">`;
    }

    if (session.connected_at) {
        connectedAt.textContent = formatDateTime(session.connected_at);
    }

    if (session.last_seen) {
        lastSeen.textContent = formatDateTime(session.last_seen);
    }

    accountInfoCard.style.display = 'block';
}

/**
 * Esconder informações da conta
 */
function hideAccountInfo() {
    const accountInfoCard = document.getElementById('accountInfoCard');
    accountInfoCard.style.display = 'none';
}

/**
 * Atualizar status da conexão
 */
function updateConnectionStatus(status) {
    connectionStatus = status;

    const statusIcon = document.getElementById('statusIcon');
    const statusTitle = document.getElementById('statusTitle');
    const statusDescription = document.getElementById('statusDescription');
    const connectBtn = document.getElementById('connectBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');

    // Remover classes anteriores
    statusIcon.classList.remove('connected', 'connecting');

    switch (status) {
        case 'connected':
            statusIcon.classList.add('connected');
            statusIcon.innerHTML = `
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            `;
            statusTitle.textContent = '✅ WhatsApp Conectado';
            statusDescription.textContent = 'Sua conta está conectada e funcionando perfeitamente';
            connectBtn.style.display = 'none';
            disconnectBtn.style.display = 'inline-flex';
            break;

        case 'connecting':
            statusIcon.classList.add('connecting');
            statusIcon.innerHTML = `
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            `;
            statusTitle.textContent = '🔄 Conectando...';
            statusDescription.textContent = 'Escaneie o QR Code com seu celular para conectar';
            connectBtn.style.display = 'none';
            disconnectBtn.style.display = 'inline-flex';
            break;

        case 'error':
            statusIcon.innerHTML = `
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
            `;
            statusTitle.textContent = '❌ Erro na Conexão';
            statusDescription.textContent = 'Ocorreu um erro. Verifique sua conexão e tente novamente';
            connectBtn.style.display = 'inline-flex';
            disconnectBtn.style.display = 'none';
            break;

        default: // disconnected
            statusIcon.innerHTML = `
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                </svg>
            `;
            statusTitle.textContent = '📱 WhatsApp Desconectado';
            statusDescription.textContent = 'Clique em "Conectar" para iniciar o pareamento com seu WhatsApp';
            connectBtn.style.display = 'inline-flex';
            disconnectBtn.style.display = 'none';
            break;
    }
}

/**
 * Formatar data e hora
 */
function formatDateTime(dateString) {
    if (!dateString) return '--';

    try {
        const date = new Date(dateString);
        return date.toLocaleString('pt-BR');
    } catch (error) {
        return '--';
    }
}

// toggleSubmenu agora está em common.js

/**
 * Mostrar notificação
 */
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');

    // Remover notificações antigas do mesmo tipo para evitar spam
    const existingNotifications = container.querySelectorAll(`.notification.${type}`);
    existingNotifications.forEach(notification => {
        if (notification.querySelector('.notification-message').textContent.includes(message.replace(/[🔄✅❌📱🎉⏰🔍]/g, '').trim())) {
            notification.remove();
        }
    });

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // Ícones para diferentes tipos
    const icons = {
        'success': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'error': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        'warning': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        'info': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>'
    };
    
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                ${icons[type] || icons['info']}
            </div>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    `;

    container.appendChild(notification);

    // Animação de entrada
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    // Auto remove baseado no tipo
    const autoRemoveTime = type === 'error' ? 8000 : type === 'success' ? 4000 : 6000;
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('hide');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }, autoRemoveTime);
}

/**
 * Iniciar verificação de QR Code
 */
function startQRCheck() {
    // Limpar intervalo anterior se existir
    if (qrCheckInterval) {
        clearInterval(qrCheckInterval);
    }

    // Verificar QR Code a cada 3 segundos
    qrCheckInterval = setInterval(async () => {
        try {
            const response = await fetch('/api-whatsapp-qr.php');
            const data = await response.json();

            if (data.success) {
                if (data.connected) {
                    // WhatsApp conectado!
                    clearInterval(qrCheckInterval);
                    qrCheckInterval = null;
                    hideQRCode();
                    updateConnectionStatus('connected');
                    showNotification('🎉 WhatsApp conectado com sucesso!', 'success');
                    
                    // Atualizar informações do perfil
                    if (data.profile_name) {
                        const profileName = document.getElementById('profileName');
                        if (profileName) {
                            profileName.textContent = data.profile_name;
                        }
                    }
                    if (data.phone_number) {
                        const phoneNumber = document.getElementById('phoneNumber');
                        if (phoneNumber) {
                            phoneNumber.textContent = data.phone_number;
                        }
                    }
                } else if (data.qr_code) {
                    // Atualizar QR Code
                    showQRCode(data.qr_code);
                }
            }
        } catch (error) {
            }
    }, 3000);

    // Parar verificação após 5 minutos (QR Code expira)
    setTimeout(() => {
        if (qrCheckInterval) {
            clearInterval(qrCheckInterval);
            qrCheckInterval = null;
            showNotification('⏰ QR Code expirado. Tente conectar novamente.', 'warning');
            updateConnectionStatus('disconnected');
            hideQRCode();
        }
    }, 300000); // 5 minutos
}

/**
 * Parar verificação de QR Code
 */
function stopQRCheck() {
    if (qrCheckInterval) {
        clearInterval(qrCheckInterval);
        qrCheckInterval = null;
    }
}

// Cleanup ao sair da página
window.addEventListener('beforeunload', function () {
    stopQRCheck();
    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
    }
});