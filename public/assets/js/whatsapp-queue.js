/**
 * WhatsApp Queue Management
 */

let refreshInterval = null;
let currentPage = 1;
let currentStatus = '';

document.addEventListener('DOMContentLoaded', () => {
    // Remover loading inicial
    const loadingOverlay = document.querySelector('.loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
    
    loadRateLimitConfig();
    loadQueue();
    loadStats();
    
    // Auto-refresh a cada 10 segundos
    refreshInterval = setInterval(() => {
        loadQueue(currentPage);
        loadStats();
    }, 10000);
});

/**
 * Carregar configuração de rate limit
 */
async function loadRateLimitConfig() {
    try {
        const response = await fetch('/api-whatsapp-queue.php?action=get_config', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.config) {
            document.getElementById('ratePerMinute').textContent = result.config.messages_per_minute;
            document.getElementById('ratePerHour').textContent = result.config.messages_per_hour;
            document.getElementById('rateDelay').textContent = result.config.delay_between_messages + 's';
            
            // Preencher form
            document.getElementById('messagesPerMinute').value = result.config.messages_per_minute;
            document.getElementById('messagesPerHour').value = result.config.messages_per_hour;
            document.getElementById('delayBetween').value = result.config.delay_between_messages;
        }
    } catch (error) {
        console.error('Erro ao carregar configuração:', error);
    }
}

/**
 * Carregar estatísticas
 */
async function loadStats() {
    try {
        const response = await fetch('/api-whatsapp-queue.php?action=get_stats', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.stats) {
            document.getElementById('pendingCount').textContent = result.stats.pending || 0;
            document.getElementById('processingCount').textContent = result.stats.processing || 0;
            document.getElementById('sentCount').textContent = result.stats.sent || 0;
            document.getElementById('failedCount').textContent = result.stats.failed || 0;
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
}

/**
 * Carregar fila de mensagens
 */
async function loadQueue(page = 1) {
    const tbody = document.getElementById('queueTableBody');
    const statusFilter = document.getElementById('statusFilter').value;
    
    currentPage = page;
    currentStatus = statusFilter;
    
    try {
        const url = `/api-whatsapp-queue.php?action=get_queue&page=${page}&per_page=20${statusFilter ? '&status=' + statusFilter : ''}`;
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.queue) {
            if (result.queue.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                            📭 Nenhuma mensagem na fila
                        </td>
                    </tr>
                `;
                renderPagination(null);
                return;
            }
            
            tbody.innerHTML = result.queue.map(msg => `
                <tr>
                    <td data-label="ID">#${msg.id}</td>
                    <td data-label="Telefone">${formatPhone(msg.phone)}</td>
                    <td data-label="Mensagem">
                        <div class="message-preview" title="${escapeHtml(msg.message)}">
                            ${escapeHtml(msg.message.substring(0, 50))}${msg.message.length > 50 ? '...' : ''}
                        </div>
                    </td>
                    <td data-label="Status">
                        <span class="status-badge ${msg.status}">
                            ${getStatusIcon(msg.status)} ${getStatusText(msg.status)}
                        </span>
                    </td>
                    <td data-label="Data">${formatDate(msg.scheduled_at || msg.created_at)}</td>
                    <td data-label="Tentativas">${msg.attempts}/${msg.max_attempts}</td>
                    <td data-label="Ações">
                        <div class="action-buttons">
                            ${msg.status === 'pending' || msg.status === 'failed' ? `
                                <button class="btn-icon" onclick="retryMessage(${msg.id})" title="Reenviar">
                                    🔄
                                </button>
                            ` : ''}
                            <button class="btn-icon" onclick="deleteMessage(${msg.id})" title="Excluir">
                                🗑️
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            renderPagination(result.pagination);
        }
    } catch (error) {
        console.error('Erro ao carregar fila:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem; color: #ef4444;">
                    ❌ Erro ao carregar fila
                </td>
            </tr>
        `;
        renderPagination(null);
    }
}

/**
 * Renderizar paginação
 */
function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;
    
    if (!pagination || pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="pagination">';
    
    // Botão anterior
    if (pagination.has_prev) {
        html += `<button class="pagination-btn" onclick="loadQueue(${pagination.current_page - 1})">← Anterior</button>`;
    } else {
        html += `<button class="pagination-btn" disabled>← Anterior</button>`;
    }
    
    // Informação da página
    html += `<span class="pagination-info">Página ${pagination.current_page} de ${pagination.total_pages} (${pagination.total} mensagens)</span>`;
    
    // Botão próximo
    if (pagination.has_next) {
        html += `<button class="pagination-btn" onclick="loadQueue(${pagination.current_page + 1})">Próximo →</button>`;
    } else {
        html += `<button class="pagination-btn" disabled>Próximo →</button>`;
    }
    
    html += '</div>';
    container.innerHTML = html;
}



/**
 * Reenviar mensagem
 */
async function retryMessage(id) {
    if (!confirm('Deseja reenviar esta mensagem agora?')) return;
    
    try {
        const response = await fetch('/api-whatsapp-queue.php?action=retry', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Mensagem reenviada!');
            loadQueue();
            loadStats();
        } else {
            alert('❌ Erro: ' + result.error);
        }
    } catch (error) {
        alert('❌ Erro ao reenviar mensagem');
        console.error(error);
    }
}

/**
 * Excluir mensagem
 */
async function deleteMessage(id) {
    if (!confirm('Deseja excluir esta mensagem da fila?')) return;
    
    try {
        const response = await fetch('/api-whatsapp-queue.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Mensagem excluída!');
            loadQueue();
            loadStats();
        } else {
            alert('❌ Erro: ' + result.error);
        }
    } catch (error) {
        alert('❌ Erro ao excluir mensagem');
        console.error(error);
    }
}

/**
 * Helpers
 */
function formatPhone(phone) {
    return phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusIcon(status) {
    const icons = {
        'pending': '⏳',
        'processing': '🔄',
        'sent': '✅',
        'failed': '❌'
    };
    return icons[status] || '❓';
}

function getStatusText(status) {
    const texts = {
        'pending': 'Pendente',
        'processing': 'Processando',
        'sent': 'Enviada',
        'failed': 'Falha'
    };
    return texts[status] || status;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Forçar processamento da fila
 */
async function forceProcessQueue() {
    if (!confirm('⚡ Deseja forçar o processamento imediato da fila?\n\nIsso irá processar as mensagens pendentes agora, respeitando os limites configurados.')) {
        return;
    }
    
    try {
        const response = await fetch('/api-whatsapp-queue.php?action=force_process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ ' + result.message);
            loadQueue(currentPage);
            loadStats();
        } else {
            alert('❌ Erro: ' + result.error);
        }
    } catch (error) {
        alert('❌ Erro ao forçar processamento');
        console.error(error);
    }
}

/**
 * Excluir mensagens enviadas
 */
async function deleteSentMessages() {
    if (!confirm('🗑️ Deseja excluir TODAS as mensagens já enviadas?\n\nEsta ação não pode ser desfeita!')) {
        return;
    }
    
    try {
        const response = await fetch('/api-whatsapp-queue.php?action=delete_sent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ ${result.deleted} mensagens enviadas foram excluídas!`);
            loadQueue(currentPage);
            loadStats();
        } else {
            alert('❌ Erro: ' + result.error);
        }
    } catch (error) {
        alert('❌ Erro ao excluir mensagens');
        console.error(error);
    }
}

/**
 * Excluir todas as mensagens
 */
async function deleteAllMessages() {
    if (!confirm('⚠️ ATENÇÃO! Deseja excluir TODAS as mensagens do histórico?\n\nIsso inclui pendentes, enviadas e falhadas.\n\nEsta ação não pode ser desfeita!')) {
        return;
    }
    
    // Confirmação dupla
    const confirmText = prompt('Digite "EXCLUIR TUDO" para confirmar:');
    if (confirmText !== 'EXCLUIR TUDO') {
        alert('❌ Operação cancelada');
        return;
    }
    
    try {
        const response = await fetch('/api-whatsapp-queue.php?action=delete_all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ ${result.deleted} mensagens foram excluídas!`);
            loadQueue(currentPage);
            loadStats();
        } else {
            alert('❌ Erro: ' + result.error);
        }
    } catch (error) {
        alert('❌ Erro ao excluir mensagens');
        console.error(error);
    }
}

// Limpar interval ao sair da página
window.addEventListener('beforeunload', () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
