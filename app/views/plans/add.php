<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Adicionar Plano - UltraGestor</title>
    <?php $v = time(); ?>
    <link rel="stylesheet" href="/assets/css/dashboard.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/header-menu.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/clients-import.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/plan-add.css?v=<?php echo $v; ?>">
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
        <div class="add-plan-container">
            <div class="add-plan-header">
                <button class="btn-back" onclick="window.location.href='/plans'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Voltar
                </button>
                <div class="header-content">
                    <h1>Adicionar Novo Plano</h1>
                    <p>Configure os detalhes do plano IPTV</p>
                </div>
            </div>

            <div class="add-plan-form-card">
                <form id="planForm">
                    <!-- Seção: Informações Básicas -->
                    <div class="form-section">
                        <h2 class="section-title">Informações Básicas</h2>
                        <div class="form-grid">
                            <!-- Servidor -->
                            <div class="form-group full-width">
                                <label for="planServer">Servidor *</label>
                                <select id="planServer" name="server_id" required>
                                    <option value="">Carregando servidores...</option>
                                </select>
                                <small class="form-hint">Escolha o servidor onde este plano será aplicado</small>
                            </div>

                            <!-- Nome do Plano -->
                            <div class="form-group">
                                <label for="planName">Nome do Plano *</label>
                                <input type="text" id="planName" name="name" placeholder="Ex: Premium Plus" required>
                            </div>

                            <!-- Preço -->
                            <div class="form-group">
                                <label for="planPrice">Preço Mensal *</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">R$</span>
                                    <input type="text" id="planPrice" name="price" placeholder="0,00" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Configurações -->
                    <div class="form-section">
                        <h2 class="section-title">Configurações</h2>
                        <div class="form-grid">
                            <!-- Duração -->
                            <div class="form-group">
                                <label for="planDuration">Duração (dias) *</label>
                                <input type="number" id="planDuration" name="duration_days" min="1" placeholder="30" value="30" required>
                            </div>

                            <!-- Máximo de Telas -->
                            <div class="form-group">
                                <label for="planScreens">Máximo de Telas *</label>
                                <input type="number" id="planScreens" name="max_screens" min="1" max="10" placeholder="1" value="1" required>
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label for="planStatus">Status</label>
                                <select id="planStatus" name="status">
                                    <option value="active">Ativo</option>
                                    <option value="inactive">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Descrição -->
                    <div class="form-section">
                        <h2 class="section-title">Descrição</h2>
                        <div class="form-group full-width">
                            <label for="planDescription">Descrição</label>
                            <textarea id="planDescription" name="description" rows="4" placeholder="Descreva os benefícios do plano..."></textarea>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="window.location.href='/plans'">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Adicionar Plano
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="/assets/js/common.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/auth.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/plan-add.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
