<?php
/**
 * View de Saques (Payouts)
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-bank text-primary"></i>
                Saques
            </h1>
            <p class="text-muted mb-0">Gerencie saques e transferências</p>
        </div>
        <button class="btn btn-outline-primary" onclick="loadPayouts()" title="Atualizar">
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
                            <p class="text-muted mb-1 small fw-medium">Total de Saques</p>
                            <h2 class="mb-0 fw-bold" id="totalPayoutsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-bank fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Pagos</p>
                            <h2 class="mb-0 fw-bold" id="paidPayoutsStat">-</h2>
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
                            <h2 class="mb-0 fw-bold" id="pendingPayoutsStat">-</h2>
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
                            <h2 class="mb-0 fw-bold" id="totalPayoutsAmountStat">-</h2>
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
                        <option value="paid">Pago</option>
                        <option value="pending">Pendente</option>
                        <option value="in_transit">Em Trânsito</option>
                        <option value="canceled">Cancelado</option>
                        <option value="failed">Falhou</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadPayouts()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadPayouts()" title="Atualizar">
                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Saques -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Saques
            </h5>
            <span class="badge bg-primary" id="payoutsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingPayouts" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando saques...</p>
            </div>
            <div id="payoutsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Método</th>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="payoutsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-bank fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum saque encontrado</h5>
                    <p class="text-muted">Os saques aparecerão aqui quando forem processados.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadPayouts();
    }, 100);
});

async function loadPayouts() {
    try {
        document.getElementById('loadingPayouts').style.display = 'block';
        document.getElementById('payoutsList').style.display = 'none';
        
        const params = new URLSearchParams();
        const statusFilter = document.getElementById('statusFilter')?.value;
        
        if (statusFilter) params.append('status', statusFilter);
        
        const url = '/v1/payouts' + (params.toString() ? '?' + params.toString() : '');
        const response = await apiRequest(url);
        const payoutsData = response.data || [];
        
        renderPayouts(payoutsData);
    } catch (error) {
        showAlert('Erro ao carregar saques: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingPayouts').style.display = 'none';
        document.getElementById('payoutsList').style.display = 'block';
    }
}

let payouts = [];

function renderPayouts(payoutsData) {
    const tbody = document.getElementById('payoutsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('payoutsCountBadge');
    
    payouts = payoutsData || [];
    
    if (payouts.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        
        // Atualiza estatísticas
        const stats = calculatePayoutStats();
        updatePayoutStats(stats);
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(payouts.length);
    }
    
    // Calcula estatísticas
    const stats = calculatePayoutStats();
    updatePayoutStats(stats);
    
    tbody.innerHTML = payouts.map(payout => {
        const statusBadge = {
            'paid': 'bg-success',
            'pending': 'bg-warning',
            'in_transit': 'bg-info',
            'canceled': 'bg-secondary',
            'failed': 'bg-danger'
        }[payout.status] || 'bg-secondary';
        
        const statusText = {
            'paid': 'Pago',
            'pending': 'Pendente',
            'in_transit': 'Em Trânsito',
            'canceled': 'Cancelado',
            'failed': 'Falhou'
        }[payout.status] || payout.status || '-';
        
        return `
            <tr>
                <td>
                    <code class="text-muted small">${escapeHtml(payout.id)}</code>
                </td>
                <td>
                    <div class="fw-bold text-success">
                        ${formatCurrency(payout.amount, payout.currency || 'BRL')}
                    </div>
                </td>
                <td>
                    <span class="badge ${statusBadge}">${statusText}</span>
                </td>
                <td>
                    <div>${escapeHtml(payout.method || payout.type || '-')}</div>
                </td>
                <td>
                    <small class="text-muted">${formatDate(payout.created)}</small>
                </td>
                <td>
                    <div>${escapeHtml(payout.description || '-')}</div>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewPayout('${payout.id}')" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function calculatePayoutStats() {
    const total = payouts.length;
    const paid = payouts.filter(p => p.status === 'paid').length;
    const pending = payouts.filter(p => p.status === 'pending' || p.status === 'in_transit').length;
    const totalAmount = payouts
        .filter(p => p.status === 'paid')
        .reduce((sum, p) => sum + (p.amount || 0), 0);
    
    return { total, paid, pending, totalAmount };
}

function updatePayoutStats(stats) {
    document.getElementById('totalPayoutsStat').textContent = formatNumber(stats.total);
    document.getElementById('paidPayoutsStat').textContent = formatNumber(stats.paid);
    document.getElementById('pendingPayoutsStat').textContent = formatNumber(stats.pending);
    document.getElementById('totalPayoutsAmountStat').textContent = formatCurrency(stats.totalAmount, 'BRL');
}

function viewPayout(id) {
    window.location.href = `/payout-details?id=${id}`;
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

