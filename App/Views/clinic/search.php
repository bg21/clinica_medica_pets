<?php
/**
 * View de Busca Avançada
 *
 * @var string $apiUrl URL base da API
 * @var array|null $user Dados do usuário autenticado
 * @var array|null $tenant Dados do tenant
 * @var string $currentPage Página atual (para highlight no menu)
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-search text-primary"></i>
                Busca Avançada
            </h1>
            <p class="text-muted mb-0">Busque por pets, clientes, agendamentos e profissionais</p>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Barra de Busca -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="searchForm" onsubmit="performSearch(event)">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="searchInput" class="form-label">
                            <i class="bi bi-search me-1"></i>
                            Termo de Busca
                        </label>
                        <input 
                            type="text" 
                            class="form-control form-control-lg" 
                            id="searchInput" 
                            placeholder="Digite o nome, email, telefone, chip, CRMV, etc..."
                            autocomplete="off"
                            required>
                        <small class="text-muted">Busca em pets, clientes, agendamentos e profissionais</small>
                    </div>
                    <div class="col-md-4">
                        <label for="searchTypes" class="form-label">
                            <i class="bi bi-funnel me-1"></i>
                            Tipos de Busca
                        </label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="typePets" value="pets" checked>
                            <label class="form-check-label" for="typePets">
                                <i class="bi bi-heart-pulse"></i> Pets
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="typeCustomers" value="customers" checked>
                            <label class="form-check-label" for="typeCustomers">
                                <i class="bi bi-people"></i> Clientes
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="typeAppointments" value="appointments" checked>
                            <label class="form-check-label" for="typeAppointments">
                                <i class="bi bi-calendar-check"></i> Agendamentos
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="typeProfessionals" value="professionals" checked>
                            <label class="form-check-label" for="typeProfessionals">
                                <i class="bi bi-person-badge"></i> Profissionais
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg" id="searchBtn">
                            <i class="bi bi-search me-2"></i>
                            Buscar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="clearSearch()">
                            <i class="bi bi-x-circle me-2"></i>
                            Limpar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div id="resultsContainer" style="display: none;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    Resultados da Busca
                </h5>
                <span class="badge bg-primary" id="totalResultsBadge">0 resultados</span>
            </div>
            <div class="card-body">
                <!-- Tabs para diferentes tipos de resultados -->
                <ul class="nav nav-tabs mb-3" id="resultsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pets-tab" data-bs-toggle="tab" data-bs-target="#petsResults" type="button" role="tab">
                            <i class="bi bi-heart-pulse me-1"></i>
                            Pets <span class="badge bg-secondary" id="petsCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customersResults" type="button" role="tab">
                            <i class="bi bi-people me-1"></i>
                            Clientes <span class="badge bg-secondary" id="customersCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointmentsResults" type="button" role="tab">
                            <i class="bi bi-calendar-check me-1"></i>
                            Agendamentos <span class="badge bg-secondary" id="appointmentsCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="professionals-tab" data-bs-toggle="tab" data-bs-target="#professionalsResults" type="button" role="tab">
                            <i class="bi bi-person-badge me-1"></i>
                            Profissionais <span class="badge bg-secondary" id="professionalsCount">0</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="resultsTabContent">
                    <!-- Pets -->
                    <div class="tab-pane fade show active" id="petsResults" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>Chip</th>
                                        <th>Espécie</th>
                                        <th>Raça</th>
                                        <th>Tutor</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="petsTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Nenhum pet encontrado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Customers -->
                    <div class="tab-pane fade" id="customersResults" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>CPF</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="customersTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Nenhum cliente encontrado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Appointments -->
                    <div class="tab-pane fade" id="appointmentsResults" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Pet</th>
                                        <th>Tutor</th>
                                        <th>Profissional</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Nenhum agendamento encontrado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Professionals -->
                    <div class="tab-pane fade" id="professionalsResults" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>CRMV</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="professionalsTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Nenhum profissional encontrado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado vazio -->
    <div id="emptyState" class="text-center py-5">
        <i class="bi bi-search fs-1 text-muted"></i>
        <h5 class="mt-3 text-muted">Digite um termo de busca para começar</h5>
        <p class="text-muted">Busque por nome, email, telefone, chip, CRMV, etc.</p>
    </div>
</div>

<script>
const apiUrl = '<?php echo $apiUrl; ?>';

function showAlert(message, type = 'info', timeout = 5000) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = `alert-${Date.now()}`;
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" id="${alertId}">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    alertContainer.innerHTML = alertHtml;
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) alert.remove();
    }, timeout);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatPhone(phone) {
    if (!phone) return '-';
    const cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 11) {
        return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (cleaned.length === 10) {
        return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    return phone;
}

function formatCPF(cpf) {
    if (!cpf) return '-';
    const cleaned = cpf.replace(/\D/g, '');
    if (cleaned.length === 11) {
        return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    return cpf;
}

async function performSearch(event) {
    event.preventDefault();
    
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput.value.trim();
    
    if (!searchTerm) {
        showAlert('Por favor, digite um termo de busca.', 'warning');
        return;
    }

    // Coleta tipos selecionados
    const types = [];
    if (document.getElementById('typePets').checked) types.push('pets');
    if (document.getElementById('typeCustomers').checked) types.push('customers');
    if (document.getElementById('typeAppointments').checked) types.push('appointments');
    if (document.getElementById('typeProfessionals').checked) types.push('professionals');

    if (types.length === 0) {
        showAlert('Selecione pelo menos um tipo de busca.', 'warning');
        return;
    }

    const searchBtn = document.getElementById('searchBtn');
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Buscando...';

    try {
        const params = new URLSearchParams({
            q: searchTerm,
            types: types.join(','),
            limit: 50
        });

        const response = await apiRequest(`/v1/clinic/search?${params.toString()}`);
        const data = response.data;

        // Atualiza contadores
        document.getElementById('totalResultsBadge').textContent = `${data.total_results} resultados`;
        document.getElementById('petsCount').textContent = data.results.pets.length;
        document.getElementById('customersCount').textContent = data.results.customers.length;
        document.getElementById('appointmentsCount').textContent = data.results.appointments.length;
        document.getElementById('professionalsCount').textContent = data.results.professionals.length;

        // Renderiza resultados
        renderPets(data.results.pets);
        renderCustomers(data.results.customers);
        renderAppointments(data.results.appointments);
        renderProfessionals(data.results.professionals);

        // Mostra container de resultados
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('resultsContainer').style.display = 'block';

    } catch (error) {
        console.error('Erro na busca:', error);
        showAlert('Erro ao realizar busca: ' + (error.response?.message || error.message), 'danger');
    } finally {
        searchBtn.disabled = false;
        searchBtn.innerHTML = '<i class="bi bi-search me-2"></i> Buscar';
    }
}

function renderPets(pets) {
    const tbody = document.getElementById('petsTableBody');
    
    if (pets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhum pet encontrado.</td></tr>';
        return;
    }

    tbody.innerHTML = pets.map(pet => `
        <tr>
            <td><strong>${pet.name || 'N/A'}</strong></td>
            <td>${pet.chip || '-'}</td>
            <td>${pet.species || '-'}</td>
            <td>${pet.breed || '-'}</td>
            <td>${pet.customer_name || '-'}</td>
            <td>
                <a href="/clinic/pets" class="btn btn-sm btn-outline-primary" title="Ver pet">
                    <i class="bi bi-eye"></i>
                </a>
            </td>
        </tr>
    `).join('');
}

function renderCustomers(customers) {
    const tbody = document.getElementById('customersTableBody');
    
    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhum cliente encontrado.</td></tr>';
        return;
    }

    tbody.innerHTML = customers.map(customer => `
        <tr>
            <td><strong>${customer.name || 'N/A'}</strong></td>
            <td>${customer.email || '-'}</td>
            <td>${formatPhone(customer.phone)}</td>
            <td>${formatCPF(customer.cpf)}</td>
            <td>
                <a href="/customers" class="btn btn-sm btn-outline-primary" title="Ver cliente">
                    <i class="bi bi-eye"></i>
                </a>
            </td>
        </tr>
    `).join('');
}

function renderAppointments(appointments) {
    const tbody = document.getElementById('appointmentsTableBody');
    
    if (appointments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Nenhum agendamento encontrado.</td></tr>';
        return;
    }

    const statusClass = {
        'scheduled': 'info',
        'confirmed': 'primary',
        'completed': 'success',
        'cancelled': 'danger',
        'no_show': 'secondary'
    };

    const statusText = {
        'scheduled': 'Agendado',
        'confirmed': 'Confirmado',
        'completed': 'Concluído',
        'cancelled': 'Cancelado',
        'no_show': 'Não Compareceu'
    };

    tbody.innerHTML = appointments.map(app => {
        const status = app.status || 'scheduled';
        return `
            <tr>
                <td>${formatDate(app.appointment_date)}</td>
                <td>${app.pet_name || 'N/A'}</td>
                <td>${app.customer_name || 'N/A'}</td>
                <td>${app.professional_name || 'N/A'}</td>
                <td>${app.type || '-'}</td>
                <td><span class="badge bg-${statusClass[status] || 'secondary'}">${statusText[status] || status}</span></td>
                <td>
                    <a href="/clinic/appointments" class="btn btn-sm btn-outline-primary" title="Ver agendamento">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
        `;
    }).join('');
}

function renderProfessionals(professionals) {
    const tbody = document.getElementById('professionalsTableBody');
    
    if (professionals.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhum profissional encontrado.</td></tr>';
        return;
    }

    tbody.innerHTML = professionals.map(professional => `
        <tr>
            <td><strong>${professional.name || 'N/A'}</strong></td>
            <td>${professional.crmv || '-'}</td>
            <td>${professional.email || '-'}</td>
            <td>${formatPhone(professional.phone)}</td>
            <td><span class="badge bg-${professional.status === 'active' ? 'success' : 'secondary'}">${professional.status === 'active' ? 'Ativo' : 'Inativo'}</span></td>
            <td>
                <a href="/clinic/professionals" class="btn btn-sm btn-outline-primary" title="Ver profissional">
                    <i class="bi bi-eye"></i>
                </a>
            </td>
        </tr>
    `).join('');
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('emptyState').style.display = 'block';
    document.getElementById('resultsContainer').style.display = 'none';
    
    // Marca todos os tipos como selecionados
    document.getElementById('typePets').checked = true;
    document.getElementById('typeCustomers').checked = true;
    document.getElementById('typeAppointments').checked = true;
    document.getElementById('typeProfessionals').checked = true;
}

// Busca ao pressionar Enter
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('searchForm').dispatchEvent(new Event('submit'));
            }
        });
    }
});
</script>

