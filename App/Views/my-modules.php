<?php
/**
 * View - Meus Módulos
 * Página onde o usuário visualiza os módulos disponíveis no seu plano atual
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-puzzle text-primary"></i>
                Meus Módulos
            </h1>
            <p class="text-muted mb-0">Módulos disponíveis no seu plano atual</p>
        </div>
        <a href="/my-subscription" class="btn btn-outline-primary">
            <i class="bi bi-credit-card me-2"></i>
            Ver Assinatura
        </a>
    </div>

    <div id="alertContainer"></div>

    <!-- Loading -->
    <div id="loadingModules" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Carregando módulos...</p>
    </div>

    <!-- Conteúdo Principal -->
    <div id="modulesContent" style="display: none;">
        <!-- Informações do Plano -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Informações do Plano
                </h5>
            </div>
            <div class="card-body" id="planInfoCard">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <!-- Módulos Disponíveis -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-puzzle me-2"></i>
                    Módulos Disponíveis
                </h5>
            </div>
            <div class="card-body">
                <div id="modulesContainer" class="row g-3">
                    <!-- Será preenchido via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadMyModules();
});

async function loadMyModules() {
    const loadingEl = document.getElementById('loadingModules');
    const contentEl = document.getElementById('modulesContent');
    
    loadingEl.style.display = 'block';
    contentEl.style.display = 'none';

    try {
        // Carrega limites do plano (inclui módulos)
        const limitsResponse = await apiRequest('/v1/plan-limits');
        
        if (!limitsResponse.success) {
            throw new Error(limitsResponse.message || 'Erro ao carregar módulos');
        }

        const limits = limitsResponse.data;
        
        // Renderiza informações do plano
        renderPlanInfo(limits);
        
        // Renderiza módulos disponíveis
        if (limits.has_subscription && limits.limits && limits.limits.modules) {
            renderModules(limits.limits.modules);
        } else {
            document.getElementById('modulesContainer').innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Nenhuma assinatura ativa encontrada. <a href="/my-subscription">Assine um plano</a> para ter acesso aos módulos.
                    </div>
                </div>
            `;
        }

        loadingEl.style.display = 'none';
        contentEl.style.display = 'block';
    } catch (error) {
        console.error('Erro ao carregar módulos:', error);
        showAlert('Erro ao carregar módulos: ' + error.message, 'danger');
        loadingEl.style.display = 'none';
    }
}

function renderPlanInfo(limits) {
    const container = document.getElementById('planInfoCard');
    
    if (!limits.has_subscription) {
        container.innerHTML = `
            <div class="alert alert-warning mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Você não possui uma assinatura ativa. <a href="/my-subscription">Assine um plano</a> para ter acesso aos módulos.
            </div>
        `;
        return;
    }

    const subscription = limits.subscription;
    const planLimits = limits.limits;
    const userLimits = limits.users;

    container.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Plano Atual</h6>
                <p class="h5 mb-0">${subscription.plan_name || 'Plano'}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Status</h6>
                <span class="badge bg-${subscription.status === 'active' ? 'success' : 'warning'}">
                    ${subscription.status === 'active' ? 'Ativo' : subscription.status}
                </span>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Usuários</h6>
                <p class="mb-0">
                    ${userLimits.current || 0} / ${userLimits.limit === null ? 'Ilimitado' : userLimits.limit}
                    ${userLimits.percentage > 0 ? `<span class="text-muted">(${userLimits.percentage}%)</span>` : ''}
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Módulos Disponíveis</h6>
                <p class="h5 mb-0">${Object.keys(planLimits.modules || {}).length}</p>
            </div>
        </div>
    `;
}

function renderModules(modules) {
    const container = document.getElementById('modulesContainer');
    
    if (!modules || Object.keys(modules).length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Nenhum módulo disponível no seu plano atual.
                </div>
            </div>
        `;
        return;
    }

    const modulesArray = Object.values(modules);
    
    container.innerHTML = modulesArray.map(module => `
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi ${module.icon || 'bi-puzzle'} text-success fs-4 me-3"></i>
                        <h6 class="mb-0">${module.name}</h6>
                    </div>
                    <p class="text-muted small mb-0">${module.description || ''}</p>
                </div>
                <div class="card-footer bg-transparent border-top-0">
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Disponível
                    </span>
                </div>
            </div>
        </div>
    `).join('');
}
</script>

