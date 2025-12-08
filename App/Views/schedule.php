<?php
/**
 * View de Calendário/Agenda
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-calendar3 text-primary"></i>
                Agenda
            </h1>
            <p class="text-muted mb-0">Visualize e gerencie os agendamentos</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="previousMonth()">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-outline-primary" onclick="today()">
                Hoje
            </button>
            <button class="btn btn-outline-primary" onclick="nextMonth()">
                <i class="bi bi-chevron-right"></i>
            </button>
            <button class="btn btn-primary" onclick="createNewAppointment()">
                <i class="bi bi-plus-circle"></i> Novo Agendamento
            </button>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Profissional</label>
                    <select class="form-select" id="professionalFilter">
                        <option value="">Todos</option>
                    </select>
                </div>
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
                    <label class="form-label">Visualização</label>
                    <select class="form-select" id="viewMode">
                        <option value="month">Mensal</option>
                        <option value="week">Semanal</option>
                        <option value="day">Diária</option>
                        <option value="list">Lista</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadAppointments()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendário -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0" id="calendarTitle">
                <i class="bi bi-calendar3 me-2"></i>
                <span id="currentMonthYear"></span>
            </h5>
            <span class="badge bg-primary" id="appointmentsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingAppointments" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando agendamentos...</p>
            </div>
            <div id="calendarContainer" style="display: none;">
                <!-- Conteúdo será renderizado aqui -->
            </div>
        </div>
    </div>
</div>

<script>
let appointments = [];
let professionals = [];
let pets = [];
let customers = [];
let currentDate = new Date();
let currentView = 'month';

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatTime(date) {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit'
    });
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

function getMonthName(month) {
    const months = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    return months[month];
}

function getDayName(day) {
    const days = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    return days[day];
}

document.addEventListener('DOMContentLoaded', () => {
    loadProfessionals();
    loadPets();
    loadCustomers();
    loadAppointments();
    
    const viewMode = document.getElementById('viewMode');
    if (viewMode) {
        viewMode.addEventListener('change', function() {
            currentView = this.value;
            loadAppointments();
        });
    }
    
    updateCalendarTitle();
});

async function loadProfessionals() {
    try {
        const response = await apiRequest('/v1/clinic/professionals/active', {
            cacheTTL: 60000
        });
        professionals = response.data || [];
        
        const select = document.getElementById('professionalFilter');
        if (select) {
            select.innerHTML = '<option value="">Todos</option>';
            professionals.forEach(professional => {
                const option = document.createElement('option');
                option.value = professional.id;
                option.textContent = professional.name || 'Sem nome';
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Erro ao carregar profissionais:', error);
    }
}

async function loadPets() {
    try {
        const response = await apiRequest('/v1/clinic/pets?limit=1000', {
            cacheTTL: 60000
        });
        pets = response.data || [];
    } catch (error) {
        console.error('Erro ao carregar pets:', error);
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

async function loadAppointments() {
    try {
        document.getElementById('loadingAppointments').style.display = 'block';
        document.getElementById('calendarContainer').style.display = 'none';
        
        const params = new URLSearchParams();
        
        // Calcula período baseado na visualização
        let startDate, endDate;
        if (currentView === 'month') {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            startDate = new Date(year, month, 1);
            endDate = new Date(year, month + 1, 0);
        } else if (currentView === 'week') {
            const day = currentDate.getDay();
            startDate = new Date(currentDate);
            startDate.setDate(currentDate.getDate() - day);
            endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + 6);
        } else if (currentView === 'day') {
            startDate = new Date(currentDate);
            endDate = new Date(currentDate);
        } else {
            // Lista - últimos 30 dias
            startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            endDate = new Date();
            endDate.setDate(endDate.getDate() + 30);
        }
        
        params.append('start_date', startDate.toISOString().split('T')[0]);
        params.append('end_date', endDate.toISOString().split('T')[0]);
        
        const professionalFilter = document.getElementById('professionalFilter')?.value;
        if (professionalFilter) {
            params.append('professional_id', professionalFilter);
        }
        
        const statusFilter = document.getElementById('statusFilter')?.value;
        if (statusFilter) {
            params.append('status', statusFilter);
        }
        
        const response = await apiRequest('/v1/clinic/appointments?' + params.toString(), {
            cacheTTL: 10000
        });
        
        appointments = response.data || [];
        
        renderCalendar();
        updateCalendarTitle();
    } catch (error) {
        showAlert('Erro ao carregar agendamentos: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingAppointments').style.display = 'none';
        document.getElementById('calendarContainer').style.display = 'block';
    }
}

function updateCalendarTitle() {
    const titleEl = document.getElementById('currentMonthYear');
    const countBadge = document.getElementById('appointmentsCountBadge');
    
    if (currentView === 'month') {
        titleEl.textContent = `${getMonthName(currentDate.getMonth())} ${currentDate.getFullYear()}`;
    } else if (currentView === 'week') {
        const weekStart = new Date(currentDate);
        weekStart.setDate(currentDate.getDate() - currentDate.getDay());
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekStart.getDate() + 6);
        titleEl.textContent = `${formatDate(weekStart)} - ${formatDate(weekEnd)}`;
    } else if (currentView === 'day') {
        titleEl.textContent = formatDate(currentDate);
    } else {
        titleEl.textContent = 'Lista de Agendamentos';
    }
    
    if (countBadge) {
        countBadge.textContent = appointments.length;
    }
}

function renderCalendar() {
    const container = document.getElementById('calendarContainer');
    if (!container) return;
    
    if (currentView === 'month') {
        renderMonthView(container);
    } else if (currentView === 'week') {
        renderWeekView(container);
    } else if (currentView === 'day') {
        renderDayView(container);
    } else {
        renderListView(container);
    }
}

function renderMonthView(container) {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    let html = '<div class="calendar-month">';
    html += '<div class="row g-2 mb-2">';
    
    // Cabeçalho dos dias da semana
    const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    weekDays.forEach(day => {
        html += `<div class="col text-center fw-bold text-muted small">${day}</div>`;
    });
    html += '</div>';
    
    html += '<div class="row g-2">';
    
    // Dias vazios no início
    for (let i = 0; i < startingDayOfWeek; i++) {
        html += '<div class="col calendar-day-empty"></div>';
    }
    
    // Dias do mês
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        const dateStr = date.toISOString().split('T')[0];
        const dayAppointments = appointments.filter(apt => {
            const aptDate = new Date(apt.appointment_date);
            return aptDate.toISOString().split('T')[0] === dateStr;
        });
        
        const isToday = date.toDateString() === new Date().toDateString();
        const dayClass = isToday ? 'calendar-day-today' : 'calendar-day';
        
        html += `<div class="col ${dayClass}" onclick="selectDate('${dateStr}')">`;
        html += `<div class="calendar-day-number">${day}</div>`;
        if (dayAppointments.length > 0) {
            html += `<div class="calendar-day-appointments">`;
            dayAppointments.slice(0, 3).forEach(apt => {
                const pet = pets.find(p => p.id === apt.pet_id);
                const petName = pet ? pet.name : 'N/A';
                const statusMap = {
                    'scheduled': 'bg-info',
                    'confirmed': 'bg-success',
                    'completed': 'bg-primary',
                    'cancelled': 'bg-danger'
                };
                const statusClass = statusMap[apt.status] || 'bg-secondary';
                html += `<div class="badge ${statusClass} mb-1" style="font-size: 0.7rem; display: block; text-align: left; cursor: pointer;" onclick="event.stopPropagation(); editAppointment(${apt.id})" title="${formatTime(apt.appointment_date)} - ${escapeHtml(petName)}">${formatTime(apt.appointment_date)} ${escapeHtml(petName)}</div>`;
            });
            if (dayAppointments.length > 3) {
                html += `<small class="text-muted">+${dayAppointments.length - 3} mais</small>`;
            }
            html += `</div>`;
        }
        html += `</div>`;
        
        // Quebra de linha no final da semana
        if ((startingDayOfWeek + day) % 7 === 0) {
            html += '</div><div class="row g-2">';
        }
    }
    
    // Dias vazios no final
    const remainingDays = 7 - ((startingDayOfWeek + daysInMonth) % 7);
    if (remainingDays < 7) {
        for (let i = 0; i < remainingDays; i++) {
            html += '<div class="col calendar-day-empty"></div>';
        }
    }
    
    html += '</div></div>';
    
    container.innerHTML = html;
}

function renderWeekView(container) {
    const day = currentDate.getDay();
    const weekStart = new Date(currentDate);
    weekStart.setDate(currentDate.getDate() - day);
    
    let html = '<div class="calendar-week">';
    html += '<div class="row g-2">';
    
    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(weekStart.getDate() + i);
        const dateStr = date.toISOString().split('T')[0];
        const dayAppointments = appointments.filter(apt => {
            const aptDate = new Date(apt.appointment_date);
            return aptDate.toISOString().split('T')[0] === dateStr;
        });
        
        const isToday = date.toDateString() === new Date().toDateString();
        const dayClass = isToday ? 'calendar-day-today' : 'calendar-day';
        
        html += `<div class="col ${dayClass}">`;
        html += `<div class="fw-bold mb-2">${getDayName(date.getDay())} ${date.getDate()}</div>`;
        html += '<div class="calendar-day-appointments">';
        dayAppointments.forEach(apt => {
            const pet = pets.find(p => p.id === apt.pet_id);
            const professional = professionals.find(p => p.id === apt.professional_id);
            const petName = pet ? pet.name : 'N/A';
            const profName = professional ? professional.name : '-';
            const statusMap = {
                'scheduled': 'bg-info',
                'confirmed': 'bg-success',
                'completed': 'bg-primary',
                'cancelled': 'bg-danger'
            };
            const statusClass = statusMap[apt.status] || 'bg-secondary';
            html += `<div class="card mb-2" onclick="editAppointment(${apt.id})" style="cursor: pointer;">`;
            html += `<div class="card-body p-2">`;
            html += `<div class="badge ${statusClass} mb-1">${formatTime(apt.appointment_date)}</div>`;
            html += `<div class="small fw-bold">${escapeHtml(petName)}</div>`;
            html += `<div class="small text-muted">${escapeHtml(profName)}</div>`;
            html += `</div></div>`;
        });
        html += '</div>';
        html += `</div>`;
    }
    
    html += '</div></div>';
    container.innerHTML = html;
}

function renderDayView(container) {
    const dateStr = currentDate.toISOString().split('T')[0];
    const dayAppointments = appointments.filter(apt => {
        const aptDate = new Date(apt.appointment_date);
        return aptDate.toISOString().split('T')[0] === dateStr;
    }).sort((a, b) => {
        return new Date(a.appointment_date) - new Date(b.appointment_date);
    });
    
    let html = '<div class="calendar-day-view">';
    html += `<h5 class="mb-3">${formatDate(currentDate)}</h5>`;
    
    if (dayAppointments.length === 0) {
        html += '<div class="text-center py-5 text-muted">';
        html += '<i class="bi bi-calendar-x fs-1"></i>';
        html += '<p class="mt-3">Nenhum agendamento para este dia</p>';
        html += '</div>';
    } else {
        dayAppointments.forEach(apt => {
            const pet = pets.find(p => p.id === apt.pet_id);
            const customer = customers.find(c => c.id === apt.customer_id);
            const professional = professionals.find(p => p.id === apt.professional_id);
            const petName = pet ? pet.name : 'N/A';
            const customerName = customer ? (customer.name || customer.email) : 'N/A';
            const profName = professional ? professional.name : '-';
            const statusMap = {
                'scheduled': 'bg-info',
                'confirmed': 'bg-success',
                'completed': 'bg-primary',
                'cancelled': 'bg-danger'
            };
            const statusClass = statusMap[apt.status] || 'bg-secondary';
            
            html += '<div class="card mb-3" onclick="editAppointment(' + apt.id + ')" style="cursor: pointer;">';
            html += '<div class="card-body">';
            html += '<div class="d-flex justify-content-between align-items-start mb-2">';
            html += `<div><span class="badge ${statusClass} me-2">${formatTime(apt.appointment_date)}</span><span class="fw-bold">${escapeHtml(petName)}</span></div>`;
            html += `<span class="badge bg-secondary">${apt.duration_minutes || 30} min</span>`;
            html += '</div>';
            html += `<div class="small text-muted mb-1"><i class="bi bi-person"></i> Tutor: ${escapeHtml(customerName)}</div>`;
            html += `<div class="small text-muted mb-1"><i class="bi bi-person-badge"></i> Profissional: ${escapeHtml(profName)}</div>`;
            if (apt.type) {
                html += `<div class="small text-muted mb-1"><i class="bi bi-tag"></i> Tipo: ${escapeHtml(apt.type)}</div>`;
            }
            if (apt.notes) {
                html += `<div class="small mt-2">${escapeHtml(apt.notes)}</div>`;
            }
            html += '</div></div>';
        });
    }
    
    html += '</div>';
    container.innerHTML = html;
}

function renderListView(container) {
    const sortedAppointments = [...appointments].sort((a, b) => {
        return new Date(a.appointment_date) - new Date(b.appointment_date);
    });
    
    let html = '<div class="table-responsive">';
    html += '<table class="table table-hover">';
    html += '<thead class="table-light">';
    html += '<tr>';
    html += '<th>Data/Hora</th>';
    html += '<th>Pet</th>';
    html += '<th>Tutor</th>';
    html += '<th>Profissional</th>';
    html += '<th>Tipo</th>';
    html += '<th>Duração</th>';
    html += '<th>Status</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';
    
    if (sortedAppointments.length === 0) {
        html += '<tr><td colspan="7" class="text-center py-5 text-muted">Nenhum agendamento encontrado</td></tr>';
    } else {
        sortedAppointments.forEach(apt => {
            const pet = pets.find(p => p.id === apt.pet_id);
            const customer = customers.find(c => c.id === apt.customer_id);
            const professional = professionals.find(p => p.id === apt.professional_id);
            const petName = pet ? pet.name : 'N/A';
            const customerName = customer ? (customer.name || customer.email) : 'N/A';
            const profName = professional ? professional.name : '-';
            const statusMap = {
                'scheduled': '<span class="badge bg-info">Agendado</span>',
                'confirmed': '<span class="badge bg-success">Confirmado</span>',
                'completed': '<span class="badge bg-primary">Concluído</span>',
                'cancelled': '<span class="badge bg-danger">Cancelado</span>'
            };
            const statusBadge = statusMap[apt.status] || '<span class="badge bg-secondary">' + apt.status + '</span>';
            
            html += '<tr onclick="editAppointment(' + apt.id + ')" style="cursor: pointer;">';
            html += '<td>' + formatDateTime(apt.appointment_date) + '</td>';
            html += '<td>' + escapeHtml(petName) + '</td>';
            html += '<td><small>' + escapeHtml(customerName) + '</small></td>';
            html += '<td>' + escapeHtml(profName) + '</td>';
            html += '<td>' + escapeHtml(apt.type || '-') + '</td>';
            html += '<td>' + (apt.duration_minutes || 30) + ' min</td>';
            html += '<td>' + statusBadge + '</td>';
            html += '</tr>';
        });
    }
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function previousMonth() {
    if (currentView === 'month') {
        currentDate.setMonth(currentDate.getMonth() - 1);
    } else if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() - 7);
    } else if (currentView === 'day') {
        currentDate.setDate(currentDate.getDate() - 1);
    }
    loadAppointments();
}

function nextMonth() {
    if (currentView === 'month') {
        currentDate.setMonth(currentDate.getMonth() + 1);
    } else if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() + 7);
    } else if (currentView === 'day') {
        currentDate.setDate(currentDate.getDate() + 1);
    }
    loadAppointments();
}

function today() {
    currentDate = new Date();
    loadAppointments();
}

// Variável global para armazenar a data selecionada
let selectedDate = null;

function selectDate(dateStr) {
    selectedDate = dateStr; // Armazena a data selecionada
    viewDay(dateStr);
}

function viewDay(dateStr) {
    currentDate = new Date(dateStr);
    currentView = 'day';
    document.getElementById('viewMode').value = 'day';
    loadAppointments();
}

function createNewAppointment() {
    // Se há uma data selecionada, passa como parâmetro
    if (selectedDate) {
        window.location.href = `/clinic/appointments?date=${selectedDate}`;
    } else {
        // Se não há data selecionada, usa a data atual
        const today = new Date().toISOString().split('T')[0];
        window.location.href = `/clinic/appointments?date=${today}`;
    }
}

function editAppointment(id) {
    window.location.href = `/clinic/appointments?edit=${id}`;
}

// Estilos CSS inline para o calendário
const style = document.createElement('style');
style.textContent = `
    .calendar-day, .calendar-day-today, .calendar-day-empty {
        min-height: 120px;
        border: 1px solid #dee2e6;
        padding: 8px;
        border-radius: 4px;
        background: #fff;
    }
    .calendar-day-today {
        background: #e7f3ff;
        border: 2px solid #0d6efd;
    }
    .calendar-day-empty {
        background: #f8f9fa;
        border: none;
    }
    .calendar-day-number {
        font-weight: bold;
        margin-bottom: 4px;
    }
    .calendar-day-appointments {
        font-size: 0.75rem;
    }
    .calendar-day:hover {
        background: #f8f9fa;
        cursor: pointer;
    }
`;
document.head.appendChild(style);
</script>

