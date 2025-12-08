<?php
/**
 * View de Cupons
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-ticket-perforated text-primary"></i>
                Cupons
            </h1>
            <p class="text-muted mb-0">Gerencie cupons de desconto</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCouponModal">
            <i class="bi bi-plus-circle"></i> Novo Cupom
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
                            <p class="text-muted mb-1 small fw-medium">Total de Cupons</p>
                            <h2 class="mb-0 fw-bold" id="totalCouponsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-ticket-perforated fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Válidos</p>
                            <h2 class="mb-0 fw-bold" id="validCouponsStat">-</h2>
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
                            <p class="text-muted mb-1 small fw-medium">Percentuais</p>
                            <h2 class="mb-0 fw-bold" id="percentCouponsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-percent fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Valor Fixo</p>
                            <h2 class="mb-0 fw-bold" id="amountCouponsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-cash-stack fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Cupons -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Cupons
            </h5>
            <span class="badge bg-primary" id="couponsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingCoupons" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando cupons...</p>
            </div>
            <div id="couponsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Duração</th>
                                <th>Status</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="couponsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-ticket-perforated fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhum cupom encontrado</h5>
                    <p class="text-muted">Crie um novo cupom para começar.</p>
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createCouponModal">
                        <i class="bi bi-plus-circle"></i> Criar Primeiro Cupom
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Cupom -->
<div class="modal fade" id="createCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Cupom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createCouponForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID do Cupom *</label>
                        <input type="text" class="form-control" name="id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Desconto *</label>
                        <select class="form-select" name="discount_type" id="discountType" required>
                            <option value="percent">Percentual</option>
                            <option value="amount">Valor Fixo</option>
                        </select>
                    </div>
                    <div class="mb-3" id="percentField">
                        <label class="form-label">Percentual de Desconto *</label>
                        <input type="number" class="form-control" name="percent_off" min="1" max="100">
                    </div>
                    <div class="mb-3" id="amountField" style="display: none;">
                        <label class="form-label">Valor do Desconto (em centavos) *</label>
                        <input type="number" class="form-control" name="amount_off" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duração *</label>
                        <select class="form-select" name="duration" required>
                            <option value="once">Uma vez</option>
                            <option value="repeating">Repetir</option>
                            <option value="forever">Para sempre</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Cupom</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadCoupons();
    }, 100);
    
    document.getElementById('discountType').addEventListener('change', (e) => {
        if (e.target.value === 'percent') {
            document.getElementById('percentField').style.display = 'block';
            document.getElementById('amountField').style.display = 'none';
        } else {
            document.getElementById('percentField').style.display = 'none';
            document.getElementById('amountField').style.display = 'block';
        }
    });
    
    document.getElementById('createCouponForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            id: formData.get('id'),
            duration: formData.get('duration')
        };
        
        if (formData.get('discount_type') === 'percent') {
            data.percent_off = parseInt(formData.get('percent_off'));
        } else {
            data.amount_off = parseInt(formData.get('amount_off'));
        }
        
        try {
            await apiRequest('/v1/coupons', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            showAlert('Cupom criado com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createCouponModal')).hide();
            e.target.reset();
            loadCoupons();
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });
});

async function loadCoupons() {
    try {
        document.getElementById('loadingCoupons').style.display = 'block';
        document.getElementById('couponsList').style.display = 'none';
        
        const response = await apiRequest('/v1/coupons');
        const coupons = response.data || [];
        
        renderCoupons(coupons);
    } catch (error) {
        showAlert('Erro ao carregar cupons: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingCoupons').style.display = 'none';
        document.getElementById('couponsList').style.display = 'block';
    }
}

let coupons = [];

function renderCoupons(couponsData) {
    const tbody = document.getElementById('couponsTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('couponsCountBadge');
    
    coupons = couponsData || [];
    
    if (coupons.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        
        // Atualiza estatísticas
        const stats = calculateCouponStats();
        updateCouponStats(stats);
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(coupons.length);
    }
    
    // Calcula estatísticas
    const stats = calculateCouponStats();
    updateCouponStats(stats);
    
    tbody.innerHTML = coupons.map(coupon => {
        const durationText = {
            'once': 'Uma vez',
            'repeating': 'Repetir',
            'forever': 'Para sempre'
        }[coupon.duration] || coupon.duration || '-';
        
        return `
        <tr>
            <td>
                <code class="text-muted small">${escapeHtml(coupon.id)}</code>
            </td>
            <td>
                <div class="fw-medium">${escapeHtml(coupon.name || coupon.id || '-')}</div>
            </td>
            <td>
                <span class="badge ${coupon.percent_off ? 'bg-info' : 'bg-secondary'}">
                    ${coupon.percent_off ? 'Percentual' : 'Valor Fixo'}
                </span>
            </td>
            <td>
                <div class="fw-bold text-primary">
                    ${coupon.percent_off ? `${coupon.percent_off}%` : formatCurrency(coupon.amount_off, coupon.currency || 'BRL')}
                </div>
            </td>
            <td>
                <small>${durationText}</small>
            </td>
            <td>
                <span class="badge ${coupon.valid !== false ? 'bg-success' : 'bg-secondary'}">
                    ${coupon.valid !== false ? 'Válido' : 'Inválido'}
                </span>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewCoupon('${coupon.id}')" title="Ver detalhes">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCoupon('${coupon.id}')" title="Excluir">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    }).join('');
}

function calculateCouponStats() {
    const total = coupons.length;
    const valid = coupons.filter(c => c.valid !== false).length;
    const percent = coupons.filter(c => c.percent_off).length;
    const amount = coupons.filter(c => c.amount_off).length;
    
    return { total, valid, percent, amount };
}

function updateCouponStats(stats) {
    document.getElementById('totalCouponsStat').textContent = formatNumber(stats.total);
    document.getElementById('validCouponsStat').textContent = formatNumber(stats.valid);
    document.getElementById('percentCouponsStat').textContent = formatNumber(stats.percent);
    document.getElementById('amountCouponsStat').textContent = formatNumber(stats.amount);
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

function viewCoupon(id) {
    // ✅ Validação básica: ID não pode estar vazio
    if (!id || id.trim() === '') {
        showAlert('ID do cupom inválido', 'danger');
        return;
    }
    
    // ✅ CORREÇÃO: Redireciona para página de detalhes ao invés de alert
    window.location.href = `/coupon-details?id=${encodeURIComponent(id)}`;
}

async function deleteCoupon(id) {
    // ✅ Validação básica: ID não pode estar vazio
    if (!id || id.trim() === '') {
        showAlert('ID do cupom inválido', 'danger');
        return;
    }
    
    const confirmed = await showConfirmModal(
        'Tem certeza que deseja remover este cupom? Esta ação não pode ser desfeita.',
        'Confirmar Exclusão',
        'Remover Cupom'
    );
    if (!confirmed) return;
    
    try {
        await apiRequest(`/v1/coupons/${encodeURIComponent(id)}`, { method: 'DELETE' });
        showAlert('Cupom removido com sucesso!', 'success');
        loadCoupons();
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}
</script>

