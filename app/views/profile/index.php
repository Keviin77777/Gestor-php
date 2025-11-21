<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <link rel="stylesheet" href="/assets/css/profile.css">
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
        <div class="page-container">
            <div class="page-header">
                <div class="page-title">
                    <h1>Meu Perfil</h1>
                    <p>Gerencie suas informações pessoais e configurações da conta</p>
                </div>
            </div>

            <div class="profile-container">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <div class="avatar-circle" id="profileAvatar">
                                <span id="avatarInitials">U</span>
                            </div>
                            <button class="avatar-edit-btn" onclick="changeAvatar()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="profile-info">
                            <h2 id="profileName">Carregando...</h2>
                            <p id="profileEmail">carregando@email.com</p>
                            <div class="profile-badges">
                                <span class="role-badge" id="roleBadge">Revendedor</span>
                                <span class="plan-badge" id="planBadge">Plano Básico</span>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Information (apenas para revendedores) -->
                    <div class="plan-section" id="planSection" style="display: none;">
                        <h3>Informações do Plano</h3>
                        <div class="plan-details">
                            <div class="plan-item">
                                <div class="plan-label">Plano Atual</div>
                                <div class="plan-value" id="currentPlan">-</div>
                            </div>
                            <div class="plan-item">
                                <div class="plan-label">Status</div>
                                <div class="plan-value">
                                    <span class="status-badge" id="planStatus">Ativo</span>
                                </div>
                            </div>
                            <div class="plan-item">
                                <div class="plan-label">Vencimento</div>
                                <div class="plan-value" id="planExpiry">-</div>
                            </div>
                            <div class="plan-item">
                                <div class="plan-label">Dias Restantes</div>
                                <div class="plan-value">
                                    <span class="days-badge" id="daysRemaining">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="plan-actions">
                            <a href="/renew-access" class="btn btn-primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 4v6h6"></path>
                                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                </svg>
                                Renovar Acesso
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="profile-form-card">
                    <h3>Informações Pessoais</h3>
                    <form id="profileForm" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="userName">Nome Completo</label>
                                <input type="text" id="userName" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="userEmail">E-mail</label>
                                <input type="email" id="userEmail" name="email" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="userPhone">WhatsApp</label>
                                <input type="tel" id="userPhone" name="phone" placeholder="(11) 99999-9999">
                            </div>
                            <div class="form-group">
                                <label for="userCompany">Empresa (Opcional)</label>
                                <input type="text" id="userCompany" name="company" placeholder="Nome da sua empresa">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Settings -->
                <div class="security-card">
                    <h3>Segurança</h3>
                    <form id="passwordForm" class="security-form">
                        <div class="form-group">
                            <label for="currentPassword">Senha Atual</label>
                            <div class="password-input">
                                <input type="password" id="currentPassword" name="current_password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('currentPassword')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="newPassword">Nova Senha</label>
                                <div class="password-input">
                                    <input type="password" id="newPassword" name="new_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirmar Nova Senha</label>
                                <div class="password-input">
                                    <input type="password" id="confirmPassword" name="confirm_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="password-requirements">
                            <h4>Requisitos da senha:</h4>
                            <ul>
                                <li id="req-length">Mínimo 8 caracteres</li>
                                <li id="req-uppercase">Pelo menos 1 letra maiúscula</li>
                                <li id="req-lowercase">Pelo menos 1 letra minúscula</li>
                                <li id="req-number">Pelo menos 1 número</li>
                            </ul>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-secondary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <circle cx="12" cy="16" r="1"></circle>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="loading-spinner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
            </svg>
            <span>Carregando...</span>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/access-control.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/profile.js"></script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>