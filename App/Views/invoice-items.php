<?php
/**
 * View de Itens de Fatura
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-list-ul text-primary"></i>
                Itens de Fatura
            </h1>
            <p class="text-muted mb-0">Gerencie itens adicionais de faturas</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceItemModal">
            <i class="bi bi-plus-circle"></i> Novo Item
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
                            <p class="text-muted mb-1 small fw-medium">Total de Itens</p>
                            <h2 class="mb-0 fw-bold" id="totalItemsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-list-ul fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Clientees Únicos</p>
                            <h2 class="mb-0 fw-bold" id="uniqueCustomersStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-people fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Este Mês</p>
                            <h2 class="mb-0 fw-bold" id="thisMonthStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-calendar-month fs-1"></i>
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
                            <h2 class="mb-0 fw-bold" id="totalItemsAmountStat">-</h2>
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
                    <label class="form-label">Cliente</label>
                    <input type="number" class="form-control" id="customerFilter" placeholder="ID do cliente">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadInvoiceItems()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadInvoiceItems()" title="Atualizar">
                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Itens -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Itens de Fatura
            </h5>
            <span class="badge bg-primary" id="itemsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingItems" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando itens...</p>
            </div>
            <div id="itemsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th>Quantidade</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-list-ul fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum item encontrado</h5>
                    <p class="text-muted">Crie um novo item de fatura para começar.</p>
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createInvoiceItemModal">
                        <i class="bi bi-plus-circle"></i> Criar Primeiro Item
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Item -->
<div class="modal fade" id="createInvoiceItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Item de Fatura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createInvoiceItemForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cliente ID *</label>
                        <input type="number" class="form-control" name="customer_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (em centavos) *</label>
                        <input type="number" class="form-control" name="amount" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Moeda *</label>
                        <select class="form-select" name="currency" required>
                            <option value="brl">BRL</option>
                            <option value="usd">USD</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadInvoiceItems();
    }, 100);
    
    document.getElementById('createInvoiceItemForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            customer_id: parseInt(formData.get('customer_id')),
            amount: parseInt(formData.get('amount')),
            currency: formData.get('currency'),
            description: formData.get('description')
        };
        
        try {
            await apiRequest('/v1/invoice-items', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            showAlert('Item criado com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createInvoiceItemModal')).hide();
            e.target.reset();
            loadInvoiceItems();
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });
});

async function loadInvoiceItems() {
    try {
        document.getElementById('loadingItems').style.display = 'block';
        document.getElementById('itemsList').style.display = 'none';
        
        const params = new URLSearchParams();
        const customerFilter = document.getElementById('customerFilter')?.value;
        
        if (customerFilter) params.append('customer_id', customerFilter);
        
        const url = '/v1/invoice-items' + (params.toString() ? '?' + params.toString() : '');
        const response = await apiRequest(url);
        const itemsData = response.data || [];
        
        renderItems(itemsData);
    } catch (error) {
        showAlert('Erro ao carregar itens: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingItems').style.display = 'none';
        document.getElementById('itemsList').style.display = 'block';
    }
}

let invoiceItems = [];

function renderItems(itemsData) {
    const tbody = document.getElementById('itemsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('itemsCountBadge');
    
    invoiceItems = itemsData || [];
    
    if (invoiceItems.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        
        // Atualiza estatísticas
        const stats = calculateItemStats();
        updateItemStats(stats);
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(invoiceItems.length);
    }
    
    // Calcula estatísticas
    const stats = calculateItemStats();
    updateItemStats(stats);
    
    tbody.innerHTML = invoiceItems.map(item => `
        <tr>
            <td>
                <code class="text-muted small">${escapeHtml(item.id)}</code>
            </td>
            <td>
                <div>${escapeHtml(item.customer || item.customer_id || '-')}</div>
            </td>
            <td>
                <div>${escapeHtml(item.description || '-')}</div>
            </td>
            <td>
                <span class="badge bg-secondary">${item.quantity || 1}</span>
            </td>
            <td>
                <div class="fw-bold text-success">
                    ${formatCurrency(item.amount, item.currency || 'BRL')}
                </div>
            </td>
            <td>
                <small class="text-muted">${formatDate(item.created)}</small>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewItem('${item.id}')" title="Ver detalhes">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem('${item.id}')" title="Excluir">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function calculateItemStats() {
    const total = invoiceItems.length;
    const uniqueCustomers = new Set(invoiceItems.map(i => i.customer_id || i.customer)).size;
    const now = new Date();
    const thisMonth = invoiceItems.filter(i => {
        const created = new Date(i.created);
        return created.getMonth() === now.getMonth() && created.getFullYear() === now.getFullYear();
    }).length;
    const totalAmount = invoiceItems.reduce((sum, i) => sum + ((i.amount || 0) * (i.quantity || 1)), 0);
    
    return { total, uniqueCustomers, thisMonth, totalAmount };
}

function updateItemStats(stats) {
    document.getElementById('totalItemsStat').textContent = formatNumber(stats.total);
    document.getElementById('uniqueCustomersStat').textContent = formatNumber(stats.uniqueCustomers);
    document.getElementById('thisMonthStat').textContent = formatNumber(stats.thisMonth);
    document.getElementById('totalItemsAmountStat').textContent = formatCurrency(stats.totalAmount, 'BRL');
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

function viewItem(id) {
    // ✅ Valida formato de invoice_item_id
    if (typeof validateStripeId === 'function') {
        const idError = validateStripeId(id, 'invoice_item_id', true);
        if (idError) {
            showAlert('ID de item de fatura inválido: ' + idError, 'danger');
            return;
        }
    } else {
        // Fallback: validação básica
        const idPattern = /^ii_[a-zA-Z0-9]+$/;
        if (!idPattern.test(id)) {
            showAlert('Formato de Invoice Item ID inválido. Use: ii_xxxxx', 'danger');
            return;
        }
    }
    
    alert('Detalhes do item: ' + id);
}

async function deleteItem(id) {
    // ✅ Valida formato de invoice_item_id
    if (typeof validateStripeId === 'function') {
        const idError = validateStripeId(id, 'invoice_item_id', true);
        if (idError) {
            showAlert('ID de item de fatura inválido: ' + idError, 'danger');
            return;
        }
    } else {
        // Fallback: validação básica
        const idPattern = /^ii_[a-zA-Z0-9]+$/;
        if (!idPattern.test(id)) {
            showAlert('Formato de Invoice Item ID inválido. Use: ii_xxxxx', 'danger');
            return;
        }
    }
    
    const confirmed = await showConfirmModal(
        'Tem certeza que deseja remover este item?',
        'Confirmar Exclusão',
        'Remover Item'
    );
    if (!confirmed) return;
    
    try {
        await apiRequest(`/v1/invoice-items/${id}`, { method: 'DELETE' });
        showAlert('Item removido com sucesso!', 'success');
        loadInvoiceItems();
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}
</script>

