<?php
// Carregar .env ANTES de qualquer coisa
require_once __DIR__ . '/../../helpers/functions.php';
loadEnv(__DIR__ . '/../../.env');

// Iniciar sessão antes de carregar classes que dependem dela
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Agora carregar as classes
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';

$currentUser = null;
$isAdmin = false;
$userPlan = null;

// Obter a página atual para destacar no menu
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$currentPath = parse_url($currentPath, PHP_URL_PATH);

try {
    // Carregar .env se ainda não foi carregado
    if (!getenv('DB_HOST')) {
        loadEnv(__DIR__ . '/../../.env');
    }
    
    // Obter usuário autenticado
    $currentUser = Auth::user();
    
    if ($currentUser) {
        // Verificar se é admin - buscar dados completos do banco
        $userFromDB = Database::fetch(
            "SELECT * FROM users WHERE id = ? OR email = ?",
            [$currentUser['id'] ?? '', $currentUser['email'] ?? '']
        );
        
        if ($userFromDB) {
            // Atualizar dados do usuário com informações do banco
            $currentUser = array_merge($currentUser, $userFromDB);
            
            // Verificação robusta de admin - verificar tanto role quanto is_admin
            $isAdmin = false;
            if (isset($userFromDB['role']) && $userFromDB['role'] === 'admin') {
                $isAdmin = true;
            } elseif (isset($userFromDB['is_admin']) && ($userFromDB['is_admin'] === 1 || $userFromDB['is_admin'] === true || $userFromDB['is_admin'] === '1')) {
                $isAdmin = true;
            }
            
            // Garantir que a sessão tenha os dados corretos
            if ($isAdmin && isset($_SESSION['user'])) {
                $_SESSION['user']['role'] = $userFromDB['role'];
                $_SESSION['user']['is_admin'] = ($userFromDB['is_admin'] ?? 0) == 1;
            }
            
            // Se não for admin, obter informações do plano
            if (!$isAdmin) {
                $userDetails = Database::fetch("
                    SELECT 
                        u.*,
                        rp.name as plan_name,
                        CASE 
                            WHEN u.plan_expires_at IS NULL THEN 0
                            WHEN DATE(u.plan_expires_at) < CURDATE() THEN DATEDIFF(DATE(u.plan_expires_at), CURDATE())
                            WHEN DATE(u.plan_expires_at) = CURDATE() THEN 0
                            ELSE DATEDIFF(DATE(u.plan_expires_at), CURDATE())
                        END as days_remaining
                    FROM users u
                    LEFT JOIN reseller_plans rp ON u.current_plan_id = rp.id
                    WHERE u.id = ?
                ", [$userFromDB['id']]);
                
                if ($userDetails) {
                    $userPlan = [
                        'name' => $userDetails['plan_name'] ?? 'Sem plano',
                        'days_remaining' => (int)$userDetails['days_remaining'],
                        'expires_at' => $userDetails['plan_expires_at']
                    ];
                }
            }
        }
    } else {
        // Se não há usuário autenticado, verificar se é uma página pública
        // Não redirecionar aqui para evitar loop - deixar o index.php gerenciar
        // Apenas definir como não autenticado
        $currentUser = null;
        $isAdmin = false;
        $userPlan = null;
    }
    
} catch (Exception $e) {
    // error_log("Erro no sidebar: " . $e->getMessage());
    // Em caso de erro, assumir como não autenticado
    $currentUser = null;
    $isAdmin = false;
    $userPlan = null;
}
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h1 class="logo">UltraGestor</h1>
        <button class="sidebar-toggle" id="sidebarToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <a href="/dashboard" class="nav-item <?= $currentPath === '/dashboard' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>Dashboard</span>
        </a>
        
        <!-- Menu para Admin: Seção Administrativa -->
        <?php if ($isAdmin): ?>
            <div class="nav-group">
                <a href="#" class="nav-item has-submenu <?= strpos($currentPath, '/admin/') === 0 ? 'active' : '' ?>" onclick="toggleSubmenu(event, 'admin-submenu')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                    <span class="nav-text">Administração</span>
                    <svg class="submenu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </a>
                <div class="submenu <?= strpos($currentPath, '/admin/') === 0 ? 'expanded' : '' ?>" id="admin-submenu">
                    <a href="/admin/resellers" class="submenu-item <?= $currentPath === '/admin/resellers' ? 'active' : '' ?>">
                        <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Revendedores</span>
                    </a>
                    <a href="/admin/reseller-plans" class="submenu-item <?= $currentPath === '/admin/reseller-plans' ? 'active' : '' ?>">
                        <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>Planos de Revendedores</span>
                    </a>
                    <a href="/admin/payment-history" class="submenu-item <?= $currentPath === '/admin/payment-history' ? 'active' : '' ?>">
                        <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <span>Histórico de Pagamentos</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Menu Comum: Clientes (para todos os usuários) -->
            <div class="nav-group">
                <a href="#" class="nav-item has-submenu <?= in_array($currentPath, ['/clients', '/plans', '/applications']) ? 'active' : '' ?>" onclick="toggleSubmenu(event, 'clients-submenu')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span class="nav-text">Clientes</span>
                    <svg class="submenu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </a>
                <div class="submenu <?= in_array($currentPath, ['/clients', '/plans', '/applications']) ? 'expanded' : '' ?>" id="clients-submenu">
                    <a href="/clients" class="submenu-item <?= $currentPath === '/clients' ? 'active' : '' ?>">
                        <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                        <span>Lista de Clientes</span>
                    </a>
                    <a href="/plans" class="submenu-item <?= $currentPath === '/plans' ? 'active' : '' ?>">
                        <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>Planos</span>
                    </a>
                    <a href="/applications" class="submenu-item <?= $currentPath === '/applications' ? 'active' : '' ?>">
                        <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        <span>Aplicativos</span>
                    </a>
                </div>
            </div>
            
            <a href="/invoices" class="nav-item <?= $currentPath === '/invoices' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>Faturas</span>
            </a>
            
            <a href="/servidores" class="nav-item <?= $currentPath === '/servidores' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                <span>Servidores</span>
            </a>
            
            <div class="nav-group">
                <a href="#" class="nav-item has-submenu <?= in_array($currentPath, ['/whatsapp/parear', '/whatsapp/templates', '/whatsapp/scheduling']) ? 'active' : '' ?>" onclick="toggleSubmenu(event, 'whatsapp-submenu')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span class="nav-text">WhatsApp</span>
                    <svg class="submenu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </a>
                <div class="submenu <?= in_array($currentPath, ['/whatsapp/parear', '/whatsapp/templates', '/whatsapp/scheduling']) ? 'expanded' : '' ?>" id="whatsapp-submenu">
                    <a href="/whatsapp/parear" class="submenu-item <?= $currentPath === '/whatsapp/parear' ? 'active' : '' ?>">
                        <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5"></path>
                            <path d="M2 12l10 5 10-5"></path>
                        </svg>
                        <span>Parear WhatsApp</span>
                    </a>
                    <a href="/whatsapp/templates" class="submenu-item <?= $currentPath === '/whatsapp/templates' ? 'active' : '' ?>">
                        <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span>Templates</span>
                    </a>
                    <a href="/whatsapp/scheduling" class="submenu-item <?= $currentPath === '/whatsapp/scheduling' ? 'active' : '' ?>">
                        <svg class="submenu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Agendamento</span>
                    </a>
                </div>
            </div>
            
        <!-- Renovar Acesso (apenas para revendedores) -->
        <?php if (!$isAdmin): ?>
            <a href="/renew-access" class="nav-item <?= $currentPath === '/renew-access' ? 'active' : '' ?> renew-access-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 4v6h6"></path>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
                <span>Renovar Acesso</span>
                <?php if ($userPlan && $userPlan['days_remaining'] <= 7): ?>
                    <span class="nav-badge <?= $userPlan['days_remaining'] <= 0 ? 'expired' : 'warning' ?>">
                        <?= $userPlan['days_remaining'] <= 0 ? 'Vencido' : $userPlan['days_remaining'] . 'd' ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        
        <a href="/payment-methods" class="nav-item <?= $currentPath === '/payment-methods' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span>Métodos de Pagamento</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <?php if (!$isAdmin && $userPlan): ?>
            <!-- Informações do plano atual para revendedores -->
            <div class="plan-info">
                <div class="plan-name"><?= htmlspecialchars($userPlan['name']) ?></div>
                <div class="plan-expires">
                    <?php if ($userPlan['days_remaining'] <= 0): ?>
                        <span class="expired">Vencido há <?= abs($userPlan['days_remaining']) ?> dias</span>
                    <?php else: ?>
                        <span class="<?= $userPlan['days_remaining'] <= 7 ? 'warning' : '' ?>">
                            <?= $userPlan['days_remaining'] ?> dias restantes
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($currentUser['name'] ?? 'Usuário') ?></div>
            <div class="user-role"><?= $isAdmin ? 'Administrador' : 'Revendedor' ?></div>
        </div>
        
        <button class="logout-btn" onclick="logout()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Sair</span>
        </button>
    </div>
</aside>

<style>
/* Estilos específicos para o sidebar */
.nav-badge {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: #28a745;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    text-transform: uppercase;
}

.nav-badge.warning {
    background: #ffc107;
    color: #212529;
}

.nav-badge.expired {
    background: #dc3545;
    color: white;
}

.renew-access-item {
    position: relative;
}

.plan-info {
    padding: 12px 16px;
    border-top: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 12px;
}

.plan-name {
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 4px;
}

.plan-expires {
    font-size: 11px;
    color: rgba(255,255,255,0.7);
}

.plan-expires .expired {
    color: #ff6b6b;
    font-weight: 600;
}

.plan-expires .warning {
    color: #ffd93d;
    font-weight: 600;
}

.user-info {
    padding: 12px 16px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.user-name {
    font-size: 13px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 2px;
}

.user-role {
    font-size: 11px;
    color: rgba(255,255,255,0.7);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.logout-btn {
    width: 100%;
    padding: 12px 16px;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #ef4444;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s ease;
    min-height: 44px; /* Área de toque adequada */
    margin-top: 8px;
}

.logout-btn:hover {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.logout-btn:active {
    transform: translateY(0);
}

.logout-btn svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

/* Responsividade do botão logout */
@media (max-width: 768px) {
    .logout-btn {
        padding: 14px 16px;
        font-size: 14px;
        min-height: 48px;
        margin-top: 12px;
    }
    
    .logout-btn svg {
        width: 18px;
        height: 18px;
    }
}

@media (max-width: 480px) {
    .logout-btn {
        padding: 12px 14px;
        font-size: 13px;
        min-height: 44px;
    }
    
    .logout-btn svg {
        width: 16px;
        height: 16px;
    }
}

@media (max-width: 360px) {
    .logout-btn {
        padding: 10px 12px;
        font-size: 12px;
        min-height: 42px;
    }
    
    .logout-btn svg {
        width: 14px;
        height: 14px;
    }
}
</style>

<script>
// Função de logout
async function logout() {
    if (confirm('Tem certeza que deseja sair?')) {
        try {
            // Chamar API de logout para destruir sessão
            const response = await fetch('/api-auth.php', {
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
}

// Função para alternar submenu
function toggleSubmenu(event, submenuId) {
    event.preventDefault();
    const submenu = document.getElementById(submenuId);
    const parentItem = event.currentTarget;
    
    if (submenu.classList.contains('expanded')) {
        submenu.classList.remove('expanded');
        parentItem.classList.remove('active');
    } else {
        // Fechar outros submenus
        document.querySelectorAll('.submenu.expanded').forEach(menu => {
            menu.classList.remove('expanded');
        });
        document.querySelectorAll('.nav-item.has-submenu.active').forEach(item => {
            if (item !== parentItem) {
                item.classList.remove('active');
            }
        });
        
        // Abrir o submenu clicado
        submenu.classList.add('expanded');
        parentItem.classList.add('active');
    }
}
</script>
