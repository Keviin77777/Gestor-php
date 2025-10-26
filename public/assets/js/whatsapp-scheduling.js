/**
 * WhatsApp Scheduling - JavaScript
 */

let templates = [];
let currentSchedulingId = null;

// Mapeamento de tipos para exibição
const typeLabels = {
    'welcome': 'Boas Vindas',
    'invoice_generated': 'Fatura Gerada',
    'renewed': 'Renovação Confirmada',
    'expires_3d': 'Vence em 3 dias',
    'expires_7d': 'Vence em 7 dias',
    'expires_today': 'Vence Hoje',
    'expired_1d': 'Vencido há 1 dia',
    'expired_3d': 'Vencido há 3 dias',
    'custom': 'Personalizado'
};

// Mapeamento de dias da semana
const dayLabels = {
    'monday': 'Segunda',
    'tuesday': 'Terça',
    'wednesday': 'Quarta',
    'thursday': 'Quinta',
    'friday': 'Sexta',
    'saturday': 'Sábado',
    'sunday': 'Domingo'
};

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    loadUserProfile();
    loadTemplates();
    setupEventListeners();
});

/**
 * Carregar perfil do usuário
 */
function loadUserProfile() {
    try {
        const userStr = localStorage.getItem('user');
        if (!userStr) return;

        const user = JSON.parse(userStr);

        // Nome do usuário
        const userName = document.getElementById('userName');
        if (userName) {
            userName.textContent = user.name || 'Usuário';
        }

        // Email do usuário
        const userEmail = document.getElementById('userEmail');
        if (userEmail) {
            userEmail.textContent = user.email || '';
        }

        // Avatar do usuário
        const userAvatar = document.getElementById('userAvatar');
        if (userAvatar) {
            const initials = (user.name || 'U').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            userAvatar.textContent = initials;
        }

    } catch (error) {
        // Erro ao carregar dados do usuário
    }
}

function setupEventListeners() {
    // Event listeners para os checkboxes de dias
    document.querySelectorAll('.day-input').forEach(input => {
        input.addEventListener('change', function() {
            updateDaysDisplay();
        });
    });
}

