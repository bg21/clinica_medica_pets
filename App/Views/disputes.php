<?php
/**
 * View de Disputas
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-exclamation-triangle text-primary"></i>
                Disputas e Chargebacks
            </h1>
            <p class="text-muted mb-0">Gerencie disputas e chargebacks</p>
        </div>
        <button class="btn btn-outline-primary" onclick="loadDisputes()" title="Atualizar">
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
                            <p class="text-muted mb-1 small fw-medium">Total de Disputas</p>
                            <h2 class="mb-0 fw-bold" id="totalDisputesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Precisam Resposta</p>
                            <h2 class="mb-0 fw-bold" id="needsResponseStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-exclamation-circle fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Em Revisão</p>
                            <h2 class="mb-0 fw-bold" id="underReviewStat">-</h2>
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
                            <h2 class="mb-0 fw-bold" id="totalDisputesAmountStat">-</h2>
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
                        <option value="warning_needs_response">Precisa Resposta</option>
                        <option value="warning_under_review">Em Revisão</option>
                        <option value="warning_closed">Fechada</option>
                        <option value="needs_response">Aguardando Resposta</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadDisputes()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadDisputes()" title="Atualizar">
                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Disputas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Disputas
            </h5>
            <span class="badge bg-primary" id="disputesCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingDisputes" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando disputas...</p>
            </div>
            <div id="disputesList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Charge</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Razão</th>
                                <th>Data</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="disputesTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <h5 class="mt-3 text-muted">Nenhuma disputa encontrada</h5>
                    <p class="text-muted">Ótimo! Não há disputas ou chargebacks pendentes.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadDisputes();
    }, 100);
});

async function loadDisputes() {
    try {
        document.getElementById('loadingDisputes').style.display = 'block';
        document.getElementById('disputesList').style.display = 'none';
        
        const response = await apiRequest('/v1/disputes');
        const disputes = response.data || [];
        
        renderDisputes(disputes);
    } catch (error) {
        showAlert('Erro ao carregar disputas: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingDisputes').style.display = 'none';
        document.getElementById('disputesList').style.display = 'block';
    }
}

let disputes = [];

function renderDisputes(disputesData) {
    const tbody = document.getElementById('disputesTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('disputesCountBadge');
    
    disputes = disputesData || [];
    
    if (disputes.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        
        // Atualiza estatísticas
        const stats = calculateDisputeStats();
        updateDisputeStats(stats);
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(disputes.length);
    }
    
    // Calcula estatísticas
    const stats = calculateDisputeStats();
    updateDisputeStats(stats);
    
    tbody.innerHTML = disputes.map(dispute => {
        const statusBadge = {
            'warning_needs_response': 'bg-danger',
            'needs_response': 'bg-danger',
            'warning_under_review': 'bg-warning',
            'under_review': 'bg-warning',
            'warning_closed': 'bg-secondary',
            'won': 'bg-success',
            'lost': 'bg-danger',
            'charge_refunded': 'bg-info'
        }[dispute.status] || 'bg-secondary';
        
        const statusText = {
            'warning_needs_response': 'Precisa Resposta',
            'needs_response': 'Aguardando Resposta',
            'warning_under_review': 'Em Revisão',
            'under_review': 'Em Revisão',
            'warning_closed': 'Fechada',
            'won': 'Ganha',
            'lost': 'Perdida',
            'charge_refunded': 'Reembolsada'
        }[dispute.status] || dispute.status || '-';
        
        const reasonText = {
            'fraudulent': 'Fraudulento',
            'subscription_canceled': 'Assinatura Cancelada',
            'product_unacceptable': 'Produto Inaceitável',
            'unrecognized': 'Não Reconhecido',
            'credit_not_processed': 'Crédito Não Processado',
            'general': 'Geral',
            'duplicate': 'Duplicado',
            'incorrect_account_details': 'Detalhes Incorretos',
            'insufficient_funds': 'Fundos Insuficientes',
            'product_not_received': 'Produto Não Recebido',
            'subscription_canceled': 'Assinatura Cancelada',
            'unrecognized': 'Não Reconhecido'
        }[dispute.reason] || dispute.reason || '-';
        
        return `
            <tr>
                <td>
                    <code class="text-muted small">${escapeHtml(dispute.id)}</code>
                </td>
                <td>
                    <code class="text-muted small">${escapeHtml(dispute.charge || '-')}</code>
                </td>
                <td>
                    <div class="fw-bold text-danger">
                        ${formatCurrency(dispute.amount, dispute.currency || 'BRL')}
                    </div>
                </td>
                <td>
                    <span class="badge ${statusBadge}">${statusText}</span>
                </td>
                <td>
                    <small>${reasonText}</small>
                </td>
                <td>
                    <small class="text-muted">${formatDate(dispute.created)}</small>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewDispute('${dispute.id}')" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function calculateDisputeStats() {
    const total = disputes.length;
    const needsResponse = disputes.filter(d => 
        d.status === 'warning_needs_response' || d.status === 'needs_response'
    ).length;
    const underReview = disputes.filter(d => 
        d.status === 'warning_under_review' || d.status === 'under_review'
    ).length;
    const totalAmount = disputes.reduce((sum, d) => sum + (d.amount || 0), 0);
    
    return { total, needsResponse, underReview, totalAmount };
}

function updateDisputeStats(stats) {
    document.getElementById('totalDisputesStat').textContent = formatNumber(stats.total);
    document.getElementById('needsResponseStat').textContent = formatNumber(stats.needsResponse);
    document.getElementById('underReviewStat').textContent = formatNumber(stats.underReview);
    document.getElementById('totalDisputesAmountStat').textContent = formatCurrency(stats.totalAmount, 'BRL');
}

function viewDispute(id) {
    window.location.href = `/dispute-details?id=${id}`;
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

