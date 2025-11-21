<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renovar Acesso - UltraGestor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <link href="/assets/css/header-menu.css" rel="stylesheet">
    <style>
        /* Renovar Acesso - Design Profissional Melhorado */
        .renew-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            min-height: calc(100vh - var(--header-height));
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        @media (max-width: 768px) {
            .renew-container {
                padding: 1rem;
            }
        }

        /* Header Melhorado */
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .page-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-title i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .page-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Current Plan Card Melhorado */
        .current-plan-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .current-plan-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .current-plan-content {
            position: relative;
            z-index: 1;
        }

        .current-plan-title {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .current-plan-name {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
        }

        .current-plan-expires {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .days-remaining {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            backdrop-filter: blur(10px);
        }

        .days-remaining.warning {
            background: rgba(245, 158, 11, 0.9);
            color: white;
        }

        .days-remaining.danger {
            background: rgba(239, 68, 68, 0.9);
            color: white;
        }

        /* Botão Renovar no Card Atual */
        .btn-renew-current {
            margin-top: 1.5rem;
            padding: 0.625rem 1.75rem;
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-renew-current:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .btn-renew-current:active {
            transform: translateY(0);
        }

        .btn-renew-current i {
            font-size: 1rem;
        }

        /* Plans Section Melhorada */
        .plans-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        /* Plan Cards Melhorados */
        .plan-card {
            background: var(--bg-primary);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .plan-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .plan-card.recommended {
            border-color: var(--success);
            transform: scale(1.02);
        }

        .plan-card.recommended::after {
            content: '⭐ Recomendado';
            position: absolute;
            top: 1rem;
            right: -2.5rem;
            background: linear-gradient(135deg, var(--success), #34d399);
            color: white;
            padding: 0.375rem 2.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            transform: rotate(45deg);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: var(--shadow);
        }

        .plan-card.current {
            border-color: var(--primary);
            background: var(--bg-secondary);
        }

        .plan-card.current::before {
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        /* Plan Header Melhorado */
        .plan-header {
            padding: 1.5rem;
            text-align: center;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .plan-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0;
            line-height: 1.4;
        }

        .plan-price {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 0.25rem;
        }

        .plan-price .currency {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .plan-price .value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }

        .plan-price.free .value {
            color: var(--success);
        }

        .plan-duration {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Plan Body Melhorado */
        .plan-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
            flex: 1;
        }

        .plan-features li {
            padding: 0.5rem 0;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .plan-features li i {
            color: var(--success);
            width: 16px;
            flex-shrink: 0;
        }

        .plan-savings {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            padding: 0.75rem;
            border-radius: var(--radius-sm);
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* Buttons Melhorados */
        .btn-select-plan {
            width: 100%;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 44px; /* Touch target mínimo */
            -webkit-tap-highlight-color: transparent;
        }

        .btn-select-plan:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .btn-select-plan:active {
            transform: translateY(0);
        }

        .btn-select-plan:disabled {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
        }

        .btn-select-plan.current {
            background: var(--success);
        }

        .btn-select-plan.unavailable {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            cursor: not-allowed;
        }

        /* Touch improvements */
        @media (hover: none) and (pointer: coarse) {
            .btn-select-plan:hover {
                transform: none;
                box-shadow: var(--shadow);
            }
            
            .plan-card:hover {
                transform: none;
                box-shadow: var(--shadow);
            }
            
            .contact-item:hover {
                transform: none;
            }
        }

        /* Payment Info Melhorada */
        .payment-info {
            background: var(--bg-primary);
            padding: 2rem;
            border-radius: var(--radius);
            text-align: center;
            margin-top: 2rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .payment-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .payment-title i {
            color: var(--primary);
        }

        .payment-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: var(--text-primary);
            font-weight: 600;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            transition: all 0.2s ease;
        }

        .contact-item:hover {
            background: var(--bg-tertiary);
            transform: translateY(-1px);
        }

        .contact-item i {
            color: var(--primary);
            font-size: 1.125rem;
        }

        /* Loading Melhorado - Centralizado */
        .loading-spinner {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 4rem 2rem;
            text-align: center;
            min-height: 60vh;
            width: 100%;
            margin: 0 auto;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        .loading-text {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsividade Completa para Mobile */
        
        /* Tablet - 1024px e abaixo */
        @media (max-width: 1024px) {
            .renew-container {
                padding: 1.5rem;
            }

            .plans-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 1.25rem;
            }

            .contact-info {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        /* Mobile Grande - 768px e abaixo */
        @media (max-width: 768px) {
            .renew-container {
                margin-left: 0;
                padding: 1rem;
                width: 100%;
            }

            /* Header Mobile */
            .page-header {
                padding: 1rem 0;
                margin-bottom: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .page-title i {
                font-size: 1.25rem;
            }

            .page-subtitle {
                font-size: 0.875rem;
            }

            /* Current Plan Card Mobile */
            .current-plan-card {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .current-plan-name {
                font-size: 1.5rem;
            }

            .current-plan-expires {
                font-size: 0.875rem;
            }

            .days-remaining {
                padding: 0.375rem 0.75rem;
                font-size: 0.8rem;
            }

            .btn-renew-current {
                margin-top: 1rem;
                padding: 0.625rem 1.25rem;
                font-size: 0.8125rem;
            }

            .btn-renew-current i {
                font-size: 0.9375rem;
            }

            /* Plans Grid Mobile */
            .plans-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .plan-card {
                margin-bottom: 0;
            }

            .plan-card.recommended {
                transform: scale(1);
            }

            .plan-card.recommended::after {
                top: 0.75rem;
                right: -2rem;
                padding: 0.25rem 2rem;
                font-size: 0.7rem;
            }

            /* Plan Cards Mobile */
            .plan-header {
                padding: 1.25rem;
                gap: 0.5rem;
            }

            .plan-name {
                font-size: 1.25rem;
            }

            .plan-description {
                font-size: 0.8rem;
            }

            .plan-price .value {
                font-size: 2rem;
            }

            .plan-price .currency {
                font-size: 1rem;
            }

            .plan-duration {
                font-size: 0.8rem;
            }

            .plan-body {
                padding: 1.25rem;
            }

            .plan-features li {
                padding: 0.375rem 0;
                font-size: 0.8rem;
            }

            .plan-savings {
                padding: 0.5rem;
                font-size: 0.8rem;
                margin-bottom: 1rem;
            }

            .btn-select-plan {
                padding: 0.75rem;
                font-size: 0.8rem;
            }

            /* Payment Info Mobile */
            .payment-info {
                padding: 1.5rem;
                margin-top: 1.5rem;
            }

            .payment-title {
                font-size: 1.125rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .payment-description {
                font-size: 0.875rem;
            }

            .contact-info {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .contact-item {
                padding: 0.75rem;
                font-size: 0.875rem;
            }

            .contact-item i {
                font-size: 1rem;
            }
        }

        /* Mobile Pequeno - 480px e abaixo */
        @media (max-width: 480px) {
            .renew-container {
                padding: 0.75rem;
            }

            /* Header Ultra Compacto */
            .page-header {
                padding: 0.75rem 0;
                margin-bottom: 1rem;
            }

            .page-title {
                font-size: 1.25rem;
            }

            .page-subtitle {
                font-size: 0.8rem;
            }

            /* Current Plan Card Ultra Compacto */
            .current-plan-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .current-plan-title {
                font-size: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .current-plan-name {
                font-size: 1.25rem;
                margin-bottom: 0.5rem;
            }

            .current-plan-expires {
                font-size: 0.8rem;
                margin-bottom: 0.75rem;
            }

            .days-remaining {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
                gap: 0.25rem;
            }

            .btn-renew-current {
                margin-top: 0.75rem;
                padding: 0.5rem 1rem;
                font-size: 0.75rem;
            }

            .btn-renew-current i {
                font-size: 0.875rem;
            }

            /* Section Title */
            .section-title {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }

            /* Plan Cards Ultra Compacto */
            .plan-card.recommended::after {
                display: none; /* Esconder badge em telas muito pequenas */
            }

            .plan-header {
                padding: 1rem;
            }

            .plan-name {
                font-size: 1.125rem;
            }

            .plan-description {
                font-size: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .plan-price .value {
                font-size: 1.75rem;
            }

            .plan-price .currency {
                font-size: 0.875rem;
            }

            .plan-duration {
                font-size: 0.75rem;
            }

            .plan-body {
                padding: 1rem;
            }

            .plan-features {
                margin-bottom: 1rem;
            }

            .plan-features li {
                padding: 0.25rem 0;
                font-size: 0.75rem;
            }

            .plan-features li i {
                width: 14px;
            }

            .plan-savings {
                padding: 0.375rem;
                font-size: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .btn-select-plan {
                padding: 0.625rem;
                font-size: 0.75rem;
                gap: 0.25rem;
            }

            /* Payment Info Ultra Compacto */
            .payment-info {
                padding: 1rem;
                margin-top: 1rem;
            }

            .payment-title {
                font-size: 1rem;
                margin-bottom: 0.75rem;
            }

            .payment-description {
                font-size: 0.8rem;
                margin-bottom: 1rem;
            }

            .contact-item {
                padding: 0.625rem;
                font-size: 0.8rem;
                gap: 0.5rem;
            }

            .contact-item i {
                font-size: 0.875rem;
            }

            /* Loading Mobile */
            .loading-spinner {
                padding: 2rem;
                min-height: 40vh;
                margin: 0 auto;
            }

            .spinner {
                width: 32px;
                height: 32px;
            }
        }

        /* Mobile Extra Pequeno - 360px e abaixo */
        @media (max-width: 360px) {
            .renew-container {
                padding: 0.5rem;
            }

            .page-title {
                font-size: 1.125rem;
            }

            .current-plan-card {
                padding: 0.75rem;
            }

            .current-plan-name {
                font-size: 1.125rem;
            }

            .plan-header {
                padding: 0.75rem;
            }

            .plan-body {
                padding: 0.75rem;
            }

            .plan-price .value {
                font-size: 1.5rem;
            }

            .payment-info {
                padding: 0.75rem;
            }

            .contact-item {
                padding: 0.5rem;
                font-size: 0.75rem;
            }

            .btn-renew-current {
                margin-top: 0.5rem;
                padding: 0.5rem 0.875rem;
                font-size: 0.7rem;
                gap: 0.375rem;
            }

            .btn-renew-current i {
                font-size: 0.8rem;
            }
        }
    </style>
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
        <div class="renew-container">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-sync-alt"></i>
                Renovar Acesso
            </h1>
            <p class="page-subtitle">
                Escolha o melhor plano para continuar usando o UltraGestor
            </p>
        </div>

        <!-- Loading -->
        <div id="loadingSpinner" class="loading-spinner">
            <div class="spinner"></div>
            <div class="loading-text">Carregando planos...</div>
        </div>

        <!-- Conteúdo Principal -->
        <div id="mainContent" style="display: none;">
            <!-- Plano Atual -->
            <div id="currentPlanCard" class="current-plan-card">
                <div class="current-plan-content">
                    <!-- Será preenchido via JavaScript -->
                </div>
            </div>

            <!-- Planos Disponíveis -->
            <div class="plans-section">
                <h2 class="section-title">Planos Disponíveis</h2>
                <div id="plansGrid" class="plans-grid">
                    <!-- Será preenchido via JavaScript -->
                </div>
            </div>

            <!-- Informações de Pagamento -->
            <div class="payment-info">
                <h3 class="payment-title">
                    <i class="fas fa-credit-card"></i>
                    Como Renovar?
                </h3>
                <p class="payment-description">
                    Para renovar seu acesso, entre em contato conosco através dos canais abaixo. 
                    Nossa equipe irá te auxiliar com o processo de pagamento e ativação do seu novo plano.
                </p>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fab fa-whatsapp"></i>
                        <span>(11) 99999-9999</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>suporte@ultragestor.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <span>Seg-Sex: 8h às 18h</span>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/theme-global.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script>
        let currentUser = null;
        let availablePlans = [];

        // Função para gerar descrição simples do plano
        function getSimplePlanDescription(plan) {
            if (plan.is_trial) {
                return 'Período de teste gratuito';
            }
            
            // Gerar descrição baseada na duração
            if (plan.duration_days <= 7) {
                return 'Plano de curta duração';
            } else if (plan.duration_days <= 31) {
                return 'Renovação mensal';
            } else if (plan.duration_days <= 93) {
                return 'Plano trimestral com desconto';
            } else if (plan.duration_days <= 186) {
                return 'Plano semestral econômico';
            } else if (plan.duration_days <= 365) {
                return 'Plano anual com máximo desconto';
            } else {
                return 'Plano personalizado';
            }
        }

        // Carregar dados ao inicializar
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                // Carregar dados do usuário e planos em paralelo
                await Promise.all([
                    loadUserData(),
                    loadPlans()
                ]);
                
                // Após carregar ambos, re-renderizar o plano atual com dados completos
                if (currentUser && availablePlans) {
                    renderCurrentPlan();
                }
                
                } catch (error) {
                hideLoading();
            }
        });

        // Carregar dados do usuário atual
        async function loadUserData() {
            try {
                const response = await fetch('/api-auth-me.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    currentUser = data.user;
                    renderCurrentPlan();
                } else {
                    throw new Error(data.error || 'Erro ao carregar dados do usuário');
                }
            } catch (error) {
                // Se for erro 401, redirecionar para login
                if (error.message.includes('401') || error.message.includes('Não autorizado')) {
                    showError('Sessão expirada. Redirecionando para login...');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                } else {
                    showError('Erro ao carregar dados do usuário: ' + error.message);
                }
            }
        }

        // Carregar planos disponíveis
        async function loadPlans() {
            try {
                const response = await fetch('/api-reseller-plans.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Para revendedores, todos os planos retornados já são ativos
                    availablePlans = data.plans;
                    renderPlansGrid();
                    hideLoading();
                } else {
                    throw new Error(data.error || 'Erro ao carregar planos');
                }
            } catch (error) {
                // Se for erro 401, redirecionar para login
                if (error.message.includes('401') || error.message.includes('Não autorizado')) {
                    showError('Sessão expirada. Redirecionando para login...');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                } else {
                    showError('Erro ao carregar planos: ' + error.message);
                }
                hideLoading();
            }
        }

        // Renderizar plano atual
        function renderCurrentPlan() {
            if (!currentUser) return;
            
            const content = document.querySelector('.current-plan-content');
            
            // Usar o valor de days_remaining que vem da API (já calculado corretamente no backend)
            const daysRemaining = currentUser.days_remaining || 0;
            
            let daysClass = '';
            let daysText = '';
            let daysIcon = '';
            
            if (daysRemaining < 0) {
                daysClass = 'danger';
                daysText = `Vencido há ${Math.abs(daysRemaining)} ${Math.abs(daysRemaining) === 1 ? 'dia' : 'dias'}`;
                daysIcon = '<i class="fas fa-exclamation-triangle"></i>';
            } else if (daysRemaining === 0) {
                daysClass = 'danger';
                daysText = 'Vence hoje';
                daysIcon = '<i class="fas fa-clock"></i>';
            } else if (daysRemaining <= 3) {
                daysClass = 'danger';
                daysText = `${daysRemaining} ${daysRemaining === 1 ? 'dia restante' : 'dias restantes'}`;
                daysIcon = '<i class="fas fa-exclamation-circle"></i>';
            } else if (daysRemaining <= 7) {
                daysClass = 'warning';
                daysText = `${daysRemaining} dias restantes`;
                daysIcon = '<i class="fas fa-clock"></i>';
            } else {
                daysText = `${daysRemaining} dias restantes`;
                daysIcon = '<i class="fas fa-check-circle"></i>';
            }
            
            // Buscar nome do plano atual - primeiro tenta dos dados do usuário, depois dos planos disponíveis
            let planName = currentUser.plan_name || 'Plano Desconhecido';
            
            // Se não tem o nome nos dados do usuário, busca nos planos disponíveis
            if (planName === 'Plano Desconhecido' && availablePlans && availablePlans.length > 0) {
                const currentPlan = availablePlans.find(p => p.id === currentUser.current_plan_id);
                planName = currentPlan ? currentPlan.name : 'Plano Desconhecido';
            }
            
            // Se ainda não encontrou e o plano é trial, usar nome padrão
            if (planName === 'Plano Desconhecido' && currentUser.current_plan_id === 'plan-trial') {
                planName = 'Trial 3 Dias';
            }
            
            // Verificar se o plano atual é trial
            const isTrialPlan = currentUser.current_plan_id === 'plan-trial' || 
                               (availablePlans && availablePlans.find(p => p.id === currentUser.current_plan_id)?.is_trial);
            
            // Botão de renovação (apenas para planos não-trial)
            const renewButton = !isTrialPlan ? `
                <button class="btn-renew-current" onclick="renewCurrentPlan()">
                    <i class="fas fa-sync-alt"></i>
                    Renovar Plano
                </button>
            ` : '';
            
            content.innerHTML = `
                <div class="current-plan-title">Seu Plano Atual</div>
                <div class="current-plan-name">${planName}</div>
                <div class="current-plan-expires">
                    Vence em: ${formatDate(currentUser.plan_expires_at)}
                </div>
                <div class="days-remaining ${daysClass}">
                    ${daysIcon}
                    ${daysText}
                </div>
                ${renewButton}
            `;
        }
        
        // Renovar plano atual
        async function renewCurrentPlan() {
            if (!currentUser || !availablePlans) {
                showError('Dados não carregados. Tente novamente.');
                return;
            }
            
            // Buscar o plano atual nos planos disponíveis
            const currentPlan = availablePlans.find(p => p.id === currentUser.current_plan_id);
            
            if (!currentPlan) {
                showError('Plano atual não encontrado. Entre em contato com o suporte.');
                return;
            }
            
            if (currentPlan.is_trial) {
                showInfo('O plano trial não pode ser renovado. Escolha um plano pago abaixo.');
                return;
            }
            
            // Gerar PIX para renovação do mesmo plano
            await generatePixPayment(currentPlan);
        }

        // Renderizar grid de planos
        function renderPlansGrid() {
            const grid = document.getElementById('plansGrid');
            
            if (!availablePlans || availablePlans.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-secondary);">
                        <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">Nenhum plano disponível</h3>
                        <p>Entre em contato conosco para mais informações sobre os planos.</p>
                    </div>
                `;
                return;
            }
            
            // Ordenar planos: trial primeiro, depois por preço
            const sortedPlans = [...availablePlans].sort((a, b) => {
                if (a.is_trial && !b.is_trial) return -1;
                if (!a.is_trial && b.is_trial) return 1;
                return a.price - b.price;
            });
            
            grid.innerHTML = sortedPlans.map((plan, index) => {
                const isCurrentPlan = currentUser && currentUser.current_plan_id === plan.id;
                const isRecommended = !plan.is_trial && index === 1; // Segundo plano pago como recomendado
                
                // Calcular economia para planos anuais/semestrais
                let savings = '';
                if (plan.duration_days >= 180) {
                    const monthlyEquivalent = availablePlans.find(p => p.duration_days === 30 && !p.is_trial);
                    if (monthlyEquivalent) {
                        const monthlyTotal = (monthlyEquivalent.price * plan.duration_days / 30);
                        const savingsAmount = monthlyTotal - plan.price;
                        const savingsPercent = ((savingsAmount / monthlyTotal) * 100).toFixed(0);
                        
                        if (savingsAmount > 0) {
                            savings = `
                                <div class="plan-savings">
                                    Economize ${savingsPercent}% (R$ ${savingsAmount.toFixed(2).replace('.', ',')})
                                </div>
                            `;
                        }
                    }
                }
                
                return `
                    <div class="plan-card ${isRecommended ? 'recommended' : ''} ${isCurrentPlan ? 'current' : ''}">
                        <div class="plan-header">
                            <h3 class="plan-name">${plan.name}</h3>
                            <p class="plan-description">${getSimplePlanDescription(plan)}</p>
                            <div class="plan-price ${plan.price === 0 ? 'free' : ''}">
                                <span class="currency">R$</span>
                                <span class="value">${plan.price.toFixed(2).replace('.', ',')}</span>
                            </div>
                            <div class="plan-duration">por ${plan.duration_days} dias</div>
                        </div>
                        
                        <div class="plan-body">
                            ${savings}
                            
                            <ul class="plan-features">
                                <li><i class="fas fa-check-circle"></i> ${plan.duration_days} dias de acesso</li>
                                <li><i class="fas fa-check-circle"></i> Gerenciamento de clientes</li>
                                <li><i class="fas fa-check-circle"></i> Controle de faturas</li>
                                <li><i class="fas fa-check-circle"></i> Relatórios completos</li>
                                <li><i class="fas fa-check-circle"></i> Suporte técnico</li>
                                ${!plan.is_trial ? '<li><i class="fas fa-check-circle"></i> Recursos ilimitados</li>' : '<li><i class="fas fa-info-circle"></i> Período de teste</li>'}
                            </ul>
                            
                            <button 
                                class="btn-select-plan ${isCurrentPlan ? 'current' : ''} ${plan.is_trial ? 'unavailable' : ''}" 
                                ${isCurrentPlan || plan.is_trial ? 'disabled' : ''}
                                onclick="selectPlan('${plan.id}')"
                            >
                                ${isCurrentPlan ? 
                                    '<i class="fas fa-check"></i> Plano Atual' : 
                                    plan.is_trial ? 
                                        '<i class="fas fa-times"></i> Indisponível' : 
                                        '<i class="fas fa-shopping-cart"></i> Selecionar Plano'
                                }
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Selecionar plano e gerar PIX
        async function selectPlan(planId) {
            const plan = availablePlans.find(p => p.id === planId);
            
            if (!plan) {
                showError('Plano não encontrado');
                return;
            }
            
            if (plan.is_trial) {
                showInfo('O plano trial não está disponível para renovação. Entre em contato conosco para mais informações.');
                return;
            }
            
            // Gerar PIX automaticamente
            await generatePixPayment(plan);
        }
        
        // Gerar pagamento PIX
        async function generatePixPayment(plan) {
            try {
                showInfo('Gerando PIX... Aguarde...');
                
                const response = await fetch('/api-reseller-renew-pix.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    },
                    body: JSON.stringify({
                        plan_id: plan.id
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showPixModal(result, plan);
                } else {
                    showError(result.error || 'Erro ao gerar PIX');
                }
            } catch (error) {
                showError('Erro ao gerar PIX. Tente novamente.');
            }
        }

        // Funções de notificação
        function showSuccess(message) {
            const notification = document.createElement('div');
            const isMobile = window.innerWidth <= 768;
            
            notification.style.cssText = `
                position: fixed;
                ${isMobile ? 'top: 10px; left: 10px; right: 10px;' : 'top: 20px; right: 20px;'}
                background: var(--success);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-sm);
                box-shadow: var(--shadow-lg);
                z-index: 9999;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                animation: ${isMobile ? 'slideInDown' : 'slideInRight'} 0.3s ease;
                ${isMobile ? '' : 'max-width: 400px;'}
                font-size: ${isMobile ? '0.875rem' : '1rem'};
            `;
            notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = `${isMobile ? 'slideOutUp' : 'slideOutRight'} 0.3s ease`;
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        function showError(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--danger);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-sm);
                box-shadow: var(--shadow-lg);
                z-index: 9999;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                animation: slideInRight 0.3s ease;
                max-width: 400px;
            `;
            notification.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 6000);
        }

        function showInfo(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--info);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-sm);
                box-shadow: var(--shadow-lg);
                z-index: 9999;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                animation: slideInRight 0.3s ease;
                max-width: 400px;
            `;
            notification.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Utilitários
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('mainContent').style.display = 'block';
        }

        // Mostrar modal com PIX
        function showPixModal(pixData, plan) {
            // Criar modal
            const modal = document.createElement('div');
            modal.id = 'pixModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.8);
                backdrop-filter: blur(4px);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                animation: fadeIn 0.3s ease;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: var(--bg-primary);
                border-radius: 16px;
                max-width: 500px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: slideUp 0.3s ease;
            `;
            
            modalContent.innerHTML = `
                <div style="padding: 2rem;">
                    <!-- Header -->
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="fas fa-qrcode" style="font-size: 1.5rem; color: white;"></i>
                        </div>
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 0 0 0.5rem 0;">
                            Pagamento via PIX
                        </h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">
                            Escaneie o QR Code ou copie o código
                        </p>
                    </div>
                    
                    <!-- Plano Info -->
                    <div style="background: var(--bg-secondary); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">Plano:</span>
                            <span style="color: var(--text-primary); font-weight: 600;">${plan.name}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">Valor:</span>
                            <span style="color: var(--primary); font-weight: 700; font-size: 1.25rem;">R$ ${plan.price.toFixed(2).replace('.', ',')}</span>
                        </div>
                    </div>
                    
                    <!-- QR Code -->
                    <div style="background: white; padding: 1.5rem; border-radius: 12px; text-align: center; margin-bottom: 1.5rem; border: 2px solid var(--border);">
                        <div id="qrCodeContainer" style="display: flex; justify-content: center; align-items: center; min-height: 250px;">
                            <div class="spinner"></div>
                        </div>
                    </div>
                    
                    <!-- Código PIX -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; color: var(--text-secondary); font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">
                            Código PIX Copia e Cola:
                        </label>
                        <div style="position: relative;">
                            <textarea 
                                id="pixCode" 
                                readonly 
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-family: monospace; font-size: 0.75rem; resize: none; background: var(--bg-secondary); color: var(--text-primary);"
                                rows="3"
                            ></textarea>
                            <button 
                                onclick="copyPixCode()" 
                                style="position: absolute; top: 0.5rem; right: 0.5rem; background: var(--primary); color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;"
                            >
                                <i class="fas fa-copy"></i>
                                Copiar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Instruções -->
                    <div style="background: rgba(99, 102, 241, 0.1); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid rgba(99, 102, 241, 0.2);">
                        <h4 style="color: var(--primary); font-size: 0.875rem; font-weight: 600; margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-info-circle"></i>
                            Como pagar:
                        </h4>
                        <ol style="margin: 0; padding-left: 1.25rem; color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
                            <li>Abra o app do seu banco</li>
                            <li>Escolha pagar com PIX</li>
                            <li>Escaneie o QR Code ou cole o código</li>
                            <li>Confirme o pagamento</li>
                        </ol>
                    </div>
                    
                    <!-- Status -->
                    <div id="paymentStatus" style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: var(--warning);">
                            <div class="spinner-small"></div>
                            <span style="font-size: 0.875rem; font-weight: 600;">Aguardando pagamento...</span>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div style="display: flex; gap: 0.75rem;">
                        <button 
                            onclick="closePixModal()" 
                            style="flex: 1; padding: 0.75rem; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border); border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.875rem;"
                        >
                            Fechar
                        </button>
                        <button 
                            onclick="checkPaymentStatus('${pixData.payment_id}')" 
                            style="flex: 1; padding: 0.75rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                        >
                            <i class="fas fa-sync-alt"></i>
                            Verificar Pagamento
                        </button>
                    </div>
                </div>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Preencher código PIX
            document.getElementById('pixCode').value = pixData.qr_code;
            
            // Carregar biblioteca QRCode e gerar QR Code
            if (typeof QRCode === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
                script.onload = () => generateQRCode(pixData.qr_code);
                document.head.appendChild(script);
            } else {
                generateQRCode(pixData.qr_code);
            }
            
            // Iniciar verificação automática
            startPaymentCheck(pixData.payment_id);
        }
        
        // Gerar QR Code
        function generateQRCode(pixCode) {
            setTimeout(() => {
                const container = document.getElementById('qrCodeContainer');
                if (container) {
                    container.innerHTML = '';
                    try {
                        new QRCode(container, {
                            text: pixCode,
                            width: 250,
                            height: 250,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    } catch (error) {
                        console.error('Erro ao gerar QR Code:', error);
                        container.innerHTML = '<p style="color: var(--danger);">Erro ao gerar QR Code. Use o código copia e cola.</p>';
                    }
                }
            }, 100);
        }
        
        // Copiar código PIX
        function copyPixCode() {
            const textarea = document.getElementById('pixCode');
            textarea.select();
            document.execCommand('copy');
            showSuccess('Código PIX copiado!');
        }
        
        // Fechar modal
        function closePixModal() {
            const modal = document.getElementById('pixModal');
            if (modal) {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => modal.remove(), 300);
            }
            
            // Parar verificação automática
            if (window.paymentCheckInterval) {
                clearInterval(window.paymentCheckInterval);
            }
        }
        
        // Verificar status do pagamento
        async function checkPaymentStatus(paymentId) {
            try {
                const response = await fetch(`/api-check-payment-status.php?payment_id=${paymentId}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const statusDiv = document.getElementById('paymentStatus');
                    
                    if (result.status === 'approved') {
                        statusDiv.innerHTML = `
                            <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: var(--success);">
                                <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                                <span style="font-size: 0.875rem; font-weight: 600;">Pagamento aprovado!</span>
                            </div>
                        `;
                        
                        // Parar verificação
                        if (window.paymentCheckInterval) {
                            clearInterval(window.paymentCheckInterval);
                        }
                        
                        // Recarregar dados após 2 segundos
                        setTimeout(() => {
                            closePixModal();
                            showSuccess('Seu acesso foi renovado com sucesso!');
                            loadUserData();
                        }, 2000);
                    } else if (result.status === 'rejected' || result.status === 'cancelled') {
                        statusDiv.innerHTML = `
                            <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: var(--danger);">
                                <i class="fas fa-times-circle" style="font-size: 1.5rem;"></i>
                                <span style="font-size: 0.875rem; font-weight: 600;">Pagamento não aprovado</span>
                            </div>
                        `;
                        
                        if (window.paymentCheckInterval) {
                            clearInterval(window.paymentCheckInterval);
                        }
                    }
                }
            } catch (error) {
                }
        }
        
        // Iniciar verificação automática
        function startPaymentCheck(paymentId) {
            // Verificar a cada 5 segundos
            window.paymentCheckInterval = setInterval(() => {
                checkPaymentStatus(paymentId);
            }, 5000);
            
            // Parar após 10 minutos
            setTimeout(() => {
                if (window.paymentCheckInterval) {
                    clearInterval(window.paymentCheckInterval);
                }
            }, 600000);
        }

        // Adicionar animações CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes slideOutRight {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .spinner-small {
                width: 16px;
                height: 16px;
                border: 2px solid var(--border);
                border-top: 2px solid var(--warning);
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
