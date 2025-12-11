<?php
/**
 * View de Dashboard de Métricas Stripe
 * 
 * Exibe:
 * - MRR, ARR, Churn Rate, Conversion Rate
 * - Alertas de falhas de pagamento
 * - Alertas de disputas/chargebacks
 * - Alertas de webhooks falhando
 * - Alertas de assinaturas canceladas
 * - Métricas de performance
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-graph-up-arrow text-primary"></i>
                Dashboard de Métricas Stripe
            </h1>
            <p class="text-muted mb-0">Monitore receita, churn, conversão e alertas do Stripe</p>
        </div>
        <button class="btn btn-outline-primary" onclick="loadMetrics()" title="Atualizar">
            <i class="bi bi-arrow-clockwise"></i> Atualizar
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Período (horas)</label>
                    <select class="form-select" id="hoursFilter" onchange="loadMetrics()">
                        <option value="1">Última hora</option>
                        <option value="6">Últimas 6 horas</option>
                        <option value="24" selected>Últimas 24 horas</option>
                        <option value="48">Últimas 48 horas</option>
                        <option value="168">Última semana</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Alerta</label>
                    <select class="form-select" id="alertTypeFilter" onchange="loadAlerts()">
                        <option value="">Todos</option>
                        <option value="failed_payments">Falhas de Pagamento</option>
                        <option value="disputes">Disputas</option>
                        <option value="webhook_failures">Webhooks Falhando</option>
                        <option value="canceled_subscriptions">Assinaturas Canceladas</option>
                        <option value="performance">Performance</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Métricas Principais -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">MRR</p>
                            <h2 class="mb-0 fw-bold" id="mrrValue">-</h2>
                            <small class="text-muted" id="mrrSubscriptions">0 assinaturas</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-graph-up-arrow fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">ARR</p>
                            <h2 class="mb-0 fw-bold" id="arrValue">-</h2>
                            <small class="text-muted">Receita Recorrente Anual</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-calendar-year fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Churn Rate</p>
                            <h2 class="mb-0 fw-bold" id="churnRate">-</h2>
                            <small class="text-muted" id="churnDetails">0% de churn</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-graph-down-arrow fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Conversion Rate</p>
                            <h2 class="mb-0 fw-bold" id="conversionRate">-</h2>
                            <small class="text-muted" id="conversionDetails">0% de conversão</small>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-arrow-up-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-bell text-warning"></i>
                        Alertas do Stripe
                    </h5>
                    <div>
                        <span class="badge bg-danger" id="criticalAlertsBadge">0 críticos</span>
                        <span class="badge bg-warning" id="warningAlertsBadge">0 avisos</span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="alertsLoading" class="text-center py-3">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                    <div id="alertsList" style="display: none;">
                        <!-- Alertas serão inseridos aqui via JavaScript -->
                    </div>
                    <div id="noAlerts" style="display: none;" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle fs-1"></i>
                        <p class="mt-2">Nenhum alerta no momento</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas Detalhadas -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">MRR por Plano</h5>
                </div>
                <div class="card-body">
                    <div id="mrrByPlanLoading" class="text-center py-3">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                    <div id="mrrByPlanList" style="display: none;">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Plano</th>
                                    <th>Assinaturas</th>
                                    <th>MRR</th>
                                </tr>
                            </thead>
                            <tbody id="mrrByPlanTableBody">
                                <!-- Dados serão inseridos via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Estatísticas de Churn</h5>
                </div>
                <div class="card-body">
                    <div id="churnStatsLoading" class="text-center py-3">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                    <div id="churnStatsContent" style="display: none;">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <p class="text-muted mb-1 small">Taxa de Retenção</p>
                                    <h3 class="mb-0 fw-bold text-success" id="retentionRate">-</h3>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <p class="text-muted mb-1 small">Ativas no Início</p>
                                    <h3 class="mb-0 fw-bold" id="activeStart">-</h3>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <p class="text-muted mb-1 small">Canceladas</p>
                                    <h3 class="mb-0 fw-bold text-danger" id="canceledCount">-</h3>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <p class="text-muted mb-1 small">Período</p>
                                    <h6 class="mb-0 text-muted" id="churnPeriod">-</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas de Performance -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-speedometer2"></i>
                        Métricas de Performance do Stripe
                    </h5>
                </div>
                <div class="card-body">
                    <div id="performanceMetricsLoading" class="text-center py-3">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                    <div id="performanceMetricsList" style="display: none;">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Endpoint</th>
                                    <th>Método</th>
                                    <th>Tempo Médio (ms)</th>
                                    <th>Total de Requisições</th>
                                    <th>Requisições Lentas</th>
                                    <th>Taxa de Erro</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="performanceMetricsTableBody">
                                <!-- Dados serão inseridos via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div id="noPerformanceMetrics" style="display: none;" class="text-center text-muted py-4">
                        <i class="bi bi-info-circle fs-1"></i>
                        <p class="mt-2">Nenhuma métrica de performance disponível</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ✅ Garante que as variáveis globais estejam disponíveis
if (typeof API_URL === 'undefined') {
    window.API_URL = '';
}

let metricsData = {};
let alertsData = {};

// Carrega todas as métricas
async function loadMetrics() {
    try {
        showLoading();
        
        const hours = document.getElementById('hoursFilter').value;
        // ✅ CORREÇÃO: Verifica session_id ou saas_admin_session_id
        const sessionId = localStorage.getItem('session_id') || localStorage.getItem('saas_admin_session_id');
        
        if (!sessionId || sessionId.trim() === '') {
            throw new Error('Sessão não encontrada. Por favor, faça login novamente.');
        }
        
        // Usa apiRequest se disponível, senão faz fetch direto
        let metricsResult;
        
        if (typeof apiRequest === 'function') {
            metricsResult = await apiRequest('/v1/stripe-metrics?hours=' + hours);
        } else {
            // Fallback: fetch direto
            const apiUrl = (typeof API_URL !== 'undefined' && API_URL) ? API_URL : '';
            const url = apiUrl + '/v1/stripe-metrics?hours=' + hours;
            
            // Constrói header Authorization
            const authHeader = 'Bearer ' + sessionId.trim();
            
            console.log('Stripe Metrics: Fazendo requisição...', { 
                url, 
                hasSessionId: !!sessionId,
                sessionIdLength: sessionId.length,
                authHeaderLength: authHeader.length,
                authHeaderPrefix: authHeader.substring(0, 20) + '...'
            });
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': authHeader,
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                cache: 'no-cache'
            });
            
            console.log('Stripe Metrics: Resposta recebida', { 
                status: response.status, 
                ok: response.ok,
                statusText: response.statusText
            });
            
            const responseData = await response.json();
            
            if (!response.ok) {
                console.error('Stripe Metrics: Erro na resposta', responseData);
                throw new Error(responseData.error || responseData.message || 'Erro ao carregar métricas');
            }
            
            metricsResult = responseData;
        }
        
        if (metricsResult.success && metricsResult.data) {
            metricsData = metricsResult.data;
            renderMetrics(metricsData.metrics);
        }
        
        // Carrega alertas
        await loadAlerts();
        
    } catch (error) {
        console.error('Erro ao carregar métricas:', error);
        showAlert('Erro ao carregar métricas: ' + (error.message || 'Tente novamente.'), 'danger');
    } finally {
        hideLoading();
    }
}

// Carrega alertas
async function loadAlerts() {
    try {
        document.getElementById('alertsLoading').style.display = 'block';
        document.getElementById('alertsList').style.display = 'none';
        document.getElementById('noAlerts').style.display = 'none';
        
        const hours = document.getElementById('hoursFilter').value;
        const type = document.getElementById('alertTypeFilter').value;
        // ✅ CORREÇÃO: Verifica session_id ou saas_admin_session_id
        const sessionId = localStorage.getItem('session_id') || localStorage.getItem('saas_admin_session_id');
        
        if (!sessionId || sessionId.trim() === '') {
            throw new Error('Sessão não encontrada');
        }
        
        let url = '/v1/stripe-metrics/alerts?hours=' + hours;
        if (type) {
            url += '&type=' + type;
        }
        
        // Usa apiRequest se disponível, senão faz fetch direto
        let result;
        
        if (typeof apiRequest === 'function') {
            result = await apiRequest(url);
        } else {
            // Fallback: fetch direto
            const apiUrl = (typeof API_URL !== 'undefined' && API_URL) ? API_URL : '';
            const fullUrl = apiUrl + url;
            const authHeader = 'Bearer ' + sessionId.trim();
            
            const response = await fetch(fullUrl, {
                method: 'GET',
                headers: {
                    'Authorization': authHeader,
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                cache: 'no-cache'
            });
            
            const responseData = await response.json();
            
            if (!response.ok) {
                console.error('Stripe Metrics: Erro ao carregar alertas', responseData);
                throw new Error(responseData.error || responseData.message || 'Erro ao carregar alertas');
            }
            
            result = responseData;
        }
        
        if (result.success && result.data) {
            alertsData = result.data;
            renderAlerts(alertsData);
        }
        
    } catch (error) {
        console.error('Erro ao carregar alertas:', error);
    } finally {
        document.getElementById('alertsLoading').style.display = 'none';
    }
}

// Renderiza métricas principais
function renderMetrics(metrics) {
    if (!metrics) return;
    
    // MRR
    if (metrics.mrr) {
        document.getElementById('mrrValue').textContent = formatCurrency(metrics.mrr.mrr || 0, metrics.mrr.currency || 'BRL');
        document.getElementById('mrrSubscriptions').textContent = (metrics.mrr.total_subscriptions || 0) + ' assinaturas';
        renderMRRByPlan(metrics.mrr.by_plan || []);
    }
    
    // ARR
    if (metrics.arr) {
        document.getElementById('arrValue').textContent = formatCurrency(metrics.arr.arr || 0, metrics.arr.currency || 'BRL');
    }
    
    // Churn
    if (metrics.churn) {
        document.getElementById('churnRate').textContent = (metrics.churn.churn_rate || 0) + '%';
        document.getElementById('churnDetails').textContent = 
            (metrics.churn.retention_rate || 0) + '% de retenção';
        renderChurnStats(metrics.churn);
    }
    
    // Conversion
    if (metrics.conversion) {
        document.getElementById('conversionRate').textContent = (metrics.conversion.conversion_rate || 0) + '%';
        document.getElementById('conversionDetails').textContent = 
            (metrics.conversion.total_subscriptions || 0) + ' de ' + (metrics.conversion.total_customers || 0) + ' clientes';
    }
}

// Renderiza MRR por plano
function renderMRRByPlan(plans) {
    const tbody = document.getElementById('mrrByPlanTableBody');
    tbody.innerHTML = '';
    
    if (plans.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Nenhum plano encontrado</td></tr>';
        return;
    }
    
    plans.forEach(plan => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><code>${plan.plan_id || 'N/A'}</code></td>
            <td>${plan.subscriptions || 0}</td>
            <td class="fw-bold">${formatCurrency(plan.mrr || 0, 'BRL')}</td>
        `;
        tbody.appendChild(row);
    });
    
    document.getElementById('mrrByPlanLoading').style.display = 'none';
    document.getElementById('mrrByPlanList').style.display = 'block';
}

// Renderiza estatísticas de churn
function renderChurnStats(churn) {
    document.getElementById('retentionRate').textContent = (churn.retention_rate || 0) + '%';
    document.getElementById('activeStart').textContent = churn.active_start || 0;
    document.getElementById('canceledCount').textContent = churn.canceled || 0;
    
    if (churn.period && churn.period.start && churn.period.end) {
        const start = new Date(churn.period.start).toLocaleDateString('pt-BR');
        const end = new Date(churn.period.end).toLocaleDateString('pt-BR');
        document.getElementById('churnPeriod').textContent = `${start} - ${end}`;
    }
    
    document.getElementById('churnStatsLoading').style.display = 'none';
    document.getElementById('churnStatsContent').style.display = 'block';
}

// Renderiza alertas
function renderAlerts(alerts) {
    const container = document.getElementById('alertsList');
    container.innerHTML = '';
    
    // Calcula totais
    let totalAlerts = 0;
    let criticalAlerts = 0;
    let warningAlerts = 0;
    
    const alertTypes = ['failed_payments', 'disputes', 'webhook_failures', 'canceled_subscriptions', 'performance'];
    
    alertTypes.forEach(type => {
        if (alerts[type] && Array.isArray(alerts[type])) {
            alerts[type].forEach(alert => {
                totalAlerts++;
                if (alert.severity === 'critical') {
                    criticalAlerts++;
                } else {
                    warningAlerts++;
                }
            });
        }
    });
    
    // Atualiza badges
    document.getElementById('criticalAlertsBadge').textContent = criticalAlerts + ' críticos';
    document.getElementById('warningAlertsBadge').textContent = warningAlerts + ' avisos';
    
    if (totalAlerts === 0) {
        document.getElementById('noAlerts').style.display = 'block';
        return;
    }
    
    // Renderiza alertas por tipo
    alertTypes.forEach(type => {
        if (alerts[type] && Array.isArray(alerts[type]) && alerts[type].length > 0) {
            const typeTitle = {
                'failed_payments': 'Falhas de Pagamento',
                'disputes': 'Disputas/Chargebacks',
                'webhook_failures': 'Webhooks Falhando',
                'canceled_subscriptions': 'Assinaturas Canceladas',
                'performance': 'Performance'
            };
            
            const section = document.createElement('div');
            section.className = 'mb-3';
            section.innerHTML = `<h6 class="text-muted mb-2">${typeTitle[type] || type}</h6>`;
            
            alerts[type].forEach(alert => {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${alert.severity === 'critical' ? 'danger' : 'warning'} alert-dismissible fade show`;
                alertDiv.innerHTML = `
                    <strong>${alert.message || 'Alerta'}</strong>
                    ${alert.detected_at ? '<br><small class="text-muted">Detectado em: ' + new Date(alert.detected_at).toLocaleString('pt-BR') + '</small>' : ''}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                section.appendChild(alertDiv);
            });
            
            container.appendChild(section);
        }
    });
    
    // Renderiza métricas de performance
    if (alerts.performance && Array.isArray(alerts.performance) && alerts.performance.length > 0) {
        renderPerformanceMetrics(alerts.performance);
    }
    
    document.getElementById('alertsList').style.display = 'block';
}

// Renderiza métricas de performance
function renderPerformanceMetrics(metrics) {
    const tbody = document.getElementById('performanceMetricsTableBody');
    tbody.innerHTML = '';
    
    if (metrics.length === 0) {
        document.getElementById('noPerformanceMetrics').style.display = 'block';
        return;
    }
    
    metrics.forEach(metric => {
        const row = document.createElement('tr');
        const statusBadge = metric.severity === 'critical' 
            ? '<span class="badge bg-danger">Crítico</span>'
            : '<span class="badge bg-warning">Atenção</span>';
        
        row.innerHTML = `
            <td><code>${metric.endpoint || 'N/A'}</code></td>
            <td><span class="badge bg-secondary">${metric.method || 'N/A'}</span></td>
            <td class="fw-bold ${metric.avg_duration_ms > 2000 ? 'text-danger' : 'text-warning'}">${metric.avg_duration_ms || 0}ms</td>
            <td>${metric.total_requests || 0}</td>
            <td>${metric.slow_requests || 0}</td>
            <td>${metric.error_rate || 0}%</td>
            <td>${statusBadge}</td>
        `;
        tbody.appendChild(row);
    });
    
    document.getElementById('performanceMetricsLoading').style.display = 'none';
    document.getElementById('performanceMetricsList').style.display = 'block';
}

// Funções auxiliares
function formatCurrency(value, currency = 'BRL') {
    // Se formatCurrency já estiver definido globalmente (dashboard.js), usa ele
    if (typeof window.formatCurrency === 'function') {
        return window.formatCurrency(value, currency);
    }
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: currency
    }).format(value);
}

function showLoading() {
    // Mostra indicadores de loading
}

function hideLoading() {
    // Esconde indicadores de loading
}

function showAlert(message, type = 'info') {
    const container = document.getElementById('alertContainer');
    container.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}

// Carrega métricas ao carregar a página
// Usa window.onload para garantir que todos os scripts carregaram
window.addEventListener('load', function() {
    // Aguarda um pouco para garantir que todos os scripts carregaram
    setTimeout(() => {
        // ✅ CORREÇÃO: Verifica session_id ou saas_admin_session_id
        const sessionId = localStorage.getItem('session_id') || localStorage.getItem('saas_admin_session_id');
        const isSaasAdmin = !!localStorage.getItem('saas_admin_session_id');
        
        console.log('Stripe Metrics: Verificando sessão...', { 
            hasSessionId: !!sessionId,
            isSaasAdmin: isSaasAdmin,
            sessionIdLength: sessionId ? sessionId.length : 0,
            sessionIdPrefix: sessionId ? sessionId.substring(0, 20) + '...' : null,
            hasApiRequest: typeof apiRequest === 'function',
            hasApiUrl: typeof API_URL !== 'undefined',
            apiUrl: typeof API_URL !== 'undefined' ? API_URL : 'undefined'
        });
        
        if (!sessionId || sessionId.trim() === '') {
            console.error('SESSION_ID não encontrado no localStorage');
            showAlert('Sessão não encontrada. Por favor, faça login novamente.', 'danger');
            setTimeout(() => {
                // ✅ CORREÇÃO: Redireciona para login correto baseado no tipo de usuário
                const loginUrl = isSaasAdmin ? '/saas-admin/login' : '/login';
                window.location.href = loginUrl;
            }, 2000);
            return;
        }
        
        // Testa se o header está sendo enviado corretamente
        // Faz um teste rápido antes de carregar as métricas
        const testUrl = (typeof API_URL !== 'undefined' && API_URL) ? API_URL : '';
        // ✅ CORREÇÃO: Para SaaS admins, não precisa testar /v1/auth/me (pode não existir)
        // Vai direto para carregar métricas
        if (isSaasAdmin) {
            return loadMetrics();
        }
        
        const testEndpoint = testUrl + '/v1/auth/me';
        const testAuthHeader = 'Bearer ' + sessionId.trim();
        
        console.log('Stripe Metrics: Testando autenticação...', {
            testUrl: testEndpoint,
            authHeaderLength: testAuthHeader.length,
            authHeaderPrefix: testAuthHeader.substring(0, 30) + '...'
        });
        
        // Testa autenticação primeiro
        fetch(testEndpoint, {
            method: 'GET',
            headers: {
                'Authorization': testAuthHeader,
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            cache: 'no-cache'
        })
        .then(response => {
            console.log('Stripe Metrics: Teste de autenticação', {
                status: response.status,
                ok: response.ok,
                statusText: response.statusText
            });
            
            if (!response.ok) {
                return response.json().then(errData => {
                    console.error('Stripe Metrics: Erro no teste de autenticação', errData);
                    throw new Error(errData.error || 'Erro de autenticação');
                });
            }
            
            // Se autenticação OK, carrega métricas
            return loadMetrics();
        })
        .catch(error => {
            console.error('Stripe Metrics: Erro ao testar autenticação', error);
            showAlert('Erro de autenticação: ' + (error.message || 'Tente novamente.'), 'danger');
        });
        
        // Auto-refresh a cada 5 minutos
        setInterval(() => {
            // ✅ CORREÇÃO: Verifica session_id ou saas_admin_session_id
            const currentSessionId = localStorage.getItem('session_id') || localStorage.getItem('saas_admin_session_id');
            if (currentSessionId && currentSessionId.trim() !== '') {
                loadMetrics();
            }
        }, 5 * 60 * 1000);
    }, 500); // Aumenta delay para garantir que tudo carregou
});
</script>

<style>
.kpi-card {
    border-left: 4px solid;
    transition: transform 0.2s;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.kpi-card-primary {
    border-left-color: #0d6efd;
}

.kpi-card-success {
    border-left-color: #198754;
}

.kpi-card-warning {
    border-left-color: #ffc107;
}

.kpi-card-info {
    border-left-color: #0dcaf0;
}

.kpi-icon {
    opacity: 0.3;
}
</style>

