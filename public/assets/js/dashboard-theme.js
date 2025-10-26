/**
 * Dashboard Theme - JavaScript Limpo
 */

/**
 * Alternar tema (igual ao clients.js)
 */
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

/**
 * Carregar tema salvo
 */
function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
}

/**
 * Configurar event listeners
 */
function setupThemeEvents() {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleTheme();
        });
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Carregar tema salvo
    loadTheme();
    
    // Configurar eventos após um delay
    setTimeout(() => {
        setupThemeEvents();
    }, 1000);
});