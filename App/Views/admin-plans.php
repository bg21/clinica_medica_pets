<?php
/**
 * View - Gerenciar Planos e Módulos (Admin)
 * Interface para o dono do SaaS gerenciar planos e módulos
 */
?>
<div class="container-fluid py-4 admin-plans-page">
    <!-- Page Header -->
    <div class="page-header-card mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold">
                    <i class="bi bi-grid-3x3-gap text-primary me-2"></i>
                    Gerenciar Planos e Módulos
                </h1>
                <p class="text-muted mb-0">Crie e gerencie planos e módulos do sistema</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary btn-lg shadow-sm" onclick="showCreatePlanModal()">
                    <i class="bi bi-plus-circle me-2"></i>
                    Novo Plano
                </button>
                <button class="btn btn-outline-primary btn-lg shadow-sm" onclick="showCreateModuleModal()">
                    <i class="bi bi-puzzle me-2"></i>
                    Novo Módulo
                </button>
            </div>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Tabs -->
    <div class="tabs-wrapper mb-4">
        <ul class="nav nav-pills nav-pills-custom" id="plansTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="plans-tab" data-bs-toggle="tab" data-bs-target="#plans" type="button" role="tab">
                    <i class="bi bi-credit-card me-2"></i>
                    Planos
                    <span class="badge bg-light text-dark ms-2" id="plansCountBadge">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="modules-tab" data-bs-toggle="tab" data-bs-target="#modules" type="button" role="tab">
                    <i class="bi bi-puzzle me-2"></i>
                    Módulos
                    <span class="badge bg-light text-dark ms-2" id="modulesCountBadge">0</span>
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="plansTabContent">
        <!-- Tab: Planos -->
        <div class="tab-pane fade show active" id="plans" role="tabpanel">
            <div id="loadingPlans" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div id="plansContainer" class="row g-3">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <!-- Tab: Módulos -->
        <div class="tab-pane fade" id="modules" role="tabpanel">
            <div id="loadingModules" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div id="modulesContainer" class="row g-3">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Modal: Criar/Editar Plano -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="planModalTitle">Novo Plano</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="planForm">
                    <input type="hidden" id="planId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ID do Plano *</label>
                            <input type="text" class="form-control" id="planPlanId" name="plan_id" required>
                            <small class="text-muted">Ex: basic, professional (sem espaços, minúsculas)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="planName" name="name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" id="planDescription" name="description" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Preço Mensal (centavos) *</label>
                            <input type="number" class="form-control" id="planMonthlyPrice" name="monthly_price" required>
                            <small class="text-muted">Ex: 4900 = R$ 49,00</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Preço Anual (centavos) *</label>
                            <input type="number" class="form-control" id="planYearlyPrice" name="yearly_price" required>
                            <small class="text-muted">Ex: 49000 = R$ 490,00</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Limite de Usuários</label>
                            <input type="number" class="form-control" id="planMaxUsers" name="max_users" min="1">
                            <small class="text-muted">Deixe vazio para ilimitado</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stripe Price ID (Mensal)</label>
                            <input type="text" class="form-control" id="planStripeMonthly" name="stripe_price_id_monthly">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Stripe Price ID (Anual)</label>
                        <input type="text" class="form-control" id="planStripeYearly" name="stripe_price_id_yearly">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Módulos Disponíveis *</label>
                        <div id="planModulesCheckboxes" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <!-- Será preenchido via JavaScript -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Features (uma por linha)</label>
                        <textarea class="form-control" id="planFeatures" name="features" rows="4" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"></textarea>
                        <small class="text-muted">Uma feature por linha</small>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="planIsActive" name="is_active" checked>
                        <label class="form-check-label" for="planIsActive">
                            Plano Ativo
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="savePlan()">Salvar Plano</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Criar/Editar Módulo -->
<div class="modal fade" id="moduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moduleModalTitle">Novo Módulo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="moduleForm">
                    <input type="hidden" id="moduleId" name="id">
                    
                    <div class="mb-3">
                        <label class="form-label">ID do Módulo *</label>
                        <input type="text" class="form-control" id="moduleModuleId" name="module_id" required>
                        <small class="text-muted">Ex: vaccines, hospitalization (sem espaços, minúsculas)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="moduleName" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" id="moduleDescription" name="description" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ícone Bootstrap Icons</label>
                        <input type="text" class="form-control" id="moduleIcon" name="icon" placeholder="bi-shield-check">
                        <small class="text-muted">Ex: bi-shield-check, bi-hospital</small>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="moduleIsActive" name="is_active" checked>
                        <label class="form-check-label" for="moduleIsActive">
                            Módulo Ativo
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveModule()">Salvar Módulo</button>
            </div>
        </div>
    </div>
