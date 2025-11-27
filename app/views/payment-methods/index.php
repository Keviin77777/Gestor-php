<?php
// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Carregar dependências
require_once __DIR__ . '/../../helpers/functions.php';
loadEnv(__DIR__ . '/../../.env');

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';

// Verificar se é admin
$isAdmin = false;

try {
    $currentUser = Auth::user();
    
    if ($currentUser) {
        $userFromDB = Database::fetch(
            "SELECT * FROM users WHERE id = ? OR email = ?",
            [$currentUser['id'] ?? '', $currentUser['email'] ?? '']
        );
        
        if ($userFromDB) {
            $role = strtolower(trim($userFromDB['role'] ?? ''));
            if ($role === 'admin') {
                $isAdmin = true;
            }
            
            if (!$isAdmin && isset($userFromDB['is_admin'])) {
                $isAdminValue = $userFromDB['is_admin'];
                if ($isAdminValue === 1 || $isAdminValue === true || $isAdminValue === '1' || $isAdminValue === 1.0) {
                    $isAdmin = true;
                }
            }
        }
    }
} catch (Exception $e) {
    // Se houver erro de conexão, continuar sem verificar admin
    error_log("Erro ao verificar admin: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métodos de Pagamento - UltraGestor</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/assets/css/header-menu.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/assets/css/payment-methods.css?v=<?= time() ?>">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header Menu -->
        <?php include __DIR__ . '/../components/header-menu.php'; ?>
        
        <!-- Content -->
        <div class="content-wrapper">
            <div class="payment-methods-page">
                
                <!-- Header -->
                <div class="page-header">
                    <div class="page-header-content">
                        <h1>Métodos de Pagamento</h1>
                        <p>Configure os provedores de pagamento disponíveis para seus clientes</p>
                    </div>
                </div>
                
                <!-- Payment Providers Grid -->
                <div class="payment-providers-grid">
                    
                    <!-- Mercado Pago -->
                    <div class="provider-card" data-provider="mercadopago">
                        <div class="provider-card-header">
                            <div class="provider-logo mercadopago-logo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </div>
                            <div class="provider-info">
                                <h3>Mercado Pago</h3>
                                <p>Pagamentos via PIX com QR Code</p>
                            </div>
                            <div class="provider-status" id="mpStatus">
                                <span class="status-badge status-inactive">Inativo</span>
                            </div>
                        </div>
                        <div class="provider-card-body">
                            <button class="btn-configure" onclick="openProviderModal('mercadopago')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Configurar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Asaas -->
                    <div class="provider-card" data-provider="asaas">
                        <div class="provider-card-header">
                            <div class="provider-logo asaas-logo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 6v6l4 2"></path>
                                </svg>
                            </div>
                            <div class="provider-info">
                                <h3>Asaas</h3>
                                <p>Pagamentos via PIX com QR Code</p>
                            </div>
                            <div class="provider-status" id="asaasStatus">
                                <span class="status-badge status-inactive">Inativo</span>
                            </div>
                        </div>
                        <div class="provider-card-body">
                            <button class="btn-configure" onclick="openProviderModal('asaas')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Configurar
                            </button>
                        </div>
                    </div>
                    
                    <!-- EFI Bank - Apenas Admin -->
                    <?php if ($isAdmin): ?>
                    <div class="provider-card" data-provider="efibank">
                        <div class="provider-card-header">
                            <div class="provider-logo efibank-logo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                    <path d="M2 17l10 5 10-5"></path>
                                    <path d="M2 12l10 5 10-5"></path>
                                </svg>
                            </div>
                            <div class="provider-info">
                                <h3>EFI Bank</h3>
                                <p>Pagamentos via PIX (Apenas Admin)</p>
                            </div>
                            <div class="provider-status" id="efiStatus">
                                <span class="status-badge status-inactive">Inativo</span>
                            </div>
                        </div>
                        <div class="provider-card-body">
                            <button class="btn-configure" onclick="openProviderModal('efibank')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Configurar
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Info Box -->
                <div class="info-section">
                    <div class="info-card">
                        <div class="info-card-header">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <h4>Como Funciona</h4>
                        </div>
                        <div class="info-card-body">
                            <ul>
                                <li>Configure um ou mais provedores de pagamento</li>
                                <li>Seus clientes poderão pagar faturas via PIX automaticamente</li>
                                <li>Renovações automáticas no gestor e Sigma após pagamento</li>
                                <li>Notificações via WhatsApp quando o pagamento for confirmado</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>

    <!-- Modal de Configuração -->
    <div id="providerModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalTitle">Configurar Provedor</h2>
                <button class="modal-close" onclick="closeProviderModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Conteúdo dinâmico -->
            </div>
        </div>
    </div>

    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/access-control.js"></script>
    <script src="/assets/js/mobile-responsive.js"></script>
    <script src="/assets/js/payment-methods.js"></script>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
