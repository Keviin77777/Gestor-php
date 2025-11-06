/**
 * Gerenciamento de autenticação
 */

// Configuração da API
const API_URL = window.location.origin;

// Chaves para localStorage - "Lembre-me"
const REMEMBER_KEY = 'ultragestor_remember';
const CREDENTIALS_KEY = 'ultragestor_credentials';

// Elementos do DOM
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const messageDiv = document.getElementById('message');

// Event Listeners
if (loginForm) {
    loginForm.addEventListener('submit', handleLogin);
    
    // Carregar credenciais salvas ao carregar a página
    loadSavedCredentials();
    
    // Auto-login se "Lembre-me" estiver ativo
    checkAutoLogin();
}

if (registerForm) {
    registerForm.addEventListener('submit', handleRegister);
}

/**
 * Manipular login
 */
async function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Validação básica
    if (!email || !password) {
        showNotification('Por favor, preencha todos os campos obrigatórios', 'error');
        return;
    }
    
    // Validação de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showNotification('Por favor, insira um email válido', 'error');
        return;
    }
    
    // Loading state
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
    hideMessage();
    
    try {
        const response = await fetch('/api-auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                action: 'login',
                email, 
                password 
            })
        });
        
        const text = await response.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            showNotification('Erro na comunicação com o servidor. Tente novamente.', 'error');
            return;
        }
        
        if (response.ok && data.token) {
            // Salvar token
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            
            // Gerenciar "Lembre-me"
            handleRememberMe(email, password);
            
            // Mostrar mensagem de sucesso
            showNotification('Login realizado com sucesso! Redirecionando...', 'success');
            
            // Redirecionar para página salva ou dashboard
            const redirectTo = sessionStorage.getItem('redirectAfterLogin') || '/dashboard';
            sessionStorage.removeItem('redirectAfterLogin');
            
            setTimeout(() => {
                window.location.href = redirectTo;
            }, 1500);
        } else {
            // Mensagens de erro mais específicas
            let errorMessage = 'Erro ao fazer login';
            
            if (data.error) {
                if (data.error.includes('credenciais') || data.error.includes('inválid') || data.error.includes('incorret')) {
                    errorMessage = 'Email ou senha incorretos. Verifique suas credenciais.';
                } else if (data.error.includes('não encontrado') || data.error.includes('not found')) {
                    errorMessage = 'Usuário não encontrado. Verifique o email informado.';
                } else if (data.error.includes('bloqueado') || data.error.includes('suspenso')) {
                    errorMessage = 'Conta bloqueada. Entre em contato com o suporte.';
                } else if (data.error.includes('expirado') || data.error.includes('vencido')) {
                    errorMessage = 'Acesso expirado. Renove sua assinatura para continuar.';
                } else {
                    errorMessage = data.error;
                }
            }
            
            showNotification(errorMessage, 'error');
        }
    } catch (error) {
        showNotification('Erro de conexão. Verifique sua internet e tente novamente.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        submitBtn.innerHTML = originalText || 'Entrar';
    }
}

/**
 * Manipular registro
 */
async function handleRegister(e) {
    e.preventDefault();
    
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const whatsapp = document.getElementById('whatsapp').value;
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const terms = document.getElementById('terms').checked;
    const planId = document.getElementById('plan_id').value;
    const submitBtn = registerForm.querySelector('button[type="submit"]');
    
    // Validação básica
    if (!name || !email || !whatsapp || !password || !passwordConfirm) {
        showMessage('Preencha todos os campos obrigatórios', 'error');
        return;
    }
    
    if (password !== passwordConfirm) {
        showMessage('As senhas não coincidem', 'error');
        return;
    }
    
    if (password.length < 6) {
        showMessage('A senha deve ter no mínimo 6 caracteres', 'error');
        return;
    }
    
    if (!terms) {
        showMessage('Você deve aceitar os termos de uso', 'error');
        return;
    }
    
    // Loading state
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle></svg> Criando conta...';
    hideMessage();
    
    try {
        const response = await fetch('/api-auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                action: 'register',
                name, 
                email, 
                whatsapp: whatsapp || null,
                password,
                plan_id: planId || null
            })
        });
        
        const text = await response.text();
        let data;
        
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            showMessage('Erro na resposta do servidor', 'error');
            return;
        }
        
        if (response.ok && data.success && data.token) {
            // Salvar token
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            
            showMessage(data.message || 'Conta criada com sucesso!', 'success');
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 1000);
        } else {
            const errorMsg = data.error || data.message || 'Erro ao criar conta';
            showMessage(errorMsg, 'error');
        }
    } catch (error) {
        console.error('Register error:', error);
        showMessage('Erro ao conectar com o servidor', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        submitBtn.innerHTML = originalText;
    }
}

/**
 * Mostrar mensagem
 */
function showMessage(text, type = 'info', autoHide = true) {
    if (!messageDiv) return;
    
    messageDiv.textContent = text;
    messageDiv.className = `message ${type}`;
    
    // Forçar reflow para garantir que a animação funcione
    messageDiv.offsetHeight;
    
    // Adicionar classe show para animação
    messageDiv.classList.add('show');
    
    // Auto-ocultar mensagens de erro após 5 segundos
    if (autoHide && type === 'error') {
        setTimeout(() => {
            hideMessage();
        }, 5000);
    }
    
    // Auto-ocultar mensagens de info após 3 segundos
    if (autoHide && type === 'info') {
        setTimeout(() => {
            hideMessage();
        }, 3000);
    }
}

