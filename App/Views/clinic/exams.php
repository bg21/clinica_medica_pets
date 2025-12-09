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
        <div>
            <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#createExamTypeModal">
                <i class="bi bi-plus-circle"></i> Novo Exame
            </button>
            <button class="btn btn-primary" onclick="showScheduleExamModal()">
                <i class="bi bi-calendar-plus"></i> Agendar Exame
            </button>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Tipos de Exames Cadastrados -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    <i class="bi bi-tags me-2"></i>
                    Tipos de Exames Disponíveis
                </h5>
                <small class="text-muted">Gerencie os tipos de exames que podem ser realizados</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary" id="examTypesCountBadge">0</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createExamTypeModal">
                    <i class="bi bi-plus-circle"></i> Novo Tipo de Exame
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="loadingExamTypes" class="text-center py-3" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <p class="mt-2 text-muted small">Carregando tipos de exames...</p>
            </div>
            <div id="examTypesList">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Descrição</th>
                                <th>Preço</th>
                                <th>Status</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="examTypesTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyExamTypesState" class="text-center py-4" style="display: none;">
                    <i class="bi bi-tags fs-1 text-muted"></i>
                    <h6 class="mt-3 text-muted">Nenhum tipo de exame cadastrado</h6>
                    <p class="text-muted small">Crie um novo tipo de exame para começar.</p>
                    <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createExamTypeModal">
                        <i class="bi bi-plus-circle"></i> Criar Tipo de Exame
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Tipo de Exame -->
<div class="modal fade" id="createExamTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Novo Tipo de Exame
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createExamTypeForm" novalidate>
                <div class="modal-body">
                    <div id="examTypeAlertContainer"></div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Crie um novo tipo de exame (ex: Hemograma, Raio-X, etc.)
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="createExamTypeName" required maxlength="255" placeholder="Ex: Hemograma">
                            <div class="invalid-feedback">
                                Por favor, insira o nome do tipo de exame.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select class="form-select" name="category" id="createExamTypeCategory" required>
                                <option value="">Selecione a categoria...</option>
                                <option value="blood">Sangue</option>
                                <option value="urine">Urina</option>
                                <option value="imaging">Imagem</option>
                                <option value="other">Outro</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione a categoria.
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" id="createExamTypeDescription" rows="2" maxlength="500" placeholder="Descrição do tipo de exame..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="notes" id="createExamTypeNotes" rows="3" maxlength="5000" placeholder="Instruções e observações sobre este tipo de exame..."></textarea>
                        <small class="form-text text-muted">
                            <span id="createNotesCounter">0</span>/5000 caracteres
                        </small>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Preço no Stripe (Opcional)
                            </label>
                            <select class="form-select" name="price_id" id="createExamTypePriceId">
                                <option value="">Selecione um preço (opcional)...</option>
                            </select>
                            <small class="form-text text-muted">
                                Selecione um preço para vincular a este tipo de exame
                            </small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="createExamTypeStatus">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitCreateExamTypeBtn">
                        <i class="bi bi-plus-circle me-1"></i> Criar Tipo de Exame
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Tipo de Exame -->
<div class="modal fade" id="editExamTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>
                    Editar Tipo de Exame
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editExamTypeForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editExamTypeId">
                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editExamTypeName" required maxlength="255">
                        <div class="invalid-feedback">
                            Por favor, insira o nome do tipo de exame.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria <span class="text-danger">*</span></label>
                        <select class="form-select" name="category" id="editExamTypeCategory" required>
                            <option value="">Selecione a categoria...</option>
                            <option value="blood">Sangue</option>
                            <option value="urine">Urina</option>
                            <option value="imaging">Imagem</option>
                            <option value="other">Outro</option>
                        </select>
                        <div class="invalid-feedback">
                            Por favor, selecione a categoria.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" id="editExamTypeDescription" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="notes" id="editExamTypeNotes" rows="3" maxlength="5000"></textarea>
                        <small class="form-text text-muted">
                            <span id="editNotesCounter">0</span>/5000 caracteres
                        </small>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Preço no Stripe (Opcional)
                            </label>
                            <select class="form-select" name="price_id" id="editExamTypePriceId">
                                <option value="">Selecione um preço (opcional)...</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editExamTypeStatus">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitEditExamTypeBtn">
                        <i class="bi bi-check-circle me-1"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Agendar Exame -->
