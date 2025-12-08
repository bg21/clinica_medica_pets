<?php
/**
 * View de Preços
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-tag text-primary"></i>
                Preços
            </h1>
            <p class="text-muted mb-0">Gerencie preços e planos de assinatura</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPriceModal">
            <i class="bi bi-plus-circle"></i> Novo Preço
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
                            <p class="text-muted mb-1 small fw-medium">Total de Preços</p>
                            <h2 class="mb-0 fw-bold" id="totalPricesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-tag fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Preços Ativos</p>
                            <h2 class="mb-0 fw-bold" id="activePricesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-check-circle fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Recorrentes</p>
                            <h2 class="mb-0 fw-bold" id="recurringPricesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-arrow-repeat fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Pagamento Único</p>
                            <h2 class="mb-0 fw-bold" id="oneTimePricesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-cash-coin fs-1"></i>
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
                        <option value="true">Ativos</option>
                        <option value="false">Inativos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">Todos</option>
                        <option value="one_time">Pagamento Único</option>
                        <option value="recurring">Recorrente</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Moeda</label>
                    <select class="form-select" id="currencyFilter">
                        <option value="">Todas</option>
                        <option value="brl">BRL</option>
                        <option value="usd">USD</option>
                        <option value="eur">EUR</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadPrices()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadPrices()" title="Atualizar">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Preços -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Preços
            </h5>
            <span class="badge bg-primary" id="pricesCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingPrices" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando preços...</p>
            </div>
            <div id="pricesList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Produto</th>
                                <th>Valor</th>
                                <th>Tipo</th>
                                <th>Intervalo</th>
                                <th>Status</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="pricesTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-tag fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum preço encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro preço.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createPriceModal">
                        <i class="bi bi-plus-circle"></i> Criar Preço
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Preço -->
<div class="modal fade" id="createPriceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-tag me-2"></i>
                    Novo Preço
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createPriceForm" novalidate>
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <div>
                            <strong>Campos obrigatórios:</strong> Todos os campos marcados com * são obrigatórios. 
                            O valor deve ser informado em centavos (ex: 2999 = R$ 29,99).
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Produto *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-box"></i>
                            </span>
                            <select class="form-select" name="product" id="productSelect" required>
                                <option value="">Carregando produtos...</option>
                            </select>
                            <div class="invalid-feedback" id="productError"></div>
                        </div>
                        <small class="text-muted">Selecione o produto ao qual este preço será associado</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Valor (em centavos) *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-currency-dollar"></i>
                            </span>
                            <input type="number" 
                                   class="form-control" 
                                   name="unit_amount" 
                                   id="unitAmountInput" 
                                   min="1" 
                                   max="99999999" 
                                   placeholder="Ex: 2999 (para R$ 29,99)"
                                   required>
                            <div class="invalid-feedback" id="unitAmountError"></div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Valor mínimo: 1 centavo | Valor máximo: 99.999.999 centavos
                            </small>
                        </div>
                        <div id="amountPreview" class="mt-2" style="display: none;">
                            <div class="alert alert-success mb-0 py-2">
                                <i class="bi bi-check-circle me-1"></i>
                                <strong>Valor formatado:</strong> <span id="formattedAmount">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Moeda *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-globe"></i>
                            </span>
                            <select class="form-select" name="currency" id="currencySelect" required>
                                <option value="brl">BRL (Real Brasileiro)</option>
                                <option value="usd">USD (Dólar Americano)</option>
                                <option value="eur">EUR (Euro)</option>
                            </select>
                        </div>
                        <small class="text-muted">A moeda selecionada será usada para processar os pagamentos</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo de Preço *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-arrow-repeat"></i>
                            </span>
                            <select class="form-select" name="price_type" id="priceType" required>
                                <option value="one_time">Pagamento Único</option>
                                <option value="recurring">Recorrente (Assinatura)</option>
                            </select>
                        </div>
                        <small class="text-muted">Escolha se o preço será cobrado uma vez ou de forma recorrente</small>
                    </div>
                    
                    <div class="mb-3" id="recurringOptions" style="display: none;">
                        <label class="form-label">Intervalo de Cobrança *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-calendar-event"></i>
                            </span>
                            <select class="form-select" name="interval" id="intervalSelect">
                                <option value="">Selecione um intervalo</option>
                                <option value="day">Diário</option>
                                <option value="week">Semanal</option>
                                <option value="month" selected>Mensal</option>
                                <option value="year">Anual</option>
                            </select>
                            <div class="invalid-feedback" id="intervalError">Intervalo é obrigatório para preços recorrentes</div>
                        </div>
                        <small class="text-muted">Com que frequência o cliente será cobrado</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitPriceBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Criar Preço
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let prices = [];
let products = [];

document.addEventListener('DOMContentLoaded', () => {
    // Carrega dados após um pequeno delay para não bloquear a renderização
    setTimeout(() => {
        loadPrices();
        loadProducts();
    }, 100);
    
    // Toggle campos recorrentes
    const priceTypeSelect = document.getElementById('priceType');
    const recurringOptions = document.getElementById('recurringOptions');
    const intervalSelect = document.getElementById('intervalSelect');
    const currencySelect = document.getElementById('currencySelect');
    const unitAmountInput = document.getElementById('unitAmountInput');
    const amountPreview = document.getElementById('amountPreview');
    const formattedAmount = document.getElementById('formattedAmount');
    const productSelect = document.getElementById('productSelect');
    
    // Função para formatar e exibir preview do valor
    function updateAmountPreview() {
        const value = parseInt(unitAmountInput.value);
        const currency = currencySelect.value.toUpperCase();
        
        if (value && value >= 1 && value <= 99999999) {
            const formatted = formatCurrency(value, currency);
            formattedAmount.textContent = formatted;
            amountPreview.style.display = 'block';
            unitAmountInput.classList.remove('is-invalid');
            unitAmountInput.classList.add('is-valid');
            document.getElementById('unitAmountError').textContent = '';
        } else {
            amountPreview.style.display = 'none';
            if (unitAmountInput.value && (value < 1 || value > 99999999)) {
                unitAmountInput.classList.add('is-invalid');
                unitAmountInput.classList.remove('is-valid');
            } else {
                unitAmountInput.classList.remove('is-invalid', 'is-valid');
            }
        }
    }
    
    priceTypeSelect.addEventListener('change', (e) => {
        const isRecurring = e.target.value === 'recurring';
        recurringOptions.style.display = isRecurring ? 'block' : 'none';
        
        // Torna interval obrigatório quando recurring é selecionado
        if (isRecurring) {
            intervalSelect.setAttribute('required', 'required');
            intervalSelect.setAttribute('aria-required', 'true');
            // Garante que há um valor selecionado (padrão: month)
            if (!intervalSelect.value) {
                intervalSelect.value = 'month';
            }
            intervalSelect.classList.remove('is-invalid');
        } else {
            intervalSelect.removeAttribute('required');
            intervalSelect.removeAttribute('aria-required');
            intervalSelect.value = '';
            intervalSelect.classList.remove('is-invalid', 'is-valid');
        }
    });
    
    // Validação de unit_amount com preview em tempo real
    if (unitAmountInput) {
        unitAmountInput.addEventListener('input', () => {
            const value = parseInt(unitAmountInput.value);
            if (!unitAmountInput.value || unitAmountInput.value.trim() === '') {
                unitAmountInput.classList.remove('is-invalid', 'is-valid');
                amountPreview.style.display = 'none';
                document.getElementById('unitAmountError').textContent = '';
            } else if (value < 1) {
                unitAmountInput.classList.add('is-invalid');
                unitAmountInput.classList.remove('is-valid');
                document.getElementById('unitAmountError').textContent = 'Valor mínimo é 1 centavo';
                amountPreview.style.display = 'none';
            } else if (value > 99999999) {
                unitAmountInput.classList.add('is-invalid');
                unitAmountInput.classList.remove('is-valid');
                document.getElementById('unitAmountError').textContent = 'Valor máximo é 99.999.999 centavos';
                amountPreview.style.display = 'none';
            } else {
                updateAmountPreview();
            }
        });
        
        unitAmountInput.addEventListener('blur', () => {
            const value = parseInt(unitAmountInput.value);
            if (!unitAmountInput.value || unitAmountInput.value.trim() === '') {
                unitAmountInput.classList.remove('is-invalid', 'is-valid');
            } else if (value < 1 || value > 99999999) {
                unitAmountInput.classList.add('is-invalid');
                unitAmountInput.classList.remove('is-valid');
            }
        });
    }
    
    // Atualiza preview quando a moeda muda
    if (currencySelect) {
        currencySelect.addEventListener('change', () => {
            if (unitAmountInput.value) {
                updateAmountPreview();
            }
        });
    }
    
    // Validação de produto
    if (productSelect) {
        productSelect.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                document.getElementById('productError').textContent = '';
            } else {
                this.classList.remove('is-valid');
            }
        });
        
        productSelect.addEventListener('blur', function() {
            if (!this.value) {
                this.classList.add('is-invalid');
                document.getElementById('productError').textContent = 'Por favor, selecione um produto';
            }
        });
    }
    
    // Validação de intervalo (quando recorrente)
    if (intervalSelect) {
        intervalSelect.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                document.getElementById('intervalError').textContent = '';
            } else {
                this.classList.remove('is-valid');
            }
        });
    }
    
    // Reset do formulário quando a modal é fechada
    const createPriceModal = document.getElementById('createPriceModal');
    if (createPriceModal) {
        createPriceModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('createPriceForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                
                // Remove classes de validação
                const inputs = form.querySelectorAll('.is-invalid, .is-valid');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid', 'is-valid');
                });
                
                // Reseta estados
                recurringOptions.style.display = 'none';
                intervalSelect.removeAttribute('required');
                intervalSelect.removeAttribute('aria-required');
                amountPreview.style.display = 'none';
                
                // Limpa mensagens de erro
                document.getElementById('productError').textContent = '';
                document.getElementById('unitAmountError').textContent = '';
                document.getElementById('intervalError').textContent = '';
            }
        });
    }
    
    // Form criar preço
    const createPriceForm = document.getElementById('createPriceForm');
    const submitPriceBtn = document.getElementById('submitPriceBtn');
    
    if (createPriceForm) {
        createPriceForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Validação HTML5
            if (!createPriceForm.checkValidity()) {
                createPriceForm.classList.add('was-validated');
                return;
            }
            
            const formData = new FormData(e.target);
            
            // Valida produto
            const product = formData.get('product');
            if (!product || product.trim() === '') {
                productSelect.classList.add('is-invalid');
                document.getElementById('productError').textContent = 'Por favor, selecione um produto';
                productSelect.focus();
                return;
            }
            
            // Valida unit_amount
            const unitAmount = parseInt(formData.get('unit_amount'));
            if (!unitAmount || unitAmount < 1 || unitAmount > 99999999) {
                unitAmountInput.classList.add('is-invalid');
                document.getElementById('unitAmountError').textContent = 'Valor deve estar entre 1 e 99.999.999 centavos';
                unitAmountInput.focus();
                return;
            }
            
            // Valida interval se recurring
            const priceType = formData.get('price_type');
            if (priceType === 'recurring') {
                const interval = formData.get('interval');
                if (!interval || interval.trim() === '') {
                    intervalSelect.classList.add('is-invalid');
                    document.getElementById('intervalError').textContent = 'Intervalo é obrigatório para preços recorrentes';
                    intervalSelect.focus();
                    return;
                }
            }
            
            // Desabilita botão durante o envio
            const originalBtnText = submitPriceBtn.innerHTML;
            submitPriceBtn.disabled = true;
            submitPriceBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Criando...';
            
            const data = {
                product: product,
                unit_amount: unitAmount,
                currency: formData.get('currency')
            };
            
            if (priceType === 'recurring') {
                data.recurring = {
                    interval: formData.get('interval')
                };
            }
            
            try {
                const response = await apiRequest('/v1/prices', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                // Limpa cache após criar preço
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/prices');
                }
                
                showAlert('Preço criado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('createPriceModal')).hide();
                
                // Reset já é feito pelo evento hidden.bs.modal
                loadPrices();
            } catch (error) {
                showAlert(error.message || 'Erro ao criar preço. Tente novamente.', 'danger');
            } finally {
                submitPriceBtn.disabled = false;
                submitPriceBtn.innerHTML = originalBtnText;
            }
        });
    }
});

async function loadProducts() {
    const select = document.getElementById('productSelect');
    
    try {
        // Carrega todos os produtos (sem limite ou com limite alto)
        const response = await apiRequest('/v1/products?limit=100');
        products = response.data?.products || response.data || [];
        
        // Atualiza o select apenas se existir
        if (select) {
            select.innerHTML = '<option value="">Selecione um produto</option>' +
                products.map(p => {
                    const name = escapeHtml(p.name || p.id);
                    return `<option value="${p.id}">${name} (${p.id})</option>`;
                }).join('');
        }
        
        console.log(`Produtos carregados: ${products.length}`);
    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
        products = [];
        if (select) {
            select.innerHTML = '<option value="">Erro ao carregar produtos</option>';
            select.classList.add('is-invalid');
            const productError = document.getElementById('productError');
            if (productError) {
                productError.textContent = 'Erro ao carregar lista de produtos';
            }
        }
    }
}

async function loadPrices() {
    try {
        document.getElementById('loadingPrices').style.display = 'block';
        document.getElementById('pricesList').style.display = 'none';
        
        // Garante que os produtos estão carregados antes de renderizar
        if (products.length === 0) {
            await loadProducts();
        }
        
        const params = new URLSearchParams();
        const statusFilter = document.getElementById('statusFilter')?.value;
        const typeFilter = document.getElementById('typeFilter')?.value;
        const currencyFilter = document.getElementById('currencyFilter')?.value;
        
        if (statusFilter) params.append('active', statusFilter);
        if (typeFilter) params.append('type', typeFilter);
        if (currencyFilter) params.append('currency', currencyFilter);
        
        const url = '/v1/prices' + (params.toString() ? '?' + params.toString() : '');
        const response = await apiRequest(url);
        prices = response.data?.prices || response.data || [];
        
        renderPrices();
    } catch (error) {
        showAlert('Erro ao carregar preços: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingPrices').style.display = 'none';
        document.getElementById('pricesList').style.display = 'block';
    }
}

function renderPrices() {
    const tbody = document.getElementById('pricesTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('pricesCountBadge');
    
    if (prices.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(prices.length);
    }
    
    // Calcula estatísticas
    const stats = calculatePriceStats();
    updatePriceStats(stats);
    
    tbody.innerHTML = prices.map(price => {
        // Tenta obter o nome do produto de diferentes formas
        let productName = 'N/A';
        let productId = null;
        
        // Se price.product é um objeto (com id e name)
        if (price.product && typeof price.product === 'object' && !Array.isArray(price.product)) {
            productName = price.product.name || price.product.id || 'N/A';
            productId = price.product.id || price.product;
        } 
        // Se price.product é uma string (ID do produto)
        else if (price.product && typeof price.product === 'string') {
            productId = price.product;
            // Busca o produto no array de produtos carregados
            const product = products.find(p => p.id === productId);
            if (product) {
                productName = product.name || product.id || productId;
            } else {
                // Se não encontrou no array, usa o ID como nome temporariamente
                productName = productId;
            }
        }
        // Se ainda não encontrou, tenta usar product_id
        else if (price.product_id) {
            productId = price.product_id;
            const product = products.find(p => p.id === productId);
            if (product) {
                productName = product.name || product.id || productId;
            } else {
                productName = productId;
            }
        }
        
        // Escapa HTML para segurança
        productName = escapeHtml(productName);
        productId = productId ? escapeHtml(String(productId)) : (price.product ? escapeHtml(String(price.product)) : 'N/A');
        
        const type = price.type === 'recurring' ? 'Recorrente' : 'Único';
        const interval = price.recurring?.interval ? 
            (price.recurring.interval === 'month' ? 'Mensal' : 
             price.recurring.interval === 'year' ? 'Anual' :
             price.recurring.interval === 'week' ? 'Semanal' : 
             price.recurring.interval === 'day' ? 'Diário' : price.recurring.interval) : '-';
        
        return `
            <tr>
                <td>
                    <div>
                        <code class="text-muted small">${escapeHtml(price.id)}</code>
                    </div>
                </td>
                <td>
                    <div class="fw-medium">${productName}</div>
                    ${productId && productId !== 'N/A' ? `<small class="text-muted">ID: ${productId}</small>` : ''}
                </td>
                <td>
                    <div class="fw-bold">${formatCurrency(price.unit_amount, price.currency)}</div>
                    <small class="text-muted">${price.currency?.toUpperCase() || ''}</small>
                </td>
                <td>
                    <span class="badge bg-${price.type === 'recurring' ? 'primary' : 'secondary'}">${type}</span>
                </td>
                <td>
                    ${interval !== '-' ? `
                        <div>${interval}</div>
                        ${price.recurring?.interval_count && price.recurring.interval_count > 1 ? `
                            <small class="text-muted">A cada ${price.recurring.interval_count}</small>
                        ` : ''}
                    ` : '<span class="text-muted">-</span>'}
                </td>
                <td>
                    <span class="badge bg-${price.active ? 'success' : 'secondary'}">
                        ${price.active ? 'Ativo' : 'Inativo'}
                    </span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="/price-details?id=${price.id}" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePrice('${price.id}')" title="Excluir">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function calculatePriceStats() {
    const total = prices.length;
    const active = prices.filter(p => p.active).length;
    const recurring = prices.filter(p => p.type === 'recurring').length;
    const oneTime = prices.filter(p => p.type === 'one_time').length;
    
    return { total, active, recurring, oneTime };
}

function updatePriceStats(stats) {
    document.getElementById('totalPricesStat').textContent = formatNumber(stats.total);
    document.getElementById('activePricesStat').textContent = formatNumber(stats.active);
    document.getElementById('recurringPricesStat').textContent = formatNumber(stats.recurring);
    document.getElementById('oneTimePricesStat').textContent = formatNumber(stats.oneTime);
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

function formatCurrency(amount, currency = 'BRL') {
    if (!amount && amount !== 0) return '-';
    
    const currencyMap = {
        'BRL': 'pt-BR',
        'USD': 'en-US',
        'EUR': 'de-DE',
        'GBP': 'en-GB'
    };
    
    const locale = currencyMap[currency?.toUpperCase()] || 'pt-BR';
    const currencyCode = currency?.toUpperCase() || 'BRL';
    
    // Se o valor já estiver em centavos (maior que 1000), divide por 100
    const finalAmount = amount > 1000 ? amount / 100 : amount;
    
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currencyCode
    }).format(finalAmount);
}

async function deletePrice(priceId) {
    const confirmed = await showConfirmModal(
        'Tem certeza que deseja desativar este preço? Esta ação não pode ser desfeita.',
        'Confirmar Desativação',
        'Desativar Preço'
    );
    if (!confirmed) return;
    
    try {
        const response = await apiRequest(`/v1/prices/${priceId}`, {
            method: 'DELETE'
        });
        
        // Limpa cache após deletar preço
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/prices');
        }
        
        showAlert('Preço desativado com sucesso!', 'success');
        
        // Recarrega a lista
        loadPrices();
    } catch (error) {
        console.error('Erro ao desativar preço:', error);
        showAlert(error.message || 'Erro ao desativar preço', 'danger');
    }
}

</script>

