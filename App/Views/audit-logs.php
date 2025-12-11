<?php
/**
 * View de Logs de Auditoria
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-journal-text text-primary"></i>
                Logs de Auditoria
            </h1>
            <p class="text-muted mb-0">Histórico completo de ações do sistema</p>
        </div>
        <button class="btn btn-outline-primary" onclick="loadAuditLogs()" title="Atualizar">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Cards de Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Total de Logs</p>
                            <h2 class="mb-0 fw-bold" id="totalLogsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-journal-text fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Criações</p>
                            <h2 class="mb-0 fw-bold" id="createLogsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-plus-circle fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Atualizações</p>
                            <h2 class="mb-0 fw-bold" id="updateLogsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-pencil-square fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Exclusões</p>
                            <h2 class="mb-0 fw-bold" id="deleteLogsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-trash fs-1"></i>
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
                    <label class="form-label">Ação</label>
                    <input type="text" class="form-control" id="actionFilter" placeholder="Ex: create, update, delete">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="userFilter" placeholder="ID do usuário">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Limite</label>
                    <select class="form-select" id="limitFilter">
                        <option value="50">50</option>
                        <option value="100" selected>100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Offset</label>
                    <input type="number" class="form-control" id="offsetFilter" value="0" min="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadAuditLogs()">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Logs -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Logs de Auditoria
            </h5>
            <span class="badge bg-primary" id="logsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingLogs" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando logs...</p>
            </div>
            <div id="logsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>Recurso</th>
                                <th>IP</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-journal-text fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum log encontrado</h5>
                    <p class="text-muted">Os logs de auditoria aparecerão aqui quando houver ações no sistema.</p>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <button class="btn btn-outline-secondary" id="prevPage" onclick="previousPage()" disabled>
                        <i class="bi bi-arrow-left"></i> Anterior
                    </button>
                    <span id="pageInfo" class="text-muted"></span>
                    <button class="btn btn-outline-secondary" id="nextPage" onclick="nextPage()" disabled>
                        Próxima <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes do Log -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ✅ Helper para mostrar alertas (caso não esteja disponível no dashboard.js)
if (typeof showAlert === 'undefined') {
    function showAlert(message, type = 'info', containerId = 'alertContainer') {
        const container = document.getElementById(containerId);
        if (!container) {
            console.warn('Container de alertas não encontrado:', containerId);
            return;
        }
        
        const iconMap = {
            'danger': 'exclamation-triangle',
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            <i class="bi bi-${iconMap[type] || 'info-circle'}-fill"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

// ✅ Helper para formatar data (caso não esteja disponível no dashboard.js)
// ✅ CORREÇÃO: Protege contra redeclaração de dateFormatter
if (typeof formatDate === 'undefined') {
    // Usa dateFormatter global se existir, senão cria local
    let dateFormatter;
    if (typeof window.dateFormatter !== 'undefined') {
        dateFormatter = window.dateFormatter;
    } else {
        dateFormatter = new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        // Armazena globalmente para reutilização
        window.dateFormatter = dateFormatter;
    }
    
    function formatDate(timestamp) {
        if (!timestamp) return '-';
        try {
            // Se for string de data MySQL (YYYY-MM-DD HH:MM:SS), converte
            if (typeof timestamp === 'string' && timestamp.match(/^\d{4}-\d{2}-\d{2}/)) {
                const date = new Date(timestamp);
                if (isNaN(date.getTime())) return timestamp; // Retorna original se inválido
                return dateFormatter.format(date);
            }
            // Se for timestamp Unix (número)
            if (typeof timestamp === 'number') {
                const date = new Date(timestamp * 1000);
                return dateFormatter.format(date);
            }
            // Tenta converter diretamente
            const date = new Date(timestamp);
            if (isNaN(date.getTime())) return timestamp; // Retorna original se inválido
            return dateFormatter.format(date);
        } catch (e) {
            console.warn('Erro ao formatar data:', timestamp, e);
            return timestamp || '-';
        }
    }
}

let logs = [];
let currentOffset = 0;
let currentLimit = 100;
let pagination = null; // ✅ Armazena informações de paginação da API

document.addEventListener('DOMContentLoaded', () => {
    // Carrega dados após um pequeno delay para não bloquear a renderização
    setTimeout(() => {
        loadAuditLogs();
    }, 100);
});

async function loadAuditLogs() {
    try {
        document.getElementById('loadingLogs').style.display = 'block';
        document.getElementById('logsList').style.display = 'none';
        
        const params = new URLSearchParams();
        const action = document.getElementById('actionFilter')?.value;
        const userId = document.getElementById('userFilter')?.value;
        currentLimit = parseInt(document.getElementById('limitFilter')?.value || 100);
        currentOffset = parseInt(document.getElementById('offsetFilter')?.value || 0);
        
        if (action) params.append('action', action);
        if (userId) params.append('user_id', userId);
        params.append('limit', currentLimit);
        params.append('offset', currentOffset);
        
        const url = '/v1/audit-logs' + (params.toString() ? '?' + params.toString() : '');
        const response = await apiRequest(url);
        
        // ✅ CORREÇÃO: A resposta tem estrutura { success: true, data: { logs: [...], pagination: {...} } }
        // O apiRequest retorna o JSON parseado completo, então response.data contém { logs: [...], pagination: {...} }
        if (response && response.data) {
            // Extrai logs
            if (response.data.logs && Array.isArray(response.data.logs)) {
                logs = response.data.logs;
            } else if (Array.isArray(response.data)) {
                // Fallback: se data for diretamente um array
                logs = response.data;
            } else {
                logs = [];
            }
            
            // Extrai paginação
            if (response.data.pagination) {
                pagination = response.data.pagination;
            }
        } else if (response && response.logs && Array.isArray(response.logs)) {
            // Fallback: se a estrutura for diferente (logs diretamente em response)
            logs = response.logs;
            if (response.pagination) {
                pagination = response.pagination;
            }
        } else if (Array.isArray(response)) {
            // Fallback: se a resposta for diretamente um array
            logs = response;
            pagination = null;
        } else {
            logs = [];
            pagination = null;
        }
        
        // Garante que logs seja sempre um array
        if (!Array.isArray(logs)) {
            console.warn('Logs não é um array. Resposta recebida:', response);
            logs = [];
        }
        
        renderLogs();
        updatePagination();
    } catch (error) {
        console.error('Erro ao carregar logs:', error);
        showAlert('Erro ao carregar logs: ' + error.message, 'danger');
        logs = []; // Garante que logs seja um array mesmo em caso de erro
    } finally {
        document.getElementById('loadingLogs').style.display = 'none';
        document.getElementById('logsList').style.display = 'block';
    }
}

function renderLogs() {
    const tbody = document.getElementById('logsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('logsCountBadge');
    
    // ✅ CORREÇÃO: Garante que logs seja um array antes de usar .map()
    if (!Array.isArray(logs)) {
        logs = [];
    }
    
    if (logs.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        
        // Atualiza estatísticas
        const stats = calculateLogStats();
        updateLogStats(stats);
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(logs.length);
    }
    
    // Calcula estatísticas
    const stats = calculateLogStats();
    updateLogStats(stats);
    
    tbody.innerHTML = logs.map(log => {
        const actionBadge = {
            'create': 'bg-success',
            'update': 'bg-primary',
            'delete': 'bg-danger',
            'read': 'bg-info',
            'login': 'bg-warning',
            'logout': 'bg-secondary'
        }[log.action] || 'bg-secondary';
        
        const actionText = {
            'create': 'Criar',
            'update': 'Atualizar',
            'delete': 'Excluir',
            'read': 'Ler',
            'login': 'Login',
            'logout': 'Logout'
        }[log.action] || log.action || '-';
        
        return `
            <tr>
                <td>
                    <code class="text-muted small">${log.id}</code>
                </td>
                <td>
                    <small class="text-muted">${formatDate(log.created_at)}</small>
                </td>
                <td>
                    <div>${log.user_id || '-'}</div>
                </td>
                <td>
                    <span class="badge ${actionBadge}">${actionText}</span>
                </td>
                <td>
                    <code class="text-muted small">${escapeHtml(log.resource_type || '-')}</code>
                </td>
                <td>
                    <small class="text-muted">${escapeHtml(log.ip_address || '-')}</small>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewLogDetails(${log.id})" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function calculateLogStats() {
    const total = logs.length;
    const create = logs.filter(l => l.action === 'create').length;
    const update = logs.filter(l => l.action === 'update').length;
    const delete = logs.filter(l => l.action === 'delete').length;
    
    return { total, create, update, delete };
}

function updateLogStats(stats) {
    document.getElementById('totalLogsStat').textContent = formatNumber(stats.total);
    document.getElementById('createLogsStat').textContent = formatNumber(stats.create);
    document.getElementById('updateLogsStat').textContent = formatNumber(stats.update);
    document.getElementById('deleteLogsStat').textContent = formatNumber(stats.delete);
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

function updatePagination() {
    // ✅ CORREÇÃO: Usa informações de paginação da API se disponível
    let hasMore = false;
    let total = logs.length;
    
    if (pagination) {
        hasMore = pagination.has_more || false;
        total = pagination.total || logs.length;
    } else {
        // Fallback: usa lógica antiga se não houver paginação
        hasMore = logs.length === currentLimit;
    }
    
    const prevButton = document.getElementById('prevPage');
    const nextButton = document.getElementById('nextPage');
    const pageInfo = document.getElementById('pageInfo');
    
    if (prevButton) prevButton.disabled = currentOffset === 0;
    if (nextButton) nextButton.disabled = !hasMore;
    
    if (pageInfo) {
        const start = currentOffset + 1;
        const end = currentOffset + logs.length;
        pageInfo.textContent = `Mostrando ${start} - ${end}${total > 0 ? ` de ${total}` : ''}`;
    }
}

function previousPage() {
    if (currentOffset > 0) {
        currentOffset = Math.max(0, currentOffset - currentLimit);
        document.getElementById('offsetFilter').value = currentOffset;
        loadAuditLogs();
    }
}

function nextPage() {
    if (logs.length === currentLimit) {
        currentOffset += currentLimit;
        document.getElementById('offsetFilter').value = currentOffset;
        loadAuditLogs();
    }
}

async function viewLogDetails(logId) {
    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    const content = document.getElementById('logDetailsContent');
    
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border"></div></div>';
    modal.show();
    
    try {
        const response = await apiRequest(`/v1/audit-logs/${logId}`);
        // ✅ CORREÇÃO: A resposta tem estrutura { success: true, data: {...} }
        const data = response?.data || response || {};
        
        content.innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>ID:</strong> ${data.id}
                </div>
                <div class="col-md-6">
                    <strong>Data/Hora:</strong> ${formatDate(data.created_at)}
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Usuário ID:</strong> ${data.user_id || '-'}
                </div>
                <div class="col-md-6">
                    <strong>IP:</strong> ${data.ip_address || '-'}
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Ação:</strong> <span class="badge bg-primary">${data.action}</span>
                </div>
                <div class="col-md-6">
                    <strong>Recurso:</strong> <code>${data.resource_type || '-'}</code>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <strong>Resource ID:</strong> ${data.resource_id || '-'}
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <strong>Dados:</strong>
                    <pre class="bg-light p-3 rounded mt-2" style="max-height: 300px; overflow-y: auto;">${JSON.stringify(data.data || {}, null, 2)}</pre>
                </div>
            </div>
        `;
    } catch (error) {
        content.innerHTML = `<div class="alert alert-danger">Erro ao carregar detalhes: ${error.message}</div>`;
    }
}
</script>

