/**
 * Dashboard - JavaScript Limpo
 */

/**
 * Formatar valor monetário
 */
function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value || 0);
}

// Aguardar DOM e CSS carregarem
document.addEventListener('DOMContentLoaded', function () {
    // Aguardar um pouco para garantir que o CSS foi carregado
    setTimeout(() => {
        // Verificar se estamos na página do dashboard
        if (window.location.pathname === '/dashboard' || window.location.pathname === '/') {
            initializeDashboard();
        }
    }, 100);
});

/**
 * Inicializar dashboard
 */
function initializeDashboard() {
    // 1. Configurar eventos PRIMEIRO
    setupDashboardEvents();

    // 3. Carregar dados do usuário
    loadUserData();

    // 4. Mostrar dados por último
    setTimeout(() => {
        showDashboardData();
    }, 200);
}

/**
 * Carregar dados do usuário
 */
function loadUserData() {
    try {
        const userStr = localStorage.getItem('user');
        if (!userStr) {
            return;
        }

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
 * Mostrar dados do dashboard
 */
async function showDashboardData() {
    // Preservar tema atual antes de carregar dados
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const currentThemeClass = document.body.classList.contains('dark-theme');

    try {
        // Buscar dados reais da API
        const token = localStorage.getItem('token');
        if (!token) {
            showFallbackData();
            return;
        }

        const response = await fetch('/api-test.php', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            // Usar dados reais
            await updateDashboardWithRealData(result);
        } else {
            throw new Error('API retornou erro: ' + (result.error || 'Erro desconhecido'));
        }

    } catch (error) {
        await showFallbackData();
    }

    // Restaurar tema após carregar dados
    setTimeout(() => {
        if (currentTheme) {
            document.documentElement.setAttribute('data-theme', currentTheme);
            document.body.setAttribute('data-theme', currentTheme);

            if (currentThemeClass) {
                document.body.classList.add('dark-theme');
                document.documentElement.classList.add('dark-theme');
            }
        }
    }, 100);
}

/**
 * Atualizar dashboard com dados reais
 */
async function updateDashboardWithRealData(data) {
    // Atualizar cards
    updateCard('totalClients', data.totalClients || 0);
    updateCard('monthRevenue', formatCurrency(data.monthRevenue || 0));
    updateCard('inadimplentesValue', formatCurrency(data.inadimplentesValue || 0));
    updateCard('expiringClients', data.expiringClients || 0);
    
    // Atualizar label de inadimplentes com contagem
    const inadimplentesLabel = document.getElementById('inadimplentesLabel');
    if (inadimplentesLabel) {
        const count = data.inadimplentesCount || 0;
        inadimplentesLabel.textContent = `${count} clientes inadimplentes`;
    }

    // Atualizar mudanças
    updateChange('clientsChange', `+${data.clientsGrowth || 0}%`);
    updateChange('revenueChange', `+${data.revenueGrowth || 0}%`);
    updateChange('pendingValue', formatCurrency(data.pendingValue || 0));

    // Atualizar cards de saldo líquido
    updateBalanceCards(data);

    // Carregar tabela de clientes reais
    if (data.expiringClientsList && data.expiringClientsList.length > 0) {
        loadRealExpiringClients(data.expiringClientsList);
    } else {
        loadEmptyExpiringClients();
    }

    // Desenhar gráfico com dados reais
    if (data.revenueChart && data.revenueChart.data && data.revenueChart.data.length > 0) {
        window.lastChartData = data.revenueChart; // Salvar para re-desenhar no tema
        drawRealChart(data.revenueChart);
    } else {
        drawEmptyChart();
    }

    // Inicializar gráficos de analytics
    await initializeAnalyticsCharts();
}

/**
 * Mostrar dados de fallback
 */
async function showFallbackData() {
    // Dados zerados para quando não há dados no banco
    updateCard('totalClients', 0);
    updateCard('monthRevenue', formatCurrency(0));
    updateCard('inadimplentesValue', formatCurrency(0));
    updateCard('expiringClients', 0);
    
    // Atualizar label de inadimplentes com contagem zero
    const inadimplentesLabel = document.getElementById('inadimplentesLabel');
    if (inadimplentesLabel) {
        inadimplentesLabel.textContent = '0 clientes inadimplentes';
    }

    // Atualizar mudanças
    updateChange('clientsChange', '+0%');
    updateChange('revenueChange', '+0%');
    updateChange('pendingValue', formatCurrency(0));

    // Mostrar mensagem de que não há dados
    loadEmptyExpiringClients();
    drawEmptyChart();

    // Inicializar gráficos de analytics mesmo sem dados da API
    await initializeAnalyticsCharts();
}

/**
 * Atualizar card
 */
function updateCard(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

/**
 * Atualizar mudança
 */
function updateChange(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

/**
 * Formatar moeda
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

/**
 * Carregar clientes a vencer
 */
function loadExpiringClients() {
    const clients = [
        { name: 'João Silva', renewal_date: '2025-10-25', status: 'active' },
        { name: 'Maria Santos', renewal_date: '2025-10-26', status: 'active' },
        { name: 'Pedro Costa', renewal_date: '2025-10-27', status: 'active' },
        { name: 'Ana Oliveira', renewal_date: '2025-10-28', status: 'active' },
        { name: 'Carlos Souza', renewal_date: '2025-10-29', status: 'active' }
    ];

    const tbody = document.querySelector('#expiringClientsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = clients.map(client => {
        const daysUntil = calculateDaysUntil(client.renewal_date);
        const statusText = daysUntil === 0 ? 'Hoje' : daysUntil === 1 ? 'Amanhã' : `${daysUntil} dias`;
        const statusClass = daysUntil <= 3 ? 'danger' : daysUntil <= 7 ? 'warning' : 'success';

        return `
            <tr>
                <td>${client.name}</td>
                <td>${formatDate(client.renewal_date)}</td>
                <td><span class="badge badge-${statusClass}">${statusText}</span></td>
            </tr>
        `;
    }).join('');
}

/**
 * Calcular dias até data
 */
function calculateDaysUntil(dateString) {
    const today = new Date();
    const targetDate = new Date(dateString);
    const diffTime = targetDate - today;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

/**
 * Formatar data
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    
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
        return dateString;
    }
}

/**
 * Desenhar gráfico
 */
function drawChart() {
    const canvas = document.getElementById('revenueChart');
    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');

    // Configurar tamanho
    canvas.width = 800;
    canvas.height = 300;

    // Limpar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Dados do gráfico
    const data = [12500, 13200, 14100, 13800, 15200, 15840];
    const labels = ['Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out'];

    // Configurações
    const padding = 60;
    const chartWidth = canvas.width - padding * 2;
    const chartHeight = canvas.height - padding * 2;
    const barWidth = chartWidth / data.length;
    const maxValue = Math.max(...data);

    // Cores baseadas no tema
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const barColor = isDark ? '#818cf8' : '#6366f1';
    const textColor = isDark ? '#cbd5e1' : '#64748b';
    const gridColor = isDark ? '#334155' : '#e2e8f0';

    // Desenhar grid
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 1;
    for (let i = 0; i <= 5; i++) {
        const y = padding + (chartHeight / 5) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(canvas.width - padding, y);
        ctx.stroke();
    }

    // Desenhar barras
    data.forEach((value, index) => {
        const barHeight = (value / maxValue) * chartHeight;
        const x = padding + (barWidth * index) + (barWidth * 0.2);
        const y = canvas.height - padding - barHeight;
        const width = barWidth * 0.6;

        // Barra
        ctx.fillStyle = barColor;
        ctx.fillRect(x, y, width, barHeight);

        // Label
        ctx.fillStyle = textColor;
        ctx.font = '12px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(labels[index], x + width / 2, canvas.height - padding + 20);

        // Valor
        ctx.fillStyle = textColor;
        ctx.font = 'bold 11px sans-serif';
        const shortValue = value >= 1000 ? `${(value / 1000).toFixed(1)}k` : value.toString();
        ctx.fillText(`R$ ${shortValue}`, x + width / 2, y - 5);
    });
}

/**
 * Configurar eventos
 */
function setupDashboardEvents() {
    // Menu mobile
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }

    // Logout
    window.logout = function () {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    };
}

// Adicionar estilos para badges se não existirem
if (!document.querySelector('#badge-styles')) {
    const style = document.createElement('style');
    style.id = 'badge-styles';
    style.textContent = `
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
    `;
    document.head.appendChild(style);
}

/**
 * Carregar clientes reais a vencer
 */
function loadRealExpiringClients(clients) {
    const tbody = document.querySelector('#expiringClientsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = clients.map(client => {
        const daysUntil = calculateDaysUntil(client.renewal_date);
        const statusText = daysUntil === 0 ? 'Hoje' : daysUntil === 1 ? 'Amanhã' : `${daysUntil} dias`;
        const statusClass = daysUntil <= 3 ? 'danger' : daysUntil <= 7 ? 'warning' : 'success';

        return `
            <tr>
                <td>${client.name}</td>
                <td>${formatDate(client.renewal_date)}</td>
                <td><span class="badge badge-${statusClass}">${statusText}</span></td>
            </tr>
        `;
    }).join('');
}

/**
 * Carregar tabela vazia
 */
function loadEmptyExpiringClients() {
    const tbody = document.querySelector('#expiringClientsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="3" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <div style="opacity: 0.6;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; margin-bottom: 1rem;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <div>Nenhum cliente a vencer nos próximos 7 dias</div>
                    <div style="font-size: 0.875rem; margin-top: 0.5rem;">Adicione clientes para ver os vencimentos aqui</div>
                </div>
            </td>
        </tr>
    `;
}

/**
 * Desenhar gráfico com dados reais
 */
function drawRealChart(chartData) {
    const canvas = document.getElementById('revenueChart');
    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');

    // Configurar tamanho
    canvas.width = 800;
    canvas.height = 300;

    // Limpar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Usar dados reais
    const data = chartData.data || [];
    const labels = chartData.labels || [];

    if (data.length === 0 || Math.max(...data) === 0) {
        drawEmptyChart();
        return;
    }

    // Configurações
    const padding = 60;
    const chartWidth = canvas.width - padding * 2;
    const chartHeight = canvas.height - padding * 2;
    const barWidth = chartWidth / data.length;
    const maxValue = Math.max(...data);

    // Cores baseadas no tema
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const barColor = isDark ? '#818cf8' : '#6366f1';
    const textColor = isDark ? '#cbd5e1' : '#64748b';
    const gridColor = isDark ? '#334155' : '#e2e8f0';

    // Desenhar grid
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 1;
    for (let i = 0; i <= 5; i++) {
        const y = padding + (chartHeight / 5) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(canvas.width - padding, y);
        ctx.stroke();
    }

    // Desenhar barras
    data.forEach((value, index) => {
        const barHeight = maxValue > 0 ? (value / maxValue) * chartHeight : 0;
        const x = padding + (barWidth * index) + (barWidth * 0.2);
        const y = canvas.height - padding - barHeight;
        const width = barWidth * 0.6;

        // Barra
        ctx.fillStyle = barColor;
        ctx.fillRect(x, y, width, barHeight);

        // Label
        ctx.fillStyle = textColor;
        ctx.font = '12px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(labels[index] || `Mês ${index + 1}`, x + width / 2, canvas.height - padding + 20);

        // Valor
        ctx.fillStyle = textColor;
        ctx.font = 'bold 11px sans-serif';
        const shortValue = value >= 100 ? `${(value / 1).toFixed(0)}` : value.toFixed(1);
        ctx.fillText(`R$ ${shortValue}`, x + width / 2, y - 5);
    });
}

/**
 * Desenhar gráfico vazio
 */
function drawEmptyChart() {
    const canvas = document.getElementById('revenueChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    canvas.width = 800;
    canvas.height = 300;
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Cores baseadas no tema
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#cbd5e1' : '#64748b';

    // Mensagem de gráfico vazio
    ctx.fillStyle = textColor;
    ctx.font = '16px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Nenhuma receita registrada ainda', canvas.width / 2, canvas.height / 2 - 10);

    ctx.font = '12px sans-serif';
    ctx.fillText('Adicione clientes e faturas para ver o gráfico de receitas', canvas.width / 2, canvas.height / 2 + 15);
}

/**
 * Atualizar cards de saldo líquido
 */
function updateBalanceCards(data) {
    // Saldo Mensal
    updateCard('monthlyBalance', formatCurrency(data.monthlyBalance || 0));
    updateCard('monthlyRevenue', formatCurrency(data.monthlyRevenue || 0));
    updateCard('monthlyExpenses', formatCurrency(data.monthlyExpenses || 0));
    updateChange('monthlyBalanceChange', `+${data.monthlyBalanceChange || 0}%`);

    // Saldo Anual
    updateCard('annualBalance', formatCurrency(data.annualBalance || 0));
    updateCard('annualRevenue', formatCurrency(data.annualRevenue || 0));
    updateCard('annualExpenses', formatCurrency(data.annualExpenses || 0));
    updateChange('annualBalanceChange', `+${data.annualBalanceChange || 0}%`);

    // Aplicar cores baseadas no saldo
    applyBalanceColors(data.monthlyBalance || 0, 'monthly');
    applyBalanceColors(data.annualBalance || 0, 'annual');
}

/**
 * Aplicar cores baseadas no valor do saldo
 */
function applyBalanceColors(balance, type) {
    const balanceElement = document.getElementById(`${type}Balance`);
    const trendElement = document.getElementById(`${type}BalanceChange`);

    if (balanceElement) {
        // Remover classes existentes
        balanceElement.classList.remove('positive-balance', 'negative-balance');

        // Aplicar cor baseada no valor
        if (balance > 0) {
            balanceElement.classList.add('positive-balance');
            balanceElement.style.color = '#10b981';
        } else if (balance < 0) {
            balanceElement.classList.add('negative-balance');
            balanceElement.style.color = '#ef4444';
        } else {
            balanceElement.style.color = 'var(--text-secondary)';
        }
    }

    if (trendElement) {
        const trendIcon = trendElement.parentElement.querySelector('.trend-icon');
        if (trendIcon) {
            if (balance > 0) {
                trendIcon.style.color = '#10b981';
                trendIcon.innerHTML = `
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                    <polyline points="17 6 23 6 23 12"></polyline>
                `;
            } else {
                trendIcon.style.color = '#ef4444';
                trendIcon.innerHTML = `
                    <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
                    <polyline points="17 18 23 18 23 12"></polyline>
                `;
            }
        }
    }
}

/**
 * Inicializar gráficos de analytics (Clientes Novos e Pagamentos)
 */
async function initializeAnalyticsCharts() {
    // Buscar dados reais
    const clientsData = await generateClientsData();
    const paymentsData = generatePaymentsData();

    // Desenhar gráficos
    drawClientsChart(clientsData);
    drawPaymentsChart(paymentsData);

    // Atualizar métricas
    updateClientsMetrics(clientsData);
    updatePaymentsMetrics(paymentsData);

    // Configurar eventos dos selects
    setupAnalyticsEvents();
}

/**
 * Gerar dados reais para clientes novos
 */
async function generateClientsData() {
    const days = 31; // Outubro tem 31 dias
    const data = [];
    const labels = [];

    try {
        // Buscar dados reais da API de clientes
        const response = await fetch('http://localhost:8000/api-clients.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            const result = await response.json();
            const clients = result.clients || [];

            // Inicializar array com zeros para todos os dias
            for (let i = 1; i <= days; i++) {
                data.push(0);
                labels.push(i.toString().padStart(2, '0'));
            }

            // Contar clientes por dia de criação
            clients.forEach(client => {
                if (client.created_at) {
                    const createdDate = new Date(client.created_at);
                    const day = createdDate.getDate();
                    
                    // Se o cliente foi criado em outubro do ano atual
                    const currentYear = new Date().getFullYear();
                    if (createdDate.getMonth() === 9 && createdDate.getFullYear() === currentYear) {
                        if (day >= 1 && day <= 31) {
                            data[day - 1] = (data[day - 1] || 0) + 1;
                        }
                    }
                }
            });
        } else {
            // Fallback: dados simulados
            for (let i = 1; i <= days; i++) {
                data.push(0);
                labels.push(i.toString().padStart(2, '0'));
            }
        }
    } catch (error) {
        // Fallback: dados simulados
        for (let i = 1; i <= days; i++) {
            data.push(0);
            labels.push(i.toString().padStart(2, '0'));
        }
    }

    return { data, labels };
}

/**
 * Gerar dados simulados para pagamentos
 */
function generatePaymentsData() {
    const days = 31; // Outubro tem 31 dias
    const data = [];
    const labels = [];

    for (let i = 1; i <= days; i++) {
        // Simular dados mais realistas - sem pagamentos ainda
        const value = 0;

        data.push(value);
        labels.push(i.toString().padStart(2, '0'));
    }

    return { data, labels };
}

/**
 * Desenhar gráfico de clientes novos
 */
function drawClientsChart(chartData) {
    const canvas = document.getElementById('clientsChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    canvas.width = 500;
    canvas.height = 200;

    // Limpar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const { data, labels } = chartData;
    const maxValue = Math.max(...data, 4); // Mínimo 4 para melhor visualização

    // Configurações
    const padding = 40;
    const chartWidth = canvas.width - padding * 2;
    const chartHeight = canvas.height - padding * 2;
    const pointSpacing = chartWidth / (data.length - 1);

    // Cores fixas profissionais
    const lineColor = '#34d399';
    const gradientStart = 'rgba(52, 211, 153, 0.3)';
    const gradientEnd = 'rgba(52, 211, 153, 0.05)';
    const gridColor = '#334155';
    const textColor = '#cbd5e1';

    // Desenhar grid horizontal com estilo mais sutil
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 0.5;
    ctx.setLineDash([2, 4]);
    for (let i = 0; i <= 4; i++) {
        const y = padding + (chartHeight / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(canvas.width - padding, y);
        ctx.stroke();
    }
    ctx.setLineDash([]);

    // Preparar pontos
    const points = data.map((value, index) => ({
        x: padding + (index * pointSpacing),
        y: canvas.height - padding - (value / maxValue) * chartHeight,
        value: value,
        label: labels[index],
        day: index + 1
    }));

    // Criar gradiente para área preenchida
    const gradient = ctx.createLinearGradient(0, padding, 0, canvas.height - padding);
    gradient.addColorStop(0, gradientStart);
    gradient.addColorStop(1, gradientEnd);

    // Desenhar área preenchida com gradiente suave
    if (points.length > 0) {
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.moveTo(points[0].x, canvas.height - padding);

        // Usar curvas suaves (Bézier) para linha mais profissional
        for (let i = 0; i < points.length; i++) {
            if (i === 0) {
                ctx.lineTo(points[i].x, points[i].y);
            } else {
                const prevPoint = points[i - 1];
                const currentPoint = points[i];
                const cpx = (prevPoint.x + currentPoint.x) / 2;
                ctx.quadraticCurveTo(cpx, prevPoint.y, currentPoint.x, currentPoint.y);
            }
        }

        ctx.lineTo(points[points.length - 1].x, canvas.height - padding);
        ctx.closePath();
        ctx.fill();
    }

    // Desenhar linha principal com curvas suaves
    if (points.length > 1) {
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        // Adicionar sombra à linha
        ctx.shadowColor = 'rgba(52, 211, 153, 0.3)';
        ctx.shadowBlur = 8;
        ctx.shadowOffsetY = 2;

        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);

        for (let i = 1; i < points.length; i++) {
            const prevPoint = points[i - 1];
            const currentPoint = points[i];
            const cpx = (prevPoint.x + currentPoint.x) / 2;
            ctx.quadraticCurveTo(cpx, prevPoint.y, currentPoint.x, currentPoint.y);
        }

        ctx.stroke();
        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
        ctx.shadowOffsetY = 0;
    }

    // Desenhar pontos com efeito mais elegante
    points.forEach((point, index) => {
        // Desenhar ponto para todos os dias, mas destacar os com dados
        const hasData = data[index] > 0;
        const pointSize = hasData ? 6 : 3;
        const pointColor = hasData ? lineColor : gridColor;

        // Sombra do ponto
        if (hasData) {
            ctx.shadowColor = 'rgba(52, 211, 153, 0.4)';
            ctx.shadowBlur = 6;
        }

        // Ponto principal
        ctx.fillStyle = pointColor;
        ctx.beginPath();
        ctx.arc(point.x, point.y, pointSize, 0, Math.PI * 2);
        ctx.fill();

        // Círculo interno escuro
        if (hasData) {
            ctx.fillStyle = '#1e293b';
            ctx.beginPath();
            ctx.arc(point.x, point.y, pointSize - 2, 0, Math.PI * 2);
            ctx.fill();
        }

        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
    });

    // Labels dos dias (melhor distribuição)
    ctx.fillStyle = textColor;
    ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    ctx.textAlign = 'center';

    const labelStep = Math.max(1, Math.ceil(data.length / 10));
    for (let i = 0; i < data.length; i += labelStep) {
        const x = padding + (i * pointSpacing);
        const dayLabel = `${(i + 1).toString().padStart(2, '0')}/10`;
        ctx.fillText(dayLabel, x, canvas.height - 10);
    }

    // Salvar dados para tooltip
    canvas.chartData = { points, data, labels };
}

/**
 * Desenhar gráfico de pagamentos
 */
function drawPaymentsChart(chartData) {
    const canvas = document.getElementById('paymentsChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    canvas.width = 500;
    canvas.height = 200;

    // Limpar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const { data, labels } = chartData;
    const maxValue = Math.max(...data, 100); // Valor mínimo para visualização

    // Configurações
    const padding = 40;
    const chartWidth = canvas.width - padding * 2;
    const chartHeight = canvas.height - padding * 2;
    const pointSpacing = chartWidth / (data.length - 1);

    // Cores fixas profissionais
    const lineColor = '#fbbf24';
    const gradientStart = 'rgba(251, 191, 36, 0.3)';
    const gradientEnd = 'rgba(251, 191, 36, 0.05)';
    const gridColor = '#334155';
    const textColor = '#cbd5e1';

    // Desenhar grid horizontal com estilo mais sutil
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 0.5;
    ctx.setLineDash([2, 4]);
    for (let i = 0; i <= 4; i++) {
        const y = padding + (chartHeight / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(canvas.width - padding, y);
        ctx.stroke();
    }
    ctx.setLineDash([]);

    // Preparar pontos
    const points = data.map((value, index) => ({
        x: padding + (index * pointSpacing),
        y: canvas.height - padding - (value / maxValue) * chartHeight,
        value: value,
        label: labels[index],
        day: index + 1
    }));

    // Verificar se há dados reais
    const hasRealData = data.some(value => value > 0);

    if (hasRealData) {
        // Criar gradiente para área preenchida
        const gradient = ctx.createLinearGradient(0, padding, 0, canvas.height - padding);
        gradient.addColorStop(0, gradientStart);
        gradient.addColorStop(1, gradientEnd);

        // Desenhar área preenchida com gradiente suave
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.moveTo(points[0].x, canvas.height - padding);

        // Usar curvas suaves (Bézier) para linha mais profissional
        for (let i = 0; i < points.length; i++) {
            if (i === 0) {
                ctx.lineTo(points[i].x, points[i].y);
            } else {
                const prevPoint = points[i - 1];
                const currentPoint = points[i];
                const cpx = (prevPoint.x + currentPoint.x) / 2;
                ctx.quadraticCurveTo(cpx, prevPoint.y, currentPoint.x, currentPoint.y);
            }
        }

        ctx.lineTo(points[points.length - 1].x, canvas.height - padding);
        ctx.closePath();
        ctx.fill();

        // Desenhar linha principal com curvas suaves
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        // Adicionar sombra à linha
        ctx.shadowColor = 'rgba(251, 191, 36, 0.3)';
        ctx.shadowBlur = 8;
        ctx.shadowOffsetY = 2;

        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);

        for (let i = 1; i < points.length; i++) {
            const prevPoint = points[i - 1];
            const currentPoint = points[i];
            const cpx = (prevPoint.x + currentPoint.x) / 2;
            ctx.quadraticCurveTo(cpx, prevPoint.y, currentPoint.x, currentPoint.y);
        }

        ctx.stroke();
        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
        ctx.shadowOffsetY = 0;

        // Desenhar pontos
        points.forEach((point, index) => {
            const hasData = data[index] > 0;
            const pointSize = hasData ? 6 : 3;
            const pointColor = hasData ? lineColor : gridColor;

            if (hasData) {
                ctx.shadowColor = 'rgba(251, 191, 36, 0.4)';
                ctx.shadowBlur = 6;
            }

            ctx.fillStyle = pointColor;
            ctx.beginPath();
            ctx.arc(point.x, point.y, pointSize, 0, Math.PI * 2);
            ctx.fill();

            if (hasData) {
                ctx.fillStyle = '#1e293b';
                ctx.beginPath();
                ctx.arc(point.x, point.y, pointSize - 2, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;
        });
    } else {
        // Mostrar linha zero com estilo elegante
        const zeroY = canvas.height - padding;

        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 2;
        ctx.globalAlpha = 0.3;
        ctx.setLineDash([8, 8]);
        ctx.beginPath();
        ctx.moveTo(padding, zeroY);
        ctx.lineTo(canvas.width - padding, zeroY);
        ctx.stroke();
        ctx.setLineDash([]);
        ctx.globalAlpha = 1;

        // Mensagem de sem dados mais elegante
        ctx.fillStyle = textColor;
        ctx.font = '14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.textAlign = 'center';
        ctx.globalAlpha = 0.6;
        ctx.fillText('Nenhum pagamento registrado ainda', canvas.width / 2, canvas.height / 2 - 10);

        ctx.font = '12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText('Adicione faturas para ver o gráfico de pagamentos', canvas.width / 2, canvas.height / 2 + 10);
        ctx.globalAlpha = 1;
    }

    // Labels dos dias (melhor distribuição)
    ctx.fillStyle = textColor;
    ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    ctx.textAlign = 'center';

    const labelStep = Math.max(1, Math.ceil(data.length / 10));
    for (let i = 0; i < data.length; i += labelStep) {
        const x = padding + (i * pointSpacing);
        const dayLabel = `${(i + 1).toString().padStart(2, '0')}/10`;
        ctx.fillText(dayLabel, x, canvas.height - 10);
    }

    // Salvar dados para tooltip
    canvas.chartData = { points, data, labels };
}

/**
 * Atualizar métricas de clientes
 */
function updateClientsMetrics(chartData) {
    const { data } = chartData;

    const total = data.reduce((sum, val) => sum + val, 0);
    const avg = (total / 7).toFixed(1); // Média dos últimos 7 dias
    const today = data[data.length - 1] || 0;
    const maxValue = Math.max(...data);
    const bestDayIndex = data.indexOf(maxValue);

    // Atualizar elementos
    updateElement('totalNewClients', total);
    updateElement('avgNewClients', avg);
    updateElement('todayNewClients', today);
    updateElement('bestDayClients', maxValue);

    // Atualizar subtítulos
    const bestDayElement = document.querySelector('#bestDayClients')?.parentElement?.querySelector('.metric-subtitle');
    if (bestDayElement && maxValue > 0) {
        const dayName = new Date(2025, 9, bestDayIndex + 1).toLocaleDateString('pt-BR', { day: 'numeric', month: 'long' });
        bestDayElement.textContent = dayName;
    }
}

/**
 * Atualizar métricas de pagamentos
 */
function updatePaymentsMetrics(chartData) {
    const { data } = chartData;

    const total = data.reduce((sum, val) => sum + val, 0);
    const avg = (total / 7).toFixed(0); // Média dos últimos 7 dias
    const today = data[data.length - 1] || 0;
    const maxValue = Math.max(...data);

    // Atualizar elementos
    updateElement('totalPayments', formatCurrency(total));
    updateElement('avgPayments', formatCurrency(avg));
    updateElement('todayPayments', today);
    updateElement('bestDayPayments', formatCurrency(maxValue));
}

/**
 * Configurar eventos dos analytics
 */
function setupAnalyticsEvents() {
    const clientsPeriodSelect = document.getElementById('clientsPeriod');
    const paymentsPeriodSelect = document.getElementById('paymentsPeriod');

    if (clientsPeriodSelect) {
        clientsPeriodSelect.addEventListener('change', function () {
            // Aqui você pode recarregar os dados baseado no período selecionado
            const newData = generateClientsData(); // Por enquanto, mesmos dados
            drawClientsChart(newData);
            updateClientsMetrics(newData);
        });
    }

    if (paymentsPeriodSelect) {
        paymentsPeriodSelect.addEventListener('change', function () {
            // Aqui você pode recarregar os dados baseado no período selecionado
            const newData = generatePaymentsData(); // Por enquanto, mesmos dados
            drawPaymentsChart(newData);
            updatePaymentsMetrics(newData);
        });
    }
}

/**
 * Atualizar elemento por ID
 */
function updateElement(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}/*
*
 * Top 5 Servidores - Funcionalidades
 */

// Variáveis globais para o componente de servidores
let serversData = [];
let serversChart = null;
let currentView = 'chart';

/**
 * Inicializar componente Top 5 Servidores
 */
async function initializeTopServers() {
    try {
        await loadServersData();
        setupServersEventListeners();
        renderServersChart();
        renderServersList();
        updateServersStats();
    } catch (error) {
        console.error('Erro ao inicializar Top 5 Servidores:', error);
        showServersError();
    }
}

/**
 * Carregar dados dos servidores
 */
async function loadServersData() {
    try {
        console.log('Carregando dados dos servidores...');
        const response = await fetch('/api-servers-stats.php');
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Erro na resposta:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Dados recebidos:', data);
        
        if (data.success) {
            serversData = data;
            console.log('Dados dos servidores carregados com sucesso');
        } else {
            throw new Error(data.error || 'Erro ao carregar dados dos servidores');
        }
    } catch (error) {
        console.error('Erro ao carregar dados dos servidores:', error);
        // Usar dados de fallback em caso de erro
        serversData = generateFallbackServersData();
        console.log('Usando dados de fallback');
    }
}

/**
 * Configurar event listeners para servidores
 */
function setupServersEventListeners() {
    // Tabs de visualização
    const viewTabs = document.querySelectorAll('.view-tab');
    viewTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const view = this.dataset.view;
            switchServersView(view);
        });
    });
    
    // Hover no gráfico para tooltip
    const canvas = document.getElementById('serversChart');
    if (canvas) {
        canvas.addEventListener('mousemove', handleChartHover);
        canvas.addEventListener('mouseleave', hideServersTooltip);
    }
}

/**
 * Alternar visualização (pizza/barras)
 */
function switchServersView(view) {
    currentView = view;
    
    // Atualizar tabs
    document.querySelectorAll('.view-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-view="${view}"]`).classList.add('active');
    
    // Re-renderizar gráfico
    renderServersChart();
}

/**
 * Renderizar gráfico de servidores
 */
function renderServersChart() {
    const canvas = document.getElementById('serversChart');
    if (!canvas || !serversData.servers) return;
    
    const ctx = canvas.getContext('2d');
    
    // Configurar tamanho do canvas baseado na tela
    let size = 400;
    if (window.innerWidth <= 480) {
        size = 250;
    } else if (window.innerWidth <= 768) {
        size = 300;
    }
    
    canvas.width = size;
    canvas.height = size;
    canvas.style.width = size + 'px';
    canvas.style.height = size + 'px';
    
    // Limpar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (serversData.servers.length === 0) {
        drawEmptyServersChart(ctx, canvas);
        return;
    }
    
    if (currentView === 'chart') {
        drawPieChart(ctx, canvas);
    } else {
        drawBarChart(ctx, canvas);
    }
}

/**
 * Desenhar gráfico de pizza
 */
function drawPieChart(ctx, canvas) {
    const servers = serversData.servers;
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const radius = Math.min(centerX, centerY) - 40;
    
    let currentAngle = -Math.PI / 2; // Começar do topo
    const total = servers.reduce((sum, server) => sum + server.client_count, 0);
    
    // Desenhar fatias
    servers.forEach((server, index) => {
        const sliceAngle = (server.client_count / total) * 2 * Math.PI;
        
        // Fatia
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
        ctx.closePath();
        ctx.fillStyle = server.color;
        ctx.fill();
        
        // Borda
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.stroke();
        
        // Armazenar dados para hover
        server.chartData = {
            centerX,
            centerY,
            radius,
            startAngle: currentAngle,
            endAngle: currentAngle + sliceAngle
        };
        
        currentAngle += sliceAngle;
    });
    
    // Círculo central para efeito donut
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius * 0.4, 0, 2 * Math.PI);
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--bg-secondary');
    ctx.fill();
    
    // Texto central
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-primary');
    ctx.font = 'bold 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(total.toString(), centerX, centerY - 5);
    
    ctx.font = '14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary');
    ctx.fillText('Total Clientes', centerX, centerY + 20);
}

