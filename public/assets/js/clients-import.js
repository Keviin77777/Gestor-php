/**
 * Importação de Clientes - JavaScript
 */

let selectedFile = null;
let parsedData = [];
let servers = [];
let applications = [];
let plans = [];

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname === '/clients/import') {
        initImport();
    }
});

/**
 * Inicializar importação
 */
function initImport() {
    setupDragAndDrop();
    loadServers();
    loadApplications();
    loadPlans();
    restoreProgress();
}

/**
 * Restaurar progresso salvo
 */
function restoreProgress() {
    const savedStep = sessionStorage.getItem('importStep');
    const savedData = sessionStorage.getItem('importData');
    const savedFileName = sessionStorage.getItem('importFileName');
    
    if (savedStep && savedData) {
        try {
            parsedData = JSON.parse(savedData);
            
            if (savedStep === 'step2' && savedFileName) {
                // Restaurar step 2 (upload)
                showUploadStep();
                document.getElementById('uploadArea').style.display = 'none';
                document.getElementById('fileInfo').style.display = 'flex';
                document.getElementById('continueBtn').style.display = 'block';
                document.getElementById('fileName').textContent = savedFileName;
                document.getElementById('fileSize').textContent = 'Arquivo carregado';
            } else if (savedStep === 'step3' && parsedData.length > 0) {
                // Restaurar step 3 (preview)
                showPreviewStep();
                renderPreview();
            }
        } catch (error) {
            console.error('Erro ao restaurar progresso:', error);
            clearProgress();
        }
    }
}

/**
 * Salvar progresso
 */
function saveProgress(step) {
    sessionStorage.setItem('importStep', step);
    if (parsedData.length > 0) {
        sessionStorage.setItem('importData', JSON.stringify(parsedData));
    }
    if (selectedFile) {
        sessionStorage.setItem('importFileName', selectedFile.name);
    }
}

/**
 * Limpar progresso salvo
 */
function clearProgress() {
    sessionStorage.removeItem('importStep');
    sessionStorage.removeItem('importData');
    sessionStorage.removeItem('importFileName');
}

/**
 * Configurar drag and drop
 */
function setupDragAndDrop() {
    const uploadArea = document.getElementById('uploadArea');
    
    if (!uploadArea) return;

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('drag-over');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });

    uploadArea.addEventListener('click', () => {
        document.getElementById('fileInput').click();
    });
}

/**
 * Carregar servidores
 */
async function loadServers() {
    try {
        const response = await fetch('/api-servers.php');
        const data = await response.json();
        
        if (data.success) {
            servers = data.servers || [];
        }
    } catch (error) {
        console.error('Erro ao carregar servidores:', error);
    }
}

/**
 * Carregar aplicativos
 */
async function loadApplications() {
    try {
        const response = await fetch('/api-applications.php');
        const data = await response.json();
        
        if (data.success && data.applications) {
            applications = data.applications;
        } else {
            // Fallback para lista padrão
            applications = [
                { id: 1, name: 'NextApp' },
                { id: 2, name: 'SmartIPTV' },
                { id: 3, name: 'IPTV Smarters' },
                { id: 4, name: 'TiviMate' }
            ];
        }
    } catch (error) {
        console.error('Erro ao carregar aplicativos:', error);
        // Fallback para lista padrão
        applications = [
            { id: 1, name: 'NextApp' },
            { id: 2, name: 'SmartIPTV' },
            { id: 3, name: 'IPTV Smarters' },
            { id: 4, name: 'TiviMate' }
        ];
    }
}

/**
 * Carregar planos
 */
async function loadPlans() {
    try {
        const response = await fetch('/api-plans.php');
        const data = await response.json();
        
        if (data.success && data.plans) {
            plans = data.plans.map(p => ({
                id: p.id,
                name: p.name,
                price: parseFloat(p.price) || 0
            }));
        } else {
            // Fallback para lista padrão
            plans = [
                { id: 1, name: 'Básico', price: 25.00 },
                { id: 2, name: 'Premium', price: 35.00 },
                { id: 3, name: 'VIP', price: 50.00 },
                { id: 4, name: 'Personalizado', price: 0 }
            ];
        }
    } catch (error) {
        console.error('Erro ao carregar planos:', error);
        // Fallback para lista padrão
        plans = [
            { id: 1, name: 'Básico', price: 25.00 },
            { id: 2, name: 'Premium', price: 35.00 },
            { id: 3, name: 'VIP', price: 50.00 },
            { id: 4, name: 'Personalizado', price: 0 }
        ];
    }
}

