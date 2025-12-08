<?php
/**
 * View de Configurações da Clínica
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-gear text-primary"></i>
                Configurações da Clínica
            </h1>
            <p class="text-muted mb-0">Configure as informações e horários de funcionamento da clínica</p>
        </div>
        <button class="btn btn-primary" onclick="saveConfiguration()" id="saveConfigBtn">
            <i class="bi bi-save"></i> Salvar Configurações
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Informações Básicas -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-building me-2"></i>
                Informações Básicas
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome da Clínica</label>
                    <input type="text" class="form-control" id="clinicName" placeholder="Nome da clínica">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="clinicPhone" placeholder="(00) 00000-0000" maxlength="15">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="clinicEmail" placeholder="contato@clinica.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Website</label>
                    <input type="url" class="form-control" id="clinicWebsite" placeholder="https://www.clinica.com">
                </div>
                <div class="col-12">
                    <label class="form-label">Endereço</label>
                    <input type="text" class="form-control" id="clinicAddress" placeholder="Rua, número, complemento">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="clinicCity" placeholder="Cidade">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <input type="text" class="form-control" id="clinicState" placeholder="Estado" maxlength="2">
                </div>
                <div class="col-md-4">
                    <label class="form-label">CEP</label>
                    <input type="text" class="form-control" id="clinicZipCode" placeholder="00000-000" maxlength="9">
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" id="clinicDescription" rows="3" placeholder="Descrição da clínica"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Logo da Clínica</label>
                    <div class="d-flex align-items-center gap-3">
                        <div id="logoPreview" style="display: none;">
                            <img id="logoPreviewImg" src="" alt="Logo" style="max-width: 200px; max-height: 100px; border: 1px solid #dee2e6; border-radius: 4px; padding: 5px;">
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" class="form-control" id="clinicLogo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                            <small class="text-muted">Formatos aceitos: JPEG, PNG, GIF, WebP. Tamanho máximo: 5MB</small>
                        </div>
                        <button class="btn btn-outline-primary" onclick="uploadLogo()" id="uploadLogoBtn">
                            <i class="bi bi-upload"></i> Enviar Logo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Horários de Funcionamento -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-clock me-2"></i>
                Horários de Funcionamento
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 150px;">Dia da Semana</th>
                            <th>Horário de Abertura</th>
                            <th>Horário de Fechamento</th>
                        </tr>
                    </thead>
                    <tbody id="businessHoursTableBody">
                        <!-- Será preenchido via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Configurações Operacionais -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-sliders me-2"></i>
                Configurações Operacionais
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Duração Padrão de Consultas (minutos)</label>
                    <input type="number" class="form-control" id="defaultAppointmentDuration" min="1" value="30">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Intervalo Entre Consultas (minutos)</label>
                    <input type="number" class="form-control" id="timeSlotInterval" min="1" value="15">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Horas Mínimas para Cancelamento</label>
                    <input type="number" class="form-control" id="cancellationHours" min="0" value="24">
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="allowOnlineBooking" checked>
                        <label class="form-check-label" for="allowOnlineBooking">
                            Permitir agendamento online
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="requireConfirmation">
                        <label class="form-check-label" for="requireConfirmation">
                            Exigir confirmação para agendamentos
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentConfiguration = null;
const daysOfWeek = [
    { key: 'monday', name: 'Segunda-feira' },
    { key: 'tuesday', name: 'Terça-feira' },
    { key: 'wednesday', name: 'Quarta-feira' },
    { key: 'thursday', name: 'Quinta-feira' },
    { key: 'friday', name: 'Sexta-feira' },
    { key: 'saturday', name: 'Sábado' },
    { key: 'sunday', name: 'Domingo' }
];

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
    
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) alert.remove();
    }, 5000);
}

async function loadConfiguration() {
    try {
        if (typeof apiRequest === 'undefined') {
            console.error('apiRequest não está disponível');
            showAlert('Erro: função apiRequest não encontrada. Recarregue a página.', 'danger');
            return;
        }
        
        const response = await apiRequest('/v1/clinic/configuration', {
            cacheTTL: 30000
        });
        
        console.log('Resposta da API de configurações:', response);
        
        // ResponseHelper retorna {success: true, data: {...}}
        // Mas se houver erro, retorna {error: ..., message: ..., code: ...}
        if (response && response.error) {
            console.error('Erro ao carregar configurações:', response);
            // Se houver erro, usa configuração padrão vazia
            currentConfiguration = {
                tenant_id: null,
                default_appointment_duration: 30,
                time_slot_interval: 15,
                allow_online_booking: true,
                require_confirmation: false,
                cancellation_hours: 24
            };
            populateForm();
            return;
        }
        
        if (response && response.success !== false) {
            // Se response.data existe, usa ele
            if (response.data) {
                currentConfiguration = response.data;
            } 
            // Se response.data não existe mas response tem propriedades, usa response diretamente
            else if (response && typeof response === 'object' && Object.keys(response).length > 0) {
                // Remove propriedades de controle (success, message)
                const {success, message, ...configData} = response;
                currentConfiguration = configData;
            }
            // Se tudo falhar, usa objeto vazio
            else {
                console.warn('Resposta da API não contém dados, usando configuração padrão');
                currentConfiguration = {
                    tenant_id: null,
                    default_appointment_duration: 30,
                    time_slot_interval: 15,
                    allow_online_booking: true,
                    require_confirmation: false,
                    cancellation_hours: 24
                };
            }
            
            populateForm();
        } else {
            console.warn('Resposta da API indica erro, usando configuração padrão');
            currentConfiguration = {
                tenant_id: null,
                default_appointment_duration: 30,
                time_slot_interval: 15,
                allow_online_booking: true,
                require_confirmation: false,
                cancellation_hours: 24
            };
            populateForm();
        }
    } catch (error) {
        console.error('Erro ao carregar configurações:', error);
        showAlert('Erro ao carregar configurações: ' + (error.message || 'Erro desconhecido'), 'danger');
        // Tenta carregar formulário vazio mesmo com erro
        currentConfiguration = {};
        populateForm();
    }
}

function populateForm() {
    if (!currentConfiguration) return;
    
    // Informações básicas
    document.getElementById('clinicName').value = currentConfiguration.clinic_name || '';
    document.getElementById('clinicPhone').value = currentConfiguration.clinic_phone || '';
    document.getElementById('clinicEmail').value = currentConfiguration.clinic_email || '';
    document.getElementById('clinicWebsite').value = currentConfiguration.clinic_website || '';
    document.getElementById('clinicAddress').value = currentConfiguration.clinic_address || '';
    document.getElementById('clinicCity').value = currentConfiguration.clinic_city || '';
    document.getElementById('clinicState').value = currentConfiguration.clinic_state || '';
    document.getElementById('clinicZipCode').value = currentConfiguration.clinic_zip_code || '';
    document.getElementById('clinicDescription').value = currentConfiguration.clinic_description || '';
    
    // Logo
    if (currentConfiguration.clinic_logo) {
        const logoUrl = currentConfiguration.clinic_logo.startsWith('/') 
            ? currentConfiguration.clinic_logo 
            : '/' + currentConfiguration.clinic_logo;
        document.getElementById('logoPreviewImg').src = logoUrl;
        document.getElementById('logoPreview').style.display = 'block';
    }
    
    // Configurações operacionais
    document.getElementById('defaultAppointmentDuration').value = currentConfiguration.default_appointment_duration || 30;
    document.getElementById('timeSlotInterval').value = currentConfiguration.time_slot_interval || 15;
    document.getElementById('cancellationHours').value = currentConfiguration.cancellation_hours || 24;
    document.getElementById('allowOnlineBooking').checked = currentConfiguration.allow_online_booking !== false;
    document.getElementById('requireConfirmation').checked = currentConfiguration.require_confirmation === true;
    
    // Renderiza horários
    renderBusinessHours();
}

function renderBusinessHours() {
    const tbody = document.getElementById('businessHoursTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    daysOfWeek.forEach(day => {
        const openingKey = `opening_time_${day.key}`;
        const closingKey = `closing_time_${day.key}`;
        
        const openingTime = currentConfiguration?.[openingKey] || '';
        const closingTime = currentConfiguration?.[closingKey] || '';
        
        // Converte formato HH:MM:SS para HH:MM se necessário
        const openingFormatted = openingTime ? openingTime.substring(0, 5) : '';
        const closingFormatted = closingTime ? closingTime.substring(0, 5) : '';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${day.name}</strong></td>
            <td>
                <input type="time" class="form-control" 
                       id="${openingKey}" 
                       value="${openingFormatted}">
            </td>
            <td>
                <input type="time" class="form-control" 
                       id="${closingKey}" 
                       value="${closingFormatted}">
            </td>
        `;
        tbody.appendChild(row);
    });
}

async function saveConfiguration() {
    const submitBtn = document.getElementById('saveConfigBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
    }
    
    try {
        // Coleta dados do formulário
        const data = {
            // Informações básicas
            clinic_name: document.getElementById('clinicName').value.trim() || null,
            clinic_phone: document.getElementById('clinicPhone').value.trim() || null,
            clinic_email: document.getElementById('clinicEmail').value.trim() || null,
            clinic_website: document.getElementById('clinicWebsite').value.trim() || null,
            clinic_address: document.getElementById('clinicAddress').value.trim() || null,
            clinic_city: document.getElementById('clinicCity').value.trim() || null,
            clinic_state: document.getElementById('clinicState').value.trim().toUpperCase() || null,
            clinic_zip_code: document.getElementById('clinicZipCode').value.trim() || null,
            clinic_description: document.getElementById('clinicDescription').value.trim() || null,
            
            // Horários de funcionamento
            // Input type="time" retorna formato HH:MM, mas precisamos garantir que não seja string vazia
            opening_time_monday: (document.getElementById('opening_time_monday').value || null),
            closing_time_monday: (document.getElementById('closing_time_monday').value || null),
            opening_time_tuesday: (document.getElementById('opening_time_tuesday').value || null),
            closing_time_tuesday: (document.getElementById('closing_time_tuesday').value || null),
            opening_time_wednesday: (document.getElementById('opening_time_wednesday').value || null),
            closing_time_wednesday: (document.getElementById('closing_time_wednesday').value || null),
            opening_time_thursday: (document.getElementById('opening_time_thursday').value || null),
            closing_time_thursday: (document.getElementById('closing_time_thursday').value || null),
            opening_time_friday: (document.getElementById('opening_time_friday').value || null),
            closing_time_friday: (document.getElementById('closing_time_friday').value || null),
            opening_time_saturday: (document.getElementById('opening_time_saturday').value || null),
            closing_time_saturday: (document.getElementById('closing_time_saturday').value || null),
            opening_time_sunday: (document.getElementById('opening_time_sunday').value || null),
            closing_time_sunday: (document.getElementById('closing_time_sunday').value || null),
            
            // Configurações operacionais
            default_appointment_duration: parseInt(document.getElementById('defaultAppointmentDuration').value) || 30,
            time_slot_interval: parseInt(document.getElementById('timeSlotInterval').value) || 15,
            cancellation_hours: parseInt(document.getElementById('cancellationHours').value) || 24,
            allow_online_booking: document.getElementById('allowOnlineBooking').checked,
            require_confirmation: document.getElementById('requireConfirmation').checked
        };
        
        // Processa horários: se apenas um está preenchido, remove ambos
        // Se ambos estão preenchidos, garante que fechamento > abertura
        daysOfWeek.forEach(day => {
            const openingKey = `opening_time_${day.key}`;
            const closingKey = `closing_time_${day.key}`;
            
            const openingValue = data[openingKey];
            const closingValue = data[closingKey];
            
            // Se ambos estão vazios, define como null
            if ((!openingValue || openingValue === '') && (!closingValue || closingValue === '')) {
                data[openingKey] = null;
                data[closingKey] = null;
            }
            // Se apenas um está preenchido, remove ambos (validação exige ambos ou nenhum)
            else if ((!openingValue || openingValue === '') && closingValue && closingValue !== '') {
                data[openingKey] = null;
                data[closingKey] = null;
            }
            else if (openingValue && openingValue !== '' && (!closingValue || closingValue === '')) {
                data[openingKey] = null;
                data[closingKey] = null;
            }
            // Se ambos estão preenchidos, valida e formata
            else if (openingValue && closingValue) {
                // Adiciona :00 ao final se necessário (formato HH:MM -> HH:MM:SS)
                data[openingKey] = openingValue.length === 5 ? openingValue + ':00' : openingValue;
                data[closingKey] = closingValue.length === 5 ? closingValue + ':00' : closingValue;
                
                // Validação frontend: se fechamento <= abertura, remove ambos
                const openingTime = openingValue.split(':').map(Number);
                const closingTime = closingValue.split(':').map(Number);
                const openingMinutes = openingTime[0] * 60 + (openingTime[1] || 0);
                const closingMinutes = closingTime[0] * 60 + (closingTime[1] || 0);
                
                if (closingMinutes <= openingMinutes) {
                    console.warn(`Horário de fechamento de ${day.name} deve ser posterior ao de abertura. Removendo ambos.`);
                    data[openingKey] = null;
                    data[closingKey] = null;
                }
            }
        });
        
        console.log('Dados sendo enviados:', data);
        
        try {
            const response = await apiRequest('/v1/clinic/configuration', {
                method: 'PUT',
                body: JSON.stringify(data)
            });
            
            if (typeof cache !== 'undefined' && cache.clear) {
                cache.clear('/v1/clinic/configuration');
            }
            
            showAlert('Configurações salvas com sucesso!', 'success');
            await loadConfiguration();
        } catch (apiError) {
            console.error('Erro completo da API:', apiError);
            console.error('Erro response:', apiError.response);
            
            // Tenta extrair erros de validação da resposta
            let errorMessage = apiError.message || 'Erro desconhecido';
            
            if (apiError.response && apiError.response.errors) {
                // Se há erros de validação específicos, mostra eles
                const validationErrors = apiError.response.errors;
                const errorList = Object.entries(validationErrors)
                    .map(([field, message]) => {
                        // Traduz nomes de campos para português
                        const fieldNames = {
                            'clinic_email': 'Email',
                            'clinic_website': 'Website',
                            'default_appointment_duration': 'Duração padrão',
                            'time_slot_interval': 'Intervalo entre consultas',
                            'cancellation_hours': 'Horas de cancelamento',
                            'opening_time_monday': 'Horário de abertura - Segunda',
                            'closing_time_monday': 'Horário de fechamento - Segunda',
                            'opening_time_tuesday': 'Horário de abertura - Terça',
                            'closing_time_tuesday': 'Horário de fechamento - Terça',
                            'opening_time_wednesday': 'Horário de abertura - Quarta',
                            'closing_time_wednesday': 'Horário de fechamento - Quarta',
                            'opening_time_thursday': 'Horário de abertura - Quinta',
                            'closing_time_thursday': 'Horário de fechamento - Quinta',
                            'opening_time_friday': 'Horário de abertura - Sexta',
                            'closing_time_friday': 'Horário de fechamento - Sexta',
                            'opening_time_saturday': 'Horário de abertura - Sábado',
                            'closing_time_saturday': 'Horário de fechamento - Sábado',
                            'opening_time_sunday': 'Horário de abertura - Domingo',
                            'closing_time_sunday': 'Horário de fechamento - Domingo'
                        };
                        const fieldName = fieldNames[field] || field;
                        return `<strong>${fieldName}:</strong> ${message}`;
                    })
                    .join('<br>');
                errorMessage = 'Erros de validação encontrados:<br><br>' + errorList;
            } else if (apiError.response && apiError.response.message) {
                errorMessage = apiError.response.message;
            }
            
            showAlert(errorMessage, 'danger');
            throw apiError; // Re-lança para manter o fluxo de erro
        }
    } catch (error) {
        // Erro geral (não relacionado à API)
        console.error('Erro geral:', error);
        showAlert('Erro ao salvar configurações: ' + (error.message || 'Erro desconhecido'), 'danger');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-save"></i> Salvar Configurações';
        }
    }
}

async function uploadLogo() {
    const fileInput = document.getElementById('clinicLogo');
    const uploadBtn = document.getElementById('uploadLogoBtn');
    
    if (!fileInput.files || fileInput.files.length === 0) {
        showAlert('Selecione um arquivo antes de enviar', 'warning');
        return;
    }
    
    if (uploadBtn) {
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando...';
    }
    
    try {
        const formData = new FormData();
        formData.append('logo', fileInput.files[0]);
        
        const response = await fetch('/v1/clinic/logo', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Erro ao fazer upload do logo');
        }
        
        const result = await response.json();
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/clinic/configuration');
        }
        
        // Atualiza preview
        if (result.data && result.data.logo_url) {
            const logoUrl = result.data.logo_url.startsWith('/') 
                ? result.data.logo_url 
                : '/' + result.data.logo_url;
            document.getElementById('logoPreviewImg').src = logoUrl;
            document.getElementById('logoPreview').style.display = 'block';
            currentConfiguration.clinic_logo = result.data.logo_path;
        }
        
        showAlert('Logo enviado com sucesso!', 'success');
        fileInput.value = '';
    } catch (error) {
        showAlert('Erro ao enviar logo: ' + error.message, 'danger');
    } finally {
        if (uploadBtn) {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="bi bi-upload"></i> Enviar Logo';
        }
    }
}

// Máscaras para campos
document.addEventListener('DOMContentLoaded', () => {
    // Aguarda um pouco para garantir que apiRequest está disponível
    if (typeof apiRequest === 'undefined') {
        console.warn('apiRequest não está disponível, aguardando...');
        setTimeout(() => {
            if (typeof apiRequest !== 'undefined') {
                loadConfiguration();
            } else {
                console.error('apiRequest não está disponível após timeout');
                showAlert('Erro ao carregar: função apiRequest não encontrada. Recarregue a página.', 'danger');
            }
        }, 500);
    } else {
        loadConfiguration();
    }
    
    // Máscara de telefone
    const phoneInput = document.getElementById('clinicPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else {
                value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
            }
            e.target.value = value;
        });
    }
    
    // Máscara de CEP
    const zipCodeInput = document.getElementById('clinicZipCode');
    if (zipCodeInput) {
        zipCodeInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d{0,3})/, '$1-$2');
            e.target.value = value;
        });
    }
    
    // Preview de logo antes de enviar
    const logoInput = document.getElementById('clinicLogo');
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('logoPreviewImg').src = e.target.result;
                    document.getElementById('logoPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