/**
 * Esconder mensagem
 */
function hideMessage() {
    if (!messageDiv) return;
    
    messageDiv.classList.remove('show');
    
    // Remover completamente após a animação
    setTimeout(() => {
        messageDiv.className = 'message';
        messageDiv.textContent = '';
    }, 300);
}

/**
 * Verificar se está autenticado
 */
function isAuthenticated() {
    const token = localStorage.getItem('token');
    return !!token;
}

/**
 * Obter token
 */
function getToken() {
    return localStorage.getItem('token');
}

/**
 * Obter usuário
 */
function getUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
}

/**
 * Fazer logout
 */
async function logout() {
    try {
        // Chamar API de logout para destruir sessão
        await fetch('/api-auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'logout'
            })
        });
    } catch (error) {
        console.error('Erro no logout:', error);
    }
    
    // Limpar dados locais independente da resposta da API
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    // Limpar credenciais salvas para impedir auto-login
    clearSavedCredentials();
    
    // Redirecionar para login
    window.location.href = '/login';
}

// Verificar autenticação em páginas protegidas
const publicPages = ['/login', '/register', '/test.php', '/test.html', '/info.php', '/phpinfo.php'];
const currentPath = window.location.pathname;

if (!publicPages.includes(currentPath) && !currentPath.startsWith('/api/')) {
    if (!isAuthenticated()) {
        // Salvar URL de destino
        sessionStorage.setItem('redirectAfterLogin', currentPath);
        window.location.href = '/login';
    }
}
/**
 * Sistema de Toast Notifications
 */
function createToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
}

function showToast(message, type = 'info', duration = 5000) {
    const container = createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✅',
        error: '❌',
        info: 'ℹ️',
        warning: '⚠️'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || icons.info}</div>
        <div class="toast-content">${message}</div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Auto remove
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    }, duration);
    
    return toast;
}

// Função melhorada para mostrar mensagens
function showNotification(message, type = 'info') {
    // Mostrar tanto no elemento message quanto no toast
    showMessage(message, type, false);
    showToast(message, type);
}/**
 * Funcionalidade "Lembre-me"
 */

/**
 * Salvar credenciais no localStorage
 */
function saveCredentials(email, password) {
    const credentials = {
        email: email,
        password: btoa(password), // Codificar senha em base64 (não é seguro, mas melhor que texto puro)
        timestamp: Date.now(),
        userAgent: navigator.userAgent.substring(0, 50), // Verificação básica de dispositivo
        domain: window.location.hostname
    };
    
    localStorage.setItem(CREDENTIALS_KEY, JSON.stringify(credentials));
    localStorage.setItem(REMEMBER_KEY, 'true');
}

/**
 * Carregar credenciais salvas
 */
function loadSavedCredentials() {
    const rememberMe = localStorage.getItem(REMEMBER_KEY);
    const credentialsStr = localStorage.getItem(CREDENTIALS_KEY);
    
    if (rememberMe === 'true' && credentialsStr) {
        try {
            const credentials = JSON.parse(credentialsStr);
            
            // Verificar se as credenciais não são muito antigas (30 dias)
            const thirtyDaysAgo = Date.now() - (30 * 24 * 60 * 60 * 1000);
            if (credentials.timestamp < thirtyDaysAgo) {
                clearSavedCredentials();
                return;
            }
            
            // Preencher campos
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            const rememberCheckbox = document.getElementById('remember');
            
            if (emailField && passwordField && rememberCheckbox) {
                emailField.value = credentials.email;
                passwordField.value = atob(credentials.password); // Decodificar senha
                rememberCheckbox.checked = true;
            }
            
        } catch (error) {
            clearSavedCredentials();
        }
    }
}

/**
 * Verificar se deve fazer auto-login
 */
function checkAutoLogin() {
    // Auto-login desabilitado - usuário deve fazer login manualmente
    return;
}



/**
 * Limpar credenciais salvas
 */
function clearSavedCredentials() {
    localStorage.removeItem(CREDENTIALS_KEY);
    localStorage.removeItem(REMEMBER_KEY);
    
    const rememberCheckbox = document.getElementById('remember');
    if (rememberCheckbox) {
        rememberCheckbox.checked = false;
    }
}

/**
 * Verificar se "Lembre-me" está marcado e salvar credenciais
 */
function handleRememberMe(email, password) {
    const rememberCheckbox = document.getElementById('remember');
    
    if (rememberCheckbox && rememberCheckbox.checked) {
        saveCredentials(email, password);
    } else {
        clearSavedCredentials();
    }
}/**

 * Adicionar listener para o checkbox "Lembre-me"
 */
document.addEventListener('DOMContentLoaded', function() {
    const rememberCheckbox = document.getElementById('remember');
    
    if (rememberCheckbox) {
        rememberCheckbox.addEventListener('change', function() {
            if (!this.checked) {
                // Se desmarcou "Lembre-me", limpar credenciais salvas
                clearSavedCredentials();
            }
        });
    }
});

/**
 * I
nicialização e verificações
 */
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se todos os elementos necessários existem
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember');
    const loginForm = document.getElementById('loginForm');
    
    if (emailField && passwordField && rememberCheckbox && loginForm) {
        // Carregar credenciais salvas
        loadSavedCredentials();
        
        // Auto-login desabilitado
    }
});