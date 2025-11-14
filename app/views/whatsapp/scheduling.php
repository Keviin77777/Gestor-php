<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos WhatsApp - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <link rel="stylesheet" href="/assets/css/whatsapp.css">
    <link rel="stylesheet" href="/assets/css/whatsapp-scheduling.css">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="app-container">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header Menu -->
            <?php include __DIR__ . '/../components/header-menu.php'; ?>
            
            <!-- Content -->
            <div class="content">
                <!-- Scheduling List -->
                <div class="scheduling-table-container">
                    <!-- Table Header -->
                    <div class="scheduling-table-header">
                        <div class="header-col col-template">TEMPLATE</div>
                        <div class="header-col col-type">TIPO</div>
                        <div class="header-col col-days">DIAS DA SEMANA</div>
                        <div class="header-col col-time">HORÁRIO</div>
                        <div class="header-col col-status">STATUS</div>
                        <div class="header-col col-actions">AÇÕES</div>
                    </div>
                    
                    <!-- Table Body -->
                    <div class="scheduling-table-body" id="schedulingList">
                        <!-- Agendamentos serão carregados aqui -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Editar Agendamento -->
    <div class="modal" id="schedulingModal">
        <div class="modal-content modal-scheduling">
            <div class="modal-header">
                <h3>Editar configuração</h3>
                <button class="modal-close" onclick="closeSchedulingModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="schedulingForm">
                    <input type="hidden" id="schedulingTemplateId">
                    
                    <!-- Grid de 3 colunas -->
                    <div class="scheduling-form-grid">
                        <!-- Coluna 1: Horário -->
                        <div class="form-section">
                            <label class="form-label">Horário do envio!</label>
                            <div class="time-input-wrapper">
                                <input type="time" id="schedulingTime" class="form-control time-input" value="09:00">
                            </div>
                        </div>
                        
                        <!-- Coluna 2: Dias antes/depois (apenas para templates de vencimento) -->
                        <div class="form-section" id="daysOffsetSection" style="display: none;">
                            <label class="form-label">Dias após ou antes</label>
                            <input type="number" id="daysOffset" class="form-control" value="0" min="-30" max="30">
                            <small class="form-hint">Escolha o número de dias após ou antes</small>
                        </div>
                        
                        <!-- Coluna 3: Template -->
                        <div class="form-section">
                            <label class="form-label">Template de Mensagem</label>
                            <div class="template-display" id="templateInfo">
                                <!-- Será preenchido via JS -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dias da semana -->
                    <div class="form-section">
                        <label class="form-label">Dias da semana</label>
                        <div class="days-grid">
                            <label class="day-checkbox-modern">
                                <input type="checkbox" value="monday" class="day-input">
                                <span class="day-box">Segunda-feira</span>
                            </label>
                            <label class="day-checkbox-modern">
                                <input type="checkbox" value="tuesday" class="day-input">
                                <span class="day-box">Terça-feira</span>
                            </label>
                            <label class="day-checkbox-modern">
                                <input type="checkbox" value="wednesday" class="day-input">
                                <span class="day-box">Quarta-feira</span>
                            </label>
                            <label class="day-checkbox-modern">
                                <input type="checkbox" value="thursday" class="day-input">
                                <span class="day-box">Quinta-feira</span>
                            </label>
                            <label class="day-checkbox-modern">
                                <input type="checkbox" value="friday" class="day-input">
                                <span class="day-box">Sexta-feira</span>
                            </label>
                            <label class="day-checkbox-modern">
                                <input type="checkbox" value="saturday" class="day-input">
                                <span class="day-box">Sábado</span>
                            </label>
                            <label class="day-checkbox-modern">
                                <input type="checkbox" value="sunday" class="day-input">
                                <span class="day-box">Domingo</span>
                            </label>
                        </div>
                        <button type="button" class="btn-mark-all" onclick="toggleAllDays()">Marcar Todos</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSchedulingModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveScheduling()">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Loading Manager -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p id="loadingText">Carregando...</p>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/whatsapp-scheduling.js"></script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>
