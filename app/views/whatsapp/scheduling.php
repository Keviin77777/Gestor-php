<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos WhatsApp - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/whatsapp.css">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <h2 class="page-title">Agendamentos WhatsApp</h2>
                </div>
                <div class="header-right">
                    <button class="btn btn-secondary" onclick="refreshScheduling()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                        </svg>
                        Atualizar
                    </button>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Scheduling List -->
                <div class="templates-list-container">
                    <!-- Table Header -->
                    <div class="templates-list-header">
                        <div class="header-col col-name">Template</div>
                        <div class="header-col col-type">Tipo</div>
                        <div class="header-col col-days">Dias da Semana</div>
                        <div class="header-col col-time">Horário</div>
                        <div class="header-col col-status">Status</div>
                        <div class="header-col col-actions">Ações</div>
                    </div>
                    
                    <!-- Table Body -->
                    <div class="templates-list" id="schedulingList">
                        <!-- Agendamentos serão carregados aqui -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Editar Agendamento -->
    <div class="modal" id="schedulingModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="schedulingModalTitle">Configurar Agendamento</h3>
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
                    
                    <div class="form-group">
                        <label>Template Selecionado</label>
                        <div class="template-info-card" id="templateInfo">
                            <!-- Informações do template serão preenchidas aqui -->
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label checkbox-switch">
                            <input type="checkbox" id="schedulingEnabled" onchange="toggleSchedulingOptions()">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Ativar Agendamento Automático</span>
                        </label>
                    </div>
                    
                    <div id="schedulingOptions" class="scheduling-options" style="display: none;">
                        <div class="form-group">
                            <label>Dias da Semana</label>
                            <div class="days-selector">
                                <label class="day-checkbox">
                                    <input type="checkbox" value="sunday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Dom</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="monday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Seg</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="tuesday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Ter</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="wednesday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Qua</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="thursday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Qui</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="friday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Sex</span>
                                    </span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="saturday" class="day-input">
                                    <span class="day-label">
                                        <span class="day-name">Sáb</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="schedulingTime">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Horário de Envio
                            </label>
                            <input type="time" id="schedulingTime" class="form-control" value="09:00">
                        </div>
                        
                        <div class="info-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <div>
                                <strong>Como funciona:</strong>
                                <p>O template será enviado automaticamente nos dias e horário selecionados para clientes que atendam aos critérios do tipo de template.</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSchedulingModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveScheduling()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar Agendamento
                </button>
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
    <script src="/assets/js/whatsapp-scheduling.js"></script>
</body>
</html>
