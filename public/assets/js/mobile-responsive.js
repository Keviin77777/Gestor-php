/**
 * JavaScript Universal para Responsividade Mobile
 * Aplicado em todas as páginas do sistema
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando responsividade mobile universal...');
    
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
        console.log('Abrindo sidebar mobile...');
        if (sidebar) sidebar.classList.add('active');
        if (sidebarOverlay) sidebarOverlay.classList.add('active');
        if (mobileMenuBtn) mobileMenuBtn.classList.add('active');
        document.body.classList.add('sidebar-open');
    }
    
    function closeSidebar() {
        console.log('Fechando sidebar mobile...');
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
            console.log('Mobile menu button clicado');
            toggleSidebar();
        });
        
        mobileMenuBtn.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Mobile menu touch end');
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
        
        console.log('Configurando search box mobile...');
        
        function performSearch() {
            if (searchInput.value.trim()) {
                console.log('Fazendo busca por:', searchInput.value);
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
            console.log('Notificações clicadas');
            
            // Implementar modal de notificações ou redirecionamento
            alert('Você tem 3 notificações pendentes!\n\n• Cliente João Silva vence hoje\n• Pagamento recebido: R$ 150,00\n• Novo cliente cadastrado');
        });
    }
    
    // Garantir que sidebar inicie fechada no mobile
    if (window.innerWidth <= 768) {
        closeSidebar();
    }
    
    // Redimensionamento da janela
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            // Mobile: garantir que sidebar esteja fechada
            closeSidebar();
        } else {
            // Desktop: remover classes mobile
            if (sidebar) sidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            if (mobileMenuBtn) mobileMenuBtn.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    });
    
    // Forçar sidebar fechada imediatamente no mobile
    if (window.innerWidth <= 768) {
        if (sidebar) {
            sidebar.classList.remove('active');
            sidebar.style.transform = 'translateX(-100%)';
        }
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('active');
        }
        if (mobileMenuBtn) {
            mobileMenuBtn.classList.remove('active');
        }
        document.body.classList.remove('sidebar-open');
    }
    
    console.log('Responsividade mobile universal inicializada com sucesso!');
});

// Função global para logout
window.logout = function() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/login';
};