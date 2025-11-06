<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Planos - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <link rel="stylesheet" href="/assets/css/plans.css">
    <link rel="stylesheet" href="/assets/css/modal-responsive.css">
    <style>
        /* Correções específicas para modal de planos em mobile */
        @media (max-width: 768px) {
            #planModal .modern-modal-body {
                padding-top: 1rem !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }
            
            #planModal .modern-form-group:first-child {
                margin-top: 0.5rem !important;
            }
            
            #planModal .modern-form-grid {
                margin-top: 0 !important;
            }
        }
    </style>
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
            <button class="btn btn-primary" id="newPlanBtn" onclick="openPlanModalGlobal()">
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
                    <select id="serverFilter" class="server-select">
                        <option value="">Todos os servidores</option>
                        <!-- Servidores serão carregados via JavaScript -->
                    </select>
                    <button class="btn btn-outline" onclick="clearServerFilter()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.5rem;">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Limpar Filtro
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

    <!-- Modal Adicionar/Editar Plano -->
    <div class="modern-modal" id="planModal">
        <div class="modern-modal-overlay" onclick="closePlanModal()"></div>
        <div class="modern-modal-content">
            <!-- Header -->
            <div class="modern-modal-header">
                <div class="modal-title-section">
                    <h2 id="planModalTitle">Adicionar Novo Plano</h2>
                    <p class="modal-subtitle">Configure os detalhes do plano IPTV.</p>
                </div>
                <button class="modern-modal-close" onclick="closePlanModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="modern-modal-body">
                <form id="planForm">
                    <div class="modern-form-grid">
                        <!-- Servidor -->
                        <div class="modern-form-group full-width">
                            <label for="planServer">Servidor *</label>
                            <select id="planServer" name="server_id" required>
                                <option value="">Selecione um servidor...</option>
                                <!-- Servidores serão carregados via JavaScript -->
                            </select>
                            <small class="form-hint">Escolha o servidor onde este plano será aplicado</small>
                        </div>

                        <!-- Nome do Plano -->
                        <div class="modern-form-group">
                            <label for="planName">Nome do Plano *</label>
                            <input type="text" id="planName" name="name" placeholder="Ex: Premium Plus" required>
                        </div>

                        <!-- Preço -->
                        <div class="modern-form-group">
                            <label for="planPrice">Preço Mensal *</label>
                            <input type="number" id="planPrice" name="price" step="0.01" min="0" placeholder="35.00" required>
                        </div>

                        <!-- Duração -->
                        <div class="modern-form-group">
                            <label for="planDuration">Duração (dias)</label>
                            <input type="number" id="planDuration" name="duration_days" min="1" placeholder="30" value="30">
                        </div>

                        <!-- Máximo de Telas -->
                        <div class="modern-form-group">
                            <label for="planScreens">Máximo de Telas</label>
                            <input type="number" id="planScreens" name="max_screens" min="1" max="10" placeholder="2" value="1">
                        </div>

                        <!-- Status -->
                        <div class="modern-form-group">
                            <label for="planStatus">Status</label>
                            <select id="planStatus" name="status">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div class="modern-form-group full-width">
                        <label for="planDescription">Descrição</label>
                        <textarea id="planDescription" name="description" rows="3" placeholder="Descreva os benefícios do plano..."></textarea>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modern-modal-footer">
                <button type="button" class="btn-modern btn-secondary" onclick="closePlanModalGlobal()">Cancelar</button>
                <button type="submit" form="planForm" class="btn-modern btn-primary" id="planSubmitBtn">Adicionar Plano</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/theme-global.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/plans-modal-fix.js"></script>
    <script src="/assets/js/plans.js"></script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>