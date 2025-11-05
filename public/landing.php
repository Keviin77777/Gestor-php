<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UltraGestor - Sistema Profissional de Gestão IPTV</title>
    <meta name="description" content="Sistema completo de gestão para provedores IPTV com automação WhatsApp, controle de clientes e interface moderna.">
    <meta name="keywords" content="IPTV, gestão, sistema, whatsapp, automação, clientes, faturas">
    
    <!-- Open Graph -->
    <meta property="og:title" content="UltraGestor - Sistema Profissional de Gestão IPTV">
    <meta property="og:description" content="Sistema completo de gestão para provedores IPTV com automação WhatsApp">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- CSS -->
    <link href="/assets/css/landing.css" rel="stylesheet">
    <link href="/assets/css/landing-animations.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <nav class="nav container">
            <div class="nav-brand">
                <div class="brand-icon">
                    <i class="fas fa-tv"></i>
                </div>
                <span class="brand-text">UltraGestor</span>
            </div>
            
            <div class="nav-menu" id="navMenu">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="#home" class="nav-link active">Início</a>
                    </li>
                    <li class="nav-item">
                        <a href="#features" class="nav-link">Recursos</a>
                    </li>
                    <li class="nav-item">
                        <a href="#plans" class="nav-link">Planos</a>
                    </li>
                    <li class="nav-item">
                        <a href="#about" class="nav-link">Sobre</a>
                    </li>
                    <li class="nav-item">
                        <a href="#contact" class="nav-link">Contato</a>
                    </li>
                </ul>
                
                <div class="nav-actions">
                    <a href="/login" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                    <a href="/register" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Cadastrar
                    </a>
                </div>
            </div>
            
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-bg">
            <div class="hero-particles"></div>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <div class="hero-badge animate-fade-in-down">
                        <i class="fas fa-rocket"></i>
                        <span>Sistema Profissional</span>
                    </div>
                    
                    <h1 class="hero-title animate-fade-in-up stagger-1">
                        Gerencie seu negócio IPTV com
                        <span class="gradient-text">inteligência</span>
                    </h1>
                    
                    <p class="hero-description animate-fade-in-up stagger-2">
                        Sistema completo de gestão para provedores IPTV com automação WhatsApp, 
                        controle de clientes, faturas e relatórios em tempo real. 
                        Tudo que você precisa em uma plataforma moderna e intuitiva.
                    </p>
                    
                    <div class="hero-actions animate-fade-in-up stagger-3">
                        <a href="#plans" class="btn btn-primary btn-lg hover-glow">
                            <i class="fas fa-play"></i>
                            Começar Agora
                        </a>
                        <a href="#features" class="btn btn-outline btn-lg hover-pulse">
                            <i class="fas fa-info-circle"></i>
                            Saiba Mais
                        </a>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="stat">
                            <div class="stat-number">500+</div>
                            <div class="stat-label">Clientes Ativos</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number">99.9%</div>
                            <div class="stat-label">Uptime</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Suporte</div>
                        </div>
                    </div>
                </div>
                
                <div class="hero-visual">
                    <div class="dashboard-preview animate-slide-in-right hover-float">
                        <div class="preview-header">
                            <div class="preview-dots">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <div class="preview-title">UltraGestor Dashboard</div>
                        </div>
                        <div class="preview-content">
                            <div class="preview-sidebar">
                                <div class="sidebar-item active"></div>
                                <div class="sidebar-item"></div>
                                <div class="sidebar-item"></div>
                                <div class="sidebar-item"></div>
                            </div>
                            <div class="preview-main">
                                <div class="preview-cards">
                                    <div class="preview-card"></div>
                                    <div class="preview-card"></div>
                                    <div class="preview-card"></div>
                                </div>
                                <div class="preview-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">
                    <i class="fas fa-star"></i>
                    <span>Recursos</span>
                </div>
                <h2 class="section-title">
                    Tudo que você precisa para
                    <span class="gradient-text">gerenciar seu negócio</span>
                </h2>
                <p class="section-description">
                    Recursos avançados desenvolvidos especificamente para provedores IPTV
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card hover-float" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon animate-bounce-in stagger-1">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Gestão de Clientes</h3>
                    <p class="feature-description">
                        Controle completo de clientes, planos, vencimentos e histórico de pagamentos
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> CRUD completo de clientes</li>
                        <li><i class="fas fa-check"></i> Controle de planos e servidores</li>
                        <li><i class="fas fa-check"></i> Histórico detalhado</li>
                    </ul>
                </div>
                
                <div class="feature-card hover-float" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon animate-bounce-in stagger-2">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3 class="feature-title">Automação WhatsApp</h3>
                    <p class="feature-description">
                        Automação completa de mensagens, lembretes e notificações via WhatsApp
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Lembretes automáticos</li>
                        <li><i class="fas fa-check"></i> Templates personalizáveis</li>
                        <li><i class="fas fa-check"></i> Agendamento inteligente</li>
                    </ul>
                </div>
                
                <div class="feature-card hover-float" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon animate-bounce-in stagger-3">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3 class="feature-title">Múltiplos Servidores</h3>
                    <p class="feature-description">
                        Gerencie múltiplos servidores IPTV com monitoramento em tempo real
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Monitoramento 24/7</li>
                        <li><i class="fas fa-check"></i> Balanceamento de carga</li>
                        <li><i class="fas fa-check"></i> Relatórios detalhados</li>
                    </ul>
                </div>
                
                <div class="feature-card hover-float" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-icon animate-bounce-in stagger-4">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Dashboard Inteligente</h3>
                    <p class="feature-description">
                        Visualize métricas importantes e tome decisões baseadas em dados
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Gráficos em tempo real</li>
                        <li><i class="fas fa-check"></i> Relatórios customizáveis</li>
                        <li><i class="fas fa-check"></i> Alertas inteligentes</li>
                    </ul>
                </div>
                
                <div class="feature-card hover-float" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-icon animate-bounce-in stagger-5">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3 class="feature-title">Pagamentos PIX</h3>
                    <p class="feature-description">
                        Integração completa com PIX e Mercado Pago para pagamentos automáticos
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> PIX instantâneo</li>
                        <li><i class="fas fa-check"></i> Renovação automática</li>
                        <li><i class="fas fa-check"></i> Webhook em tempo real</li>
                    </ul>
                </div>
                
                <div class="feature-card hover-float" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-icon animate-bounce-in stagger-6">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">100% Responsivo</h3>
                    <p class="feature-description">
                        Interface moderna que funciona perfeitamente em qualquer dispositivo
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Design responsivo</li>
                        <li><i class="fas fa-check"></i> PWA compatível</li>
                        <li><i class="fas fa-check"></i> Performance otimizada</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Plans Section -->
    <section class="plans" id="plans">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">
                    <i class="fas fa-tags"></i>
                    <span>Planos</span>
                </div>
                <h2 class="section-title">
                    Escolha o plano ideal para
                    <span class="gradient-text">seu negócio</span>
                </h2>
                <p class="section-description">
                    Planos flexíveis que crescem junto com seu negócio
                </p>
            </div>
            
            <div class="plans-grid" id="plansGrid">
                <!-- Plans will be loaded dynamically -->
                <div class="plans-loading">
                    <div class="spinner"></div>
                    <p>Carregando planos...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <div class="section-badge">
                        <i class="fas fa-info-circle"></i>
                        <span>Sobre</span>
                    </div>
                    <h2 class="section-title">
                        Por que escolher o
                        <span class="gradient-text">UltraGestor?</span>
                    </h2>
                    <p class="section-description">
                        Desenvolvido especificamente para provedores IPTV, o UltraGestor oferece 
                        todas as ferramentas necessárias para automatizar e otimizar seu negócio.
                    </p>
                    
                    <div class="about-features">
                        <div class="about-feature">
                            <div class="about-feature-icon">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <div class="about-feature-content">
                                <h4>Performance Superior</h4>
                                <p>Sistema otimizado para alta performance e disponibilidade</p>
                            </div>
                        </div>
                        
                        <div class="about-feature">
                            <div class="about-feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="about-feature-content">
                                <h4>Segurança Avançada</h4>
                                <p>Proteção completa dos seus dados e dos seus clientes</p>
                            </div>
                        </div>
                        
                        <div class="about-feature">
                            <div class="about-feature-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="about-feature-content">
                                <h4>Suporte 24/7</h4>
                                <p>Equipe especializada sempre disponível para ajudar</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="about-actions">
                        <a href="#plans" class="btn btn-primary">
                            <i class="fas fa-play"></i>
                            Começar Agora
                        </a>
                        <a href="#contact" class="btn btn-outline">
                            <i class="fas fa-phone"></i>
                            Falar com Vendas
                        </a>
                    </div>
                </div>
                
                <div class="about-visual">
                    <div class="about-image">
                        <div class="image-placeholder">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">
                    <i class="fas fa-envelope"></i>
                    <span>Contato</span>
                </div>
                <h2 class="section-title">
                    Entre em contato
                    <span class="gradient-text">conosco</span>
                </h2>
                <p class="section-description">
                    Estamos aqui para ajudar você a crescer seu negócio
                </p>
            </div>
            
            <div class="contact-content">
                <div class="contact-info-centered">
                    <div class="contact-item" data-aos="fade-up" data-aos-delay="100">
                        <div class="contact-icon animate-pulse infinite">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="contact-details">
                            <h4>WhatsApp</h4>
                            <p>+55 (14) 99734-9352</p>
                            <a href="https://wa.me/5514997349352" target="_blank" class="contact-link hover-glow">
                                <i class="fab fa-whatsapp"></i>
                                Conversar agora
                            </a>
                        </div>
                    </div>
                    
                    <div class="contact-item" data-aos="fade-up" data-aos-delay="200">
                        <div class="contact-icon animate-bounce-in stagger-2">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Email</h4>
                            <p>ultragestorbr@gmail.com</p>
                            <a href="mailto:ultragestorbr@gmail.com" class="contact-link hover-pulse">
                                <i class="fas fa-envelope"></i>
                                Enviar email
                            </a>
                        </div>
                    </div>
                    
                    <div class="contact-item" data-aos="fade-up" data-aos-delay="300">
                        <div class="contact-icon animate-fade-in stagger-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Atendimento</h4>
                            <p>24 horas por dia</p>
                            <span class="contact-link">7 dias por semana</span>
                        </div>
                    </div>
                    
                    <div class="contact-cta" data-aos="fade-up" data-aos-delay="400">
                        <h3>Pronto para começar?</h3>
                        <p>Entre em contato conosco e descubra como o UltraGestor pode transformar seu negócio IPTV</p>
                        <div class="contact-actions">
                            <a href="https://wa.me/5514997349352" target="_blank" class="btn btn-primary btn-lg hover-glow">
                                <i class="fab fa-whatsapp"></i>
                                Falar no WhatsApp
                            </a>
                            <a href="#plans" class="btn btn-outline btn-lg hover-pulse">
                                <i class="fas fa-tags"></i>
                                Ver Planos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="brand-icon">
                        <i class="fas fa-tv"></i>
                    </div>
                    <span class="brand-text">UltraGestor</span>
                    <p class="footer-description">
                        Sistema profissional de gestão IPTV com automação WhatsApp
                    </p>
                </div>
                
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Produto</h4>
                        <ul>
                            <li><a href="#features">Recursos</a></li>
                            <li><a href="#plans">Planos</a></li>
                            <li><a href="/login">Login</a></li>
                            <li><a href="/register">Cadastro</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Suporte</h4>
                        <ul>
                            <li><a href="#contact">Contato</a></li>
                            <li><a href="#">Documentação</a></li>
                            <li><a href="#">FAQ</a></li>
                            <li><a href="#">Status</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Empresa</h4>
                        <ul>
                            <li><a href="#about">Sobre</a></li>
                            <li><a href="#">Blog</a></li>
                            <li><a href="#">Carreiras</a></li>
                            <li><a href="#">Privacidade</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 UltraGestor. Todos os direitos reservados.</p>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true,
            offset: 100
        });
    </script>
    <script src="/assets/js/landing.js"></script>

    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>