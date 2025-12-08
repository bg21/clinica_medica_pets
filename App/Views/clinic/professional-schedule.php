<?php
/**
 * View de Agenda de Profissionais
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-calendar-week text-primary"></i>
                Agenda de Profissionais
            </h1>
            <p class="text-muted mb-0">Configure os horários de trabalho dos profissionais</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select" id="professionalSelect" style="width: auto;">
                <option value="">Selecione um profissional...</option>
            </select>
            <button class="btn btn-primary" onclick="saveSchedule()" id="saveScheduleBtn" disabled>
                <i class="bi bi-save"></i> Salvar Agenda
            </button>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Agenda Semanal -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-calendar3 me-2"></i>
                Horários de Trabalho
            </h5>
        </div>
        <div class="card-body">
            <div id="loadingSchedule" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando agenda...</p>
            </div>
            <div id="scheduleContainer" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 150px;">Dia da Semana</th>
                                <th>Disponível</th>
                                <th>Horário de Início</th>
                                <th>Horário de Fim</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody">
                            <!-- Será preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="emptyScheduleState" class="text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                <h5 class="mt-3 text-muted">Selecione um profissional</h5>
                <p class="text-muted">Escolha um profissional acima para configurar sua agenda.</p>
            </div>
        </div>
    </div>

    <!-- Bloqueios de Agenda -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-calendar-x me-2"></i>
                Bloqueios de Agenda
            </h5>
            <button class="btn btn-sm btn-outline-primary" onclick="showCreateBlockModal()">
                <i class="bi bi-plus-circle"></i> Novo Bloqueio
            </button>
        </div>
        <div class="card-body">
            <div id="blocksContainer">
                <div id="emptyBlocksState" class="text-center py-3">
                    <p class="text-muted mb-0">Nenhum bloqueio cadastrado</p>
                </div>
                <div id="blocksList" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Data/Hora Início</th>
                                    <th>Data/Hora Fim</th>
                                    <th>Motivo</th>
                                    <th style="width: 100px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="blocksTableBody">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar/Editar Bloqueio -->
<div class="modal fade" id="blockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="blockModalTitle">Novo Bloqueio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="blockForm">
                    <input type="hidden" id="blockId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Data e Hora de Início <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="blockStartDatetime" name="start_datetime" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data e Hora de Fim <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="blockEndDatetime" name="end_datetime" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <input type="text" class="form-control" id="blockReason" name="reason" placeholder="Ex: Férias, Almoço, etc.">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveBlock()" id="saveBlockBtn">
                    <i class="bi bi-check-circle me-1"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let professionals = [];
let currentProfessionalId = null;
let currentSchedule = [];
let currentBlocks = [];
const daysOfWeek = [
    { value: 0, name: 'Domingo' },
    { value: 1, name: 'Segunda-feira' },
    { value: 2, name: 'Terça-feira' },
    { value: 3, name: 'Quarta-feira' },
    { value: 4, name: 'Quinta-feira' },
    { value: 5, name: 'Sexta-feira' },
    { value: 6, name: 'Sábado' }
];

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;
    
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    alertContainer.insertAdjacentHTML('beforeend', alertHtml);
    
    // Remove após 5 segundos
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) alert.remove();
    }, 5000);
}

async function loadProfessionals() {
    try {
        const response = await apiRequest('/v1/clinic/professionals?limit=1000', {
            cacheTTL: 60000
        });
        
        professionals = response.data || [];
        populateProfessionalSelect();
    } catch (error) {
        console.error('Erro ao carregar profissionais:', error);
        showAlert('Erro ao carregar profissionais: ' + error.message, 'danger');
    }
}

function populateProfessionalSelect() {
    const select = document.getElementById('professionalSelect');
    if (!select) return;
    
    // Limpa opções existentes (exceto a primeira)
    while (select.options.length > 1) {
        select.remove(1);
    }
    
    professionals.forEach(prof => {
        const option = document.createElement('option');
        option.value = prof.id;
        option.textContent = prof.name || `Profissional #${prof.id}`;
        select.appendChild(option);
    });
}

async function loadSchedule(professionalId) {
    if (!professionalId) {
        document.getElementById('emptyScheduleState').style.display = 'block';
        document.getElementById('scheduleContainer').style.display = 'none';
        document.getElementById('loadingSchedule').style.display = 'none';
        return;
    }
    
    try {
        document.getElementById('loadingSchedule').style.display = 'block';
        document.getElementById('scheduleContainer').style.display = 'none';
        document.getElementById('emptyScheduleState').style.display = 'none';
        
        const response = await apiRequest(`/v1/clinic/professionals/${professionalId}/schedule`, {
            cacheTTL: 10000
        });
        
        currentSchedule = response.data?.schedule || [];
        currentBlocks = response.data?.blocks || [];
        
        renderSchedule();
        renderBlocks();
        
        document.getElementById('loadingSchedule').style.display = 'none';
        document.getElementById('scheduleContainer').style.display = 'block';
        document.getElementById('saveScheduleBtn').disabled = false;
    } catch (error) {
        console.error('Erro ao carregar agenda:', error);
        showAlert('Erro ao carregar agenda: ' + error.message, 'danger');
        document.getElementById('loadingSchedule').style.display = 'none';
    }
}

function renderSchedule() {
    const tbody = document.getElementById('scheduleTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    daysOfWeek.forEach(day => {
        // Busca horário existente para este dia
        const daySchedule = currentSchedule.find(s => s.day_of_week == day.value);
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${day.name}</strong></td>
            <td>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" 
                           id="day_${day.value}_available" 
                           ${daySchedule && daySchedule.is_available ? 'checked' : ''}
                           onchange="updateDaySchedule(${day.value})">
                </div>
            </td>
            <td>
                <input type="time" class="form-control" 
                       id="day_${day.value}_start" 
                       value="${daySchedule ? daySchedule.start_time.substring(0, 5) : '08:00'}"
                       onchange="updateDaySchedule(${day.value})"
                       ${daySchedule && daySchedule.is_available ? '' : 'disabled'}>
            </td>
            <td>
                <input type="time" class="form-control" 
                       id="day_${day.value}_end" 
                       value="${daySchedule ? daySchedule.end_time.substring(0, 5) : '18:00'}"
                       onchange="updateDaySchedule(${day.value})"
                       ${daySchedule && daySchedule.is_available ? '' : 'disabled'}>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function updateDaySchedule(dayOfWeek) {
    const availableCheckbox = document.getElementById(`day_${dayOfWeek}_available`);
    const startInput = document.getElementById(`day_${dayOfWeek}_start`);
    const endInput = document.getElementById(`day_${dayOfWeek}_end`);
    
    if (availableCheckbox.checked) {
        startInput.disabled = false;
        endInput.disabled = false;
    } else {
        startInput.disabled = true;
        endInput.disabled = true;
    }
}

async function saveSchedule() {
    if (!currentProfessionalId) {
        showAlert('Selecione um profissional primeiro', 'warning');
        return;
    }
    
    const submitBtn = document.getElementById('saveScheduleBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
    }
    
    try {
        // Coleta horários de todos os dias
        const schedule = [];
        daysOfWeek.forEach(day => {
            const availableCheckbox = document.getElementById(`day_${day.value}_available`);
            if (availableCheckbox && availableCheckbox.checked) {
                const startInput = document.getElementById(`day_${day.value}_start`);
                const endInput = document.getElementById(`day_${day.value}_end`);
                
                if (startInput && endInput) {
                    schedule.push({
                        day_of_week: day.value,
                        start_time: startInput.value + ':00',
                        end_time: endInput.value + ':00',
                        is_available: true
                    });
                }
            }
        });
        
        await apiRequest(`/v1/clinic/professionals/${currentProfessionalId}/schedule`, {
            method: 'POST',
            body: JSON.stringify({ schedule })
        });
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear(`/v1/clinic/professionals/${currentProfessionalId}/schedule`);
        }
        
        showAlert('Agenda salva com sucesso!', 'success');
        await loadSchedule(currentProfessionalId);
    } catch (error) {
        showAlert('Erro ao salvar agenda: ' + error.message, 'danger');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-save"></i> Salvar Agenda';
        }
    }
}

function renderBlocks() {
    const emptyState = document.getElementById('emptyBlocksState');
    const blocksList = document.getElementById('blocksList');
    const tbody = document.getElementById('blocksTableBody');
    
    if (!tbody) return;
    
    if (currentBlocks.length === 0) {
        emptyState.style.display = 'block';
        blocksList.style.display = 'none';
        return;
    }
    
    emptyState.style.display = 'none';
    blocksList.style.display = 'block';
    
    tbody.innerHTML = currentBlocks.map(block => {
        return `
            <tr>
                <td>${formatDateTime(block.start_datetime)}</td>
                <td>${formatDateTime(block.end_datetime)}</td>
                <td>${escapeHtml(block.reason || '-')}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBlock(${block.id})" title="Deletar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function showCreateBlockModal() {
    if (!currentProfessionalId) {
        showAlert('Selecione um profissional primeiro', 'warning');
        return;
    }
    
    document.getElementById('blockModalTitle').textContent = 'Novo Bloqueio';
    document.getElementById('blockId').value = '';
    document.getElementById('blockForm').reset();
    
    const modal = new bootstrap.Modal(document.getElementById('blockModal'));
    modal.show();
}

async function saveBlock() {
    if (!currentProfessionalId) {
        showAlert('Selecione um profissional primeiro', 'warning');
        return;
    }
    
    const startDatetime = document.getElementById('blockStartDatetime').value;
    const endDatetime = document.getElementById('blockEndDatetime').value;
    const reason = document.getElementById('blockReason').value;
    
    if (!startDatetime || !endDatetime) {
        showAlert('Preencha data/hora de início e fim', 'warning');
        return;
    }
    
    // Converte datetime-local para formato YYYY-MM-DD HH:MM:SS
    const startFormatted = startDatetime.replace('T', ' ') + ':00';
    const endFormatted = endDatetime.replace('T', ' ') + ':00';
    
    const submitBtn = document.getElementById('saveBlockBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
    }
    
    try {
        await apiRequest('/v1/clinic/schedule-blocks', {
            method: 'POST',
            body: JSON.stringify({
                professional_id: currentProfessionalId,
                start_datetime: startFormatted,
                end_datetime: endFormatted,
                reason: reason || null
            })
        });
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear(`/v1/clinic/professionals/${currentProfessionalId}/schedule`);
        }
        
        showAlert('Bloqueio criado com sucesso!', 'success');
        const modal = bootstrap.Modal.getInstance(document.getElementById('blockModal'));
        if (modal) modal.hide();
        
        await loadSchedule(currentProfessionalId);
    } catch (error) {
        showAlert('Erro ao criar bloqueio: ' + error.message, 'danger');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar';
        }
    }
}

async function deleteBlock(blockId) {
    if (!confirm('Tem certeza que deseja deletar este bloqueio?')) {
        return;
    }
    
    try {
        await apiRequest(`/v1/clinic/schedule-blocks/${blockId}`, {
            method: 'DELETE'
        });
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear(`/v1/clinic/professionals/${currentProfessionalId}/schedule`);
        }
        
        showAlert('Bloqueio deletado com sucesso!', 'success');
        await loadSchedule(currentProfessionalId);
    } catch (error) {
        showAlert('Erro ao deletar bloqueio: ' + error.message, 'danger');
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    loadProfessionals();
    
    const professionalSelect = document.getElementById('professionalSelect');
    if (professionalSelect) {
        professionalSelect.addEventListener('change', function() {
            currentProfessionalId = this.value ? parseInt(this.value) : null;
            if (currentProfessionalId) {
                loadSchedule(currentProfessionalId);
            } else {
                document.getElementById('emptyScheduleState').style.display = 'block';
                document.getElementById('scheduleContainer').style.display = 'none';
                document.getElementById('saveScheduleBtn').disabled = true;
                currentBlocks = [];
                renderBlocks();
            }
        });
    }
    
    // Verifica se há parâmetro professional_id na URL
    const urlParams = new URLSearchParams(window.location.search);
    const professionalIdParam = urlParams.get('professional_id');
    if (professionalIdParam && professionalSelect) {
        professionalSelect.value = professionalIdParam;
        professionalSelect.dispatchEvent(new Event('change'));
    }
});
</script>

