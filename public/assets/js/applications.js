/**
 * Applications - JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticação
    if (!isAuthenticated()) {
        window.location.href = '/login';
        return;
    }
    
    // Carregar dados do usuário
    loadUserData();
    
    // Carregar aplicativos
    loadApplications();
    
    // Configurar eventos
    setupEvents();
});

/**
 * Configurar eventos
 */
function setupEvents() {
    // Menu mobile já é gerenciado pelo mobile-responsive.js
    // Não precisa duplicar aqui

    // Busca
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterApplications(this.value);
        });
    }

    // Botão adicionar aplicativo
    const addBtn = document.querySelector('.btn.btn-primary');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            openApplicationModal();
        });
    }
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
        }
}

/**
 * Carregar aplicativos
 */
async function loadApplications() {
    try {
        // Verificar se LoadingManager está disponível
        if (typeof LoadingManager !== 'undefined' && LoadingManager.show) {
            LoadingManager.show('Carregando aplicativos...');
        }
        
        const response = await fetch('/api/applications', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        if (result.success) {
            displayApplications(result.applications || []);
        } else {
            throw new Error(result.error || 'Erro desconhecido');
        }

    } catch (error) {
        showError('Erro ao carregar aplicativos: ' + error.message);
        displayEmptyState();
    } finally {
        // Verificar se LoadingManager está disponível
        if (typeof LoadingManager !== 'undefined' && LoadingManager.hide) {
            // Verificar se LoadingManager está disponível
        if (typeof LoadingManager !== 'undefined' && LoadingManager.hide) {
            LoadingManager.hide();
        }
        }
    }
}

/**
 * Exibir aplicativos
 */
function displayApplications(applications) {
    const cardBody = document.querySelector('.card-body');
    if (!cardBody) return;

    if (applications.length === 0) {
        displayEmptyState();
        return;
    }

    cardBody.innerHTML = `
        <div class="applications-grid">
            ${applications.map(app => `
                <div class="application-card" data-id="${app.id}">
                    <div class="application-header">
                        <div class="application-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        </div>
                        <div class="application-actions">
                            <button class="btn-action" onclick="editApplication(${app.id})" title="Editar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="btn-action danger" onclick="deleteApplication(${app.id})" title="Excluir">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3,6 5,6 21,6"></polyline>
                                    <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="application-body">
                        <h3 class="application-name">${escapeHtml(app.name)}</h3>
                        <p class="application-description">${escapeHtml(app.description || 'Sem descrição')}</p>
                    </div>
                    <div class="application-footer">
                        <span class="application-date">
                            Criado em ${formatDate(app.created_at)}
                        </span>
                    </div>
                </div>
            `).join('')}
        </div>
        <div class="applications-actions">
            <button class="btn btn-primary" onclick="openApplicationModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Adicionar Aplicativo
            </button>
        </div>
    `;
}

/**
 * Exibir estado vazio
 */
function displayEmptyState() {
    const cardBody = document.querySelector('.card-body');
    if (!cardBody) return;

    cardBody.innerHTML = `
        <div style="text-align: center; padding: 3rem 2rem; color: var(--text-secondary);">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem; opacity: 0.5;">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Nenhum aplicativo encontrado</h3>
            <p style="margin-bottom: 2rem;">Adicione seu primeiro aplicativo para começar.</p>
            <button class="btn btn-primary" onclick="openApplicationModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Adicionar Aplicativo
            </button>
        </div>
    `;
}

/**
 * Abrir modal de aplicativo
 */
function openApplicationModal(applicationId = null) {
    // Criar modal dinamicamente
    const modal = createApplicationModal();
    document.body.appendChild(modal);
    
    const modalTitle = modal.querySelector('.modal-title');
    const form = modal.querySelector('#applicationForm');
    
    if (applicationId) {
        modalTitle.textContent = 'Editar Aplicativo';
        loadApplicationData(applicationId, form);
    } else {
        modalTitle.textContent = 'Novo Aplicativo';
        form.reset();
    }
    
    modal.style.display = 'flex';
    modal.classList.add('active');
    
    // Focar no primeiro campo
    setTimeout(() => {
        const nameInput = modal.querySelector('#applicationName');
        if (nameInput) nameInput.focus();
    }, 100);
}

/**
 * Criar modal de aplicativo
 */
function createApplicationModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'applicationModal';
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Novo Aplicativo</h2>
                <button class="modal-close" onclick="closeApplicationModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="applicationForm">
                    <div class="form-group">
                        <label for="applicationName">Nome do Aplicativo *</label>
                        <input type="text" id="applicationName" name="name" required 
                               placeholder="Ex: XCIPTV, IPTV SMARTERS, TiviMate">
                    </div>
                    
                    <div class="form-group">
                        <label for="applicationDescription">Descrição</label>
                        <textarea id="applicationDescription" name="description" rows="3" 
                                  placeholder="Descreva o aplicativo e seus recursos..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeApplicationModal()">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="saveApplication()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    Salvar
                </button>
            </div>
        </div>
    `;
    
    // Fechar modal ao clicar fora
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeApplicationModal();
        }
    });
    
    return modal;
}

/**
 * Fechar modal de aplicativo
 */
function closeApplicationModal() {
    const modal = document.getElementById('applicationModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

/**
 * Salvar aplicativo
 */
async function saveApplication() {
    try {
        const form = document.getElementById('applicationForm');
        const formData = new FormData(form);
        const applicationId = form.dataset.applicationId;
        
        const data = {
            name: formData.get('name'),
            description: formData.get('description') || ''
        };

        // Validação
        if (!data.name || data.name.trim() === '') {
            showError('O nome do aplicativo é obrigatório');
            return;
        }

        // Verificar se LoadingManager está disponível
        if (typeof LoadingManager !== 'undefined' && LoadingManager.show) {
            LoadingManager.show('Salvando aplicativo...');
        }

        const token = localStorage.getItem('token');
        const url = applicationId ? `/api/applications/${applicationId}` : '/api/applications';
        const method = applicationId ? 'PUT' : 'POST';
        
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        const response = await fetch(url, {
            method: method,
            credentials: 'include',
            headers: headers,
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            throw new Error('Resposta inválida do servidor');
        }

        if (result.success) {
            showSuccess(applicationId ? 'Aplicativo atualizado com sucesso!' : 'Aplicativo criado com sucesso!');
            closeApplicationModal();
            loadApplications();
        } else {
            throw new Error(result.error || 'Erro ao salvar aplicativo');
        }

    } catch (error) {
        showError('Erro ao salvar aplicativo: ' + error.message);
    } finally {
        // Verificar se LoadingManager está disponível
        if (typeof LoadingManager !== 'undefined' && LoadingManager.hide) {
            LoadingManager.hide();
        }
    }
}

/**
 * Carregar dados do aplicativo para edição
 */
async function loadApplicationData(applicationId, form) {
    try {
        // Verificar se LoadingManager está disponível
        if (typeof LoadingManager !== 'undefined' && LoadingManager.show) {
            LoadingManager.show('Carregando dados...');
        }
        
        const token = localStorage.getItem('token');
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        const response = await fetch(`/api/applications/${applicationId}`, {
            method: 'GET',
            credentials: 'include',
            headers: headers
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            throw new Error('Resposta inválida do servidor');
        }

        if (result.success && result.application) {
            const app = result.application;
            
            // Preencher formulário
            document.getElementById('applicationName').value = app.name;
            document.getElementById('applicationDescription').value = app.description || '';
            
            // Armazenar ID no formulário
            form.dataset.applicationId = applicationId;
        } else {
            throw new Error(result.error || 'Aplicativo não encontrado');
        }
    } catch (error) {
        showError('Erro ao carregar dados do aplicativo');
        closeApplicationModal();
    } finally {
        // Verificar se LoadingManager está disponível
        if (typeof LoadingManager !== 'undefined' && LoadingManager.hide) {
            LoadingManager.hide();
        }
    }
}

/**
 * Editar aplicativo
 */
function editApplication(applicationId) {
    openApplicationModal(applicationId);
}

/**
 * Excluir aplicativo
 */
async function deleteApplication(applicationId) {
    if (!confirm('Tem certeza que deseja excluir este aplicativo?')) {
        return;
    }

    try {
        // Verificar se LoadingManager está disponível
        if (typeof LoadingManager !== 'undefined' && LoadingManager.show) {
            LoadingManager.show('Excluindo aplicativo...');
        }
        
        const token = localStorage.getItem('token');
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        const response = await fetch(`/api/applications/${applicationId}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: headers
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            throw new Error('Resposta inválida do servidor');
        }

        if (result.success) {
            showSuccess('Aplicativo excluído com sucesso!');
            loadApplications();
        } else {
            throw new Error(result.error || 'Erro ao excluir aplicativo');
        }

    } catch (error) {
        showError('Erro ao excluir aplicativo: ' + error.message);
    } finally {
        // Verificar se LoadingManager está disponível
        if (typeof LoadingManager !== 'undefined' && LoadingManager.hide) {
            LoadingManager.hide();
        }
    }
}

/**
 * Filtrar aplicativos
 */
function filterApplications(searchTerm) {
    const cards = document.querySelectorAll('.application-card');
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        card.style.display = matches ? '' : 'none';
    });
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
        // Erro ao formatar data
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
    // Implementar notificação de erro
    alert('Erro: ' + message);
}

/**
 * Mostrar sucesso
 */
function showSuccess(message) {
    // Implementar notificação de sucesso
    alert('Sucesso: ' + message);
}

// logout agora está em common.js