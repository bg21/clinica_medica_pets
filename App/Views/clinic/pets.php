<?php
/**
 * View de Pets
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-heart-pulse text-primary"></i>
                Pets
            </h1>
            <p class="text-muted mb-0">Gerencie os animais dos tutores</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPetModal">
            <i class="bi bi-plus-circle"></i> Novo Pet
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
                        <input type="text" class="form-control" id="searchInput" placeholder="Nome, espécie, raça...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Espécie</label>
                    <select class="form-select" id="speciesFilter">
                        <option value="">Todas</option>
                        <option value="cão">Cão</option>
                        <option value="gato">Gato</option>
                        <option value="ave">Ave</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordenar por</label>
                    <select class="form-select" id="sortFilter">
                        <option value="name">Nome</option>
                        <option value="created_at">Data de Criação</option>
                        <option value="species">Espécie</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadPets()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Pets -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Pets
            </h5>
            <span class="badge bg-primary" id="petsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingPets" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando pets...</p>
            </div>
            <div id="petsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Chip</th>
                                <th>Porte</th>
                                <th>Espécie</th>
                                <th>Raça</th>
                                <th>Idade</th>
                                <th>Sexo</th>
                                <th>Tutor</th>
                                <th>Criado em</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="petsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-heart-pulse fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum pet encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro pet.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createPetModal">
                        <i class="bi bi-plus-circle"></i> Criar Pet
                    </button>
                </div>
                <div id="paginationContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Pet -->
<div class="modal fade" id="createPetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-heart-pulse me-2"></i>
                    Novo Pet
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createPetForm" novalidate>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Campos marcados com <span class="text-danger">*</span> são obrigatórios.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-person me-1"></i>
                                Tutor (Cliente) <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="customer_id" id="petCustomerId" required>
                                <option value="">Selecione o tutor...</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um tutor.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Nome do Pet <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="name" 
                                id="petName"
                                placeholder="Ex: Rex"
                                required
                                minlength="2"
                                maxlength="255">
                            <div class="invalid-feedback">
                                O nome deve ter pelo menos 2 caracteres.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-upc-scan me-1"></i>
                                Chip (Número de Identificação)
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="chip" 
                                id="petChip"
                                placeholder="Ex: 123456789012345"
                                maxlength="100">
                            <small class="form-text text-muted">
                                Número do chip eletrônico do animal (opcional)
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-rulers me-1"></i>
                                Porte
                            </label>
                            <select class="form-select" name="porte" id="petPorte">
                                <option value="">Selecione...</option>
                                <option value="pequeno">Pequeno</option>
                                <option value="médio">Médio</option>
                                <option value="grande">Grande</option>
                                <option value="gigante">Gigante</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-paw me-1"></i>
                                Espécie
                            </label>
                            <select class="form-select" name="species" id="petSpecies">
                                <option value="">Selecione...</option>
                                <option value="cão">Cão</option>
                                <option value="gato">Gato</option>
                                <option value="ave">Ave</option>
                                <option value="roedor">Roedor</option>
                                <option value="réptil">Réptil</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag-fill me-1"></i>
                                Raça
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="breed" 
                                id="petBreed"
                                placeholder="Ex: Golden Retriever"
                                maxlength="100">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-gender-ambiguous me-1"></i>
                                Sexo
                            </label>
                            <select class="form-select" name="gender" id="petGender">
                                <option value="">Selecione...</option>
                                <option value="macho">Macho</option>
                                <option value="femea">Fêmea</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-event me-1"></i>
                                Data de Nascimento
                            </label>
                            <input 
                                type="date" 
                                class="form-control" 
                                name="birth_date" 
                                id="petBirthDate">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-speedometer2 me-1"></i>
                                Peso (kg)
                            </label>
                            <input 
                                type="number" 
                                class="form-control" 
                                name="weight" 
                                id="petWeight"
                                placeholder="0.00"
                                step="0.01"
                                min="0"
                                max="999.99">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-palette me-1"></i>
                                Cor/Pelagem
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="color" 
                                id="petColor"
                                placeholder="Ex: Marrom"
                                maxlength="50">
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
                            id="petNotes"
                            rows="3"
                            placeholder="Informações adicionais sobre o pet..."
                            maxlength="1000"></textarea>
                        <small class="form-text text-muted">
                            <span id="notesCounter">0</span>/1000 caracteres
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitPetBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Criar Pet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Pet -->
<div class="modal fade" id="editPetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>
                    Editar Pet
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPetForm" novalidate>
                <input type="hidden" id="editPetId" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-person me-1"></i>
                                Tutor (Cliente) <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="customer_id" id="editPetCustomerId" required>
                                <option value="">Selecione o tutor...</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um tutor.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Nome do Pet <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="name" 
                                id="editPetName"
                                required
                                minlength="2"
                                maxlength="255">
                            <div class="invalid-feedback">
                                O nome deve ter pelo menos 2 caracteres.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-upc-scan me-1"></i>
                                Chip (Número de Identificação)
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="chip" 
                                id="editPetChip"
                                placeholder="Ex: 123456789012345"
                                maxlength="100">
                            <small class="form-text text-muted">
                                Número do chip eletrônico do animal (opcional)
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-rulers me-1"></i>
                                Porte
                            </label>
                            <select class="form-select" name="porte" id="editPetPorte">
                                <option value="">Selecione...</option>
                                <option value="pequeno">Pequeno</option>
                                <option value="médio">Médio</option>
                                <option value="grande">Grande</option>
                                <option value="gigante">Gigante</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-paw me-1"></i>
                                Espécie
                            </label>
                            <select class="form-select" name="species" id="editPetSpecies">
                                <option value="">Selecione...</option>
                                <option value="cão">Cão</option>
                                <option value="gato">Gato</option>
                                <option value="ave">Ave</option>
                                <option value="roedor">Roedor</option>
                                <option value="réptil">Réptil</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag-fill me-1"></i>
                                Raça
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="breed" 
                                id="editPetBreed"
                                maxlength="100">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-gender-ambiguous me-1"></i>
                                Sexo
                            </label>
                            <select class="form-select" name="gender" id="editPetGender">
                                <option value="">Selecione...</option>
                                <option value="macho">Macho</option>
                                <option value="femea">Fêmea</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-event me-1"></i>
                                Data de Nascimento
                            </label>
                            <input 
                                type="date" 
                                class="form-control" 
                                name="birth_date" 
                                id="editPetBirthDate">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-speedometer2 me-1"></i>
                                Peso (kg)
                            </label>
                            <input 
                                type="number" 
                                class="form-control" 
                                name="weight" 
                                id="editPetWeight"
                                step="0.01"
                                min="0"
                                max="999.99">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="bi bi-palette me-1"></i>
                                Cor/Pelagem
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="color" 
                                id="editPetColor"
                                maxlength="50">
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
                            id="editPetNotes"
                            rows="3"
                            maxlength="1000"></textarea>
                        <small class="form-text text-muted">
                            <span id="editNotesCounter">0</span>/1000 caracteres
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitEditPetBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let pets = [];
let paginationMeta = {};
let customers = [];
let currentPage = 1;
let pageSize = 20;
let searchTimeout = null;

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

function calculateAge(birthDate) {
    if (!birthDate) return '-';
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age > 0 ? `${age} ano${age > 1 ? 's' : ''}` : 'Menos de 1 ano';
}

document.addEventListener('DOMContentLoaded', () => {
    loadCustomers();
    loadPets();
    
    // Debounce na busca
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            currentPage = 1;
            loadPets();
        }, 500));
    }
    
    // Contador de caracteres para observações
    const notesInput = document.getElementById('petNotes');
    const notesCounter = document.getElementById('notesCounter');
    if (notesInput && notesCounter) {
        notesInput.addEventListener('input', function() {
            notesCounter.textContent = this.value.length;
        });
    }
    
    const editNotesInput = document.getElementById('editPetNotes');
    const editNotesCounter = document.getElementById('editNotesCounter');
    if (editNotesInput && editNotesCounter) {
        editNotesInput.addEventListener('input', function() {
            editNotesCounter.textContent = this.value.length;
        });
    }
    
    // Reset do formulário quando a modal é fechada
    const createPetModal = document.getElementById('createPetModal');
    if (createPetModal) {
        createPetModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('createPetForm');
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
    
    const editPetModal = document.getElementById('editPetModal');
    if (editPetModal) {
        editPetModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('editPetForm');
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
    
    // Form criar pet
    const createPetForm = document.getElementById('createPetForm');
    const submitBtn = document.getElementById('submitPetBtn');
    
    if (createPetForm) {
        createPetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!createPetForm.checkValidity()) {
                createPetForm.classList.add('was-validated');
                return;
            }
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Criando...';
            }
            
            try {
                const formData = new FormData(createPetForm);
                const data = {};
                
                for (let [key, value] of formData.entries()) {
                    const trimmedValue = value.trim();
                    if (trimmedValue) {
                        if (key === 'customer_id') {
                            data[key] = parseInt(trimmedValue);
                        } else if (key === 'weight') {
                            data[key] = parseFloat(trimmedValue) || null;
                        } else {
                            data[key] = trimmedValue;
                        }
                    }
                }
                
                const response = await apiRequest('/v1/clinic/pets', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/clinic/pets');
                }
                
                showAlert('Pet criado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('createPetModal')).hide();
                loadPets();
            } catch (error) {
                showAlert(error.message || 'Erro ao criar pet. Tente novamente.', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Pet';
                }
            }
        });
    }
    
    // Form editar pet
    const editPetForm = document.getElementById('editPetForm');
    const submitEditBtn = document.getElementById('submitEditPetBtn');
    
    if (editPetForm) {
        editPetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!editPetForm.checkValidity()) {
                editPetForm.classList.add('was-validated');
                return;
            }
            
            const petId = document.getElementById('editPetId').value;
            
            if (submitEditBtn) {
                submitEditBtn.disabled = true;
                submitEditBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(editPetForm);
                const data = {};
                
                // Campos opcionais que podem ser limpos (enviar null se vazio)
                const optionalFields = ['chip', 'porte', 'species', 'breed', 'gender', 'birth_date', 'weight', 'color', 'notes'];
                
                for (let [key, value] of formData.entries()) {
                    if (key === 'id') continue;
                    const trimmedValue = value.trim();
                    
                    // Campos obrigatórios sempre são enviados se tiverem valor
                    if (key === 'customer_id' || key === 'name') {
                        if (trimmedValue) {
                            if (key === 'customer_id') {
                                data[key] = parseInt(trimmedValue);
                            } else {
                                data[key] = trimmedValue;
                            }
                        }
                    }
                    // Campos opcionais: envia valor se preenchido, ou null se vazio (permite limpar)
                    else if (optionalFields.includes(key)) {
                        if (key === 'weight') {
                            data[key] = trimmedValue ? parseFloat(trimmedValue) : null;
                        } else {
                            // Envia string vazia como null para limpar campos opcionais
                            data[key] = trimmedValue || null;
                        }
                    }
                }
                
                const response = await apiRequest(`/v1/clinic/pets/${petId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/clinic/pets');
                }
                
                showAlert('Pet atualizado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editPetModal')).hide();
                loadPets();
            } catch (error) {
                showAlert(error.message || 'Erro ao atualizar pet. Tente novamente.', 'danger');
            } finally {
                if (submitEditBtn) {
                    submitEditBtn.disabled = false;
                    submitEditBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar Alterações';
                }
            }
        });
    }
});

async function loadCustomers() {
    try {
        const response = await apiRequest('/v1/customers?limit=1000', {
            cacheTTL: 60000
        });
        customers = response.data || [];
        
        // Preenche selects de clientes
        const customerSelects = ['petCustomerId', 'editPetCustomerId'];
        customerSelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                const currentValue = select.value;
                select.innerHTML = '<option value="">Selecione o tutor...</option>';
                customers.forEach(customer => {
                    const option = document.createElement('option');
                    option.value = customer.id;
                    option.textContent = `${customer.name || 'Sem nome'} (${customer.email})`;
                    select.appendChild(option);
                });
                if (currentValue) {
                    select.value = currentValue;
                }
            }
        });
    } catch (error) {
        console.error('Erro ao carregar clientes:', error);
    }
}

async function loadPets() {
    try {
        document.getElementById('loadingPets').style.display = 'block';
        document.getElementById('petsList').style.display = 'none';
        
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('limit', pageSize);
        
        const search = document.getElementById('searchInput')?.value.trim();
        if (search) {
            params.append('search', search);
        }
        
        const speciesFilter = document.getElementById('speciesFilter')?.value;
        if (speciesFilter) {
            params.append('species', speciesFilter);
        }
        
        const sortFilter = document.getElementById('sortFilter')?.value;
        if (sortFilter) {
            params.append('sort', sortFilter);
        }
        
        const response = await apiRequest('/v1/clinic/pets?' + params.toString(), {
            cacheTTL: 10000
        });
        
        pets = response.data || [];
        paginationMeta = response.meta || {};
        const total = paginationMeta.total || pets.length;
        const totalPages = Math.ceil(total / pageSize);
        
        renderPets();
        renderPagination(totalPages);
    } catch (error) {
        showAlert('Erro ao carregar pets: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingPets').style.display = 'none';
        document.getElementById('petsList').style.display = 'block';
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
    loadPets();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderPets() {
    const tbody = document.getElementById('petsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('petsCountBadge');
    
    if (pets.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        const total = paginationMeta?.total || pets.length;
        countBadge.textContent = total;
    }
    
    tbody.innerHTML = pets.map(pet => {
        const customer = customers.find(c => c.id === pet.customer_id);
        const customerName = customer ? (customer.name || customer.email) : 'N/A';
        const age = calculateAge(pet.birth_date);
        const genderIcon = pet.gender === 'macho' ? '♂' : pet.gender === 'femea' ? '♀' : '';
        
        const chipDisplay = pet.chip ? `<span class="badge bg-info">${escapeHtml(pet.chip)}</span>` : '<span class="text-muted">-</span>';
        const porteDisplay = pet.porte ? `<span class="badge bg-secondary">${escapeHtml(pet.porte.charAt(0).toUpperCase() + pet.porte.slice(1))}</span>` : '<span class="text-muted">-</span>';
        
        return `
        <tr>
            <td>
                <div class="fw-medium">${escapeHtml(pet.name || 'Sem nome')}</div>
            </td>
            <td>${chipDisplay}</td>
            <td>${porteDisplay}</td>
            <td>${escapeHtml(pet.species || '-')}</td>
            <td>${escapeHtml(pet.breed || '-')}</td>
            <td>${age}</td>
            <td>${genderIcon} ${escapeHtml(pet.gender || '-')}</td>
            <td>
                <small>${escapeHtml(customerName)}</small>
            </td>
            <td>
                <small class="text-muted">${formatDate(pet.created_at)}</small>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="editPet(${pet.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deletePet(${pet.id})" title="Deletar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        `;
    }).join('');
}

async function editPet(id) {
    try {
        const response = await apiRequest(`/v1/clinic/pets/${id}`);
        const pet = response.data;
        
        document.getElementById('editPetId').value = pet.id;
        document.getElementById('editPetCustomerId').value = pet.customer_id;
        document.getElementById('editPetName').value = pet.name || '';
        document.getElementById('editPetChip').value = pet.chip || '';
        document.getElementById('editPetPorte').value = pet.porte || '';
        document.getElementById('editPetSpecies').value = pet.species || '';
        document.getElementById('editPetBreed').value = pet.breed || '';
        document.getElementById('editPetGender').value = pet.gender || '';
        document.getElementById('editPetBirthDate').value = pet.birth_date || '';
        document.getElementById('editPetWeight').value = pet.weight || '';
        document.getElementById('editPetColor').value = pet.color || '';
        document.getElementById('editPetNotes').value = pet.notes || '';
        document.getElementById('editNotesCounter').textContent = (pet.notes || '').length;
        
        // Atualiza select de clientes
        const select = document.getElementById('editPetCustomerId');
        if (select) {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Selecione o tutor...</option>';
            customers.forEach(customer => {
                const option = document.createElement('option');
                option.value = customer.id;
                option.textContent = `${customer.name || 'Sem nome'} (${customer.email})`;
                if (customer.id == currentValue) option.selected = true;
                select.appendChild(option);
            });
        }
        
        const modal = new bootstrap.Modal(document.getElementById('editPetModal'));
        modal.show();
    } catch (error) {
        showAlert('Erro ao carregar pet: ' + error.message, 'danger');
    }
}

async function deletePet(id) {
    const confirmed = await showConfirmModal(
        'Tem certeza que deseja deletar este pet? Esta ação não pode ser desfeita.',
        'Confirmar Exclusão',
        'Deletar',
        'btn-danger'
    );
    
    if (!confirmed) return;
    
    try {
        await apiRequest(`/v1/clinic/pets/${id}`, {
            method: 'DELETE'
        });
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/clinic/pets');
        }
        
        showAlert('Pet deletado com sucesso!', 'success');
        loadPets();
    } catch (error) {
        showAlert('Erro ao deletar pet: ' + error.message, 'danger');
    }
}

// Função auxiliar para modal de confirmação
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

