/**
 * WhatsApp Templates - JavaScript
 */

let templates = [];
let currentTemplateId = null;

// Templates padrão para cada tipo
const defaultTemplates = {
    'welcome': {
        title: 'Boas-vindas',
        message: `🎉 Olá {{cliente_nome}}!

Bem-vindo(a) à {{empresa_nome}}! 

Seu cadastro foi realizado com sucesso e você já pode aproveitar todos os nossos serviços.

📋 **Seus dados:**
• Nome: {{cliente_nome}}
• Email: {{cliente_email}}
• Plano: {{cliente_plano}}

Se precisar de ajuda, estamos aqui! 😊

Equipe {{empresa_nome}}`
    },
    'invoice_generated': {
        title: 'Fatura Gerada',
        message: `📄 Olá {{cliente_nome}}!

Sua fatura foi gerada com sucesso! 

📋 **Detalhes:**
• Valor: R$ {{cliente_valor}}
• Vencimento: {{cliente_vencimento}}
• Plano: {{cliente_plano}}

🔗 **Link para pagamento:** {{link_pagamento}}

Obrigado pela confiança! 😊

Equipe {{empresa_nome}}`
    },
    'renewed': {
        title: 'Renovação Confirmada',
        message: `✅ Olá {{cliente_nome}}!

Sua renovação foi confirmada com sucesso! 

📋 **Detalhes:**
• Plano: {{cliente_plano}}
• Próximo vencimento: {{cliente_vencimento}}
• Valor: R$ {{cliente_valor}}

Seu serviço está ativo e funcionando perfeitamente.

Obrigado pela confiança! 🙏

Equipe {{empresa_nome}}`
    },
    'expires_3d': {
        title: 'Vence em 3 dias',
        message: `⏰ Olá {{cliente_nome}}!

**Lembrete importante:** Seu serviço vence em *3 dias* ({{cliente_vencimento}}).

💰 **Valor:** R$ {{cliente_valor}}
📺 **Plano:** {{cliente_plano}}

Para evitar a interrupção do serviço, efetue o pagamento o quanto antes.

🔗 **Link para pagamento:** {{link_pagamento}}

Entre em contato conosco para mais informações! 😊`
    },
    'expires_7d': {
        title: 'Vence em 7 dias',
        message: `⏰ Olá {{cliente_nome}}!

**Lembrete importante:** Seu serviço vence em *7 dias* ({{cliente_vencimento}}).

💰 **Valor:** R$ {{cliente_valor}}
📺 **Plano:** {{cliente_plano}}

Para evitar a interrupção do serviço, efetue o pagamento o quanto antes.

🔗 **Link para pagamento:** {{link_pagamento}}

Entre em contato conosco para mais informações! 😊`
    },
    'expires_today': {
        title: 'Vence Hoje',
        message: `🚨 Olá {{cliente_nome}}!

**URGENTE:** Seu serviço vence *hoje* ({{cliente_vencimento}})!

💰 **Valor:** R$ {{cliente_valor}}
📺 **Plano:** {{cliente_plano}}

Para evitar a interrupção do serviço, efetue o pagamento IMEDIATAMENTE!

🔗 **Link para pagamento:** {{link_pagamento}}

Entre em contato conosco para mais informações! 😊`
    },
    'expired_1d': {
        title: 'Vencido há 1 dia',
        message: `⚠️ Olá {{cliente_nome}}!

Seu serviço venceu há *1 dia* ({{cliente_vencimento}}).

💰 **Valor:** R$ {{cliente_valor}}
📺 **Plano:** {{cliente_plano}}

Para reativar seu serviço, efetue o pagamento o quanto antes.

🔗 **Link para pagamento:** {{link_pagamento}}

Entre em contato conosco para mais informações! 😊`
    },
    'expired_3d': {
        title: 'Vencido há 3 dias',
        message: `⚠️ Olá {{cliente_nome}}!

Seu serviço venceu há *3 dias* ({{cliente_vencimento}}).

💰 **Valor:** R$ {{cliente_valor}}
📺 **Plano:** {{cliente_plano}}

Para reativar seu serviço, efetue o pagamento o quanto antes.

🔗 **Link para pagamento:** {{link_pagamento}}

Entre em contato conosco para mais informações! 😊`
    },
    'custom': {
        title: 'Template Personalizado',
        message: `Olá {{cliente_nome}}!

Digite sua mensagem personalizada aqui...

Equipe {{empresa_nome}}`
    }
};

