<?php
/**
 * View de Histórico de Assinaturas
 */
?>
<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/subscriptions">Assinaturas</a></li>
            <li class="breadcrumb-item active">Histórico</li>
        </ol>
    </nav>

    <h1 class="h3 mb-4"><i class="bi bi-clock-history"></i> Histórico de Assinaturas</h1>

    <div id="alertContainer"></div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="active">Ativas</option>
                        <option value="canceled">Canceladas</option>
                        <option value="past_due">Vencidas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Período</label>
                    <select class="form-select" id="periodFilter">
                        <option value="all">Todos</option>
                        <option value="month">Este Mês</option>
                        <option value="year">Este Ano</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadHistory()">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
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
                            <p class="text-muted mb-1 small fw-medium">Total de Assinaturas</p>
                            <h2 class="mb-0 fw-bold" id="totalSubscriptions">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-clock-history fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Ativas</p>
                            <h2 class="mb-0 fw-bold" id="activeSubscriptions">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-check-circle fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Canceladas</p>
                            <h2 class="mb-0 fw-bold" id="canceledSubscriptions">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-x-circle fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Em Trial</p>
                            <h2 class="mb-0 fw-bold" id="trialSubscriptions">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-hourglass-split fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Histórico -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Histórico de Assinaturas
            </h5>
            <span class="badge bg-primary" id="historyCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingHistory" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando histórico...</p>
            </div>
            <div id="historyList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Status</th>
                                <th>Valor</th>
                                <th>Criado em</th>
                                <th>Cancelado em</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-clock-history fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma assinatura encontrada</h5>
                    <p class="text-muted">O histórico de assinaturas aparecerá aqui.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadHistory();
    }, 100);
});

async function loadHistory() {
    try {
        document.getElementById('loadingHistory').style.display = 'block';
        document.getElementById('historyList').style.display = 'none';
        
        const response = await apiRequest('/v1/subscriptions');
        const subscriptions = response.data || [];
        
        updateStats(subscriptions);
        renderHistory(subscriptions);
    } catch (error) {
        showAlert('Erro ao carregar histórico: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingHistory').style.display = 'none';
        document.getElementById('historyList').style.display = 'block';
    }
}

function updateStats(subscriptions) {
    const total = subscriptions.length;
    const active = subscriptions.filter(s => s.status === 'active').length;
    const canceled = subscriptions.filter(s => s.status === 'canceled').length;
    const trialing = subscriptions.filter(s => s.status === 'trialing').length;
    
    document.getElementById('totalSubscriptions').textContent = formatNumber(total);
    document.getElementById('activeSubscriptions').textContent = formatNumber(active);
    document.getElementById('canceledSubscriptions').textContent = formatNumber(canceled);
    document.getElementById('trialSubscriptions').textContent = formatNumber(trialing);
}

// ✅ CORREÇÃO: Função local para formatar moeda sem dividir por 100 (valor já vem em reais)
function formatCurrencyReais(amount, currency = 'BRL') {
    if (!amount && amount !== 0) return '-';
    
    const currencyMap = {
        'BRL': 'pt-BR',
        'USD': 'en-US',
        'EUR': 'de-DE',
        'GBP': 'en-GB'
    };
    
    const locale = currencyMap[currency?.toUpperCase()] || 'pt-BR';
    const currencyCode = currency?.toUpperCase() || 'BRL';
    
    // Garante que é um número e não divide novamente
    const finalAmount = parseFloat(amount);
    
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currencyCode
    }).format(finalAmount);
}

function renderHistory(subscriptions) {
    const tbody = document.getElementById('historyTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('historyCountBadge');
    
    if (subscriptions.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(subscriptions.length);
    }
    
    tbody.innerHTML = subscriptions.map(sub => {
        const statusBadge = {
            'active': 'bg-success',
            'canceled': 'bg-danger',
            'past_due': 'bg-warning',
            'trialing': 'bg-info',
            'incomplete': 'bg-secondary',
            'incomplete_expired': 'bg-secondary'
        }[sub.status] || 'bg-secondary';
        
        const statusText = {
            'active': 'Ativa',
            'canceled': 'Cancelada',
            'past_due': 'Vencida',
            'trialing': 'Em Trial',
            'incomplete': 'Incompleta',
            'incomplete_expired': 'Incompleta Expirada'
        }[sub.status] || sub.status || '-';
        
        // ✅ CORREÇÃO: Prioriza customer_name (retornado pelo backend para SaaS admins)
        const customerName = sub.customer_name || sub.customer?.name || sub.customer_id || '-';
        const customerId = sub.customer_id || sub.customer?.id || null;
        const customerDisplay = customerName !== '-' ? 
            `<a href="/customer-details?id=${customerId || customerName}" class="text-decoration-none">${escapeHtml(customerName)}</a>` :
            escapeHtml(customerName);
        
        // ✅ CORREÇÃO: Usa stripe_subscription_id ou id para o link
        const subscriptionId = sub.stripe_subscription_id || sub.id;
        
        return `
            <tr>
                <td>
                    <code class="text-muted small">${escapeHtml(subscriptionId)}</code>
                </td>
                <td>
                    <div>${customerDisplay}</div>
                    ${customerId && customerId !== customerName ? `<small class="text-muted">ID: ${escapeHtml(customerId)}</small>` : ''}
                </td>
                <td>
                    <span class="badge ${statusBadge}">${statusText}</span>
                </td>
                <td>
                    <div class="fw-bold text-success">
                        ${sub.amount !== undefined && sub.amount !== null ? formatCurrencyReais(sub.amount, sub.currency || 'BRL') : '-'}
                    </div>
                </td>
                <td>
                    <small class="text-muted">${formatDate(sub.created_at || sub.created)}</small>
                </td>
                <td>
                    <small class="text-muted">${sub.canceled_at ? formatDate(sub.canceled_at) : '-'}</small>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="/subscription-details?id=${subscriptionId}" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
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

