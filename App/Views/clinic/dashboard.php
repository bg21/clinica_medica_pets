<?php
/**
 * View de Dashboard da Clínica
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
                <i class="bi bi-speedometer2 text-primary"></i>
                Dashboard da Clínica
            </h1>
            <p class="text-muted mb-0">Visão geral das atividades e estatísticas da sua clínica</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="timeRangeFilter" style="width: auto;">
                <option value="7">Últimos 7 dias</option>
                <option value="30" selected>Últimos 30 dias</option>
                <option value="90">Últimos 90 dias</option>
            </select>
            <button class="btn btn-outline-primary" onclick="loadDashboardData()">
                <i class="bi bi-arrow-clockwise"></i> Atualizar
            </button>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- KPIs Cards -->
    <div class="row mb-4" id="kpisContainer">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Consultas Hoje</h6>
                            <h2 class="mb-0" id="kpiConsultasHoje">-</h2>
                        </div>
                        <i class="bi bi-calendar-check fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Agendamentos Pendentes</h6>
                            <h2 class="mb-0" id="kpiAgendamentosPendentes">-</h2>
                        </div>
                        <i class="bi bi-clock-history fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Pets Cadastrados</h6>
                            <h2 class="mb-0" id="kpiPetsCadastrados">-</h2>
                        </div>
                        <i class="bi bi-heart-pulse fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Clientes Cadastrados</h6>
                            <h2 class="mb-0" id="kpiClientesCadastrados">-</h2>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Profissionais Ativos</h6>
                            <h2 class="mb-0" id="kpiProfissionaisAtivos">-</h2>
                        </div>
                        <i class="bi bi-person-badge fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Concluídas Hoje</h6>
                            <h2 class="mb-0" id="kpiConcluidasHoje">-</h2>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart-line me-2"></i>
                        Consultas por Período
                    </h5>
                </div>
                <div class="card-body">
                    <div id="loadingChart" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Carregando gráfico...</p>
                    </div>
                    <canvas id="appointmentsChart" style="display: none;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Próximos Agendamentos -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-event me-2"></i>
                        Próximos Agendamentos
                    </h5>
                    <a href="/clinic/appointments" class="btn btn-sm btn-outline-primary">
                        Ver Todos <i class="bi bi-arrow-right"></i>
                    </a>
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
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center">Nenhum agendamento encontrado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
const apiUrl = '<?php echo $apiUrl; ?>';
let appointmentsChart = null;

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardData();
    
    const timeRangeFilter = document.getElementById('timeRangeFilter');
    if (timeRangeFilter) {
        timeRangeFilter.addEventListener('change', () => {
            loadDashboardData();
        });
    }
});

async function loadDashboardData() {
    await Promise.all([
        loadKPIs(),
        loadAppointmentsStats(),
        loadUpcomingAppointments()
    ]);
}

async function loadKPIs() {
    try {
        const response = await apiRequest('/v1/clinic/dashboard/kpis');
        const kpis = response.data;

        document.getElementById('kpiConsultasHoje').textContent = kpis.consultas_hoje || 0;
        document.getElementById('kpiAgendamentosPendentes').textContent = kpis.agendamentos_pendentes || 0;
        document.getElementById('kpiPetsCadastrados').textContent = kpis.pets_cadastrados || 0;
        document.getElementById('kpiClientesCadastrados').textContent = kpis.clientes_cadastrados || 0;
        document.getElementById('kpiProfissionaisAtivos').textContent = kpis.profissionais_ativos || 0;
        document.getElementById('kpiConcluidasHoje').textContent = kpis.consultas_concluidas_hoje || 0;
    } catch (error) {
        console.error('Erro ao carregar KPIs:', error);
        showAlert('Erro ao carregar KPIs: ' + (error.response?.message || error.message), 'danger');
    }
}

async function loadAppointmentsStats() {
    const loadingChart = document.getElementById('loadingChart');
    const chartCanvas = document.getElementById('appointmentsChart');
    const days = document.getElementById('timeRangeFilter').value;

    loadingChart.style.display = 'block';
    chartCanvas.style.display = 'none';

    try {
        const response = await apiRequest(`/v1/clinic/dashboard/appointments-stats?days=${days}`);
        const stats = response.data;

        // Destroi gráfico anterior se existir
        if (appointmentsChart) {
            appointmentsChart.destroy();
        }

        // Cria novo gráfico
        const ctx = chartCanvas.getContext('2d');
        appointmentsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: stats.by_date.dates.map(date => {
                    const d = new Date(date);
                    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                }),
                datasets: [{
                    label: 'Consultas',
                    data: stats.by_date.counts,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: `Consultas nos últimos ${stats.period.days} dias`
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        loadingChart.style.display = 'none';
        chartCanvas.style.display = 'block';
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
        showAlert('Erro ao carregar estatísticas: ' + (error.response?.message || error.message), 'danger');
        loadingChart.style.display = 'none';
    }
}

async function loadUpcomingAppointments() {
    const loadingAppointments = document.getElementById('loadingAppointments');
    const appointmentsList = document.getElementById('appointmentsList');
    const tableBody = document.getElementById('appointmentsTableBody');

    loadingAppointments.style.display = 'block';
    appointmentsList.style.display = 'none';

    try {
        const response = await apiRequest('/v1/clinic/dashboard/upcoming-appointments?limit=10');
        const data = response.data;

        if (data.appointments && data.appointments.length > 0) {
            tableBody.innerHTML = data.appointments.map(app => {
                const date = new Date(app.appointment_date);
                const formattedDate = date.toLocaleString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const statusClass = {
                    'scheduled': 'info',
                    'confirmed': 'primary',
                    'completed': 'success',
                    'cancelled': 'danger',
                    'no_show': 'secondary'
                }[app.status] || 'secondary';

                const statusText = {
                    'scheduled': 'Agendado',
                    'confirmed': 'Confirmado',
                    'completed': 'Concluído',
                    'cancelled': 'Cancelado',
                    'no_show': 'Não Compareceu'
                }[app.status] || app.status;

                return `
                    <tr>
                        <td>${formattedDate}</td>
                        <td>${app.pet_name || 'N/A'}</td>
                        <td>${app.customer_name || 'N/A'}</td>
                        <td>${app.professional_name || 'N/A'}</td>
                        <td>${app.type || 'N/A'}</td>
                        <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                    </tr>
                `;
            }).join('');
        } else {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Nenhum agendamento encontrado.</td></tr>';
        }

        loadingAppointments.style.display = 'none';
        appointmentsList.style.display = 'block';
    } catch (error) {
        console.error('Erro ao carregar próximos agendamentos:', error);
        showAlert('Erro ao carregar próximos agendamentos: ' + (error.response?.message || error.message), 'danger');
        loadingAppointments.style.display = 'none';
        appointmentsList.style.display = 'block';
    }
}

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
</script>

