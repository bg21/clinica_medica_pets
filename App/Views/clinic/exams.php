<?php
/**
 * View de Exames
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-clipboard-pulse text-primary"></i>
                Exames
            </h1>
            <p class="text-muted mb-0">Gerencie os exames realizados na clínica</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createExamModal">
            <i class="bi bi-plus-circle"></i> Novo Exame
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="pending">Pendente</option>
                        <option value="scheduled">Agendado</option>
                        <option value="completed">Concluído</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="dateFromFilter">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="dateToFilter">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo de Exame</label>
                    <select class="form-select" id="examTypeFilter">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Profissional</label>
                    <select class="form-select" id="professionalFilter">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadExams()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Exames -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Exames
            </h5>
            <span class="badge bg-primary" id="examsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingExams" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando exames...</p>
            </div>
            <div id="examsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Data/Hora</th>
                                <th>Pet</th>
                                <th>Tutor</th>
                                <th>Tipo</th>
                                <th>Profissional</th>
                                <th>Status</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="examsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-clipboard-pulse fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum exame encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro exame.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createExamModal">
                        <i class="bi bi-plus-circle"></i> Criar Exame
                    </button>
                </div>
                <div id="paginationContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Exame -->
<div class="modal fade" id="createExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-clipboard-pulse me-2"></i>
                    Novo Exame
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createExamForm" novalidate>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Campos marcados com <span class="text-danger">*</span> são obrigatórios.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-heart-pulse me-1"></i>
                                Pet <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="pet_id" id="examPetId" required>
                                <option value="">Selecione o pet...</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um pet.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-person-badge me-1"></i>
                                Profissional
                            </label>
                            <select class="form-select" name="professional_id" id="examProfessionalId">
                                <option value="">Selecione o profissional...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-event me-1"></i>
                                Data <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="date" 
                                class="form-control" 
                                name="exam_date" 
                                id="examDate"
                                required>
                            <div class="invalid-feedback">
                                Por favor, selecione uma data.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-clock me-1"></i>
                                Hora
                            </label>
                            <input 
                                type="time" 
                                class="form-control" 
                                name="exam_time" 
                                id="examTime">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Tipo de Exame
                            </label>
                            <select class="form-select" name="exam_type_id" id="examTypeId">
                                <option value="">Selecione o tipo...</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-info-circle me-1"></i>
                                Status
                            </label>
                            <select class="form-select" name="status" id="examStatus">
                                <option value="pending">Pendente</option>
                                <option value="scheduled">Agendado</option>
                                <option value="completed">Concluído</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text me-1"></i>
                            Observações
                        </label>
                        <textarea 
                            class="form-control" 
                            name="notes" 
                            id="examNotes"
                            rows="3"
                            placeholder="Informações adicionais sobre o exame..."
                            maxlength="1000"></textarea>
                        <small class="form-text text-muted">
                            <span id="notesCounter">0</span>/1000 caracteres
                        </small>
                    </div>
                    
                    <!-- Seção de Pagamento -->
                    <div class="card bg-light mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-credit-card me-2"></i>
                                Pagamento (Opcional)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-tag me-1"></i>
                                        Preço/Serviço
                                    </label>
                                    <select class="form-select" name="price_id" id="examPriceId">
                                        <option value="">Selecione um preço (opcional)...</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Selecione um preço para criar invoice automaticamente
                                    </small>
                                </div>
                                <div class="col-md-4 mb-3 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            name="auto_charge" 
                                            id="examAutoCharge"
                                            checked>
                                        <label class="form-check-label" for="examAutoCharge">
                                            <i class="bi bi-lightning-charge me-1"></i>
                                            Cobrar automaticamente
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="client_id" id="examClientId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitExamBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Criar Exame
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Exame -->
<div class="modal fade" id="editExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-clipboard-pulse me-2"></i>
                    Editar Exame
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editExamForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editExamId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-event me-1"></i>
                                Data <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="date" 
                                class="form-control" 
                                name="exam_date" 
                                id="editExamDate"
                                required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-clock me-1"></i>
                                Hora
                            </label>
                            <input 
                                type="time" 
                                class="form-control" 
                                name="exam_time" 
                                id="editExamTime">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Tipo de Exame
                            </label>
                            <select class="form-select" name="exam_type_id" id="editExamTypeId">
                                <option value="">Selecione o tipo...</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-info-circle me-1"></i>
                                Status
                            </label>
                            <select class="form-select" name="status" id="editExamStatus">
                                <option value="pending">Pendente</option>
                                <option value="scheduled">Agendado</option>
                                <option value="completed">Concluído</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text me-1"></i>
                            Observações
                        </label>
                        <textarea 
                            class="form-control" 
                            name="notes" 
                            id="editExamNotes"
                            rows="3"
                            maxlength="1000"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-file-text me-1"></i>
                            Resultados
                        </label>
                        <textarea 
                            class="form-control" 
                            name="results" 
                            id="editExamResults"
                            rows="5"
                            placeholder="Digite os resultados do exame..."
                            maxlength="5000"></textarea>
                        <small class="form-text text-muted">
                            <span id="resultsCounter">0</span>/5000 caracteres
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitEditExamBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Salvar Alterações
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
    let exams = [];
    let pets = [];
    let professionals = [];
    let examTypes = [];
    let prices = [];
    
    // Função para mostrar alertas
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        alertContainer.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    // Função para formatar data
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }
    
    // Função para formatar data e hora
    function formatDateTime(dateString, timeString) {
        if (!dateString) return '-';
        let result = formatDate(dateString);
        if (timeString) {
            const time = timeString.substring(0, 5);
            result += ` ${time}`;
        }
        return result;
    }
    
    // Função para obter badge de status
    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge bg-warning">Pendente</span>',
            'scheduled': '<span class="badge bg-info">Agendado</span>',
            'completed': '<span class="badge bg-success">Concluído</span>',
            'cancelled': '<span class="badge bg-danger">Cancelado</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
    }
    
    // Carregar exames
    window.loadExams = async function(page = 1) {
        currentPage = page;
        const loadingDiv = document.getElementById('loadingExams');
        const examsList = document.getElementById('examsList');
        const tableBody = document.getElementById('examsTableBody');
        const emptyState = document.getElementById('emptyState');
        const countBadge = document.getElementById('examsCountBadge');
        
        if (loadingDiv) loadingDiv.style.display = 'block';
        if (examsList) examsList.style.display = 'none';
        if (emptyState) emptyState.style.display = 'none';
        
        try {
            const params = new URLSearchParams({
                page: page,
                limit: 20
            });
            
            const statusFilter = document.getElementById('statusFilter')?.value;
            if (statusFilter) params.append('status', statusFilter);
            
            const dateFrom = document.getElementById('dateFromFilter')?.value;
            if (dateFrom) params.append('date_from', dateFrom);
            
            const dateTo = document.getElementById('dateToFilter')?.value;
            if (dateTo) params.append('date_to', dateTo);
            
            const examTypeFilter = document.getElementById('examTypeFilter')?.value;
            if (examTypeFilter) params.append('exam_type_id', examTypeFilter);
            
            const professionalFilter = document.getElementById('professionalFilter')?.value;
            if (professionalFilter) params.append('professional_id', professionalFilter);
            
            const response = await apiRequest(`/v1/clinic/exams?${params.toString()}`);
            
            exams = response.data || [];
            const meta = response.meta || {};
            
            if (countBadge) countBadge.textContent = meta.total || 0;
            
            if (loadingDiv) loadingDiv.style.display = 'none';
            if (examsList) examsList.style.display = 'block';
            
            if (exams.length === 0) {
                if (emptyState) emptyState.style.display = 'block';
                if (tableBody) tableBody.innerHTML = '';
            } else {
                if (emptyState) emptyState.style.display = 'none';
                if (tableBody) {
                    tableBody.innerHTML = exams.map(exam => {
                        const petName = exam.pet?.name || 'N/A';
                        const customerName = exam.customer?.name || 'N/A';
                        const examTypeName = exam.exam_type?.name || '-';
                        const professionalName = exam.professional?.name || '-';
                        
                        return `
                            <tr>
                                <td>${formatDateTime(exam.exam_date, exam.exam_time)}</td>
                                <td>${petName}</td>
                                <td>${customerName}</td>
                                <td>${examTypeName}</td>
                                <td>${professionalName}</td>
                                <td>${getStatusBadge(exam.status)}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editExam(${exam.id})" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        ${exam.status !== 'completed' ? `
                                        <button class="btn btn-outline-success" onclick="completeExam(${exam.id})" title="Marcar como concluído">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        ` : ''}
                                        <button class="btn btn-outline-danger" onclick="deleteExam(${exam.id})" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            }
            
            // Paginação
            const paginationContainer = document.getElementById('paginationContainer');
            if (paginationContainer && meta.total_pages > 1) {
                let pagination = '<nav><ul class="pagination justify-content-center">';
                
                if (meta.page > 1) {
                    pagination += `<li class="page-item"><a class="page-link" href="#" onclick="loadExams(${meta.page - 1}); return false;">Anterior</a></li>`;
                }
                
                for (let i = 1; i <= meta.total_pages; i++) {
                    if (i === meta.page) {
                        pagination += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                    } else {
                        pagination += `<li class="page-item"><a class="page-link" href="#" onclick="loadExams(${i}); return false;">${i}</a></li>`;
                    }
                }
                
                if (meta.page < meta.total_pages) {
                    pagination += `<li class="page-item"><a class="page-link" href="#" onclick="loadExams(${meta.page + 1}); return false;">Próxima</a></li>`;
                }
                
                pagination += '</ul></nav>';
                paginationContainer.innerHTML = pagination;
            } else if (paginationContainer) {
                paginationContainer.innerHTML = '';
            }
        } catch (error) {
            console.error('Erro ao carregar exames:', error);
            showAlert('Erro ao carregar exames. Tente novamente.', 'danger');
            if (loadingDiv) loadingDiv.style.display = 'none';
        }
    };
    
    // Carregar dados iniciais
    async function loadInitialData() {
        await Promise.all([
            loadPets(),
            loadProfessionals(),
            loadExamTypes(),
            loadPrices()
        ]);
    }
    
    async function loadPets() {
        try {
            const response = await apiRequest('/v1/clinic/pets?limit=1000', { cacheTTL: 60000 });
            pets = response.data || [];
            
            const petSelects = ['examPetId'];
            petSelects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    select.innerHTML = '<option value="">Selecione o pet...</option>';
                    pets.forEach(pet => {
                        const option = document.createElement('option');
                        option.value = pet.id;
                        option.textContent = `${pet.name || 'Sem nome'} (${pet.species || 'N/A'})`;
                        option.dataset.customerId = pet.customer_id;
                        select.appendChild(option);
                    });
                }
            });
            
            // Quando pet é selecionado, preenche client_id
            const examPetId = document.getElementById('examPetId');
            if (examPetId) {
                examPetId.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const customerId = selectedOption.dataset.customerId;
                    const clientIdInput = document.getElementById('examClientId');
                    if (clientIdInput && customerId) {
                        clientIdInput.value = customerId;
                    }
                });
            }
        } catch (error) {
            console.error('Erro ao carregar pets:', error);
        }
    }
    
    async function loadProfessionals() {
        try {
            const response = await apiRequest('/v1/clinic/professionals/active', { cacheTTL: 60000 });
            professionals = response.data || [];
            
            const professionalSelects = ['examProfessionalId', 'professionalFilter'];
            professionalSelects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    const currentValue = select.value;
                    select.innerHTML = '<option value="">Selecione o profissional...</option>';
                    professionals.forEach(professional => {
                        const option = document.createElement('option');
                        option.value = professional.id;
                        option.textContent = `${professional.name || 'Sem nome'}${professional.crmv ? ' - ' + professional.crmv : ''}`;
                        select.appendChild(option);
                    });
                    if (currentValue) select.value = currentValue;
                }
            });
        } catch (error) {
            console.error('Erro ao carregar profissionais:', error);
        }
    }
    
    async function loadExamTypes() {
        try {
            const response = await apiRequest('/v1/clinic/exam-types', { cacheTTL: 60000 });
            examTypes = response.data || [];
            
            const examTypeSelects = ['examTypeId', 'editExamTypeId', 'examTypeFilter'];
            examTypeSelects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    const currentValue = select.value;
                    select.innerHTML = '<option value="">Selecione o tipo...</option>';
                    examTypes.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id;
                        option.textContent = type.name;
                        select.appendChild(option);
                    });
                    if (currentValue) select.value = currentValue;
                }
            });
        } catch (error) {
            console.error('Erro ao carregar tipos de exame:', error);
        }
    }
    
    async function loadPrices() {
        try {
            const response = await apiRequest('/v1/prices?active=true&limit=100', { cacheTTL: 60000 });
            prices = response.data?.prices || response.data || [];
            
            const priceSelect = document.getElementById('examPriceId');
            if (priceSelect) {
                priceSelect.innerHTML = '<option value="">Selecione um preço (opcional)...</option>';
                prices.forEach(price => {
                    const option = document.createElement('option');
                    option.value = price.id;
                    const amount = (price.unit_amount / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                    const productName = price.product?.name || 'Produto';
                    option.textContent = `${productName} - ${amount}`;
                    priceSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Erro ao carregar preços:', error);
        }
    }
    
    // Form criar exame
    const createForm = document.getElementById('createExamForm');
    const submitBtn = document.getElementById('submitExamBtn');
    
    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!createForm.checkValidity()) {
                createForm.classList.add('was-validated');
                return;
            }
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Criando...';
            }
            
            try {
                const formData = new FormData(createForm);
                const data = {
                    pet_id: parseInt(formData.get('pet_id')),
                    client_id: parseInt(formData.get('client_id')),
                    exam_date: formData.get('exam_date'),
                    status: formData.get('status') || 'pending'
                };
                
                const examTime = formData.get('exam_time');
                if (examTime) data.exam_time = examTime;
                
                const professionalId = formData.get('professional_id');
                if (professionalId) data.professional_id = parseInt(professionalId);
                
                const examTypeId = formData.get('exam_type_id');
                if (examTypeId) data.exam_type_id = parseInt(examTypeId);
                
                const notes = formData.get('notes');
                if (notes && notes.trim()) data.notes = notes.trim();
                
                const priceId = formData.get('price_id');
                if (priceId && priceId.trim()) {
                    data.price_id = priceId.trim();
                    data.auto_charge = formData.get('auto_charge') === 'on';
                }
                
                await apiRequest('/v1/clinic/exams', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                showAlert('Exame criado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('createExamModal')).hide();
                loadExams();
            } catch (error) {
                showAlert(error.message || 'Erro ao criar exame. Tente novamente.', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Exame';
                }
            }
        });
    }
    
    // Form editar exame
    const editForm = document.getElementById('editExamForm');
    const submitEditBtn = document.getElementById('submitEditExamBtn');
    
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!editForm.checkValidity()) {
                editForm.classList.add('was-validated');
                return;
            }
            
            const examId = document.getElementById('editExamId').value;
            
            if (submitEditBtn) {
                submitEditBtn.disabled = true;
                submitEditBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(editForm);
                const data = {
                    exam_date: formData.get('exam_date'),
                    status: formData.get('status')
                };
                
                const examTime = formData.get('exam_time');
                if (examTime) data.exam_time = examTime;
                
                const examTypeId = formData.get('exam_type_id');
                if (examTypeId) {
                    data.exam_type_id = parseInt(examTypeId);
                } else {
                    data.exam_type_id = null;
                }
                
                const notes = formData.get('notes');
                if (notes && notes.trim()) {
                    data.notes = notes.trim();
                }
                
                const results = formData.get('results');
                if (results && results.trim()) {
                    data.results = results.trim();
                }
                
                await apiRequest(`/v1/clinic/exams/${examId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                showAlert('Exame atualizado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editExamModal')).hide();
                loadExams();
            } catch (error) {
                showAlert(error.message || 'Erro ao atualizar exame. Tente novamente.', 'danger');
            } finally {
                if (submitEditBtn) {
                    submitEditBtn.disabled = false;
                    submitEditBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar Alterações';
                }
            }
        });
    }
    
    // Funções globais
    window.editExam = async function(examId) {
        try {
            const response = await apiRequest(`/v1/clinic/exams/${examId}`);
            const exam = response.data;
            
            document.getElementById('editExamId').value = exam.id;
            document.getElementById('editExamDate').value = exam.exam_date;
            document.getElementById('editExamTime').value = exam.exam_time || '';
            document.getElementById('editExamStatus').value = exam.status;
            document.getElementById('editExamNotes').value = exam.notes || '';
            document.getElementById('editExamResults').value = exam.results || '';
            
            if (exam.exam_type_id) {
                document.getElementById('editExamTypeId').value = exam.exam_type_id;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('editExamModal'));
            modal.show();
        } catch (error) {
            showAlert('Erro ao carregar exame. Tente novamente.', 'danger');
        }
    };
    
    window.completeExam = async function(examId) {
        if (!confirm('Deseja marcar este exame como concluído?')) return;
        
        try {
            await apiRequest(`/v1/clinic/exams/${examId}`, {
                method: 'PUT',
                body: JSON.stringify({ status: 'completed' })
            });
            showAlert('Exame marcado como concluído!', 'success');
            loadExams();
        } catch (error) {
            showAlert('Erro ao atualizar exame. Tente novamente.', 'danger');
        }
    };
    
    window.deleteExam = async function(examId) {
        if (!confirm('Tem certeza que deseja excluir este exame?')) return;
        
        try {
            await apiRequest(`/v1/clinic/exams/${examId}`, {
                method: 'DELETE'
            });
            showAlert('Exame excluído com sucesso!', 'success');
            loadExams();
        } catch (error) {
            showAlert('Erro ao excluir exame. Tente novamente.', 'danger');
        }
    };
    
    // Contador de caracteres
    const notesTextarea = document.getElementById('examNotes');
    const notesCounter = document.getElementById('notesCounter');
    if (notesTextarea && notesCounter) {
        notesTextarea.addEventListener('input', function() {
            notesCounter.textContent = this.value.length;
        });
    }
    
    const resultsTextarea = document.getElementById('editExamResults');
    const resultsCounter = document.getElementById('resultsCounter');
    if (resultsTextarea && resultsCounter) {
        resultsTextarea.addEventListener('input', function() {
            resultsCounter.textContent = this.value.length;
        });
    }
    
    // Reset do formulário quando a modal é fechada
    const createModal = document.getElementById('createExamModal');
    if (createModal) {
        createModal.addEventListener('hidden.bs.modal', function() {
            if (createForm) {
                createForm.reset();
                createForm.classList.remove('was-validated');
                if (notesCounter) notesCounter.textContent = '0';
            }
        });
    }
    
    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        loadInitialData();
        loadExams();
    });
})();
</script>

