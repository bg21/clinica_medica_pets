<?php
/**
 * View de Faturas (Geral)
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-receipt text-primary"></i>
                Faturas
            </h1>
            <p class="text-muted mb-0">Gerencie faturas e cobranças</p>
        </div>
        <button class="btn btn-outline-primary" onclick="loadInvoices()" title="Atualizar">
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
                            <p class="text-muted mb-1 small fw-medium">Total de Faturas</p>
                            <h2 class="mb-0 fw-bold" id="totalInvoicesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-receipt fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Pagas</p>
                            <h2 class="mb-0 fw-bold" id="paidInvoicesStat">-</h2>
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
                            <p class="text-muted mb-1 small fw-medium">Abertas</p>
                            <h2 class="mb-0 fw-bold" id="openInvoicesStat">-</h2>
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
                            <h2 class="mb-0 fw-bold" id="totalInvoicesAmountStat">-</h2>
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
                        <option value="draft">Rascunho</option>
                        <option value="open">Aberta</option>
                        <option value="paid">Paga</option>
                        <option value="void">Anulada</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadInvoices()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Faturas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Faturas
            </h5>
            <span class="badge bg-primary" id="invoicesCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingInvoices" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando faturas...</p>
            </div>
            <div id="invoicesList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="invoicesTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-receipt fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma fatura encontrada</h5>
                    <p class="text-muted">As faturas aparecerão aqui quando forem geradas.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadInvoices();
    }, 100);
});

let invoices = [];

// ✅ CORREÇÃO: Função local para formatar moeda sem dividir por 100 (valor já vem em reais)
function formatCurrencyReais(value, currency = 'BRL') {
    if (!value && value !== 0) return '-';
    
    const currencyMap = {
        'BRL': 'pt-BR',
        'USD': 'en-US',
        'EUR': 'de-DE',
        'GBP': 'en-GB'
    };
    
    const locale = currencyMap[currency?.toUpperCase()] || 'pt-BR';
    const currencyCode = currency?.toUpperCase() || 'BRL';
    
    // ✅ CORREÇÃO: O valor já vem em reais do backend (não divide por 100)
    const finalAmount = parseFloat(value);
    
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currencyCode
    }).format(finalAmount);
}

async function loadInvoices() {
    try {
        document.getElementById('loadingInvoices').style.display = 'block';
        document.getElementById('invoicesList').style.display = 'none';
        
        const statusFilter = document.getElementById('statusFilter')?.value || '';
        const params = new URLSearchParams();
        if (statusFilter) {
            params.append('status', statusFilter);
        }
        params.append('limit', '50');
        
        const response = await apiRequest(`/v1/invoices?${params.toString()}`);
        invoices = response.data || [];
        
        renderInvoices(invoices);
        
        // Atualiza estatísticas
        const stats = calculateInvoiceStats();
        updateInvoiceStats(stats);
        
    } catch (error) {
        console.error('Erro ao carregar faturas:', error);
        showAlert('Erro ao carregar faturas: ' + error.message, 'danger');
        invoices = [];
        renderInvoices([]);
    } finally {
        document.getElementById('loadingInvoices').style.display = 'none';
        document.getElementById('invoicesList').style.display = 'block';
    }
}

function renderInvoices(invoices) {
    const tbody = document.getElementById('invoicesTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('invoicesCountBadge');
    
    if (countBadge) countBadge.textContent = invoices.length.toString();
    
    if (invoices.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    const statusBadgeMap = {
        'draft': 'bg-secondary',
        'open': 'bg-warning',
        'paid': 'bg-success',
        'void': 'bg-danger',
        'uncollectible': 'bg-dark'
    };
    
    tbody.innerHTML = invoices.map(inv => {
        const statusBadge = statusBadgeMap[inv.status] || 'bg-secondary';
        const statusText = {
            'draft': 'Rascunho',
            'open': 'Aberta',
            'paid': 'Paga',
            'void': 'Anulada',
            'uncollectible': 'Não cobrável'
        }[inv.status] || inv.status;
        
        // Busca nome do cliente se disponível
        const customerName = inv.customer_name || inv.customer_email || inv.customer || 'Cliente não identificado';
        
        return `
            <tr>
                <td><code class="small">${escapeHtml(inv.id)}</code></td>
                <td>
                    ${typeof inv.customer === 'string' && inv.customer.startsWith('cus_') 
                        ? `<a href="/customer-details?id=${inv.customer}">${escapeHtml(customerName)}</a>`
                        : escapeHtml(customerName)
                    }
                </td>
                <td><strong>${formatCurrencyReais(inv.amount_due, inv.currency)}</strong></td>
                <td><span class="badge ${statusBadge}">${statusText}</span></td>
                <td>${formatDate(inv.created)}</td>
                <td>
                    <a href="/invoice-details?id=${inv.id}" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                        <i class="bi bi-eye"></i>
                    </a>
                    ${inv.invoice_pdf ? `
                        <a href="${inv.invoice_pdf}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Baixar PDF">
                            <i class="bi bi-file-pdf"></i>
                        </a>
                    ` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

function calculateInvoiceStats() {
    return {
        total: invoices.length,
        paid: invoices.filter(i => i.status === 'paid').length,
        open: invoices.filter(i => i.status === 'open').length,
        totalAmount: invoices.reduce((sum, i) => sum + (i.amount_due || i.total || 0), 0)
    };
}

function updateInvoiceStats(stats) {
    document.getElementById('totalInvoicesStat').textContent = formatNumber(stats.total);
    document.getElementById('paidInvoicesStat').textContent = formatNumber(stats.paid);
    document.getElementById('openInvoicesStat').textContent = formatNumber(stats.open);
    // ✅ CORREÇÃO: Usa formatCurrencyReais porque o valor já vem em reais
    document.getElementById('totalInvoicesAmountStat').textContent = formatCurrencyReais(stats.totalAmount, 'BRL');
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

