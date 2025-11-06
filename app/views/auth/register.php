<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - UltraGestor</title>
    <meta name="description" content="Crie sua conta no UltraGestor - Sistema Profissional de Gestão IPTV">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/auth-modern.css">
    
    <style>
        /* Estilos específicos para registro */
        .register-page .auth-container {
            max-width: 1400px;
            max-height: none !important;
            overflow: visible !important;
        }
        
        .register-page .auth-right {
            max-height: none !important;
            overflow: visible !important;
        }
        
        .register-page .auth-form-container {
            max-width: 500px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .register-page .auth-container {
                overflow: visible !important;
            }
            
            .register-page .auth-right {
                overflow: visible !important;
            }
        }
        
        .checkbox-label a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .checkbox-label a:hover {
            text-decoration: underline;
        }
        
        .plan-selected {
            background: var(--bg-glass);
            border: 1px solid var(--primary);
            border-radius: var(--radius-sm);
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .plan-selected h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .plan-selected p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="register-page">
    <!-- Back to Landing -->
    <div class="back-to-landing">
        <a href="/" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Voltar ao Site
        </a>
    </div>

    <div class="auth-container register-page">
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
                <div class="trial-highlight">
                    <div class="trial-badge">
                        <i class="fas fa-gift"></i>
                        <span>3 DIAS GRÁTIS</span>
                    </div>
                    <h3>Teste Todas as Funcionalidades</h3>
                    <p>Sem compromisso • Cancele quando quiser</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card featured">
                        <div class="feature-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3>Setup em Minutos</h3>
                        <p>Configure seu sistema completo rapidamente com nossa interface intuitiva</p>
                    </div>

                    <div class="feature-card featured">
                        <div class="feature-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h3>Suporte Especializado</h3>
                        <p>Equipe técnica dedicada para ajudar você em cada passo da configuração</p>
                    </div>

                    <div class="feature-card featured">
                        <div class="feature-icon">
                            <i class="fas fa-shield-check"></i>
                        </div>
                        <h3>Segurança Total</h3>
                        <p>Dados protegidos com criptografia avançada e backup automático na nuvem</p>
                    </div>
                </div>

                <div class="benefits-compact">
                    <div class="benefit-row">
                        <i class="fas fa-check-circle"></i>
                        <span>Setup gratuito • Treinamento incluído • Migração sem custo</span>
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
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h2>Criar Conta</h2>
                    <p>Preencha os dados para começar seu trial gratuito</p>
                </div>

                <div id="message" class="message" style="display: none;"></div>

                <!-- Plano Selecionado -->
                <div id="planSelected" class="plan-selected" style="display: none;">
                    <h4 id="selectedPlanName">Plano Selecionado</h4>
                    <p id="selectedPlanDescription">Descrição do plano</p>
                </div>

                <form id="registerForm" class="auth-form">
                    <!-- Primeira linha: Nome e E-mail -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nome Completo</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                placeholder="Digite seu nome completo" 
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

                    <!-- Segunda linha: WhatsApp -->
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
                        <small>Usado para suporte e notificações importantes</small>
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
                                    <i class="fas fa-eye"></i>
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
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="plan_id" name="plan_id" value="">

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" id="terms" name="terms" required>
                            <span>Eu aceito os <a href="#" target="_blank">termos de uso</a> e <a href="#" target="_blank">política de privacidade</a></span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-rocket"></i>
                        Criar Conta e Começar Trial
                    </button>

                    <div class="form-divider">
                        <span>ou</span>
                    </div>

                    <a href="/login" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i>
                        Já tenho uma conta
                    </a>

                    <div class="security-badge">
                        <i class="fas fa-gift"></i>
                        3 dias grátis • Sem compromisso • Cancele quando quiser
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/auth.js"></script>
    <script>
        // Toggle password visibility
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            
            if (toggle && input) {
                toggle.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    
                    if (input.getAttribute('type') === 'password') {
                        input.setAttribute('type', 'text');
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.setAttribute('type', 'password');
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }
        }

        setupPasswordToggle('togglePassword', 'password');
        setupPasswordToggle('togglePasswordConfirm', 'password_confirm');

        // Obter plan_id da URL se existir
        const urlParams = new URLSearchParams(window.location.search);
        const planId = urlParams.get('plan');
        if (planId) {
            document.getElementById('plan_id').value = planId;
            // Aqui você pode fazer uma requisição para buscar os detalhes do plano
            // e exibir na seção "Plano Selecionado"
            showSelectedPlan(planId);
        }

        function showSelectedPlan(planId) {
            // Simular busca do plano (substitua por requisição real)
            const planNames = {
                'trial': 'Trial Gratuito',
                'monthly': 'Plano Mensal',
                'quarterly': 'Plano Trimestral',
                'annual': 'Plano Anual'
            };
            
            const planDescriptions = {
                'trial': '3 dias grátis para testar todas as funcionalidades',
                'monthly': 'Acesso completo por 30 dias',
                'quarterly': 'Acesso completo por 90 dias',
                'annual': 'Acesso completo por 365 dias'
            };
            
            const planName = planNames[planId] || 'Plano Selecionado';
            const planDescription = planDescriptions[planId] || 'Plano personalizado';
            
            document.getElementById('selectedPlanName').textContent = planName;
            document.getElementById('selectedPlanDescription').textContent = planDescription;
            document.getElementById('planSelected').style.display = 'block';
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

        // Validação de senha
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('password_confirm');
        
        function validatePasswords() {
            if (passwordInput.value && passwordConfirmInput.value) {
                if (passwordInput.value !== passwordConfirmInput.value) {
                    passwordConfirmInput.setCustomValidity('As senhas não coincidem');
                } else {
                    passwordConfirmInput.setCustomValidity('');
                }
            }
        }
        
        passwordInput?.addEventListener('input', validatePasswords);
        passwordConfirmInput?.addEventListener('input', validatePasswords);

        // Form animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const formElements = document.querySelectorAll('.form-group, .form-row, .form-options, .btn, .form-divider, .security-badge');
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

</body>
</html>