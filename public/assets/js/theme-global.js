/**
 * Tema Global - Compartilhado entre todas as páginas
 */

/**
 * Carregar tema salvo
 */
function loadGlobalTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const html = document.documentElement;
    const body = document.body;
    
    // Aplicar tema
    html.setAttribute('data-theme', savedTheme);
    body.setAttribute('data-theme', savedTheme);
    
    // Aplicar classes também
    if (savedTheme === 'dark') {
        body.classList.add('dark-theme');
        html.classList.add('dark-theme');
    } else {
        body.classList.remove('dark-theme');
        html.classList.remove('dark-theme');
    }
    
    return savedTheme;
}

/**
 * Alternar tema global
 */
function toggleGlobalTheme() {
    const html = document.documentElement;
    const body = document.body;
    const currentTheme = html.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    // Aplicar tema no HTML e BODY
    html.setAttribute('data-theme', newTheme);
    body.setAttribute('data-theme', newTheme);
    
    // Aplicar/remover classes CSS também
    if (newTheme === 'dark') {
        body.classList.add('dark-theme');
        html.classList.add('dark-theme');
    } else {
        body.classList.remove('dark-theme');
        html.classList.remove('dark-theme');
    }
    
    localStorage.setItem('theme', newTheme);

    // Forçar re-render do CSS
    const allElements = document.querySelectorAll('*');
    allElements.forEach(el => {
        if (el.style) {
            el.style.transition = 'all 0.3s ease';
        }
    });

    // Forçar repaint
    setTimeout(() => {
        body.style.display = 'none';
        body.offsetHeight; // trigger reflow
        body.style.display = '';
        
        // Disparar evento de resize para forçar re-render
        window.dispatchEvent(new Event('resize'));
    }, 50);
    
    // Disparar evento customizado para outras páginas
    window.dispatchEvent(new CustomEvent('themeChanged', { 
        detail: { theme: newTheme } 
    }));
    
    return newTheme;
}

/**
 * Configurar event listener global do tema
 */
function setupGlobalTheme() {
    // Carregar tema salvo primeiro
    loadGlobalTheme();
    
    // Configurar botão de tema se existir
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        // Remover listeners existentes
        themeToggle.replaceWith(themeToggle.cloneNode(true));
        
        // Adicionar novo listener
        const newThemeToggle = document.getElementById('themeToggle');
        newThemeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleGlobalTheme();
        });
    }
    
    // Escutar mudanças de tema de outras páginas
    window.addEventListener('themeChanged', function(e) {
        const newTheme = e.detail.theme;
        document.documentElement.setAttribute('data-theme', newTheme);
    });
}

// Auto-inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Carregar tema imediatamente
    loadGlobalTheme();
    
    // Configurar eventos após delay
    setTimeout(() => {
        setupGlobalTheme();
    }, 500);
});