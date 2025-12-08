<?php
/**
 * View de Reembolsos
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-arrow-counterclockwise text-primary"></i>
                Reembolsos
            </h1>
            <p class="text-muted mb-0">Gerencie reembolsos e estornos</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRefundModal">
            <i class="bi bi-plus-circle"></i> Novo Reembolso
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
                            <p class="text-muted mb-1 small fw-medium">Total de Reembolsos</p>
                            <h2 class="mb-0 fw-bold" id="totalRefundsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-arrow-counterclockwise fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Concluídos</p>
                            <h2 class="mb-0 fw-bold" id="succeededRefundsStat">-</h2>
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
                            <h2 class="mb-0 fw-bold" id="pendingRefundsStat">-</h2>
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
                            <h2 class="mb-0 fw-bold" id="totalRefundsAmountStat">-</h2>
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
                        <option value="pending">Pendente</option>
                        <option value="succeeded">Sucesso</option>
                        <option value="failed">Falhou</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadRefunds()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadRefunds()" title="Atualizar">
                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Reembolsos -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Reembolsos
            </h5>
            <span class="badge bg-primary" id="refundsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingRefunds" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando reembolsos...</p>
            </div>
            <div id="refundsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Charge</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Razão</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="refundsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-arrow-counterclockwise fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum reembolso encontrado</h5>
                    <p class="text-muted">Os reembolsos aparecerão aqui quando forem processados.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Reembolso -->
<div class="modal fade" id="createRefundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Reembolso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createRefundForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Charge ID *</label>
                        <input type="text" class="form-control" name="charge_id" placeholder="ch_xxxxx" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (em centavos, deixe vazio para reembolso total)</label>
                        <input type="number" class="form-control" name="amount" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Razão</label>
                        <select class="form-select" name="reason">
                            <option value="duplicate">Duplicado</option>
                            <option value="fraudulent">Fraudulento</option>
                            <option value="requested_by_customer" selected>Solicitado pelo cliente</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Reembolso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadRefunds();
    }, 100);
    
    document.getElementById('createRefundForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            charge_id: formData.get('charge_id'),
            reason: formData.get('reason')
        };
        
        if (formData.get('amount')) {
            data.amount = parseInt(formData.get('amount'));
        }
        
        try {
            await apiRequest('/v1/refunds', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            showAlert('Reembolso criado com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createRefundModal')).hide();
            e.target.reset();
            loadRefunds();
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });
});

let refunds = [];

async function loadRefunds() {
    try {
        document.getElementById('loadingRefunds').style.display = 'block';
        document.getElementById('refundsList').style.display = 'none';
        
        // Nota: Precisa de endpoint para listar reembolsos
        // Por enquanto, mostra estrutura preparada
        refunds = [];
        
        renderRefunds();
    } catch (error) {
        showAlert('Erro: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingRefunds').style.display = 'none';
        document.getElementById('refundsList').style.display = 'block';
    }
}

function renderRefunds() {
    const tbody = document.getElementById('refundsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('refundsCountBadge');
    
    if (refunds.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        
        // Atualiza estatísticas
        const stats = calculateRefundStats();
        updateRefundStats(stats);
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(refunds.length);
    }
    
    // Calcula estatísticas
    const stats = calculateRefundStats();
    updateRefundStats(stats);
    
    tbody.innerHTML = refunds.map(refund => {
        const statusBadge = {
            'pending': 'bg-warning',
            'succeeded': 'bg-success',
            'failed': 'bg-danger',
            'canceled': 'bg-secondary'
        }[refund.status] || 'bg-secondary';
        
        const statusText = {
            'pending': 'Pendente',
            'succeeded': 'Concluído',
            'failed': 'Falhou',
            'canceled': 'Cancelado'
        }[refund.status] || refund.status || '-';
        
        const reasonText = {
            'duplicate': 'Duplicado',
            'fraudulent': 'Fraudulento',
            'requested_by_customer': 'Solicitado pelo cliente'
        }[refund.reason] || refund.reason || '-';
        
        return `
            <tr>
                <td>
                    <code class="text-muted small">${escapeHtml(refund.id)}</code>
                </td>
                <td>
                    <code class="text-muted small">${escapeHtml(refund.charge || '-')}</code>
                </td>
                <td>
                    <div class="fw-bold text-danger">
                        -${formatCurrency(refund.amount, refund.currency || 'BRL')}
                    </div>
                </td>
                <td>
                    <span class="badge ${statusBadge}">${statusText}</span>
                </td>
                <td>
                    <small class="text-muted">${formatDate(refund.created)}</small>
                </td>
                <td>
                    <small>${reasonText}</small>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="/refund-details?id=${refund.id}" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function calculateRefundStats() {
    const total = refunds.length;
    const succeeded = refunds.filter(r => r.status === 'succeeded').length;
    const pending = refunds.filter(r => r.status === 'pending').length;
    const totalAmount = refunds.reduce((sum, r) => sum + (r.amount || 0), 0);
    
    return { total, succeeded, pending, totalAmount };
}

function updateRefundStats(stats) {
    document.getElementById('totalRefundsStat').textContent = formatNumber(stats.total);
    document.getElementById('succeededRefundsStat').textContent = formatNumber(stats.succeeded);
    document.getElementById('pendingRefundsStat').textContent = formatNumber(stats.pending);
    document.getElementById('totalRefundsAmountStat').textContent = formatCurrency(stats.totalAmount, 'BRL');
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

