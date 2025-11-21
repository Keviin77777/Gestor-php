<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates WhatsApp - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <link rel="stylesheet" href="/assets/css/whatsapp.css">
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
                <!-- Templates List -->
                <div class="templates-list-container">
                    <!-- Table Header -->
                    <div class="templates-list-header">
                        <div class="header-col col-name">Nome</div>
                        <div class="header-col col-type">Tipo</div>
                        <div class="header-col col-media">Mídia</div>
                        <div class="header-col col-default">Padrão</div>
                        <div class="header-col col-status">Status</div>
                        <div class="header-col col-actions">Ações</div>
                    </div>
                    
                    <!-- Table Body -->
                    <div class="templates-list" id="templatesList">
                        <!-- Templates serão carregados aqui -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Template -->
    <div class="modal" id="templateModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="modalTitle">Novo Template</h3>
                <button class="modal-close" onclick="closeTemplateModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" id="templateId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="templateName">Nome do Template</label>
                            <input type="text" id="templateName" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="templateType">Tipo</label>
                            <select id="templateType" class="form-control" required>
                                <option value="">Selecione...</option>
                                <option value="welcome">Boas Vindas</option>
                                <option value="invoice_generated">Fatura Gerada</option>
                                <option value="renewed">Renovação Confirmada</option>
                                <option value="expires_3d">Vence em 3 dias</option>
                                <option value="expires_7d">Vence em 7 dias</option>
                                <option value="expires_today">Vence Hoje</option>
                                <option value="expired_1d">Vencido há 1 dia</option>
                                <option value="expired_3d">Vencido há 3 dias</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="templateTitle">Título</label>
                        <input type="text" id="templateTitle" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="templateMessage">Mensagem</label>
                        <textarea id="templateMessage" class="form-control" rows="10" required></textarea>
                        <small class="form-text">Use {{variavel}} para inserir variáveis dinâmicas</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Variáveis Disponíveis</label>
                        <div class="variables-list" id="variablesList">
                            <span class="variable-tag" onclick="insertVariable('cliente_nome')">{{cliente_nome}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_usuario')">{{cliente_usuario}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_senha')">{{cliente_senha}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_servidor')">{{cliente_servidor}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_plano')">{{cliente_plano}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_vencimento')">{{cliente_vencimento}}</span>
                            <span class="variable-tag" onclick="insertVariable('cliente_valor')">{{cliente_valor}}</span>
                            <span class="variable-tag" onclick="insertVariable('fatura_valor')">{{fatura_valor}}</span>
                            <span class="variable-tag" onclick="insertVariable('fatura_vencimento')">{{fatura_vencimento}}</span>
                            <span class="variable-tag" onclick="insertVariable('fatura_periodo')">{{fatura_periodo}}</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="templateActive" checked>
                                <span>Template Ativo</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="templateDefault">
                                <span>Template Padrão</span>
                            </label>
                        </div>
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTemplateModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">Salvar Template</button>
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

    <!-- Modal Visualizar Template -->
    <div class="modal" id="viewTemplateModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Visualizar Template</h3>
                <button class="modal-close" onclick="closeViewModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="phone-preview-container">
                    <div class="view-message" id="viewMessage"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="editFromView()">Editar Template</button>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/loading-manager.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/access-control.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/whatsapp-templates.js"></script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>