<div class="modal fade" id="createExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar-plus me-2"></i>
                    Agendar Exame
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
                                <i class="bi bi-journal-medical me-1"></i>
                                Tipo de Exame <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="exam_type_id" id="examTypeId" required>
                                <option value="">Selecione o tipo de exame...</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um tipo de exame.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-person-badge me-1"></i>
                                Veterinário que Solicitou
                            </label>
                            <select class="form-select" name="professional_id" id="examProfessionalId">
                                <option value="">Selecione o veterinário...</option>
                            </select>
                            <small class="form-text text-muted">
                                Veterinário que solicitou o exame
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-person-workspace me-1"></i>
                                Funcionário Responsável
                            </label>
                            <select class="form-select" name="responsible_employee_id" id="examResponsibleEmployeeId">
                                <option value="">Selecione o funcionário...</option>
                            </select>
                            <small class="form-text text-muted">
                                Funcionário responsável pela realização do exame
                            </small>
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
                                <i class="bi bi-info-circle me-1"></i>
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="status" id="examStatus" required>
                                <option value="pending">Pendente</option>
                                <option value="scheduled" selected>Agendado</option>
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
                        <i class="bi bi-calendar-plus me-1"></i>
                        Agendar Exame
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
                            placeholder="Informações adicionais sobre o exame..."
                            maxlength="1000"></textarea>
                        <small class="form-text text-muted">
                            <span id="editNotesCounter">0</span>/1000 caracteres
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            Resultados (Texto)
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
    function showAlert(message, type = 'info', containerId = 'alertContainer') {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Se for o container padrão, adiciona ao final. Caso contrário, substitui o conteúdo
        if (containerId === 'alertContainer') {
            container.appendChild(alert);
        } else {
            container.innerHTML = '';
            container.appendChild(alert);
        }
        
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
        const date = new Date(dateString);
        let formatted = date.toLocaleDateString('pt-BR');
        if (timeString) {
            formatted += ' ' + timeString.substring(0, 5);
        }
        return formatted;
    }
    
    // Função auxiliar para escapar HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Usa a função apiRequest global do dashboard.js que já inclui autenticação
    // Não precisa definir aqui, apenas usar
    
    async function loadExams(page = 1) {
        const loadingDiv = document.getElementById('loadingExams');
        const listDiv = document.getElementById('examsList');
        const emptyState = document.getElementById('emptyState');
        const tableBody = document.getElementById('examsTableBody');
        const countBadge = document.getElementById('examsCountBadge');
        
        if (!listDiv) return;
        
        loadingDiv.style.display = 'block';
        listDiv.style.display = 'none';
        emptyState.style.display = 'none';
        tableBody.innerHTML = '';
        
        try {
            const statusFilter = document.getElementById('statusFilter')?.value || '';
            const dateFrom = document.getElementById('dateFromFilter')?.value || '';
            const dateTo = document.getElementById('dateToFilter')?.value || '';
            const examTypeFilter = document.getElementById('examTypeFilter')?.value || '';
            const professionalFilter = document.getElementById('professionalFilter')?.value || '';
            
            let url = `/v1/clinic/exams?page=${page}&limit=20`;
            if (statusFilter) url += `&status=${statusFilter}`;
            if (dateFrom) url += `&date_from=${dateFrom}`;
            if (dateTo) url += `&date_to=${dateTo}`;
            if (examTypeFilter) url += `&exam_type_id=${examTypeFilter}`;
            if (professionalFilter) url += `&professional_id=${professionalFilter}`;
            
            const response = await apiRequest(url);
            
            // A resposta pode vir como array direto ou dentro de data
            exams = Array.isArray(response.data) ? response.data : (Array.isArray(response) ? response : []);
            const meta = response.meta || {};
            
            countBadge.textContent = meta.total || exams.length || 0;
            
            if (exams.length === 0) {
                emptyState.style.display = 'block';
                listDiv.style.display = 'none';
            } else {
                exams.forEach(exam => {
                    const row = tableBody.insertRow();
                    const statusBadges = {
                        'pending': '<span class="badge bg-warning">Pendente</span>',
                        'scheduled': '<span class="badge bg-info">Agendado</span>',
                        'completed': '<span class="badge bg-success">Concluído</span>',
                        'cancelled': '<span class="badge bg-danger">Cancelado</span>'
                    };
                    const statusBadge = statusBadges[exam.status] || '<span class="badge bg-secondary">' + exam.status + '</span>';
                    
                    row.innerHTML = `
                        <td>${formatDateTime(exam.exam_date, exam.exam_time)}</td>
                        <td>${escapeHtml(exam.pet?.name || '-')}</td>
                        <td>${escapeHtml(exam.client?.name || '-')}</td>
                        <td>${escapeHtml(exam.exam_type?.name || '-')}</td>
                        <td>${escapeHtml(exam.professional?.name || '-')}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-warning me-1" onclick="editExam(${exam.id})" data-bs-toggle="tooltip" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteExam(${exam.id})" data-bs-toggle="tooltip" title="Excluir">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    `;
                });
                listDiv.style.display = 'block';
                emptyState.style.display = 'none';
            }
            
            currentPage = page;
        } catch (error) {
            console.error('Erro ao carregar exames:', error);
            showAlert('Erro ao carregar exames. Tente novamente.', 'danger');
            emptyState.style.display = 'block';
        } finally {
            loadingDiv.style.display = 'none';
        }
    }
    
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
            
            // Preenche select de pets no modal de agendar exame
            const petSelect = document.getElementById('examPetId');
            if (petSelect) {
                const currentValue = petSelect.value;
                petSelect.innerHTML = '<option value="">Selecione o pet...</option>';
                pets.forEach(pet => {
                    const option = document.createElement('option');
                    option.value = pet.id;
                    option.textContent = `${pet.name || 'Sem nome'} (${pet.species || 'N/A'})`;
                    option.dataset.customerId = pet.customer_id || pet.client_id;
                    petSelect.appendChild(option);
                });
                if (currentValue) petSelect.value = currentValue;
            }
        } catch (error) {
            console.error('Erro ao carregar pets:', error);
        }
    }
    
    async function loadProfessionals() {
        try {
            const response = await apiRequest('/v1/clinic/professionals/active', { cacheTTL: 60000 });
            professionals = response.data || [];
            
            // Preenche select de profissionais no filtro
            const professionalSelect = document.getElementById('professionalFilter');
            if (professionalSelect) {
                professionalSelect.innerHTML = '<option value="">Todos</option>';
                professionals.forEach(prof => {
                    const option = document.createElement('option');
                    option.value = prof.id;
                    option.textContent = prof.name;
                    professionalSelect.appendChild(option);
                });
            }
            
            // Preenche select de veterinário que solicitou
            const vetSelect = document.getElementById('examProfessionalId');
            if (vetSelect) {
                const currentValue = vetSelect.value;
                vetSelect.innerHTML = '<option value="">Selecione o veterinário...</option>';
                professionals.forEach(prof => {
                    const option = document.createElement('option');
                    option.value = prof.id;
                    option.textContent = `${prof.name || 'Sem nome'}${prof.crmv ? ' - ' + prof.crmv : ''}`;
                    vetSelect.appendChild(option);
                });
                if (currentValue) vetSelect.value = currentValue;
            }
            
            // Preenche select de funcionário responsável (mesma lista de profissionais)
            const employeeSelect = document.getElementById('examResponsibleEmployeeId');
            if (employeeSelect) {
                const currentValue = employeeSelect.value;
                employeeSelect.innerHTML = '<option value="">Selecione o funcionário...</option>';
                professionals.forEach(prof => {
                    const option = document.createElement('option');
                    option.value = prof.id;
                    option.textContent = `${prof.name || 'Sem nome'}${prof.crmv ? ' - ' + prof.crmv : ''}`;
                    employeeSelect.appendChild(option);
                });
                if (currentValue) employeeSelect.value = currentValue;
            }
        } catch (error) {
            console.error('Erro ao carregar profissionais:', error);
        }
    }
    
    async function loadExamTypes() {
        try {
            // Carrega todos os tipos de exames (ativos e inativos) para mostrar na tabela
            const response = await apiRequest('/v1/clinic/exam-types?limit=1000', { cacheTTL: 60000 });
            examTypes = response.data || [];
            
            // Atualiza contador
            const countBadge = document.getElementById('examTypesCountBadge');
            if (countBadge) {
                countBadge.textContent = examTypes.length;
            }
            
            // Preenche selects apenas com tipos ativos
            const examTypeSelects = ['examTypeId', 'editExamTypeId', 'examTypeFilter'];
            examTypeSelects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    const currentValue = select.value;
                    select.innerHTML = '<option value="">Selecione o tipo...</option>';
                    examTypes.filter(t => t.status === 'active').forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id;
                        option.textContent = type.name;
                        select.appendChild(option);
                    });
                    if (currentValue) select.value = currentValue;
                }
            });
            
            // Renderiza tabela de tipos de exames
            renderExamTypesList();
        } catch (error) {
            console.error('Erro ao carregar tipos de exame:', error);
        }
    }
    
    function renderExamTypesList() {
        const loadingDiv = document.getElementById('loadingExamTypes');
        const listDiv = document.getElementById('examTypesList');
        const emptyState = document.getElementById('emptyExamTypesState');
        const tableBody = document.getElementById('examTypesTableBody');
        
        if (!listDiv || !tableBody) return;
        
        if (loadingDiv) loadingDiv.style.display = 'none';
        
        if (examTypes.length === 0) {
            listDiv.style.display = 'none';
            if (emptyState) emptyState.style.display = 'block';
            return;
        }
        
        if (emptyState) emptyState.style.display = 'none';
        listDiv.style.display = 'block';
        
        const categoryMap = {
            'blood': 'Sangue',
            'urine': 'Urina',
            'imaging': 'Imagem',
            'other': 'Outro'
        };
        
        tableBody.innerHTML = examTypes.map(type => {
            const category = categoryMap[type.category] || type.category;
            const statusBadge = type.status === 'active' 
                ? '<span class="badge bg-success">Ativo</span>' 
                : '<span class="badge bg-secondary">Inativo</span>';
            
            const priceText = type.price_id 
                ? '<small class="text-success"><i class="bi bi-check-circle"></i> Preço configurado</small>' 
                : '<small class="text-muted">Sem preço</small>';
            
            return `
                <tr>
                    <td><strong>${escapeHtml(type.name || '-')}</strong></td>
                    <td><span class="badge bg-info">${escapeHtml(category)}</span></td>
                    <td><small class="text-muted">${escapeHtml((type.description || '').substring(0, 50))}${type.description && type.description.length > 50 ? '...' : ''}</small></td>
                    <td>${priceText}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-warning me-1" onclick="editExamType(${type.id})" data-bs-toggle="tooltip" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteExamTypeConfirm(${type.id})" data-bs-toggle="tooltip" title="Excluir">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
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
    
    // Função para agendar exame (abre modal de criar exame)
    window.scheduleExam = function(examTypeId = null) {
        const modalElement = document.getElementById('createExamModal');
        if (!modalElement) {
            console.error('Modal createExamModal não encontrado');
            showAlert('Erro ao abrir modal de agendamento.', 'danger');
            return;
        }
        
        // Se um tipo de exame foi especificado, preenche o select
        if (examTypeId) {
            const examType = examTypes.find(et => et.id === examTypeId);
            if (!examType) {
                showAlert('Tipo de exame não encontrado.', 'danger');
                return;
            }
            
            // Preenche o formulário de criar exame com o tipo selecionado
            const examTypeSelect = document.getElementById('examTypeId');
            if (examTypeSelect) {
                examTypeSelect.value = examTypeId;
            }
        } else {
            // Limpa o select se não houver tipo específico
            const examTypeSelect = document.getElementById('examTypeId');
            if (examTypeSelect) {
                examTypeSelect.value = '';
            }
        }
        
        // Abre o modal
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    };

    // Função para mostrar modal de agendar exame (sem tipo específico)
    window.showScheduleExamModal = function() {
        const modalElement = document.getElementById('createExamModal');
        if (!modalElement) {
            console.error('Modal createExamModal não encontrado');
            showAlert('Erro ao abrir modal de agendamento.', 'danger');
            return;
        }
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    };
    
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
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Agendando...';
            }
            
            try {
                const formData = new FormData(createForm);
                const petId = parseInt(formData.get('pet_id'));
                if (!petId) {
                    showAlert('Por favor, selecione um pet.', 'warning');
                    createForm.classList.add('was-validated');
                    return;
                }
                
                // Busca o pet para obter o client_id
                const pet = pets.find(p => p.id === petId);
                if (!pet) {
                    showAlert('Pet não encontrado.', 'danger');
                    return;
                }
                
                const data = {
                    exam_date: formData.get('exam_date'),
                    client_id: pet.customer_id || pet.client_id,
                    pet_id: petId,
                    exam_type_id: parseInt(formData.get('exam_type_id'))
                };
                
                const examTime = formData.get('exam_time');
                if (examTime) data.exam_time = examTime;
                
                const professionalId = formData.get('professional_id');
                if (professionalId) data.professional_id = parseInt(professionalId);
                
                const responsibleEmployeeId = formData.get('responsible_employee_id');
                if (responsibleEmployeeId) data.responsible_employee_id = parseInt(responsibleEmployeeId);
                
                const status = formData.get('status');
                if (status) data.status = status;
                
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
                
                showAlert('Exame agendado com sucesso!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('createExamModal'));
                if (modal) modal.hide();
                createForm.reset();
                createForm.classList.remove('was-validated');
                document.getElementById('examClientId').value = '';
                const notesCounter = document.getElementById('notesCounter');
                if (notesCounter) notesCounter.textContent = '0';
                loadExams();
            } catch (error) {
                showAlert(error.message || 'Erro ao agendar exame. Tente novamente.', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-calendar-plus me-1"></i> Agendar Exame';
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
                    exam_type_id: formData.get('exam_type_id') ? parseInt(formData.get('exam_type_id')) : null,
                    status: formData.get('status')
                };
                
                const examTime = formData.get('exam_time');
                if (examTime) data.exam_time = examTime;
                
                const notes = formData.get('notes');
                if (notes && notes.trim()) data.notes = notes.trim();
                
                const results = formData.get('results');
                if (results && results.trim()) data.results = results.trim();
                
                await apiRequest(`/v1/clinic/exams/${examId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                showAlert('Exame atualizado com sucesso!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('editExamModal'));
                if (modal) modal.hide();
                editForm.reset();
                editForm.classList.remove('was-validated');
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
    
    // Funções para editar e deletar exames
    window.editExam = async function(examId) {
        try {
            const response = await apiRequest(`/v1/clinic/exams/${examId}`);
            const exam = response.data;
            
            document.getElementById('editExamId').value = exam.id;
            document.getElementById('editExamDate').value = exam.exam_date ? exam.exam_date.split(' ')[0] : '';
            document.getElementById('editExamTime').value = exam.exam_time || '';
            document.getElementById('editExamTypeId').value = exam.exam_type_id || '';
            document.getElementById('editExamStatus').value = exam.status;
            document.getElementById('editExamNotes').value = exam.notes || '';
            document.getElementById('editExamResults').value = exam.results || '';
            
            const notesCounter = document.getElementById('editNotesCounter');
            if (notesCounter) notesCounter.textContent = (exam.notes || '').length;
            
            const resultsCounter = document.getElementById('resultsCounter');
            if (resultsCounter) resultsCounter.textContent = (exam.results || '').length;
            
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editExamModal'));
            modal.show();
        } catch (error) {
            showAlert('Erro ao carregar exame. Tente novamente.', 'danger');
        }
    };
    
    window.deleteExam = async function(examId) {
        if (!confirm('Tem certeza que deseja excluir este exame?')) {
            return;
        }
        
        try {
            await apiRequest(`/v1/clinic/exams/${examId}`, {
                method: 'DELETE'
            });
            
            showAlert('Exame excluído com sucesso!', 'success');
            loadExams();
        } catch (error) {
            showAlert(error.message || 'Erro ao excluir exame. Tente novamente.', 'danger');
        }
    };
    
    // Form criar tipo de exame
    const createExamTypeForm = document.getElementById('createExamTypeForm');
    const submitCreateExamTypeBtn = document.getElementById('submitCreateExamTypeBtn');
    
    if (createExamTypeForm) {
        createExamTypeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!createExamTypeForm.checkValidity()) {
                createExamTypeForm.classList.add('was-validated');
                return;
            }
            
            if (submitCreateExamTypeBtn) {
                submitCreateExamTypeBtn.disabled = true;
                submitCreateExamTypeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Criando...';
            }
            
            try {
                const formData = new FormData(createExamTypeForm);
                const data = {
                    name: formData.get('name'),
                    category: formData.get('category'),
                    description: formData.get('description') || null,
                    notes: formData.get('notes') || null,
                    status: formData.get('status') || 'active'
                };
                
                const priceId = formData.get('price_id');
                if (priceId) {
                    data.price_id = priceId;
                }
                
                await apiRequest('/v1/clinic/exam-types', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                showAlert('Tipo de exame criado com sucesso!', 'success', 'examTypeAlertContainer');
                createExamTypeForm.reset();
                createExamTypeForm.classList.remove('was-validated');
                const notesCounter = document.getElementById('createNotesCounter');
                if (notesCounter) notesCounter.textContent = '0';
                await loadExamTypes(); // Recarrega para atualizar os selects
                
                // Fecha o modal após 1 segundo
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createExamTypeModal'));
                    if (modal) modal.hide();
                }, 1000);
            } catch (error) {
                showAlert(error.message || 'Erro ao criar tipo de exame. Tente novamente.', 'danger', 'examTypeAlertContainer');
            } finally {
                if (submitCreateExamTypeBtn) {
                    submitCreateExamTypeBtn.disabled = false;
                    submitCreateExamTypeBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Criar Tipo de Exame';
                }
            }
        });
    }

    // Event listener para modal de criar tipo de exame
    const createExamTypeModal = document.getElementById('createExamTypeModal');
    if (createExamTypeModal) {
        createExamTypeModal.addEventListener('show.bs.modal', function() {
            // Carrega preços no select
            const priceSelect = document.getElementById('createExamTypePriceId');
            if (priceSelect) {
                const currentValue = priceSelect.value;
                priceSelect.innerHTML = '<option value="">Selecione um preço (opcional)...</option>';
                prices.forEach(price => {
                    const option = document.createElement('option');
                    option.value = price.id;
                    const amount = (price.unit_amount / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                    const productName = price.product?.name || 'Produto';
                    option.textContent = `${productName} - ${amount}`;
                    priceSelect.appendChild(option);
                });
                if (currentValue) priceSelect.value = currentValue;
            }
        });
        
        createExamTypeModal.addEventListener('hidden.bs.modal', function() {
            // Limpa formulário de criação
            const createForm = document.getElementById('createExamTypeForm');
            if (createForm) {
                createForm.reset();
                createForm.classList.remove('was-validated');
                const notesCounter = document.getElementById('createNotesCounter');
                if (notesCounter) notesCounter.textContent = '0';
            }
        });
    }

    // Contador de caracteres para observações
    const createNotesTextarea = document.getElementById('createExamTypeNotes');
    const createNotesCounter = document.getElementById('createNotesCounter');
    if (createNotesTextarea && createNotesCounter) {
        createNotesTextarea.addEventListener('input', function() {
            createNotesCounter.textContent = this.value.length;
        });
    }

    // Event listener para quando selecionar um pet, preencher automaticamente o client_id
    const examPetSelect = document.getElementById('examPetId');
    if (examPetSelect) {
        examPetSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.customerId) {
                document.getElementById('examClientId').value = selectedOption.dataset.customerId;
            }
        });
    }
    
    // Event listener para resetar formulário quando modal for fechado
    const createExamModal = document.getElementById('createExamModal');
    if (createExamModal) {
        createExamModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('createExamForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                document.getElementById('examClientId').value = '';
                const notesCounter = document.getElementById('notesCounter');
                if (notesCounter) notesCounter.textContent = '0';
            }
        });
    }
    
    // Contador de caracteres para observações
    const examNotesTextarea = document.getElementById('examNotes');
    const notesCounter = document.getElementById('notesCounter');
    if (examNotesTextarea && notesCounter) {
        examNotesTextarea.addEventListener('input', function() {
            notesCounter.textContent = this.value.length;
        });
    }

    // Função para editar tipo de exame
    window.editExamType = async function(examTypeId) {
        try {
            const response = await apiRequest(`/v1/clinic/exam-types/${examTypeId}`);
            const examType = response.data;
            
            if (!examType) {
                showAlert('Tipo de exame não encontrado.', 'danger', 'examTypeAlertContainer');
                return;
            }
            
            // Preenche formulário de edição
            document.getElementById('editExamTypeId').value = examType.id;
            document.getElementById('editExamTypeName').value = examType.name || '';
            document.getElementById('editExamTypeCategory').value = examType.category || '';
            document.getElementById('editExamTypeDescription').value = examType.description || '';
            document.getElementById('editExamTypeNotes').value = examType.notes || '';
            document.getElementById('editExamTypePriceId').value = examType.price_id || '';
            document.getElementById('editExamTypeStatus').value = examType.status || 'active';
            
            const notesCounter = document.getElementById('editNotesCounter');
            if (notesCounter) notesCounter.textContent = (examType.notes || '').length;
            
            // Abre modal de edição
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editExamTypeModal'));
            modal.show();
        } catch (error) {
            showAlert('Erro ao carregar tipo de exame. Tente novamente.', 'danger', 'examTypeAlertContainer');
        }
    };
    
    // Função para confirmar exclusão de tipo de exame
    window.deleteExamTypeConfirm = function(examTypeId) {
        const examType = examTypes.find(et => et.id === examTypeId);
        const name = examType ? examType.name : 'este tipo de exame';
        
        if (!confirm(`Tem certeza que deseja excluir "${name}"?\n\nEsta ação não pode ser desfeita.`)) {
            return;
        }
        
        deleteExamType(examTypeId);
    };
    
    // Função para excluir tipo de exame
    async function deleteExamType(examTypeId) {
        try {
            await apiRequest(`/v1/clinic/exam-types/${examTypeId}`, {
                method: 'DELETE'
            });
            
            showAlert('Tipo de exame excluído com sucesso!', 'success', 'examTypeAlertContainer');
            await loadExamTypes(); // Recarrega a lista
        } catch (error) {
            showAlert(error.message || 'Erro ao excluir tipo de exame. Tente novamente.', 'danger', 'examTypeAlertContainer');
        }
    }
    
    // Form editar tipo de exame
    const editExamTypeForm = document.getElementById('editExamTypeForm');
    const submitEditExamTypeBtn = document.getElementById('submitEditExamTypeBtn');
    
    if (editExamTypeForm) {
        editExamTypeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!editExamTypeForm.checkValidity()) {
                editExamTypeForm.classList.add('was-validated');
                return;
            }
            
            if (submitEditExamTypeBtn) {
                submitEditExamTypeBtn.disabled = true;
                submitEditExamTypeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(editExamTypeForm);
                const examTypeId = parseInt(formData.get('id'));
                const data = {
                    name: formData.get('name'),
                    category: formData.get('category'),
                    description: formData.get('description') || null,
                    notes: formData.get('notes') || null,
                    status: formData.get('status') || 'active'
                };
                
                const priceId = formData.get('price_id');
                if (priceId) {
                    data.price_id = priceId;
                } else {
                    data.price_id = null;
                }
                
                await apiRequest(`/v1/clinic/exam-types/${examTypeId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                showAlert('Tipo de exame atualizado com sucesso!', 'success', 'examTypeAlertContainer');
                editExamTypeForm.reset();
                editExamTypeForm.classList.remove('was-validated');
                const notesCounter = document.getElementById('editNotesCounter');
                if (notesCounter) notesCounter.textContent = '0';
                await loadExamTypes(); // Recarrega a lista
                
                // Fecha o modal após 1 segundo
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editExamTypeModal'));
                    if (modal) modal.hide();
                }, 1000);
            } catch (error) {
                showAlert(error.message || 'Erro ao atualizar tipo de exame. Tente novamente.', 'danger', 'examTypeAlertContainer');
            } finally {
                if (submitEditExamTypeBtn) {
                    submitEditExamTypeBtn.disabled = false;
                    submitEditExamTypeBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar Alterações';
                }
            }
        });
    }

    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        loadInitialData();
        loadExams();
        
        // Event listeners para filtros
        document.getElementById('statusFilter')?.addEventListener('change', () => loadExams(1));
        document.getElementById('examTypeFilter')?.addEventListener('change', () => loadExams(1));
        document.getElementById('dateFromFilter')?.addEventListener('change', () => loadExams(1));
        document.getElementById('dateToFilter')?.addEventListener('change', () => loadExams(1));
        document.getElementById('professionalFilter')?.addEventListener('change', () => loadExams(1));
    });
})();
</script>