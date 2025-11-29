/**
 * Sistema de Loading Global
 * Uso: GlobalLoading.show('Mensagem', 'Submensagem')
 *      GlobalLoading.hide()
 */

const GlobalLoading = {
    element: null,
    
    /**
     * Inicializar loading
     */
    init() {
        if (this.element) return;
        
        // Criar elemento de loading
        this.element = document.createElement('div');
        this.element.className = 'global-loading';
        this.element.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner">
                    <div class="loading-spinner-ring"></div>
                    <div class="loading-spinner-ring"></div>
                    <div class="loading-spinner-ring"></div>
                </div>
                <div class="loading-text">Carregando...</div>
                <div class="loading-subtext">Por favor, aguarde</div>
                <div class="loading-progress">
                    <div class="loading-progress-bar"></div>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.element);
    },
    
    /**
     * Mostrar loading
     */
    show(text = 'Carregando...', subtext = 'Por favor, aguarde', type = 'default') {
        this.init();
        
        // Atualizar textos
        const textElement = this.element.querySelector('.loading-text');
        const subtextElement = this.element.querySelector('.loading-subtext');
        
        if (textElement) textElement.textContent = text;
        if (subtextElement) subtextElement.textContent = subtext;
        
        // Aplicar tipo (success, error, default)
        this.element.className = 'global-loading';
        if (type !== 'default') {
            this.element.classList.add(type);
        }
        
        // Mostrar
        setTimeout(() => {
            this.element.classList.add('active');
        }, 10);
        
        // Bloquear scroll
        document.body.style.overflow = 'hidden';
    },
    
    /**
     * Esconder loading
     */
    hide() {
        if (!this.element) return;
        
        this.element.classList.remove('active');
        
        // Desbloquear scroll
        setTimeout(() => {
            document.body.style.overflow = '';
        }, 300);
    },
    
    /**
     * Mostrar loading com contador
     */
    showWithCounter(text, current, total) {
        this.show(text, `Processando ${current} de ${total}`);
    },
    
    /**
     * Mostrar loading de sucesso
     */
    showSuccess(text = 'Sucesso!', subtext = 'Operação concluída') {
        this.show(text, subtext, 'success');
        
        // Auto-hide após 2 segundos
        setTimeout(() => {
            this.hide();
        }, 2000);
    },
    
    /**
     * Mostrar loading de erro
     */
    showError(text = 'Erro!', subtext = 'Algo deu errado') {
        this.show(text, subtext, 'error');
        
        // Auto-hide após 3 segundos
        setTimeout(() => {
            this.hide();
        }, 3000);
    }
};

// Exportar para uso global ANTES de inicializar
window.GlobalLoading = GlobalLoading;

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        GlobalLoading.init();
    });
} else {
    // Se o DOM já está pronto, inicializar imediatamente
    GlobalLoading.init();
}

// Garantir que está disponível globalmente
if (typeof window !== 'undefined') {
    window.GlobalLoading = GlobalLoading;
}
