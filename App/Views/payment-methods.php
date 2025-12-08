<?php
/**
 * View de Métodos de Pagamento
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-wallet2 text-primary"></i>
                Métodos de Pagamento
            </h1>
            <p class="text-muted mb-0">Gerencie métodos de pagamento dos clientees</p>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Cards de Estatísticas -->
    <div class="row g-3 mb-4" id="statsCards" style="display: none;">
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Total de Métodos</p>
                            <h2 class="mb-0 fw-bold" id="totalMethodsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-wallet2 fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Cartões</p>
                            <h2 class="mb-0 fw-bold" id="cardMethodsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-credit-card fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Padrão</p>
                            <h2 class="mb-0 fw-bold" id="defaultMethodsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-star-fill fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Outros Tipos</p>
                            <h2 class="mb-0 fw-bold" id="otherMethodsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-bank fs-1"></i>
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
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <select class="form-select" id="customerFilter" onchange="loadPaymentMethods()">
                        <option value="">Selecione um cliente...</option>
                    </select>
                    <small class="text-muted">Ou informe o ID manualmente abaixo</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label">ID Manual</label>
                    <input type="number" class="form-control" id="customerIdManual" placeholder="ID do cliente" onchange="document.getElementById('customerFilter').value = this.value || ''; loadPaymentMethods();">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" id="typeFilter" onchange="loadPaymentMethods()">
                        <option value="">Todos</option>
                        <option value="card">Cartão</option>
                        <option value="bank_account">Conta Bancária</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="loadPaymentMethods()">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Métodos de Pagamento -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Métodos de Pagamento
            </h5>
            <span class="badge bg-primary" id="methodsCountBadge" style="display: none;">0</span>
        </div>
        <div class="card-body">
            <div id="loadingMethods" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando métodos de pagamento...</p>
            </div>
            <div id="methodsList">
                <div id="emptyState" class="text-center py-5">
                    <i class="bi bi-wallet2 fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Selecione um cliente</h5>
                    <p class="text-muted">Selecione ou informe o ID de um cliente para ver seus métodos de pagamento.</p>
                </div>
                <div id="paymentMethodsContainer"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Carrega lista de clientees
    loadCustomers();
    
    setTimeout(() => {
        // Carrega métodos de pagamento se houver customer_id na URL
        const urlParams = new URLSearchParams(window.location.search);
        const customerId = urlParams.get('customer_id');
        if (customerId) {
            document.getElementById('customerFilter').value = customerId;
            document.getElementById('customerIdManual').value = customerId;
            loadPaymentMethods();
        }
    }, 100);
});

async function loadCustomers() {
    try {
        const response = await apiRequest('/v1/customers?limit=100');
        // ✅ CORREÇÃO: response.data já é o array de clientees
        const customers = Array.isArray(response.data) ? response.data : [];
        
        const select = document.getElementById('customerFilter');
        // Limpa opções existentes (exceto a primeira)
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }
        
        customers.forEach(customer => {
            const option = document.createElement('option');
            option.value = customer.id;
            const name = customer.name || customer.email || 'Cliente';
            option.textContent = `${name} (ID: ${customer.id})`;
            select.appendChild(option);
        });
    } catch (error) {
        console.warn('Erro ao carregar lista de clientees:', error);
        // Não bloqueia a funcionalidade se falhar
    }
}

async function loadPaymentMethods() {
    // ✅ CORREÇÃO: Tenta obter customer_id do select ou do input manual
    let customerId = document.getElementById('customerFilter').value;
    if (!customerId) {
        customerId = document.getElementById('customerIdManual').value;
        if (customerId) {
            document.getElementById('customerFilter').value = customerId;
        }
    } else {
        document.getElementById('customerIdManual').value = customerId;
    }
    
    if (!customerId) {
        showAlert('Por favor, selecione ou informe o ID do cliente', 'warning');
        // Mostra estado vazio
        document.getElementById('emptyState').style.display = 'block';
        document.getElementById('paymentMethodsContainer').innerHTML = '';
        return;
    }
    
    try {
        document.getElementById('loadingMethods').style.display = 'block';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('paymentMethodsContainer').innerHTML = '';
        
        const response = await apiRequest(`/v1/customers/${customerId}/payment-methods`);
        // ✅ CORREÇÃO: Garante que response.data seja um array
        const methods = Array.isArray(response.data) ? response.data : [];
        
        renderPaymentMethods(methods, customerId);
    } catch (error) {
        console.error('Erro ao carregar métodos de pagamento:', error);
        showAlert('Erro ao carregar métodos de pagamento: ' + error.message, 'danger');
        // Mostra estado vazio em caso de erro
        document.getElementById('emptyState').style.display = 'block';
        document.getElementById('paymentMethodsContainer').innerHTML = '';
    } finally {
        document.getElementById('loadingMethods').style.display = 'none';
    }
}

function renderPaymentMethods(methods, customerId) {
    const container = document.getElementById('paymentMethodsContainer');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('methodsCountBadge');
    const statsCards = document.getElementById('statsCards');
    
    // ✅ CORREÇÃO: Garante que methods seja um array
    if (!Array.isArray(methods)) {
        console.warn('renderPaymentMethods recebeu valor não-array:', methods);
        methods = [];
    }
    
    if (methods.length === 0) {
        emptyState.style.display = 'block';
        emptyState.innerHTML = `
            <i class="bi bi-wallet2 fs-1 text-muted"></i>
            <h5 class="mt-3 text-muted">Nenhum método de pagamento encontrado</h5>
            <p class="text-muted">Este cliente não possui métodos de pagamento cadastrados.</p>
        `;
        container.innerHTML = '';
        if (countBadge) countBadge.style.display = 'none';
        if (statsCards) statsCards.style.display = 'none';
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(methods.length);
        countBadge.style.display = 'inline-block';
    }
    if (statsCards) statsCards.style.display = 'flex';
    
    // Calcula estatísticas
    const stats = calculatePaymentMethodStats(methods);
    updatePaymentMethodStats(stats);
    
    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Últimos 4 dígitos</th>
                        <th>Bandeira</th>
                        <th>Validade</th>
                        <th>Padrão</th>
                        <th style="width: 200px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    ${methods.map(pm => `
                        <tr>
                            <td>
                                <code class="text-muted small">${escapeHtml(pm.id)}</code>
                            </td>
                            <td>
                                <span class="badge bg-info">${escapeHtml(pm.type || '-')}</span>
                            </td>
                            <td>
                                <div class="fw-medium">${pm.card ? `****${pm.card.last4}` : '-'}</div>
                            </td>
                            <td>
                                <div>${pm.card ? pm.card.brand : '-'}</div>
                            </td>
                            <td>
                                <small>${pm.card ? `${pm.card.exp_month}/${pm.card.exp_year}` : '-'}</small>
                            </td>
                            <td>
                                ${pm.is_default ? '<span class="badge bg-primary">Padrão</span>' : '-'}
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    ${!pm.is_default ? `
                                        <button class="btn btn-sm btn-outline-primary" onclick="setDefault('${customerId}', '${pm.id}')" title="Definir como padrão">
                                            <i class="bi bi-star"></i>
                                        </button>
                                    ` : ''}
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMethod('${customerId}', '${pm.id}')" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function calculatePaymentMethodStats(methods) {
    const total = methods.length;
    const cards = methods.filter(m => m.type === 'card').length;
    const defaultCount = methods.filter(m => m.is_default).length;
    const other = methods.filter(m => m.type !== 'card').length;
    
    return { total, cards, defaultCount, other };
}

function updatePaymentMethodStats(stats) {
    document.getElementById('totalMethodsStat').textContent = formatNumber(stats.total);
    document.getElementById('cardMethodsStat').textContent = formatNumber(stats.cards);
    document.getElementById('defaultMethodsStat').textContent = formatNumber(stats.defaultCount);
    document.getElementById('otherMethodsStat').textContent = formatNumber(stats.other);
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

async function setDefault(customerId, methodId) {
    // ✅ Valida formato de payment_method_id
    if (typeof validateStripeId === 'function') {
        const methodIdError = validateStripeId(methodId, 'payment_method_id', true);
        if (methodIdError) {
            showAlert('ID de método de pagamento inválido: ' + methodIdError, 'danger');
            return;
        }
    } else {
        // Fallback: validação básica
        const methodIdPattern = /^pm_[a-zA-Z0-9]+$/;
        if (!methodIdPattern.test(methodId)) {
            showAlert('Formato de Payment Method ID inválido. Use: pm_xxxxx', 'danger');
            return;
        }
    }
    
    try {
        await apiRequest(`/v1/customers/${customerId}/payment-methods/${methodId}/set-default`, {
            method: 'POST'
        });
        
        showAlert('Método de pagamento definido como padrão!', 'success');
        loadPaymentMethods();
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}

async function deleteMethod(customerId, methodId) {
    // ✅ Valida formato de payment_method_id
    if (typeof validateStripeId === 'function') {
        const methodIdError = validateStripeId(methodId, 'payment_method_id', true);
        if (methodIdError) {
            showAlert('ID de método de pagamento inválido: ' + methodIdError, 'danger');
            return;
        }
    } else {
        // Fallback: validação básica
        const methodIdPattern = /^pm_[a-zA-Z0-9]+$/;
        if (!methodIdPattern.test(methodId)) {
            showAlert('Formato de Payment Method ID inválido. Use: pm_xxxxx', 'danger');
            return;
        }
    }
    
    const confirmed = await showConfirmModal(
        'Tem certeza que deseja remover este método de pagamento?',
        'Confirmar Exclusão',
        'Remover Método'
    );
    if (!confirmed) return;
    
    try {
        await apiRequest(`/v1/customers/${customerId}/payment-methods/${methodId}`, {
            method: 'DELETE'
        });
        
        showAlert('Método de pagamento removido!', 'success');
        loadPaymentMethods();
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}
</script>

