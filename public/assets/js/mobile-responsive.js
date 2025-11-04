/**
 * JavaScript Universal para Responsividade Mobile
 * Aplicado em todas as páginas do sistema
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos principais
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const searchBox = document.getElementById('searchBox');
    const searchIcon = document.getElementById('searchIcon');
    const searchInput = document.getElementById('searchInput');
    const notificationBtn = document.getElementById('notificationBtn');
    
    // Funções de controle da sidebar
    function openSidebar() {
        if (sidebar) sidebar.classList.add('active');
        if (sidebarOverlay) sidebarOverlay.classList.add('active');
        if (mobileMenuBtn) mobileMenuBtn.classList.add('active');
        document.body.classList.add('sidebar-open');
    }
    
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('active');
        if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        if (mobileMenuBtn) mobileMenuBtn.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    }
    
    function toggleSidebar() {
        if (sidebar && sidebar.classList.contains('active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }
    
    // Event listeners para mobile menu
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
        
        mobileMenuBtn.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    // Fechar sidebar ao clicar no overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('touchend', closeSidebar);
    }
    
    // Fechar sidebar ao pressionar ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });
    
    // Fechar sidebar ao clicar em links da navegação (mobile) - APENAS links que navegam
    if (sidebar) {
        // Apenas links diretos que realmente navegam (não têm submenu e têm href válido)
        const directNavLinks = sidebar.querySelectorAll('a.nav-item:not(.has-submenu)[href]:not([href="#"])');
        directNavLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    setTimeout(closeSidebar, 100);
                }
            });
        });

        // Links de submenu que realmente navegam
        const submenuLinks = sidebar.querySelectorAll('a.submenu-item[href]:not([href="#"])');
        submenuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    setTimeout(closeSidebar, 100);
                }
            });
        });
    }
    
    // Search Box Mobile
    function setupSearchBox() {
        if (!searchIcon || !searchBox || !searchInput) return;
        
        function performSearch() {
            if (searchInput.value.trim()) {
                // Redirecionar para página de clientes com busca
                window.location.href = '/clients?search=' + encodeURIComponent(searchInput.value);
            } else {
                searchInput.focus();
            }
        }
        
        searchIcon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            performSearch();
        });
        
        searchIcon.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            performSearch();
        });
        
        // Eventos do input
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        searchInput.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    setupSearchBox();
    
    // Notification Button
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Implementar modal de notificações ou redirecionamento
            alert('Você tem 3 notificações pendentes!\n\n• Cliente João Silva vence hoje\n• Pagamento recebido: R$ 150,00\n• Novo cliente cadastrado');
        });
    }
    
    // Garantir que sidebar inicie fechada no mobile
    function initializeMobileLayout() {
        if (window.innerWidth <= 768) {
            closeSidebar();
            // Forçar estilos inline para garantir funcionamento
            if (sidebar) {
                sidebar.style.transform = 'translateX(-100%)';
                sidebar.style.transition = 'transform 0.3s ease';
            }
        }
    }
    
    // Inicializar layout mobile
    initializeMobileLayout();
    
    // Redimensionamento da janela com debounce
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if (window.innerWidth <= 768) {
                // Mobile: garantir que sidebar esteja fechada
                closeSidebar();
                if (sidebar) {
                    sidebar.style.transform = 'translateX(-100%)';
                }
            } else {
                // Desktop: remover classes mobile e estilos inline
                if (sidebar) {
                    sidebar.classList.remove('active');
                    sidebar.style.transform = '';
                }
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                if (mobileMenuBtn) mobileMenuBtn.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        }, 100);
    });
    
    // Prevenir scroll horizontal em mobile
    function preventHorizontalScroll() {
        if (window.innerWidth <= 768) {
            document.body.style.overflowX = 'hidden';
            document.documentElement.style.overflowX = 'hidden';
        } else {
            document.body.style.overflowX = '';
            document.documentElement.style.overflowX = '';
        }
    }
    
    preventHorizontalScroll();
    window.addEventListener('resize', preventHorizontalScroll);
    
    // Melhorar performance em dispositivos touch
    if ('ontouchstart' in window) {
        document.body.classList.add('touch-device');
        
        // Adicionar suporte a swipe para fechar sidebar
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        if (sidebar) {
            sidebar.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                isDragging = true;
            }, { passive: true });
            
            sidebar.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                currentX = e.touches[0].clientX;
                const diffX = startX - currentX;
                
                // Se arrastar para a esquerda mais de 50px, fechar sidebar
                if (diffX > 50) {
                    closeSidebar();
                    isDragging = false;
                }
            }, { passive: true });
            
            sidebar.addEventListener('touchend', function() {
                isDragging = false;
            }, { passive: true });
        }
    }
    
    });

// Função global para logout
window.logout = async function() {
    if (confirm('Tem certeza que deseja sair?')) {
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
};