/**
 * Mostrar step de método
 */
function showMethodStep() {
    document.getElementById('step1').style.display = 'block';
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step3').style.display = 'none';
    clearProgress();
}

/**
 * Mostrar step de upload
 */
function showUploadStep() {
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
    document.getElementById('step3').style.display = 'none';
    saveProgress('step2');
}

/**
 * Mostrar step de preview
 */
function showPreviewStep() {
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step3').style.display = 'block';
    saveProgress('step3');
}

/**
 * Manipular seleção de arquivo
 */
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        handleFile(file);
    }
}

/**
 * Manipular arquivo
 */
function handleFile(file) {
    // Validar tipo de arquivo
    if (!file.name.endsWith('.xlsx')) {
        showNotification('Apenas arquivos .xlsx são aceitos', 'error');
        return;
    }

    // Validar tamanho (máximo 10MB)
    if (file.size > 10 * 1024 * 1024) {
        showNotification('Arquivo muito grande. Máximo 10MB', 'error');
        return;
    }

    selectedFile = file;

    // Mostrar informações do arquivo
    document.getElementById('uploadArea').style.display = 'none';
    document.getElementById('fileInfo').style.display = 'flex';
    document.getElementById('continueBtn').style.display = 'block';
    
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatFileSize(file.size);
}

/**
 * Remover arquivo
 */
function removeFile() {
    selectedFile = null;
    parsedData = [];
    
    document.getElementById('uploadArea').style.display = 'block';
    document.getElementById('fileInfo').style.display = 'none';
    document.getElementById('continueBtn').style.display = 'none';
    document.getElementById('fileInput').value = '';
}

/**
 * Processar arquivo
 */
async function processFile() {
    if (!selectedFile) {
        showNotification('Nenhum arquivo selecionado', 'error');
        return;
    }

    // Pequeno delay para garantir que o loading seja mostrado
    setTimeout(async () => {
        GlobalLoading.show('Processando Arquivo...', 'Lendo planilha e validando dados');

        try {
            const data = await readExcelFile(selectedFile);
            parsedData = validateData(data);
            
            // Pequeno delay antes de esconder para garantir transição suave
            await new Promise(resolve => setTimeout(resolve, 300));
            
            // Esconder loading
            GlobalLoading.hide();
            
            // Pequeno delay antes de mostrar preview
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // Mostrar preview
            showPreviewStep();
            renderPreview();
            
        } catch (error) {
            GlobalLoading.hide();
            setTimeout(() => {
                showNotification('Erro ao processar arquivo: ' + error.message, 'error');
            }, 300);
        }
    }, 100);
}

/**
 * Ler arquivo Excel
 */
function readExcelFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                
                // Pegar primeira planilha
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet);
                
                if (jsonData.length === 0) {
                    reject(new Error('Planilha vazia'));
                    return;
                }

                if (jsonData.length > 1000) {
                    reject(new Error('Máximo de 1000 clientes por importação'));
                    return;
                }
                
                resolve(jsonData);
            } catch (error) {
                reject(error);
            }
        };
        
        reader.onerror = () => reject(new Error('Erro ao ler arquivo'));
        reader.readAsArrayBuffer(file);
    });
}

/**
 * Validar dados
 */
