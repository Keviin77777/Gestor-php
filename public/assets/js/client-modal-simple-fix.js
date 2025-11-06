/**
 * Correção simples e direta para o modal de clientes
 * Foca apenas em garantir que todos os campos sejam visíveis
 */

// Função principal para corrigir o modal
function fixClientModal() {
    const modal = document.getElementById('clientModal');
    if (!modal) return;

    // Interceptar quando o modal for aberto
    const originalOpenClientModal = window.openClientModal;
    
    window.openClientModal = function() {
        // Chamar a função original
        if (originalOpenClientModal) {
            originalOpenClientModal.apply(this, arguments);
        }
        
        // Aplicar correções após um pequeno delay
        setTimeout(() => {
            const modal = document.getElementById('clientModal');
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
                
                // Em mobile, apenas garantir que o modal esteja no topo
                if (window.innerWidth <= 768) {
                    // Não forçar scroll para campo específico, deixar o usuário navegar
                    setTimeout(() => {
                        // Apenas garantir que o modal-body esteja no topo
                        const modalBody = modal.querySelector('.modern-modal-body');
                        if (modalBody) {
                            modalBody.scrollTop = 0;
                        }
                    }, 100);
                }
            }
        }, 50);
    };
}

// CSS específico para garantir visibilidade
function addFixCSS() {
    const style = document.createElement('style');
    style.textContent = `
        @media (max-width: 768px) {
            #clientModal .modern-modal-body {
                padding-top: 1rem !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                max-height: calc(100vh - 140px) !important;
            }
            
            #clientModal .modern-form-group:first-child {
                margin-top: 0.5rem !important;
                padding-top: 0 !important;
            }
            
            #clientModal .modern-form-grid {
                margin-top: 0 !important;
            }
            
            #clientModal #clientName {
                scroll-margin-top: 1rem !important;
            }
        }
    `;
    document.head.appendChild(style);
}

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        fixClientModal();
        addFixCSS();
    });
} else {
    fixClientModal();
    addFixCSS();
}

// Também executar após um delay para garantir que tudo foi carregado
setTimeout(() => {
    fixClientModal();
    addFixCSS();
}, 1000);