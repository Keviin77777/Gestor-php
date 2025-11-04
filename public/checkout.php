<?php
/**
 * Checkout de Pagamento - Fatura
 * Página pública para pagamento de faturas via PIX
 */

// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carregar funções auxiliares e Database
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';

// Obter ID da fatura
$invoiceId = $_GET['invoice'] ?? null;

if (!$invoiceId) {
    die('Fatura não encontrada');
}

try {
    // Testar conexão
    $db = Database::connect();
    
    // Buscar fatura
    $invoice = Database::fetch(
        "SELECT i.*, c.name as client_name, c.email as client_email, c.phone as client_phone,
                i.reseller_id, u.name as reseller_name
         FROM invoices i
         JOIN clients c ON i.client_id = c.id
         LEFT JOIN users u ON i.reseller_id = u.id
         WHERE i.id = ?",
        [$invoiceId]
    );

    if (!$invoice) {
        die('Fatura não encontrada no banco de dados');
    }
} catch (Exception $e) {
    die('Erro ao buscar fatura: ' . $e->getMessage() . '<br>Trace: ' . $e->getTraceAsString());
}

// Verificar se tem método de pagamento configurado
$hasPaymentMethod = Database::fetch(
    "SELECT id FROM payment_methods 
     WHERE reseller_id = ? AND provider = 'mercadopago' AND is_active = 1
     LIMIT 1",
    [$invoice['reseller_id']]
);

