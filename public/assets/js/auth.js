/**
 * Gerenciamento de autenticação
 */

// Configuração da API
const API_URL = window.location.origin;

// Elementos do DOM
const loginForm = document.getElementById('loginForm');
const messageDiv = document.getElementById('message');

// Event Listeners
if (loginForm) {
    loginForm.addEventListener('submit', handleLogin);
}

/**
 * Manipular login
 */
async function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    
    // Validação básica
    if (!email || !password) {
        showMessage('Preencha todos os campos', 'error');
        return;
    }
    
    // Loading state
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    hideMessage();
    
    try {
        const response = await fetch(`${API_URL}/api/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (response.ok && data.token) {
            // Salvar token
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            
            // Redirecionar para página salva ou dashboard
            const redirectTo = sessionStorage.getItem('redirectAfterLogin') || '/dashboard';
            sessionStorage.removeItem('redirectAfterLogin');
            
            showMessage('Login realizado com sucesso!', 'success');
            setTimeout(() => {
                window.location.href = redirectTo;
            }, 500);
        } else {
            showMessage(data.error || 'Erro ao fazer login', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showMessage('Erro ao conectar com o servidor', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
    }
}

/**
 * Mostrar mensagem
 */
function showMessage(text, type = 'info') {
    messageDiv.textContent = text;
    messageDiv.className = `message ${type} show`;
}

/**
 * Esconder mensagem
 */
function hideMessage() {
    messageDiv.className = 'message';
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
        await fetch(`${API_URL}/api/auth/logout`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });
    } catch (error) {
        console.error('Erro ao fazer logout:', error);
    }
    
    localStorage.removeItem('token');
    localStorage.removeItem('user');
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
