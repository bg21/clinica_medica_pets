<?php
/**
 * View de Agendamentos
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-calendar-check text-primary"></i>
                Agendamentos
            </h1>
            <p class="text-muted mb-0">Gerencie os agendamentos da clínica</p>
        </div>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="window.location.href='/clinic/exams'">
                <i class="bi bi-clipboard-pulse"></i> Agendar Exame
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAppointmentModal">
                <i class="bi bi-calendar-plus"></i> Agendar Consulta
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
                        <option value="scheduled">Agendado</option>
                        <option value="confirmed">Confirmado</option>
                        <option value="completed">Concluído</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="dateFromFilter">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="dateToFilter">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadAppointments()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Agendamentos -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Agendamentos
            </h5>
            <span class="badge bg-primary" id="appointmentsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingAppointments" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando agendamentos...</p>
            </div>
            <div id="appointmentsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Data/Hora</th>
                                <th>Pet</th>
                                <th>Tutor</th>
                                <th>Profissional</th>
                                <th>Tipo</th>
                                <th>Duração</th>
                                <th>Status</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-calendar-check fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum agendamento encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro agendamento.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createAppointmentModal">
                        <i class="bi bi-plus-circle"></i> Criar Agendamento
                    </button>
                </div>
                <div id="paginationContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Agendamento -->
<div class="modal fade" id="createAppointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar-check me-2"></i>
                    Novo Agendamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createAppointmentForm" novalidate>
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
                            <select class="form-select" name="pet_id" id="appointmentPetId" required>
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
                            <select class="form-select" name="professional_id" id="appointmentProfessionalId">
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
                                name="appointment_date_date" 
                                id="appointmentDate"
                                required>
                            <div class="invalid-feedback">
                                Por favor, selecione uma data.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-clock me-1"></i>
                                Hora <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="time" 
                                class="form-control" 
                                name="appointment_date_time" 
                                id="appointmentTime"
                                required>
                            <div class="invalid-feedback">
                                Por favor, selecione uma hora.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-hourglass-split me-1"></i>
                                Duração (minutos) <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="number" 
                                class="form-control" 
                                name="duration_minutes" 
                                id="appointmentDuration"
                                placeholder="30"
                                required
                                min="15"
                                max="480"
                                step="15">
                            <small class="form-text text-muted">
                                Duração mínima: 15 minutos, máxima: 480 minutos (8 horas)
                            </small>
                            <div class="invalid-feedback">
                                A duração deve ser entre 15 e 480 minutos.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Tipo de Consulta
                            </label>
                            <select class="form-select" name="type" id="appointmentType">
                                <option value="">Selecione...</option>
                                <option value="consulta">Consulta</option>
                                <option value="cirurgia">Cirurgia</option>
                                <option value="vacinação">Vacinação</option>
                                <option value="exame">Exame</option>
                                <option value="retorno">Retorno</option>
                                <option value="outro">Outro</option>
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
                            id="appointmentNotes"
                            rows="3"
                            placeholder="Informações adicionais sobre o agendamento..."
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
                                    <select class="form-select" name="price_id" id="appointmentPriceId">
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
                                            id="appointmentAutoCharge"
                                            checked>
                                        <label class="form-check-label" for="appointmentAutoCharge">
                                            <i class="bi bi-lightning-charge me-1"></i>
                                            Cobrar automaticamente
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div id="priceInfo" class="alert alert-info" style="display: none;">
                                <i class="bi bi-info-circle me-2"></i>
                                <span id="priceInfoText"></span>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="customer_id" id="appointmentCustomerId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitAppointmentBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Criar Agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Agendamento -->
<div class="modal fade" id="editAppointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>
                    Editar Agendamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAppointmentForm" novalidate>
                <input type="hidden" id="editAppointmentId" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-heart-pulse me-1"></i>
                                Pet <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="pet_id" id="editAppointmentPetId" required>
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
                            <select class="form-select" name="professional_id" id="editAppointmentProfessionalId">
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
                                name="appointment_date_date" 
                                id="editAppointmentDate"
                                required>
                            <div class="invalid-feedback">
                                Por favor, selecione uma data.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-clock me-1"></i>
                                Hora <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="time" 
                                class="form-control" 
                                name="appointment_date_time" 
                                id="editAppointmentTime"
                                required>
                            <div class="invalid-feedback">
                                Por favor, selecione uma hora.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-hourglass-split me-1"></i>
                                Duração (minutos) <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="number" 
                                class="form-control" 
                                name="duration_minutes" 
                                id="editAppointmentDuration"
                                required
                                min="15"
                                max="480"
                                step="15">
                            <div class="invalid-feedback">
                                A duração deve ser entre 15 e 480 minutos.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Tipo de Consulta
                            </label>
                            <select class="form-select" name="type" id="editAppointmentType">
                                <option value="">Selecione...</option>
                                <option value="consulta">Consulta</option>
                                <option value="cirurgia">Cirurgia</option>
                                <option value="vacinação">Vacinação</option>
                                <option value="exame">Exame</option>
                                <option value="retorno">Retorno</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-toggle-on me-1"></i>
                                Status
                            </label>
                            <select class="form-select" name="status" id="editAppointmentStatus">
                                <option value="scheduled">Agendado</option>
                                <option value="confirmed">Confirmado</option>
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
                            id="editAppointmentNotes"
                            rows="3"
                            maxlength="1000"></textarea>
                        <small class="form-text text-muted">
                            <span id="editNotesCounter">0</span>/1000 caracteres
                        </small>
                    </div>
                    
                    <!-- Informações de Pagamento (somente leitura) -->
                    <div id="editAppointmentInvoiceInfo" class="card bg-light mb-3" style="display: none;">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-credit-card me-2"></i>
                                Informações de Pagamento
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="editAppointmentInvoiceContent"></div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="refreshInvoiceBtn">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                Atualizar Status
                            </button>
                        </div>
                    </div>
                    
                    <input type="hidden" name="customer_id" id="editAppointmentCustomerId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitEditAppointmentBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let appointments = [];
let paginationMeta = {};
let pets = [];
let professionals = [];
let customers = [];
let prices = [];
let currentPage = 1;
let pageSize = 20;

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num);
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadPets();
    loadProfessionals();
    loadCustomers();
    loadPrices();
    loadAppointments();
    
    // Verifica se há parâmetro de data na URL e preenche automaticamente
    const urlParams = new URLSearchParams(window.location.search);
    const dateParam = urlParams.get('date');
    const editParam = urlParams.get('edit');
    
    if (dateParam && !editParam) {
        // Preenche a data no formulário de criação
        const dateInput = document.getElementById('appointmentDate');
        if (dateInput) {
            dateInput.value = dateParam;
        }
        
        // Abre o modal de criação automaticamente
        const createModal = new bootstrap.Modal(document.getElementById('createAppointmentModal'));
        createModal.show();
        
        // Remove o parâmetro da URL sem recarregar a página
        const newUrl = window.location.pathname + (editParam ? `?edit=${editParam}` : '');
        window.history.replaceState({}, '', newUrl);
    }
    
    // Se há parâmetro edit, abre o modal de edição
    if (editParam) {
        editAppointment(parseInt(editParam));
        // Remove o parâmetro da URL
        window.history.replaceState({}, '', window.location.pathname);
    }
    
    // Contador de caracteres para observações
    const notesInput = document.getElementById('appointmentNotes');
    const notesCounter = document.getElementById('notesCounter');
    if (notesInput && notesCounter) {
        notesInput.addEventListener('input', function() {
            notesCounter.textContent = this.value.length;
        });
    }
    
    const editNotesInput = document.getElementById('editAppointmentNotes');
    const editNotesCounter = document.getElementById('editNotesCounter');
    if (editNotesInput && editNotesCounter) {
        editNotesInput.addEventListener('input', function() {
            editNotesCounter.textContent = this.value.length;
        });
    }
    
    // Atualiza customer_id quando pet é selecionado
    const petSelect = document.getElementById('appointmentPetId');
    if (petSelect) {
        petSelect.addEventListener('change', function() {
            const petId = this.value;
            const pet = pets.find(p => p.id == petId);
            if (pet) {
                document.getElementById('appointmentCustomerId').value = pet.customer_id;
            }
        });
    }
    
    const editPetSelect = document.getElementById('editAppointmentPetId');
    if (editPetSelect) {
        editPetSelect.addEventListener('change', function() {
            const petId = this.value;
            const pet = pets.find(p => p.id == petId);
            if (pet) {
                document.getElementById('editAppointmentCustomerId').value = pet.customer_id;
            }
        });
    }
    
    // ✅ NOVO: Sugere preço automaticamente ao selecionar profissional
    // Lógica simplificada: busca preço do profissional ou da especialidade
    const professionalSelect = document.getElementById('appointmentProfessionalId');
    const priceSelect = document.getElementById('appointmentPriceId');
    
    async function suggestPrice() {
        const professionalId = professionalSelect?.value || null;
        
        if (!priceSelect || !professionalId) return;
        
        try {
            const response = await apiRequest(`/v1/clinic/professionals/${professionalId}/suggested-price`);
            if (response.data?.price_id) {
                priceSelect.value = response.data.price_id;
                priceSelect.dispatchEvent(new Event('change'));
                console.log('Preço sugerido:', response.data.source === 'professional' ? 'do profissional' : 'da especialidade');
            }
        } catch (error) {
            console.error('Erro ao sugerir preço:', error);
        }
    }
    
    if (professionalSelect) {
        professionalSelect.addEventListener('change', suggestPrice);
    }
    
    // Reset do formulário quando a modal é fechada
    const createModal = document.getElementById('createAppointmentModal');
    if (createModal) {
        createModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('createAppointmentForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                const inputs = form.querySelectorAll('.is-invalid, .is-valid');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid', 'is-valid');
                });
                if (notesCounter) notesCounter.textContent = '0';
            }
        });
    }
    
    const editModal = document.getElementById('editAppointmentModal');
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('editAppointmentForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                const inputs = form.querySelectorAll('.is-invalid, .is-valid');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid', 'is-valid');
                });
            }
        });
    }
    
    // Form criar agendamento
    const createForm = document.getElementById('createAppointmentForm');
    const submitBtn = document.getElementById('submitAppointmentBtn');
    
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
                const date = formData.get('appointment_date_date');
                const time = formData.get('appointment_date_time');
                const appointmentDate = `${date} ${time}:00`;
                
                const data = {
                    pet_id: parseInt(formData.get('pet_id')),
                    customer_id: parseInt(formData.get('customer_id')),
                    appointment_date: appointmentDate,
                    duration_minutes: parseInt(formData.get('duration_minutes'))
                };
                
                const professionalId = formData.get('professional_id');
                if (professionalId) {
                    data.professional_id = parseInt(professionalId);
                }
                
                const type = formData.get('type');
                if (type) {
                    data.type = type;
                }
                
                const notes = formData.get('notes');
                if (notes && notes.trim()) {
                    data.notes = notes.trim();
                }
                
                // ✅ NOVO: Adiciona price_id e auto_charge se fornecidos
                const priceId = formData.get('price_id');
                if (priceId && priceId.trim()) {
                    data.price_id = priceId.trim();
                    const autoCharge = formData.get('auto_charge');
                    data.auto_charge = autoCharge === 'on' || autoCharge === true;
                }
                
                const response = await apiRequest('/v1/clinic/appointments', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/clinic/appointments');
                }
                
                showAlert('Agendamento criado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('createAppointmentModal')).hide();
                loadAppointments();
            } catch (error) {
                showAlert(error.message || 'Erro ao criar agendamento. Tente novamente.', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Agendamento';
                }
            }
        });
    }
    
    // Form editar agendamento
    const editForm = document.getElementById('editAppointmentForm');
    const submitEditBtn = document.getElementById('submitEditAppointmentBtn');
    
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!editForm.checkValidity()) {
                editForm.classList.add('was-validated');
                return;
            }
            
            const appointmentId = document.getElementById('editAppointmentId').value;
            
            if (submitEditBtn) {
                submitEditBtn.disabled = true;
                submitEditBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(editForm);
                const date = formData.get('appointment_date_date');
                const time = formData.get('appointment_date_time');
                const appointmentDate = `${date} ${time}:00`;
                
                const data = {
                    pet_id: parseInt(formData.get('pet_id')),
                    customer_id: parseInt(formData.get('customer_id')),
                    appointment_date: appointmentDate,
                    duration_minutes: parseInt(formData.get('duration_minutes')),
                    status: formData.get('status')
                };
                
                const professionalId = formData.get('professional_id');
                if (professionalId) {
                    data.professional_id = parseInt(professionalId);
                } else {
                    data.professional_id = null;
                }
                
                const type = formData.get('type');
                if (type) {
                    data.type = type;
                }
                
                const notes = formData.get('notes');
                if (notes && notes.trim()) {
                    data.notes = notes.trim();
                }
                
                const response = await apiRequest(`/v1/clinic/appointments/${appointmentId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/clinic/appointments');
                }
                
                showAlert('Agendamento atualizado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editAppointmentModal')).hide();
                loadAppointments();
            } catch (error) {
                showAlert(error.message || 'Erro ao atualizar agendamento. Tente novamente.', 'danger');
            } finally {
                if (submitEditBtn) {
                    submitEditBtn.disabled = false;
                    submitEditBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar Alterações';
                }
            }
        });
    }
});

async function loadPets() {
    try {
        const response = await apiRequest('/v1/clinic/pets?limit=1000', {
            cacheTTL: 60000
        });
        pets = response.data || [];
        
        // Preenche selects de pets
        const petSelects = ['appointmentPetId', 'editAppointmentPetId'];
        petSelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                const currentValue = select.value;
                select.innerHTML = '<option value="">Selecione o pet...</option>';
                pets.forEach(pet => {
                    const option = document.createElement('option');
                    option.value = pet.id;
                    option.textContent = `${pet.name || 'Sem nome'} (${pet.species || 'N/A'})`;
                    select.appendChild(option);
                });
                if (currentValue) {
                    select.value = currentValue;
                }
            }
        });
    } catch (error) {
        console.error('Erro ao carregar pets:', error);
    }
}

async function loadProfessionals() {
    try {
        const response = await apiRequest('/v1/clinic/professionals/active', {
            cacheTTL: 60000
        });
        professionals = response.data || [];
        
        // Preenche selects de profissionais
        const professionalSelects = ['appointmentProfessionalId', 'editAppointmentProfessionalId'];
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
                if (currentValue) {
                    select.value = currentValue;
                }
            }
        });
    } catch (error) {
        console.error('Erro ao carregar profissionais:', error);
    }
}

async function loadCustomers() {
    try {
        const response = await apiRequest('/v1/customers?limit=1000', {
            cacheTTL: 60000
        });
        customers = response.data || [];
    } catch (error) {
        console.error('Erro ao carregar clientes:', error);
    }
}

async function loadPrices() {
    try {
        const response = await apiRequest('/v1/prices?active=true&limit=100', {
            cacheTTL: 60000
        });
        prices = response.data?.prices || response.data || [];
        
        // Preenche select de preços
        const priceSelect = document.getElementById('appointmentPriceId');
        if (priceSelect) {
            priceSelect.innerHTML = '<option value="">Selecione um preço (opcional)...</option>';
            prices.forEach(price => {
                const option = document.createElement('option');
                option.value = price.id;
                const amount = formatCurrency(price.unit_amount, price.currency);
                const productName = price.product?.name || 'Produto';
                const interval = price.recurring ? `/${price.recurring.interval}` : '';
                option.textContent = `${productName} - ${amount}${interval}`;
                option.dataset.amount = price.unit_amount;
                option.dataset.currency = price.currency;
                priceSelect.appendChild(option);
            });
        }
        
        // Listener para mostrar informações do preço selecionado
        const priceInfo = document.getElementById('priceInfo');
        const priceInfoText = document.getElementById('priceInfoText');
        
        if (priceSelect && priceInfo && priceInfoText) {
            priceSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value && selectedOption.dataset.amount) {
                    const amount = parseFloat(selectedOption.dataset.amount) / 100;
                    const currency = selectedOption.dataset.currency.toUpperCase();
                    const formatted = new Intl.NumberFormat('pt-BR', { 
                        style: 'currency', 
                        currency: currency === 'BRL' ? 'BRL' : 'USD' 
                    }).format(amount);
                    priceInfoText.textContent = `Valor: ${formatted}`;
                    priceInfo.style.display = 'block';
                } else {
                    priceInfo.style.display = 'none';
                }
            });
        }
    } catch (error) {
        console.error('Erro ao carregar preços:', error);
    }
}

function formatCurrency(amount, currency = 'brl') {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: currency.toUpperCase()
    }).format(amount / 100);
}

async function loadAppointments() {
    try {
        document.getElementById('loadingAppointments').style.display = 'block';
        document.getElementById('appointmentsList').style.display = 'none';
        
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('limit', pageSize);
        
        const statusFilter = document.getElementById('statusFilter')?.value;
        if (statusFilter) {
            params.append('status', statusFilter);
        }
        
        const dateFrom = document.getElementById('dateFromFilter')?.value;
        if (dateFrom) {
            params.append('start_date', dateFrom);
        }
        
        const dateTo = document.getElementById('dateToFilter')?.value;
        if (dateTo) {
            params.append('end_date', dateTo);
        }
        
        const response = await apiRequest('/v1/clinic/appointments?' + params.toString(), {
            cacheTTL: 10000
        });
        
        appointments = response.data || [];
        paginationMeta = response.meta || {};
        const total = paginationMeta.total || appointments.length;
        const totalPages = Math.ceil(total / pageSize);
        
        renderAppointments();
        renderPagination(totalPages);
    } catch (error) {
        showAlert('Erro ao carregar agendamentos: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingAppointments').style.display = 'none';
        document.getElementById('appointmentsList').style.display = 'block';
    }
}

function renderPagination(totalPages) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;
    
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<nav><ul class="pagination">';
    
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Anterior</a>
    </li>`;
    
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(1); return false;">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
        </li>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${totalPages}); return false;">${totalPages}</a></li>`;
    }
    
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Próximo</a>
    </li>`;
    
    html += '</ul></nav>';
    container.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    loadAppointments();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderAppointments() {
    const tbody = document.getElementById('appointmentsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('appointmentsCountBadge');
    
    if (appointments.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        const total = paginationMeta?.total || appointments.length;
        countBadge.textContent = total;
    }
    
    tbody.innerHTML = appointments.map(appointment => {
        const pet = pets.find(p => p.id === appointment.pet_id);
        const customer = customers.find(c => c.id === appointment.customer_id);
        const professional = professionals.find(p => p.id === appointment.professional_id);
        
        const petName = pet ? pet.name : 'N/A';
        const customerName = customer ? (customer.name || customer.email) : 'N/A';
        const professionalName = professional ? professional.name : '-';
        
        const statusMap = {
            'scheduled': '<span class="badge bg-info">Agendado</span>',
            'confirmed': '<span class="badge bg-success">Confirmado</span>',
            'completed': '<span class="badge bg-primary">Concluído</span>',
            'cancelled': '<span class="badge bg-danger">Cancelado</span>'
        };
        const statusBadge = statusMap[appointment.status] || '<span class="badge bg-secondary">' + appointment.status + '</span>';
        
        return `
        <tr>
            <td>${formatDateTime(appointment.appointment_date)}</td>
            <td>${escapeHtml(petName)}</td>
            <td><small>${escapeHtml(customerName)}</small></td>
            <td>${escapeHtml(professionalName)}</td>
            <td>${escapeHtml(appointment.type || '-')}</td>
            <td>${appointment.duration_minutes || 0} min</td>
            <td>
                ${statusBadge}
                ${appointment.stripe_invoice_id ? `
                <br><small class="text-success">
                    <i class="bi bi-credit-card"></i> Invoice criada
                </small>
                ` : `
                <br><small class="text-muted">
                    <i class="bi bi-credit-card-2-front"></i> Sem pagamento
                </small>
                `}
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="editAppointment(${appointment.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    ${!appointment.stripe_invoice_id ? `
                    <button class="btn btn-sm btn-outline-info" onclick="processPayment(${appointment.id})" title="Processar Pagamento">
                        <i class="bi bi-credit-card"></i>
                    </button>
                    ` : ''}
                    ${appointment.status === 'scheduled' || appointment.status === 'confirmed' ? `
                    <button class="btn btn-sm btn-outline-success" onclick="confirmAppointment(${appointment.id})" title="Confirmar">
                        <i class="bi bi-check-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="cancelAppointment(${appointment.id})" title="Cancelar">
                        <i class="bi bi-x-circle"></i>
                    </button>
                    ` : ''}
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAppointment(${appointment.id})" title="Deletar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        `;
    }).join('');
}

async function editAppointment(id) {
    try {
        const response = await apiRequest(`/v1/clinic/appointments/${id}`);
        const appointment = response.data;
        
        const appointmentDate = new Date(appointment.appointment_date);
        const dateStr = appointmentDate.toISOString().split('T')[0];
        const timeStr = appointmentDate.toTimeString().slice(0, 5);
        
        document.getElementById('editAppointmentId').value = appointment.id;
        document.getElementById('editAppointmentPetId').value = appointment.pet_id;
        document.getElementById('editAppointmentProfessionalId').value = appointment.professional_id || '';
        document.getElementById('editAppointmentDate').value = dateStr;
        document.getElementById('editAppointmentTime').value = timeStr;
        document.getElementById('editAppointmentDuration').value = appointment.duration_minutes || 30;
        document.getElementById('editAppointmentType').value = appointment.type || '';
        document.getElementById('editAppointmentStatus').value = appointment.status || 'scheduled';
        document.getElementById('editAppointmentNotes').value = appointment.notes || '';
        document.getElementById('editAppointmentCustomerId').value = appointment.customer_id;
        document.getElementById('editNotesCounter').textContent = (appointment.notes || '').length;
        
        // Atualiza selects
        const petSelect = document.getElementById('editAppointmentPetId');
        if (petSelect) {
            const currentValue = petSelect.value;
            petSelect.innerHTML = '<option value="">Selecione o pet...</option>';
            pets.forEach(pet => {
                const option = document.createElement('option');
                option.value = pet.id;
                option.textContent = `${pet.name || 'Sem nome'} (${pet.species || 'N/A'})`;
                if (pet.id == currentValue) option.selected = true;
                petSelect.appendChild(option);
            });
        }
        
        const professionalSelect = document.getElementById('editAppointmentProfessionalId');
        if (professionalSelect) {
            const currentValue = professionalSelect.value;
            professionalSelect.innerHTML = '<option value="">Selecione o profissional...</option>';
            professionals.forEach(professional => {
                const option = document.createElement('option');
                option.value = professional.id;
                option.textContent = `${professional.name || 'Sem nome'}${professional.crmv ? ' - ' + professional.crmv : ''}`;
                if (professional.id == currentValue) option.selected = true;
                professionalSelect.appendChild(option);
            });
        }
        
        // ✅ NOVO: Carrega informações do invoice se existir
        if (appointment.stripe_invoice_id) {
            await loadAppointmentInvoice(id);
        } else {
            document.getElementById('editAppointmentInvoiceInfo').style.display = 'none';
        }
        
        const modal = new bootstrap.Modal(document.getElementById('editAppointmentModal'));
        modal.show();
    } catch (error) {
        showAlert('Erro ao carregar agendamento: ' + error.message, 'danger');
    }
}

async function confirmAppointment(id) {
    try {
        await apiRequest(`/v1/clinic/appointments/${id}/confirm`, {
            method: 'POST'
        });
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/clinic/appointments');
        }
        
        showAlert('Agendamento confirmado com sucesso!', 'success');
        loadAppointments();
    } catch (error) {
        showAlert('Erro ao confirmar agendamento: ' + error.message, 'danger');
    }
}

async function cancelAppointment(id) {
    const confirmed = await showConfirmModal(
        'Tem certeza que deseja cancelar este agendamento?',
        'Confirmar Cancelamento',
        'Cancelar',
        'btn-warning'
    );
    
    if (!confirmed) return;
    
    try {
        await apiRequest(`/v1/clinic/appointments/${id}/cancel`, {
            method: 'POST'
        });
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/clinic/appointments');
        }
        
        showAlert('Agendamento cancelado com sucesso!', 'success');
        loadAppointments();
    } catch (error) {
        showAlert('Erro ao cancelar agendamento: ' + error.message, 'danger');
    }
}

async function processPayment(appointmentId) {
    // Abre modal para selecionar preço e processar pagamento
    if (prices.length === 0) {
        await loadPrices();
    }
    
    if (prices.length === 0) {
        showAlert('Nenhum preço disponível. Crie um preço primeiro em Produtos/Preços.', 'warning');
        return;
    }
    
    // Cria modal simples para selecionar preço
    const priceSelectHtml = prices.map(price => {
        const amount = formatCurrency(price.unit_amount, price.currency);
        const productName = price.product?.name || 'Produto';
        return `<option value="${price.id}">${productName} - ${amount}</option>`;
    }).join('');
    
    const modalHtml = `
        <div class="modal fade" id="processPaymentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-credit-card me-2"></i>
                            Processar Pagamento
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Selecione o Preço/Serviço</label>
                            <select class="form-select" id="paymentPriceSelect">
                                <option value="">Selecione...</option>
                                ${priceSelectHtml}
                            </select>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="paymentAutoCharge" checked>
                            <label class="form-check-label" for="paymentAutoCharge">
                                Cobrar automaticamente
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="submitPaymentBtn">
                            <i class="bi bi-check-circle me-1"></i>
                            Processar Pagamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove modal anterior se existir
    const existingModal = document.getElementById('processPaymentModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Adiciona modal ao body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('processPaymentModal'));
    modal.show();
    
    // Handler do botão de submit
    document.getElementById('submitPaymentBtn').onclick = async () => {
        const priceId = document.getElementById('paymentPriceSelect').value;
        const autoCharge = document.getElementById('paymentAutoCharge').checked;
        
        if (!priceId) {
            showAlert('Por favor, selecione um preço', 'warning');
            return;
        }
        
        const submitBtn = document.getElementById('submitPaymentBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processando...';
        
        try {
            await apiRequest(`/v1/clinic/appointments/${appointmentId}/pay`, {
                method: 'POST',
                body: JSON.stringify({
                    price_id: priceId,
                    auto_charge: autoCharge
                })
            });
            
            if (typeof cache !== 'undefined' && cache.clear) {
                cache.clear('/v1/clinic/appointments');
            }
            
            showAlert('Pagamento processado com sucesso!', 'success');
            modal.hide();
            loadAppointments();
        } catch (error) {
            showAlert('Erro ao processar pagamento: ' + error.message, 'danger');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Processar Pagamento';
        }
    };
    
    // Remove modal quando fechado
    document.getElementById('processPaymentModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    }, { once: true });
}

async function deleteAppointment(id) {
    const confirmed = await showConfirmModal(
        'Tem certeza que deseja deletar este agendamento? Esta ação não pode ser desfeita.',
        'Confirmar Exclusão',
        'Deletar',
        'btn-danger'
    );
    
    if (!confirmed) return;
    
    try {
        await apiRequest(`/v1/clinic/appointments/${id}`, {
            method: 'DELETE'
        });
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/clinic/appointments');
        }
        
        showAlert('Agendamento deletado com sucesso!', 'success');
        loadAppointments();
    } catch (error) {
        showAlert('Erro ao deletar agendamento: ' + error.message, 'danger');
    }
}

async function loadAppointmentInvoice(appointmentId) {
    try {
        const response = await apiRequest(`/v1/clinic/appointments/${appointmentId}/invoice`);
        const invoice = response.data;
        
        if (invoice) {
            const invoiceInfo = document.getElementById('editAppointmentInvoiceInfo');
            const invoiceContent = document.getElementById('editAppointmentInvoiceContent');
            
            const statusMap = {
                'draft': '<span class="badge bg-secondary">Rascunho</span>',
                'open': '<span class="badge bg-warning">Aberto</span>',
                'paid': '<span class="badge bg-success">Pago</span>',
                'uncollectible': '<span class="badge bg-danger">Não cobrável</span>',
                'void': '<span class="badge bg-dark">Cancelado</span>'
            };
            const statusBadge = statusMap[invoice.status] || `<span class="badge bg-secondary">${invoice.status}</span>`;
            
            invoiceContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Status:</strong> ${statusBadge}</p>
                        <p class="mb-1"><strong>Valor:</strong> ${formatCurrency(invoice.amount_due * 100, invoice.currency)}</p>
                        <p class="mb-1"><strong>Pago:</strong> ${invoice.paid ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-warning">Não</span>'}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>ID:</strong> <code class="small">${escapeHtml(invoice.id)}</code></p>
                        ${invoice.hosted_invoice_url ? `
                        <p class="mb-1">
                            <a href="${invoice.hosted_invoice_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                Ver Invoice
                            </a>
                        </p>
                        ` : ''}
                        ${invoice.invoice_pdf ? `
                        <p class="mb-1">
                            <a href="${invoice.invoice_pdf}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-file-pdf me-1"></i>
                                Baixar PDF
                            </a>
                        </p>
                        ` : ''}
                    </div>
                </div>
            `;
            
            invoiceInfo.style.display = 'block';
            
            // Listener para botão de atualizar
            const refreshBtn = document.getElementById('refreshInvoiceBtn');
            if (refreshBtn) {
                refreshBtn.onclick = () => loadAppointmentInvoice(appointmentId);
            }
        } else {
            document.getElementById('editAppointmentInvoiceInfo').style.display = 'none';
        }
    } catch (error) {
        console.error('Erro ao carregar invoice:', error);
        document.getElementById('editAppointmentInvoiceInfo').style.display = 'none';
    }
}

function showConfirmModal(message, title, buttonText, buttonClass) {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const modalBody = document.getElementById('confirmModalBody');
        const modalTitle = document.getElementById('confirmModalLabel');
        const confirmButton = document.getElementById('confirmModalButton');
        
        modalTitle.textContent = title;
        modalBody.textContent = message;
        confirmButton.textContent = buttonText;
        confirmButton.className = `btn ${buttonClass}`;
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        confirmButton.onclick = () => {
            bsModal.hide();
            resolve(true);
        };
        
        modal.addEventListener('hidden.bs.modal', () => {
            resolve(false);
        }, { once: true });
    });
}
</script>

