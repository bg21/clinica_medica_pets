<?php
/**
 * View de Cobranças (Charges)
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-credit-card-2-front text-primary"></i>
                Cobranças
            </h1>
            <p class="text-muted mb-0">Gerencie todas as cobranças realizadas</p>
        </div>
        <button class="btn btn-outline-primary" onclick="loadCharges()" title="Atualizar">
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
                            <p class="text-muted mb-1 small fw-medium">Total de Cobranças</p>
                            <h2 class="mb-0 fw-bold" id="totalChargesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-credit-card-2-front fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Bem-sucedidas</p>
                            <h2 class="mb-0 fw-bold" id="succeededChargesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-check-circle fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Pendentes</p>
                            <h2 class="mb-0 fw-bold" id="pendingChargesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-hourglass-split fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Valor Total</p>
                            <h2 class="mb-0 fw-bold" id="totalChargesAmountStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-cash-stack fs-1"></i>
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
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="succeeded">Sucesso</option>
                        <option value="pending">Pendente</option>
                        <option value="failed">Falhou</option>
                        <option value="refunded">Reembolsado</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadCharges()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadCharges()" title="Atualizar">
                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Cobranças -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Cobranças
            </h5>
            <span class="badge bg-primary" id="chargesCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingCharges" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando cobranças...</p>
            </div>
            <div id="chargesList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Descrição</th>
                                <th>Data</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="chargesTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-credit-card-2-front fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma cobrança encontrada</h5>
                    <p class="text-muted">As cobranças aparecerão aqui quando forem realizadas.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadCharges();
    }, 100);
});

async function loadCharges() {
    try {
        document.getElementById('loadingCharges').style.display = 'block';
        document.getElementById('chargesList').style.display = 'none';
        
        const params = new URLSearchParams();
        const statusFilter = document.getElementById('statusFilter')?.value;
        
        if (statusFilter) params.append('status', statusFilter);
        
        const url = '/v1/charges' + (params.toString() ? '?' + params.toString() : '');
        const response = await apiRequest(url);
        const chargesData = response.data || [];
        
        renderCharges(chargesData);
    } catch (error) {
        showAlert('Erro ao carregar cobranças: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingCharges').style.display = 'none';
        document.getElementById('chargesList').style.display = 'block';
    }
}

let charges = [];

function renderCharges(chargesData) {
    const tbody = document.getElementById('chargesTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('chargesCountBadge');
    
    charges = chargesData || [];
    
    if (charges.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        
        // Atualiza estatísticas
        const stats = calculateChargeStats();
        updateChargeStats(stats);
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(charges.length);
    }
    
    // Calcula estatísticas
    const stats = calculateChargeStats();
    updateChargeStats(stats);
    
    tbody.innerHTML = charges.map(charge => {
        const statusBadge = {
            'succeeded': 'bg-success',
            'pending': 'bg-warning',
            'failed': 'bg-danger',
            'refunded': 'bg-info',
            'canceled': 'bg-secondary'
        }[charge.status] || 'bg-secondary';
        
        const statusText = {
            'succeeded': 'Sucesso',
            'pending': 'Pendente',
            'failed': 'Falhou',
            'refunded': 'Reembolsado',
            'canceled': 'Cancelado'
        }[charge.status] || charge.status || '-';
        
        return `
            <tr>
                <td>
                    <code class="text-muted small">${escapeHtml(charge.id)}</code>
                </td>
                <td>
                    <div>${escapeHtml(charge.customer || charge.customer_id || '-')}</div>
                </td>
                <td>
                    <div class="fw-bold text-success">
                        ${formatCurrency(charge.amount, charge.currency || 'BRL')}
                    </div>
                </td>
                <td>
                    <span class="badge ${statusBadge}">${statusText}</span>
                </td>
                <td>
                    <div>${escapeHtml(charge.description || '-')}</div>
                </td>
                <td>
                    <small class="text-muted">${formatDate(charge.created)}</small>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewCharge('${charge.id}')" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function calculateChargeStats() {
    const total = charges.length;
    const succeeded = charges.filter(c => c.status === 'succeeded').length;
    const pending = charges.filter(c => c.status === 'pending').length;
    const totalAmount = charges
        .filter(c => c.status === 'succeeded')
        .reduce((sum, c) => sum + (c.amount || 0), 0);
    
    return { total, succeeded, pending, totalAmount };
}

function updateChargeStats(stats) {
    document.getElementById('totalChargesStat').textContent = formatNumber(stats.total);
    document.getElementById('succeededChargesStat').textContent = formatNumber(stats.succeeded);
    document.getElementById('pendingChargesStat').textContent = formatNumber(stats.pending);
    document.getElementById('totalChargesAmountStat').textContent = formatCurrency(stats.totalAmount, 'BRL');
}

function viewCharge(id) {
    window.location.href = `/charge-details?id=${id}`;
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