/**
 * Desenhar gráfico de barras
 */
function drawBarChart(ctx, canvas) {
    const servers = serversData.servers;
    const padding = 60;
    const chartWidth = canvas.width - padding * 2;
    const chartHeight = canvas.height - padding * 2;
    const barWidth = chartWidth / servers.length * 0.8;
    const barSpacing = chartWidth / servers.length * 0.2;
    
    const maxClients = Math.max(...servers.map(s => s.client_count));
    
    // Cores do tema
    const textColor = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary');
    const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border');
    
    // Grid horizontal
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 1;
    for (let i = 0; i <= 5; i++) {
        const y = padding + (chartHeight / 5) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(canvas.width - padding, y);
        ctx.stroke();
    }
    
    // Barras
    servers.forEach((server, index) => {
        const barHeight = (server.client_count / maxClients) * chartHeight;
        const x = padding + (barWidth + barSpacing) * index + barSpacing / 2;
        const y = canvas.height - padding - barHeight;
        
        // Barra
        ctx.fillStyle = server.color;
        ctx.fillRect(x, y, barWidth, barHeight);
        
        // Label do servidor
        ctx.fillStyle = textColor;
        ctx.font = '12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.textAlign = 'center';
        
        // Nome do servidor (truncado se necessário)
        let serverName = server.name;
        if (serverName.length > 10) {
            serverName = serverName.substring(0, 10) + '...';
        }
        ctx.fillText(serverName, x + barWidth / 2, canvas.height - padding + 20);
        
        // Valor
        ctx.fillStyle = textColor;
        ctx.font = 'bold 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText(server.client_count.toString(), x + barWidth / 2, y - 10);
        
        // Armazenar dados para hover
        server.chartData = {
            x: x,
            y: y,
            width: barWidth,
            height: barHeight
        };
    });
}

