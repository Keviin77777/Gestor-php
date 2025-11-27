<?php
require_once __DIR__ . '/../../helpers/functions.php';
loadEnv(__DIR__ . '/../../.env');
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::user();
if (!$user) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fila de Mensagens - WhatsApp</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/header-menu.css">
    <link rel="stylesheet" href="/assets/css/whatsapp-queue.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/../components/header-menu.php'; ?>
        
        <div class="content-wrapper">
            <div class="queue-page">
                
                <!-- Header -->
                <div class="page-header">
                    <div>
                        <h1>📬 Fila de Mensagens WhatsApp</h1>
                        <p>Gerencie e monitore o envio de mensagens em massa</p>
                        <small style="color: var(--text-secondary); margin-top: 0.5rem; display: block;">
                            💡 Configure os limites de envio na aba <a href="/whatsapp/parear" style="color: #667eea;">Parear WhatsApp</a>
                        </small>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="header-actions">
                        <button class="btn-action btn-success" onclick="forceProcessQueue()" title="Forçar processamento imediato da fila">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                            Forçar Envio
                        </button>
                        <button class="btn-action btn-danger" onclick="deleteSentMessages()" title="Excluir mensagens já enviadas">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            Excluir Enviadas
                        </button>
                        <button class="btn-action btn-danger-outline" onclick="deleteAllMessages()" title="Excluir todas as mensagens do histórico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            Excluir Todas
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card pending">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <span class="stat-label">Pendentes</span>
                            <span class="stat-value" id="pendingCount">0</span>
                        </div>
                    </div>
                    
                    <div class="stat-card processing">
                        <div class="stat-icon">🔄</div>
                        <div class="stat-info">
                            <span class="stat-label">Processando</span>
                            <span class="stat-value" id="processingCount">0</span>
                        </div>
                    </div>
                    
                    <div class="stat-card sent">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <span class="stat-label">Enviadas</span>
                            <span class="stat-value" id="sentCount">0</span>
                        </div>
                    </div>
                    
                    <div class="stat-card failed">
                        <div class="stat-icon">❌</div>
                        <div class="stat-info">
                            <span class="stat-label">Falhas</span>
                            <span class="stat-value" id="failedCount">0</span>
                        </div>
                    </div>
                </div>

                <!-- Rate Limit Info -->
                <div class="rate-limit-info" id="rateLimitInfo">
                    <div class="rate-limit-card">
                        <h3>⚙️ Configuração Atual</h3>
                        <div class="rate-limit-details">
                            <div class="rate-item">
                                <span>Mensagens por minuto:</span>
                                <strong id="ratePerMinute">20</strong>
                            </div>
                            <div class="rate-item">
                                <span>Mensagens por hora:</span>
                                <strong id="ratePerHour">100</strong>
                            </div>
                            <div class="rate-item">
                                <span>Delay entre mensagens:</span>
                                <strong id="rateDelay">3s</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-bar">
                    <select id="statusFilter" onchange="loadQueue()">
                        <option value="">Todos os Status</option>
                        <option value="pending">Pendentes</option>
                        <option value="processing">Processando</option>
                        <option value="sent">Enviadas</option>
                        <option value="failed">Falhas</option>
                    </select>
                    
                    <button class="btn-secondary" onclick="loadQueue()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                        Atualizar
                    </button>
                </div>

                <!-- Queue Table -->
                <div class="queue-table-container">
                    <table class="queue-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Telefone</th>
                                <th>Mensagem</th>
                                <th>Status</th>
                                <th>Agendado</th>
                                <th>Tentativas</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="queueTableBody">
                            <tr>
                                <td colspan="7" class="loading-row">
                                    <div class="spinner"></div>
                                    Carregando fila...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="paginationContainer"></div>

            </div>
        </div>
    </main>



    <script src="/assets/js/auth.js"></script>
    <script src="/assets/js/whatsapp-queue.js"></script>
</body>
</html>