function validateData(data) {
    const requiredFields = [
        'nome',
        'usuario_iptv',
        'senha_iptv',
        'whatsapp',
        'vencimento',
        'servidor',
        'aplicativo',
        'mac',
        'plano',
        'email'
    ];

    return data.map((row, index) => {
        const errors = [];
        const client = {
            index: index + 1,
            name: row.nome || row.Nome || row.NOME || '',
            username: row.usuario_iptv || row['Usuário IPTV'] || row.USUARIO_IPTV || '',
            iptv_password: row.senha_iptv || row['Senha IPTV'] || row.SENHA_IPTV || '',
            phone: row.whatsapp || row.WhatsApp || row.WHATSAPP || '',
            renewal_date: row.vencimento || row.Vencimento || row.VENCIMENTO || '',
            server: row.servidor || row.Servidor || row.SERVIDOR || '',
            application: row.aplicativo || row.Aplicativo || row.APLICATIVO || '',
            mac: row.mac || row.MAC || row.Mac || '',
            plan: row.plano || row.Plano || row.PLANO || '',
            email: row.email || row.Email || row.EMAIL || '',
            errors: [],
            valid: true
        };

        // Validar campos obrigatórios
        if (!client.name) errors.push('Nome é obrigatório');
        if (!client.username) errors.push('Usuário IPTV é obrigatório');
        if (!client.iptv_password) errors.push('Senha IPTV é obrigatória');
        if (!client.phone) errors.push('WhatsApp é obrigatório');
        if (!client.renewal_date) errors.push('Vencimento é obrigatório');
        if (!client.server) errors.push('Servidor é obrigatório');
        if (!client.application) errors.push('Aplicativo é obrigatório');
        if (!client.mac) errors.push('MAC é obrigatório');
        if (!client.plan) errors.push('Plano é obrigatório');
        if (!client.email) errors.push('Email é obrigatório');

        // Validar formato de email
        if (client.email && !isValidEmail(client.email)) {
            errors.push('Email inválido');
        }

        // Validar formato de data
        if (client.renewal_date && !isValidDate(client.renewal_date)) {
            errors.push('Data de vencimento inválida');
        }

        // Validar servidor existe
        if (client.server && !servers.find(s => s.name === client.server)) {
            errors.push('Servidor não encontrado');
        }

        client.errors = errors;
        client.valid = errors.length === 0;

        return client;
    });
}

/**
 * Popular selects de ação em massa
 */
async function populateBulkSelects() {
    // Garantir que os dados estejam carregados
    if (servers.length === 0) {
        await loadServers();
    }
    if (plans.length === 0) {
        await loadPlans();
    }
    if (applications.length === 0) {
        await loadApplications();
    }
    
    // Popular servidor
    const bulkServer = document.getElementById('bulkServer');
    if (bulkServer) {
        bulkServer.innerHTML = '<option value="">Selecione...</option>' +
            servers.map(s => `<option value="${escapeHtml(s.name)}">${escapeHtml(s.name)}</option>`).join('');
    }
    
    // Popular plano
    const bulkPlan = document.getElementById('bulkPlan');
    if (bulkPlan) {
        bulkPlan.innerHTML = '<option value="">Selecione...</option>' +
            plans.map(p => `<option value="${escapeHtml(p.name)}">${escapeHtml(p.name)}</option>`).join('');
    }
    
    // Popular app
    const bulkApp = document.getElementById('bulkApp');
    if (bulkApp) {
        bulkApp.innerHTML = '<option value="">Selecione...</option>' +
            applications.map(a => `<option value="${escapeHtml(a.name)}">${escapeHtml(a.name)}</option>`).join('');
    }
}

/**
 * Aplicar servidor para todos
 */
function applyBulkServer(value) {
    if (!value) return;
    
    parsedData.forEach((client, idx) => {
        client.server = value;
        updateClient(idx, 'server', value);
    });
    
    // Re-renderizar tabela
    renderPreview();
    
    showNotification(`Servidor "${value}" aplicado para todos os clientes`, 'success');
}

/**
 * Aplicar plano para todos
 */
function applyBulkPlan(value) {
    if (!value) return;
    
    parsedData.forEach((client, idx) => {
        client.plan = value;
        // Atualizar valor automaticamente
        const selectedPlan = plans.find(p => p.name === value);
        if (selectedPlan && selectedPlan.price) {
            client.value = selectedPlan.price;
        }
    });
    
    // Re-renderizar tabela
    renderPreview();
    
    showNotification(`Plano "${value}" aplicado para todos os clientes`, 'success');
}

/**
 * Aplicar app para todos
 */
function applyBulkApp(value) {
    if (!value) return;
    
    parsedData.forEach((client, idx) => {
        client.application = value;
        updateClient(idx, 'application', value);
    });
    
    // Re-renderizar tabela
    renderPreview();
    
    showNotification(`Aplicativo "${value}" aplicado para todos os clientes`, 'success');
}

/**
 * Renderizar preview
 */
