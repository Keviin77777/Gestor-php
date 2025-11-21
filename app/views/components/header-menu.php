<?php
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não estiver autenticado, não renderiza
if (!isset($_SESSION['user'])) {
    return;
}

// Pegar dados da sessão
$user = $_SESSION['user'];
$userName = $user['name'] ?? 'Usuário';
$userEmail = $user['email'] ?? '';
$userRole = $user['role'] ?? 'reseller';
$userId = $user['id'] ?? null;

// Verificação robusta de admin - verificar tanto role quanto is_admin
$isAdmin = false;
if (isset($user['role']) && $user['role'] === 'admin') {
    $isAdmin = true;
} elseif (isset($user['is_admin']) && ($user['is_admin'] === true || $user['is_admin'] === 1 || $user['is_admin'] === '1')) {
    $isAdmin = true;
}

// Se ainda não identificou como admin, buscar do banco para garantir
if (!$isAdmin && isset($userId)) {
    try {
        $rootPath = dirname(dirname(dirname(__DIR__)));
        if (!function_exists('loadEnv')) {
            require_once $rootPath . '/app/helpers/functions.php';
        }
        if (!class_exists('Database')) {
            require_once $rootPath . '/app/core/Database.php';
        }
        
        $userFromDB = Database::fetch(
            "SELECT role, is_admin FROM users WHERE id = ? OR email = ? LIMIT 1",
            [$userId, $userEmail]
        );
        
        if ($userFromDB) {
            if ($userFromDB['role'] === 'admin' || ($userFromDB['is_admin'] ?? 0) == 1) {
                $isAdmin = true;
                // Atualizar sessão com role correto
                $_SESSION['user']['role'] = $userFromDB['role'];
                $_SESSION['user']['is_admin'] = ($userFromDB['is_admin'] ?? 0) == 1;
                $userRole = $userFromDB['role'];
            }
        }
    } catch (Exception $e) {
        // Silenciar erro, usar valor da sessão
    }
}
?>

<div class="top-header-menu">
    <div class="header-menu-left">
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Menu" style="display: none;">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <!-- WhatsApp Status -->
        <div class="header-status-item">
            <div class="status-icon disconnected">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
            </div>
            <span class="status-text">Desconectado</span>
        </div>

        <!-- Plan Expiry (apenas revendedores) -->
        <?php if (!$isAdmin): ?>
        <div class="header-status-item plan-item" id="planExpiryHeader">
            <div class="status-icon active" id="planExpiryIcon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <span class="status-text" id="planExpiryText">
                Vencimento do Acesso: <strong id="planExpiryDays">Carregando...</strong>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <div class="header-menu-right">
        <!-- Notifications -->
        <div class="notifications-menu" id="notificationsMenu">
            <button class="header-icon-btn" id="notificationsBtn" title="Notificações">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notification-badge" id="notificationCount">3</span>
            </button>
            
            <!-- Notifications Dropdown -->
            <div class="notifications-dropdown" id="notificationsDropdown">
                <div class="notifications-header">
                    <h3>Notificações</h3>
                    <button class="mark-all-read" onclick="markAllAsRead()">
                        Marcar todas como lidas
                    </button>
                </div>
                
                <div class="notifications-list" id="notificationsList">
                    <div class="loading-notifications">
                        <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                        <span>Carregando notificações...</span>
                    </div>
                </div>
                
                <div class="notifications-footer">
                    <a href="/notifications" class="view-all-link">Ver todas as notificações</a>
                </div>
            </div>
        </div>

        <!-- User Profile -->
        <div class="user-profile-menu" id="userProfileMenu">
            <button class="user-profile-btn" id="userProfileBtn">
                <div class="user-avatar-small">
                    <?= strtoupper(substr($userName, 0, 1)) ?>
                </div>
                <div class="user-info-small">
                    <span class="user-name-small"><?= htmlspecialchars($userName) ?></span>
                    <span class="user-email-small"><?= htmlspecialchars($userEmail) ?></span>
                </div>
            </button>

            <!-- Dropdown -->
            <div class="user-dropdown-menu" id="userDropdownMenu">
                <div class="dropdown-user-info">
                    <div class="user-avatar-large">
                        <?= strtoupper(substr($userName, 0, 1)) ?>
                    </div>
                    <div>
                        <div class="dropdown-user-name"><?= htmlspecialchars($userName) ?></div>
                        <div class="dropdown-user-email"><?= htmlspecialchars($userEmail) ?></div>
                    </div>
                </div>

                <div class="dropdown-divider"></div>

                <a href="/profile" class="dropdown-menu-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Meu Perfil
                </a>

                <?php if (!$isAdmin): ?>
                <a href="/renew-access" class="dropdown-menu-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 4v6h6"></path>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    Renovar Acesso
                </a>
                <?php endif; ?>

                <div class="dropdown-divider"></div>

                <button class="dropdown-menu-item logout-item" onclick="logout()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Sair
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Notifications Dropdown
document.getElementById('notificationsBtn')?.addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('notificationsDropdown');
    const userDropdown = document.getElementById('userDropdownMenu');
    
    // Fechar dropdown do usuário se estiver aberto
    if (userDropdown) userDropdown.classList.remove('active');
    
    dropdown?.classList.toggle('active');
});

