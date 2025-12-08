<?php
/**
 * View de Dashboard - Sistema de Saúde
 * Dashboard profissional com KPIs visuais, agendamentos e consultas
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-speedometer2 text-primary"></i>
                Dashboard
            </h1>
            <p class="text-muted mb-0">
                Bem-vindo, <span id="userName"><?php echo htmlspecialchars($user['name'] ?? 'Usuário', ENT_QUOTES, 'UTF-8'); ?></span>
            </p>
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

    <!-- Assinaturas (se aplicável) -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-credit-card me-2"></i>
                Assinaturas Ativas
            </h5>
            <button class="btn btn-primary btn-sm" onclick="loadSubscriptions()">
                <i class="bi bi-arrow-clockwise me-2"></i>
                Atualizar
            </button>
        </div>
        <div class="card-body">
            <div id="loadingState" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Carregando assinaturas...</p>
            </div>
            <div id="subscriptionsContainer"></div>
            <div id="emptyState" class="text-center py-5" style="display: none;">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <h5 class="mt-3">Nenhuma assinatura encontrada</h5>
                <p class="text-muted">Você ainda não possui assinaturas ativas.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Carrega dados imediatamente
    loadDashboardData();
    
    // Atualiza filtro de período
    const timeRangeFilter = document.getElementById('timeRangeFilter');
    if (timeRangeFilter) {
        timeRangeFilter.addEventListener('change', () => {
            loadDashboardData();
        });
    }
});

// Função para carregar dados do dashboard
async function loadDashboardData() {
    await Promise.all([
        loadSubscriptions()
    ]);
}

// Carrega assinaturas
async function loadSubscriptions() {
    const container = document.getElementById('subscriptionsContainer');
    const loading = document.getElementById('loadingState');
    const empty = document.getElementById('emptyState');
    
    container.innerHTML = '';
    loading.style.display = 'block';
    empty.style.display = 'none';
    
    try {
        const response = await apiRequest('/v1/subscriptions');
        loading.style.display = 'none';
        
        if (!response.data || response.data.length === 0) {
            empty.style.display = 'block';
            return;
        }
        
        // Renderiza assinaturas
        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Próximo Pagamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${response.data.map(sub => `
                            <tr>
                                <td><code>${sub.id}</code></td>
                                <td><span class="badge bg-${sub.status === 'active' ? 'success' : 'secondary'}">${sub.status}</span></td>
                                <td>${sub.customer_id || '-'}</td>
                                <td>${sub.amount ? formatCurrency(sub.amount, sub.currency || 'BRL') : '-'}</td>
                                <td>${sub.current_period_end ? formatDate(sub.current_period_end) : '-'}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewSubscription(${sub.id})">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    } catch (error) {
        loading.style.display = 'none';
        showAlert('Erro ao carregar assinaturas: ' + error.message, 'danger');
    }
}


// Função auxiliar para escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function viewSubscription(id) {
    window.location.href = `/subscriptions?view=${id}`;
}
</script>
