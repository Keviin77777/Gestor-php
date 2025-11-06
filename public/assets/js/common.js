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
/**

 * Correção específica para centralização do dropdown do perfil em mobile
 */
function fixProfileDropdownPosition() {
    const dropdown = document.getElementById('userDropdownMenu');
    if (!dropdown) return;
    
    // Função para centralizar o dropdown
    function centerDropdown() {
        if (window.innerWidth <= 768) {
            // Remover estilos inline que possam interferir
            dropdown.style.left = '';
            dropdown.style.right = '';
            dropdown.style.transform = '';
            
            // Aplicar centralização via CSS
            dropdown.style.position = 'fixed';
            dropdown.style.left = '50%';
            dropdown.style.transform = 'translateX(-50%)';
            dropdown.style.width = 'calc(100vw - 2rem)';
            dropdown.style.maxWidth = '320px';
            dropdown.style.top = window.innerWidth <= 480 ? '60px' : '70px';
            dropdown.style.zIndex = '1000';
        }
    }
    
    // Observar quando o dropdown é aberto
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                if (dropdown.classList.contains('active')) {
                    setTimeout(centerDropdown, 10);
                }
            }
        });
    });
    
    observer.observe(dropdown, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Aplicar correção quando a tela for redimensionada
    window.addEventListener('resize', () => {
        if (dropdown.classList.contains('active')) {
            centerDropdown();
        }
    });
    
    // Aplicar correção inicial se o dropdown já estiver ativo
    if (dropdown.classList.contains('active')) {
        centerDropdown();
    }
}

// Inicializar correção do dropdown do perfil
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fixProfileDropdownPosition);
} else {
    fixProfileDropdownPosition();
}

// Também executar após um pequeno delay
setTimeout(fixProfileDropdownPosition, 500);

/**
 * Correção específica para o menu mobile em Android
 */
function fixMobileMenuAndroid() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (!mobileMenuBtn || !sidebar) return;
    
    // Função para toggle do sidebar
    function toggleSidebar(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isActive = sidebar.classList.contains('active');
        
        if (isActive) {
            // Fechar sidebar
            sidebar.classList.remove('active');
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('active');
            }
            document.body.classList.remove('sidebar-open');
        } else {
            // Abrir sidebar
            sidebar.classList.add('active');
            if (sidebarOverlay) {
                sidebarOverlay.classList.add('active');
            }
            document.body.classList.add('sidebar-open');
        }
    }
    
    // Remover listeners existentes
    mobileMenuBtn.removeEventListener('click', toggleSidebar);
    
    // Adicionar listener
    mobileMenuBtn.addEventListener('click', toggleSidebar);
    mobileMenuBtn.addEventListener('touchstart', toggleSidebar);
    
    // Fechar sidebar ao clicar no overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
    }
    
    // Garantir que o sidebar esteja fechado inicialmente em mobile
    if (window.innerWidth <= 768) {
        sidebar.classList.remove('active');
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('active');
        }
        document.body.classList.remove('sidebar-open');
    }
}

// Inicializar correção do menu mobile
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fixMobileMenuAndroid);
} else {
    fixMobileMenuAndroid();
}

// Executar após delay para garantir que tudo foi carregado
setTimeout(fixMobileMenuAndroid, 1000);