// Inicializar página
document.addEventListener('DOMContentLoaded', function () {
    loadUserProfile();
    loadTemplates();
    setupTemplateTypeListener();
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

/**
 * Configurar listener para preenchimento automático de templates
 */
function setupTemplateTypeListener() {
    const typeSelect = document.getElementById('templateType');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            const messageTextarea = document.getElementById('templateMessage');
            const titleInput = document.getElementById('templateTitle');
            
            
            // Se não é edição e há um template padrão para este tipo
            if (!currentTemplateId && selectedType && defaultTemplates[selectedType]) {
                const defaultTemplate = defaultTemplates[selectedType];
                
                // Sempre substituir título e mensagem quando selecionar um novo tipo
                titleInput.value = defaultTemplate.title;
                messageTextarea.value = defaultTemplate.message;
                
            }
        });
    }
}

/**
 * Carregar templates
 */
async function loadTemplates() {
    try {
        const response = await fetch('/api-whatsapp-templates.php');
        const data = await response.json();

        if (data.success) {
            templates = data.templates;
            renderTemplates();
        } else {
            showNotification('Erro ao carregar templates: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Erro ao carregar templates:', error);
        showNotification('Erro ao carregar templates', 'error');
    }
}

/**
 * Renderizar templates
 */
function renderTemplates() {
    const list = document.getElementById('templatesList');
    
    if (templates.length === 0) {
        list.innerHTML = `
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <h3>Nenhum template encontrado</h3>
                <p>Crie seu primeiro template de mensagem</p>
                <button class="btn btn-primary" onclick="openTemplateModal()">Criar Template</button>
            </div>
        `;
        return;
    }

    list.innerHTML = templates.map(template => createTemplateListItem(template)).join('');
}

/**
 * Criar item de lista de template
 */
function createTemplateListItem(template) {
    const typeLabels = {
        'welcome': 'Boas-vindas',
        'invoice_generated': 'Renovacao',
        'renewed': 'Renovacao',
        'expires_3d': 'Lembrete (Antes)',
        'expires_7d': 'Lembrete (Antes)',
        'expires_today': 'Vencimento',
        'expired_1d': 'Lembrete (Apos)',
        'expired_3d': 'Lembrete (Apos)',
        'custom': 'Personalizado'
    };

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

    const isActive = parseInt(template.is_active) === 1;
    const isDefault = parseInt(template.is_default) === 1;
    const color = typeColors[template.type] || '#6366f1';
    const icon = typeIcons[template.type] || typeIcons['custom'];

    return `
        <div class="template-list-item ${!isActive ? 'inactive' : ''}">
            <div class="template-col col-name">
                <div class="template-icon" style="background: ${color}20; color: ${color};">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        ${icon}
                    </svg>
                </div>
                <div class="template-content">
                    <div class="template-title">${template.name}</div>
                    <div class="template-description">${template.message.substring(0, 60)}${template.message.length > 60 ? '...' : ''}</div>
                </div>
            </div>
            
            <div class="template-col col-type">
                <div class="template-badge" style="background: ${color}20; color: ${color};">
                    ${typeLabels[template.type] || template.type}
                </div>
            </div>
            
            <div class="template-col col-media">
                NAO
            </div>
            
            <div class="template-col col-default">
                ${isDefault ? 
                    `<svg class="check-icon success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>` : 
                    ''
                }
            </div>
            
            <div class="template-col col-status">
                ${isActive ? 
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
                <button class="template-icon-btn" onclick="toggleTemplate('${template.id}')" title="${isActive ? 'Desativar' : 'Ativar'}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </button>
                
                <button class="template-icon-btn" onclick="viewTemplate('${template.id}')" title="Visualizar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
                
                <button class="template-icon-btn" onclick="editTemplate('${template.id}')" title="Editar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                
                <button class="template-icon-btn danger" onclick="deleteTemplate('${template.id}')" title="Excluir">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
}

/**
 * Abrir modal de template
 */
function openTemplateModal(templateId = null) {
    const modal = document.getElementById('templateModal');
    const form = document.getElementById('templateForm');
    const title = document.getElementById('modalTitle');
    
    form.reset();
    currentTemplateId = templateId;
    
    if (templateId) {
        title.textContent = 'Editar Template';
        const template = templates.find(t => t.id === templateId);
        if (template) {
            document.getElementById('templateId').value = template.id;
            document.getElementById('templateName').value = template.name;
            document.getElementById('templateType').value = template.type;
            document.getElementById('templateTitle').value = template.title;
            document.getElementById('templateMessage').value = template.message;
            document.getElementById('templateActive').checked = parseInt(template.is_active) === 1;
            document.getElementById('templateDefault').checked = parseInt(template.is_default) === 1;
        }
    } else {
        title.textContent = 'Novo Template';
        document.getElementById('templateActive').checked = true;
    }
    
    modal.style.display = 'flex';
}

/**
 * Fechar modal
 */
function closeTemplateModal() {
    const form = document.getElementById('templateForm');
    Array.from(form.elements).forEach(el => el.disabled = false);
    document.getElementById('templateModal').style.display = 'none';
    currentTemplateId = null;
}

/**
 * Inserir variável no textarea
 */
function insertVariable(variable) {
    const textarea = document.getElementById('templateMessage');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const before = text.substring(0, start);
    const after = text.substring(end, text.length);
    
    textarea.value = before + '{{' + variable + '}}' + after;
    textarea.selectionStart = textarea.selectionEnd = start + variable.length + 4;
    textarea.focus();
}

/**
 * Salvar template
 */
async function saveTemplate() {
    const form = document.getElementById('templateForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const templateData = {
        id: document.getElementById('templateId').value || null,
        name: document.getElementById('templateName').value,
        type: document.getElementById('templateType').value,
        title: document.getElementById('templateTitle').value,
        message: document.getElementById('templateMessage').value,
        is_active: document.getElementById('templateActive').checked ? 1 : 0,
        is_default: document.getElementById('templateDefault').checked ? 1 : 0
    };
    
    try {
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch('/api-whatsapp-templates.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(templateData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Template salvo com sucesso!', 'success');
                closeTemplateModal();
                loadTemplates();
            } else {
                throw new Error(data.error || 'Erro ao salvar template');
            }
        }, {
            type: 'global',
            id: 'save-template'
        });
    } catch (error) {
        showNotification('Erro ao salvar template: ' + error.message, 'error');
    }
}

