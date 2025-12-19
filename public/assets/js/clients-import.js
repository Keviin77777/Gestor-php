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
    // Validar tipo de arquivo (aceitar .xlsx e .csv)
    const isXlsx = file.name.endsWith('.xlsx');
    const isCsv = file.name.endsWith('.csv');
    
    if (!isXlsx && !isCsv) {
        showNotification('Apenas arquivos .xlsx ou .csv são aceitos', 'error');
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
    
    // Se for CSV, mostrar botão de download XLSX
    if (isCsv) {
        document.getElementById('csvConvertBtn').style.display = 'block';
    } else {
        document.getElementById('csvConvertBtn').style.display = 'none';
    }
    
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
 * Ler arquivo Excel ou CSV
 */
function readExcelFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        const isCsv = file.name.endsWith('.csv');
        
        reader.onload = (e) => {
            try {
                let jsonData;
                
                if (isCsv) {
                    // Ler CSV
                    const text = e.target.result;
                    const workbook = XLSX.read(text, { type: 'string' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    jsonData = XLSX.utils.sheet_to_json(firstSheet);
                } else {
                    // Ler XLSX
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    jsonData = XLSX.utils.sheet_to_json(firstSheet);
                }
                
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
        
        if (isCsv) {
            reader.readAsText(file);
        } else {
            reader.readAsArrayBuffer(file);
        }
    });
}

/**
 * Validar dados
 */
function validateData(data) {
    return data.map((row, index) => {
        const errors = [];
        
        // Detectar formato Sigma (username, password, expiry_date, connections, name, whatsapp, telegram, email, note, plan_price, server, package)
        const isSigmaFormat = row.hasOwnProperty('username') && row.hasOwnProperty('password') && row.hasOwnProperty('expiry_date') && row.hasOwnProperty('package');
        
        let client;
        
        if (isSigmaFormat) {
            // Formato Sigma
            // Log para debug - ver todos os campos disponíveis
            if (index === 0) {
                console.log('Campos disponíveis na planilha Sigma:', Object.keys(row));
                console.log('Valor do campo package:', row.package);
                console.log('Valor do campo plan:', row.plan);
            }
            
            client = {
                index: index + 1,
                name: row.name || row.note || `Cliente ${index + 1}`,
                username: row.username || '',
                iptv_password: row.password || '',
                phone: row.whatsapp || row.telegram || '',
                renewal_date: row.expiry_date || '',
                server: row.server || '',
                application: 'NextApp', // Padrão para Sigma
                mac: '', // Sigma não exporta MAC
                plan: row.package || row.plan || row.plano || '',
                email: row.email || '',
                value: parseFloat(row.plan_price) || 0,
                screens: parseInt(row.connections) || 1,
                notes: row.note || '',
                errors: [],
                valid: true,
                isSigmaFormat: true
            };
        } else {
            // Formato padrão do sistema
            client = {
                index: index + 1,
                name: row.nome || row.Nome || row.NOME || row.name || '',
                username: row.usuario_iptv || row['Usuário IPTV'] || row.USUARIO_IPTV || row.username || '',
                iptv_password: row.senha_iptv || row['Senha IPTV'] || row.SENHA_IPTV || row.password || '',
                phone: row.whatsapp || row.WhatsApp || row.WHATSAPP || '',
                renewal_date: row.vencimento || row.Vencimento || row.VENCIMENTO || row.expiry_date || '',
                server: row.servidor || row.Servidor || row.SERVIDOR || row.server || '',
                application: row.aplicativo || row.Aplicativo || row.APLICATIVO || row.application || '',
                mac: row.mac || row.MAC || row.Mac || '',
                plan: row.plano || row.Plano || row.PLANO || row.package || '',
                email: row.email || row.Email || row.EMAIL || '',
                value: parseFloat(row.valor || row.value || row.plan_price || 0),
                screens: parseInt(row.telas || row.screens || row.connections || 1),
                notes: row.observacoes || row.notes || row.note || '',
                errors: [],
                valid: true,
                isSigmaFormat: false
            };
        }

        // Validar campos obrigatórios (email é opcional)
        if (!client.name) errors.push('Nome é obrigatório');
        if (!client.username) errors.push('Usuário IPTV é obrigatório');
        if (!client.iptv_password) errors.push('Senha IPTV é obrigatória');
        if (!client.phone) errors.push('WhatsApp é obrigatório');
        if (!client.renewal_date) errors.push('Vencimento é obrigatório');
        if (!client.server) errors.push('Servidor é obrigatório');
        if (!client.application) errors.push('Aplicativo é obrigatório');
        if (!client.plan) errors.push('Plano é obrigatório');

        // Validar formato de email (se fornecido)
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
 * Criar planos da planilha automaticamente
 */
async function createPlansFromSpreadsheet() {
    // Verificar se há servidores cadastrados
    if (servers.length === 0) {
        showNotification('Você precisa cadastrar pelo menos um servidor antes de criar planos', 'error');
        return;
    }
    
    // Obter planos únicos da planilha
    const uniquePlans = [...new Set(parsedData.map(c => c.plan).filter(p => p))];
    
    // Verificar quais planos não existem
    const missingPlans = uniquePlans.filter(planName => 
        !plans.find(p => p.name === planName)
    );
    
    if (missingPlans.length === 0) {
        showNotification('Todos os planos já existem no sistema', 'info');
        return;
    }
    
    // Perguntar qual servidor usar para os planos
    let selectedServerId = null;
    
    if (servers.length === 1) {
        // Se só tem 1 servidor, usar automaticamente
        selectedServerId = servers[0].id;
    } else {
        // Se tem múltiplos servidores, perguntar
        const serverOptions = servers.map((s, idx) => `${idx + 1}. ${s.name}`).join('\n');
        const serverChoice = prompt(`Selecione o servidor para os planos:\n\n${serverOptions}\n\nDigite o número:`);
        
        if (!serverChoice) return;
        
        const serverIndex = parseInt(serverChoice) - 1;
        if (serverIndex >= 0 && serverIndex < servers.length) {
            selectedServerId = servers[serverIndex].id;
        } else {
            showNotification('Servidor inválido', 'error');
            return;
        }
    }
    
    if (!confirm(`Deseja criar ${missingPlans.length} plano(s) automaticamente no servidor "${servers.find(s => s.id === selectedServerId)?.name}"?\n\n${missingPlans.slice(0, 10).join('\n')}${missingPlans.length > 10 ? '\n...' : ''}`)) {
        return;
    }
    
    GlobalLoading.show('Criando Planos...', `Criando ${missingPlans.length} plano(s)`);
    
    let created = 0;
    let errors = [];
    
    for (const planName of missingPlans) {
        try {
            // Buscar valor do plano na planilha (usar o primeiro cliente com esse plano)
            const clientWithPlan = parsedData.find(c => c.plan === planName);
            const planValue = clientWithPlan?.value || 25.00; // Valor padrão se não encontrar
            
            const response = await fetch('/api-plans.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: planName,
                    price: planValue,
                    server_id: selectedServerId,
                    duration_days: 30,
                    max_screens: 1,
                    description: `Plano importado automaticamente da planilha`
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                created++;
                // Adicionar à lista de planos
                plans.push({
                    id: data.plan_id || data.id || Date.now(),
                    name: planName,
                    price: planValue
                });
            } else {
                errors.push(`${planName}: ${data.error || 'Erro desconhecido'}`);
            }
        } catch (error) {
            errors.push(`${planName}: ${error.message}`);
        }
    }
    
    GlobalLoading.hide();
    
    if (created > 0) {
        showNotification(`${created} plano(s) criado(s) com sucesso!`, 'success');
        // Recarregar planos e atualizar selects
        await loadPlans();
        renderPreview();
    }
    
    if (errors.length > 0) {
        console.error('Erros ao criar planos:', errors);
        showNotification(`Alguns planos não puderam ser criados. Verifique o console para detalhes.`, 'warning');
    }
}

/**
 * Aplicar servidor para todos
 */
function applyBulkServer(value) {
    if (!value) return;
    
    parsedData.forEach((client) => {
        client.server = value;
        // Revalidar apenas este campo
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
    
    parsedData.forEach((client) => {
        client.application = value;
        // Revalidar
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

        // Formatar data corretamente
        const formattedDate = formatDate(client.renewal_date);
        
        // Montar opções de servidor
        let serverOptions = '<option value="">Selecione...</option>';
        if (servers && servers.length > 0) {
            serverOptions += servers.map(s => `<option value="${escapeHtml(s.name)}" ${s.name === client.server ? 'selected' : ''}>${escapeHtml(s.name)}</option>`).join('');
        }
        
        // Verificar se o plano existe, se não, adicionar opção de criar
        const planExists = plans.find(p => p.name === client.plan);
        let planOptions = '<option value="">Selecione...</option>';
        
        if (client.plan && !planExists) {
            planOptions += `<option value="${escapeHtml(client.plan)}" selected style="color: #f59e0b;">⚠️ ${escapeHtml(client.plan)} (Criar)</option>`;
        }
        
        if (plans && plans.length > 0) {
            planOptions += plans.map(p => `<option value="${escapeHtml(p.name)}" ${p.name === client.plan ? 'selected' : ''}>${escapeHtml(p.name)}</option>`).join('');
        }
        
        // Montar opções de aplicativo
        let appOptions = '<option value="">Selecione...</option>';
        if (applications && applications.length > 0) {
            appOptions += applications.map(a => `<option value="${escapeHtml(a.name)}" ${a.name === client.application ? 'selected' : ''}>${escapeHtml(a.name)}</option>`).join('');
        }
        
        tr.innerHTML = `
            <td>${client.index}</td>
            <td><input type="text" class="${nameClass}" value="${escapeHtml(client.name)}" onchange="updateClient(${idx}, 'name', this.value)" placeholder="Nome completo"></td>
            <td><input type="text" class="${usernameClass}" value="${escapeHtml(client.username)}" onchange="updateClient(${idx}, 'username', this.value)" placeholder="Usuário"></td>
            <td><input type="text" class="${passwordClass}" value="${escapeHtml(client.iptv_password)}" onchange="updateClient(${idx}, 'iptv_password', this.value)" placeholder="Senha"></td>
            <td><input type="text" class="${phoneClass}" value="${escapeHtml(client.phone)}" onchange="updateClient(${idx}, 'phone', this.value)" placeholder="11999999999"></td>
            <td><input type="date" class="${dateClass}" value="${formattedDate}" onchange="updateClient(${idx}, 'renewal_date', this.value)"></td>
            <td>
                <select class="${serverClass}" onchange="updateClient(${idx}, 'server', this.value)">
                    ${serverOptions}
                </select>
            </td>
            <td>
                <select class="${planClass}" onchange="updateClient(${idx}, 'plan', this.value)">
                    ${planOptions}
                </select>
            </td>
            <td>
                <select class="${appClass}" onchange="updateClient(${idx}, 'application', this.value)">
                    ${appOptions}
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
    // Aceitar formatos: DD/MM/YYYY, YYYY-MM-DD, DD-MM-YYYY, YYYY-MM-DD HH:MM:SS
    const formats = [
        /^\d{2}\/\d{2}\/\d{4}$/,
        /^\d{4}-\d{2}-\d{2}$/,
        /^\d{2}-\d{2}-\d{4}$/,
        /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/ // Formato Sigma
    ];

    return formats.some(format => format.test(dateString));
}

/**
 * Formatar data
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    // Remover espaços extras
    dateString = String(dateString).trim();
    
    // Se já estiver no formato YYYY-MM-DD, retornar
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        return dateString;
    }
    
    // Formato Sigma: YYYY-MM-DD HH:MM:SS -> YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dateString)) {
        return dateString.split(' ')[0];
    }
    
    // Converter DD/MM/YYYY para YYYY-MM-DD
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
        const [day, month, year] = dateString.split('/');
        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }
    
    // Converter DD-MM-YYYY para YYYY-MM-DD
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateString)) {
        const [day, month, year] = dateString.split('-');
        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }
    
    // Converter serial number do Excel para data
    // Excel armazena datas como números seriais onde 1 = 01/01/1900
    if (!isNaN(dateString) && Number(dateString) > 0) {
        const excelEpoch = new Date(1899, 11, 30); // 30/12/1899 (Excel base)
        const days = Math.floor(Number(dateString));
        const date = new Date(excelEpoch.getTime() + days * 86400000);
        
        if (!isNaN(date.getTime())) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    }
    
    // Tentar parsear como Date (último recurso)
    try {
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    } catch (e) {
        console.error('Erro ao formatar data:', dateString, e);
    }
    
    return '';
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
 * Converter CSV para XLSX e baixar
 */
async function convertCsvToXlsx() {
    if (!selectedFile || !selectedFile.name.endsWith('.csv')) {
        showNotification('Nenhum arquivo CSV selecionado', 'error');
        return;
    }
    
    try {
        GlobalLoading.show('Convertendo...', 'Gerando arquivo XLSX');
        
        // Ler CSV
        const text = await readFileAsText(selectedFile);
        const workbook = XLSX.read(text, { type: 'string' });
        
        // Converter para XLSX
        const xlsxData = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
        
        // Criar blob e baixar
        const blob = new Blob([xlsxData], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = selectedFile.name.replace('.csv', '.xlsx');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        GlobalLoading.hide();
        showNotification('Arquivo XLSX baixado com sucesso!', 'success');
        
    } catch (error) {
        GlobalLoading.hide();
        showNotification('Erro ao converter arquivo: ' + error.message, 'error');
    }
}

/**
 * Ler arquivo como texto
 */
function readFileAsText(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target.result);
        reader.onerror = () => reject(new Error('Erro ao ler arquivo'));
        reader.readAsText(file);
    });
}



/**
 * Remover clientes vencidos da importação
 */
function removeExpiredClients() {
    // Obter data de hoje no formato YYYY-MM-DD (sem hora)
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    
    const expiredClients = parsedData.filter(client => {
        if (!client.renewal_date) return false;
        
        // Garantir que a data está no formato YYYY-MM-DD
        const renewalDateStr = formatDate(client.renewal_date);
        if (!renewalDateStr) return false;
        
        // Comparar strings de data (YYYY-MM-DD)
        return renewalDateStr < todayStr;
    });
    
    if (expiredClients.length === 0) {
        showNotification('Nenhum cliente vencido encontrado', 'info');
        return;
    }
    
    if (!confirm(`Deseja remover ${expiredClients.length} cliente(s) vencido(s) da importação?\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    // Remover clientes vencidos
    parsedData = parsedData.filter(client => {
        if (!client.renewal_date) return true;
        
        // Garantir que a data está no formato YYYY-MM-DD
        const renewalDateStr = formatDate(client.renewal_date);
        if (!renewalDateStr) return true;
        
        // Manter apenas clientes com data >= hoje
        return renewalDateStr >= todayStr;
    });
    
    // Reindexar
    parsedData.forEach((client, idx) => {
        client.index = idx + 1;
    });
    
    showNotification(`${expiredClients.length} cliente(s) vencido(s) removido(s)`, 'success');
    renderPreview();
}

/**
 * Remover clientes de teste da importação
 */
function removeTestClients() {
    const testKeywords = ['teste', 'test', 'demo', 'trial', 'prova'];
    
    const testClients = parsedData.filter(client => {
        const name = (client.name || '').toLowerCase();
        const plan = (client.plan || '').toLowerCase();
        const username = (client.username || '').toLowerCase();
        
        return testKeywords.some(keyword => 
            name.includes(keyword) || 
            plan.includes(keyword) || 
            username.includes(keyword)
        );
    });
    
    if (testClients.length === 0) {
        showNotification('Nenhum cliente de teste encontrado', 'info');
        return;
    }
    
    if (!confirm(`Deseja remover ${testClients.length} cliente(s) de teste da importação?\n\nSerão removidos clientes com "teste", "test", "demo", "trial" ou "prova" no nome, usuário ou plano.\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    // Remover clientes de teste
    parsedData = parsedData.filter(client => {
        const name = (client.name || '').toLowerCase();
        const plan = (client.plan || '').toLowerCase();
        const username = (client.username || '').toLowerCase();
        
        return !testKeywords.some(keyword => 
            name.includes(keyword) || 
            plan.includes(keyword) || 
            username.includes(keyword)
        );
    });
    
    // Reindexar
    parsedData.forEach((client, idx) => {
        client.index = idx + 1;
    });
    
    showNotification(`${testClients.length} cliente(s) de teste removido(s)`, 'success');
    renderPreview();
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
