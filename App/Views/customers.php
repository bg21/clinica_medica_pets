<?php
/**
 * View de Clientes
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-people text-primary"></i>
                Clientes
            </h1>
            <p class="text-muted mb-0">Gerencie seus clientees e assinaturas</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
            <i class="bi bi-plus-circle"></i> Novo Cliente
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
                            <p class="text-muted mb-1 small fw-medium">Total de Clientes</p>
                            <h2 class="mb-0 fw-bold" id="totalCustomersStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-people fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Clientes Ativos</p>
                            <h2 class="mb-0 fw-bold" id="activeCustomersStat">-</h2>
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
                            <p class="text-muted mb-1 small fw-medium">Com Assinaturas</p>
                            <h2 class="mb-0 fw-bold" id="withSubscriptionsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-credit-card fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Novos (30 dias)</p>
                            <h2 class="mb-0 fw-bold" id="newCustomersStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-person-plus fs-1"></i>
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
                    <label class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Email, nome...">
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
                        <option value="created_at">Data de Criação</option>
                        <option value="email">Email</option>
                        <option value="name">Nome</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadCustomers()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Clientes -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Clientes
            </h5>
            <span class="badge bg-primary" id="customersCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingCustomers" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando clientees...</p>
            </div>
            <div id="customersList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th>Stripe ID</th>
                                <th>Assinaturas</th>
                                <th>Criado em</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-people fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum cliente encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro cliente.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
                        <i class="bi bi-plus-circle"></i> Criar Cliente
                    </button>
                </div>
                <div id="paginationContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Cliente -->
<div class="modal fade" id="createCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2"></i>
                    Novo Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createCustomerForm" novalidate>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Campos marcados com <span class="text-danger">*</span> são obrigatórios.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-envelope me-1"></i>
                                Email <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    name="email" 
                                    id="customerEmail" 
                                    placeholder="exemplo@email.com"
                                    required
                                    autocomplete="email">
                                <div class="invalid-feedback" id="emailError">
                                    Por favor, insira um email válido.
                                </div>
                                <div class="valid-feedback" id="emailSuccess" style="display: none;">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Email disponível
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                O email será usado para login e comunicação.
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-person me-1"></i>
                                Nome Completo
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    name="name" 
                                    id="customerName"
                                    placeholder="João da Silva"
                                    minlength="2"
                                    maxlength="255"
                                    pattern="[A-Za-zÀ-ÿ\s]+"
                                    autocomplete="name">
                                <div class="invalid-feedback" id="nameError">
                                    O nome deve ter pelo menos 2 caracteres e conter apenas letras.
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Nome completo do cliente (opcional).
                            </small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-telephone me-1"></i>
                                Telefone
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-telephone"></i>
                                </span>
                                <input 
                                    type="tel" 
                                    class="form-control" 
                                    name="phone" 
                                    id="customerPhone"
                                    placeholder="(11) 98765-4321"
                                    pattern="[\(]?[0-9]{2}[\)]?[\s]?[0-9]{4,5}[-]?[0-9]{4}"
                                    autocomplete="tel">
                                <div class="invalid-feedback" id="phoneError">
                                    Por favor, insira um telefone válido. Ex: (11) 98765-4321
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Telefone com DDD (opcional).
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-telephone-fill me-1"></i>
                                Telefone Alternativo
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-telephone-fill"></i>
                                </span>
                                <input 
                                    type="tel" 
                                    class="form-control" 
                                    name="phone_alt" 
                                    id="customerPhoneAlt"
                                    placeholder="(11) 91234-5678"
                                    pattern="[\(]?[0-9]{2}[\)]?[\s]?[0-9]{4,5}[-]?[0-9]{4}"
                                    autocomplete="tel">
                                <div class="invalid-feedback" id="phoneAltError">
                                    Por favor, insira um telefone válido.
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Telefone alternativo (opcional).
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
                            name="notes" 
                            id="customerNotes"
                            rows="3"
                            placeholder="Informações adicionais sobre o cliente..."
                            maxlength="500"></textarea>
                        <small class="form-text text-muted">
                            <span id="notesCounter">0</span>/500 caracteres
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitCustomerBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Criar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
let customers = [];
let paginationMeta = {};

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

document.addEventListener('DOMContentLoaded', () => {
    // Carrega dados imediatamente
    loadCustomers();
    
    // Debounce na busca
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            currentPage = 1;
            loadCustomers();
        }, 500));
    }
    
    // Validação de telefone (máscara)
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
    
    const phoneInput = document.getElementById('customerPhone');
    const phoneAltInput = document.getElementById('customerPhoneAlt');
    if (phoneInput) applyPhoneMask(phoneInput);
    if (phoneAltInput) applyPhoneMask(phoneAltInput);
    
    // Contador de caracteres para observações
    const notesInput = document.getElementById('customerNotes');
    const notesCounter = document.getElementById('notesCounter');
    if (notesInput && notesCounter) {
        notesInput.addEventListener('input', function() {
            notesCounter.textContent = this.value.length;
        });
    }
    
    // Validação de nome
    const nameInput = document.getElementById('customerName');
    if (nameInput) {
        nameInput.addEventListener('blur', function() {
            if (this.value.trim() && !this.validity.valid) {
                this.classList.add('is-invalid');
            } else if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
        
        nameInput.addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
        });
    }
    
    // Validação de telefone
    function validatePhone(input, errorElementId) {
        input.addEventListener('blur', function() {
            const value = this.value.replace(/\D/g, '');
            if (this.value.trim() && (value.length < 10 || value.length > 11)) {
                this.classList.add('is-invalid');
                const errorEl = document.getElementById(errorElementId);
                if (errorEl) {
                    errorEl.textContent = 'Telefone deve ter 10 ou 11 dígitos (com DDD)';
                }
            } else if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
        
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
        });
    }
    
    if (phoneInput) validatePhone(phoneInput, 'phoneError');
    if (phoneAltInput) validatePhone(phoneAltInput, 'phoneAltError');
    
    // Validação assíncrona de email duplicado
    const emailInput = document.getElementById('customerEmail');
    let emailCheckTimeout = null;
    
    if (emailInput) {
        emailInput.addEventListener('blur', async () => {
            const email = emailInput.value.trim();
            if (!email || !emailInput.validity.valid) {
                if (email && !emailInput.validity.valid) {
                    emailInput.classList.add('is-invalid');
                    document.getElementById('emailError').textContent = 'Por favor, insira um email válido.';
                }
                return;
            }
            
            clearTimeout(emailCheckTimeout);
            emailCheckTimeout = setTimeout(async () => {
                try {
                    const response = await apiRequest('/v1/customers?search=' + encodeURIComponent(email));
                    const existingCustomers = response.data || [];
                    const emailExists = existingCustomers.some(c => c.email.toLowerCase() === email.toLowerCase());
                    
                    if (emailExists) {
                        emailInput.classList.add('is-invalid');
                        emailInput.classList.remove('is-valid');
                        document.getElementById('emailError').textContent = 'Este email já está cadastrado';
                        document.getElementById('emailSuccess').style.display = 'none';
                    } else {
                        emailInput.classList.remove('is-invalid');
                        emailInput.classList.add('is-valid');
                        document.getElementById('emailError').textContent = '';
                        document.getElementById('emailSuccess').style.display = 'block';
                    }
                } catch (error) {
                    // Ignora erros na validação (não bloqueia o formulário)
                    console.error('Erro ao validar email:', error);
                }
            }, 500);
        });
        
        emailInput.addEventListener('input', () => {
            emailInput.classList.remove('is-invalid', 'is-valid');
            document.getElementById('emailError').textContent = 'Por favor, insira um email válido.';
            document.getElementById('emailSuccess').style.display = 'none';
        });
    }
    
    // Reset do formulário quando a modal é fechada
    const createCustomerModal = document.getElementById('createCustomerModal');
    if (createCustomerModal) {
        createCustomerModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('createCustomerForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                
                // Remove classes de validação
                const inputs = form.querySelectorAll('.is-invalid, .is-valid');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid', 'is-valid');
                });
                
                // Reseta mensagens
                document.getElementById('emailError').textContent = 'Por favor, insira um email válido.';
                document.getElementById('emailSuccess').style.display = 'none';
                if (notesCounter) notesCounter.textContent = '0';
            }
        });
    }
    
    // Form criar cliente
    const createCustomerForm = document.getElementById('createCustomerForm');
    const submitBtn = document.getElementById('submitCustomerBtn');
    
    if (createCustomerForm) {
        createCustomerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Validação HTML5
            if (!createCustomerForm.checkValidity()) {
                createCustomerForm.classList.add('was-validated');
                return;
            }
            
            // Validação customizada adicional
            const email = emailInput.value.trim();
            if (!email || !emailInput.validity.valid) {
                emailInput.classList.add('is-invalid');
                createCustomerForm.classList.add('was-validated');
                return;
            }
            
            // Verifica se email já existe (validação final)
            try {
                const checkResponse = await apiRequest('/v1/customers?search=' + encodeURIComponent(email));
                const existingCustomers = checkResponse.data || [];
                const emailExists = existingCustomers.some(c => c.email.toLowerCase() === email.toLowerCase());
                
                if (emailExists) {
                    emailInput.classList.add('is-invalid');
                    document.getElementById('emailError').textContent = 'Este email já está cadastrado';
                    createCustomerForm.classList.add('was-validated');
                    return;
                }
            } catch (error) {
                console.warn('Não foi possível verificar email duplicado:', error);
            }
            
            // Desabilita botão durante o envio
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Criando...';
            }
            
            try {
                const formData = new FormData(createCustomerForm);
                const data = {};
                
                // Processa dados do formulário
                for (let [key, value] of formData.entries()) {
                    const trimmedValue = value.trim();
                    if (trimmedValue) {
                        // Remove máscara de telefone
                        if (key === 'phone' || key === 'phone_alt') {
                            data[key] = trimmedValue.replace(/\D/g, '');
                        } else {
                            data[key] = trimmedValue;
                        }
                    }
                }
                
                const response = await apiRequest('/v1/customers', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                // Limpa cache após criar cliente
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/customers');
                }
                
                showAlert('Cliente criado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('createCustomerModal')).hide();
                
                // Reset do formulário já é feito pelo evento hidden.bs.modal
                loadCustomers();
            } catch (error) {
                showAlert(error.message || 'Erro ao criar cliente. Tente novamente.', 'danger');
            } finally {
                // Reabilita botão
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Cliente';
                }
            }
        });
    }
});

async function loadCustomers() {
    try {
        document.getElementById('loadingCustomers').style.display = 'block';
        document.getElementById('customersList').style.display = 'none';
        
        // Constrói query string com paginação e filtros
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
        
        const response = await apiRequest('/v1/customers?' + params.toString(), {
            cacheTTL: 10000 // Cache de 10 segundos
        });
        
        customers = response.data || [];
        paginationMeta = response.meta || {};
        const total = paginationMeta.total || customers.length;
        const totalPages = Math.ceil(total / pageSize);
        
        renderCustomers();
        renderPagination(totalPages);
    } catch (error) {
        showAlert('Erro ao carregar clientees: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingCustomers').style.display = 'none';
        document.getElementById('customersList').style.display = 'block';
    }
}

function renderPagination(totalPages) {
    const container = document.getElementById('paginationContainer');
    if (!container) {
        // Cria container de paginação se não existir
        const tableContainer = document.querySelector('.table-responsive');
        if (tableContainer && totalPages > 1) {
            const paginationDiv = document.createElement('div');
            paginationDiv.id = 'paginationContainer';
            paginationDiv.className = 'mt-3 d-flex justify-content-center';
            tableContainer.parentElement.appendChild(paginationDiv);
        } else {
            return;
        }
    }
    
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<nav><ul class="pagination">';
    
    // Botão anterior
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Anterior</a>
    </li>`;
    
    // Páginas
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
    
    // Botão próximo
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Próximo</a>
    </li>`;
    
    html += '</ul></nav>';
    container.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    loadCustomers();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderCustomers() {
    const tbody = document.getElementById('customersTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('customersCountBadge');
    
    if (customers.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        const total = paginationMeta?.total || customers.length;
        countBadge.textContent = total;
    }
    
    // Calcula estatísticas
    const stats = calculateCustomerStats();
    updateCustomerStats(stats);
    
    tbody.innerHTML = customers.map(customer => {
        const name = customer.name || 'Sem nome';
        const initial = name.charAt(0).toUpperCase();
        const subscriptionsCount = customer.subscriptions_count || 0;
        const hasSubscriptions = subscriptionsCount > 0;
        
        return `
        <tr>
            <td>
                <input type="checkbox" class="form-check-input" value="${customer.id}">
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-circle bg-primary text-white me-2" style="width: 36px; height: 36px; font-size: 0.875rem;">
                        ${initial}
                    </div>
                    <div>
                        <div class="fw-medium">${escapeHtml(name)}</div>
                        <small class="text-muted">ID: ${customer.id}</small>
                    </div>
                </div>
            </td>
            <td>
                <div>
                    <i class="bi bi-envelope text-muted me-1"></i>
                    ${escapeHtml(customer.email)}
                </div>
            </td>
            <td>
                ${customer.stripe_customer_id ? `
                    <code class="text-muted small">${escapeHtml(customer.stripe_customer_id)}</code>
                ` : '<span class="text-muted">-</span>'}
            </td>
            <td>
                ${hasSubscriptions ? `
                    <span class="badge bg-success">${subscriptionsCount}</span>
                ` : '<span class="badge bg-secondary">0</span>'}
            </td>
            <td>
                <small class="text-muted">${formatDate(customer.created_at)}</small>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <a href="/customer-details?id=${customer.id}" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
            </td>
        </tr>
        `;
    }).join('');
}

function calculateCustomerStats() {
    const total = customers.length;
    const active = customers.filter(c => c.status === 'active' || !c.status).length;
    const withSubscriptions = customers.filter(c => (c.subscriptions_count || 0) > 0).length;
    
    // Clientes criados nos últimos 30 dias
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    const newCustomers = customers.filter(c => {
        const createdDate = new Date(c.created_at);
        return createdDate >= thirtyDaysAgo;
    }).length;
    
    return { total, active, withSubscriptions, newCustomers };
}

function updateCustomerStats(stats) {
    document.getElementById('totalCustomersStat').textContent = formatNumber(stats.total);
    document.getElementById('activeCustomersStat').textContent = formatNumber(stats.active);
    document.getElementById('withSubscriptionsStat').textContent = formatNumber(stats.withSubscriptions);
    document.getElementById('newCustomersStat').textContent = formatNumber(stats.newCustomers);
}

</script>

