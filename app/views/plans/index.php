<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Planos - UltraGestor</title>
    
    <!-- Limpar tema light do localStorage -->
    <script>
        localStorage.removeItem('ultragestor_theme');
        localStorage.removeItem('theme');
        document.documentElement.removeAttribute('data-theme');
    </script>
    
    <?php $v = time(); ?>
    <link rel="stylesheet" href="/assets/css/dashboard.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/header-menu.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/plans-modern.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/modal-responsive.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/plan-add-page-mobile.css?v=<?php echo $v; ?>">

</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header Menu -->
        <?php include __DIR__ . '/../components/header-menu.php'; ?>
        
        <!-- Action Buttons -->
        <div class="action-buttons-bar">
            <button class="btn btn-primary" id="newPlanBtn" onclick="window.location.href='/plans/add'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Novo Plano
            </button>
            
            <button class="btn btn-secondary" onclick="exportPlans()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Exportar
            </button>
        </div>

        <!-- Server Filter -->
        <div class="server-filter-section">
            <div class="server-filter-header">
                <div class="filter-title-section">
                    <h3>Planos por Servidor</h3>
                    <p>Gerencie os planos organizados por servidor</p>
                </div>
                <div class="filter-actions">
                    <div class="search-box">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchPlans" class="search-input" placeholder="Pesquisar planos...">
                    </div>
                    <select id="serverFilter" class="server-select">
                        <option value="">Todos os servidores</option>
                        <!-- Servidores serão carregados via JavaScript -->
                    </select>
                    <button class="btn btn-outline" onclick="clearFilters()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.5rem;">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Limpar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Plans Grid -->
        <div class="dashboard-content">
            <div class="plans-container" id="plansContainer">
                <!-- Planos agrupados por servidor serão carregados via JavaScript -->
            </div>
        </div>
    </main>

    <!-- Modal de adicionar plano removido - agora usa página separada -->

    <script src="/assets/js/common.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/loading-manager.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/auth.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/access-control.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/theme-global.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/mobile-responsive.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/plans-modal-fix.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/plans.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/plan-add-page-mobile.js?v=<?php echo $v; ?>"></script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>