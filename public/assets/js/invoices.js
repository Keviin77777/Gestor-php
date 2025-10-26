/**
 * Invoices - JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Invoices page loaded');
    
    // Verificar autenticação
    if (!isAuthenticated()) {
        window.location.href = '/login';
        return;
    }
    
    // Carregar dados do usuário
    loadUserData();
    
    // Carregar faturas
    loadInvoices();
    
    // Configurar eventos
    setupEvents();
});

/**
 * Configurar eventos
 */
function setupEvents() {
    // Menu mobile
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Busca
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterInvoices(this.value);
        });
    }

    // Filtros de status
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remover active de todos
            filterBtns.forEach(b => b.classList.remove('active'));
            // Adicionar active no clicado
            this.classList.add('active');
            // Filtrar por status
            filterByStatus(this.dataset.status);
        });
    });
}

/**
 * Carregar dados do usuário
 */
function loadUserData() {
    try {
        const userStr = localStorage.getItem('user');
        if (!userStr) return;

        const user = JSON.parse(userStr);
        
        const userName = document.getElementById('userName');
        if (userName) {
            userName.textContent = user.name || 'Administrador';
        }

        const userEmail = document.getElementById('userEmail');
        if (userEmail) {
            userEmail.textContent = user.email || '';
        }

        const userAvatar = document.getElementById('userAvatar');
        if (userAvatar) {
            const initials = (user.name || 'A').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            userAvatar.textContent = initials;
        }
    } catch (error) {
        console.error('Erro ao carregar dados do usuário:', error);
    }
}/**

 * Carregar faturas
 */
async function loadInvoices() {
    try {
        if (typeof LoadingManager !== 'undefined' && LoadingManager.show) {
            LoadingManager.show('Carregando faturas...');
        }
        
        const response = await fetch('/api/invoices', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            displayInvoicesSummary(result.summary);
            displayInvoices(result.invoices || []);
        } else {
            throw new Error(result.error || 'Erro desconhecido');
        }

    } catch (error) {
        console.error('Erro ao carregar faturas:', error);
        showError('Erro ao carregar faturas: ' + error.message);
        displayEmptyState();
    } finally {
        if (typeof LoadingManager !== 'undefined' && LoadingManager.hide) {
            LoadingManager.hide();
        }
    }
}

/**
 * Exibir resumo das faturas
 */
function displayInvoicesSummary(summary) {
    if (!summary) return;

    // Faturas em aberto
    const pendingCount = document.getElementById('pendingCount');
    const pendingAmount = document.getElementById('pendingAmount');
    if (pendingCount) pendingCount.textContent = summary.pending.count;
    if (pendingAmount) pendingAmount.textContent = formatCurrency(summary.pending.amount);

    // Faturas pagas
    const paidCount = document.getElementById('paidCount');
    const paidAmount = document.getElementById('paidAmount');
    if (paidCount) paidCount.textContent = summary.paid.count;
    if (paidAmount) paidAmount.textContent = formatCurrency(summary.paid.amount);

    // Faturas vencidas
    const overdueCount = document.getElementById('overdueCount');
    const overdueAmount = document.getElementById('overdueAmount');
    if (overdueCount) overdueCount.textContent = summary.overdue.count;
    if (overdueAmount) overdueAmount.textContent = formatCurrency(summary.overdue.amount);

    // Total geral
    const totalCount = document.getElementById('totalCount');
    const totalAmount = document.getElementById('totalAmount');
    if (totalCount) totalCount.textContent = summary.total.count;
    if (totalAmount) totalAmount.textContent = formatCurrency(summary.total.amount);
}

/**
 * Exibir faturas
 */
function displayInvoices(invoices) {
    const container = document.getElementById('invoicesContainer');
    if (!container) return;

    if (invoices.length === 0) {
        displayEmptyState();
        return;
    }

    // Separar faturas por status
    const pendingInvoices = invoices.filter(inv => inv.status === 'pending');
    const paidInvoices = invoices.filter(inv => inv.status === 'paid');
    const overdueInvoices = invoices.filter(inv => inv.status === 'overdue');

    container.innerHTML = `
        <div class="invoices-sections">
            ${overdueInvoices.length > 0 ? createInvoiceSection('Faturas Vencidas', overdueInvoices, 'overdue') : ''}
            ${pendingInvoices.length > 0 ? createInvoiceSection('Faturas em Aberto', pendingInvoices, 'pending') : ''}
            ${paidInvoices.length > 0 ? createInvoiceSection('Faturas Pagas', paidInvoices, 'paid') : ''}
        </div>
    `;
}

