/**
 * Profile Page JavaScript
 * Gerenciamento do perfil do usuário - APENAS BANCO DE DADOS
 */

let currentUser = null;
let isAdmin = false;

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    loadUserProfile();
    setupEventListeners();
    setupPasswordValidation();
});

/**
 * Carregar perfil do usuário - APENAS DO BANCO DE DADOS
 */
async function loadUserProfile() {
    try {
        showLoading(true);
        
        // Buscar dados APENAS do servidor (banco de dados)
        const response = await fetch('/api-profile.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin' // Usar cookies de sessão
        });
        
        if (!response.ok) {
            if (response.status === 401) {
                throw new Error('Não autenticado');
            }
            throw new Error('Erro ao carregar perfil');
        }
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            isAdmin = data.user.is_admin == 1 || data.user.role === 'admin';
            
            updateProfileDisplay();
            populateForm();
            
            if (!isAdmin && data.plan) {
                updatePlanInfo(data.plan);
            }
            
            // Atualizar o header também se a função existir
            if (typeof updatePlanExpiry === 'function') {
                updatePlanExpiry();
            }
        } else {
            throw new Error(data.error || 'Erro ao carregar perfil');
        }
        
    } catch (error) {
        
        if (error.message === 'Não autenticado') {
            showNotification('Sessão expirada. Redirecionando para login...', 'error');
            setTimeout(() => {
                window.location.href = '/login';
            }, 1500);
        } else {
            showNotification('Erro ao carregar perfil: ' + error.message, 'error');
        }
    } finally {
        showLoading(false);
    }
}

/**
 * Atualizar exibição do perfil
 */
function updateProfileDisplay() {
    if (!currentUser) return;
    
    // Avatar e informações básicas
    const avatarInitials = document.getElementById('avatarInitials');
    const profileName = document.getElementById('profileName');
    const profileEmail = document.getElementById('profileEmail');
    const roleBadge = document.getElementById('roleBadge');
    
    if (avatarInitials) {
        const initials = (currentUser.name || 'U').split(' ')
            .map(n => n[0])
            .join('')
            .substring(0, 2)
            .toUpperCase();
        avatarInitials.textContent = initials;
    }
    
    if (profileName) {
        profileName.textContent = currentUser.name || 'Usuário';
    }
    
    if (profileEmail) {
        profileEmail.textContent = currentUser.email || '';
    }
    
    if (roleBadge) {
        roleBadge.textContent = isAdmin ? 'Administrador' : 'Revendedor';
        roleBadge.className = isAdmin ? 'role-badge admin' : 'role-badge';
    }
    
    // Mostrar/ocultar seção do plano
    const planSection = document.getElementById('planSection');
    if (planSection) {
        planSection.style.display = isAdmin ? 'none' : 'block';
    }
}

/**
 * Atualizar informações do plano
 */
function updatePlanInfo(planData) {
    const currentPlan = document.getElementById('currentPlan');
    const planStatus = document.getElementById('planStatus');
    const planExpiry = document.getElementById('planExpiry');
    const daysRemaining = document.getElementById('daysRemaining');
    const planBadge = document.getElementById('planBadge');
    
    // Nome do plano
    const planName = planData.name || 'Sem plano';
    if (currentPlan) {
        currentPlan.textContent = planName;
    }
    
    if (planBadge) {
        planBadge.textContent = planName;
    }
    
    // Status do plano
    if (planStatus) {
        const days = planData.days_remaining || 0;
        
        if (days <= 0) {
            planStatus.textContent = 'Vencido';
            planStatus.className = 'status-badge expired';
        } else if (planData.is_trial) {
            planStatus.textContent = 'Trial';
            planStatus.className = 'status-badge trial';
        } else {
            planStatus.textContent = 'Ativo';
            planStatus.className = 'status-badge active';
        }
    }
    
    // Data de vencimento
    if (planExpiry && planData.expires_at) {
        const date = new Date(planData.expires_at);
        planExpiry.textContent = date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    } else if (planExpiry) {
        planExpiry.textContent = 'Não definido';
    }
    
    // Dias restantes
    if (daysRemaining) {
        const days = planData.days_remaining || 0;
        
        if (days <= 0) {
            daysRemaining.textContent = `Vencido há ${Math.abs(days)} ${Math.abs(days) === 1 ? 'dia' : 'dias'}`;
            daysRemaining.className = 'days-badge danger';
        } else if (days <= 7) {
            daysRemaining.textContent = `${days} ${days === 1 ? 'dia' : 'dias'} restante${days === 1 ? '' : 's'}`;
            daysRemaining.className = 'days-badge warning';
        } else {
            daysRemaining.textContent = `${days} ${days === 1 ? 'dia' : 'dias'} restante${days === 1 ? '' : 's'}`;
            daysRemaining.className = 'days-badge';
        }
    }
}

