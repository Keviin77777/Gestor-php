<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Importar Clientes - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <link rel="stylesheet" href="/assets/css/clients-import.css">
    <link rel="stylesheet" href="/assets/css/loading.css">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header Menu -->
        <?php include __DIR__ . '/../components/header-menu.php'; ?>
        
        <!-- Page Content -->
        <div class="import-container">
            <!-- Step 1: Escolher Método -->
            <div class="import-step" id="step1">
                <div class="import-header">
                    <div class="import-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <h1>Escolha o Método de Importação</h1>
                    <p>Importe seus clientes de forma rápida e organizada</p>
                </div>

                <div class="import-method-card">
                    <div class="method-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                    <h2>Importar via Planilha</h2>
                    <p>Faça upload de um arquivo Excel (.xlsx) com os dados dos seus clientes</p>
                    
                    <div class="import-features">
                        <div class="feature-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <span>Importação em massa</span>
                        </div>
                        <div class="feature-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <span>Até 1000 clientes</span>
                        </div>
                        <div class="feature-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <span>Validação automática</span>
                        </div>
                    </div>

                    <button class="btn-primary" onclick="showUploadStep()">
                        Selecionar Planilha
                    </button>
                </div>
            </div>

            <!-- Step 2: Upload e Campos Obrigatórios -->
            <div class="import-step" id="step2" style="display: none;">
                <div class="import-header">
                    <button class="btn-back" onclick="showMethodStep()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Voltar
                    </button>
                    <h1>Preparação Inteligente do Arquivo</h1>
                    <p>Campos obrigatórios para importação</p>
                </div>

                <div class="required-fields-section">
                    <div class="alert alert-warning">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div>
                            <strong>IMPORTANTE:</strong> Sua planilha deve conter as seguintes colunas obrigatórias
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4"></path>
                            <path d="M12 8h.01"></path>
                        </svg>
                        <div>
                            <strong>DICA:</strong> Se sua planilha não contiver todos os campos, continue para o preview e preencha os campos com erro manualmente
                        </div>
                    </div>

                    <div class="fields-grid">
                        <div class="field-card required">
                            <div class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <h3>Nome Sistema</h3>
                            <p>Nome completo do cliente</p>
                            <span class="field-badge required">Obrigatório</span>
                        </div>

                        <div class="field-card required">
                            <div class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <h3>Usuário IPTV</h3>
                            <p>Login de acesso IPTV</p>
                            <span class="field-badge required">Obrigatório</span>
                        </div>

                        <div class="field-card required">
                            <div class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                            <h3>Senha IPTV</h3>
                            <p>Senha de acesso IPTV</p>
                            <span class="field-badge required">Obrigatório</span>
                        </div>

                        <div class="field-card required">
                            <div class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                            </div>
                            <h3>WhatsApp</h3>
                            <p>Número com DDD</p>
                            <span class="field-badge required">Obrigatório</span>
                        </div>

                        <div class="field-card required">
                            <div class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                            <h3>Vencimento</h3>
                            <p>Data de vencimento</p>
                            <span class="field-badge required">Obrigatório</span>
                        </div>

                        <div class="field-card required">
                            <div class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                    <line x1="8" y1="21" x2="16" y2="21"></line>
                                    <line x1="12" y1="17" x2="12" y2="21"></line>
                                </svg>
                            </div>
                            <h3>Servidor</h3>
                            <p>Nome do servidor</p>
                            <span class="field-badge required">Obrigatório</span>
                        </div>

                        <div class="field-card required">
                            <div class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                    <line x1="8" y1="21" x2="16" y2="21"></line>
                                    <line x1="12" y1="17" x2="12" y2="21"></line>
                                </svg>
                            </div>
                            <h3>Aplicativo</h3>
                            <p>Nome do aplicativo</p>
                            <span class="field-badge required">Obrigatório</span>
                        </div>

                        <div class="field-card required">
                            <div class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                            <h3>Plano</h3>
                            <p>Nome do plano</p>
                            <span class="field-badge required">Obrigatório</span>
                        </div>
                    </div>
                </div>

                <div class="upload-section">
                    <div class="upload-area" id="uploadArea">
                        <input type="file" id="fileInput" accept=".xlsx" style="display: none;" onchange="handleFileSelect(event)">
                        <div class="upload-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                        </div>
                        <h3>Arraste o arquivo aqui ou clique para selecionar</h3>
                        <p>Apenas arquivos .xlsx são aceitos</p>
                        <button class="btn-secondary" onclick="document.getElementById('fileInput').click()">
                            Selecionar Arquivo
                        </button>
                    </div>

                    <div class="file-info" id="fileInfo" style="display: none;">
                        <div class="file-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                        </div>
                        <div class="file-details">
                            <h4 id="fileName"></h4>
                            <p id="fileSize"></p>
                        </div>
                        <button class="btn-remove" onclick="removeFile()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <button class="btn-primary btn-large" id="continueBtn" style="display: none;" onclick="processFile()">
                        Continuar para Preview
                    </button>
                </div>
            </div>

            <!-- Step 3: Preview e Validação -->
            <div class="import-step" id="step3" style="display: none;">
                <div class="import-header">
                    <button class="btn-back" onclick="showUploadStep()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Voltar
                    </button>
                    <h1>Preview da Importação</h1>
                    <p>Revise e ajuste os dados antes de importar</p>
                </div>

                <div class="preview-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalClients">0</h3>
                            <p>Total de Clientes</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <h3 id="validClients">0</h3>
                            <p>Válidos</p>
                        </div>
                    </div>
                    <div class="stat-card error">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <h3 id="invalidClients">0</h3>
                            <p>Com Erros</p>
                        </div>
                    </div>
                </div>

                <div class="import-actions-top">
                    <div class="bulk-actions">
                        <div class="bulk-action-group">
                            <label>Servidor para Todos</label>
                            <select id="bulkServer" onchange="applyBulkServer(this.value)">
                                <option value="">Selecione...</option>
                            </select>
                        </div>
                        <div class="bulk-action-group">
                            <label>Plano para Todos</label>
                            <select id="bulkPlan" onchange="applyBulkPlan(this.value)">
                                <option value="">Selecione...</option>
                            </select>
                        </div>
                        <div class="bulk-action-group">
                            <label>App para Todos</label>
                            <select id="bulkApp" onchange="applyBulkApp(this.value)">
                                <option value="">Selecione...</option>
                            </select>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn-secondary-action" onclick="showUploadStep()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Cancelar
                        </button>
                        <button class="btn-primary-action" id="importBtn" onclick="importClients()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            Importar Clientes
                        </button>
                    </div>
                </div>

                <div class="preview-table-container">
                    <table class="preview-table" id="previewTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Usuário</th>
                                <th>Senha</th>
                                <th>WhatsApp</th>
                                <th>Vencimento</th>
                                <th>Servidor</th>
                                <th>Plano</th>
                                <th>App</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="previewTableBody">
                            <!-- Dados serão carregados via JavaScript -->
                        </tbody>
                    </table>
                </div>


            </div>
        </div>
    </main>

    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/loading.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="/assets/js/clients-import.js"></script>
</body>
</html>