</div>

<script>
let allModules = [];
let allPlans = [];
let editingPlanId = null;
let editingModuleId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadPlans();
    loadModules();
});

// ========== PLANOS ==========

async function loadPlans() {
    const loadingEl = document.getElementById('loadingPlans');
    const containerEl = document.getElementById('plansContainer');
    
    loadingEl.style.display = 'block';
    containerEl.innerHTML = '';

    try {
        const response = await apiRequest('/v1/admin/plans');
        
        if (response.success) {
            allPlans = response.data;
            renderPlans(allPlans);
        } else {
            showAlert('Erro ao carregar planos: ' + (response.message || 'Erro desconhecido'), 'danger');
        }
    } catch (error) {
        console.error('Erro ao carregar planos:', error);
        showAlert('Erro ao carregar planos: ' + error.message, 'danger');
    } finally {
        loadingEl.style.display = 'none';
    }
}

function renderPlans(plans) {
    const container = document.getElementById('plansContainer');
    const countBadge = document.getElementById('plansCountBadge');
    
    if (countBadge) {
        countBadge.textContent = plans.length;
    }
    
    if (plans.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="empty-state-card">
                    <div class="empty-state-icon">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                    <h5 class="empty-state-title">Nenhum plano cadastrado</h5>
                    <p class="empty-state-text">Comece criando seu primeiro plano para oferecer às clínicas.</p>
                    <button class="btn btn-primary" onclick="showCreatePlanModal()">
                        <i class="bi bi-plus-circle me-2"></i>
                        Criar Primeiro Plano
                    </button>
                </div>
            </div>
        `;
        return;
    }

    container.innerHTML = plans.map(plan => {
        const modulesCount = plan.modules?.length || 0;
        const monthlyPrice = (plan.monthly_price / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const yearlyPrice = (plan.yearly_price / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const maxUsers = plan.max_users === null ? 'Ilimitado' : plan.max_users;
        const isActive = plan.is_active == 1;

        return `
            <div class="col-md-6 col-lg-4">
                <div class="plan-card ${!isActive ? 'plan-inactive' : ''}">
                    <div class="plan-card-header">
                        <div class="plan-card-title-section">
                            <h5 class="plan-card-title">${escapeHtml(plan.name)}</h5>
                            ${!isActive ? '<span class="badge bg-secondary">Inativo</span>' : '<span class="badge bg-success">Ativo</span>'}
                        </div>
                        <div class="plan-card-actions">
                            <button class="btn btn-sm btn-icon btn-edit" onclick="editPlan(${plan.id})" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-icon btn-delete" onclick="deletePlan(${plan.id})" title="Remover">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="plan-card-body">
                        ${plan.description ? `<p class="plan-card-description">${escapeHtml(plan.description)}</p>` : ''}
                        
                        <div class="plan-pricing">
                            <div class="pricing-item">
                                <span class="pricing-label">
                                    <i class="bi bi-calendar-month me-1"></i>
                                    Mensal
                                </span>
                                <span class="pricing-value">${monthlyPrice}</span>
                            </div>
                            <div class="pricing-item">
                                <span class="pricing-label">
                                    <i class="bi bi-calendar-year me-1"></i>
                                    Anual
                                </span>
                                <span class="pricing-value">${yearlyPrice}</span>
                            </div>
                        </div>
                        
                        <div class="plan-info-grid">
                            <div class="info-item">
                                <i class="bi bi-people info-icon"></i>
                                <div>
                                    <span class="info-label">Usuários</span>
                                    <span class="info-value">${maxUsers}</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-puzzle info-icon"></i>
                                <div>
                                    <span class="info-label">Módulos</span>
                                    <span class="info-value">${modulesCount}</span>
                                </div>
                            </div>
                        </div>
                        
                        ${modulesCount > 0 ? `
                            <div class="plan-modules-preview">
                                <div class="modules-preview-header">
                                    <small class="text-muted">Módulos incluídos:</small>
                                </div>
                                <div class="modules-tags">
                                    ${(plan.modules || []).slice(0, 4).map(m => `
                                        <span class="module-tag">
                                            <i class="bi ${m.icon || 'bi-puzzle'} me-1"></i>
                                            ${escapeHtml(m.name)}
                                        </span>
                                    `).join('')}
                                    ${modulesCount > 4 ? `<span class="module-tag module-tag-more">+${modulesCount - 4} mais</span>` : ''}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function showCreatePlanModal() {
    editingPlanId = null;
    document.getElementById('planModalTitle').textContent = 'Novo Plano';
    document.getElementById('planForm').reset();
    document.getElementById('planId').value = '';
    loadModulesForPlan();
    new bootstrap.Modal(document.getElementById('planModal')).show();
}

function editPlan(planId) {
    editingPlanId = planId;
    const plan = allPlans.find(p => p.id == planId);
    
    if (!plan) return;

    document.getElementById('planModalTitle').textContent = 'Editar Plano';
    document.getElementById('planId').value = plan.id;
    document.getElementById('planPlanId').value = plan.plan_id;
    document.getElementById('planName').value = plan.name;
    document.getElementById('planDescription').value = plan.description || '';
    document.getElementById('planMonthlyPrice').value = plan.monthly_price;
    document.getElementById('planYearlyPrice').value = plan.yearly_price;
    document.getElementById('planMaxUsers').value = plan.max_users || '';
    document.getElementById('planStripeMonthly').value = plan.stripe_price_id_monthly || '';
    document.getElementById('planStripeYearly').value = plan.stripe_price_id_yearly || '';
    document.getElementById('planIsActive').checked = plan.is_active == 1;
    
    // Features
    const features = plan.features || [];
    document.getElementById('planFeatures').value = Array.isArray(features) ? features.join('\n') : '';
    
    loadModulesForPlan(plan.modules?.map(m => m.id) || []);
    new bootstrap.Modal(document.getElementById('planModal')).show();
}

async function loadModulesForPlan(selectedModuleIds = []) {
    const container = document.getElementById('planModulesCheckboxes');
    
    if (allModules.length === 0) {
        await loadModules();
    }
    
    container.innerHTML = allModules.map(module => {
        const isChecked = selectedModuleIds.includes(module.id);
        return `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="modules[]" value="${module.id}" id="module_${module.id}" ${isChecked ? 'checked' : ''}>
                <label class="form-check-label" for="module_${module.id}">
                    <i class="bi ${module.icon || 'bi-puzzle'} me-2"></i>
                    ${module.name}
                </label>
            </div>
        `;
    }).join('');
}

async function savePlan() {
    const form = document.getElementById('planForm');
    const formData = new FormData(form);
    
    const data = {
        plan_id: formData.get('plan_id'),
        name: formData.get('name'),
        description: formData.get('description') || null,
        monthly_price: parseInt(formData.get('monthly_price')),
        yearly_price: parseInt(formData.get('yearly_price')),
        max_users: formData.get('max_users') ? parseInt(formData.get('max_users')) : null,
        stripe_price_id_monthly: formData.get('stripe_price_id_monthly') || null,
        stripe_price_id_yearly: formData.get('stripe_price_id_yearly') || null,
        is_active: formData.get('is_active') === 'on',
        features: formData.get('features').split('\n').filter(f => f.trim()).map(f => f.trim())
    };
    
    // Módulos selecionados
    const modules = Array.from(form.querySelectorAll('input[name="modules[]"]:checked')).map(cb => parseInt(cb.value));
    data.modules = modules;
    
    try {
        const planId = formData.get('id');
        const url = planId ? `/v1/admin/plans/${planId}` : '/v1/admin/plans';
        const method = planId ? 'PUT' : 'POST';
        
        const response = await apiRequest(url, {
            method: method,
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (response.success) {
            showAlert('Plano salvo com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('planModal')).hide();
            loadPlans();
        } else {
            showAlert('Erro ao salvar plano: ' + (response.message || 'Erro desconhecido'), 'danger');
        }
    } catch (error) {
        console.error('Erro ao salvar plano:', error);
        showAlert('Erro ao salvar plano: ' + error.message, 'danger');
    }
}

async function deletePlan(planId) {
    if (!confirm('Tem certeza que deseja remover este plano?')) return;
    
    try {
        const response = await apiRequest(`/v1/admin/plans/${planId}`, { method: 'DELETE' });
        
        if (response.success) {
            showAlert('Plano removido com sucesso!', 'success');
            loadPlans();
        } else {
            showAlert('Erro ao remover plano: ' + (response.message || 'Erro desconhecido'), 'danger');
        }
    } catch (error) {
        console.error('Erro ao remover plano:', error);
        showAlert('Erro ao remover plano: ' + error.message, 'danger');
    }
}

// ========== MÓDULOS ==========

async function loadModules() {
    const loadingEl = document.getElementById('loadingModules');
    const containerEl = document.getElementById('modulesContainer');
    
    loadingEl.style.display = 'block';
    containerEl.innerHTML = '';

    try {
        const response = await apiRequest('/v1/admin/modules');
        
        if (response.success) {
            allModules = response.data;
            renderModules(allModules);
        } else {
            showAlert('Erro ao carregar módulos: ' + (response.message || 'Erro desconhecido'), 'danger');
        }
    } catch (error) {
        console.error('Erro ao carregar módulos:', error);
        showAlert('Erro ao carregar módulos: ' + error.message, 'danger');
    } finally {
        loadingEl.style.display = 'none';
    }
}

function renderModules(modules) {
    const container = document.getElementById('modulesContainer');
    const countBadge = document.getElementById('modulesCountBadge');
    
    if (countBadge) {
        countBadge.textContent = modules.length;
    }
    
    if (modules.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="empty-state-card">
                    <div class="empty-state-icon">
                        <i class="bi bi-puzzle"></i>
                    </div>
                    <h5 class="empty-state-title">Nenhum módulo cadastrado</h5>
                    <p class="empty-state-text">Comece criando seu primeiro módulo para vincular aos planos.</p>
                    <button class="btn btn-primary" onclick="showCreateModuleModal()">
                        <i class="bi bi-plus-circle me-2"></i>
                        Criar Primeiro Módulo
                    </button>
                </div>
            </div>
        `;
        return;
    }

    container.innerHTML = modules.map(module => {
        const isActive = module.is_active == 1;
        return `
            <div class="col-md-6 col-lg-4">
                <div class="module-card ${!isActive ? 'module-inactive' : ''}">
                    <div class="module-card-header">
                        <div class="module-card-icon">
                            <i class="bi ${module.icon || 'bi-puzzle'}"></i>
                        </div>
                        <div class="module-card-title-section">
                            <h6 class="module-card-title">${escapeHtml(module.name)}</h6>
                            ${!isActive ? '<span class="badge bg-secondary">Inativo</span>' : '<span class="badge bg-success">Ativo</span>'}
                        </div>
                        <div class="module-card-actions">
                            <button class="btn btn-sm btn-icon btn-edit" onclick="editModule(${module.id})" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-icon btn-delete" onclick="deleteModule(${module.id})" title="Remover">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="module-card-body">
                        ${module.description ? `<p class="module-card-description">${escapeHtml(module.description)}</p>` : '<p class="module-card-description text-muted">Sem descrição</p>'}
                        <div class="module-card-footer">
                            <small class="text-muted">
                                <i class="bi bi-tag me-1"></i>
                                ID: <code>${escapeHtml(module.module_id)}</code>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Função auxiliar para escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showCreateModuleModal() {
    editingModuleId = null;
    document.getElementById('moduleModalTitle').textContent = 'Novo Módulo';
    document.getElementById('moduleForm').reset();
    document.getElementById('moduleId').value = '';
    new bootstrap.Modal(document.getElementById('moduleModal')).show();
}

function editModule(moduleId) {
    editingModuleId = moduleId;
    const module = allModules.find(m => m.id == moduleId);
    
    if (!module) return;

    document.getElementById('moduleModalTitle').textContent = 'Editar Módulo';
    document.getElementById('moduleId').value = module.id;
    document.getElementById('moduleModuleId').value = module.module_id;
    document.getElementById('moduleName').value = module.name;
    document.getElementById('moduleDescription').value = module.description || '';
    document.getElementById('moduleIcon').value = module.icon || '';
    document.getElementById('moduleIsActive').checked = module.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('moduleModal')).show();
}

async function saveModule() {
    const form = document.getElementById('moduleForm');
    const formData = new FormData(form);
    
    const data = {
        module_id: formData.get('module_id'),
        name: formData.get('name'),
        description: formData.get('description') || null,
        icon: formData.get('icon') || null,
        is_active: formData.get('is_active') === 'on'
    };
    
    try {
        const moduleId = formData.get('id');
        const url = moduleId ? `/v1/admin/modules/${moduleId}` : '/v1/admin/modules';
        const method = moduleId ? 'PUT' : 'POST';
        
        const response = await apiRequest(url, {
            method: method,
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (response.success) {
            showAlert('Módulo salvo com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('moduleModal')).hide();
            loadModules();
            // Recarrega módulos para o modal de planos também
            if (document.getElementById('planModal').classList.contains('show')) {
                loadModulesForPlan();
            }
        } else {
            showAlert('Erro ao salvar módulo: ' + (response.message || 'Erro desconhecido'), 'danger');
        }
    } catch (error) {
        console.error('Erro ao salvar módulo:', error);
        showAlert('Erro ao salvar módulo: ' + error.message, 'danger');
    }
}

async function deleteModule(moduleId) {
    if (!confirm('Tem certeza que deseja remover este módulo?')) return;
    
    try {
        const response = await apiRequest(`/v1/admin/modules/${moduleId}`, { method: 'DELETE' });
        
        if (response.success) {
            showAlert('Módulo removido com sucesso!', 'success');
            loadModules();
        } else {
            showAlert('Erro ao remover módulo: ' + (response.message || 'Erro desconhecido'), 'danger');
        }
    } catch (error) {
        console.error('Erro ao remover módulo:', error);
        showAlert('Erro ao remover módulo: ' + error.message, 'danger');
    }
}
</script>

<style>
/* ========== ADMIN PLANS PAGE STYLES ========== */
.admin-plans-page {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: calc(100vh - 60px);
}

/* Page Header */
.page-header-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.page-header-card h1 {
    color: #1a1a1a;
    font-weight: 700;
}

/* Tabs */
.tabs-wrapper {
    background: white;
    border-radius: 12px;
    padding: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.nav-pills-custom .nav-link {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    color: #6c757d;
    transition: all 0.3s ease;
    border: none;
    background: transparent;
}

.nav-pills-custom .nav-link:hover {
    background: #f8f9fa;
    color: #0d6efd;
}

.nav-pills-custom .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.nav-pills-custom .nav-link .badge {
    background: rgba(255, 255, 255, 0.2) !important;
    color: inherit !important;
}

.nav-pills-custom .nav-link.active .badge {
    background: rgba(255, 255, 255, 0.3) !important;
    color: white !important;
}

/* Plan Cards */
.plan-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.plan-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.plan-card.plan-inactive {
    opacity: 0.7;
    border-color: #dee2e6;
}

.plan-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.25rem;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.plan-card-title-section {
    flex: 1;
}

.plan-card-title {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
}

.plan-card-header .badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
}

.plan-card-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.1);
    color: white;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.05);
}

.btn-edit:hover {
    background: rgba(13, 110, 253, 0.3);
}

.btn-delete:hover {
    background: rgba(220, 53, 69, 0.3);
}

.plan-card-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.plan-card-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 1.25rem;
    line-height: 1.5;
}

.plan-pricing {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 2px solid #f0f0f0;
}

.pricing-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.pricing-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.pricing-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a1a;
}

.plan-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-icon {
    font-size: 1.5rem;
    color: #667eea;
}

.info-item div {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.info-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.info-value {
    font-size: 1rem;
    font-weight: 700;
    color: #1a1a1a;
}

.plan-modules-preview {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid #f0f0f0;
}

.modules-preview-header {
    margin-bottom: 0.5rem;
}

.modules-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.module-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 20px;
    font-size: 0.8rem;
    color: #667eea;
    font-weight: 500;
}

.module-tag-more {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #6c757d;
}

/* Module Cards */
.module-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.module-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.module-card.module-inactive {
    opacity: 0.7;
    border-color: #dee2e6;
}

.module-card-header {
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

.module-card-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.module-card-title-section {
    flex: 1;
    min-width: 0;
}

.module-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 0.25rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.module-card-header .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

.module-card-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.module-card-actions .btn-icon {
    background: white;
    border-color: #dee2e6;
    color: #6c757d;
}

.module-card-actions .btn-icon:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.module-card-body {
    padding: 1.25rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.module-card-description {
    color: #495057;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 1rem;
    flex: 1;
}

.module-card-footer {
    padding-top: 1rem;
    border-top: 1px solid #f0f0f0;
}

/* Empty State */
.empty-state-card {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border-radius: 50%;
    color: #667eea;
    font-size: 2.5rem;
}

.empty-state-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.75rem;
}

.empty-state-text {
    color: #6c757d;
    margin-bottom: 1.5rem;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

/* Loading States */
#loadingPlans,
#loadingModules {
    background: white;
    border-radius: 12px;
    padding: 3rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Modal Improvements */
.modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px 16px 0 0;
    padding: 1.5rem;
}

.modal-header .modal-title {
    font-weight: 600;
    color: white;
}

.modal-header .btn-close {
    filter: invert(1);
    opacity: 0.8;
}

.modal-header .btn-close:hover {
    opacity: 1;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 1px solid #f0f0f0;
    padding: 1.25rem 2rem;
}

/* Form Improvements */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control,
.form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

#planModulesCheckboxes {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.form-check {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.form-check:hover {
    background: #f8f9fa;
    border-color: #667eea;
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header-card {
        padding: 1.5rem;
    }
    
    .plan-pricing {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .plan-info-grid {
        grid-template-columns: 1fr;
    }
    
    .module-card-header {
        flex-wrap: wrap;
    }
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.plan-card,
.module-card {
    animation: fadeIn 0.4s ease;
}

/* Badge improvements */
.badge {
    font-weight: 600;
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
}
</style>

