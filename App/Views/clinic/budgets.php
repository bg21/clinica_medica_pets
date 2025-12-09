<?php
/**
 * View de Orçamentos
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-text text-primary"></i>
                Orçamentos
            </h1>
            <p class="text-muted mb-0">Gerencie os orçamentos da clínica</p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBudgetModal">
                <i class="bi bi-plus-circle"></i> Novo Orçamento
            </button>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="draft">Rascunho</option>
                        <option value="sent">Enviado</option>
                        <option value="accepted">Aceito</option>
                        <option value="rejected">Rejeitado</option>
                        <option value="expired">Expirado</option>
                        <option value="converted">Convertido</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Funcionário</label>
                    <select class="form-select" id="userFilter">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-control" id="searchFilter" placeholder="Buscar...">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadBudgets()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Orçamentos -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Orçamentos
            </h5>
            <span class="badge bg-primary" id="budgetsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingBudgets" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando orçamentos...</p>
            </div>
            <div id="budgetsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Funcionário</th>
                                <th>Valor Total</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="budgetsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum orçamento encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro orçamento.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createBudgetModal">
                        <i class="bi bi-plus-circle"></i> Novo Orçamento
                    </button>
                </div>
                <div id="paginationContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Orçamento -->
<div class="modal fade" id="createBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Novo Orçamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createBudgetForm" novalidate>
                <div class="modal-body">
                    <div id="budgetAlertContainer"></div>
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
                            <select class="form-select" name="customer_id" id="budgetCustomerId" required>
                                <option value="">Selecione o cliente...</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um cliente.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-event me-1"></i>
                                Validade
                            </label>
                            <input type="date" class="form-control" name="valid_until" id="budgetValidUntil">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-list-ul me-1"></i>
                            Itens do Orçamento
                        </label>
                        <div id="budgetItemsContainer">
                            <div class="budget-item mb-2 p-3 border rounded">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control form-control-sm" placeholder="Descrição" name="items[0][description]">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" class="form-control form-control-sm" placeholder="Qtd" name="items[0][quantity]" value="1" min="1" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" class="form-control form-control-sm" placeholder="Preço Unit." name="items[0][unit_price]" min="0" step="0.01">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeBudgetItem(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addBudgetItem()">
                            <i class="bi bi-plus"></i> Adicionar Item
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-currency-dollar me-1"></i>
                                Valor Total
                            </label>
                            <input type="number" class="form-control" name="total_amount" id="budgetTotalAmount" readonly value="0.00" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-info-circle me-1"></i>
                                Status
                            </label>
                            <select class="form-select" name="status" id="budgetStatus">
                                <option value="draft">Rascunho</option>
                                <option value="sent" selected>Enviado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text me-1"></i>
                            Observações
                        </label>
                        <textarea class="form-control" name="notes" id="budgetNotes" rows="3" placeholder="Observações sobre o orçamento..." maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBudgetBtn">
                        <i class="bi bi-plus-circle me-1"></i> Criar Orçamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Orçamento -->
<div class="modal fade" id="editBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>
                    Editar Orçamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBudgetForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editBudgetId">
                    <div id="editBudgetAlertContainer"></div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Validade</label>
                            <input type="date" class="form-control" name="valid_until" id="editBudgetValidUntil">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editBudgetStatus">
                                <option value="draft">Rascunho</option>
                                <option value="sent">Enviado</option>
                                <option value="accepted">Aceito</option>
                                <option value="rejected">Rejeitado</option>
                                <option value="expired">Expirado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="notes" id="editBudgetNotes" rows="3" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitEditBudgetBtn">
                        <i class="bi bi-check-circle me-1"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    let currentPage = 1;
    let budgets = [];
    let customers = [];
    let users = [];
    let budgetItemIndex = 1;
    
    // Função para mostrar alertas
    function showAlert(message, type = 'info', containerId = 'alertContainer') {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.innerHTML = '';
        container.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    // Função para formatar moeda
    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }
    
    // Função para formatar data
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }
    
    // Função para escapar HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Carrega dados iniciais
    async function loadInitialData() {
        await Promise.all([
            loadCustomers(),
            loadUsers()
        ]);
    }
    
    async function loadCustomers() {
        try {
            const response = await apiRequest('/v1/customers?limit=1000', { cacheTTL: 60000 });
            // Garante que customers é sempre um array
            const data = response.data?.data || response.data || [];
            customers = Array.isArray(data) ? data : [];
            
            const customerSelect = document.getElementById('budgetCustomerId');
            if (customerSelect) {
                customerSelect.innerHTML = '<option value="">Selecione o cliente...</option>';
                customers.forEach(customer => {
                    const option = document.createElement('option');
                    option.value = customer.id;
                    option.textContent = `${customer.name || 'Sem nome'} (${customer.email || 'Sem email'})`;
                    customerSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Erro ao carregar clientes:', error);
            customers = []; // Garante que é array mesmo em caso de erro
        }
    }
    
    async function loadUsers() {
        try {
            const response = await apiRequest('/v1/users?limit=1000', { cacheTTL: 60000 });
            // UserController retorna { users: [...], count: ... } dentro de data
            users = response.data?.users || response.data?.data || response.data || [];
            
            // Garante que é um array
            if (!Array.isArray(users)) {
                users = [];
            }
            
            const userSelect = document.getElementById('userFilter');
            if (userSelect) {
                userSelect.innerHTML = '<option value="">Todos</option>';
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name || user.email;
                    userSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Erro ao carregar usuários:', error);
            users = []; // Garante que é array mesmo em caso de erro
        }
    }
    
    // Carrega orçamentos (exposta globalmente para uso em onclick)
    const loadBudgets = async function(page = 1) {
        const loadingDiv = document.getElementById('loadingBudgets');
        const listDiv = document.getElementById('budgetsList');
        const emptyState = document.getElementById('emptyState');
        const tableBody = document.getElementById('budgetsTableBody');
        const countBadge = document.getElementById('budgetsCountBadge');
        
        if (!listDiv) return;
        
        loadingDiv.style.display = 'block';
        listDiv.style.display = 'none';
        emptyState.style.display = 'none';
        tableBody.innerHTML = '';
        
        try {
            const statusFilter = document.getElementById('statusFilter')?.value || '';
            const userFilter = document.getElementById('userFilter')?.value || '';
            const searchFilter = document.getElementById('searchFilter')?.value || '';
            
            let url = `/v1/clinic/budgets?page=${page}&limit=20`;
            if (statusFilter) url += `&status=${statusFilter}`;
            if (userFilter) url += `&user_id=${userFilter}`;
            if (searchFilter) url += `&search=${encodeURIComponent(searchFilter)}`;
            
            const response = await apiRequest(url);
            
            // Garante que budgets é sempre um array
            // ResponseHelper retorna { success: true, data: { data: [...], total: ..., page: ... } }
            const result = response.data || {};
            const data = result.data || result || [];
            budgets = Array.isArray(data) ? data : [];
            const meta = {
                total: result.total || budgets.length || 0,
                page: result.page || 1,
                limit: result.limit || 20,
                total_pages: result.total_pages || 1
            };
            
            countBadge.textContent = meta.total;
            
            if (budgets.length === 0) {
                emptyState.style.display = 'block';
                listDiv.style.display = 'none';
            } else {
                budgets.forEach(budget => {
                    const row = tableBody.insertRow();
                    const statusBadges = {
                        'draft': '<span class="badge bg-secondary">Rascunho</span>',
                        'sent': '<span class="badge bg-info">Enviado</span>',
                        'accepted': '<span class="badge bg-success">Aceito</span>',
                        'rejected': '<span class="badge bg-danger">Rejeitado</span>',
                        'expired': '<span class="badge bg-warning">Expirado</span>',
                        'converted': '<span class="badge bg-primary">Convertido</span>'
                    };
                    const statusBadge = statusBadges[budget.status] || '<span class="badge bg-secondary">' + budget.status + '</span>';
                    
                    const user = Array.isArray(users) ? users.find(u => u.id === budget.created_by_user_id) : null;
                    const customer = Array.isArray(customers) ? customers.find(c => c.id === budget.customer_id) : null;
                    
                    row.innerHTML = `
                        <td><strong>${escapeHtml(budget.budget_number || '-')}</strong></td>
                        <td>${escapeHtml(customer?.name || '-')}</td>
                        <td>${escapeHtml(user?.name || '-')}</td>
                        <td>${formatCurrency(budget.total_amount || 0)}</td>
                        <td>${statusBadge}</td>
                        <td>${formatDate(budget.created_at)}</td>
                        <td>
                            <button class="btn btn-sm btn-info me-1" onclick="viewBudget(${budget.id})" data-bs-toggle="tooltip" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${budget.status !== 'converted' ? `
                                <button class="btn btn-sm btn-warning me-1" onclick="editBudget(${budget.id})" data-bs-toggle="tooltip" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                ${budget.status === 'sent' || budget.status === 'accepted' ? `
                                    <button class="btn btn-sm btn-success me-1" onclick="convertBudget(${budget.id})" data-bs-toggle="tooltip" title="Converter (Fechar)">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                ` : ''}
                            ` : ''}
                            ${budget.status !== 'converted' ? `
                                <button class="btn btn-sm btn-danger" onclick="deleteBudget(${budget.id})" data-bs-toggle="tooltip" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            ` : ''}
                        </td>
                    `;
                });
                listDiv.style.display = 'block';
                emptyState.style.display = 'none';
            }
            
            currentPage = page;
        } catch (error) {
            console.error('Erro ao carregar orçamentos:', error);
            showAlert('Erro ao carregar orçamentos. Tente novamente.', 'danger');
            emptyState.style.display = 'block';
        } finally {
            loadingDiv.style.display = 'none';
        }
    };
    
    // Expõe loadBudgets globalmente para uso em onclick
    window.loadBudgets = loadBudgets;
    
    // Adiciona item ao orçamento
    window.addBudgetItem = function() {
        const container = document.getElementById('budgetItemsContainer');
        const item = document.createElement('div');
        item.className = 'budget-item mb-2 p-3 border rounded';
        item.innerHTML = `
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm" placeholder="Descrição" name="items[${budgetItemIndex}][description]">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm" placeholder="Qtd" name="items[${budgetItemIndex}][quantity]" value="1" min="1" step="0.01" onchange="calculateBudgetTotal()">
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control form-control-sm" placeholder="Preço Unit." name="items[${budgetItemIndex}][unit_price]" min="0" step="0.01" onchange="calculateBudgetTotal()">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeBudgetItem(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(item);
        budgetItemIndex++;
    };
    
    // Remove item do orçamento
    window.removeBudgetItem = function(button) {
        button.closest('.budget-item').remove();
        calculateBudgetTotal();
    };
    
    // Calcula total do orçamento
    window.calculateBudgetTotal = function() {
        const items = document.querySelectorAll('.budget-item');
        let total = 0;
        
        items.forEach(item => {
            const quantity = parseFloat(item.querySelector('input[name*="[quantity]"]')?.value || 0);
            const unitPrice = parseFloat(item.querySelector('input[name*="[unit_price]"]')?.value || 0);
            total += quantity * unitPrice;
        });
        
        const totalInput = document.getElementById('budgetTotalAmount');
        if (totalInput) {
            totalInput.value = total.toFixed(2);
        }
    };
    
    // Form criar orçamento
    const createForm = document.getElementById('createBudgetForm');
    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!createForm.checkValidity()) {
                createForm.classList.add('was-validated');
                return;
            }
            
            const submitBtn = document.getElementById('submitBudgetBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Criando...';
            }
            
            try {
                const formData = new FormData(createForm);
                
                // Coleta itens
                const items = [];
                document.querySelectorAll('.budget-item').forEach(item => {
                    const description = item.querySelector('input[name*="[description]"]')?.value;
                    const quantity = parseFloat(item.querySelector('input[name*="[quantity]"]')?.value || 0);
                    const unitPrice = parseFloat(item.querySelector('input[name*="[unit_price]"]')?.value || 0);
                    
                    if (description && quantity > 0 && unitPrice > 0) {
                        items.push({
                            description: description,
                            quantity: quantity,
                            unit_price: unitPrice,
                            total: quantity * unitPrice
                        });
                    }
                });
                
                const data = {
                    customer_id: parseInt(formData.get('customer_id')),
                    created_by_user_id: (USER && USER.id) ? parseInt(USER.id) : 1,
                    total_amount: parseFloat(formData.get('total_amount') || 0),
                    status: formData.get('status') || 'draft',
                    items: items.length > 0 ? items : null
                };
                
                if (formData.get('valid_until')) {
                    data.valid_until = formData.get('valid_until');
                }
                
                if (formData.get('notes')) {
                    data.notes = formData.get('notes');
                }
                
                const response = await apiRequest('/v1/clinic/budgets', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                showAlert('Orçamento criado com sucesso!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('createBudgetModal'));
                if (modal) modal.hide();
                createForm.reset();
                createForm.classList.remove('was-validated');
                document.getElementById('budgetItemsContainer').innerHTML = '';
                budgetItemIndex = 1;
                addBudgetItem();
                loadBudgets();
            } catch (error) {
                showAlert(error.message || 'Erro ao criar orçamento. Tente novamente.', 'danger', 'budgetAlertContainer');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Criar Orçamento';
                }
            }
        });
    }
    
    // Função para converter orçamento
    window.convertBudget = async function(budgetId) {
        if (!confirm('Tem certeza que deseja converter este orçamento? Isso criará uma comissão automaticamente.')) {
            return;
        }
        
        try {
            const response = await apiRequest(`/v1/clinic/budgets/${budgetId}/convert`, {
                method: 'POST',
                body: JSON.stringify({})
            });
            
            showAlert('Orçamento convertido com sucesso! Comissão criada automaticamente.', 'success');
            loadBudgets();
        } catch (error) {
            showAlert(error.message || 'Erro ao converter orçamento. Tente novamente.', 'danger');
        }
    };
    
    // Função para editar orçamento
    window.editBudget = async function(budgetId) {
        try {
            const response = await apiRequest(`/v1/clinic/budgets/${budgetId}`);
            const budget = response.data;
            
            document.getElementById('editBudgetId').value = budget.id;
            document.getElementById('editBudgetValidUntil').value = budget.valid_until ? budget.valid_until.split(' ')[0] : '';
            document.getElementById('editBudgetStatus').value = budget.status;
            document.getElementById('editBudgetNotes').value = budget.notes || '';
            
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editBudgetModal'));
            modal.show();
        } catch (error) {
            showAlert('Erro ao carregar orçamento. Tente novamente.', 'danger');
        }
    };
    
    // Form editar orçamento
    const editForm = document.getElementById('editBudgetForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!editForm.checkValidity()) {
                editForm.classList.add('was-validated');
                return;
            }
            
            const budgetId = document.getElementById('editBudgetId').value;
            const submitBtn = document.getElementById('submitEditBudgetBtn');
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(editForm);
                const data = {};
                
                if (formData.get('valid_until')) {
                    data.valid_until = formData.get('valid_until');
                }
                if (formData.get('status')) {
                    data.status = formData.get('status');
                }
                if (formData.get('notes')) {
                    data.notes = formData.get('notes');
                }
                
                await apiRequest(`/v1/clinic/budgets/${budgetId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                showAlert('Orçamento atualizado com sucesso!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('editBudgetModal'));
                if (modal) modal.hide();
                editForm.reset();
                editForm.classList.remove('was-validated');
                loadBudgets();
            } catch (error) {
                showAlert(error.message || 'Erro ao atualizar orçamento. Tente novamente.', 'danger', 'editBudgetAlertContainer');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar Alterações';
                }
            }
        });
    }
    
    // Função para visualizar orçamento
    window.viewBudget = async function(budgetId) {
        try {
            const response = await apiRequest(`/v1/clinic/budgets/${budgetId}`);
            const budget = response.data;
            
            let itemsHtml = '';
            if (budget.items) {
                const items = typeof budget.items === 'string' ? JSON.parse(budget.items) : budget.items;
                items.forEach(item => {
                    itemsHtml += `
                        <tr>
                            <td>${escapeHtml(item.description || '-')}</td>
                            <td>${item.quantity || 0}</td>
                            <td>${formatCurrency(item.unit_price || 0)}</td>
                            <td><strong>${formatCurrency(item.total || 0)}</strong></td>
                        </tr>
                    `;
                });
            }
            
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Orçamento ${escapeHtml(budget.budget_number)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Cliente:</strong> ${escapeHtml((Array.isArray(customers) ? customers.find(c => c.id === budget.customer_id) : null)?.name || '-')}
                                </div>
                                <div class="col-md-6">
                                    <strong>Status:</strong> ${budget.status}
                                </div>
                            </div>
                            ${itemsHtml ? `
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Descrição</th>
                                            <th>Qtd</th>
                                            <th>Preço Unit.</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>${itemsHtml}</tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Total</th>
                                            <th>${formatCurrency(budget.total_amount || 0)}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            ` : ''}
                            ${budget.notes ? `<p><strong>Observações:</strong> ${escapeHtml(budget.notes)}</p>` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            modal.addEventListener('hidden.bs.modal', () => modal.remove());
        } catch (error) {
            showAlert('Erro ao carregar orçamento. Tente novamente.', 'danger');
        }
    };
    
    // Função para deletar orçamento
    window.deleteBudget = async function(budgetId) {
        if (!confirm('Tem certeza que deseja excluir este orçamento?')) {
            return;
        }
        
        try {
            await apiRequest(`/v1/clinic/budgets/${budgetId}`, {
                method: 'DELETE'
            });
            
            showAlert('Orçamento excluído com sucesso!', 'success');
            loadBudgets();
        } catch (error) {
            showAlert(error.message || 'Erro ao excluir orçamento. Tente novamente.', 'danger');
        }
    };
    
    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        loadInitialData();
        loadBudgets();
        
        // Event listeners para filtros
        document.getElementById('statusFilter')?.addEventListener('change', () => loadBudgets(1));
        document.getElementById('userFilter')?.addEventListener('change', () => loadBudgets(1));
        document.getElementById('searchFilter')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadBudgets(1);
        });
        
        // Adiciona primeiro item ao criar orçamento
        const createModal = document.getElementById('createBudgetModal');
        if (createModal) {
            createModal.addEventListener('show.bs.modal', () => {
                document.getElementById('budgetItemsContainer').innerHTML = '';
                budgetItemIndex = 1;
                addBudgetItem();
            });
        }
    });
})();
</script>

