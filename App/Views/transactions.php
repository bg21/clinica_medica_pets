<?php
/**
 * View de Transações
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-arrow-left-right text-primary"></i>
                Transações
            </h1>
            <p class="text-muted mb-0">Histórico de todas as transações financeiras</p>
        </div>
        <button class="btn btn-outline-primary" onclick="loadTransactions()" title="Atualizar">
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
                            <p class="text-muted mb-1 small fw-medium">Total de Transações</p>
                            <h2 class="mb-0 fw-bold" id="totalTransactionsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-arrow-left-right fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Concluídas</p>
                            <h2 class="mb-0 fw-bold" id="succeededTransactionsStat">-</h2>
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
                            <h2 class="mb-0 fw-bold" id="pendingTransactionsStat">-</h2>
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
                            <h2 class="mb-0 fw-bold" id="totalAmountStat">-</h2>
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
                    <label class="form-label">Tipo</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">Todos</option>
                        <option value="charge">Cobrança</option>
                        <option value="refund">Reembolso</option>
                        <option value="payout">Saque</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="available">Disponível</option>
                        <option value="pending">Pendente</option>
                        <option value="failed">Falhou</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadTransactions()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadTransactions()" title="Atualizar">
                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Transações -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Transações
            </h5>
            <span class="badge bg-primary" id="transactionsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingTransactions" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando transações...</p>
            </div>
            <div id="transactionsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-arrow-left-right fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma transação encontrada</h5>
                    <p class="text-muted">As transações aparecerão aqui quando houver movimentações financeiras.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadTransactions();
    }, 100);
});

async function loadTransactions() {
    try {
        document.getElementById('loadingTransactions').style.display = 'block';
        document.getElementById('transactionsList').style.display = 'none';
        
        const params = new URLSearchParams();
        const typeFilter = document.getElementById('typeFilter')?.value;
        const statusFilter = document.getElementById('statusFilter')?.value;
        
        if (typeFilter) params.append('type', typeFilter);
        if (statusFilter) params.append('status', statusFilter);
        
        const url = '/v1/balance-transactions' + (params.toString() ? '?' + params.toString() : '');
        const balanceResponse = await apiRequest(url);
        const transactionsData = balanceResponse.data || [];
        
        renderTransactions(transactionsData);
    } catch (error) {
        showAlert('Erro ao carregar transações: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingTransactions').style.display = 'none';
        document.getElementById('transactionsList').style.display = 'block';
    }
}

let transactions = [];

function renderTransactions(transactionsData) {
    const tbody = document.getElementById('transactionsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('transactionsCountBadge');
    
    transactions = transactionsData || [];
    
    if (transactions.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(transactions.length);
    }
    
    // Calcula estatísticas
    const stats = calculateTransactionStats();
    updateTransactionStats(stats);
    
    tbody.innerHTML = transactions.map(tx => {
        const statusBadge = {
            'available': 'bg-success',
            'pending': 'bg-warning',
            'failed': 'bg-danger'
        }[tx.status] || 'bg-secondary';
        
        const statusText = {
            'available': 'Disponível',
            'pending': 'Pendente',
            'failed': 'Falhou'
        }[tx.status] || tx.status || '-';
        
        const typeBadge = {
            'charge': 'bg-primary',
            'refund': 'bg-warning',
            'payout': 'bg-info',
            'adjustment': 'bg-secondary'
        }[tx.type] || 'bg-secondary';
        
        const typeText = {
            'charge': 'Cobrança',
            'refund': 'Reembolso',
            'payout': 'Saque',
            'adjustment': 'Ajuste'
        }[tx.type] || tx.type || '-';
        
        return `
            <tr>
                <td>
                    <code class="text-muted small">${escapeHtml(tx.id)}</code>
                </td>
                <td>
                    <span class="badge ${typeBadge}">${typeText}</span>
                </td>
                <td>
                    <div>${escapeHtml(tx.description || '-')}</div>
                </td>
                <td>
                    <div class="fw-bold ${tx.amount >= 0 ? 'text-success' : 'text-danger'}">
                        ${tx.amount >= 0 ? '+' : ''}${formatCurrency(tx.amount, tx.currency || 'BRL')}
                    </div>
                </td>
                <td>
                    <span class="badge ${statusBadge}">${statusText}</span>
                </td>
                <td>
                    <small class="text-muted">${formatDate(tx.created)}</small>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewTransaction('${tx.id}')" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function calculateTransactionStats() {
    const total = transactions.length;
    const succeeded = transactions.filter(t => t.status === 'available').length;
    const pending = transactions.filter(t => t.status === 'pending').length;
    const totalAmount = transactions.reduce((sum, t) => sum + (Math.abs(t.amount) || 0), 0);
    
    return { total, succeeded, pending, totalAmount };
}

function updateTransactionStats(stats) {
    document.getElementById('totalTransactionsStat').textContent = formatNumber(stats.total);
    document.getElementById('succeededTransactionsStat').textContent = formatNumber(stats.succeeded);
    document.getElementById('pendingTransactionsStat').textContent = formatNumber(stats.pending);
    document.getElementById('totalAmountStat').textContent = formatCurrency(stats.totalAmount, 'BRL');
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

function viewTransaction(id) {
    window.location.href = `/transaction-details?id=${id}`;
}
</script>