/**
 * Criar seção de faturas
 */
function createInvoiceSection(title, invoices, type) {
    const sectionConfig = {
        'pending': {
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="9" stroke-width="2"/>
                <path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="1" fill="currentColor"/>
            </svg>`,
            title: '⏳ Faturas Pendentes',
            subtitle: 'Aguardando pagamento',
            gradient: 'linear-gradient(135deg, #f59e0b 0%, #fb923c 100%)'
        },
        'paid': {
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="9" stroke-width="2"/>
                <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>`,
            title: '✅ Faturas Pagas',
            subtitle: 'Pagamentos confirmados',
            gradient: 'linear-gradient(135deg, #10b981 0%, #34d399 100%)'
        },
        'overdue': {
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="9" stroke-width="2"/>
                <path d="M12 8v4" stroke-linecap="round"/>
                <path d="M12 16h.01" stroke-linecap="round"/>
            </svg>`,
            title: '🚨 Faturas Vencidas',
            subtitle: 'Requer atenção imediata',
            gradient: 'linear-gradient(135deg, #ef4444 0%, #f87171 100%)'
        }
    };

    const config = sectionConfig[type];
    const totalValue = invoices.reduce((sum, inv) => sum + parseFloat(inv.final_value), 0);

    return `
        <div class="invoices-section ${type}" data-section="${type}">
            <div class="section-header">
                <div class="section-header-content">
                    <div class="section-icon" style="background: ${config.gradient}">
                        ${config.icon}
                    </div>
                    <div class="section-info">
                        <h3 class="section-title">${config.title}</h3>
                        <p class="section-subtitle">${config.subtitle}</p>
                    </div>
                </div>
                <div class="section-stats">
                    <div class="section-count">
                        <span class="count-number">${invoices.length}</span>
                        <span class="count-label">${invoices.length === 1 ? 'fatura' : 'faturas'}</span>
                    </div>
                    <div class="section-total">
                        <span class="total-amount">${formatCurrency(totalValue)}</span>
                        <span class="total-label">valor total</span>
                    </div>
                </div>
            </div>
            <div class="section-body">
                <div class="invoices-list">
                    ${invoices.map(invoice => createInvoiceCard(invoice)).join('')}
                </div>
            </div>
        </div>
    `;
}

/**
 * Criar card de fatura
 */
function createInvoiceCard(invoice) {
    const statusIcons = {
        'pending': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
        'paid': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>',
        'overdue': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
    };

    const dueDate = new Date(invoice.due_date);
    const isOverdue = dueDate < new Date() && invoice.status === 'pending';
    const actualStatus = isOverdue ? 'overdue' : invoice.status;

    return `
        <div class="invoice-item" data-id="${invoice.id}" data-status="${actualStatus}">
            <div class="invoice-main">
                <div class="invoice-icon ${actualStatus}">
                    ${statusIcons[actualStatus] || statusIcons[invoice.status]}
                </div>
                
                <div class="invoice-info">
                    <h4>Fatura #${invoice.id}</h4>
                    <p>${escapeHtml(invoice.client_name)}</p>
                </div>
            </div>
            
            <div class="invoice-details">
                <div class="invoice-detail value">
                    <label>Valor</label>
                    <span>${formatCurrency(invoice.final_value)}</span>
                </div>
                <div class="invoice-detail ${isOverdue ? 'overdue' : ''}">
                    <label>Vencimento</label>
                    <span>${formatDate(invoice.due_date)}</span>
                </div>
                ${invoice.payment_date ? `
                <div class="invoice-detail">
                    <label>Pagamento</label>
                    <span>${formatDate(invoice.payment_date)}</span>
                </div>
                ` : ''}
            </div>
            
            <div class="invoice-actions">
                <button class="btn-action" onclick="viewInvoice('${invoice.id}')" title="Visualizar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
                ${invoice.status === 'pending' || actualStatus === 'overdue' ? `
                <button class="btn-action success" onclick="markAsPaid('${invoice.id}')" title="Marcar como Paga">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </button>
                ` : ''}
                <button class="btn-action primary" onclick="editInvoice('${invoice.id}')" title="Editar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                <button class="btn-action danger" onclick="deleteInvoice('${invoice.id}')" title="Excluir">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3,6 5,6 21,6"></polyline>
                        <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
}

/**
 * Exibir estado vazio
 */
function displayEmptyState() {
    const container = document.getElementById('invoicesContainer');
    if (!container) return;

    container.innerHTML = `
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            <h3>Nenhuma fatura encontrada</h3>
            <p>Crie sua primeira fatura para começar a gerenciar os pagamentos.</p>
            <button class="btn btn-primary" onclick="openInvoiceModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nova Fatura
            </button>
        </div>
    `;
}

/**
 * Filtrar faturas por texto
 */
function filterInvoices(searchTerm) {
    const invoiceItems = document.querySelectorAll('.invoice-item');
    
    invoiceItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        item.style.display = matches ? '' : 'none';
    });
}