// User Profile Dropdown
document.getElementById('userProfileBtn')?.addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('userDropdownMenu');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    
    // Fechar dropdown de notificações se estiver aberto
    if (notificationsDropdown) notificationsDropdown.classList.remove('active');
    
    dropdown?.classList.toggle('active');
});

document.addEventListener('click', function(e) {
    const userDropdown = document.getElementById('userDropdownMenu');
    const userBtn = document.getElementById('userProfileBtn');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationsBtn = document.getElementById('notificationsBtn');
    
    // Fechar dropdown do usuário
    if (userDropdown && !userBtn?.contains(e.target) && !userDropdown.contains(e.target)) {
        userDropdown.classList.remove('active');
    }
    
    // Fechar dropdown de notificações
    if (notificationsDropdown && !notificationsBtn?.contains(e.target) && !notificationsDropdown.contains(e.target)) {
        notificationsDropdown.classList.remove('active');
    }
});

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
            
            // Limpar credenciais salvas apenas se o usuário fizer logout manual
            // (não limpar no auto-logout por expiração)
            if (!window.autoLogout) {
                localStorage.removeItem('ultragestor_credentials');
                localStorage.removeItem('ultragestor_remember');
            }
            
            // Redirecionar para login
            window.location.href = '/login';
        } catch (error) {
            // Mesmo com erro, limpar dados locais e redirecionar
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.clear();
            
            // Limpar credenciais salvas apenas se o usuário fizer logout manual
            if (!window.autoLogout) {
                localStorage.removeItem('ultragestor_credentials');
                localStorage.removeItem('ultragestor_remember');
            }
            window.location.href = '/login';
        }
    }
}

function markAllAsRead() {
    const notifications = document.querySelectorAll('.notification-item.unread');
    notifications.forEach(notif => notif.classList.remove('unread'));
    
    const badge = document.getElementById('notificationCount');
    if (badge) {
        badge.textContent = '0';
        badge.style.display = 'none';
    }
}

// Função para atualizar status do WhatsApp
function updateWhatsAppStatus() {
    fetch('/api-whatsapp-status.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const icon = document.querySelector('.top-header-menu .header-status-item .status-icon');
                const text = document.querySelector('.top-header-menu .header-status-item .status-text');
                
                if (data.session && data.session.status === 'connected') {
                    if (icon) {
                        icon.className = 'status-icon connected';
                    }
                    if (text) {
                        text.textContent = 'Conectado';
                    }
                } else {
                    if (icon) {
                        icon.className = 'status-icon disconnected';
                    }
                    if (text) {
                        text.textContent = 'Desconectado';
                    }
                }
            }
        })
        .catch(e => {
            // Em caso de erro, manter como desconectado
            const icon = document.querySelector('.top-header-menu .header-status-item .status-icon');
            const text = document.querySelector('.top-header-menu .header-status-item .status-text');
            if (icon) icon.className = 'status-icon disconnected';
            if (text) text.textContent = 'Desconectado';
        });
}

// Atualizar status imediatamente ao carregar
updateWhatsAppStatus();

// Atualizar status a cada 30 segundos
setInterval(updateWhatsAppStatus, 30000);

// Carregar notificações
function loadNotifications() {
    const notificationsList = document.getElementById('notificationsList');
    const notificationCount = document.getElementById('notificationCount');
    
    if (!notificationsList) return;
    
    // Buscar clientes vencendo hoje e nos próximos 7 dias
    fetch('/api-clients.php', {
        credentials: 'include'
    })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.clients) {
                showEmptyNotifications();
                return;
            }
            
            const notifications = [];
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            data.clients.forEach(client => {
                if (!client.renewal_date) return;
                
                const renewalDate = new Date(client.renewal_date);
                renewalDate.setHours(0, 0, 0, 0);
                
                const diffTime = renewalDate - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                // Clientes vencendo hoje
                if (diffDays === 0) {
                    notifications.push({
                        type: 'warning',
                        title: 'Cliente vence hoje',
                        message: `${client.name} - Plano vence hoje`,
                        time: 'Hoje',
                        unread: true
                    });
                }
                // Clientes vencendo nos próximos 3 dias
                else if (diffDays > 0 && diffDays <= 3) {
                    notifications.push({
                        type: 'warning',
                        title: `Cliente vence em ${diffDays} dia${diffDays > 1 ? 's' : ''}`,
                        message: `${client.name} - Plano ${client.plan || 'N/A'}`,
                        time: `Em ${diffDays} dia${diffDays > 1 ? 's' : ''}`,
                        unread: true
                    });
                }
                // Clientes vencidos
                else if (diffDays < 0) {
                    notifications.push({
                        type: 'danger',
                        title: 'Cliente vencido',
                        message: `${client.name} - Vencido há ${Math.abs(diffDays)} dia${Math.abs(diffDays) > 1 ? 's' : ''}`,
                        time: `Há ${Math.abs(diffDays)} dia${Math.abs(diffDays) > 1 ? 's' : ''}`,
                        unread: true
                    });
                }
            });
            
            if (notifications.length === 0) {
                showEmptyNotifications();
            } else {
                renderNotifications(notifications);
                updateNotificationCount(notifications.filter(n => n.unread).length);
            }
        })
        .catch(error => {
            showEmptyNotifications();
        });
}

