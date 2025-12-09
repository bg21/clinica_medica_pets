<?php
/**
 * View de Profissionais
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-person-badge text-primary"></i>
                Profissionais
            </h1>
            <p class="text-muted mb-0">Gerencie os profissionais veterinários</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProfessionalModal">
            <i class="bi bi-plus-circle"></i> Novo Profissional
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
                        <input type="text" class="form-control" id="searchInput" placeholder="Nome, CRMV, especialidade...">
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
                        <option value="name">Nome</option>
                        <option value="created_at">Data de Criação</option>
                        <option value="crmv">CRMV</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadProfessionals()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Profissionais -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Profissionais
            </h5>
            <span class="badge bg-primary" id="professionalsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingProfessionals" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando profissionais...</p>
            </div>
            <div id="professionalsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>CRMV</th>
                                <th>Especialidade</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="professionalsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-person-badge fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum profissional encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro profissional.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createProfessionalModal">
                        <i class="bi bi-plus-circle"></i> Criar Profissional
                    </button>
                </div>
                <div id="paginationContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Profissional -->
<div class="modal fade" id="createProfessionalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-badge me-2"></i>
                    Novo Profissional
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createProfessionalForm" novalidate>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Campos marcados com <span class="text-danger">*</span> são obrigatórios.
                    </div>
                    
                    <!-- Foto do Profissional -->
                    <div class="mb-4 text-center">
                        <label class="form-label d-block">
                            <i class="bi bi-camera me-1"></i>
                            Foto do Profissional
                        </label>
                        <div class="mb-2">
                            <img id="createProfessionalPhotoPreview" src="/img/default-user.svg" alt="Foto do Profissional" 
                                 class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover; cursor: pointer;"
                                 onclick="document.getElementById('createProfessionalPhotoInput').click()">
                        </div>
                        <input type="file" id="createProfessionalPhotoInput" accept="image/jpeg,image/png,image/gif,image/webp" 
                               style="display: none;" onchange="handleProfessionalPhotoPreview('createProfessionalPhotoInput', 'createProfessionalPhotoPreview')">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('createProfessionalPhotoInput').click()">
                            <i class="bi bi-upload"></i> Escolher Foto
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="removeCreateProfessionalPhotoBtn" style="display: none;" onclick="removeProfessionalPhoto('createProfessionalPhotoInput', 'createProfessionalPhotoPreview')">
                            <i class="bi bi-trash"></i> Remover
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-person me-1"></i>
                                Usuário <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="user_id" id="professionalUserId" required>
                                <option value="">Selecione o usuário...</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um usuário.
                            </div>
                            <small class="form-text text-muted">
                                Primeiro crie o usuário no sistema, depois selecione-o aqui.
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-briefcase me-1"></i>
                                Função do Profissional <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="professional_role_id" id="professionalRoleId" required>
                                <option value="">Selecione a função...</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione a função do profissional.
                            </div>
                            <small class="form-text text-muted" id="professionalUserRoleHint">
                                Selecione a função do profissional na clínica
                            </small>
                        </div>
                    </div>
                    
                    <div class="row" id="crmvRow" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-card-text me-1"></i>
                                CRMV <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="crmv" 
                                id="professionalCrmv"
                                placeholder="Ex: CRMV-SP 12345"
                                maxlength="50">
                            <small class="form-text text-muted">
                                Registro do Conselho Regional de Medicina Veterinária (obrigatório para veterinários)
                            </small>
                            <div class="invalid-feedback">
                                CRMV é obrigatório para veterinários.
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="name" id="professionalName">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-envelope me-1"></i>
                                Email
                            </label>
                            <input 
                                type="email" 
                                class="form-control" 
                                name="email" 
                                id="professionalEmail"
                                placeholder="Será preenchido automaticamente"
                                readonly
                                maxlength="255">
                            <small class="form-text text-muted">
                                Email do usuário selecionado
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-telephone me-1"></i>
                                Telefone
                            </label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                name="phone" 
                                id="professionalPhone"
                                placeholder="(11) 98765-4321"
                                maxlength="20">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-file-earmark-person me-1"></i>
                                CPF
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="cpf" 
                                id="professionalCpf"
                                placeholder="000.000.000-00"
                                maxlength="14">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-briefcase me-1"></i>
                                Especialidade
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="specialty" 
                                id="professionalSpecialty"
                                placeholder="Ex: Clínica Geral, Cirurgia, etc."
                                maxlength="100">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-toggle-on me-1"></i>
                                Status
                            </label>
                            <select class="form-select" name="status" id="professionalStatus">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitProfessionalBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Criar Profissional
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Profissional -->
<div class="modal fade" id="editProfessionalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>
                    Editar Profissional
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProfessionalForm" novalidate>
                <input type="hidden" id="editProfessionalId" name="id">
                <div class="modal-body">
                    <!-- Foto do Profissional -->
                    <div class="mb-4 text-center">
                        <label class="form-label d-block">
                            <i class="bi bi-camera me-1"></i>
                            Foto do Profissional
                        </label>
                        <div class="mb-2">
                            <img id="editProfessionalPhotoPreview" src="/img/default-user.svg" alt="Foto do Profissional" 
                                 class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover; cursor: pointer;"
                                 onclick="document.getElementById('editProfessionalPhotoInput').click()">
                        </div>
                        <input type="file" id="editProfessionalPhotoInput" accept="image/jpeg,image/png,image/gif,image/webp" 
                               style="display: none;" onchange="handleProfessionalPhotoPreview('editProfessionalPhotoInput', 'editProfessionalPhotoPreview')">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('editProfessionalPhotoInput').click()">
                            <i class="bi bi-upload"></i> Escolher Foto
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="removeEditProfessionalPhotoBtn" style="display: none;" onclick="removeProfessionalPhoto('editProfessionalPhotoInput', 'editProfessionalPhotoPreview')">
                            <i class="bi bi-trash"></i> Remover
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-person me-1"></i>
                                Usuário <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="user_id" id="editProfessionalUserId" required>
                                <option value="">Selecione o usuário...</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um usuário.
                            </div>
                            <small class="form-text text-muted">
                                Primeiro crie o usuário no sistema, depois selecione-o aqui.
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-briefcase me-1"></i>
                                Função do Profissional <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="professional_role_id" id="editProfessionalRoleId" required>
                                <option value="">Selecione a função...</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione a função do profissional.
                            </div>
                            <small class="form-text text-muted" id="editProfessionalUserRoleHint">
                                Selecione a função do profissional na clínica
                            </small>
                        </div>
                    </div>
                    
                    <div class="row" id="editCrmvRow" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-card-text me-1"></i>
                                CRMV <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="crmv" 
                                id="editProfessionalCrmv"
                                placeholder="Ex: CRMV-SP 12345"
                                maxlength="50">
                            <small class="form-text text-muted">
                                Registro do Conselho Regional de Medicina Veterinária (obrigatório para veterinários)
                            </small>
                            <div class="invalid-feedback">
                                CRMV é obrigatório para veterinários.
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="name" id="editProfessionalName">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-envelope me-1"></i>
                                Email
                            </label>
                            <input 
                                type="email" 
                                class="form-control" 
                                name="email" 
                                id="editProfessionalEmail"
                                readonly
                                placeholder="Será preenchido automaticamente"
                                maxlength="255">
                            <small class="form-text text-muted">
                                Email do usuário selecionado
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-telephone me-1"></i>
                                Telefone
                            </label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                name="phone" 
                                id="editProfessionalPhone"
                                maxlength="20">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-file-earmark-person me-1"></i>
                                CPF
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="cpf" 
                                id="editProfessionalCpf"
                                maxlength="14">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-briefcase me-1"></i>
                                Especialidade
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="specialty" 
                                id="editProfessionalSpecialty"
                                maxlength="100">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-toggle-on me-1"></i>
                                Status
                            </label>
                            <select class="form-select" name="status" id="editProfessionalStatus">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitEditProfessionalBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let professionals = [];
let paginationMeta = {};
let currentPage = 1;
let pageSize = 20;
let searchTimeout = null;

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num);
}

// Máscara de telefone
function applyPhoneMask(input) {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 11) {
            if (value.length <= 2) {
                value = value ? `(${value}` : value;
            } else if (value.length <= 6) {
                value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
            } else if (value.length <= 10) {
                value = `(${value.slice(0, 2)}) ${value.slice(2, 6)}-${value.slice(6)}`;
            } else {
                value = `(${value.slice(0, 2)}) ${value.slice(2, 7)}-${value.slice(7, 11)}`;
            }
            e.target.value = value;
        }
    });
}

// Máscara de CPF
function applyCpfMask(input) {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 11) {
            if (value.length <= 3) {
                value = value;
            } else if (value.length <= 6) {
                value = `${value.slice(0, 3)}.${value.slice(3)}`;
            } else if (value.length <= 9) {
                value = `${value.slice(0, 3)}.${value.slice(3, 6)}.${value.slice(6)}`;
            } else {
                value = `${value.slice(0, 3)}.${value.slice(3, 6)}.${value.slice(6, 9)}-${value.slice(9, 11)}`;
            }
            e.target.value = value;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadProfessionals();
    // Debounce na busca
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            currentPage = 1;
            loadProfessionals();
        }, 500));
    }
    
    // Carrega usuários e funções profissionais para os selects
    let users = [];
    let professionalRoles = [];
    loadUsers();
    loadProfessionalRoles();
    
    // Aplica máscaras
    const phoneInputs = ['professionalPhone', 'editProfessionalPhone'];
    phoneInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) applyPhoneMask(input);
    });
    
    const cpfInputs = ['professionalCpf', 'editProfessionalCpf'];
    cpfInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) applyCpfMask(input);
    });
    
    // Event listeners para seleção de usuário
    const professionalUserId = document.getElementById('professionalUserId');
    if (professionalUserId) {
        professionalUserId.addEventListener('change', async function() {
            await handleUserSelection(this.value, 'create');
        });
    }
    
    const editProfessionalUserId = document.getElementById('editProfessionalUserId');
    if (editProfessionalUserId) {
        editProfessionalUserId.addEventListener('change', async function() {
            await handleUserSelection(this.value, 'edit');
        });
    }
    
    // Event listeners para seleção de função profissional
    const professionalRoleId = document.getElementById('professionalRoleId');
    if (professionalRoleId) {
        professionalRoleId.addEventListener('change', function() {
            console.log('Evento change disparado no select de função (criar):', this.value);
            handleRoleSelection(this.value, 'create');
        });
        console.log('Event listener registrado para professionalRoleId');
    } else {
        console.error('Elemento professionalRoleId não encontrado!');
    }
    
    const editProfessionalRoleId = document.getElementById('editProfessionalRoleId');
    if (editProfessionalRoleId) {
        editProfessionalRoleId.addEventListener('change', function() {
            console.log('Evento change disparado no select de função (editar):', this.value);
            handleRoleSelection(this.value, 'edit');
        });
        console.log('Event listener registrado para editProfessionalRoleId');
    } else {
        console.error('Elemento editProfessionalRoleId não encontrado!');
    }
    
    // Reset do formulário quando a modal é fechada
    const createModal = document.getElementById('createProfessionalModal');
    if (createModal) {
        createModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('createProfessionalForm');
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
    
    const editModal = document.getElementById('editProfessionalModal');
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('editProfessionalForm');
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
    
    // Form criar profissional
    const createForm = document.getElementById('createProfessionalForm');
    const submitBtn = document.getElementById('submitProfessionalBtn');
    
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
                const data = {};
                
                // Obtém user_id primeiro
                const userId = formData.get('user_id');
                if (!userId) {
                    showAlert('Por favor, selecione um usuário', 'danger');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Profissional';
                    }
                    return;
                }
                
                data['user_id'] = parseInt(userId);
                
                // Nome vem do campo hidden (preenchido automaticamente)
                const name = document.getElementById('professionalName').value;
                if (name) {
                    data['name'] = name;
                }
                
                // Processa professional_role_id
                const professionalRoleId = formData.get('professional_role_id');
                if (professionalRoleId) {
                    data['professional_role_id'] = parseInt(professionalRoleId);
                }
                
                for (let [key, value] of formData.entries()) {
                    if (key === 'user_id' || key === 'name' || key === 'professional_role_id') continue; // Já processados
                    
                    const trimmedValue = value.trim();
                    if (trimmedValue || key === 'crmv') { // CRMV pode ser obrigatório
                        // Remove máscaras
                        if (key === 'phone') {
                            data[key] = trimmedValue.replace(/\D/g, '');
                        } else if (key === 'cpf') {
                            data[key] = trimmedValue.replace(/\D/g, '');
                        } else {
                            data[key] = trimmedValue;
                        }
                    }
                }
                
                const response = await apiRequest('/v1/clinic/professionals', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/clinic/professionals');
                }
                
                showAlert('Profissional criado com sucesso!', 'success');
                
                // Upload de foto se houver
                const photoInput = document.getElementById('createProfessionalPhotoInput');
                if (photoInput && photoInput.files && photoInput.files.length > 0) {
                    await uploadProfessionalPhoto(response.data.id, photoInput.files[0]);
                }
                
                bootstrap.Modal.getInstance(document.getElementById('createProfessionalModal')).hide();
                loadProfessionals();
            } catch (error) {
                showAlert(error.message || 'Erro ao criar profissional. Tente novamente.', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Profissional';
                }
            }
        });
    }
    
    // Form editar profissional
    const editForm = document.getElementById('editProfessionalForm');
    const submitEditBtn = document.getElementById('submitEditProfessionalBtn');
    
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!editForm.checkValidity()) {
                editForm.classList.add('was-validated');
                return;
            }
            
            const professionalId = document.getElementById('editProfessionalId').value;
            
            if (submitEditBtn) {
                submitEditBtn.disabled = true;
                submitEditBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(editForm);
                const data = {};
                
                // Campos obrigatórios
                const requiredFields = ['name', 'status'];
                // Campos opcionais que podem ser limpos
                const optionalFields = ['crmv', 'cpf', 'specialty', 'phone', 'email', 'user_id', 'professional_role_id'];
                
                for (let [key, value] of formData.entries()) {
                    if (key === 'id') continue;
                    const trimmedValue = value.trim();
                    
                    // Campos obrigatórios sempre são enviados se tiverem valor
                    if (requiredFields.includes(key) && trimmedValue) {
                        data[key] = trimmedValue;
                    }
                    // Campos opcionais: envia valor se preenchido, ou null se vazio (permite limpar)
                    else if (optionalFields.includes(key)) {
                        // Remove máscaras antes de processar
                        if (key === 'phone' || key === 'cpf') {
                            const cleanedValue = trimmedValue.replace(/\D/g, '');
                            data[key] = cleanedValue || null;
                        } else if (key === 'user_id' || key === 'professional_role_id') {
                            // Campos numéricos
                            data[key] = trimmedValue ? parseInt(trimmedValue) : null;
                        } else {
                            data[key] = trimmedValue || null;
                        }
                    }
                    // Outros campos (se houver)
                    else if (trimmedValue) {
                        data[key] = trimmedValue;
                    }
                }
                
                const response = await apiRequest(`/v1/clinic/professionals/${professionalId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/clinic/professionals');
                }
                
                showAlert('Profissional atualizado com sucesso!', 'success');
                
                // Upload de foto se houver
                const photoInput = document.getElementById('editProfessionalPhotoInput');
                const editProfessionalId = document.getElementById('editProfessionalId').value;
                if (photoInput && photoInput.files && photoInput.files.length > 0 && editProfessionalId) {
                    await uploadProfessionalPhoto(parseInt(editProfessionalId), photoInput.files[0]);
                }
                
                bootstrap.Modal.getInstance(document.getElementById('editProfessionalModal')).hide();
                loadProfessionals();
            } catch (error) {
                showAlert(error.message || 'Erro ao atualizar profissional. Tente novamente.', 'danger');
            } finally {
                if (submitEditBtn) {
                    submitEditBtn.disabled = false;
                    submitEditBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar Alterações';
                }
            }
        });
    }
});

async function loadProfessionals() {
    try {
        document.getElementById('loadingProfessionals').style.display = 'block';
        document.getElementById('professionalsList').style.display = 'none';
        
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('limit', pageSize);
        
        const search = document.getElementById('searchInput')?.value.trim();
        if (search) {
            params.append('search', search);
        }
        
        const statusFilter = document.getElementById('statusFilter')?.value;
        if (statusFilter) {
            params.append('status', statusFilter);
        }
        
        const sortFilter = document.getElementById('sortFilter')?.value;
        if (sortFilter) {
            params.append('sort', sortFilter);
        }
        
        const response = await apiRequest('/v1/clinic/professionals?' + params.toString(), {
            cacheTTL: 10000
        });
        
        console.log('Resposta completa da API:', response);
        
        // A resposta pode vir em response.data ou diretamente em response
        // Garante que professionals seja sempre um array
        let professionalsData = response.data || response || [];
        
        // Se não for array, tenta converter
        if (!Array.isArray(professionalsData)) {
            console.warn('Profissionais não é um array, tentando converter:', professionalsData);
            if (professionalsData && typeof professionalsData === 'object') {
                // Se for um objeto com propriedades, tenta extrair array
                professionalsData = Object.values(professionalsData).find(v => Array.isArray(v)) || [];
            } else {
                professionalsData = [];
            }
        }
        
        professionals = professionalsData;
        paginationMeta = response.meta || response.pagination || {};
        const total = paginationMeta.total || professionals.length;
        const totalPages = Math.ceil(total / pageSize);
        
        console.log('Profissionais carregados:', professionals.length);
        console.log('Total:', total);
        console.log('Páginas:', totalPages);
        console.log('Tipo de professionals:', Array.isArray(professionals) ? 'Array' : typeof professionals);
        
        renderProfessionals();
        renderPagination(totalPages);
    } catch (error) {
        showAlert('Erro ao carregar profissionais: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingProfessionals').style.display = 'none';
        document.getElementById('professionalsList').style.display = 'block';
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
    loadProfessionals();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderProfessionals() {
    const tbody = document.getElementById('professionalsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('professionalsCountBadge');
    
    // Garante que professionals seja um array
    if (!Array.isArray(professionals)) {
        console.error('professionals não é um array:', professionals, typeof professionals);
        professionals = [];
    }
    
    if (professionals.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        const total = paginationMeta?.total || professionals.length;
        countBadge.textContent = total;
    }
    
    tbody.innerHTML = professionals.map(professional => {
        const statusBadge = professional.status === 'active' 
            ? '<span class="badge bg-success">Ativo</span>'
            : '<span class="badge bg-secondary">Inativo</span>';
        
        return `
        <tr>
            <td>
                <div class="fw-medium">${escapeHtml(professional.name || 'Sem nome')}</div>
            </td>
            <td>${escapeHtml(professional.crmv || '-')}</td>
            <td>${escapeHtml(professional.specialty || '-')}</td>
            <td>${escapeHtml(professional.email || '-')}</td>
            <td>${escapeHtml(professional.phone || '-')}</td>
            <td>${statusBadge}</td>
            <td>
                <small class="text-muted">${formatDate(professional.created_at)}</small>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="editProfessional(${professional.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProfessional(${professional.id})" title="Deletar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        `;
    }).join('');
}

async function editProfessional(id) {
    try {
        const response = await apiRequest(`/v1/clinic/professionals/${id}`);
        const professional = response.data;
        
        document.getElementById('editProfessionalId').value = professional.id;
        document.getElementById('editProfessionalName').value = professional.name || '';
        document.getElementById('editProfessionalCrmv').value = professional.crmv || '';
        document.getElementById('editProfessionalEmail').value = professional.email || '';
        document.getElementById('editProfessionalPhone').value = professional.phone || '';
        document.getElementById('editProfessionalCpf').value = professional.cpf || '';
        document.getElementById('editProfessionalSpecialty').value = professional.specialty || '';
        document.getElementById('editProfessionalStatus').value = professional.status || 'active';
        // Se tiver user_id, seleciona o usuário e carrega dados
        if (professional.user_id) {
            document.getElementById('editProfessionalUserId').value = professional.user_id;
            await handleUserSelection(professional.user_id, 'edit');
        }
        
        // Se tiver professional_role_id, seleciona a função e mostra/oculta CRMV
        if (professional.professional_role_id) {
            document.getElementById('editProfessionalRoleId').value = professional.professional_role_id;
            handleRoleSelection(professional.professional_role_id, 'edit');
        }
        
        // Atualiza foto do profissional
        if (professional.photo_url) {
            document.getElementById('editProfessionalPhotoPreview').src = '/' + professional.photo_url;
            document.getElementById('removeEditProfessionalPhotoBtn').style.display = 'inline-block';
        } else {
            document.getElementById('editProfessionalPhotoPreview').src = '/img/default-user.svg';
            document.getElementById('removeEditProfessionalPhotoBtn').style.display = 'none';
        }
        
        const modal = new bootstrap.Modal(document.getElementById('editProfessionalModal'));
        modal.show();
    } catch (error) {
        showAlert('Erro ao carregar profissional: ' + error.message, 'danger');
    }
}

async function deleteProfessional(id) {
    const confirmed = await showConfirmModal(
        'Tem certeza que deseja deletar este profissional? Esta ação não pode ser desfeita.',
        'Confirmar Exclusão',
        'Deletar',
        'btn-danger'
    );
    
    if (!confirmed) return;
    
    try {
        await apiRequest(`/v1/clinic/professionals/${id}`, {
            method: 'DELETE'
        });
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/clinic/professionals');
        }
        
        showAlert('Profissional deletado com sucesso!', 'success');
        loadProfessionals();
    } catch (error) {
        showAlert('Erro ao deletar profissional: ' + error.message, 'danger');
    }
}

async function loadUsers() {
    try {
        const response = await apiRequest('/v1/users', {
            cacheTTL: 60000
        });
        
        if (response.data && response.data.users) {
            users = response.data.users;
            populateUserSelects();
        }
    } catch (error) {
        console.error('Erro ao carregar usuários:', error);
    }
}

function populateUserSelects() {
    const createSelect = document.getElementById('professionalUserId');
    const editSelect = document.getElementById('editProfessionalUserId');
    
    const populateSelect = (select) => {
        if (!select) return;
        
        // Limpa opções existentes (exceto a primeira)
        while (select.options.length > 1) {
            select.remove(1);
        }
        
        // Adiciona usuários
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = `${user.name || 'Sem nome'} (${user.email})`;
            select.appendChild(option);
        });
    };
    
    populateSelect(createSelect);
    populateSelect(editSelect);
}

async function loadProfessionalRoles() {
    try {
        console.log('=== Carregando funções profissionais... ===');
        const response = await apiRequest('/v1/clinic/professionals/roles', {
            cacheTTL: 60000
        });
        
        console.log('Resposta da API de roles:', response);
        
        if (response.data && Array.isArray(response.data)) {
            professionalRoles = response.data;
            console.log('✅ Funções profissionais carregadas:', professionalRoles);
            console.log('Total de funções:', professionalRoles.length);
            
            // Verifica se há função "Veterinário"
            const veterinarioRole = professionalRoles.find(r => {
                const name = (r.name || '').toLowerCase();
                return name.includes('veterinário') || name.includes('veterinario') || name.includes('veterin');
            });
            if (veterinarioRole) {
                console.log('✅ Função Veterinário encontrada:', veterinarioRole);
            } else {
                console.warn('⚠️ Função Veterinário NÃO encontrada nas roles carregadas!');
            }
            
            populateRoleSelects();
        } else {
            console.warn('⚠️ Nenhuma função profissional retornada pela API ou formato inválido');
            console.warn('Response.data:', response.data);
        }
    } catch (error) {
        console.error('❌ Erro ao carregar funções profissionais:', error);
        showAlert('Erro ao carregar funções profissionais: ' + error.message, 'danger');
    }
}

function populateRoleSelects() {
    const createSelect = document.getElementById('professionalRoleId');
    const editSelect = document.getElementById('editProfessionalRoleId');
    
    console.log('Populando selects de roles:', { 
        createSelect: !!createSelect, 
        editSelect: !!editSelect, 
        rolesCount: professionalRoles.length 
    });
    
    const populateSelect = (select) => {
        if (!select) {
            console.warn('Select não encontrado');
            return;
        }
        
        // Limpa opções existentes (exceto a primeira)
        while (select.options.length > 1) {
            select.remove(1);
        }
        
        // Adiciona funções profissionais
        professionalRoles.forEach(role => {
            const option = document.createElement('option');
            option.value = role.id;
            option.textContent = role.name;
            select.appendChild(option);
            console.log('Role adicionada ao select:', { id: role.id, name: role.name });
        });
    };
    
    populateSelect(createSelect);
    populateSelect(editSelect);
}

async function handleUserSelection(userId, formType) {
    if (!userId) {
        // Limpa campos se nenhum usuário selecionado
        if (formType === 'create') {
            document.getElementById('professionalEmail').value = '';
            document.getElementById('professionalName').value = '';
        } else {
            document.getElementById('editProfessionalEmail').value = '';
            document.getElementById('editProfessionalName').value = '';
        }
        return;
    }
    
    try {
        // Busca dados do usuário
        const response = await apiRequest(`/v1/users/${userId}`);
        const user = response.data;
        
        if (!user) {
            showAlert('Usuário não encontrado', 'danger');
            return;
        }
        
        // Preenche campos automaticamente
        if (formType === 'create') {
            document.getElementById('professionalEmail').value = user.email || '';
            document.getElementById('professionalName').value = user.name || '';
        } else {
            document.getElementById('editProfessionalEmail').value = user.email || '';
            document.getElementById('editProfessionalName').value = user.name || '';
        }
    } catch (error) {
        console.error('Erro ao buscar dados do usuário:', error);
        showAlert('Erro ao carregar dados do usuário: ' + error.message, 'danger');
    }
}

function handleRoleSelection(roleId, formType) {
    console.log('=== handleRoleSelection chamado ===');
    console.log('Parâmetros:', { roleId, formType, professionalRolesLength: professionalRoles.length });
    console.log('Array professionalRoles:', professionalRoles);
    
    if (!roleId) {
        console.log('Nenhum roleId fornecido, ocultando CRMV');
        // Oculta CRMV se nenhuma função selecionada
        if (formType === 'create') {
            const crmvRow = document.getElementById('crmvRow');
            const crmvInput = document.getElementById('professionalCrmv');
            if (crmvRow) crmvRow.style.display = 'none';
            if (crmvInput) {
                crmvInput.required = false;
                crmvInput.value = '';
            }
        } else {
            const crmvRow = document.getElementById('editCrmvRow');
            const crmvInput = document.getElementById('editProfessionalCrmv');
            if (crmvRow) crmvRow.style.display = 'none';
            if (crmvInput) {
                crmvInput.required = false;
                crmvInput.value = '';
            }
        }
        return;
    }
    
    // Busca a função selecionada
    const role = professionalRoles.find(r => r.id == roleId || r.id === parseInt(roleId));
    
    console.log('Role encontrada:', role);
    
    if (!role) {
        console.warn('Role não encontrada para ID:', roleId, 'Roles disponíveis:', professionalRoles);
        return;
    }
    
    // Verifica se é Veterinário (pode ser "Veterinário", "Veterinario", etc.)
    // Normaliza a string removendo acentos para comparação mais robusta
    const roleName = role.name || '';
    const roleNameLower = roleName.toLowerCase();
    
    // Remove acentos para comparação
    const roleNameNormalized = roleNameLower
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove diacríticos
        .toLowerCase();
    
    const isVeterinario = roleNameNormalized.includes('veterinario') || 
                         roleNameLower.includes('veterinário') ||
                         roleNameLower.includes('veterinario') ||
                         roleName.includes('Veterin'); // Case insensitive parcial
    
    console.log('Verificação veterinário:', { 
        roleName, 
        roleNameLower, 
        roleNameNormalized, 
        isVeterinario 
    });
    
    if (formType === 'create') {
        const crmvRow = document.getElementById('crmvRow');
        const crmvInput = document.getElementById('professionalCrmv');
        const hintEl = document.getElementById('professionalUserRoleHint');
        
        if (!crmvRow || !crmvInput) {
            console.error('Elementos CRMV não encontrados no formulário de criação');
            return;
        }
        
        if (isVeterinario) {
            crmvRow.style.display = 'block';
            crmvInput.required = true;
            if (hintEl) hintEl.textContent = `${role.name} - CRMV obrigatório`;
            console.log('CRMV mostrado para veterinário');
        } else {
            crmvRow.style.display = 'none';
            crmvInput.required = false;
            crmvInput.value = '';
            if (hintEl) hintEl.textContent = `${role.name} - CRMV não necessário`;
            console.log('CRMV ocultado para função não-veterinária');
        }
    } else {
        const crmvRow = document.getElementById('editCrmvRow');
        const crmvInput = document.getElementById('editProfessionalCrmv');
        const hintEl = document.getElementById('editProfessionalUserRoleHint');
        
        if (!crmvRow || !crmvInput) {
            console.error('Elementos CRMV não encontrados no formulário de edição');
            return;
        }
        
        if (isVeterinario) {
            crmvRow.style.display = 'block';
            crmvInput.required = true;
            if (hintEl) hintEl.textContent = `${role.name} - CRMV obrigatório`;
            console.log('CRMV mostrado para veterinário (edição)');
        } else {
            crmvRow.style.display = 'none';
            crmvInput.required = false;
            crmvInput.value = '';
            if (hintEl) hintEl.textContent = `${role.name} - CRMV não necessário`;
            console.log('CRMV ocultado para função não-veterinária (edição)');
        }
    }
}


function formatCurrency(amount, currency = 'brl') {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: currency.toUpperCase()
    }).format(amount / 100);
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

// Funções para upload de foto de profissional
function handleProfessionalPhotoPreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const removeBtn = inputId === 'createProfessionalPhotoInput' 
        ? document.getElementById('removeCreateProfessionalPhotoBtn')
        : document.getElementById('removeEditProfessionalPhotoBtn');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            if (removeBtn) removeBtn.style.display = 'inline-block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeProfessionalPhoto(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const removeBtn = inputId === 'createProfessionalPhotoInput' 
        ? document.getElementById('removeCreateProfessionalPhotoBtn')
        : document.getElementById('removeEditProfessionalPhotoBtn');
    
    input.value = '';
    preview.src = '/img/default-user.svg';
    if (removeBtn) removeBtn.style.display = 'none';
}

async function uploadProfessionalPhoto(professionalId, file) {
    try {
        const formData = new FormData();
        formData.append('photo', file);
        
        const response = await apiRequest(`/v1/files/professionals/${professionalId}/photo`, {
            method: 'POST',
            body: formData,
            isFormData: true
        });
        
        if (response && response.data) {
            console.log('Foto do profissional enviada com sucesso');
        }
    } catch (error) {
        console.error('Erro ao fazer upload da foto:', error);
        showAlert('Erro ao fazer upload da foto: ' + (error.message || 'Erro desconhecido'), 'warning');
    }
}
</script>

