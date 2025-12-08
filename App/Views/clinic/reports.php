<?php
/**
 * View de Relatórios da Clínica
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-graph-up text-primary"></i>
                Relatórios da Clínica
            </h1>
            <p class="text-muted mb-0">Visualize relatórios e estatísticas da clínica</p>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Filtros de Período -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="startDateFilter" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="endDateFilter" value="<?php echo date('Y-m-t'); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="loadCurrentReport()">
                        <i class="bi bi-funnel"></i> Aplicar Filtros
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="exportReport()">
                        <i class="bi bi-download"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de Relatórios -->
    <ul class="nav nav-tabs mb-4" id="reportsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab">
                <i class="bi bi-calendar-check"></i> Consultas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="exams-tab" data-bs-toggle="tab" data-bs-target="#exams" type="button" role="tab">
                <i class="bi bi-clipboard-check"></i> Exames
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="vaccinations-tab" data-bs-toggle="tab" data-bs-target="#vaccinations" type="button" role="tab">
                <i class="bi bi-shield-check"></i> Vacinações Pendentes
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab">
                <i class="bi bi-currency-dollar"></i> Financeiro
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="top-pets-tab" data-bs-toggle="tab" data-bs-target="#top-pets" type="button" role="tab">
                <i class="bi bi-star"></i> Pets Mais Atendidos
            </button>
        </li>
    </ul>

    <!-- Conteúdo das Tabs -->
    <div class="tab-content" id="reportsTabContent">
        <!-- Tab: Consultas -->
        <div class="tab-pane fade show active" id="appointments" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Relatório de Consultas</h5>
                </div>
                <div class="card-body">
                    <div id="loadingAppointments" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Carregando relatório...</p>
                    </div>
                    <div id="appointmentsReport" style="display: none;">
                        <div id="appointmentsStats" class="row mb-4"></div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Pet</th>
                                        <th>Tutor</th>
                                        <th>Profissional</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Exames -->
        <div class="tab-pane fade" id="exams" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Relatório de Exames</h5>
                </div>
                <div class="card-body">
                    <div id="loadingExams" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Carregando relatório...</p>
                    </div>
                    <div id="examsReport" style="display: none;">
                        <div id="examsStats" class="row mb-4"></div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Pet</th>
                                        <th>Tutor</th>
                                        <th>Tipo de Exame</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="examsTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Vacinações -->
        <div class="tab-pane fade" id="vaccinations" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Vacinações Pendentes</h5>
                </div>
                <div class="card-body">
                    <div id="loadingVaccinations" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Carregando relatório...</p>
                    </div>
                    <div id="vaccinationsReport" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong id="vaccinationsCount">0</strong> vacinações pendentes
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Pet</th>
                                        <th>Tutor</th>
                                        <th>Profissional</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="vaccinationsTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Financeiro -->
        <div class="tab-pane fade" id="financial" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Relatório Financeiro</h5>
                </div>
                <div class="card-body">
                    <div id="loadingFinancial" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Carregando relatório...</p>
                    </div>
                    <div id="financialReport" style="display: none;">
                        <div id="financialStats" class="row mb-4"></div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Pet</th>
                                        <th>Cliente</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="financialTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Pets Mais Atendidos -->
        <div class="tab-pane fade" id="top-pets" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-star me-2"></i>Pets Mais Atendidos</h5>
                </div>
                <div class="card-body">
                    <div id="loadingTopPets" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Carregando relatório...</p>
                    </div>
                    <div id="topPetsReport" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Pet</th>
                                        <th>Espécie</th>
                                        <th>Tutor</th>
                                        <th>Consultas</th>
                                        <th>Última Consulta</th>
                                    </tr>
                                </thead>
                                <tbody id="topPetsTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const apiUrl = '<?php echo $apiUrl; ?>';
let currentReportType = 'appointments';

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

function formatCurrency(amount, currency = 'BRL') {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: currency
    }).format(amount);
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

function formatDateOnly(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

async function loadAppointmentsReport() {
    try {
        document.getElementById('loadingAppointments').style.display = 'block';
        document.getElementById('appointmentsReport').style.display = 'none';
        
        const startDate = document.getElementById('startDateFilter').value;
        const endDate = document.getElementById('endDateFilter').value;
        
        const params = new URLSearchParams();
        params.append('start_date', startDate);
        params.append('end_date', endDate);
        
        const response = await apiRequest(`/v1/clinic/reports/appointments?${params.toString()}`);
        
        const appointments = response.data?.appointments || [];
        const stats = response.data?.statistics || {};
        
        // Renderiza estatísticas
        const statsHtml = `
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Total de Consultas</h6>
                        <h3 class="mb-0">${stats.total || 0}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Confirmadas</h6>
                        <h3 class="mb-0">${stats.by_status?.confirmed || 0}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Concluídas</h6>
                        <h3 class="mb-0">${stats.by_status?.completed || 0}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Canceladas</h6>
                        <h3 class="mb-0">${stats.by_status?.cancelled || 0}</h3>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('appointmentsStats').innerHTML = statsHtml;
        
        // Renderiza tabela
        const tbody = document.getElementById('appointmentsTableBody');
        if (appointments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhuma consulta encontrada no período</td></tr>';
        } else {
            tbody.innerHTML = appointments.map(apt => `
                <tr>
                    <td>${formatDate(apt.appointment_date)}</td>
                    <td>${apt.pet_name || '-'} (${apt.species || '-'})</td>
                    <td>${apt.customer_name || '-'}</td>
                    <td>${apt.professional_name || 'Sem profissional'}</td>
                    <td>${apt.type || '-'}</td>
                    <td><span class="badge bg-${getStatusColor(apt.status)}">${getStatusLabel(apt.status)}</span></td>
                </tr>
            `).join('');
        }
        
        document.getElementById('appointmentsReport').style.display = 'block';
    } catch (error) {
        showAlert('Erro ao carregar relatório de consultas: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingAppointments').style.display = 'none';
    }
}

async function loadExamsReport() {
    try {
        document.getElementById('loadingExams').style.display = 'block';
        document.getElementById('examsReport').style.display = 'none';
        
        const startDate = document.getElementById('startDateFilter').value;
        const endDate = document.getElementById('endDateFilter').value;
        
        const params = new URLSearchParams();
        params.append('start_date', startDate);
        params.append('end_date', endDate);
        
        const response = await apiRequest(`/v1/clinic/reports/exams?${params.toString()}`);
        
        const exams = response.data?.exams || [];
        const stats = response.data?.statistics || {};
        
        // Renderiza estatísticas
        const statsHtml = `
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Total de Exames</h6>
                        <h3 class="mb-0">${stats.total || 0}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Concluídos</h6>
                        <h3 class="mb-0">${stats.by_status?.completed || 0}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Pendentes</h6>
                        <h3 class="mb-0">${stats.by_status?.pending || 0}</h3>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('examsStats').innerHTML = statsHtml;
        
        // Renderiza tabela
        const tbody = document.getElementById('examsTableBody');
        if (exams.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhum exame encontrado no período</td></tr>';
        } else {
            tbody.innerHTML = exams.map(exam => `
                <tr>
                    <td>${formatDateOnly(exam.exam_date)}</td>
                    <td>${exam.pet_name || '-'} (${exam.species || '-'})</td>
                    <td>${exam.customer_name || '-'}</td>
                    <td>${exam.exam_type_name || '-'}</td>
                    <td><span class="badge bg-${getExamStatusColor(exam.status)}">${getExamStatusLabel(exam.status)}</span></td>
                </tr>
            `).join('');
        }
        
        document.getElementById('examsReport').style.display = 'block';
    } catch (error) {
        showAlert('Erro ao carregar relatório de exames: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingExams').style.display = 'none';
    }
}

async function loadVaccinationsReport() {
    try {
        document.getElementById('loadingVaccinations').style.display = 'block';
        document.getElementById('vaccinationsReport').style.display = 'none';
        
        const response = await apiRequest('/v1/clinic/reports/vaccinations');
        
        const vaccinations = response.data?.vaccinations || [];
        const total = response.data?.total_pending || 0;
        
        document.getElementById('vaccinationsCount').textContent = total;
        
        // Renderiza tabela
        const tbody = document.getElementById('vaccinationsTableBody');
        if (vaccinations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhuma vacinação pendente</td></tr>';
        } else {
            tbody.innerHTML = vaccinations.map(vac => `
                <tr>
                    <td>${formatDate(vac.appointment_date)}</td>
                    <td>${vac.pet_name || '-'} (${vac.species || '-'})</td>
                    <td>${vac.customer_name || '-'}</td>
                    <td>${vac.professional_name || 'Sem profissional'}</td>
                    <td><span class="badge bg-${getStatusColor(vac.status)}">${getStatusLabel(vac.status)}</span></td>
                </tr>
            `).join('');
        }
        
        document.getElementById('vaccinationsReport').style.display = 'block';
    } catch (error) {
        showAlert('Erro ao carregar relatório de vacinações: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingVaccinations').style.display = 'none';
    }
}

async function loadFinancialReport() {
    try {
        document.getElementById('loadingFinancial').style.display = 'block';
        document.getElementById('financialReport').style.display = 'none';
        
        const startDate = document.getElementById('startDateFilter').value;
        const endDate = document.getElementById('endDateFilter').value;
        
        const params = new URLSearchParams();
        params.append('start_date', startDate);
        params.append('end_date', endDate);
        
        const response = await apiRequest(`/v1/clinic/reports/financial?${params.toString()}`);
        
        const financial = response.data?.financial || {};
        const invoices = financial.invoices || [];
        
        // Renderiza estatísticas
        const statsHtml = `
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Receita Total</h6>
                        <h3 class="mb-0">${formatCurrency(financial.total_revenue || 0)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Receita Paga</h6>
                        <h3 class="mb-0">${formatCurrency(financial.paid_revenue || 0)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Receita Pendente</h6>
                        <h3 class="mb-0">${formatCurrency(financial.pending_revenue || 0)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Invoices Pagos</h6>
                        <h3 class="mb-0">${financial.by_status?.paid || 0}</h3>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('financialStats').innerHTML = statsHtml;
        
        // Renderiza tabela
        const tbody = document.getElementById('financialTableBody');
        if (invoices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhuma fatura encontrada no período</td></tr>';
        } else {
            tbody.innerHTML = invoices.map(inv => `
                <tr>
                    <td>${formatDateOnly(inv.appointment_date)}</td>
                    <td>${inv.pet_name || '-'}</td>
                    <td>${inv.customer_name || '-'}</td>
                    <td>${inv.type || '-'}</td>
                    <td>${formatCurrency(inv.amount, inv.currency)}</td>
                    <td><span class="badge bg-${getInvoiceStatusColor(inv.status)}">${getInvoiceStatusLabel(inv.status)}</span></td>
                </tr>
            `).join('');
        }
        
        document.getElementById('financialReport').style.display = 'block';
    } catch (error) {
        showAlert('Erro ao carregar relatório financeiro: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingFinancial').style.display = 'none';
    }
}

async function loadTopPetsReport() {
    try {
        document.getElementById('loadingTopPets').style.display = 'block';
        document.getElementById('topPetsReport').style.display = 'none';
        
        const startDate = document.getElementById('startDateFilter').value;
        const endDate = document.getElementById('endDateFilter').value;
        
        const params = new URLSearchParams();
        params.append('start_date', startDate);
        params.append('end_date', endDate);
        params.append('limit', '10');
        
        const response = await apiRequest(`/v1/clinic/reports/top-pets?${params.toString()}`);
        
        const topPets = response.data?.top_pets || [];
        
        // Renderiza tabela
        const tbody = document.getElementById('topPetsTableBody');
        if (topPets.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhum pet encontrado no período</td></tr>';
        } else {
            tbody.innerHTML = topPets.map((pet, index) => `
                <tr>
                    <td><strong>${index + 1}</strong></td>
                    <td>${pet.pet_name || '-'}</td>
                    <td>${pet.species || '-'}</td>
                    <td>${pet.customer_name || '-'}</td>
                    <td><span class="badge bg-primary">${pet.appointment_count || 0}</span></td>
                    <td>${formatDateOnly(pet.last_appointment)}</td>
                </tr>
            `).join('');
        }
        
        document.getElementById('topPetsReport').style.display = 'block';
    } catch (error) {
        showAlert('Erro ao carregar relatório de pets mais atendidos: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingTopPets').style.display = 'none';
    }
}

function loadCurrentReport() {
    const activeTab = document.querySelector('#reportsTabs .nav-link.active');
    if (activeTab) {
        const targetId = activeTab.getAttribute('data-bs-target');
        if (targetId === '#appointments') {
            loadAppointmentsReport();
        } else if (targetId === '#exams') {
            loadExamsReport();
        } else if (targetId === '#vaccinations') {
            loadVaccinationsReport();
        } else if (targetId === '#financial') {
            loadFinancialReport();
        } else if (targetId === '#top-pets') {
            loadTopPetsReport();
        }
    }
}

function exportReport() {
    showAlert('Funcionalidade de exportação será implementada em breve', 'info');
}

// Funções auxiliares
function getStatusColor(status) {
    const colors = {
        'scheduled': 'secondary',
        'confirmed': 'success',
        'completed': 'info',
        'cancelled': 'danger',
        'no_show': 'warning'
    };
    return colors[status] || 'secondary';
}

function getStatusLabel(status) {
    const labels = {
        'scheduled': 'Agendado',
        'confirmed': 'Confirmado',
        'completed': 'Concluído',
        'cancelled': 'Cancelado',
        'no_show': 'Não compareceu'
    };
    return labels[status] || status;
}

function getExamStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'scheduled': 'info',
        'completed': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}

function getExamStatusLabel(status) {
    const labels = {
        'pending': 'Pendente',
        'scheduled': 'Agendado',
        'completed': 'Concluído',
        'cancelled': 'Cancelado'
    };
    return labels[status] || status;
}

function getInvoiceStatusColor(status) {
    const colors = {
        'paid': 'success',
        'open': 'warning',
        'draft': 'secondary',
        'void': 'danger'
    };
    return colors[status] || 'secondary';
}

function getInvoiceStatusLabel(status) {
    const labels = {
        'paid': 'Pago',
        'open': 'Aberto',
        'draft': 'Rascunho',
        'void': 'Cancelado'
    };
    return labels[status] || status;
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Carrega relatório inicial
    loadAppointmentsReport();
    
    // Carrega relatório quando troca de tab
    document.querySelectorAll('#reportsTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', (e) => {
            const targetId = e.target.getAttribute('data-bs-target');
            if (targetId === '#appointments') {
                loadAppointmentsReport();
            } else if (targetId === '#exams') {
                loadExamsReport();
            } else if (targetId === '#vaccinations') {
                loadVaccinationsReport();
            } else if (targetId === '#financial') {
                loadFinancialReport();
            } else if (targetId === '#top-pets') {
                loadTopPetsReport();
            }
        });
    });
});
</script>

