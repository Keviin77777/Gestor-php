<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/auth-modern.css">
</head>
<body>
    <div class="login-container register-page">
        <!-- Lado Esquerdo - Informações -->
        <div class="login-left">
            <div class="brand">
                <div class="brand-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <h1>UltraGestor</h1>
                <p>Sistema Profissional de Gestão IPTV</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3>Gestão de Clientes</h3>
                    <p>Controle completo de clientes e assinaturas</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                            <line x1="6" y1="6" x2="6.01" y2="6"></line>
                            <line x1="6" y1="18" x2="6.01" y2="18"></line>
                        </svg>
                    </div>
                    <h3>Servidores IPTV</h3>
                    <p>Gerencie múltiplos servidores</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </div>
                    <h3>Automação WhatsApp</h3>
                    <p>Mensagens e notificações automáticas</p>
                </div>
            </div>

            <div class="login-footer">
                <p>&copy; 2025 UltraGestor. Todos os direitos reservados.</p>
            </div>
        </div>

        <!-- Lado Direito - Formulário -->
        <div class="login-right">
            <div class="login-form-container register-expanded">
                <form id="registerForm" class="login-form register-form-expanded">
                    <div class="form-header">
                        <div class="user-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <path d="M20 8v6M23 11h-6"></path>
                            </svg>
                        </div>
                        <h2>Criar Conta</h2>
                        <p>Preencha os dados para criar sua conta</p>
                    </div>

                    <!-- Primeira linha: Nome e E-mail -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nome</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                placeholder="Digite seu nome" 
                                required 
                                autocomplete="name"
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                placeholder="Digite seu e-mail" 
                                required 
                                autocomplete="email"
                            >
                        </div>
                    </div>

                    <!-- Segunda linha: WhatsApp (campo único, centralizado) -->
                    <div class="form-group">
                        <label for="whatsapp">WhatsApp</label>
                        <input 
                            type="tel" 
                            id="whatsapp" 
                            name="whatsapp" 
                            placeholder="(00) 00000-0000" 
                            required
                            autocomplete="tel"
                        >
                        <small>Formato: (00) 00000-0000</small>
                    </div>

                    <!-- Terceira linha: Senha e Confirmar Senha -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Senha</label>
                            <div class="password-input">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    placeholder="••••••••••••" 
                                    required 
                                    minlength="6"
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" id="togglePassword">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <small>Mínimo de 6 caracteres</small>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Confirmar Senha</label>
                            <div class="password-input">
                                <input 
                                    type="password" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    placeholder="••••••••••••" 
                                    required 
                                    minlength="6"
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" id="togglePasswordConfirm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="plan_id" name="plan_id" value="">

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" id="terms" name="terms" required>
                            <span>Eu aceito os <a href="/terms" target="_blank">termos de uso</a> e <a href="/privacy" target="_blank">política de privacidade</a></span>
                        </label>
                    </div>

                    <button type="submit" class="btn-login">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <path d="M20 8v6M23 11h-6"></path>
                        </svg>
                        Criar Conta
                    </button>

                    <div class="form-divider">
                        <span>ou</span>
                    </div>

                    <a href="/login" class="btn-register">
                        Já tenho uma conta
                    </a>

                    <div class="security-badge">
                        🔒 Trial de 3 dias grátis
                    </div>
                </form>

                <div id="message" class="message"></div>
            </div>
        </div>
    </div>

    <script src="/assets/js/auth.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        });

        document.getElementById('togglePasswordConfirm')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password_confirm');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        });

        // Obter plan_id da URL se existir
        const urlParams = new URLSearchParams(window.location.search);
        const planId = urlParams.get('plan');
        if (planId) {
            document.getElementById('plan_id').value = planId;
        }

        // Máscara para WhatsApp
        const whatsappInput = document.getElementById('whatsapp');
        if (whatsappInput) {
            whatsappInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    if (value.length <= 2) {
                        value = `(${value}`;
                    } else if (value.length <= 7) {
                        value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
                    } else {
                        value = `(${value.slice(0, 2)}) ${value.slice(2, 7)}-${value.slice(7, 11)}`;
                    }
                }
                e.target.value = value;
            });
        }
    </script>
</body>
</html>