function renderPreview() {
    const tbody = document.getElementById('previewTableBody');
    tbody.innerHTML = '';

    const totalClients = parsedData.length;
    const validClients = parsedData.filter(c => c.valid).length;
    const invalidClients = totalClients - validClients;

    // Atualizar estatísticas
    document.getElementById('totalClients').textContent = totalClients;
    document.getElementById('validClients').textContent = validClients;
    document.getElementById('invalidClients').textContent = invalidClients;
    
    // Popular selects de ação em massa
    populateBulkSelects();

    // Renderizar tabela com campos editáveis (8 colunas)
    parsedData.forEach((client, idx) => {
        const tr = document.createElement('tr');
        tr.id = `client-row-${idx}`;

        const nameClass = !client.name ? 'error' : 'valid';
        const usernameClass = !client.username ? 'error' : 'valid';
        const passwordClass = !client.iptv_password ? 'error' : 'valid';
        const phoneClass = !client.phone ? 'error' : 'valid';
        const dateClass = !client.renewal_date ? 'error' : 'valid';
        const serverClass = !client.server ? 'error' : 'valid';
        const planClass = !client.plan ? 'error' : 'valid';
        const appClass = !client.application ? 'error' : 'valid';

        tr.innerHTML = `
            <td>${client.index}</td>
            <td><input type="text" class="${nameClass}" value="${escapeHtml(client.name)}" onchange="updateClient(${idx}, 'name', this.value)" placeholder="Nome completo"></td>
            <td><input type="text" class="${usernameClass}" value="${escapeHtml(client.username)}" onchange="updateClient(${idx}, 'username', this.value)" placeholder="Usuário"></td>
            <td><input type="text" class="${passwordClass}" value="${escapeHtml(client.iptv_password)}" onchange="updateClient(${idx}, 'iptv_password', this.value)" placeholder="Senha"></td>
            <td><input type="text" class="${phoneClass}" value="${escapeHtml(client.phone)}" onchange="updateClient(${idx}, 'phone', this.value)" placeholder="11999999999"></td>
            <td><input type="date" class="${dateClass}" value="${formatDate(client.renewal_date)}" onchange="updateClient(${idx}, 'renewal_date', this.value)"></td>
            <td>
                <select class="${serverClass}" onchange="updateClient(${idx}, 'server', this.value)">
                    <option value="">Selecione...</option>
                    ${servers.map(s => `<option value="${escapeHtml(s.name)}" ${s.name === client.server ? 'selected' : ''}>${escapeHtml(s.name)}</option>`).join('')}
                </select>
            </td>
            <td>
                <select class="${planClass}" onchange="updateClient(${idx}, 'plan', this.value)">
                    <option value="">Selecione...</option>
                    ${plans.map(p => `<option value="${escapeHtml(p.name)}" ${p.name === client.plan ? 'selected' : ''}>${escapeHtml(p.name)}</option>`).join('')}
                </select>
            </td>
            <td>
                <select class="${appClass}" onchange="updateClient(${idx}, 'application', this.value)">
                    <option value="">Selecione...</option>
                    ${applications.map(a => `<option value="${escapeHtml(a.name)}" ${a.name === client.application ? 'selected' : ''}>${escapeHtml(a.name)}</option>`).join('')}
                </select>
            </td>
            <td id="status-${idx}">
                ${renderStatus(client)}
            </td>
        `;

        tbody.appendChild(tr);
    });

    // Atualizar botão de importar
    updateImportButton();
}

/**
 * Renderizar status
 */
function renderStatus(client) {
    if (client.valid) {
        return `
            <span class="status-badge valid">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 12l2 2 4-4"></path>
                    <circle cx="12" cy="12" r="10"></circle>
                </svg>
                Válido
            </span>
        `;
    } else {
        return `
            <span class="status-badge invalid" title="${client.errors.join(', ')}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                Erro
            </span>
        `;
    }
}

/**
 * Atualizar cliente
 */