$canPayOnline = $hasPaymentMethod && $invoice['status'] !== 'paid';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Seguro - Fatura #<?= substr($invoice['id'], -8) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg-primary: #1e293b;
            --bg-secondary: #0f172a;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --border: #334155;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
            --radius: 16px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            background-attachment: fixed;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 50%);
            animation: rotate 30s linear infinite;
            pointer-events: none;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .checkout-container {
            max-width: 600px;
            width: 100%;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .checkout-card {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }

        .checkout-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.1);
        }

        .checkout-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            padding: 3rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .checkout-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .checkout-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .checkout-header h1 i {
            margin-right: 0.5rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .checkout-header p {
            opacity: 0.95;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .checkout-body {
            padding: 2rem;
        }

        .invoice-details {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(30, 41, 59, 0.6) 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .invoice-details::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.5), transparent);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .detail-value.amount {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.paid {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .btn-pay {
            width: 100%;
            padding: 1.25rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
        }

        .btn-pay::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-pay:hover::before {
            left: 100%;
        }

        .btn-pay:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 40px rgba(16, 185, 129, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.2);
        }

        .btn-pay:active {
            transform: translateY(-1px) scale(0.98);
        }

        .btn-pay:disabled {
            background: var(--border);
            cursor: not-allowed;
            transform: none;
        }

        .btn-pay i {
            font-size: 1.25rem;
        }

        .payment-info {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
            text-align: center;
        }

        .payment-info i {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .payment-info p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .checkout-header {
                padding: 1.5rem;
            }

            .checkout-header h1 {
                font-size: 1.25rem;
            }

            .checkout-body {
                padding: 1.5rem;
            }

            .invoice-details {
                padding: 1rem;
            }

            .detail-value.amount {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-card">
            <!-- Header -->
            <div class="checkout-header">
                <h1><i class="fas fa-file-invoice"></i> Fatura #<?= $invoice['id'] ?></h1>
                <p><?= htmlspecialchars($invoice['reseller_name'] ?? 'UltraGestor') ?></p>
            </div>

            <!-- Body -->
            <div class="checkout-body">
                <!-- Detalhes da Fatura -->
                <div class="invoice-details">
                    <div class="detail-row">
                        <span class="detail-label">Cliente:</span>
                        <span class="detail-value"><?= htmlspecialchars($invoice['client_name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Vencimento:</span>
                        <span class="detail-value"><?= date('d/m/Y', strtotime($invoice['due_date'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge <?= $invoice['status'] ?>">
                            <?= $invoice['status'] === 'paid' ? 'Paga' : 'Pendente' ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Valor:</span>
                        <span class="detail-value amount">R$ <?= number_format($invoice['value'], 2, ',', '.') ?></span>
                    </div>
                </div>

                <?php if ($canPayOnline): ?>
                    <!-- Botão de Pagamento -->
                    <button class="btn-pay" onclick="generatePix()">
                        <i class="fas fa-qrcode"></i>
                        Pagar com PIX
                    </button>

                    <!-- Info -->
                    <div class="payment-info">
                        <i class="fas fa-shield-alt"></i>
                        <p>
                            Pagamento seguro via Mercado Pago.<br>
                            Após o pagamento, sua fatura será atualizada automaticamente.
                        </p>
                    </div>
                <?php elseif ($invoice['status'] === 'paid'): ?>
                    <div class="payment-info" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2);">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        <p style="color: var(--success); font-weight: 600;">
                            Esta fatura já foi paga!
                        </p>
                    </div>
                <?php else: ?>
                    <div class="payment-info" style="background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.2);">
                        <i class="fas fa-info-circle" style="color: var(--warning);"></i>
                        <p>
                            Pagamento online não disponível.<br>
                            Entre em contato com o fornecedor para mais informações.
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Loading -->
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Gerando PIX...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function generatePix() {
            const loading = document.getElementById('loading');
            const btn = document.querySelector('.btn-pay');
            
            try {
                btn.disabled = true;
                loading.style.display = 'block';

                const response = await fetch('/api-invoice-generate-pix.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        invoice_id: '<?= $invoice['id'] ?>'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showPixModal(result);
                } else {
                    alert('Erro: ' + result.error);
                    btn.disabled = false;
                }
            } catch (error) {
                alert('Erro ao gerar PIX. Tente novamente.');
                btn.disabled = false;
            } finally {
                loading.style.display = 'none';
            }
        }

        function showPixModal(pixData) {
            // Criar modal (mesmo estilo do modal de renovação)
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
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), #4f46e5); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="fas fa-qrcode" style="font-size: 1.5rem; color: white;"></i>
                        </div>
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 0 0 0.5rem 0;">
                            Pagamento via PIX
                        </h2>
                        <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">
                            Escaneie o QR Code ou copie o código
                        </p>
                    </div>
                    
                    <!-- Fatura Info -->
                    <div style="background: var(--bg-secondary); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">Fatura:</span>
                            <span style="color: var(--text-primary); font-weight: 600;">#${pixData.invoice.id}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">Cliente:</span>
                            <span style="color: var(--text-primary); font-weight: 600;">${pixData.invoice.client_name}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">Valor:</span>
                            <span style="color: var(--primary); font-weight: 700; font-size: 1.25rem;">R$ ${parseFloat(pixData.invoice.value).toFixed(2).replace('.', ',')}</span>
                        </div>
                    </div>
                    
                    <!-- QR Code -->
                    <div style="background: white; padding: 1.5rem; border-radius: 12px; text-align: center; margin-bottom: 1.5rem; border: 2px solid var(--border);">
                        <img src="data:image/png;base64,${pixData.qr_code_base64}" 
                             alt="QR Code PIX" 
                             style="max-width: 100%; height: auto; border-radius: 8px;">
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
                            >${pixData.qr_code}</textarea>
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
                            style="flex: 1; padding: 0.75rem; background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border); border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.875rem;"
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
            
            // Iniciar verificação automática
            startPaymentCheck(pixData.payment_id);
        }

        function copyPixCode() {
            const textarea = document.getElementById('pixCode');
            textarea.select();
            document.execCommand('copy');
            alert('Código PIX copiado!');
        }

        function closePixModal() {
            const modal = document.getElementById('pixModal');
            if (modal) {
                modal.remove();
            }
            
            if (window.paymentCheckInterval) {
                clearInterval(window.paymentCheckInterval);
            }
            
            // Recarregar página
            location.reload();
        }

        async function checkPaymentStatus(paymentId) {
            try {
                const response = await fetch(`/api-check-payment-status.php?payment_id=${paymentId}`);
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
                        
                        if (window.paymentCheckInterval) {
                            clearInterval(window.paymentCheckInterval);
                        }
                        
                        setTimeout(() => {
                            closePixModal();
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
                // Erro silencioso na verificação de pagamento
            }
        }

        function startPaymentCheck(paymentId) {
            window.paymentCheckInterval = setInterval(() => {
                checkPaymentStatus(paymentId);
            }, 5000);
            
            setTimeout(() => {
                if (window.paymentCheckInterval) {
                    clearInterval(window.paymentCheckInterval);
                }
            }, 600000);
        }

        // Adicionar animações CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
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
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
