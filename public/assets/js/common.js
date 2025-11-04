/**
 * Funções comuns para todas as páginas
 */

// Garantir que as funções estejam disponíveis globalmente
window.toggleSubmenu = toggleSubmenu;
window.logout = logout;

/**
 * Toggle submenu - função universal
 */
function toggleSubmenu(event, submenuId) {
    event.preventDefault();
    event.stopPropagation();
    
    const submenu = document.getElementById(submenuId);
    if (!submenu) {
        return;
    }
    
    const navGroup = submenu.parentElement;
    const navItem = navGroup?.querySelector('.nav-item.has-submenu');
    
    if (!navGroup || !navItem) {
        return;
    }
    
    // Toggle do submenu atual
    if (submenu.classList.contains('expanded')) {
        submenu.classList.remove('expanded');
        navItem.classList.remove('active');
        navGroup.classList.remove('expanded'); // Remover classe do grupo
    } else {
        // Fechar outros submenus primeiro
        document.querySelectorAll('.submenu.expanded').forEach(menu => {
            if (menu !== submenu) {
                menu.classList.remove('expanded');
                const parentNavGroup = menu.parentElement;
                const parentNavItem = parentNavGroup?.querySelector('.nav-item.has-submenu');
                if (parentNavItem) {
                    parentNavItem.classList.remove('active');
                }
                if (parentNavGroup) {
                    parentNavGroup.classList.remove('expanded'); // Remover classe do grupo
                }
            }
        });
        
        // Abrir o submenu atual
        submenu.classList.add('expanded');
        navItem.classList.add('active');
        navGroup.classList.add('expanded'); // Adicionar classe ao grupo
    }
}

/**
 * Logout function
 */
async function logout() {
    if (confirm('Tem certeza que deseja sair?')) {
        try {
            // Chamar API de logout para destruir sessão
            const response = await fetch('/api-auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'logout'
                })
            });
            
            // Limpar dados locais independente da resposta da API
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.clear();
            
            // Redirecionar para login
            window.location.href = '/login';
        } catch (error) {
            console.error('Erro no logout:', error);
            // Mesmo com erro, limpar dados locais e redirecionar
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.clear();
            window.location.href = '/login';
        }
    }
}

/**
 * Função para mostrar notificações
 */
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer') || document.body;
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
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
    
    // Auto remove após 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}