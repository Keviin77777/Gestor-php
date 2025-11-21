<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicativos - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <link rel="stylesheet" href="/assets/css/admin-responsive.css">
    <link rel="stylesheet" href="/assets/css/applications.css">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header Menu -->
        <?php include __DIR__ . '/../components/header-menu.php'; ?>
        
        <!-- Content -->
        <div class="card">
            <div class="card-header">
                <h3>Gerenciar Aplicativos</h3>
                <div class="card-actions">
                    <button class="btn-icon" title="Atualizar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                        </svg>
                    </button>
                    <button class="btn-icon" title="Configurações">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M12 1v6m0 6v6m5.2-13.2l-4.2 4.2m0 6l4.2 4.2M23 12h-6m-6 0H1m18.2 5.2l-4.2-4.2m-6 0l-4.2 4.2"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div style="text-align: center; padding: 3rem 2rem; color: var(--text-secondary);">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem; opacity: 0.5;">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Aplicativos</h3>
                    <p style="margin-bottom: 2rem;">Gerencie os aplicativos e serviços disponíveis para seus clientes.</p>
                    <button class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Adicionar Aplicativo
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/access-control.js"></script>
    <script src="/assets/js/theme-global.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/applications.js"></script>
    <script>
        // Função para controlar submenu
        function toggleSubmenu(event, submenuId) {
            event.preventDefault();
            event.stopPropagation();
            
            const submenu = document.getElementById(submenuId);
            const navItem = event.currentTarget;
            
            if (!submenu || !navItem) return;
            
            // Toggle classes
            navItem.classList.toggle('expanded');
            submenu.classList.toggle('expanded');
        }

        // Função de logout
        function logout() {
            if (confirm('Tem certeza que deseja sair?')) {
                fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '/login';
                    }
                })
                .catch(error => {
                    window.location.href = '/login';
                });
            }
        }
    </script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>
