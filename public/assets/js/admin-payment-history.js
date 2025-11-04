/**
 * Admin - Histórico de Pagamentos
 */

let allPayments = [];

// Carregar ao inicializar
document.addEventListener('DOMContentLoaded', () => {
    loadPayments();
});

// Carregar pagamentos
async function loadPayments() {
    try {
        const status = document.getElementById('filterStatus').value;
        const period = document.getElementById('filterPeriod').value;
        const search = document.getElementById('filterSearch').value;
        
        let url = '/api-payment-history.php?';
        if (status) url += `status=${status}&`;
        if (period) url += `period=${period}&`;
        if (search) url += `search=${encodeURIComponent(search)}&`;
        
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            allPayments = result.payments;
            renderPayments(result.payments);
            updateStats(result.stats);
        } else {
            showError(result.error || 'Erro ao carregar pagamentos');
        }
    } catch (error) {
        showError('Erro ao carregar pagamentos');
    }
}

// Renderizar tabela
function renderPayments(payments) {
    const tbody = document.getElementById('paymentsTable');
    
    if (!payments || payments.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    <div class="empty-state-content">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M16 16s-1.5-2-4-2-4 2-4 2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                        <p>Nenhum pagamento encontrado</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = payments.map(payment => `
        <tr>
            <td>${formatDate(payment.created_at)}</td>
            <td>
                <div style="font-weight: 600;">${payment.user_name || 'N/A'}</div>
                <div style="font-size: 0.75rem; color: var(--text-secondary);">${payment.user_email}</div>
            </td>
            <td>${payment.plan_name || 'N/A'}</td>
            <td style="font-weight: 600;">R$ ${parseFloat(payment.amount).toFixed(2).replace('.', ',')}</td>
            <td>
                <code style="font-size: 0.75rem; background: var(--bg-secondary); padding: 0.25rem 0.5rem; border-radius: 4px;">
                    ${payment.payment_id}
                </code>
            </td>
            <td>
                <span class="status-badge ${payment.status}">
                    ${getStatusLabel(payment.status)}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon btn-delete" onclick="deletePayment('${payment.id}')" title="Excluir">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Atualizar estatísticas
function updateStats(stats) {
    document.getElementById('statPending').textContent = stats.pending || 0;
    document.getElementById('statApproved').textContent = stats.approved || 0;
    document.getElementById('statRejected').textContent = stats.rejected || 0;
    document.getElementById('statTotal').textContent = `R$ ${parseFloat(stats.total || 0).toFixed(2).replace('.', ',')}`;
}

// Excluir pagamento
async function deletePayment(id) {
    if (!confirm('Tem certeza que deseja excluir este pagamento do histórico?')) {
        return;
    }
    
    try {
        const response = await fetch(`/api-payment-history.php?id=${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Pagamento excluído com sucesso');
            loadPayments();
        } else {
            showError(result.error || 'Erro ao excluir pagamento');
        }
    } catch (error) {
        showError('Erro ao excluir pagamento');
    }
}

// Helpers
function getStatusLabel(status) {
    const labels = {
        'pending': 'Pendente',
        'approved': 'Aprovado',
        'rejected': 'Rejeitado',
        'cancelled': 'Cancelado'
    };
    return labels[status] || status;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showSuccess(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--success);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: var(--shadow-lg);
        z-index: 9999;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
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
        border-radius: 8px;
        box-shadow: var(--shadow-lg);
        z-index: 9999;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 4000);
}
