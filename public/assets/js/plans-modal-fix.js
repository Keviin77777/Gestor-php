/**
 * Correção específica para o modal de planos de clientes
 * Garante que todos os campos sejam visíveis em mobile
 */

// Função principal para corrigir o modal de planos
function fixPlansModal() {
    const modal = document.getElementById('planModal');
    if (!modal) return;

    // Interceptar ambas as funções que abrem o modal
    const originalOpenPlanModalGlobal = window.openPlanModalGlobal;
    const originalOpenPlanModal = window.openPlanModal;
    
    function applyModalFixes() {
        setTimeout(() => {
            const modal = document.getElementById('planModal');
            if (modal && modal.classList.contains('active')) {
                // Forçar scroll para o topo
                modal.scrollTop = 0;
                document.body.scrollTop = 0;
                document.documentElement.scrollTop = 0;
                
                // Garantir que o modal-body também esteja no topo
                const modalBody = modal.querySelector('.modern-modal-body');
                if (modalBody) {
                    modalBody.scrollTop = 0;
                }
                
                // Em mobile, garantir que o primeiro campo seja visível
                if (window.innerWidth <= 768) {
                    const firstField = modal.querySelector('#planServer');
                    if (firstField) {
                        setTimeout(() => {
                            firstField.scrollIntoView({ 
                                behavior: 'instant', 
                                block: 'start' 
                            });
                        }, 100);
                    }
                }
            }
        }, 50);
    }
    
    if (originalOpenPlanModalGlobal) {
        window.openPlanModalGlobal = function() {
            originalOpenPlanModalGlobal.apply(this, arguments);
            applyModalFixes();
        };
    }
    
    if (originalOpenPlanModal) {
        window.openPlanModal = function() {
            originalOpenPlanModal.apply(this, arguments);
            applyModalFixes();
        };
    }
}

// CSS específico para garantir visibilidade
function addPlansModalFixCSS() {
    const style = document.createElement('style');
    style.id = 'plans-modal-fix-css';
    style.textContent = `
        @media (max-width: 768px) {
            #planModal .modern-modal-body {
                padding-top: 1rem !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                max-height: calc(100vh - 140px) !important;
            }
            
            #planModal .modern-form-group:first-child {
                margin-top: 0.5rem !important;
                padding-top: 0 !important;
            }
            
            #planModal .modern-form-grid {
                margin-top: 0 !important;
            }
            
            #planModal #planServer {
                scroll-margin-top: 1rem !important;
            }
            
            /* Garantir que o modal seja sempre visível */
            #planModal.active {
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        }
    `;
    
    // Remover estilo anterior se existir
    const existingStyle = document.getElementById('plans-modal-fix-css');
    if (existingStyle) {
        existingStyle.remove();
    }
    
    document.head.appendChild(style);
}

// Interceptar também as funções de fechar modal para limpar scroll
function interceptClosePlansModal() {
    const originalClosePlanModalGlobal = window.closePlanModalGlobal;
    const originalClosePlanModal = window.closePlanModal;
    
    function restoreBodyScroll() {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }
    
    if (originalClosePlanModalGlobal) {
        window.closePlanModalGlobal = function() {
            restoreBodyScroll();
            originalClosePlanModalGlobal.apply(this, arguments);
        };
    }
    
    if (originalClosePlanModal) {
        window.closePlanModal = function() {
            restoreBodyScroll();
            originalClosePlanModal.apply(this, arguments);
        };
    }
}

// Adicionar controle de scroll do body
function addPlansBodyScrollControl() {
    const modal = document.getElementById('planModal');
    if (!modal) return;
    
    // Observar mudanças na classe do modal
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                if (modal.classList.contains('active')) {
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
function initPlansModalFix() {
    fixPlansModal();
    addPlansModalFixCSS();
    interceptClosePlansModal();
    addPlansBodyScrollControl();
}

// Executar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlansModalFix);
} else {
    initPlansModalFix();
}

// Também executar após um delay para garantir que tudo foi carregado
setTimeout(initPlansModalFix, 1000);