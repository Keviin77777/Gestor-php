/**
 * Correção específica para o modal de planos
 * Garante que todos os campos sejam visíveis em mobile
 */

// Função principal para corrigir o modal de planos
function fixPlanModal() {
    const modal = document.getElementById('planModal');
    if (!modal) return;

    // Interceptar quando o modal for aberto
    const originalOpenModal = window.openModal;
    
    if (originalOpenModal) {
        window.openModal = function() {
            // Chamar a função original
            originalOpenModal.apply(this, arguments);
            
            // Aplicar correções após um pequeno delay
            setTimeout(() => {
                const modal = document.getElementById('planModal');
                if (modal && modal.classList.contains('show')) {
                    // Forçar scroll para o topo
                    modal.scrollTop = 0;
                    document.body.scrollTop = 0;
                    document.documentElement.scrollTop = 0;
                    
                    // Garantir que o modal-body também esteja no topo
                    const modalBody = modal.querySelector('.modal-body');
                    if (modalBody) {
                        modalBody.scrollTop = 0;
                    }
                    
                    // Em mobile, focar no primeiro campo
                    if (window.innerWidth <= 768) {
                        const firstField = modal.querySelector('#planName');
                        if (firstField) {
                            setTimeout(() => {
                                firstField.scrollIntoView({ 
                                    behavior: 'instant', 
                                    block: 'start' 
                                });
                                // Não focar automaticamente para não abrir teclado
                            }, 100);
                        }
                    }
                }
            }, 50);
        };
    }
}

// CSS específico para garantir visibilidade
function addPlanModalFixCSS() {
    const style = document.createElement('style');
    style.id = 'plan-modal-fix-css';
    style.textContent = `
        @media (max-width: 768px) {
            #planModal .modal-body {
                padding-top: 1rem !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                max-height: calc(100vh - 140px) !important;
            }
            
            #planModal .form-group:first-child {
                margin-top: 0.5rem !important;
                padding-top: 0 !important;
            }
            
            #planModal #planName {
                scroll-margin-top: 1rem !important;
            }
            
            /* Garantir que o modal seja sempre visível */
            #planModal.show {
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        }
    `;
    
    // Remover estilo anterior se existir
    const existingStyle = document.getElementById('plan-modal-fix-css');
    if (existingStyle) {
        existingStyle.remove();
    }
    
    document.head.appendChild(style);
}

// Interceptar também a função closeModal para limpar scroll
function interceptCloseModal() {
    const originalCloseModal = window.closeModal;
    
    if (originalCloseModal) {
        window.closeModal = function() {
            // Restaurar scroll do body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            
            // Chamar a função original
            originalCloseModal.apply(this, arguments);
        };
    }
}

// Adicionar controle de scroll do body
function addBodyScrollControl() {
    const modal = document.getElementById('planModal');
    if (!modal) return;
    
    // Observar mudanças na classe do modal
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                if (modal.classList.contains('show')) {
                    // Modal aberto - prevenir scroll do body
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                } else {
                    // Modal fechado - restaurar scroll do body
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                }
            }
        });
    });
    
    observer.observe(modal, {
        attributes: true,
        attributeFilter: ['class']
    });
}

// Inicializar quando o DOM estiver pronto
function initPlanModalFix() {
    fixPlanModal();
    addPlanModalFixCSS();
    interceptCloseModal();
    addBodyScrollControl();
}

// Executar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlanModalFix);
} else {
    initPlanModalFix();
}

// Também executar após um delay para garantir que tudo foi carregado
setTimeout(initPlanModalFix, 1000);