function updateClient(index, field, value) {
    parsedData[index][field] = value;
    
    // Se o campo for 'plan', atualizar o valor automaticamente
    if (field === 'plan' && value) {
        const selectedPlan = plans.find(p => p.name === value);
        if (selectedPlan && selectedPlan.price) {
            parsedData[index].value = selectedPlan.price;
        }
    }
    
    // Revalidar cliente
    const client = parsedData[index];
    const errors = [];
    
    if (!client.name) errors.push('Nome é obrigatório');
    if (!client.username) errors.push('Usuário IPTV é obrigatório');
    if (!client.iptv_password) errors.push('Senha IPTV é obrigatória');
    if (!client.phone) errors.push('WhatsApp é obrigatório');
    if (!client.renewal_date) errors.push('Vencimento é obrigatório');
    if (!client.server) errors.push('Servidor é obrigatório');
    if (!client.plan) errors.push('Plano é obrigatório');
    if (!client.application) errors.push('Aplicativo é obrigatório');
    
    client.errors = errors;
    client.valid = errors.length === 0;
    
    // Atualizar status visual do campo editado
    const row = document.getElementById(`client-row-${index}`);
    if (row) {
        const input = row.querySelector(`input[onchange*="${field}"], select[onchange*="${field}"]`);
        if (input) {
            input.classList.remove('error', 'valid');
            input.classList.add(value && value.trim() ? 'valid' : 'error');
        }
        
        // Atualizar badge de status
        const statusCell = document.getElementById(`status-${index}`);
        if (statusCell) {
            statusCell.innerHTML = renderStatus(client);
        }
    }
    
    // Atualizar estatísticas
    const validClients = parsedData.filter(c => c.valid).length;
    const invalidClients = parsedData.length - validClients;
    
    document.getElementById('validClients').textContent = validClients;
    document.getElementById('invalidClients').textContent = invalidClients;
    
    // Atualizar botão de importar
    updateImportButton();
}

/**
 * Atualizar botão de importar
 */
function updateImportButton() {
    const invalidClients = parsedData.filter(c => !c.valid).length;
    const importBtn = document.getElementById('importBtn');
    
    if (invalidClients > 0) {
        importBtn.disabled = true;
        importBtn.style.opacity = '0.5';
        importBtn.style.cursor = 'not-allowed';
    } else {
        importBtn.disabled = false;
        importBtn.style.opacity = '1';
        importBtn.style.cursor = 'pointer';
    }
}

/**
 * Escapar HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Importar clientes
 */
async function importClients() {
    const validClients = parsedData.filter(c => c.valid);
    
    if (validClients.length === 0) {
        showNotification('Nenhum cliente válido para importar', 'error');
        return;
    }

    if (!confirm(`Deseja importar ${validClients.length} cliente(s)?`)) {
        return;
    }

    // Usar GlobalLoading
    GlobalLoading.show(
        'Importando Clientes...',
        `Processando ${validClients.length} cliente(s)`
    );

    try {
        const response = await fetch('/api-clients-import.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                clients: validClients
            })
        });

        const data = await response.json();

        if (data.success) {
            clearProgress(); // Limpar progresso após sucesso
            
            // Mostrar sucesso
            GlobalLoading.showSuccess(
                'Importação Concluída!',
                `${data.imported} cliente(s) importado(s) com sucesso`
            );
            
            setTimeout(() => {
                window.location.href = '/clients';
            }, 2000);
        } else {
            GlobalLoading.hide();
            showNotification('Erro ao importar clientes: ' + data.error, 'error');
        }
    } catch (error) {
        GlobalLoading.showError(
            'Erro na Importação',
            error.message
        );
    }
}

/**
 * Validar email
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validar data
 */
function isValidDate(dateString) {
    // Aceitar formatos: DD/MM/YYYY, YYYY-MM-DD, DD-MM-YYYY
    const formats = [
        /^\d{2}\/\d{2}\/\d{4}$/,
        /^\d{4}-\d{2}-\d{2}$/,
        /^\d{2}-\d{2}-\d{4}$/
    ];

    return formats.some(format => format.test(dateString));
}

/**
 * Formatar data
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    // Se já estiver no formato YYYY-MM-DD, retornar
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        return dateString;
    }
    
    // Converter DD/MM/YYYY para YYYY-MM-DD
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
        const [day, month, year] = dateString.split('/');
        return `${year}-${month}-${day}`;
    }
    
    // Converter DD-MM-YYYY para YYYY-MM-DD
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateString)) {
        const [day, month, year] = dateString.split('-');
        return `${year}-${month}-${day}`;
    }
    
    return dateString;
}

/**
 * Formatar tamanho de arquivo
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}



/**
 * Mostrar notificação
 */
function showNotification(message, type = 'info') {
    // Criar notificação
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#6366f1'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease;
        max-width: 400px;
    `;
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Remover após 5 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 5000);
}

// Adicionar animações CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
