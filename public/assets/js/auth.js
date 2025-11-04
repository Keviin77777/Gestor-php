/**
 * Gerenciamento de autenticação
 */

// Configuração da API
const API_URL = window.location.origin;

// Elementos do DOM
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const messageDiv = document.getElementById('message');

// Event Listeners
if (loginForm) {
    loginForm.addEventListener('submit', handleLogin);
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
        
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        const text = await response.text();
        console.log('Response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            showMessage('Erro na resposta do servidor', 'error');
            return;
        }
        
        console.log('Response data:', data);
        
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
        console.error('Login error:', error);
        showMessage('Erro ao conectar com o servidor', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
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
