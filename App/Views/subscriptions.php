<?php
/**
 * View de Assinaturas
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-credit-card text-primary"></i>
                Assinaturas
            </h1>
            <p class="text-muted mb-0">Gerencie assinaturas e planos dos clientees</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSubscriptionModal">
            <i class="bi bi-plus-circle"></i> Nova Assinatura
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
                            <p class="text-muted mb-1 small fw-medium">Total</p>
                            <h2 class="mb-0 fw-bold" id="totalSubscriptions">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-credit-card fs-1"></i>
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
            <div class="card kpi-card kpi-card-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Em Trial</p>
                            <h2 class="mb-0 fw-bold" id="trialingSubscriptions">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-hourglass-split fs-1"></i>
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
    </div>

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
                        <option value="trialing">Em Trial</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="customerFilter" placeholder="ID ou Email do cliente">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadSubscriptions()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadSubscriptions()" title="Atualizar">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Assinaturas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Assinaturas
            </h5>
            <span class="badge bg-primary" id="subscriptionsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingSubscriptions" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando assinaturas...</p>
            </div>
            <div id="subscriptionsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Status</th>
                                <th>Plano</th>
                                <th>Valor</th>
                                <th>Próximo Pagamento</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="subscriptionsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-credit-card fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma assinatura encontrada</h5>
                    <p class="text-muted">Comece criando sua primeira assinatura.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createSubscriptionModal">
                        <i class="bi bi-plus-circle"></i> Criar Assinatura
                    </button>
                </div>
                <div id="paginationContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Assinatura -->
<div class="modal fade" id="createSubscriptionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-credit-card me-2"></i>
                    Nova Assinatura
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createSubscriptionForm" novalidate>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Campos marcados com <span class="text-danger">*</span> são obrigatórios.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-person me-1"></i>
                                Cliente <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <select class="form-select" name="customer_id" id="customerSelect" required>
                                    <option value="">Selecione um cliente...</option>
                                </select>
                                <div class="invalid-feedback" id="customerError">
                                    Por favor, selecione um cliente.
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Cliente que receberá a assinatura.
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Preço <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-tag"></i>
                                </span>
                                <select 
                                    class="form-select" 
                                    name="price_id" 
                                    id="priceSelect" 
                                    required>
                                    <option value="">Carregando preços...</option>
                                </select>
                                <button 
                                    type="button" 
                                    class="btn btn-outline-secondary" 
                                    id="refreshPricesBtn"
                                    title="Atualizar lista de preços">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <div class="invalid-feedback" id="priceSelectError">
                                    Por favor, selecione um preço.
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Selecione o preço/plano para a assinatura.
                            </small>
                            
                            <!-- Input alternativo para Price ID manual (opcional) -->
                            <div class="mt-2">
                                <button 
                                    type="button" 
                                    class="btn btn-link btn-sm p-0 text-decoration-none" 
                                    id="toggleManualPriceBtn"
                                    onclick="toggleManualPriceInput()">
                                    <i class="bi bi-pencil me-1"></i>
                                    Ou digite o Price ID manualmente
                                </button>
                            </div>
                            <div id="manualPriceContainer" class="mt-2" style="display: none;">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-keyboard"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="priceIdInput" 
                                        placeholder="price_1AbCdEfGhIjKlMn"
                                        pattern="^price_[a-zA-Z0-9]+$"
                                        autocomplete="off">
                                    <div class="invalid-feedback" id="priceIdError">
                                        Por favor, insira um Price ID válido do Stripe (formato: price_xxxxx).
                                    </div>
                                    <div class="valid-feedback" id="priceIdSuccess" style="display: none;">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Price ID válido
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    ID do preço no Stripe. Formato: <code>price_xxxxx</code>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-hourglass-split me-1"></i>
                                Período de Trial (dias)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-hourglass-split"></i>
                                </span>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    name="trial_period_days" 
                                    id="trialPeriodInput"
                                    placeholder="0"
                                    min="0"
                                    max="365"
                                    value="0"
                                    step="1">
                                <span class="input-group-text">dias</span>
                                <div class="invalid-feedback" id="trialPeriodError">
                                    O período de trial deve ser entre 0 e 365 dias.
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Número de dias de trial gratuito (0 = sem trial).
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-event me-1"></i>
                                Data de Início (opcional)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-calendar-event"></i>
                                </span>
                                <input 
                                    type="date" 
                                    class="form-control" 
                                    name="billing_cycle_anchor" 
                                    id="billingCycleAnchor"
                                    min="<?php echo date('Y-m-d'); ?>">
                                <div class="invalid-feedback" id="billingCycleError">
                                    A data deve ser hoje ou no futuro.
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Data de início da cobrança (padrão: hoje).
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text me-1"></i>
                            Observações
                        </label>
                        <textarea 
                            class="form-control" 
                            name="metadata" 
                            id="subscriptionMetadata"
                            rows="2"
                            placeholder='{"nota": "Informações adicionais sobre esta assinatura"}'
                            maxlength="500"></textarea>
                        <small class="form-text text-muted">
                            Metadados em formato JSON (opcional). Ex: <code>{"nota": "Cliente VIP"}</code>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitSubscriptionBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Criar Assinatura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
let subscriptions = [];
let customers = [];
let paginationMeta = {};

let currentPage = 1;
let pageSize = 20;

let prices = [];

document.addEventListener('DOMContentLoaded', () => {
    // Carrega dados imediatamente e em paralelo
    Promise.all([
        loadSubscriptions(),
        loadCustomers(),
        loadPrices()
    ]);
    
    const priceSelect = document.getElementById('priceSelect');
    const priceIdInput = document.getElementById('priceIdInput');
    const customerSelect = document.getElementById('customerSelect');
    const trialPeriodInput = document.getElementById('trialPeriodInput');
    const billingCycleAnchor = document.getElementById('billingCycleAnchor');
    const refreshPricesBtn = document.getElementById('refreshPricesBtn');
    const manualPriceContainer = document.getElementById('manualPriceContainer');
    const toggleManualPriceBtn = document.getElementById('toggleManualPriceBtn');
    
    // Carrega preços quando a modal é aberta
    const createSubscriptionModal = document.getElementById('createSubscriptionModal');
    if (createSubscriptionModal) {
        createSubscriptionModal.addEventListener('show.bs.modal', async function() {
            if (prices.length === 0) {
                await loadPrices();
            }
            populatePriceSelect();
        });
    }
    
    // Botão de atualizar preços
    if (refreshPricesBtn) {
        refreshPricesBtn.addEventListener('click', async function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            await loadPrices();
            populatePriceSelect();
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
        });
    }
    
    // Quando seleciona um preço no select, atualiza o input manual (se visível)
    if (priceSelect) {
        priceSelect.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                // Se o input manual estiver visível, preenche também
                if (priceIdInput && manualPriceContainer.style.display !== 'none') {
                    priceIdInput.value = this.value;
                    priceIdInput.classList.remove('is-invalid');
                    priceIdInput.classList.add('is-valid');
                }
            } else {
                this.classList.remove('is-valid');
            }
        });
    }
    
    // Validação do input manual de price_id (quando usado)
    if (priceIdInput) {
        priceIdInput.addEventListener('input', function() {
            const value = this.value.trim();
            if (value) {
                const isValid = /^price_[a-zA-Z0-9]+$/.test(value);
                if (isValid) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    document.getElementById('priceIdSuccess').style.display = 'block';
                    document.getElementById('priceIdError').textContent = '';
                    // Atualiza o select também se o valor corresponder
                    if (priceSelect) {
                        priceSelect.value = value;
                        priceSelect.classList.remove('is-invalid');
                        priceSelect.classList.add('is-valid');
                    }
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('priceIdSuccess').style.display = 'none';
                    document.getElementById('priceIdError').textContent = 'Formato inválido. Use: price_xxxxx';
                }
            } else {
                this.classList.remove('is-invalid', 'is-valid');
                document.getElementById('priceIdSuccess').style.display = 'none';
            }
        });
        
        priceIdInput.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && !/^price_[a-zA-Z0-9]+$/.test(value)) {
                this.classList.add('is-invalid');
                document.getElementById('priceIdError').textContent = 'Por favor, insira um Price ID válido do Stripe.';
            }
        });
    }
    
    
    // Função para alternar input manual
    window.toggleManualPriceInput = function() {
        if (manualPriceContainer.style.display === 'none') {
            manualPriceContainer.style.display = 'block';
            priceSelect.removeAttribute('required');
            if (priceIdInput) {
                priceIdInput.setAttribute('required', 'required');
                // Se já tiver um valor no select, copia para o input
                if (priceSelect.value) {
                    priceIdInput.value = priceSelect.value;
                }
            }
            toggleManualPriceBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Usar seleção de preços';
        } else {
            manualPriceContainer.style.display = 'none';
            priceSelect.setAttribute('required', 'required');
            if (priceIdInput) {
                priceIdInput.removeAttribute('required');
                priceIdInput.value = '';
                priceIdInput.classList.remove('is-invalid', 'is-valid');
            }
            toggleManualPriceBtn.innerHTML = '<i class="bi bi-pencil me-1"></i> Ou digite o Price ID manualmente';
        }
    };
    
    // Validação de trial period
    if (trialPeriodInput) {
        trialPeriodInput.addEventListener('blur', function() {
            const value = parseInt(this.value) || 0;
            if (value < 0 || value > 365) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
                if (value > 0) {
                    this.classList.add('is-valid');
                }
            }
        });
        
        trialPeriodInput.addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
        });
    }
    
    // Validação de data de início
    if (billingCycleAnchor) {
        billingCycleAnchor.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                this.classList.add('is-invalid');
            document.getElementById('billingCycleError').textContent = 'A data deve ser hoje ou no futuro.';
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    }
    
    // Validação de cliente
    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });
    }
    
    
    // Reset do formulário quando a modal é fechada
    if (createSubscriptionModal) {
        createSubscriptionModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('createSubscriptionForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                
                // Remove classes de validação
                const inputs = form.querySelectorAll('.is-invalid, .is-valid');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid', 'is-valid');
                });
                
                // Reseta mensagens e estados
                document.getElementById('priceIdSuccess').style.display = 'none';
                if (manualPriceContainer) manualPriceContainer.style.display = 'none';
                if (priceSelect) priceSelect.value = '';
                if (priceIdInput) {
                    priceIdInput.value = '';
                    priceIdInput.classList.remove('is-invalid', 'is-valid');
                }
                if (toggleManualPriceBtn) {
                    toggleManualPriceBtn.innerHTML = '<i class="bi bi-pencil me-1"></i> Ou digite o Price ID manualmente';
                }
                // Restaura required no select
                if (priceSelect) priceSelect.setAttribute('required', 'required');
            }
        });
    }
    
    // Form criar assinatura
    const createSubscriptionForm = document.getElementById('createSubscriptionForm');
    const submitBtn = document.getElementById('submitSubscriptionBtn');
    
    if (createSubscriptionForm) {
        createSubscriptionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Validação HTML5
            if (!createSubscriptionForm.checkValidity()) {
                createSubscriptionForm.classList.add('was-validated');
                return;
            }
            
            // Validação customizada de price_id
            // Pega do select (principal) ou do input manual (alternativo)
            const priceId = priceSelect.value || (priceIdInput ? priceIdInput.value.trim() : '');
            
            if (!priceId) {
                priceSelect.classList.add('is-invalid');
                createSubscriptionForm.classList.add('was-validated');
                priceSelect.focus();
                return;
            }
            
            // Se veio do input manual, valida formato
            if (priceIdInput && priceIdInput.value.trim() && !/^price_[a-zA-Z0-9]+$/.test(priceId)) {
                priceIdInput.classList.add('is-invalid');
                document.getElementById('priceIdError').textContent = 'Por favor, insira um Price ID válido do Stripe.';
                createSubscriptionForm.classList.add('was-validated');
                priceIdInput.focus();
                return;
            }
            
            // Desabilita botão durante o envio
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Criando...';
            }
            
            try {
                const formData = new FormData(createSubscriptionForm);
                const data = {};
                
                // Processa dados do formulário
                for (let [key, value] of formData.entries()) {
                    const trimmedValue = value.trim();
                    if (trimmedValue) {
                        if (key === 'customer_id') {
                            data[key] = parseInt(trimmedValue);
                        } else if (key === 'trial_period_days') {
                            data[key] = parseInt(trimmedValue) || 0;
                        } else if (key === 'billing_cycle_anchor') {
                            data[key] = trimmedValue;
                        } else if (key === 'metadata') {
                            // Tenta parsear JSON se fornecido
                            try {
                                data.metadata = JSON.parse(trimmedValue);
                            } catch (e) {
                                // Se não for JSON válido, ignora
                                console.warn('Metadata não é JSON válido, ignorando');
                            }
                        } else if (key === 'price_id') {
                            // Usa o valor do select (principal) ou do input manual
                            data[key] = priceSelect.value || (priceIdInput ? priceIdInput.value.trim() : trimmedValue);
                        } else {
                            data[key] = trimmedValue;
                        }
                    }
                }
                
                // Garante que price_id está presente
                if (!data.price_id) {
                    data.price_id = priceSelect.value || (priceIdInput ? priceIdInput.value.trim() : '');
                }
                
                const response = await apiRequest('/v1/subscriptions', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                // Limpa cache após criar assinatura
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/subscriptions');
                }
                
                showAlert('Assinatura criada com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('createSubscriptionModal')).hide();
                
                // Reset do formulário já é feito pelo evento hidden.bs.modal
                loadSubscriptions();
            } catch (error) {
                showAlert(error.message || 'Erro ao criar assinatura. Tente novamente.', 'danger');
            } finally {
                // Reabilita botão
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Assinatura';
                }
            }
        });
    }
});

async function loadCustomers() {
    const select = document.getElementById('customerSelect');
    
    if (!select) return;
    
    try {
        select.innerHTML = '<option value="">Carregando clientees...</option>';
        const response = await apiRequest('/v1/customers?limit=100');
        customers = response.data || [];
        
        if (customers.length === 0) {
            select.innerHTML = '<option value="">Nenhum cliente encontrado</option>';
            return;
        }
        
        select.innerHTML = '<option value="">Selecione um cliente...</option>' +
            customers.map(c => {
                const name = escapeHtml(c.name || 'Sem nome');
                const email = escapeHtml(c.email || '');
                const displayText = name !== 'Sem nome' 
                    ? `${name} - ${email} (ID: ${c.id})`
                    : `${email} (ID: ${c.id})`;
                return `<option value="${c.id}">${displayText}</option>`;
            }).join('');
        
        select.classList.remove('is-invalid');
    } catch (error) {
        console.error('Erro ao carregar clientees:', error);
        select.innerHTML = '<option value="">Erro ao carregar clientees</option>';
        select.classList.add('is-invalid');
        
        // Adiciona botão de tentar novamente
        let errorDiv = select.parentElement.querySelector('.alert-danger');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger mt-2';
            select.parentElement.appendChild(errorDiv);
        }
        errorDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle me-2"></i>
            Erro ao carregar clientees: ${escapeHtml(error.message)}
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="loadCustomers()">
                <i class="bi bi-arrow-clockwise"></i> Tentar novamente
            </button>
        `;
        
        // Remove mensagem de erro após 5 segundos
        setTimeout(() => {
            if (errorDiv && errorDiv.parentElement) {
                errorDiv.remove();
            }
            select.classList.remove('is-invalid');
        }, 5000);
    }
}

// Função auxiliar para escape HTML (definida antes para uso em outras funções)
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Função auxiliar para formatar moeda (definida antes para uso em outras funções)
// ✅ CORREÇÃO: Renomeada para formatCurrencyReais para evitar conflito com formatCurrency global
// do dashboard.js que divide por 100 (assume centavos). Esta função assume que o valor já está em reais.
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
    
    // ✅ CORREÇÃO: O valor já vem em reais do backend (não centavos)
    // Garante que é um número e não divide novamente
    const finalAmount = parseFloat(amount);
    
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currencyCode
    }).format(finalAmount);
}

// Função para popular o select de preços (deve estar no escopo global)
// Definida antes de loadPrices para garantir que esteja disponível
function populatePriceSelect() {
    const priceSelect = document.getElementById('priceSelect');
    if (!priceSelect) return;
    
    if (prices.length === 0) {
        priceSelect.innerHTML = '<option value="">Nenhum preço encontrado</option>';
        return;
    }
    
    // Filtra apenas preços recorrentes (necessários para assinaturas)
    const recurringPrices = prices.filter(p => p.recurring && p.recurring.interval);
    
    if (recurringPrices.length === 0) {
        priceSelect.innerHTML = '<option value="">Nenhum preço recorrente disponível</option>';
        return;
    }
    
    priceSelect.innerHTML = '<option value="">Selecione um preço...</option>' +
        recurringPrices.map(p => {
            const amount = p.amount ? formatCurrencyReais(p.amount, p.currency || 'brl') : '-';
            const interval = p.recurring?.interval === 'month' ? 'mês' : 
                               p.recurring?.interval === 'year' ? 'ano' : 
                               p.recurring?.interval || '';
            const productName = p.product?.name || p.product_name || 'Produto';
            const label = `${productName} - ${amount}${interval ? '/' + interval : ''}`;
            return `<option value="${p.id}" data-price-id="${p.id}">${escapeHtml(label)}</option>`;
        }).join('');
}

async function loadPrices() {
    try {
        const response = await apiRequest('/v1/prices?active=true&limit=100');
        prices = response.data?.prices || response.data || [];
        console.log(`Preços carregados: ${prices.length}`);
        
        // Popula o select automaticamente após carregar
        populatePriceSelect();
    } catch (error) {
        console.warn('Erro ao carregar preços:', error);
        prices = [];
        
        // Atualiza o select mesmo em caso de erro
        const priceSelect = document.getElementById('priceSelect');
        if (priceSelect) {
            priceSelect.innerHTML = '<option value="">Erro ao carregar preços</option>';
        }
    }
}

async function loadSubscriptions() {
    try {
        document.getElementById('loadingSubscriptions').style.display = 'block';
        document.getElementById('subscriptionsList').style.display = 'none';
        
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('limit', pageSize);
        
        const statusFilter = document.getElementById('statusFilter')?.value;
        if (statusFilter) {
            params.append('status', statusFilter);
        }
        
        const customerFilter = document.getElementById('customerFilter')?.value.trim();
        if (customerFilter) {
            params.append('customer', customerFilter);
        }
        
        const response = await apiRequest('/v1/subscriptions?' + params.toString(), {
            cacheTTL: 10000
        });
        
        subscriptions = response.data || [];
        paginationMeta = response.meta || {};
        
        // ✅ DEBUG: Log para verificar dados recebidos
        console.log('Assinaturas recebidas:', subscriptions);
        if (subscriptions.length > 0) {
            console.log('Primeira assinatura (exemplo):', subscriptions[0]);
            console.log('Amount da primeira:', subscriptions[0].amount, 'Tipo:', typeof subscriptions[0].amount);
        }
        
        updateStats();
        renderSubscriptions();
        renderPagination();
    } catch (error) {
        showAlert('Erro ao carregar assinaturas: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingSubscriptions').style.display = 'none';
        document.getElementById('subscriptionsList').style.display = 'block';
    }
}

function updateStats() {
    const countBadge = document.getElementById('subscriptionsCountBadge');
    const total = paginationMeta.total || subscriptions.length;
    
    if (countBadge) {
        countBadge.textContent = formatNumber(total);
    }
    
    // Se meta tiver estatísticas por status, usa elas (mais preciso)
    if (paginationMeta.stats) {
        document.getElementById('totalSubscriptions').textContent = formatNumber(paginationMeta.stats.total || total);
        document.getElementById('activeSubscriptions').textContent = formatNumber(paginationMeta.stats.active || 0);
        document.getElementById('trialingSubscriptions').textContent = formatNumber(paginationMeta.stats.trialing || 0);
        document.getElementById('canceledSubscriptions').textContent = formatNumber(paginationMeta.stats.canceled || 0);
    } else {
        // Fallback: conta apenas da página atual (aproximado)
        const active = subscriptions.filter(s => s.status === 'active').length;
        const trialing = subscriptions.filter(s => s.status === 'trialing').length;
        const canceled = subscriptions.filter(s => s.status === 'canceled').length;
        
        document.getElementById('totalSubscriptions').textContent = formatNumber(total);
        document.getElementById('activeSubscriptions').textContent = formatNumber(active);
        document.getElementById('trialingSubscriptions').textContent = formatNumber(trialing);
        document.getElementById('canceledSubscriptions').textContent = formatNumber(canceled);
    }
}

function renderSubscriptions() {
    const tbody = document.getElementById('subscriptionsTableBody');
    const emptyState = document.getElementById('emptyState');
    
    if (subscriptions.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    tbody.innerHTML = subscriptions.map(sub => {
        // ✅ CORREÇÃO: Para administradores SaaS, usa customer_name diretamente
        // Para clínicas, busca no array customers
        let customerName = null;
        const customerId = sub.customer_id || sub.customer || null;
        
        if (sub.customer_name) {
            // Administrador SaaS - usa nome direto do subscription
            customerName = escapeHtml(sub.customer_name);
        } else {
            // Clínica - busca no array customers
            const customer = customers.find(c => c.id === customerId);
            customerName = customer ? escapeHtml(customer.name || customer.email) : (customerId ? `ID: ${customerId}` : 'Cliente não encontrado');
        }
        
        const statusBadge = {
            'active': 'bg-success',
            'canceled': 'bg-danger',
            'past_due': 'bg-warning',
            'trialing': 'bg-info',
            'incomplete': 'bg-secondary'
        }[sub.status] || 'bg-secondary';
        
        // Sanitiza dados para prevenir XSS
        const status = escapeHtml(sub.status);
        const planName = escapeHtml(sub.plan_name || sub.price_id || '-');
        
        const statusText = {
            'active': 'Ativa',
            'canceled': 'Cancelada',
            'past_due': 'Vencida',
            'trialing': 'Em Trial',
            'incomplete': 'Incompleta'
        }[sub.status] || sub.status;
        
        return `
            <tr>
                <td>
                    <div>
                        <strong>#${sub.id || sub.stripe_subscription_id || '-'}</strong>
                        ${sub.stripe_subscription_id && sub.stripe_subscription_id !== sub.id ? `
                            <br><small class="text-muted"><code>${escapeHtml(sub.stripe_subscription_id)}</code></small>
                        ` : ''}
                    </div>
                </td>
                <td>
                    <div>
                        <div class="fw-medium">${customerName || 'Cliente não encontrado'}</div>
                        ${customerId ? `<small class="text-muted">ID: ${escapeHtml(customerId)}</small>` : ''}
                    </div>
                </td>
                <td>
                    <span class="badge ${statusBadge}">${statusText}</span>
                </td>
                <td>
                    <div class="fw-medium">${planName}</div>
                    ${sub.price_id && sub.price_id !== planName ? `
                        <small class="text-muted"><code>${escapeHtml(sub.price_id)}</code></small>
                    ` : ''}
                </td>
                <td>
                    <div class="fw-medium">
                        ${sub.amount !== undefined && sub.amount !== null ? formatCurrencyReais(parseFloat(sub.amount), sub.currency || 'BRL') : '-'}
                    </div>
                    ${sub.interval ? `
                        <small class="text-muted">/${sub.interval === 'month' ? 'mês' : sub.interval === 'year' ? 'ano' : sub.interval}</small>
                    ` : ''}
                </td>
                <td>
                    ${sub.current_period_end ? `
                        <div>${formatDate(sub.current_period_end)}</div>
                        <small class="text-muted">${formatTimeUntil(sub.current_period_end)}</small>
                    ` : '-'}
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="/subscription-details?id=${sub.id}" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPagination() {
    const container = document.getElementById('paginationContainer');
    if (!paginationMeta.total_pages || paginationMeta.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    const pages = [];
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(paginationMeta.total_pages, startPage + maxVisible - 1);
    
    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    // Botão Anterior
    pages.push(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Anterior</a>
        </li>
    `);
    
    // Primeira página
    if (startPage > 1) {
        pages.push(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(1); return false;">1</a>
            </li>
        `);
        if (startPage > 2) {
            pages.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
    }
    
    // Páginas visíveis
    for (let i = startPage; i <= endPage; i++) {
        pages.push(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
            </li>
        `);
    }
    
    // Última página
    if (endPage < paginationMeta.total_pages) {
        if (endPage < paginationMeta.total_pages - 1) {
            pages.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
        pages.push(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${paginationMeta.total_pages}); return false;">${paginationMeta.total_pages}</a>
            </li>
        `);
    }
    
    // Botão Próximo
    pages.push(`
        <li class="page-item ${currentPage === paginationMeta.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Próximo</a>
        </li>
    `);
    
    container.innerHTML = `
        <nav>
            <ul class="pagination justify-content-center mb-0">
                ${pages.join('')}
            </ul>
        </nav>
        <div class="text-center text-muted mt-2">
            Mostrando ${((currentPage - 1) * pageSize) + 1} - ${Math.min(currentPage * pageSize, paginationMeta.total || 0)} de ${paginationMeta.total || 0} assinaturas
        </div>
    `;
}

function changePage(page) {
    if (page < 1 || page > (paginationMeta.total_pages || 1)) return;
    currentPage = page;
    loadSubscriptions();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function formatTimeUntil(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diff = date - now;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days < 0) return 'Vencida';
    if (days === 0) return 'Hoje';
    if (days === 1) return 'Amanhã';
    if (days < 7) return `Em ${days} dias`;
    if (days < 30) return `Em ${Math.floor(days / 7)} semanas`;
    return `Em ${Math.floor(days / 30)} meses`;
}

function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num);
}

</script>

