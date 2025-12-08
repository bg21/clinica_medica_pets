<?php
/**
 * View de Especialidades da Clínica
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-briefcase text-primary"></i>
                Especialidades
            </h1>
            <p class="text-muted mb-0">Gerencie as especialidades atendidas pela clínica e seus preços</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSpecialtyModal">
            <i class="bi bi-plus-circle"></i> Nova Especialidade
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Nome da especialidade...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="active">Ativos</option>
                        <option value="inactive">Inativos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordenar por</label>
                    <select class="form-select" id="sortFilter">
                        <option value="sort_order">Ordem</option>
                        <option value="name">Nome</option>
                        <option value="created_at">Data de Criação</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadSpecialties()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Especialidades -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Especialidades
            </h5>
            <span class="badge bg-primary" id="specialtiesCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingSpecialties" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando especialidades...</p>
            </div>
            <div id="specialtiesList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Preço</th>
                                <th>Status</th>
                                <th>Ordem</th>
                                <th>Criado em</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="specialtiesTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-briefcase fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma especialidade encontrada</h5>
                    <p class="text-muted">Comece criando sua primeira especialidade.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createSpecialtyModal">
                        <i class="bi bi-plus-circle"></i> Criar Especialidade
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Especialidade -->
<div class="modal fade" id="createSpecialtyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-briefcase me-2"></i>
                    Nova Especialidade
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createSpecialtyForm" novalidate>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Campos marcados com <span class="text-danger">*</span> são obrigatórios.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tag me-1"></i>
                            Nome da Especialidade <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            name="name" 
                            id="specialtyName"
                            placeholder="Ex: Clínica Geral, Cirurgia, Dermatologia"
                            required
                            maxlength="100">
                        <div class="invalid-feedback">
                            Por favor, informe o nome da especialidade.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text me-1"></i>
                            Descrição
                        </label>
                        <textarea 
                            class="form-control" 
                            name="description" 
                            id="specialtyDescription"
                            rows="3"
                            placeholder="Descrição opcional da especialidade..."
                            maxlength="500"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-currency-dollar me-1"></i>
                            Preço Padrão
                        </label>
                        <select class="form-select" name="price_id" id="specialtyPriceId">
                            <option value="">Selecione um preço (opcional)...</option>
                        </select>
                        <small class="form-text text-muted">
                            Preço padrão do Stripe para esta especialidade. Pode ser alterado posteriormente.
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-sort-numeric-down me-1"></i>
                                Ordem de Exibição
                            </label>
                            <input 
                                type="number" 
                                class="form-control" 
                                name="sort_order" 
                                id="specialtySortOrder"
                                value="0"
                                min="0">
                            <small class="form-text text-muted">
                                Menor número aparece primeiro na lista
                            </small>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="specialtyIsActive" checked>
                                <label class="form-check-label" for="specialtyIsActive">
                                    Especialidade Ativa
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitSpecialtyBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Criar Especialidade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Especialidade -->
<div class="modal fade" id="editSpecialtyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>
                    Editar Especialidade
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSpecialtyForm" novalidate>
                <input type="hidden" id="editSpecialtyId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tag me-1"></i>
                            Nome da Especialidade <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            name="name" 
                            id="editSpecialtyName"
                            required
                            maxlength="100">
                        <div class="invalid-feedback">
                            Por favor, informe o nome da especialidade.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text me-1"></i>
                            Descrição
                        </label>
                        <textarea 
                            class="form-control" 
                            name="description" 
                            id="editSpecialtyDescription"
                            rows="3"
                            maxlength="500"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-currency-dollar me-1"></i>
                            Preço Padrão
                        </label>
                        <select class="form-select" name="price_id" id="editSpecialtyPriceId">
                            <option value="">Selecione um preço (opcional)...</option>
                        </select>
                        <small class="form-text text-muted">
                            Preço padrão do Stripe para esta especialidade.
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-sort-numeric-down me-1"></i>
                                Ordem de Exibição
                            </label>
                            <input 
                                type="number" 
                                class="form-control" 
                                name="sort_order" 
                                id="editSpecialtySortOrder"
                                min="0">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="editSpecialtyIsActive">
                                <label class="form-check-label" for="editSpecialtyIsActive">
                                    Especialidade Ativa
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitEditSpecialtyBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let specialties = [];
let prices = [];

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatCurrency(amount, currency = 'brl') {
    if (!amount) return 'Não definido';
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: currency.toUpperCase()
    }).format(amount / 100);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;
    
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    alertContainer.innerHTML = alertHtml;
    
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

async function loadPrices() {
    try {
        // Busca apenas preços ativos e limita a 100 para melhor performance
        const response = await apiRequest('/v1/prices?active=true&limit=100', {
            cacheTTL: 60000
        });
        
        if (response.data && Array.isArray(response.data)) {
            prices = response.data;
            populatePriceSelects();
        } else {
            console.warn('Resposta de preços em formato inesperado:', response);
        }
    } catch (error) {
        console.error('Erro ao carregar preços:', error);
        showAlert('Erro ao carregar preços do Stripe. Verifique sua conexão.', 'warning');
    }
}

function populatePriceSelects() {
    const createSelect = document.getElementById('specialtyPriceId');
    const editSelect = document.getElementById('editSpecialtyPriceId');
    
    const populateSelect = (select) => {
        if (!select) return;
        
        // Limpa opções existentes (exceto a primeira)
        while (select.options.length > 1) {
            select.remove(1);
        }
        
        prices.forEach(price => {
            const option = document.createElement('option');
            option.value = price.id;
            const amount = price.unit_amount || 0;
            const currency = (price.currency || 'brl').toLowerCase();
            const productName = price.product?.name || price.product_name || 'Produto';
            option.textContent = `${productName} - ${formatCurrency(amount, currency)}`;
            select.appendChild(option);
        });
    };
    
    populateSelect(createSelect);
    populateSelect(editSelect);
}

async function loadSpecialties() {
    try {
        const loadingEl = document.getElementById('loadingSpecialties');
        const listEl = document.getElementById('specialtiesList');
        const emptyState = document.getElementById('emptyState');
        const tableBody = document.getElementById('specialtiesTableBody');
        
        if (loadingEl) loadingEl.style.display = 'block';
        if (listEl) listEl.style.display = 'none';
        if (emptyState) emptyState.style.display = 'none';
        
        const search = document.getElementById('searchInput')?.value || '';
        const status = document.getElementById('statusFilter')?.value || '';
        const sort = document.getElementById('sortFilter')?.value || 'sort_order';
        
        let url = '/v1/clinic/specialties';
        const params = new URLSearchParams();
        if (status === 'active') params.append('active', 'true');
        if (params.toString()) url += '?' + params.toString();
        
        const response = await apiRequest(url, { cacheTTL: 30000 });
        
        console.log('Resposta da API de especialidades:', response);
        
        if (loadingEl) loadingEl.style.display = 'none';
        
        if (response.data && Array.isArray(response.data)) {
            specialties = response.data;
            console.log('Especialidades carregadas:', specialties.length, specialties);
            
            // Aplica filtros locais
            let filtered = specialties;
            if (search) {
                const searchLower = search.toLowerCase();
                filtered = filtered.filter(s => 
                    (s.name || '').toLowerCase().includes(searchLower) ||
                    (s.description || '').toLowerCase().includes(searchLower)
                );
            }
            
            // Ordena
            filtered.sort((a, b) => {
                if (sort === 'name') {
                    return (a.name || '').localeCompare(b.name || '');
                } else if (sort === 'created_at') {
                    return new Date(b.created_at || 0) - new Date(a.created_at || 0);
                } else {
                    return (a.sort_order || 0) - (b.sort_order || 0);
                }
            });
            
            if (filtered.length === 0) {
                if (emptyState) emptyState.style.display = 'block';
            } else {
                if (listEl) listEl.style.display = 'block';
                renderSpecialties(filtered);
            }
            
            const badge = document.getElementById('specialtiesCountBadge');
            if (badge) badge.textContent = filtered.length;
        } else {
            if (emptyState) emptyState.style.display = 'block';
        }
    } catch (error) {
        console.error('Erro ao carregar especialidades:', error);
        showAlert('Erro ao carregar especialidades: ' + (error.message || 'Erro desconhecido'), 'danger');
        const loadingEl = document.getElementById('loadingSpecialties');
        if (loadingEl) loadingEl.style.display = 'none';
    }
}

function renderSpecialties(specialtiesList) {
    console.log('renderSpecialties chamado com', specialtiesList.length, 'especialidades');
    const tableBody = document.getElementById('specialtiesTableBody');
    if (!tableBody) {
        console.error('Elemento specialtiesTableBody não encontrado!');
        return;
    }
    
    tableBody.innerHTML = '';
    
    if (specialtiesList.length === 0) {
        console.warn('Nenhuma especialidade para renderizar');
        return;
    }
    
    specialtiesList.forEach(specialty => {
        console.log('Renderizando especialidade:', specialty);
        console.log('Price ID da especialidade:', specialty.price_id);
        console.log('Preços disponíveis:', prices.length);
        
        const price = specialty.price_id ? prices.find(p => p.id === specialty.price_id) : null;
        console.log('Preço encontrado:', price ? price.id : 'Nenhum');
        
        const priceText = price ? formatCurrency(price.unit_amount || 0, price.currency || 'brl') : 'Não definido';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${escapeHtml(specialty.name || '')}</strong></td>
            <td>${escapeHtml(specialty.description || '-')}</td>
            <td>${priceText}</td>
            <td>
                <span class="badge bg-${specialty.is_active ? 'success' : 'secondary'}">
                    ${specialty.is_active ? 'Ativo' : 'Inativo'}
                </span>
            </td>
            <td>${specialty.sort_order || 0}</td>
            <td>${formatDate(specialty.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editSpecialty(${specialty.id})" title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteSpecialty(${specialty.id})" title="Deletar">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

async function editSpecialty(id) {
    const specialty = specialties.find(s => s.id == id);
    if (!specialty) {
        showAlert('Especialidade não encontrada', 'warning');
        return;
    }
    
    document.getElementById('editSpecialtyId').value = specialty.id;
    document.getElementById('editSpecialtyName').value = specialty.name || '';
    document.getElementById('editSpecialtyDescription').value = specialty.description || '';
    document.getElementById('editSpecialtyPriceId').value = specialty.price_id || '';
    document.getElementById('editSpecialtySortOrder').value = specialty.sort_order || 0;
    document.getElementById('editSpecialtyIsActive').checked = specialty.is_active !== false;
    
    const modal = new bootstrap.Modal(document.getElementById('editSpecialtyModal'));
    modal.show();
}

async function deleteSpecialty(id) {
    const specialty = specialties.find(s => s.id == id);
    if (!specialty) {
        showAlert('Especialidade não encontrada', 'warning');
        return;
    }
    
    if (!confirm(`Tem certeza que deseja deletar a especialidade "${specialty.name}"?`)) {
        return;
    }
    
    try {
        await apiRequest(`/v1/clinic/specialties/${id}`, {
            method: 'DELETE'
        });
        
        showAlert('Especialidade deletada com sucesso', 'success');
        loadSpecialties();
    } catch (error) {
        console.error('Erro ao deletar especialidade:', error);
        showAlert('Erro ao deletar especialidade: ' + (error.message || 'Erro desconhecido'), 'danger');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadPrices();
    loadSpecialties();
    
    // Form criar
    const createForm = document.getElementById('createSpecialtyForm');
    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!createForm.checkValidity()) {
                createForm.classList.add('was-validated');
                return;
            }
            
            const submitBtn = document.getElementById('submitSpecialtyBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Criando...';
            }
            
            try {
                const formData = new FormData(createForm);
                
                const priceId = formData.get('price_id');
                const description = formData.get('description')?.trim();
                
                const data = {
                    name: formData.get('name').trim(),
                    description: description && description.length > 0 ? description : null,
                    price_id: priceId && priceId.length > 0 ? priceId : null,
                    sort_order: parseInt(formData.get('sort_order') || '0'),
                    is_active: formData.get('is_active') === 'on'
                };
                
                console.log('Dados para criar:', data);
                
                await apiRequest('/v1/clinic/specialties', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                showAlert('Especialidade criada com sucesso', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('createSpecialtyModal'));
                if (modal) modal.hide();
                createForm.reset();
                createForm.classList.remove('was-validated');
                // Recarrega preços e especialidades para garantir que os dados estão atualizados
                await loadPrices();
                await loadSpecialties();
            } catch (error) {
                console.error('Erro ao criar especialidade:', error);
                showAlert('Erro ao criar especialidade: ' + (error.message || 'Erro desconhecido'), 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Especialidade';
                }
            }
        });
    }
    
    // Form editar
    const editForm = document.getElementById('editSpecialtyForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!editForm.checkValidity()) {
                editForm.classList.add('was-validated');
                return;
            }
            
            const submitBtn = document.getElementById('submitEditSpecialtyBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(editForm);
                const id = formData.get('id');
                
                const priceId = formData.get('price_id');
                const description = formData.get('description')?.trim();
                
                const data = {
                    name: formData.get('name').trim(),
                    description: description && description.length > 0 ? description : null,
                    price_id: priceId && priceId.length > 0 ? priceId : null,
                    sort_order: parseInt(formData.get('sort_order') || '0'),
                    is_active: formData.get('is_active') === 'on'
                };
                
                console.log('Dados para atualizar:', data);
                
                await apiRequest(`/v1/clinic/specialties/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                showAlert('Especialidade atualizada com sucesso', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('editSpecialtyModal'));
                if (modal) modal.hide();
                editForm.classList.remove('was-validated');
                // Recarrega preços e especialidades para garantir que os dados estão atualizados
                await loadPrices();
                await loadSpecialties();
            } catch (error) {
                console.error('Erro ao atualizar especialidade:', error);
                showAlert('Erro ao atualizar especialidade: ' + (error.message || 'Erro desconhecido'), 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar Alterações';
                }
            }
        });
    }
    
    // Limpa formulários ao fechar modais
    const createModal = document.getElementById('createSpecialtyModal');
    if (createModal) {
        createModal.addEventListener('hidden.bs.modal', () => {
            const form = document.getElementById('createSpecialtyForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
            }
        });
    }
    
    const editModal = document.getElementById('editSpecialtyModal');
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', () => {
            const form = document.getElementById('editSpecialtyForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
            }
        });
    }
});
</script>

