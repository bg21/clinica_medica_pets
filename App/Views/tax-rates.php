<?php
/**
 * View de Taxas de Imposto
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-percent text-primary"></i>
                Taxas de Imposto
            </h1>
            <p class="text-muted mb-0">Gerencie taxas e impostos aplicáveis</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaxRateModal">
            <i class="bi bi-plus-circle"></i> Nova Taxa
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
                            <p class="text-muted mb-1 small fw-medium">Total de Taxas</p>
                            <h2 class="mb-0 fw-bold" id="totalTaxRatesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-percent fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Ativas</p>
                            <h2 class="mb-0 fw-bold" id="activeTaxRatesStat">-</h2>
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
                            <p class="text-muted mb-1 small fw-medium">Inclusivas</p>
                            <h2 class="mb-0 fw-bold" id="inclusiveTaxRatesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-arrow-down-up fs-1"></i>
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
                            <p class="text-muted mb-1 small fw-medium">Taxa Média</p>
                            <h2 class="mb-0 fw-bold" id="averageTaxRateStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-graph-up fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Taxas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Taxas de Imposto
            </h5>
            <span class="badge bg-primary" id="taxRatesCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingTaxRates" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando taxas...</p>
            </div>
            <div id="taxRatesList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Porcentagem</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Jurisdição</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="taxRatesTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-percent fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma taxa encontrada</h5>
                    <p class="text-muted">Crie uma nova taxa de imposto para começar.</p>
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createTaxRateModal">
                        <i class="bi bi-plus-circle"></i> Criar Primeira Taxa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Taxa -->
<div class="modal fade" id="createTaxRateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Taxa de Imposto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createTaxRateForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome de Exibição *</label>
                        <input type="text" class="form-control" name="display_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Porcentagem *</label>
                        <input type="number" class="form-control" name="percentage" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="inclusive">
                            <option value="0">Exclusiva (adiciona ao preço)</option>
                            <option value="1">Inclusiva (já incluída no preço)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jurisdição</label>
                        <input type="text" class="form-control" name="jurisdiction" placeholder="BR, US, etc.">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Taxa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        loadTaxRates();
    }, 100);
    
    document.getElementById('createTaxRateForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            display_name: formData.get('display_name'),
            percentage: parseFloat(formData.get('percentage')),
            inclusive: formData.get('inclusive') === '1',
            jurisdiction: formData.get('jurisdiction') || null
        };
        
        try {
            await apiRequest('/v1/tax-rates', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            showAlert('Taxa criada com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createTaxRateModal')).hide();
            e.target.reset();
            loadTaxRates();
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });
});

async function loadTaxRates() {
    try {
        document.getElementById('loadingTaxRates').style.display = 'block';
        document.getElementById('taxRatesList').style.display = 'none';
        
        const response = await apiRequest('/v1/tax-rates');
        const taxRates = response.data || [];
        
        renderTaxRates(taxRates);
    } catch (error) {
        showAlert('Erro ao carregar taxas: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingTaxRates').style.display = 'none';
        document.getElementById('taxRatesList').style.display = 'block';
    }
}

let taxRates = [];

function renderTaxRates(taxRatesData) {
    const tbody = document.getElementById('taxRatesTableBody');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('taxRatesCountBadge');
    
    taxRates = taxRatesData || [];
    
    if (taxRates.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        if (countBadge) countBadge.textContent = '0';
        
        // Atualiza estatísticas
        const stats = calculateTaxRateStats();
        updateTaxRateStats(stats);
        return;
    }
    
    emptyState.style.display = 'none';
    if (countBadge) {
        countBadge.textContent = formatNumber(taxRates.length);
    }
    
    // Calcula estatísticas
    const stats = calculateTaxRateStats();
    updateTaxRateStats(stats);
    
    tbody.innerHTML = taxRates.map(rate => `
        <tr>
            <td>
                <code class="text-muted small">${escapeHtml(rate.id)}</code>
            </td>
            <td>
                <div class="fw-medium">${escapeHtml(rate.display_name || '-')}</div>
            </td>
            <td>
                <strong class="text-primary">${rate.percentage || 0}%</strong>
            </td>
            <td>
                <span class="badge ${rate.inclusive ? 'bg-info' : 'bg-secondary'}">
                    ${rate.inclusive ? 'Inclusiva' : 'Exclusiva'}
                </span>
            </td>
            <td>
                <span class="badge ${rate.active ? 'bg-success' : 'bg-secondary'}">
                    ${rate.active ? 'Ativa' : 'Inativa'}
                </span>
            </td>
            <td>
                <div>${escapeHtml(rate.jurisdiction || '-')}</div>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewTaxRate('${rate.id}')" title="Ver detalhes">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function calculateTaxRateStats() {
    const total = taxRates.length;
    const active = taxRates.filter(r => r.active).length;
    const inclusive = taxRates.filter(r => r.inclusive).length;
    const average = taxRates.length > 0
        ? (taxRates.reduce((sum, r) => sum + (r.percentage || 0), 0) / taxRates.length).toFixed(2)
        : 0;
    
    return { total, active, inclusive, average };
}

function updateTaxRateStats(stats) {
    document.getElementById('totalTaxRatesStat').textContent = formatNumber(stats.total);
    document.getElementById('activeTaxRatesStat').textContent = formatNumber(stats.active);
    document.getElementById('inclusiveTaxRatesStat').textContent = formatNumber(stats.inclusive);
    document.getElementById('averageTaxRateStat').textContent = stats.average + '%';
}

function viewTaxRate(id) {
    window.location.href = `/tax-rate-details?id=${id}`;
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