/**
 * Desenhar gráfico vazio
 */
function drawEmptyServersChart(ctx, canvas) {
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary');
    ctx.font = '16px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Nenhum servidor encontrado', centerX, centerY - 10);
    
    ctx.font = '12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    ctx.fillText('Adicione servidores para ver as estatísticas', centerX, centerY + 15);
}

/**
 * Renderizar lista de servidores
 */
function renderServersList() {
    const container = document.getElementById('serversListContent');
    if (!container || !serversData.servers) return;
    
    if (serversData.servers.length === 0) {
        container.innerHTML = `
            <div class="servers-empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
                <h3>Nenhum servidor encontrado</h3>
                <p>Adicione servidores para ver o ranking</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = serversData.servers.map(server => `
        <div class="server-item" data-server-id="${server.id}">
            <div class="server-rank rank-${server.rank}">${server.rank}</div>
            <div class="server-info">
                <div class="server-name-item">${server.name}</div>
                <div class="server-stats-item">
                    ${server.status === 'active' ? 'Ativo' : 'Inativo'} • 
                    ${formatMoney(server.total_revenue)} receita
                </div>
            </div>
            <div class="server-metrics">
                <div class="server-clients">${server.client_count}</div>
                <div class="server-percentage">${server.client_percentage}%</div>
            </div>
        </div>
    `).join('');
}

/**
 * Atualizar estatísticas dos servidores
 */
function updateServersStats() {
    if (!serversData.stats) return;
    
    const stats = serversData.stats;
    
    // Estatísticas gerais
    updateElement('totalClientsInTop', stats.top_stats.total_clients);
    updateElement('totalRevenueInTop', formatMoney(stats.top_stats.total_revenue));
    updateElement('totalServerCosts', formatMoney(stats.top_stats.total_costs || 0));
    updateElement('averageClientsPerServer', stats.top_stats.average_clients);
}

/**
 * Manipular hover no gráfico
 */
function handleChartHover(event) {
    if (!serversData.servers || currentView !== 'chart') return;
    
    const canvas = event.target;
    const rect = canvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    
    // Verificar se o mouse está sobre alguma fatia
    for (const server of serversData.servers) {
        if (server.chartData && isPointInPieSlice(x, y, server.chartData)) {
            showServersTooltip(event, server);
            return;
        }
    }
    
    hideServersTooltip();
}

/**
 * Verificar se o ponto está dentro da fatia da pizza
 */
function isPointInPieSlice(x, y, chartData) {
    const { centerX, centerY, radius, startAngle, endAngle } = chartData;
    
    // Calcular distância do centro
    const distance = Math.sqrt((x - centerX) ** 2 + (y - centerY) ** 2);
    
    // Verificar se está dentro do raio
    if (distance > radius || distance < radius * 0.4) return false;
    
    // Calcular ângulo
    let angle = Math.atan2(y - centerY, x - centerX);
    if (angle < 0) angle += 2 * Math.PI;
    
    // Ajustar para começar do topo
    angle = (angle + Math.PI / 2) % (2 * Math.PI);
    
    // Verificar se está dentro do ângulo da fatia
    let start = (startAngle + Math.PI / 2) % (2 * Math.PI);
    let end = (endAngle + Math.PI / 2) % (2 * Math.PI);
    
    if (start > end) {
        return angle >= start || angle <= end;
    } else {
        return angle >= start && angle <= end;
    }
}

/**
 * Mostrar tooltip dos servidores
 */
function showServersTooltip(event, server) {
    const tooltip = document.getElementById('serversTooltip');
    if (!tooltip) return;
    
    // Atualizar conteúdo
    tooltip.querySelector('.server-color').style.backgroundColor = server.color;
    tooltip.querySelector('.server-name').textContent = server.name;
    tooltip.querySelector('.clients-count').textContent = server.client_count;
    tooltip.querySelector('.revenue-value').textContent = formatMoney(server.total_revenue);
    tooltip.querySelector('.clients-percentage').textContent = `${server.client_percentage}%`;
    tooltip.querySelector('.revenue-percentage').textContent = `${server.revenue_percentage}%`;
    
    // Posicionar tooltip
    const rect = event.target.getBoundingClientRect();
    tooltip.style.left = `${event.clientX - rect.left + 15}px`;
    tooltip.style.top = `${event.clientY - rect.top - 15}px`;
    
    // Mostrar tooltip
    tooltip.classList.add('visible');
}

/**
 * Esconder tooltip dos servidores
 */
function hideServersTooltip() {
    const tooltip = document.getElementById('serversTooltip');
    if (tooltip) {
        tooltip.classList.remove('visible');
    }
}

/**
 * Mostrar erro nos servidores
 */
function showServersError() {
    const container = document.getElementById('serversListContent');
    if (container) {
        container.innerHTML = `
            <div class="servers-empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color: #ef4444;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <h3>Erro ao carregar servidores</h3>
                <p>Não foi possível conectar com o servidor</p>
            </div>
        `;
    }
}

/**
 * Gerar dados de fallback
 */
function generateFallbackServersData() {
    return {
        servers: [],
        stats: {
            total_clients: 0,
            total_revenue: 0,
            active_servers: 0,
            total_servers: 0,
            average_clients: 0,
            top_stats: {
                total_clients: 0,
                total_revenue: 0,
                active_servers: 0,
                total_servers_in_top: 0,
                average_clients: 0
            }
        }
    };
}

// Adicionar inicialização do Top 5 Servidores ao dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Aguardar um pouco para garantir que outros componentes foram inicializados
    setTimeout(() => {
        if (window.location.pathname === '/dashboard' || window.location.pathname === '/') {
            initializeTopServers();
        }
    }, 500);
});

// Redimensionar gráfico quando a janela for redimensionada
window.addEventListener('resize', function() {
    if (serversData && serversData.servers) {
        setTimeout(() => {
            renderServersChart();
        }, 100);
    }
});
// toggleSubmenu agora está em common.js