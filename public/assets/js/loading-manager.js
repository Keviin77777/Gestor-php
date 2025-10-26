/**
 * Loading Manager - Gerenciamento Centralizado de Loading
 * UltraGestor
 */

class LoadingManager {
    constructor() {
        this.activeLoadings = new Set();
        this.loadingContainer = null;
        this.init();
    }

    /**
     * Inicializar o gerenciador de loading
     */
    init() {
        // Criar container de loading global se não existir
        this.createGlobalContainer();
        
        // Adicionar estilos se não existirem
        this.addStyles();
    }

    /**
     * Criar container global de loading
     */
    createGlobalContainer() {
        if (document.getElementById('globalLoadingContainer')) {
            return;
        }

        const container = document.createElement('div');
        container.id = 'globalLoadingContainer';
        container.className = 'global-loading-container';
        document.body.appendChild(container);
        
        this.loadingContainer = container;
    }

    /**
     * Adicionar estilos CSS para o loading
     */
    addStyles() {
        if (document.getElementById('loading-manager-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'loading-manager-styles';
        style.textContent = `
            .global-loading-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(15, 23, 42, 0.8);
                backdrop-filter: blur(4px);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                transition: opacity 0.3s ease;
            }

            .global-loading-container.active {
                display: flex;
                opacity: 1;
            }

            .loading-content {
                background: var(--bg-primary);
                padding: 2rem;
                border-radius: var(--radius);
                text-align: center;
                box-shadow: var(--shadow-lg);
                border: 1px solid var(--border);
                min-width: 200px;
                max-width: 300px;
            }

            .loading-icon {
                width: 48px;
                height: 48px;
                margin: 0 auto;
                position: relative;
            }

            .loading-ring {
                position: absolute;
                width: 100%;
                height: 100%;
                border: 3px solid transparent;
                border-top: 3px solid var(--primary);
                border-radius: 50%;
                animation: loadingSpin 1.2s linear infinite;
            }

            .loading-ring:nth-child(2) {
                width: 80%;
                height: 80%;
                top: 10%;
                left: 10%;
                border-top-color: var(--primary-light);
                animation-delay: -0.4s;
            }

            .loading-ring:nth-child(3) {
                width: 60%;
                height: 60%;
                top: 20%;
                left: 20%;
                border-top-color: var(--primary);
                animation-delay: -0.8s;
            }

            @keyframes loadingSpin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .loading-title {
                font-size: 1.125rem;
                font-weight: 600;
                color: var(--text-primary);
                margin-bottom: 0.5rem;
            }

            .loading-message {
                font-size: 0.875rem;
                color: var(--text-secondary);
                margin: 0;
                line-height: 1.5;
            }

            /* Loading inline para containers específicos */
            .inline-loading {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 3rem 2rem;
                text-align: center;
                color: var(--text-secondary);
                min-height: 200px;
            }

            .inline-loading .loading-spinner {
                width: 48px;
                height: 48px;
                margin-bottom: 1rem;
                position: relative;
            }

            .inline-loading .spinner-ring {
                position: absolute;
                width: 100%;
                height: 100%;
                border: 2px solid transparent;
                border-top: 2px solid var(--primary);
                border-radius: 50%;
                animation: loadingSpin 1s linear infinite;
            }

            .inline-loading .spinner-ring:nth-child(2) {
                width: 75%;
                height: 75%;
                top: 12.5%;
                left: 12.5%;
                border-top-color: var(--primary-light);
                animation-delay: -0.3s;
            }

            .inline-loading .loading-text {
                font-size: 0.95rem;
                font-weight: 500;
                margin: 0;
                opacity: 0.9;
            }
        `;
        
        document.head.appendChild(style);
    }

    /**
     * Mostrar loading global
     * @param {string} title - Título do loading
     * @param {string} message - Mensagem do loading
     * @param {string} id - ID único para o loading
     */
    showGlobal(title = 'Carregando', message = 'Aguarde um momento...', id = 'default') {
        if (!this.loadingContainer) {
            this.createGlobalContainer();
        }

        // Adicionar à lista de loadings ativos
        this.activeLoadings.add(id);

        // Criar conteúdo do loading simples (sem título e mensagem)
        this.loadingContainer.innerHTML = `
            <div class="loading-content">
                <div class="loading-icon">
                    <div class="loading-ring"></div>
                    <div class="loading-ring"></div>
                    <div class="loading-ring"></div>
                </div>
            </div>
        `;

        // Mostrar container
        this.loadingContainer.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Esconder loading global
     * @param {string} id - ID do loading para remover
     */
    hideGlobal(id = 'default') {
        // Remover da lista de loadings ativos
        this.activeLoadings.delete(id);

        // Se não há mais loadings ativos, esconder container
        if (this.activeLoadings.size === 0) {
            if (this.loadingContainer) {
                this.loadingContainer.classList.remove('active');
                document.body.style.overflow = '';
                
                // Limpar conteúdo após animação
                setTimeout(() => {
                    if (this.loadingContainer && this.activeLoadings.size === 0) {
                        this.loadingContainer.innerHTML = '';
                    }
                }, 300);
            }
        }
    }

    /**
     * Mostrar loading inline em um container específico
     * @param {string|HTMLElement} container - Seletor ou elemento do container
     * @param {string} message - Mensagem do loading
     */
    showInline(container, message = 'Carregando...') {
        const element = typeof container === 'string' ? 
            document.querySelector(container) : container;
        
        if (!element) {
            console.warn('Container não encontrado para loading inline');
            return;
        }

        element.innerHTML = `
            <div class="inline-loading">
                <div class="loading-spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
                <p class="loading-text">${message}</p>
            </div>
        `;
    }

    /**
     * Esconder loading inline
     * @param {string|HTMLElement} container - Seletor ou elemento do container
     */
    hideInline(container) {
        const element = typeof container === 'string' ? 
            document.querySelector(container) : container;
        
        if (!element) {
            return;
        }

        const loadingElement = element.querySelector('.inline-loading');
        if (loadingElement) {
            loadingElement.remove();
        }
    }

    /**
     * Mostrar loading para planos especificamente
     */
    showPlansLoading() {
        this.showGlobal(
            'Carregando Planos',
            'Buscando os planos disponíveis...',
            'plans'
        );
    }

    /**
     * Esconder loading dos planos
     */
    hidePlansLoading() {
        this.hideGlobal('plans');
    }

    /**
     * Mostrar loading inline para planos
     */
    showPlansInlineLoading() {
        this.showInline('#plansGrid', 'Carregando planos...');
    }

    /**
     * Esconder loading inline dos planos
     */
    hidePlansInlineLoading() {
        this.hideInline('#plansGrid');
    }

    /**
     * Alias para showGlobal (backward compatibility)
     * @param {string} message - Mensagem do loading
     * @param {string} id - ID único para o loading
     */
    show(message = 'Carregando...', id = 'default') {
        this.showGlobal('Carregando', message, id);
    }

    /**
     * Alias para hideGlobal (backward compatibility)
     * @param {string} id - ID do loading para remover
     */
    hide(id = 'default') {
        this.hideGlobal(id);
    }

    /**
     * Wrapper para operações assíncronas com loading
     * @param {Function} asyncOperation - Função assíncrona
     * @param {Object} options - Opções do loading
     */
    async withLoading(asyncOperation, options = {}) {
        const {
            type = 'global', // 'global' ou 'inline'
            container = null,
            title = 'Processando',
            message = 'Aguarde...',
            id = 'operation'
        } = options;

        try {
            if (type === 'global') {
                this.showGlobal(title, message, id);
            } else if (type === 'inline' && container) {
                this.showInline(container, message);
            }

            const result = await asyncOperation();
            return result;
        } finally {
            if (type === 'global') {
                this.hideGlobal(id);
            } else if (type === 'inline' && container) {
                this.hideInline(container);
            }
        }
    }
}

// Criar instância global
window.LoadingManager = new LoadingManager();

// Exportar para uso em módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LoadingManager;
}