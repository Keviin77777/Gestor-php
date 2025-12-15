<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo isset($_GET['id']) ? 'Editar Cliente' : 'Adicionar Cliente'; ?> - UltraGestor</title>
    <?php $v = time(); ?>
    <link rel="stylesheet" href="/assets/css/dashboard.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/header-menu.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/clients-import.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="/assets/css/client-add.css?v=<?php echo $v; ?>">
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
        <div class="add-client-container">
            <div class="add-client-header">
                <button class="btn-back" onclick="window.location.href='/clients'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Voltar
                </button>
                <div class="header-content">
                    <h1 id="pageTitle"><?php echo isset($_GET['id']) ? 'Editar Cliente' : 'Adicionar Novo Cliente'; ?></h1>
                    <p id="pageSubtitle"><?php echo isset($_GET['id']) ? 'Atualize as informações do cliente' : 'Preencha os detalhes do novo cliente'; ?></p>
                </div>
            </div>

            <div class="add-client-form-card">
                <form id="clientForm" data-client-id="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">
                    <!-- Seção: Informações Básicas -->
                    <div class="form-section">
                        <h2 class="section-title">Informações Básicas</h2>
                        <div class="form-grid">
                            <!-- Nome Sistema -->
                            <div class="form-group">
                                <label for="clientName">Nome Sistema *</label>
                                <input type="text" id="clientName" name="name" placeholder="Nome do cliente" required>
                            </div>

                            <!-- WhatsApp -->
                            <div class="form-group">
                                <label for="clientPhone">WhatsApp *</label>
                                <input type="tel" id="clientPhone" name="phone" placeholder="(00) 00000-0000" required>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label for="clientEmail">Email</label>
                                <input type="email" id="clientEmail" name="email" placeholder="email@exemplo.com (opcional)">
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Detalhes IPTV -->
                    <div class="form-section">
                        <h2 class="section-title">Detalhes IPTV</h2>
                        <div class="form-grid">
                            <!-- Usuário IPTV -->
                            <div class="form-group">
                                <label for="clientUsername">Usuário IPTV *</label>
                                <input type="text" id="clientUsername" name="username" placeholder="Usuário" required>
                            </div>

                            <!-- Senha IPTV -->
                            <div class="form-group">
                                <label for="clientIptvPassword">Senha IPTV *</label>
                                <div class="input-with-button">
                                    <input type="text" id="clientIptvPassword" name="iptv_password" placeholder="Senha" required>
                                    <button type="button" class="btn-generate" onclick="generateIptvPassword()" title="Gerar senha">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- MAC -->
                            <div class="form-group">
                                <label for="clientMac">MAC</label>
                                <input type="text" id="clientMac" name="mac" placeholder="00:00:00:00:00:00">
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Configurações -->
                    <div class="form-section">
                        <h2 class="section-title">Configurações</h2>
                        <div class="form-grid">
                            <!-- Servidor -->
                            <div class="form-group">
                                <label for="clientServer">Servidor *</label>
                                <select id="clientServer" name="server" required>
                                    <option value="">Carregando servidores...</option>
                                </select>
                            </div>

                            <!-- Aplicativo -->
                            <div class="form-group">
                                <label for="clientApplication">Aplicativo</label>
                                <select id="clientApplication" name="application_id">
                                    <option value="">Carregando aplicativos...</option>
                                </select>
                            </div>

                            <!-- Plano -->
                            <div class="form-group">
                                <label for="clientPlan">Plano *</label>
                                <select id="clientPlan" name="plan" required>
                                    <option value="">Carregando planos...</option>
                                </select>
                            </div>

                            <!-- Número de Telas -->
                            <div class="form-group">
                                <label for="clientScreens">Número de Telas</label>
                                <input type="number" id="clientScreens" name="screens" min="1" max="10" value="1" placeholder="1 (opcional)">
                            </div>

                            <!-- Vencimento -->
                            <div class="form-group">
                                <label for="clientRenewalDate">Vencimento *</label>
                                <input type="date" id="clientRenewalDate" name="renewal_date" required>
                            </div>

                            <!-- Valor -->
                            <div class="form-group">
                                <label for="clientValue">Valor Mensal *</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">R$</span>
                                    <input type="text" id="clientValue" name="value" placeholder="0,00" required>
                                </div>
                            </div>

                            <!-- Notificações -->
                            <div class="form-group">
                                <label for="clientNotifications">Notificações</label>
                                <select id="clientNotifications" name="notifications">
                                    <option value="sim">Sim</option>
                                    <option value="nao">Não</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Observações -->
                    <div class="form-section">
                        <h2 class="section-title">Observações</h2>
                        <div class="form-group full-width">
                            <label for="clientNotes">Notas</label>
                            <textarea id="clientNotes" name="notes" rows="4" placeholder="Adicione observações sobre o cliente..."></textarea>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="window.location.href='/clients'">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <?php echo isset($_GET['id']) ? 'Salvar Alterações' : 'Adicionar Cliente'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="/assets/js/common.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/auth.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/client-add.js?v=<?php echo $v; ?>"></script>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