async function loadTemplates() {
    try {
        showLoading('Carregando templates...');
        
        const response = await fetch('/api-whatsapp-templates.php');
        const data = await response.json();
        
        if (data.success) {
            templates = data.templates || [];
            renderSchedulingList();
        } else {
            showNotification('Erro ao carregar templates: ' + data.error, 'error');
        }
    } catch (error) {
        showNotification('Erro ao carregar templates: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function renderSchedulingList() {
    const container = document.getElementById('schedulingList');
    if (!container) return;
    
    if (templates.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <h3>Nenhum template encontrado</h3>
                <p>Você precisa criar templates primeiro para configurar agendamentos.</p>
                <a href="/whatsapp/templates" class="btn btn-primary">Criar Template</a>
            </div>
        `;
        return;
    }
    
    container.innerHTML = templates.map(template => createSchedulingListItem(template)).join('');
}

function createSchedulingListItem(template) {
    const isScheduled = template.is_scheduled || false;
    const scheduledDays = template.scheduled_days ? JSON.parse(template.scheduled_days) : [];
    const scheduledTime = template.scheduled_time || '09:00';
    
    // Cores e ícones por tipo (mesmo estilo da aba TEMPLATES)
    const typeColors = {
        'welcome': '#10b981',
        'invoice_generated': '#10b981',
        'renewed': '#10b981',
        'expires_3d': '#f59e0b',
        'expires_7d': '#f59e0b',
        'expires_today': '#f97316',
        'expired_1d': '#ef4444',
        'expired_3d': '#ef4444',
        'custom': '#6366f1'
    };

    const typeIcons = {
        'welcome': `<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>`,
        'invoice_generated': `<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>`,
        'renewed': `<polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>`,
        'expires_3d': `<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>`,
        'expires_7d': `<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>`,
        'expires_today': `<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>`,
        'expired_1d': `<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>`,
        'expired_3d': `<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>`,
        'custom': `<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline>`
    };
    
    const color = typeColors[template.type] || '#6366f1';
    const icon = typeIcons[template.type] || typeIcons['custom'];
    
    // Formatação dos dias
    const daysDisplay = scheduledDays.length > 0 
        ? scheduledDays.map(day => {
            const fullLabels = {
                'monday': 'Segunda',
                'tuesday': 'Terça',
                'wednesday': 'Quarta',
                'thursday': 'Quinta',
                'friday': 'Sexta',
                'saturday': 'Sábado',
                'sunday': 'Domingo'
            };
            return `<span class="day-tag">${fullLabels[day] || day}</span>`;
        }).join('')
        : '<span class="not-configured">Não configurado</span>';
    
    return `
        <div class="template-list-item ${!isScheduled ? 'inactive' : ''}" data-template-id="${template.id}">
            <div class="template-col col-name">
                <div class="template-icon" style="background: ${color}20; color: ${color};">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        ${icon}
                    </svg>
                </div>
                <div class="template-content">
                    <div class="template-title">${template.name}</div>
                    <div class="template-description">${template.title}</div>
                </div>
            </div>
            
            <div class="template-col col-type">
                <div class="template-badge" style="background: ${color}20; color: ${color};">
                    ${typeLabels[template.type] || template.type}
                </div>
            </div>
            
            <div class="template-col col-days">
                <div class="days-display">
                    ${daysDisplay}
                </div>
            </div>
            
            <div class="template-col col-time">
                <div class="time-display">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; margin-right: 0.25rem;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    ${scheduledTime}
                </div>
            </div>
            
            <div class="template-col col-status">
                ${isScheduled ? 
                    `<svg class="check-icon success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>` : 
                    `<svg class="check-icon inactive" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>`
                }
            </div>
            
            <div class="template-col col-actions">
                <button class="template-icon-btn" onclick="editScheduling('${template.id}')" title="Configurar Agendamento">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <path d="M8 14h.01M12 14h.01M16 14h.01"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
}

function editScheduling(templateId) {
    const template = templates.find(t => t.id === templateId);
    if (!template) return;
    
    currentSchedulingId = templateId;
    
    // Cores por tipo
    const typeColors = {
        'welcome': '#10b981',
        'invoice_generated': '#10b981',
        'renewed': '#10b981',
        'expires_3d': '#f59e0b',
        'expires_7d': '#f59e0b',
        'expires_today': '#f97316',
        'expired_1d': '#ef4444',
        'expired_3d': '#ef4444',
        'custom': '#6366f1'
    };
    
    const color = typeColors[template.type] || '#6366f1';
    
    // Preencher informações do template com estilo melhorado
    document.getElementById('templateInfo').innerHTML = `
        <div class="template-info-display">
            <div class="template-info-header">
                <div class="template-info-icon" style="background: ${color}20; color: ${color};">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <div class="template-info-content">
                    <div class="template-info-name">${template.name}</div>
                    <div class="template-info-title">${template.title}</div>
                </div>
            </div>
            <div class="template-info-badge" style="background: ${color}20; color: ${color};">
                ${typeLabels[template.type] || template.type}
            </div>
        </div>
    `;
    
    // Preencher dados do agendamento
    const isScheduled = template.is_scheduled || false;
    const scheduledDays = template.scheduled_days ? JSON.parse(template.scheduled_days) : [];
    const scheduledTime = template.scheduled_time || '09:00';
    
    document.getElementById('schedulingEnabled').checked = isScheduled;
    document.getElementById('schedulingTime').value = scheduledTime;
    
    // Limpar e marcar dias selecionados
    document.querySelectorAll('.day-input').forEach(input => {
        input.checked = false;
    });
    
    scheduledDays.forEach(day => {
        const input = document.querySelector(`.day-input[value="${day}"]`);
        if (input) {
            input.checked = true;
        }
    });
    
    toggleSchedulingOptions();
    openSchedulingModal();
}

function toggleSchedulingOptions() {
    const enabled = document.getElementById('schedulingEnabled').checked;
    const options = document.getElementById('schedulingOptions');
    
    if (enabled) {
        options.style.display = 'block';
    } else {
        options.style.display = 'none';
    }
}

function updateDaysDisplay() {
    // Esta função pode ser usada para atualizar a exibição em tempo real
    // Por enquanto, não é necessária
}

function openSchedulingModal() {
    const modal = document.getElementById('schedulingModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
    }
}

function closeSchedulingModal() {
    const modal = document.getElementById('schedulingModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
    }
    currentSchedulingId = null;
}

async function saveScheduling() {
    if (!currentSchedulingId) return;
    
    try {
        showLoading('Salvando agendamento...');
        
        const enabled = document.getElementById('schedulingEnabled').checked;
        const time = document.getElementById('schedulingTime').value;
        const selectedDays = Array.from(document.querySelectorAll('.day-input:checked')).map(input => input.value);
        
        const schedulingData = {
            is_scheduled: enabled,
            scheduled_days: enabled ? selectedDays : [],
            scheduled_time: enabled ? time : null
        };
        
        const response = await fetch('/api-whatsapp-templates.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: currentSchedulingId,
                ...schedulingData
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Agendamento salvo com sucesso!', 'success');
            closeSchedulingModal();
            loadTemplates(); // Recarregar lista
        } else {
            showNotification('Erro ao salvar agendamento: ' + data.error, 'error');
        }
    } catch (error) {
        showNotification('Erro ao salvar agendamento: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function refreshScheduling() {
    loadTemplates();
}

// Funções de notificação (reutilizando do common.js)
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Auto remove após 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function showLoading(text = 'Carregando...') {
    const overlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    
    if (overlay) {
        overlay.style.display = 'flex';
    }
    
    if (loadingText) {
        loadingText.textContent = text;
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}
