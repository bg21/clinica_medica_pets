<?php
/**
 * View de Configuração de Preços para Agendamentos
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-currency-dollar text-primary"></i>
                Configuração de Preços
            </h1>
            <p class="text-muted mb-0">Configure preços padrão por tipo de consulta, especialidade ou profissional</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPriceConfigModal">
            <i class="bi bi-plus-circle"></i> Nova Configuração
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Info Card -->
    <div class="alert alert-info mb-4">
        <h6 class="alert-heading">
            <i class="bi bi-info-circle me-2"></i>
            Como funciona o sistema de prioridade?
        </h6>
        <p class="mb-0">
            Ao criar um agendamento, o sistema sugere o preço seguindo esta ordem de prioridade:
        </p>
        <ol class="mb-0 mt-2">
            <li><strong>Profissional específico</strong> - Se houver configuração para o profissional selecionado</li>
            <li><strong>Especialidade</strong> - Se houver configuração para a especialidade do profissional</li>
            <li><strong>Tipo de consulta</strong> - Se houver configuração para o tipo de consulta</li>
        </ol>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tipo de Consulta</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">Todos os tipos</option>
                        <option value="consulta">Consulta</option>
                        <option value="cirurgia">Cirurgia</option>
                        <option value="vacinação">Vacinação</option>
                        <option value="exame">Exame</option>
                        <option value="retorno">Retorno</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Especialidade</label>
                    <input type="text" class="form-control" id="specialtyFilter" placeholder="Filtrar por especialidade...">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadConfigs()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Configurações -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Configurações de Preço</h5>
        </div>
        <div class="card-body">
            <div id="loadingConfigs" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando configurações...</p>
            </div>
            <div id="configsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Especialidade</th>
                                <th>Profissional</th>
                                <th>Preço</th>
                                <th>Padrão</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="configsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-currency-dollar fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma configuração encontrada</h5>
                    <p class="text-muted">Crie uma configuração para começar a usar preços automáticos.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Configuração -->
<div class="modal fade" id="createPriceConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Nova Configuração de Preço
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createPriceConfigForm" novalidate>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Preencha pelo menos um dos campos: Tipo de Consulta, Especialidade ou Profissional.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tag me-1"></i>
                            Preço <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="price_id" id="configPriceId" required>
                            <option value="">Selecione um preço...</option>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione um preço.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-check me-1"></i>
                                Tipo de Consulta
                            </label>
                            <select class="form-select" name="appointment_type" id="configAppointmentType">
                                <option value="">Nenhum (aplicar a todos)</option>
                                <option value="consulta">Consulta</option>
                                <option value="cirurgia">Cirurgia</option>
                                <option value="vacinação">Vacinação</option>
                                <option value="exame">Exame</option>
                                <option value="retorno">Retorno</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-briefcase me-1"></i>
                                Especialidade
                            </label>
                            <input type="text" class="form-control" name="specialty" id="configSpecialty" 
                                placeholder="Ex: Clínica Geral, Cirurgia, Dermatologia" maxlength="100">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-person-badge me-1"></i>
                            Profissional
                        </label>
                        <select class="form-select" name="professional_id" id="configProfessionalId">
                            <option value="">Nenhum (aplicar a todos)</option>
                        </select>
                        <small class="form-text text-muted">
                            Se selecionado, este preço terá prioridade sobre tipo e especialidade
                        </small>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" id="configIsDefault" value="1">
                        <label class="form-check-label" for="configIsDefault">
                            Marcar como padrão
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitConfigBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Criar Configuração
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Configuração -->
<div class="modal fade" id="editPriceConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>
                    Editar Configuração de Preço
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPriceConfigForm" novalidate>
                <input type="hidden" id="editConfigId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tag me-1"></i>
                            Preço <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="price_id" id="editConfigPriceId" required>
                            <option value="">Selecione um preço...</option>
                        </select>
                        <div class="invalid-feedback">Por favor, selecione um preço.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-check me-1"></i>
                                Tipo de Consulta
                            </label>
                            <select class="form-select" name="appointment_type" id="editConfigAppointmentType">
                                <option value="">Nenhum (aplicar a todos)</option>
                                <option value="consulta">Consulta</option>
                                <option value="cirurgia">Cirurgia</option>
                                <option value="vacinação">Vacinação</option>
                                <option value="exame">Exame</option>
                                <option value="retorno">Retorno</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-briefcase me-1"></i>
                                Especialidade
                            </label>
                            <input type="text" class="form-control" name="specialty" id="editConfigSpecialty" 
                                placeholder="Ex: Clínica Geral, Cirurgia, Dermatologia" maxlength="100">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-person-badge me-1"></i>
                            Profissional
                        </label>
                        <select class="form-select" name="professional_id" id="editConfigProfessionalId">
                            <option value="">Nenhum (aplicar a todos)</option>
                        </select>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" id="editConfigIsDefault" value="1">
                        <label class="form-check-label" for="editConfigIsDefault">
                            Marcar como padrão
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitEditConfigBtn">
                        <i class="bi bi-check-circle me-1"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let configs = [];
let prices = [];
let professionals = [];

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatCurrency(amount, currency = 'brl') {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: currency.toUpperCase()
    }).format(amount / 100);
}

document.addEventListener('DOMContentLoaded', () => {
    loadPrices();
    loadProfessionals();
    loadConfigs();
    
    // Form criar
    const createForm = document.getElementById('createPriceConfigForm');
    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!createForm.checkValidity()) {
                createForm.classList.add('was-validated');
                return;
            }
            
            const submitBtn = document.getElementById('submitConfigBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Criando...';
            }
            
            try {
                const formData = new FormData(createForm);
                const data = {
                    price_id: formData.get('price_id')
                };
                
                const appointmentType = formData.get('appointment_type');
                if (appointmentType) data.appointment_type = appointmentType;
                
                const specialty = formData.get('specialty');
                if (specialty && specialty.trim()) data.specialty = specialty.trim();
                
                const professionalId = formData.get('professional_id');
                if (professionalId) data.professional_id = parseInt(professionalId);
                
                if (formData.get('is_default') === '1') {
                    data.is_default = true;
                }
                
                // Validação: pelo menos um campo deve ser preenchido
                if (!data.appointment_type && !data.specialty && !data.professional_id) {
                    showAlert('Preencha pelo menos um campo: Tipo de Consulta, Especialidade ou Profissional', 'warning');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Configuração';
                    }
                    return;
                }
                
                await apiRequest('/v1/clinic/appointment-price-config', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/clinic/appointment-price-config');
                }
                
                showAlert('Configuração criada com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('createPriceConfigModal')).hide();
                loadConfigs();
            } catch (error) {
                showAlert(error.message || 'Erro ao criar configuração. Tente novamente.', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Criar Configuração';
                }
            }
        });
    }
    
    // Form editar
    const editForm = document.getElementById('editPriceConfigForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!editForm.checkValidity()) {
                editForm.classList.add('was-validated');
                return;
            }
            
            const configId = document.getElementById('editConfigId').value;
            const submitBtn = document.getElementById('submitEditConfigBtn');
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(editForm);
                const data = {
                    price_id: formData.get('price_id')
                };
                
                const appointmentType = formData.get('appointment_type');
                data.appointment_type = appointmentType || null;
                
                const specialty = formData.get('specialty');
                data.specialty = specialty && specialty.trim() ? specialty.trim() : null;
                
                const professionalId = formData.get('professional_id');
                data.professional_id = professionalId ? parseInt(professionalId) : null;
                
                data.is_default = formData.get('is_default') === '1';
                
                await apiRequest(`/v1/clinic/appointment-price-config/${configId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                if (typeof cache !== 'undefined' && cache.clear) {
                    cache.clear('/v1/clinic/appointment-price-config');
                }
                
                showAlert('Configuração atualizada com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editPriceConfigModal')).hide();
                loadConfigs();
            } catch (error) {
                showAlert(error.message || 'Erro ao atualizar configuração. Tente novamente.', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar Alterações';
                }
            }
        });
    }
});

async function loadPrices() {
    try {
        const response = await apiRequest('/v1/prices?active=true&limit=100', {
            cacheTTL: 60000
        });
        prices = response.data?.prices || response.data || [];
        
        // Preenche selects
        const priceSelects = ['configPriceId', 'editConfigPriceId'];
        priceSelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                const currentValue = select.value;
                select.innerHTML = '<option value="">Selecione um preço...</option>';
                prices.forEach(price => {
                    const option = document.createElement('option');
                    option.value = price.id;
                    const amount = formatCurrency(price.unit_amount, price.currency);
                    const productName = price.product?.name || 'Produto';
                    option.textContent = `${productName} - ${amount}`;
                    select.appendChild(option);
                });
                if (currentValue) {
                    select.value = currentValue;
                }
            }
        });
    } catch (error) {
        console.error('Erro ao carregar preços:', error);
    }
}

async function loadProfessionals() {
    try {
        const response = await apiRequest('/v1/clinic/professionals/active', {
            cacheTTL: 60000
        });
        professionals = response.data || [];
        
        // Preenche selects
        const professionalSelects = ['configProfessionalId', 'editConfigProfessionalId'];
        professionalSelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                const currentValue = select.value;
                select.innerHTML = '<option value="">Nenhum (aplicar a todos)</option>';
                professionals.forEach(professional => {
                    const option = document.createElement('option');
                    option.value = professional.id;
                    option.textContent = `${professional.name}${professional.specialty ? ' - ' + professional.specialty : ''}`;
                    select.appendChild(option);
                });
                if (currentValue) {
                    select.value = currentValue;
                }
            }
        });
    } catch (error) {
        console.error('Erro ao carregar profissionais:', error);
    }
}

async function loadConfigs() {
    try {
        document.getElementById('loadingConfigs').style.display = 'block';
        document.getElementById('configsList').style.display = 'none';
        
        const params = new URLSearchParams();
        const typeFilter = document.getElementById('typeFilter')?.value;
        const specialtyFilter = document.getElementById('specialtyFilter')?.value;
        
        if (typeFilter) params.append('appointment_type', typeFilter);
        if (specialtyFilter) params.append('specialty', specialtyFilter);
        
        const url = '/v1/clinic/appointment-price-config' + (params.toString() ? '?' + params.toString() : '');
        const response = await apiRequest(url);
        configs = response.data || [];
        
        renderConfigs();
    } catch (error) {
        showAlert('Erro ao carregar configurações: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingConfigs').style.display = 'none';
        document.getElementById('configsList').style.display = 'block';
    }
}

function renderConfigs() {
    const tbody = document.getElementById('configsTableBody');
    const emptyState = document.getElementById('emptyState');
    
    if (configs.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    tbody.innerHTML = configs.map(config => {
        const price = prices.find(p => p.id === config.price_id);
        const professional = professionals.find(p => p.id === config.professional_id);
        
        const priceText = price ? 
            `${price.product?.name || 'Produto'} - ${formatCurrency(price.unit_amount, price.currency)}` : 
            escapeHtml(config.price_id);
        
        return `
        <tr>
            <td>${escapeHtml(config.appointment_type || '-')}</td>
            <td>${escapeHtml(config.specialty || '-')}</td>
            <td>${professional ? escapeHtml(professional.name) : '-'}</td>
            <td><small>${priceText}</small></td>
            <td>${config.is_default ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>'}</td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="editConfig(${config.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteConfig(${config.id})" title="Deletar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        `;
    }).join('');
}

async function editConfig(id) {
    try {
        const response = await apiRequest(`/v1/clinic/appointment-price-config/${id}`);
        const config = response.data;
        
        document.getElementById('editConfigId').value = config.id;
        document.getElementById('editConfigPriceId').value = config.price_id;
        document.getElementById('editConfigAppointmentType').value = config.appointment_type || '';
        document.getElementById('editConfigSpecialty').value = config.specialty || '';
        document.getElementById('editConfigProfessionalId').value = config.professional_id || '';
        document.getElementById('editConfigIsDefault').checked = config.is_default || false;
        
        const modal = new bootstrap.Modal(document.getElementById('editPriceConfigModal'));
        modal.show();
    } catch (error) {
        showAlert('Erro ao carregar configuração: ' + error.message, 'danger');
    }
}

async function deleteConfig(id) {
    if (!confirm('Tem certeza que deseja deletar esta configuração?')) {
        return;
    }
    
    try {
        await apiRequest(`/v1/clinic/appointment-price-config/${id}`, {
            method: 'DELETE'
        });
        
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/clinic/appointment-price-config');
        }
        
        showAlert('Configuração deletada com sucesso!', 'success');
        loadConfigs();
    } catch (error) {
        showAlert('Erro ao deletar configuração: ' + error.message, 'danger');
    }
}
</script>

