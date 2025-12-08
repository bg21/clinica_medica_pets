<?php
/**
 * View de Códigos Promocionais
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-tag text-primary"></i>
                Códigos Promocionais
            </h1>
            <p class="text-muted mb-0">Gerencie códigos promocionais</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPromoCodeModal">
            <i class="bi bi-plus-circle"></i> Novo Código
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Cards de Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Total de Códigos</p>
                            <h2 class="mb-0 fw-bold" id="totalPromoCodesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-tag fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Ativos</p>
                            <h2 class="mb-0 fw-bold" id="activePromoCodesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Total de Usos</p>
                            <h2 class="mb-0 fw-bold" id="totalUsagesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-arrow-repeat fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Com Limite</p>
                            <h2 class="mb-0 fw-bold" id="limitedPromoCodesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-lock fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Códigos Promocionais -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Códigos Promocionais
            </h5>
            <span class="badge bg-primary" id="promoCodesCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingPromoCodes" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando códigos promocionais...</p>
            </div>
            <div id="promoCodesList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Cupom</th>
                                <th>Status</th>
                                <th>Limite de Uso</th>
                                <th>Usos</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="promoCodesTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-tag fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum código promocional encontrado</h5>
                    <p class="text-muted">Crie um novo código promocional para começar.</p>
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createPromoCodeModal">
                        <i class="bi bi-plus-circle"></i> Criar Primeiro Código
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Código Promocional -->
<div class="modal fade" id="createPromoCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Código Promocional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createPromoCodeForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Código *</label>
                        <input type="text" class="form-control" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cupom (ID) *</label>
                        <input type="text" class="form-control" name="coupon" placeholder="cupom_id" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Código</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let coupons = [];

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadPromoCodes();
        loadCoupons();
    }, 100);
    
    document.getElementById('createPromoCodeForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            code: formData.get('code'),
            coupon: formData.get('coupon')
        };
        
        try {
            await apiRequest('/v1/promotion-codes', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            showAlert('Código promocional criado com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createPromoCodeModal')).hide();
            e.target.reset();
            loadPromoCodes();
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });
});

async function loadCoupons() {
    try {
        const response = await apiRequest('/v1/coupons');
        coupons = response.data || [];
    } catch (error) {
        console.error('Erro ao carregar cupons:', error);
    }
}

async function loadPromoCodes() {
    try {
        document.getElementById('loadingPromoCodes').style.display = 'block';
        document.getElementById('promoCodesList').style.display = 'none';
        
        const response = await apiRequest('/v1/promotion-codes');
        const promoCodes = response.data || [];
        
        renderPromoCodes(promoCodes);
    } catch (error) {
        showAlert('Erro ao carregar códigos promocionais: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingPromoCodes').style.display = 'none';
        document.getElementById('promoCodesList').style.display = 'block';
    }
}

let promoCodes = [];

function renderPromoCodes(promoCodesData) {
    const tbody = document.getElementById('promoCodesTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('promoCodesCountBadge');
    
    promoCodes = promoCodesData || [];
    
    if (promoCodes.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        
        // Atualiza estatísticas
        const stats = calculatePromoCodeStats();
        updatePromoCodeStats(stats);
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(promoCodes.length);
    }
    
    // Calcula estatísticas
    const stats = calculatePromoCodeStats();
    updatePromoCodeStats(stats);
    
    tbody.innerHTML = promoCodes.map(promo => {
        const usagePercent = promo.max_redemptions 
            ? Math.round((promo.times_redeemed || 0) / promo.max_redemptions * 100)
            : 0;
        
        return `
        <tr>
            <td>
                <code class="text-primary fw-bold">${escapeHtml(promo.code)}</code>
            </td>
            <td>
                <code class="text-muted small">${escapeHtml(promo.coupon?.id || promo.coupon || '-')}</code>
            </td>
            <td>
                <span class="badge ${promo.active !== false ? 'bg-success' : 'bg-secondary'}">
                    ${promo.active !== false ? 'Ativo' : 'Inativo'}
                </span>
            </td>
            <td>
                <div>
                    ${promo.max_redemptions ? formatNumber(promo.max_redemptions) : '<span class="text-muted">Ilimitado</span>'}
                </div>
            </td>
            <td>
                <div>
                    <span class="fw-bold">${promo.times_redeemed || 0}</span>
                    ${promo.max_redemptions ? `<small class="text-muted">(${usagePercent}%)</small>` : ''}
                </div>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewPromoCode('${promo.id}')" title="Ver detalhes">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    }).join('');
}

function calculatePromoCodeStats() {
    const total = promoCodes.length;
    const active = promoCodes.filter(p => p.active !== false).length;
    const totalUsages = promoCodes.reduce((sum, p) => sum + (p.times_redeemed || 0), 0);
    const limited = promoCodes.filter(p => p.max_redemptions).length;
    
    return { total, active, totalUsages, limited };
}

function updatePromoCodeStats(stats) {
    document.getElementById('totalPromoCodesStat').textContent = formatNumber(stats.total);
    document.getElementById('activePromoCodesStat').textContent = formatNumber(stats.active);
    document.getElementById('totalUsagesStat').textContent = formatNumber(stats.totalUsages);
    document.getElementById('limitedPromoCodesStat').textContent = formatNumber(stats.limited);
}

function viewPromoCode(id) {
    window.location.href = `/promo-code-details?id=${id}`;
}

// Função auxiliar para escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num);
}
</script>

