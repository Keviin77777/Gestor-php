/**
 * Gráficos do Dashboard com Chart.js
 */

let clientsChartInstance = null;
let paymentsChartInstance = null;

/**
 * Desenhar gráfico de clientes com Chart.js
 */
function drawClientsChartJS(chartData) {
    const canvas = document.getElementById('clientsChart');
    if (!canvas) return;

    const { data, labels } = chartData;
    
    // Destruir gráfico anterior se existir
    if (clientsChartInstance) {
        clientsChartInstance.destroy();
    }

    // Obter mês e ano atual
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                       'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    
    // Calcular total de clientes no período
    const totalClients = data.reduce((sum, val) => sum + val, 0);

    // Configuração do gráfico
    const config = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Clientes',
                data: data,
                borderColor: '#34d399',
                backgroundColor: function(context) {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                    gradient.addColorStop(0, 'rgba(52, 211, 153, 0.3)');
                    gradient.addColorStop(1, 'rgba(52, 211, 153, 0.0)');
                    return gradient;
                },
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#34d399',
                pointHoverBorderColor: '#1e293b',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(30, 41, 59, 0.95)',
                    titleColor: '#cbd5e1',
                    bodyColor: '#34d399',
                    borderColor: '#334155',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    titleFont: {
                        size: 13,
                        weight: 'normal'
                    },
                    bodyFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    callbacks: {
                        title: function(context) {
                            const day = context[0].label;
                            return `${day}/${(currentMonth + 1).toString().padStart(2, '0')}/${currentYear}`;
                        },
                        label: function(context) {
                            const value = context.parsed.y;
                            const percentage = totalClients > 0 ? ((value / totalClients) * 100).toFixed(1) : 0;
                            return `• Clientes: ${value} | ${percentage}%`;
                        },
                        afterLabel: function(context) {
                            return `• Clientes no período: ${totalClients}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: true,
                        color: 'rgba(51, 65, 85, 0.3)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 9
                        },
                        maxRotation: 0,
                        autoSkip: false,
                        callback: function(value, index) {
                            // Mostrar apenas alguns labels para não ficar muito poluído
                            const totalDays = this.chart.data.labels.length;
                            const day = parseInt(this.chart.data.labels[index]);
                            
                            if (totalDays > 20) {
                                // Para meses completos, mostrar dias estratégicos
                                if (day === 1 || day % 3 === 1 || day === totalDays) {
                                    return day;
                                }
                                return '';
                            }
                            // Para períodos menores, mostrar todos
                            return day;
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(51, 65, 85, 0.3)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 10
                        },
                        precision: 0
                    }
                }
            }
        }
    };

    clientsChartInstance = new Chart(canvas, config);
}

/**
 * Desenhar gráfico de pagamentos com Chart.js
 */
function drawPaymentsChartJS(chartData) {
    const canvas = document.getElementById('paymentsChart');
    if (!canvas) return;

    const { data, labels } = chartData;
    
    // Destruir gráfico anterior se existir
    if (paymentsChartInstance) {
        paymentsChartInstance.destroy();
    }

    // Obter mês e ano atual
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    
    // Calcular total de pagamentos no período
    const totalPayments = data.reduce((sum, val) => sum + val, 0);

    // Configuração do gráfico
    const config = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pagamentos',
                data: data,
                borderColor: '#fbbf24',
                backgroundColor: function(context) {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                    gradient.addColorStop(0, 'rgba(251, 191, 36, 0.3)');
                    gradient.addColorStop(1, 'rgba(251, 191, 36, 0.0)');
                    return gradient;
                },
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#fbbf24',
                pointHoverBorderColor: '#1e293b',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(30, 41, 59, 0.95)',
                    titleColor: '#cbd5e1',
                    bodyColor: '#fbbf24',
                    borderColor: '#334155',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    titleFont: {
                        size: 13,
                        weight: 'normal'
                    },
                    bodyFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    callbacks: {
                        title: function(context) {
                            const day = context[0].label;
                            return `${day}/${(currentMonth + 1).toString().padStart(2, '0')}/${currentYear}`;
                        },
                        label: function(context) {
                            const value = context.parsed.y;
                            const percentage = totalPayments > 0 ? ((value / totalPayments) * 100).toFixed(1) : 0;
                            return `• Pagamentos: R$ ${value.toFixed(2)} | ${percentage}%`;
                        },
                        afterLabel: function(context) {
                            return `• Total no período: R$ ${totalPayments.toFixed(2)}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: true,
                        color: 'rgba(51, 65, 85, 0.3)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 9
                        },
                        maxRotation: 0,
                        autoSkip: false,
                        callback: function(value, index) {
                            // Mostrar apenas alguns labels para não ficar muito poluído
                            const totalDays = this.chart.data.labels.length;
                            const day = parseInt(this.chart.data.labels[index]);
                            
                            if (totalDays > 20) {
                                // Para meses completos, mostrar dias estratégicos
                                if (day === 1 || day % 3 === 1 || day === totalDays) {
                                    return day;
                                }
                                return '';
                            }
                            // Para períodos menores, mostrar todos
                            return day;
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(51, 65, 85, 0.3)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 10
                        },
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    };

    paymentsChartInstance = new Chart(canvas, config);
}