function renderNotifications(notifications) {
    const notificationsList = document.getElementById('notificationsList');
    
    const icons = {
        success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
        warning: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
        info: '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>',
        danger: '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>'
    };
    
    notificationsList.innerHTML = notifications.map(notif => `
        <div class="notification-item ${notif.unread ? 'unread' : ''}">
            <div class="notification-icon ${notif.type}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    ${icons[notif.type]}
                </svg>
            </div>
            <div class="notification-content">
                <div class="notification-title">${notif.title}</div>
                <div class="notification-message">${notif.message}</div>
                <div class="notification-time">${notif.time}</div>
            </div>
        </div>
    `).join('');
}

function showEmptyNotifications() {
    const notificationsList = document.getElementById('notificationsList');
    notificationsList.innerHTML = `
        <div class="empty-notifications">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <p>Nenhuma notificação</p>
        </div>
    `;
    updateNotificationCount(0);
}

function updateNotificationCount(count) {
    const badge = document.getElementById('notificationCount');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Carregar notificações ao abrir o dropdown
document.getElementById('notificationsBtn')?.addEventListener('click', function() {
    loadNotifications();
});

// Carregar notificações inicialmente
loadNotifications();

// Atualizar notificações a cada 2 minutos
setInterval(loadNotifications, 120000);

// Função para atualizar vencimento do plano no header
async function updatePlanExpiry() {
    const planExpiryDays = document.getElementById('planExpiryDays');
    const planExpiryIcon = document.getElementById('planExpiryIcon');
    const planExpiryHeader = document.getElementById('planExpiryHeader');
    
    if (!planExpiryDays || !planExpiryIcon || !planExpiryHeader) {
        return; // Elementos não existem (usuário é admin)
    }
    
    try {
        const response = await fetch('/api-profile.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error('Erro ao buscar dados do plano');
        }
        
        const data = await response.json();
        
        if (data.success && data.plan) {
            const days = data.plan.days_remaining || 0;
            const isExpired = data.plan.is_expired || false;
            const isTrial = data.plan.is_trial || false;
            
            // Atualizar texto dos dias
            if (days < 0) {
                planExpiryDays.textContent = `Vencido há ${Math.abs(days)} ${Math.abs(days) === 1 ? 'dia' : 'dias'}`;
            } else if (days === 0) {
                planExpiryDays.textContent = 'Vence hoje';
            } else if (days === 1) {
                planExpiryDays.textContent = '1 dia';
            } else {
                planExpiryDays.textContent = `${days} dias`;
            }
            
            // Atualizar ícone e cor baseado no status
            planExpiryIcon.className = 'status-icon';
            
            if (isExpired || days < 0) {
                planExpiryIcon.classList.add('expired');
            } else if (days <= 7) {
                planExpiryIcon.classList.add('warning');
            } else if (isTrial) {
                planExpiryIcon.classList.add('warning');
            } else {
                planExpiryIcon.classList.add('active');
            }
        } else {
            // Sem plano ou erro
            planExpiryDays.textContent = 'Sem plano';
        planExpiryIcon.className = 'status-icon warning';
    }
} catch (error) {
    planExpiryDays.textContent = 'Erro ao carregar';
    planExpiryIcon.className = 'status-icon warning';
}
}

// Atualizar vencimento imediatamente ao carregar
updatePlanExpiry();

// Atualizar vencimento a cada minuto (60000ms) para refletir mudanças de dias
setInterval(updatePlanExpiry, 60000);

// Atualizar quando a página ganha foco (quando o usuário volta para a aba)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        updatePlanExpiry();
    }
});

// Atualizar também quando a página fica visível após um tempo
let lastUpdateTime = Date.now();
setInterval(function() {
    const now = Date.now();
    // Se passou mais de 5 minutos desde a última atualização e a página está visível
    if (now - lastUpdateTime > 300000 && !document.hidden) {
        updatePlanExpiry();
        lastUpdateTime = now;
    }
}, 60000);
</script>