/**
 * Editar template
 */
function editTemplate(templateId) {
    openTemplateModal(templateId);
}


/**
 * Excluir template
 */
async function deleteTemplate(templateId) {
    if (!confirm('Tem certeza que deseja excluir este template?')) {
        return;
    }
    
    try {
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch('/api-whatsapp-templates.php?id=' + templateId, {
                method: 'DELETE'
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Erro ao excluir template');
            }
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Template excluído com sucesso!', 'success');
                loadTemplates();
            } else {
                throw new Error(data.message || 'Erro ao excluir template');
            }
        }, {
            type: 'global',
            id: 'delete-template'
        });
    } catch (error) {
        showNotification('Erro ao excluir template: ' + error.message, 'error');
    }
}

/**
 * Alternar status do template
 */
async function toggleTemplate(templateId) {
    const template = templates.find(t => t.id === templateId);
    if (!template) return;
    
    const newStatus = parseInt(template.is_active) === 1 ? 0 : 1;
    
    try {
        await window.LoadingManager.withLoading(async () => {
            const response = await fetch('/api-whatsapp-templates.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: templateId,
                    name: template.name,
                    type: template.type,
                    title: template.title,
                    message: template.message,
                    is_active: newStatus,
                    is_default: template.is_default
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(newStatus ? 'Template ativado!' : 'Template desativado!', 'success');
                loadTemplates();
            } else {
                throw new Error(data.error || 'Erro ao alterar status');
            }
        }, {
            type: 'global',
            id: 'toggle-template'
        });
    } catch (error) {
        showNotification('Erro ao alterar status: ' + error.message, 'error');
    }
}

/**
 * Visualizar template
 */
function viewTemplate(templateId) {
    const template = templates.find(t => t.id === templateId);
    if (!template) {
        console.error('Template não encontrado:', templateId);
        return;
    }
    
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
    
    currentTemplateId = template.id;
    
    // Formatar mensagem para prévia do WhatsApp
    const formattedMessage = formatMessagePreview(template.message);
    const messageContainer = document.getElementById('viewMessage');
    messageContainer.innerHTML = formattedMessage;
    
    // Detectar se é uma mensagem longa e ajustar altura
    const messageLength = template.message.length;
    const previewElement = messageContainer.querySelector('.whatsapp-message-preview');
    if (previewElement) {
        if (messageLength > 200) {
            previewElement.classList.add('long-message');
        } else {
            previewElement.classList.remove('long-message');
        }
    }
    
    // Criar overlay do celular diretamente
    showPhonePreview(formattedMessage);
}

