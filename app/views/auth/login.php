<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UltraGestor</title>
    <meta name="description" content="Faça login no UltraGestor - Sistema Profissional de Gestão IPTV">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/auth-modern.css">
</head>
<body class="login-page">
    <!-- Back to Landing -->
    <div class="back-to-landing">
        <a href="/" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Voltar ao Site
        </a>
    </div>

    <div class="auth-container">
        <!-- Lado Esquerdo - Informações -->
        <div class="auth-left">
            <div class="brand">
                <div class="brand-icon">
                    <i class="fas fa-tv"></i>
                </div>
                <h1>UltraGestor</h1>
                <p>Sistema Profissional de Gestão IPTV</p>
            </div>

            <div class="features-showcase">
                <div class="showcase-stats">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Clientes Ativos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">99.9%</div>
                        <div class="stat-label">Uptime</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Suporte</div>
                    </div>
                </div>

                <div class="features-grid">
                    <div class="feature-card featured">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Gestão Completa</h3>
                        <p>Controle total de clientes, servidores e assinaturas em uma plataforma unificada</p>
                    </div>

                    <div class="feature-card featured">
                        <div class="feature-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h3>Automação WhatsApp</h3>
                        <p>Mensagens automáticas, lembretes e notificações inteligentes para seus clientes</p>
                    </div>

                    <div class="feature-card featured">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Dashboard Avançado</h3>
                        <p>Relatórios em tempo real, métricas detalhadas e insights para seu negócio</p>
                    </div>
                </div>

                <div class="trust-compact">
                    <div class="trust-row">
                        <i class="fas fa-shield-alt"></i>
                        <span>100% Seguro • Cloud Backup • Suporte 24/7</span>
                    </div>
                </div>
            </div>

            <div class="auth-footer">
                <p>&copy; 2025 UltraGestor. Todos os direitos reservados.</p>
            </div>
        </div>

        <!-- Lado Direito - Formulário -->
        <div class="auth-right">
            <div class="auth-form-container">
                <div class="form-header">
                    <div class="user-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2>Bem-vindo de volta!</h2>
                    <p>Faça login para acessar sua conta</p>
                </div>

                <div id="message" class="message" style="display: none;"></div>

                <form id="loginForm" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email ou Usuário</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Digite seu email ou usuário" 
                            required 
                            autocomplete="email"
                        >
                        <small>Digite suas credenciais para fazer login</small>
                    </div>

                    <div class="form-group">
                        <label for="password">Senha</label>
                        <div class="password-input">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="••••••••••••" 
                                required 
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" id="remember" name="remember">
                            <span>Lembrar-me</span>
                        </label>
                        <a href="#" class="forgot-link">Esqueceu a senha?</a>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Entrar no Sistema
                    </button>

                    <div class="form-divider">
                        <span>ou</span>
                    </div>

                    <a href="/register" class="btn btn-outline">
                        <i class="fas fa-user-plus"></i>
                        Criar Nova Conta
                    </a>

                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        Conexão segura e criptografada
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/auth.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.getAttribute('type') === 'password') {
                passwordInput.setAttribute('type', 'text');
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.setAttribute('type', 'password');
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Form animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const formElements = document.querySelectorAll('.form-group, .form-options, .btn, .form-divider, .security-badge');
            formElements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    element.style.transition = 'all 0.6s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>
