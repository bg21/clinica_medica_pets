<?php
/**
 * View de Comissões
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-cash-coin text-primary"></i>
                Comissões
            </h1>
            <p class="text-muted mb-0">Gerencie as comissões dos funcionários</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#configCommissionModal">
                <i class="bi bi-gear"></i> Configurar Comissão
            </button>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total de Comissões</h6>
                    <h3 class="mb-0" id="statsTotalCount">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Valor Total</h6>
                    <h3 class="mb-0 text-primary" id="statsTotalAmount">R$ 0,00</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Valor Pago</h6>
                    <h3 class="mb-0 text-success" id="statsPaidAmount">R$ 0,00</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Valor Pendente</h6>
                    <h3 class="mb-0 text-warning" id="statsPendingAmount">R$ 0,00</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="pending">Pendente</option>
                        <option value="paid">Paga</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Funcionário</label>
                    <select class="form-select" id="userFilter">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="dateFromFilter">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadCommissions()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Comissões -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Lista de Comissões
            </h5>
            <span class="badge bg-primary" id="commissionsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div id="loadingCommissions" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando comissões...</p>
            </div>
            <div id="commissionsList" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Orçamento</th>
                                <th>Funcionário</th>
                                <th>Valor do Orçamento</th>
                                <th>Porcentagem</th>
                                <th>Valor da Comissão</th>
                                <th>Status</th>
                                <th>Data de Pagamento</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="commissionsTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="bi bi-cash-coin fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Nenhuma comissão encontrada</h5>
                    <p class="text-muted">As comissões são criadas automaticamente quando um orçamento é convertido.</p>
                </div>
                <div id="paginationContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Configurar Comissão -->
<div class="modal fade" id="configCommissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-gear me-2"></i>
                    Configurar Comissão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="configCommissionForm" novalidate>
                <div class="modal-body">
                    <div id="configAlertContainer"></div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Defina a porcentagem de comissão que será aplicada quando um orçamento for convertido (fechado).
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-percent me-1"></i>
                            Porcentagem de Comissão <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="commission_percentage" id="commissionPercentage" required min="0" max="100" step="0.01" placeholder="Ex: 5.00 para 5%">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="form-text text-muted">
                            Exemplo: 5.00 = 5% de comissão sobre o valor do orçamento
                        </small>
                        <div class="invalid-feedback">
                            Por favor, insira uma porcentagem válida (0 a 100).
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="commissionIsActive" checked>
                            <label class="form-check-label" for="commissionIsActive">
                                Comissão ativa
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Se desativado, nenhuma comissão será criada ao converter orçamentos.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitConfigBtn">
                        <i class="bi bi-check-circle me-1"></i> Salvar Configuração
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Marcar Comissão como Paga -->
<div class="modal fade" id="markPaidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle me-2"></i>
                    Marcar Comissão como Paga
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="markPaidForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="commission_id" id="markPaidCommissionId">
                    <div id="markPaidAlertContainer"></div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-receipt me-1"></i>
                            Referência do Pagamento
                        </label>
                        <input type="text" class="form-control" name="payment_reference" id="markPaidReference" placeholder="Ex: Comprovante #123, Transferência #456">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text me-1"></i>
                            Observações
                        </label>
                        <textarea class="form-control" name="notes" id="markPaidNotes" rows="3" placeholder="Observações sobre o pagamento..." maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" id="submitMarkPaidBtn">
                        <i class="bi bi-check-circle me-1"></i> Marcar como Paga
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    let currentPage = 1;
    let commissions = [];
    let users = [];
    let commissionConfig = null;
    
    // Função para mostrar alertas
    function showAlert(message, type = 'info', containerId = 'alertContainer') {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.innerHTML = '';
        container.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    // Função para formatar moeda
    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }
    
    // Função para formatar data
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }
    
    // Função para formatar data e hora
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('pt-BR');
    }
    
    // Função para escapar HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Carrega dados iniciais
    async function loadInitialData() {
        await Promise.all([
            loadUsers(),
            loadCommissionConfig()
        ]);
    }
    
    async function loadUsers() {
        try {
            const response = await apiRequest('/v1/users?limit=1000', { cacheTTL: 60000 });
            // UserController retorna { users: [...], count: ... } dentro de data
            users = response.data?.users || response.data?.data || response.data || [];
            
            // Garante que é um array
            if (!Array.isArray(users)) {
                users = [];
            }
            
            const userSelect = document.getElementById('userFilter');
            if (userSelect) {
                userSelect.innerHTML = '<option value="">Todos</option>';
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name || user.email;
                    userSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Erro ao carregar usuários:', error);
            users = []; // Garante que é array mesmo em caso de erro
        }
    }
    
    async function loadCommissionConfig() {
        try {
            const response = await apiRequest('/v1/clinic/commissions/config');
            commissionConfig = response.data || response;
            
            if (commissionConfig) {
                const percentageInput = document.getElementById('commissionPercentage');
                const activeInput = document.getElementById('commissionIsActive');
                
                if (percentageInput) {
                    percentageInput.value = commissionConfig.commission_percentage || 0;
                }
                if (activeInput) {
                    activeInput.checked = commissionConfig.is_active !== false;
                }
            }
        } catch (error) {
            console.error('Erro ao carregar configuração:', error);
            // Se não existe configuração, usa valores padrão
            commissionConfig = {
                commission_percentage: 0,
                is_active: false
            };
        }
    }
    
    // Carrega estatísticas
    async function loadStats() {
        try {
            const response = await apiRequest('/v1/clinic/commissions/stats');
            const stats = response.data || response;
            
            // Garante que os elementos existem antes de atualizar
            const totalCountEl = document.getElementById('statsTotalCount');
            const totalAmountEl = document.getElementById('statsTotalAmount');
            const paidAmountEl = document.getElementById('statsPaidAmount');
            const pendingAmountEl = document.getElementById('statsPendingAmount');
            
            if (totalCountEl) totalCountEl.textContent = stats.total_count || 0;
            if (totalAmountEl) totalAmountEl.textContent = formatCurrency(stats.total_amount || 0);
            if (paidAmountEl) paidAmountEl.textContent = formatCurrency(stats.paid_amount || 0);
            if (pendingAmountEl) pendingAmountEl.textContent = formatCurrency(stats.pending_amount || 0);
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
            // Define valores padrão em caso de erro
            const totalCountEl = document.getElementById('statsTotalCount');
            const totalAmountEl = document.getElementById('statsTotalAmount');
            const paidAmountEl = document.getElementById('statsPaidAmount');
            const pendingAmountEl = document.getElementById('statsPendingAmount');
            
            if (totalCountEl) totalCountEl.textContent = '0';
            if (totalAmountEl) totalAmountEl.textContent = 'R$ 0,00';
            if (paidAmountEl) paidAmountEl.textContent = 'R$ 0,00';
            if (pendingAmountEl) pendingAmountEl.textContent = 'R$ 0,00';
        }
    }
    
    // Carrega comissões
    async function loadCommissions(page = 1) {
        const loadingDiv = document.getElementById('loadingCommissions');
        const listDiv = document.getElementById('commissionsList');
        const emptyState = document.getElementById('emptyState');
        const tableBody = document.getElementById('commissionsTableBody');
        const countBadge = document.getElementById('commissionsCountBadge');
        
        if (!listDiv) return;
        
        loadingDiv.style.display = 'block';
        listDiv.style.display = 'none';
        emptyState.style.display = 'none';
        tableBody.innerHTML = '';
        
        try {
            const statusFilter = document.getElementById('statusFilter')?.value || '';
            const userFilter = document.getElementById('userFilter')?.value || '';
            const dateFrom = document.getElementById('dateFromFilter')?.value || '';
            
            let url = `/v1/clinic/commissions?page=${page}&limit=20`;
            if (statusFilter) url += `&status=${statusFilter}`;
            if (userFilter) url += `&user_id=${userFilter}`;
            if (dateFrom) url += `&start_date=${dateFrom}`;
            
            const response = await apiRequest(url);
            
            // Garante que commissions é sempre um array
            // ResponseHelper retorna { success: true, data: { data: [...], total: ..., page: ... } }
            const result = response.data || {};
            const data = result.data || result || [];
            commissions = Array.isArray(data) ? data : [];
            const meta = {
                total: result.total || commissions.length || 0,
                page: result.page || 1,
                limit: result.limit || 20,
                total_pages: result.total_pages || 1
            };
            
            countBadge.textContent = meta.total;
            
            if (commissions.length === 0) {
                emptyState.style.display = 'block';
                listDiv.style.display = 'none';
            } else {
                commissions.forEach(commission => {
                    const row = tableBody.insertRow();
                    const statusBadges = {
                        'pending': '<span class="badge bg-warning">Pendente</span>',
                        'paid': '<span class="badge bg-success">Paga</span>',
                        'cancelled': '<span class="badge bg-danger">Cancelada</span>'
                    };
                    const statusBadge = statusBadges[commission.status] || '<span class="badge bg-secondary">' + commission.status + '</span>';
                    
                    const user = Array.isArray(users) ? users.find(u => u.id === commission.user_id) : null;
                    
                    row.innerHTML = `
                        <td><strong>#${commission.budget_id || '-'}</strong></td>
                        <td>${escapeHtml(user?.name || '-')}</td>
                        <td>${formatCurrency(commission.budget_total || 0)}</td>
                        <td>${commission.commission_percentage || 0}%</td>
                        <td><strong>${formatCurrency(commission.commission_amount || 0)}</strong></td>
                        <td>${statusBadge}</td>
                        <td>${commission.paid_at ? formatDateTime(commission.paid_at) : '-'}</td>
                        <td>
                            ${commission.status === 'pending' ? `
                                <button class="btn btn-sm btn-success" onclick="showMarkPaidModal(${commission.id})" data-bs-toggle="tooltip" title="Marcar como Paga">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                            ` : ''}
                        </td>
                    `;
                });
                listDiv.style.display = 'block';
                emptyState.style.display = 'none';
            }
            
            currentPage = page;
        } catch (error) {
            console.error('Erro ao carregar comissões:', error);
            showAlert('Erro ao carregar comissões. Tente novamente.', 'danger');
            emptyState.style.display = 'block';
        } finally {
            loadingDiv.style.display = 'none';
        }
    }
    
    // Form configurar comissão
    const configForm = document.getElementById('configCommissionForm');
    if (configForm) {
        configForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!configForm.checkValidity()) {
                configForm.classList.add('was-validated');
                return;
            }
            
            const submitBtn = document.getElementById('submitConfigBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(configForm);
                const data = {
                    commission_percentage: parseFloat(formData.get('commission_percentage')),
                    is_active: formData.get('is_active') === 'on'
                };
                
                await apiRequest('/v1/clinic/commissions/config', {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                showAlert('Configuração de comissão salva com sucesso!', 'success', 'configAlertContainer');
                await loadCommissionConfig();
                
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('configCommissionModal'));
                    if (modal) modal.hide();
                }, 1000);
            } catch (error) {
                showAlert(error.message || 'Erro ao salvar configuração. Tente novamente.', 'danger', 'configAlertContainer');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Salvar Configuração';
                }
            }
        });
    }
    
    // Mostra modal de marcar como paga
    window.showMarkPaidModal = function(commissionId) {
        document.getElementById('markPaidCommissionId').value = commissionId;
        document.getElementById('markPaidReference').value = '';
        document.getElementById('markPaidNotes').value = '';
        
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('markPaidModal'));
        modal.show();
    };
    
    // Form marcar como paga
    const markPaidForm = document.getElementById('markPaidForm');
    if (markPaidForm) {
        markPaidForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const commissionId = document.getElementById('markPaidCommissionId').value;
            const submitBtn = document.getElementById('submitMarkPaidBtn');
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }
            
            try {
                const formData = new FormData(markPaidForm);
                const data = {};
                
                if (formData.get('payment_reference')) {
                    data.payment_reference = formData.get('payment_reference');
                }
                if (formData.get('notes')) {
                    data.notes = formData.get('notes');
                }
                
                await apiRequest(`/v1/clinic/commissions/${commissionId}/mark-paid`, {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                showAlert('Comissão marcada como paga com sucesso!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('markPaidModal'));
                if (modal) modal.hide();
                markPaidForm.reset();
                loadCommissions();
                loadStats();
            } catch (error) {
                showAlert(error.message || 'Erro ao marcar comissão como paga. Tente novamente.', 'danger', 'markPaidAlertContainer');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Marcar como Paga';
                }
            }
        });
    }
    
    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        loadInitialData();
        loadStats();
        loadCommissions();
        
        // Event listeners para filtros
        document.getElementById('statusFilter')?.addEventListener('change', () => {
            loadCommissions(1);
            loadStats();
        });
        document.getElementById('userFilter')?.addEventListener('change', () => loadCommissions(1));
        document.getElementById('dateFromFilter')?.addEventListener('change', () => loadCommissions(1));
    });
})();
</script>

