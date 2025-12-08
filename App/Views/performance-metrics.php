<?php
/**
 * View de Métricas de Performance
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-speedometer2 text-primary"></i>
                Métricas de Performance
            </h1>
            <p class="text-muted mb-0">Monitore e analise a performance da API</p>
        </div>
        <button class="btn btn-outline-primary" onclick="loadMetrics()" title="Atualizar">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Cards de Estatísticas Gerais -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Tempo Médio</p>
                            <h2 class="mb-0 fw-bold" id="avgDuration">-</h2>
                            <small class="text-muted">milissegundos</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-clock-history fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Memória Média</p>
                            <h2 class="mb-0 fw-bold" id="avgMemory">-</h2>
                            <small class="text-muted">megabytes</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-cpu fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Total de Requisições</p>
                            <h2 class="mb-0 fw-bold" id="totalRequests">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-activity fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Endpoints Lentos</p>
                            <h2 class="mb-0 fw-bold" id="slowEndpoints">-</h2>
                            <small class="text-muted">> 1000ms</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Endpoint</label>
                    <input type="text" class="form-control" id="endpointFilter" placeholder="Ex: /v1/customers">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Método</label>
                    <select class="form-select" id="methodFilter">
                        <option value="">Todos</option>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                        <option value="PATCH">PATCH</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="dateFromFilter">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="dateToFilter">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Visualização</label>
                    <select class="form-select" id="viewMode">
                        <option value="list">Lista</option>
                        <option value="aggregated" selected>Estatísticas</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadMetrics()">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo -->
    <div id="loadingMetrics" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>

    <!-- Lista de Métricas -->
    <div id="metricsList" style="display: none;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    Métricas de Performance
                </h5>
                <span class="badge bg-primary" id="metricsCountBadge">0</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Endpoint</th>
                                <th>Método</th>
                                <th>Requisições</th>
                                <th>Tempo Médio (ms)</th>
                                <th>Tempo Mín (ms)</th>
                                <th>Tempo Máx (ms)</th>
                                <th>Memória Média (MB)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="metricsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-speedometer2 fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma métrica encontrada</h5>
                    <p class="text-muted">As métricas de performance aparecerão aqui quando houver requisições.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas Agregadas -->
    <div id="aggregatedStats" style="display: none;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>
                    Estatísticas Agregadas
                </h5>
                <span class="badge bg-primary" id="statsCountBadge">0</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Endpoint</th>
                                <th>Método</th>
                                <th>Total Requisições</th>
                                <th>Tempo Médio (ms)</th>
                                <th>Tempo Mín (ms)</th>
                                <th>Tempo Máx (ms)</th>
                                <th>Memória Média (MB)</th>
                                <th>Memória Mín (MB)</th>
                                <th>Memória Máx (MB)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="statsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyStats" class="text-center py-5" style="display: none;">
                    <i class="bi bi-graph-up fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma estatística encontrada</h5>
                    <p class="text-muted">As estatísticas agregadas aparecerão aqui quando houver dados.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let metrics = [];
let stats = [];
let currentOffset = 0;
let currentLimit = 100;

document.addEventListener('DOMContentLoaded', () => {
    // Define data padrão (últimos 7 dias)
    const today = new Date();
    const weekAgo = new Date(today);
    weekAgo.setDate(today.getDate() - 7);
    
    document.getElementById('dateFromFilter').value = weekAgo.toISOString().split('T')[0];
    document.getElementById('dateToFilter').value = today.toISOString().split('T')[0];
    
    setTimeout(() => {
        loadMetrics();
    }, 100);
});

async function loadMetrics() {
    try {
        document.getElementById('loadingMetrics').style.display = 'block';
        document.getElementById('metricsList').style.display = 'none';
        document.getElementById('aggregatedStats').style.display = 'none';
        
        const params = new URLSearchParams();
        const endpoint = document.getElementById('endpointFilter')?.value;
        const method = document.getElementById('methodFilter')?.value;
        const dateFrom = document.getElementById('dateFromFilter')?.value;
        const dateTo = document.getElementById('dateToFilter')?.value;
        const viewMode = document.getElementById('viewMode')?.value || 'aggregated';
        
        if (endpoint) params.append('endpoint', endpoint);
        if (method) params.append('method', method);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        if (viewMode === 'aggregated') {
            params.append('aggregated', 'true');
            const url = '/v1/metrics/performance' + (params.toString() ? '?' + params.toString() : '');
            const response = await apiRequest(url);
            stats = response.data?.stats || [];
            renderAggregatedStats();
            calculateGeneralStats();
        } else {
            currentLimit = 100;
            currentOffset = 0;
            params.append('limit', currentLimit);
            params.append('offset', currentOffset);
            const url = '/v1/metrics/performance' + (params.toString() ? '?' + params.toString() : '');
            const response = await apiRequest(url);
            metrics = response.data?.metrics || [];
            renderMetrics();
        }
    } catch (error) {
        showAlert('Erro ao carregar métricas: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingMetrics').style.display = 'none';
        const viewMode = document.getElementById('viewMode')?.value || 'aggregated';
        if (viewMode === 'aggregated') {
            document.getElementById('aggregatedStats').style.display = 'block';
        } else {
            document.getElementById('metricsList').style.display = 'block';
        }
    }
}

function renderMetrics() {
    const tbody = document.getElementById('metricsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('metricsCountBadge');
    
    if (metrics.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(metrics.length);
    }
    
    tbody.innerHTML = metrics.map(metric => {
        const durationBadge = getDurationBadge(metric.duration_ms);
        return `
            <tr>
                <td>
                    <code class="text-muted small">${escapeHtml(metric.endpoint || '-')}</code>
                </td>
                <td>
                    <span class="badge bg-secondary">${escapeHtml(metric.method || '-')}</span>
                </td>
                <td>
                    <span class="badge bg-info">1</span>
                </td>
                <td>
                    <div class="fw-medium">${metric.duration_ms || 0} ms</div>
                </td>
                <td>
                    <small class="text-muted">${metric.duration_ms || 0} ms</small>
                </td>
                <td>
                    <div class="fw-bold">${metric.duration_ms || 0} ms</div>
                </td>
                <td>
                    <div>${metric.memory_mb || 0} MB</div>
                </td>
                <td>${durationBadge}</td>
            </tr>
        `;
    }).join('');
}

function renderAggregatedStats() {
    const tbody = document.getElementById('statsTableBody');
    const emptyStats = document.getElementById('emptyStats');
    const countBadge = document.getElementById('statsCountBadge');
    
    if (stats.length === 0) {
        tbody.innerHTML = '';
        if (emptyStats) emptyStats.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    if (emptyStats) emptyStats.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(stats.length);
    }
    
    tbody.innerHTML = stats.map(stat => {
        const avgDuration = parseFloat(stat.avg_duration_ms || 0).toFixed(2);
        const minDuration = stat.min_duration_ms || 0;
        const maxDuration = stat.max_duration_ms || 0;
        const avgMemory = parseFloat(stat.avg_memory_mb || 0).toFixed(2);
        const minMemory = parseFloat(stat.min_memory_mb || 0).toFixed(2);
        const maxMemory = parseFloat(stat.max_memory_mb || 0).toFixed(2);
        const durationBadge = getDurationBadge(avgDuration);
        
        return `
            <tr>
                <td>
                    <code class="text-muted small">${escapeHtml(stat.endpoint || '-')}</code>
                </td>
                <td>
                    <span class="badge bg-secondary">${escapeHtml(stat.method || '-')}</span>
                </td>
                <td>
                    <strong class="text-primary">${formatNumber(stat.total_requests || 0)}</strong>
                </td>
                <td>
                    <div class="fw-medium">${avgDuration} ms</div>
                </td>
                <td>
                    <small class="text-success">${minDuration} ms</small>
                </td>
                <td>
                    <div class="fw-bold text-danger">${maxDuration} ms</div>
                </td>
                <td>
                    <div>${avgMemory} MB</div>
                </td>
                <td>
                    <small class="text-success">${minMemory} MB</small>
                </td>
                <td>
                    <div class="fw-bold text-danger">${maxMemory} MB</div>
                </td>
                <td>${durationBadge}</td>
            </tr>
        `;
    }).join('');
}

function calculateGeneralStats() {
    if (stats.length === 0) {
        document.getElementById('avgDuration').textContent = '-';
        document.getElementById('avgMemory').textContent = '-';
        document.getElementById('totalRequests').textContent = '0';
        document.getElementById('slowEndpoints').textContent = '0';
        return;
    }
    
    let totalRequests = 0;
    let totalDuration = 0;
    let totalMemory = 0;
    let slowCount = 0;
    
    stats.forEach(stat => {
        totalRequests += parseInt(stat.total_requests || 0);
        totalDuration += parseFloat(stat.avg_duration_ms || 0) * parseInt(stat.total_requests || 0);
        totalMemory += parseFloat(stat.avg_memory_mb || 0) * parseInt(stat.total_requests || 0);
        
        if (parseFloat(stat.avg_duration_ms || 0) > 1000) {
            slowCount++;
        }
    });
    
    const avgDuration = totalRequests > 0 ? (totalDuration / totalRequests).toFixed(2) : 0;
    const avgMemory = totalRequests > 0 ? (totalMemory / totalRequests).toFixed(2) : 0;
    
    document.getElementById('avgDuration').textContent = avgDuration + ' ms';
    document.getElementById('avgMemory').textContent = avgMemory + ' MB';
    document.getElementById('totalRequests').textContent = formatNumber(totalRequests);
    document.getElementById('slowEndpoints').textContent = formatNumber(slowCount);
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

function getDurationBadge(duration) {
    const durationNum = parseFloat(duration);
    if (durationNum < 100) {
        return '<span class="badge bg-success">Rápido</span>';
    } else if (durationNum < 500) {
        return '<span class="badge bg-info">Normal</span>';
    } else if (durationNum < 1000) {
        return '<span class="badge bg-warning">Lento</span>';
    } else {
        return '<span class="badge bg-danger">Muito Lento</span>';
    }
}
</script>

