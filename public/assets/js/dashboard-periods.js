/**
 * Gerenciamento de Períodos do Dashboard
 */

/**
 * Calcular datas baseado no período selecionado
 */
function calculatePeriodDates(period) {
    const now = new Date();
    let startDate, endDate;

    switch (period) {
        // Períodos Rápidos
        case 'today':
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
            break;

        case 'yesterday':
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
            endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1, 23, 59, 59);
            break;

        case 'this-week':
            const dayOfWeek = now.getDay();
            const diff = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Segunda-feira = 0
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - diff);
            endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            break;

        case 'last-week':
            const lastWeekStart = new Date(now);
            lastWeekStart.setDate(now.getDate() - now.getDay() - 6);
            const lastWeekEnd = new Date(lastWeekStart);
            lastWeekEnd.setDate(lastWeekStart.getDate() + 6);
            startDate = lastWeekStart;
            endDate = lastWeekEnd;
            break;

        case 'this-month':
            startDate = new Date(now.getFullYear(), now.getMonth(), 1);
            endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
            break;

        case 'last-month':
            startDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            endDate = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59);
            break;

        // Por Quantidade de Dias
        case '7':
        case '15':
        case '30':
        case '60':
        case '90':
        case '180':
            const days = parseInt(period);
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - days + 1);
            endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
            break;

        // Períodos Longos
        case 'this-quarter':
            const currentQuarter = Math.floor(now.getMonth() / 3);
            startDate = new Date(now.getFullYear(), currentQuarter * 3, 1);
            endDate = new Date(now.getFullYear(), (currentQuarter + 1) * 3, 0, 23, 59, 59);
            break;

        case 'last-quarter':
            const lastQuarter = Math.floor(now.getMonth() / 3) - 1;
            const quarterYear = lastQuarter < 0 ? now.getFullYear() - 1 : now.getFullYear();
            const quarterMonth = lastQuarter < 0 ? 9 : lastQuarter * 3;
            startDate = new Date(quarterYear, quarterMonth, 1);
            endDate = new Date(quarterYear, quarterMonth + 3, 0, 23, 59, 59);
            break;

        case 'this-year':
            startDate = new Date(now.getFullYear(), 0, 1);
            endDate = new Date(now.getFullYear(), 11, 31, 23, 59, 59);
            break;

        case 'last-year':
            startDate = new Date(now.getFullYear() - 1, 0, 1);
            endDate = new Date(now.getFullYear() - 1, 11, 31, 23, 59, 59);
            break;

        default:
            // Padrão: mês atual
            startDate = new Date(now.getFullYear(), now.getMonth(), 1);
            endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
    }

    return { startDate, endDate };
}

/**
 * Gerar dados de clientes baseado no período
 */
async function generateClientsDataByPeriod(period) {
    const { startDate, endDate } = calculatePeriodDates(period);
    
    // Calcular número de dias no período
    const diffTime = Math.abs(endDate - startDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    
    const data = [];
    const labels = [];

    try {
        // Buscar dados reais da API de clientes
        const response = await fetch('/api-clients.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            const result = await response.json();
            const clients = result.clients || [];

            // Inicializar array com zeros para todos os dias
            for (let i = 0; i < diffDays; i++) {
                data.push(0);
                const currentDate = new Date(startDate);
                currentDate.setDate(startDate.getDate() + i);
                labels.push(currentDate.getDate().toString().padStart(2, '0'));
            }

            // Contar clientes por dia de criação no período
            clients.forEach(client => {
                if (client.created_at) {
                    const createdDate = new Date(client.created_at);
                    
                    // Se o cliente foi criado no período
                    if (createdDate >= startDate && createdDate <= endDate) {
                        const daysDiff = Math.floor((createdDate - startDate) / (1000 * 60 * 60 * 24));
                        if (daysDiff >= 0 && daysDiff < diffDays) {
                            data[daysDiff] = (data[daysDiff] || 0) + 1;
                        }
                    }
                }
            });
        } else {
            // Fallback: dados zerados
            for (let i = 0; i < diffDays; i++) {
                data.push(0);
                const currentDate = new Date(startDate);
                currentDate.setDate(startDate.getDate() + i);
                labels.push(currentDate.getDate().toString().padStart(2, '0'));
            }
        }
    } catch (error) {
        // Fallback: dados zerados
        for (let i = 0; i < diffDays; i++) {
            data.push(0);
            const currentDate = new Date(startDate);
            currentDate.setDate(startDate.getDate() + i);
            labels.push(currentDate.getDate().toString().padStart(2, '0'));
        }
    }

    return { data, labels, startDate, endDate };
}

/**
 * Gerar dados de pagamentos baseado no período
 */
function generatePaymentsDataByPeriod(period) {
    const { startDate, endDate } = calculatePeriodDates(period);
    
    // Calcular número de dias no período
    const diffTime = Math.abs(endDate - startDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    
    const data = [];
    const labels = [];

    // Inicializar com zeros para todos os dias do período
    for (let i = 0; i < diffDays; i++) {
        data.push(0);
        const currentDate = new Date(startDate);
        currentDate.setDate(startDate.getDate() + i);
        labels.push(currentDate.getDate().toString().padStart(2, '0'));
    }

    // TODO: Buscar dados reais de pagamentos da API quando disponível

    return { data, labels, startDate, endDate };
}