/**
 * Preencher formulário com dados do usuário
 */
function populateForm() {
    if (!currentUser) return;
    
    const userName = document.getElementById('userName');
    const userEmail = document.getElementById('userEmail');
    const userPhone = document.getElementById('userPhone');
    const userCompany = document.getElementById('userCompany');
    
    if (userName) userName.value = currentUser.name || '';
    if (userEmail) userEmail.value = currentUser.email || '';
    if (userPhone) userPhone.value = currentUser.phone || '';
    if (userCompany) userCompany.value = currentUser.company || '';
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Formulário de perfil
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileSubmit);
    }
    
    // Formulário de senha
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', handlePasswordSubmit);
    }
    
    // Máscara para telefone
    const phoneInput = document.getElementById('userPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', formatPhoneNumber);
    }
}

/**
 * Configurar validação de senha
 */
function setupPasswordValidation() {
    const newPasswordInput = document.getElementById('newPassword');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', validatePassword);
    }
}

/**
 * Submeter formulário de perfil
 */
async function handleProfileSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const profileData = Object.fromEntries(formData);
    
    try {
        showLoading(true);
        
        const response = await fetch('/api-profile.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(profileData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Recarregar dados do banco de dados
            await loadUserProfile();
            showNotification('Perfil atualizado com sucesso!', 'success');
            
            // Atualizar o header também se a função existir
            if (typeof updatePlanExpiry === 'function') {
                updatePlanExpiry();
            }
        } else {
            throw new Error(data.error || 'Erro ao atualizar perfil');
        }
        
    } catch (error) {
        showNotification('Erro ao atualizar perfil: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

/**
 * Submeter formulário de senha
 */
async function handlePasswordSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const passwordData = Object.fromEntries(formData);
    
    // Validar senhas
    if (passwordData.new_password !== passwordData.confirm_password) {
        showNotification('As senhas não coincidem', 'error');
        return;
    }
    
    if (!isValidPassword(passwordData.new_password)) {
        showNotification('A nova senha não atende aos requisitos', 'error');
        return;
    }
    
    try {
        showLoading(true);
        
        const response = await fetch('/api-change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                current_password: passwordData.current_password,
                new_password: passwordData.new_password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Limpar formulário
            event.target.reset();
            showNotification('Senha alterada com sucesso!', 'success');
        } else {
            throw new Error(data.error || 'Erro ao alterar senha');
        }
        
    } catch (error) {
        showNotification('Erro ao alterar senha: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

/**
 * Alternar visibilidade da senha
 */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('.password-toggle');
    
    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
        `;
    } else {
        input.type = 'password';
        button.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        `;
    }
}

/**
 * Validar senha
 */
function validatePassword() {
    const password = document.getElementById('newPassword').value;
    
    const requirements = {
        'req-length': password.length >= 8,
        'req-uppercase': /[A-Z]/.test(password),
        'req-lowercase': /[a-z]/.test(password),
        'req-number': /\d/.test(password)
    };
    
    Object.entries(requirements).forEach(([id, valid]) => {
        const element = document.getElementById(id);
        if (element) {
            element.classList.toggle('valid', valid);
        }
    });
}

/**
 * Verificar se a senha é válida
 */
function isValidPassword(password) {
    return password.length >= 8 &&
           /[A-Z]/.test(password) &&
           /[a-z]/.test(password) &&
           /\d/.test(password);
}

/**
 * Formatar número de telefone
 */
function formatPhoneNumber(event) {
    let value = event.target.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        if (value.length <= 2) {
            value = value.replace(/(\d{0,2})/, '($1');
        } else if (value.length <= 7) {
            value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        } else {
            value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        }
    }
    
    event.target.value = value;
}

/**
 * Alterar avatar (placeholder)
 */
function changeAvatar() {
    showNotification('Funcionalidade de avatar em desenvolvimento', 'info');
}

/**
 * Mostrar/ocultar loading
 */
function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

/**
 * Mostrar notificação
 */
function showNotification(message, type = 'info') {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Adicionar estilos se não existirem
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
                padding: 1rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: slideIn 0.3s ease;
            }
            
            .notification-success {
                background: #dcfce7;
                color: #166534;
                border-left: 4px solid #10b981;
            }
            
            .notification-error {
                background: #fecaca;
                color: #991b1b;
                border-left: 4px solid #ef4444;
            }
            
            .notification-info {
                background: #dbeafe;
                color: #1e40af;
                border-left: 4px solid #3b82f6;
            }
            
            .notification-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
            }
            
            .notification-close {
                background: none;
                border: none;
                font-size: 1.2rem;
                cursor: pointer;
                opacity: 0.7;
            }
            
            .notification-close:hover {
                opacity: 1;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Adicionar ao DOM
    document.body.appendChild(notification);
    
    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}