/**
 * Filtrar por status
 */
function filterByStatus(status) {
    const sections = document.querySelectorAll('.invoices-section');
    
    if (status === 'all') {
        sections.forEach(section => {
            section.style.display = '';
        });
    } else {
        sections.forEach(section => {
            const sectionStatus = section.dataset.section;
            section.style.display = sectionStatus === status ? '' : 'none';
        });
    }
}

/**
 * Abrir modal de nova fatura
 */
function openInvoiceModal() {
    // TODO: Implementar modal de nova fatura
    showInfo('Funcionalidade em desenvolvimento');
}

/**
 * Visualizar fatura
 */
function viewInvoice(invoiceId) {
    // TODO: Implementar visualização de fatura
    showInfo(`Visualizar fatura #${invoiceId}`);
}

/**
 * Editar fatura
 */
function editInvoice(invoiceId) {
    // TODO: Implementar edição de fatura
    showInfo(`Editar fatura #${invoiceId}`);
}

/**
 * Marcar como paga
 */
async function markAsPaid(invoiceId) {
    if (!confirm('Tem certeza que deseja marcar esta fatura como paga?')) {
        return;
    }

    try {
        if (typeof LoadingManager !== 'undefined' && LoadingManager.show) {
            LoadingManager.show('Atualizando fatura...');
        }

        const response = await fetch(`/api/invoices/${invoiceId}/mark-paid`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            showSuccess('Fatura marcada como paga!');
            loadInvoices(); // Recarregar lista
        } else {
            throw new Error(result.error || 'Erro ao atualizar fatura');
        }

    } catch (error) {
        console.error('Erro ao marcar fatura como paga:', error);
        showError('Erro ao atualizar fatura: ' + error.message);
    } finally {
        if (typeof LoadingManager !== 'undefined' && LoadingManager.hide) {
            LoadingManager.hide();
        }
    }
}

/**
 * Excluir fatura
 */
async function deleteInvoice(invoiceId) {
    if (!confirm('Tem certeza que deseja excluir esta fatura? Esta ação não pode ser desfeita.')) {
        return;
    }

    try {
        if (typeof LoadingManager !== 'undefined' && LoadingManager.show) {
            LoadingManager.show('Excluindo fatura...');
        }

        const response = await fetch(`/api/invoices/${invoiceId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            showSuccess('Fatura excluída com sucesso!');
            loadInvoices(); // Recarregar lista
        } else {
            throw new Error(result.error || 'Erro ao excluir fatura');
        }

    } catch (error) {
        console.error('Erro ao excluir fatura:', error);
        showError('Erro ao excluir fatura: ' + error.message);
    } finally {
        if (typeof LoadingManager !== 'undefined' && LoadingManager.hide) {
            LoadingManager.hide();
        }
    }
}

/**
 * Formatar moeda
 */
function formatCurrency(value) {
    if (!value) return 'R$ 0,00';
    
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

/**
 * Formatar data
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        // Se a data já está no formato brasileiro (dd/mm/yyyy), retornar como está
        if (dateString.includes('/')) {
            return dateString;
        }
        
        // Se está no formato ISO (yyyy-mm-dd), converter para brasileiro
        if (dateString.includes('-')) {
            const [year, month, day] = dateString.split(' ')[0].split('-');
            return `${day}/${month}/${year}`;
        }
        
        // Fallback para new Date
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return dateString;
        }
        
        return date.toLocaleDateString('pt-BR');
    } catch (error) {
        console.warn('Erro ao formatar data:', dateString, error);
        return dateString || 'N/A';
    }
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Mostrar erro
 */
function showError(message) {
    alert('Erro: ' + message);
}

/**
 * Mostrar sucesso
 */
function showSuccess(message) {
    alert('Sucesso: ' + message);
}

/**
 * Mostrar informação
 */
function showInfo(message) {
    alert('Info: ' + message);
}