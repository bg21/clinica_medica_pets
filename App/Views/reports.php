<?php
/**
 * View de Relatórios e Estatísticas
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-graph-up text-primary"></i>
                Relatórios e Estatísticas
            </h1>
            <p class="text-muted mb-0">Análise completa do seu negócio</p>
        </div>
        <button class="btn btn-outline-primary" onclick="loadStats()" title="Atualizar">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Filtros de Período -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Período</label>
                    <select class="form-select" id="periodFilter" onchange="loadStats()">
                        <option value="today">Hoje</option>
                        <option value="week">Esta Semana</option>
                        <option value="month" selected>Este Mês</option>
                        <option value="year">Este Ano</option>
                        <option value="all">Todos</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Total de Clientes</p>
                            <h2 class="mb-0 fw-bold" id="totalCustomers">-</h2>
                            <small class="text-success" id="newCustomers">+0 novos</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Assinaturas Ativas</p>
                            <h2 class="mb-0 fw-bold" id="totalSubscriptions">-</h2>
                            <small class="text-success" id="newSubscriptions">+0 novas</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-credit-card fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Receita Total</p>
                            <h2 class="mb-0 fw-bold" id="totalRevenue">-</h2>
                            <small class="text-muted" id="periodRevenue">R$ 0,00 no período</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-cash-coin fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">MRR</p>
                            <h2 class="mb-0 fw-bold" id="mrr">-</h2>
                            <small class="text-muted">Receita Recorrente Mensal</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-graph-up-arrow fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Tabelas -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Assinaturas por Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="subscriptionsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Receita por Período</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Top Clientes -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Top Clientes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Assinaturas</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody id="topCustomersTable">
                        <tr>
                            <td colspan="4" class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let subscriptionsChart = null;
let revenueChart = null;

document.addEventListener('DOMContentLoaded', () => {
    // Carrega dados após um pequeno delay para não bloquear a renderização
    setTimeout(() => {
        loadStats();
        loadTopCustomers();
    }, 100);
});

async function loadStats() {
    try {
        const period = document.getElementById('periodFilter').value;
        const response = await apiRequest(`/v1/stats?period=${period}`);
        const stats = response.data || {};
        
        // Atualiza cards
        const totalCustomers = stats.customers?.total || 0;
        const newCustomers = stats.customers?.new || 0;
        document.getElementById('totalCustomers').textContent = formatNumber(totalCustomers);
        document.getElementById('newCustomers').textContent = `+${formatNumber(newCustomers)} novos`;
        
        const activeSubscriptions = stats.subscriptions?.active || 0;
        const newSubscriptions = stats.subscriptions?.new || 0;
        document.getElementById('totalSubscriptions').textContent = formatNumber(activeSubscriptions);
        document.getElementById('newSubscriptions').textContent = `+${formatNumber(newSubscriptions)} novas`;
        
        const revenue = stats.revenue?.total || 0;
        document.getElementById('totalRevenue').textContent = formatCurrency(revenue, stats.revenue?.currency || 'BRL');
        document.getElementById('periodRevenue').textContent = formatCurrency(stats.revenue?.period || 0, stats.revenue?.currency || 'BRL') + ' no período';
        
        document.getElementById('mrr').textContent = formatCurrency(stats.mrr || 0, stats.revenue?.currency || 'BRL');
        
        // Atualiza gráficos
        updateCharts(stats);
    } catch (error) {
        showAlert('Erro ao carregar estatísticas: ' + error.message, 'danger');
    }
}

function updateCharts(stats) {
    // Gráfico de Assinaturas por Status
    const subscriptionsCtx = document.getElementById('subscriptionsChart');
    if (subscriptionsChart) {
        subscriptionsChart.destroy();
    }
    
    subscriptionsChart = new Chart(subscriptionsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Ativas', 'Canceladas', 'Em Trial', 'Vencidas'],
            datasets: [{
                data: [
                    stats.subscriptions?.active || 0,
                    stats.subscriptions?.canceled || 0,
                    stats.subscriptions?.trialing || 0,
                    stats.subscriptions?.past_due || 0
                ],
                backgroundColor: [
                    '#28a745',
                    '#dc3545',
                    '#17a2b8',
                    '#ffc107'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true
        }
    });
    
    // Gráfico de Receita (simplificado - você pode melhorar com dados históricos)
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueChart) {
        revenueChart.destroy();
    }
    
    revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['Receita Total', 'MRR', 'Receita do Período'],
            datasets: [{
                label: 'Valores',
                data: [
                    (stats.revenue?.total || 0) / 100,
                    (stats.mrr || 0) / 100,
                    (stats.revenue?.period || 0) / 100
                ],
                backgroundColor: ['#007bff', '#28a745', '#ffc107']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

async function loadTopCustomers() {
    try {
        const customersResponse = await apiRequest('/v1/customers');
        const customers = customersResponse.data || [];
        
        // Ordena por número de assinaturas
        const topCustomers = customers
            .sort((a, b) => (b.subscriptions_count || 0) - (a.subscriptions_count || 0))
            .slice(0, 10);
        
        const tbody = document.getElementById('topCustomersTable');
        if (topCustomers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Nenhum cliente encontrado</td></tr>';
            return;
        }
        
        tbody.innerHTML = topCustomers.map(customer => `
            <tr>
                <td>
                    <div class="fw-medium">${escapeHtml(customer.name || 'Sem nome')}</div>
                </td>
                <td>
                    <div>${escapeHtml(customer.email || '-')}</div>
                </td>
                <td>
                    <span class="badge bg-info">${customer.subscriptions_count || 0}</span>
                </td>
                <td>
                    <div class="fw-bold text-success">-</div>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        document.getElementById('topCustomersTable').innerHTML = 
            '<tr><td colspan="4" class="text-center text-danger">Erro ao carregar clientees</td></tr>';
    }
}

// Função auxiliar para escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num);
}
</script>