/**
 * Formatar mensagem para prévia do WhatsApp
 */
function formatMessagePreview(message) {
    // Dicionário de variáveis fictícias
    const sampleData = {
        'cliente_nome': 'João Silva',
        'cliente_usuario': 'joao.silva',
        'cliente_senha': 'senha123',
        'cliente_servidor': 'servidor1.iptv.com',
        'cliente_plano': 'Plano Premium',
        'cliente_vencimento': '15/12/2024',
        'cliente_valor': '29,90',
        'fatura_valor': '29,90',
        'fatura_vencimento': '15/12/2024',
        'fatura_periodo': 'Dezembro 2024'
    };
    
    // Substituir variáveis por dados fictícios
    let formatted = message;
    Object.keys(sampleData).forEach(variable => {
        const regex = new RegExp(`\\{\\{${variable}\\}\\}`, 'g');
        formatted = formatted.replace(regex, `<span class="variable-preview">${sampleData[variable]}</span>`);
    });
    
    // Substituir quebras de linha por <br>
    formatted = formatted.replace(/\n/g, '<br>');
    
    // Destacar texto em negrito (*texto*)
    formatted = formatted.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
    
    // Criar container com estilo de mensagem do WhatsApp
    return `
        <div class="whatsapp-message-preview">
            <div class="message-header">
                <div class="message-avatar">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                    </svg>
                </div>
                <div class="message-info">
                    <div class="message-name">WhatsApp Business</div>
                    <div class="message-time">Agora</div>
                </div>
            </div>
            <div class="message-content">
                ${formatted}
            </div>
            <div class="message-footer">
                <div class="message-status">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                </div>
            </div>
        </div>
    `;
}

/**
 * Mostrar prévia do celular
 */
function showPhonePreview(formattedMessage) {
    // Remover preview existente se houver
    const existingPreview = document.getElementById('phonePreviewOverlay');
    if (existingPreview) {
        existingPreview.remove();
    }
    
    // Criar overlay
    const overlay = document.createElement('div');
    overlay.id = 'phonePreviewOverlay';
    overlay.className = 'phone-preview-overlay';
    
    // Criar container do celular
    const phoneContainer = document.createElement('div');
    phoneContainer.className = 'phone-preview-wrapper';
    
    // Criar botão de fechar
    const closeButton = document.createElement('button');
    closeButton.className = 'phone-preview-close';
    closeButton.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    `;
    closeButton.onclick = closePhonePreview;
    
    // Adicionar mensagem formatada
    phoneContainer.innerHTML = formattedMessage;
    phoneContainer.appendChild(closeButton);
    
    overlay.appendChild(phoneContainer);
    document.body.appendChild(overlay);
    
    // Animar entrada
    setTimeout(() => {
        overlay.classList.add('show');
    }, 10);
    
    // Fechar ao clicar fora do celular
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closePhonePreview();
        }
    });
    
    // Fechar com tecla ESC
    const handleKeyPress = function(e) {
        if (e.key === 'Escape') {
            closePhonePreview();
            document.removeEventListener('keydown', handleKeyPress);
        }
    };
    document.addEventListener('keydown', handleKeyPress);
}

/**
 * Fechar prévia do celular
 */
function closePhonePreview() {
    const overlay = document.getElementById('phonePreviewOverlay');
    if (overlay) {
        overlay.classList.remove('show');
        setTimeout(() => {
            overlay.remove();
        }, 300);
    }
    currentTemplateId = null;
}

/**
 * Fechar modal de visualização (manter para compatibilidade)
 */
function closeViewModal() {
    closePhonePreview();
}

/**
 * Editar a partir da visualização
 */
function editFromView() {
    closeViewModal();
    if (currentTemplateId) {
        openTemplateModal(currentTemplateId);
    }
}

/**
 * Mostrar notificação
 */
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
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
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const templateModal = document.getElementById('templateModal');
    const viewModal = document.getElementById('viewTemplateModal');
    
    if (event.target === templateModal) {
        closeTemplateModal();
    }
    
    if (event.target === viewModal) {
        closeViewModal();
    }
}
