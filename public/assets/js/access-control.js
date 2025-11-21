/**
 * Controle de Acesso - Bloqueio quando plano vencido
 * Redireciona automaticamente para página de renovação quando dias <= 0
 */

(function() {
    'use strict';
    
    // Páginas permitidas mesmo com plano vencido
    const ALLOWED_PAGES = [
        '/renew-access',
        '/login',
        '/register',
        '/logout',
        '/landing',
        '/api-auth.php',
        '/api-reseller-renew-pix.php',
        '/api-check-payment-status.php',
        '/checkout.php',
        '/webhook-mercadopago.php'
    ];
    
    // Páginas BLOQUEADAS (não podem ser acessadas com plano vencido)
    const BLOCKED_PAGES = [
        '/dashboard',
        '/clients',
        '/plans',
        '/servers',
        '/applications',
        '/invoices',
        '/payment-methods',
        '/whatsapp',
        '/profile'
    ];
    
    /**
     * Verificar se a página atual é permitida
     */
    function isAllowedPage() {
        const currentPath = window.location.pathname;
        
        // Verificar se está em uma página explicitamente permitida
        const isExplicitlyAllowed = ALLOWED_PAGES.some(page => {
            return currentPath === page || currentPath.includes(page);
        });
        
        if (isExplicitlyAllowed) {
            return true;
        }
        
        // Verificar se está em uma página explicitamente bloqueada
        const isExplicitlyBlocked = BLOCKED_PAGES.some(page => {
            return currentPath === page || currentPath.startsWith(page);
        });
        
        if (isExplicitlyBlocked) {
            return false;
        }
        
        // Por padrão, bloquear páginas não listadas (segurança)
        return false;
    }
    
    /**
     * Verificar status do plano do usuário
     */
    async function checkPlanStatus() {
        try {
            const response = await fetch('/api-auth.php?action=check_plan', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                return null;
            }
            
            const data = await response.json();
            
            if (data.success && data.plan) {
                const planStatus = {
                    daysRemaining: parseInt(data.plan.days_remaining) || 0,
                    isExpired: data.plan.is_expired || false,
                    isAdmin: data.plan.is_admin || false,
                    planName: data.plan.name || 'Sem plano'
                };
                
                return planStatus;
            }
            
            return null;
        } catch (error) {
            return null;
        }
    }
    
    /**
     * Redirecionar para página de renovação
     */
    function redirectToRenew() {
        // Evitar loop de redirecionamento
        if (window.location.pathname === '/renew-access') {
            return;
        }
        
        // Salvar página de origem para voltar depois da renovação
        sessionStorage.setItem('redirect_after_renew', window.location.pathname);
        
        // Redirecionar
        window.location.href = '/renew-access';
    }
    
    /**
     * Bloquear navegação se plano vencido
     */
    function blockNavigation() {
        // Interceptar cliques em links
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            
            if (link && link.href) {
                const linkPath = new URL(link.href).pathname;
                
                // Se não é uma página permitida, bloquear
                if (!ALLOWED_PAGES.some(page => linkPath === page || linkPath.includes(page))) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    showBlockedMessage();
                    
                    // Redirecionar após mostrar mensagem
                    setTimeout(() => {
                        redirectToRenew();
                    }, 1500);
                }
            }
        }, true);
        
        // Interceptar mudanças de histórico (SPA)
        const originalPushState = history.pushState;
        const originalReplaceState = history.replaceState;
        
        history.pushState = function() {
            const url = arguments[2];
            if (url && !ALLOWED_PAGES.some(page => url.includes(page))) {
                redirectToRenew();
                return;
            }
            return originalPushState.apply(history, arguments);
        };
        
        history.replaceState = function() {
            const url = arguments[2];
            if (url && !ALLOWED_PAGES.some(page => url.includes(page))) {
                redirectToRenew();
                return;
            }
            return originalReplaceState.apply(history, arguments);
        };
        
        // Interceptar popstate (botão voltar)
        window.addEventListener('popstate', function(e) {
            if (!isAllowedPage()) {
                e.preventDefault();
                redirectToRenew();
            }
        });
    }
    
    /**
     * Mostrar mensagem de bloqueio
     */
    function showBlockedMessage() {
        // Remover mensagem anterior se existir
        const oldMessage = document.querySelector('.access-blocked-message');
        if (oldMessage) {
            oldMessage.remove();
        }
        
        // Criar mensagem
        const message = document.createElement('div');
        message.className = 'access-blocked-message';
        message.innerHTML = `
            <div class="blocked-content">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <h3>Acesso Bloqueado</h3>
                <p>Seu plano está vencido. Renove para continuar usando o sistema.</p>
            </div>
        `;
        
        document.body.appendChild(message);
        
        // Remover após 3 segundos
        setTimeout(() => {
            message.remove();
        }, 3000);
    }
    
    /**
     * Adicionar estilos da mensagem de bloqueio
     */
    function addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .access-blocked-message {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: var(--bg-primary);
                border: 2px solid var(--danger);
                border-radius: 12px;
                padding: 2rem;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                z-index: 99999;
                max-width: 400px;
                width: 90%;
                animation: slideIn 0.3s ease;
            }
            
            .blocked-content {
                text-align: center;
            }
            
            .blocked-content svg {
                width: 64px;
                height: 64px;
                color: var(--danger);
                margin-bottom: 1rem;
            }
            
            .blocked-content h3 {
                font-size: 1.5rem;
                font-weight: 600;
                color: var(--text-primary);
                margin-bottom: 0.5rem;
            }
            
            .blocked-content p {
                color: var(--text-secondary);
                font-size: 1rem;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translate(-50%, -60%);
                }
                to {
                    opacity: 1;
                    transform: translate(-50%, -50%);
                }
            }
            
            @media (max-width: 768px) {
                .access-blocked-message {
                    padding: 1.5rem;
                }
                
                .blocked-content svg {
                    width: 48px;
                    height: 48px;
                }
                
                .blocked-content h3 {
                    font-size: 1.25rem;
                }
                
                .blocked-content p {
                    font-size: 0.9375rem;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * Inicializar controle de acesso
     */
    async function init() {
        // Adicionar estilos
        addStyles();
        
        // Se já está na página de renovação, não fazer nada
        if (isAllowedPage()) {
            return;
        }
        
        // Verificar status do plano
        const planStatus = await checkPlanStatus();
        
        if (!planStatus) {
            // Não conseguiu verificar, permitir acesso
            return;
        }
        
        // Se plano vencido (dias <= 0 ou negativo), bloquear e redirecionar
        
        // Administradores não têm restrição de plano
        if (planStatus.isAdmin) {
            return;
        }
        
        if (planStatus.daysRemaining <= 0 || planStatus.isExpired) {
            // Bloquear navegação
            blockNavigation();
            
            // Redirecionar imediatamente
            redirectToRenew();
        }
    }
    
    // Executar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Verificar periodicamente (a cada 5 minutos)
    setInterval(async function() {
        if (isAllowedPage()) {
            return;
        }
        
        const planStatus = await checkPlanStatus();
        
        // Não bloquear administradores
        if (planStatus && planStatus.isAdmin) {
            return;
        }
        
        if (planStatus && (planStatus.daysRemaining <= 0 || planStatus.isExpired)) {
            redirectToRenew();
        }
    }, 5 * 60 * 1000); // 5 minutos
    
})();
