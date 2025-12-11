<?php
/**
 * View - Minha Assinatura
 * Página onde o dono da clínica gerencia sua própria assinatura
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-credit-card text-primary"></i>
                Minha Assinatura
            </h1>
            <p class="text-muted mb-0">Gerencie seu plano e assinatura</p>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Loading -->
    <div id="loadingSubscription" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Carregando informações da assinatura...</p>
    </div>

    <!-- Conteúdo Principal -->
    <div id="subscriptionContent" style="display: none;">
        <!-- Assinatura Atual -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Assinatura Atual
                </h5>
            </div>
            <div class="card-body" id="currentSubscriptionCard">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <!-- Planos Disponíveis -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-grid me-2"></i>
                    Planos Disponíveis
                </h5>
                <button class="btn btn-sm btn-outline-primary" onclick="loadPlans()">
                    <i class="bi bi-arrow-clockwise"></i> Atualizar
                </button>
            </div>
            <div class="card-body">
                <div id="loadingPlans" class="text-center py-3" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
                <div id="plansContainer" class="row g-3">
                    <!-- Será preenchido via JavaScript -->
                </div>
            </div>
        </div>

        <!-- Faturas Recentes -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-receipt me-2"></i>
                    Faturas Recentes
                </h5>
            </div>
            <div class="card-body">
                <div id="loadingInvoices" class="text-center py-3" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
                <div id="invoicesContainer">
                    <!-- Será preenchido via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Upgrade/Downgrade -->
    <div class="modal fade" id="changePlanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Mudança de Plano</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="changePlanModalBody">
                    <!-- Será preenchido via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmChangePlanBtn" onclick="confirmChangePlan()">
                        Confirmar Mudança
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Cancelamento -->
    <div class="modal fade" id="cancelSubscriptionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Confirmar Cancelamento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja cancelar sua assinatura?</p>
                    <div class="alert alert-warning">
                        <strong>Atenção:</strong> Sua assinatura será cancelada no final do período atual. 
                        Você continuará tendo acesso até <span id="periodEndDate"></span>.
                    </div>
                    <p class="mb-0">Após o cancelamento, você perderá acesso aos recursos premium.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não, manter assinatura</button>
                    <button type="button" class="btn btn-danger" onclick="confirmCancelSubscription()">
                        <i class="bi bi-x-circle"></i> Sim, cancelar assinatura
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentSubscription = null;
let availablePlans = [];
let selectedPlanForChange = null;

document.addEventListener('DOMContentLoaded', () => {
    loadMySubscription();
    loadPlans();
    loadInvoices();
});

async function loadMySubscription() {
    try {
        document.getElementById('loadingSubscription').style.display = 'block';
        document.getElementById('subscriptionContent').style.display = 'none';

        // Busca assinatura ativa do tenant
        const response = await apiRequest('/v1/subscriptions/current');
        
        console.log('loadMySubscription - Resposta recebida:', response);
        
        if (response.success && response.data) {
            currentSubscription = response.data;
            console.log('loadMySubscription - Assinatura carregada:', {
                id: currentSubscription.id,
                stripe_subscription_id: currentSubscription.stripe_subscription_id,
                status: currentSubscription.status,
                plan_id: currentSubscription.plan_id,
                plan_name: currentSubscription.plan_name,
                amount: currentSubscription.amount,
                all_keys: Object.keys(currentSubscription)
            });
            renderCurrentSubscription();
        } else {
            // Não tem assinatura
            console.log('loadMySubscription - Nenhuma assinatura encontrada');
            currentSubscription = null;
            renderNoSubscription();
        }
    } catch (error) {
        console.error('Erro ao carregar assinatura:', error);
        showAlert('Erro ao carregar informações da assinatura: ' + error.message, 'danger');
        currentSubscription = null;
        renderNoSubscription();
    } finally {
        document.getElementById('loadingSubscription').style.display = 'none';
        document.getElementById('subscriptionContent').style.display = 'block';
    }
}

function renderCurrentSubscription() {
    const container = document.getElementById('currentSubscriptionCard');
    
    if (!currentSubscription) {
        renderNoSubscription();
        return;
    }

    const statusBadge = getStatusBadge(currentSubscription.status);
    // ✅ CORREÇÃO: amount já vem dividido por 100 do banco (não precisa dividir novamente)
    const amount = currentSubscription.amount ? 
        new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: (currentSubscription.currency || 'BRL').toUpperCase(),
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(currentSubscription.amount) : 
        'N/A';
    
    const periodStart = currentSubscription.current_period_start ? 
        formatDate(currentSubscription.current_period_start) : 'N/A';
    const periodEnd = currentSubscription.current_period_end ? 
        formatDate(currentSubscription.current_period_end) : 'N/A';

    container.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Plano Atual</h6>
                <h4 class="mb-2">${currentSubscription.plan_name || 'Plano'}</h4>
                <p class="text-muted mb-3">${amount} / ${getIntervalText(currentSubscription)}</p>
                ${statusBadge}
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Período Atual</h6>
                <p class="mb-1"><strong>Início:</strong> ${periodStart}</p>
                <p class="mb-1"><strong>Fim:</strong> ${periodEnd}</p>
                ${currentSubscription.cancel_at_period_end ? 
                    '<div class="alert alert-warning mt-3 mb-0"><i class="bi bi-exclamation-triangle"></i> Assinatura será cancelada no fim do período</div>' : 
                    ''
                }
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" onclick="openBillingPortal()">
                        <i class="bi bi-door-open"></i> Gerenciar no Portal Stripe
                    </button>
                    ${!currentSubscription.cancel_at_period_end ? 
                        `<button class="btn btn-outline-danger" onclick="openCancelModal()">
                            <i class="bi bi-x-circle"></i> Cancelar Assinatura
                        </button>` : 
                        `<button class="btn btn-success" onclick="reactivateSubscription()">
                            <i class="bi bi-arrow-clockwise"></i> Reativar Assinatura
                        </button>`
                    }
                </div>
            </div>
        </div>
    `;
}

function renderNoSubscription() {
    const container = document.getElementById('currentSubscriptionCard');
    container.innerHTML = `
        <div class="text-center py-4">
            <i class="bi bi-credit-card fs-1 text-muted mb-3"></i>
            <h5 class="mb-2">Nenhuma assinatura ativa</h5>
            <p class="text-muted mb-4">Você ainda não possui uma assinatura ativa. Escolha um plano abaixo para começar.</p>
        </div>
    `;
}

async function loadPlans() {
    try {
        document.getElementById('loadingPlans').style.display = 'block';
        
        console.log('Carregando planos de /v1/saas/plans...');
        const response = await apiRequest('/v1/saas/plans');
        
        console.log('Resposta recebida:', response);
        
        if (response.success && response.data) {
            console.log('Planos encontrados:', response.data.length);
            availablePlans = response.data;
            renderPlans();
        } else {
            console.warn('Resposta sem dados:', response);
            if (response.data && response.data.length === 0) {
                showAlert('Nenhum plano disponível no momento.', 'info');
            } else {
                showAlert('Erro ao carregar planos: resposta inválida', 'warning');
            }
        }
    } catch (error) {
        console.error('Erro ao carregar planos:', error);
        showAlert('Erro ao carregar planos: ' + error.message, 'danger');
    } finally {
        document.getElementById('loadingPlans').style.display = 'none';
    }
}

function renderPlans() {
    const container = document.getElementById('plansContainer');
    
    if (availablePlans.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted">Nenhum plano disponível no momento.</div>';
        return;
    }

    container.innerHTML = availablePlans.map((plan, index) => {
        // ✅ GARANTE que unit_amount é tratado como número inteiro (Stripe retorna em centavos)
        // Exemplo: 2900 centavos = R$ 29,00
        // IMPORTANTE: Se unit_amount vier como string "29", pode ser que já esteja em reais (erro)
        // Mas vamos assumir que sempre vem em centavos do Stripe
        let unitAmount = 0;
        if (plan.unit_amount !== null && plan.unit_amount !== undefined) {
            // Converte para número (pode ser string ou número)
            unitAmount = typeof plan.unit_amount === 'string' 
                ? parseInt(plan.unit_amount, 10) 
                : Math.floor(Number(plan.unit_amount));
            
            // ✅ VALIDAÇÃO: Se o valor for menor que 100, pode estar incorreto
            // Mas vamos dividir por 100 mesmo assim (pode ser um preço muito barato)
            // Se for menor que 1, assume que já está em reais (erro de conversão)
            if (unitAmount < 1) {
                console.warn('Aviso: unit_amount muito baixo, pode estar incorreto:', {
                    price_id: plan.id,
                    unit_amount: plan.unit_amount,
                    parsed: unitAmount
                });
            }
        }
        
        const amount = unitAmount / 100;
        
        console.log('Plano processado:', {
            id: plan.id,
            unit_amount_raw: plan.unit_amount,
            unit_amount_type: typeof plan.unit_amount,
            unit_amount_parsed: unitAmount,
            amount_final: amount,
            amount_type: typeof amount,
            product_name: plan.product?.name
        });
        
        const currency = plan.currency ? plan.currency.toUpperCase() : 'BRL';
        const interval = plan.recurring?.interval === 'month' ? 'mês' : 'ano';
        const isCurrentPlan = currentSubscription && 
            currentSubscription.plan_id === plan.id;
        const features = plan.metadata?.features ? 
            plan.metadata.features.split(',').map(f => f.trim()) : [];

        // ✅ GARANTE que amount é um número antes de formatar
        const amountToFormat = Number(amount);
        
        // ✅ CORREÇÃO: Usa formatação direta (não usa formatCurrency que pode estar sendo sobrescrita)
        // O dashboard.js tem uma formatCurrency que divide por 100, então vamos formatar diretamente
        const formattedAmount = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amountToFormat);
        
        // ✅ Log removido - problema resolvido (dashboard.js estava sobrescrevendo formatCurrency)

        return `
            <div class="col-md-4">
                <div class="card h-100 ${isCurrentPlan ? 'border-primary' : ''}">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${plan.product?.name || 'Plano'}</h5>
                        <p class="text-muted small">${plan.product?.description || ''}</p>
                        
                        <div class="my-3">
                            <h3 class="text-primary mb-0">${formattedAmount}</h3>
                            <small class="text-muted">/${interval}</small>
                        </div>

                        ${features.length > 0 ? `
                            <ul class="list-unstyled mb-3 flex-grow-1">
                                ${features.map(f => `
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        ${f}
                                    </li>
                                `).join('')}
                            </ul>
                        ` : ''}

                        <div class="mt-auto">
                            ${isCurrentPlan ? 
                                '<span class="badge bg-primary w-100 py-2">Plano Atual</span>' :
                                `<button class="btn btn-primary w-100" onclick="selectPlan('${plan.id}', '${plan.product?.name || 'Plano'}')">
                                    ${getPlanActionText(plan)}
                                </button>`
                            }
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function getPlanActionText(plan) {
    if (!currentSubscription) {
        return '<i class="bi bi-check-circle"></i> Assinar';
    }
    
    const currentAmount = currentSubscription.amount || 0;
    const newAmount = plan.unit_amount ? (plan.unit_amount / 100) : 0;
    
    if (newAmount > currentAmount) {
        return '<i class="bi bi-arrow-up"></i> Fazer Upgrade';
    } else if (newAmount < currentAmount) {
        return '<i class="bi bi-arrow-down"></i> Fazer Downgrade';
    } else {
        return '<i class="bi bi-arrow-repeat"></i> Trocar Plano';
    }
}

async function selectPlan(priceId, planName) {
    console.log('selectPlan chamado:', { priceId, planName, currentSubscription });
    
    if (!priceId) {
        console.error('priceId não fornecido');
        showAlert('ID do plano não encontrado', 'warning');
        return;
    }
    
    // ✅ Se não tem assinatura ativa, cria uma nova via checkout
    if (!currentSubscription) {
        console.log('Sem assinatura ativa - criando checkout para nova assinatura');
        await createCheckoutForPlan(priceId, planName);
        return;
    }
    
    // ✅ Se tem assinatura, permite mudança de plano
    selectedPlanForChange = { id: priceId, name: planName };
    
    const modalBody = document.getElementById('changePlanModalBody');
    if (!modalBody) {
        console.error('Modal body não encontrado');
        return;
    }
    
    modalBody.innerHTML = `
        <p>Você está prestes a alterar seu plano para <strong>${planName}</strong>.</p>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            A mudança será aplicada no final do período atual. Você continuará com acesso ao plano atual até então.
        </div>
    `;
    
    const modalElement = document.getElementById('changePlanModal');
    if (!modalElement) {
        console.error('Modal element não encontrado');
        return;
    }
    
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

async function createCheckoutForPlan(priceId, planName) {
    try {
        showAlert('Redirecionando para checkout...', 'info');
        
        const successUrl = `${window.location.origin}/subscription-success?session_id={CHECKOUT_SESSION_ID}`;
        const cancelUrl = `${window.location.origin}/my-subscription`;
        
        console.log('Criando checkout session:', { priceId, successUrl, cancelUrl });
        
        const response = await apiRequest('/v1/saas/checkout', {
            method: 'POST',
            body: JSON.stringify({
                price_id: priceId,
                success_url: successUrl,
                cancel_url: cancelUrl
            })
        });
        
        console.log('Checkout session criada:', response);
        console.log('Verificando resposta:', {
            success: response.success,
            hasData: !!response.data,
            dataType: typeof response.data,
            dataIsNull: response.data === null,
            dataIsUndefined: response.data === undefined,
            dataKeys: response.data ? Object.keys(response.data) : [],
            dataUrl: response.data?.url,
            dataCheckoutUrl: response.data?.checkout_url,
            fullResponse: JSON.stringify(response, null, 2)
        });
        
        // ✅ Verificação mais robusta: aceita response.success OU response.data
        if (response && (response.success === true || response.data)) {
            // ✅ CORREÇÃO: Backend retorna 'url', não 'checkout_url'
            const data = response.data || response;
            const checkoutUrl = data.url || data.checkout_url;
            
            console.log('URL de checkout extraída:', checkoutUrl);
            
            if (checkoutUrl && typeof checkoutUrl === 'string' && checkoutUrl.length > 0) {
                console.log('Redirecionando para checkout:', checkoutUrl);
                // Redireciona para o Stripe Checkout
                window.location.href = checkoutUrl;
                return; // Importante: evita continuar execução
            } else {
                console.error('URL de checkout não encontrada ou inválida:', {
                    response: response,
                    data: data,
                    url: data?.url,
                    checkout_url: data?.checkout_url,
                    checkoutUrl: checkoutUrl,
                    urlType: typeof checkoutUrl
                });
                showAlert('Erro: URL de checkout não encontrada. Tente novamente.', 'danger');
            }
        } else {
            console.error('Resposta de checkout inválida:', {
                success: response?.success,
                hasData: !!response?.data,
                response: response,
                responseType: typeof response
            });
            showAlert('Erro ao criar sessão de checkout. Tente novamente.', 'danger');
        }
    } catch (error) {
        console.error('Erro ao criar checkout:', error);
        showAlert('Erro ao criar sessão de checkout: ' + (error.message || 'Erro desconhecido'), 'danger');
    }
}

async function confirmChangePlan() {
    console.log('confirmChangePlan chamado:', {
        selectedPlanForChange: selectedPlanForChange,
        currentSubscription: currentSubscription,
        currentSubscriptionId: currentSubscription?.id
    });
    
    if (!selectedPlanForChange) {
        console.error('selectedPlanForChange não está definido');
        showAlert('Nenhum plano selecionado', 'warning');
        return;
    }
    
    if (!currentSubscription) {
        console.error('currentSubscription não está definido');
        showAlert('Assinatura atual não encontrada', 'warning');
        return;
    }
    
    if (!currentSubscription.id) {
        console.error('currentSubscription.id não está definido:', currentSubscription);
        showAlert('ID da assinatura não encontrado', 'warning');
        return;
    }

    try {
        showAlert('Agendando mudança de plano...', 'info');
        
        const url = `/v1/subscriptions/${currentSubscription.id}/schedule-plan-change`;
        const body = {
            new_price_id: selectedPlanForChange.id
        };
        
        console.log('Enviando requisição:', { url, body });
        
        const response = await apiRequest(url, {
            method: 'POST',
            body: JSON.stringify(body)
        });

        console.log('Resposta recebida:', response);

        if (response.success) {
            showAlert('Mudança de plano agendada com sucesso!', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('changePlanModal'));
            if (modal) {
                modal.hide();
            }
            loadMySubscription();
            loadPlans(); // Recarrega os planos também
        } else {
            console.error('Resposta não foi bem-sucedida:', response);
            showAlert(response.message || 'Erro ao agendar mudança de plano', 'danger');
        }
    } catch (error) {
        console.error('Erro ao agendar mudança de plano:', error);
        console.error('Detalhes do erro:', {
            message: error.message,
            stack: error.stack,
            response: error.response
        });
        showAlert('Erro ao agendar mudança de plano: ' + (error.message || 'Erro desconhecido'), 'danger');
    }
}

function openCancelModal() {
    if (!currentSubscription) return;
    
    const periodEnd = currentSubscription.current_period_end ? 
        formatDate(currentSubscription.current_period_end) : 'N/A';
    document.getElementById('periodEndDate').textContent = periodEnd;
    
    const modal = new bootstrap.Modal(document.getElementById('cancelSubscriptionModal'));
    modal.show();
}

async function confirmCancelSubscription() {
    if (!currentSubscription) return;

    try {
        showAlert('Cancelando assinatura...', 'info');
        
        const response = await apiRequest(`/v1/subscriptions/${currentSubscription.id}`, {
            method: 'DELETE'
        });

        if (response.success) {
            showAlert('Assinatura cancelada. Você terá acesso até o fim do período atual.', 'warning');
            bootstrap.Modal.getInstance(document.getElementById('cancelSubscriptionModal')).hide();
            loadMySubscription();
        }
    } catch (error) {
        console.error('Erro ao cancelar assinatura:', error);
        showAlert('Erro ao cancelar assinatura: ' + error.message, 'danger');
    }
}

async function reactivateSubscription() {
    if (!currentSubscription) return;

    try {
        showAlert('Reativando assinatura...', 'info');
        
        const response = await apiRequest(`/v1/subscriptions/${currentSubscription.id}/reactivate`, {
            method: 'POST'
        });

        if (response.success) {
            showAlert('Assinatura reativada com sucesso!', 'success');
            loadMySubscription();
        }
    } catch (error) {
        console.error('Erro ao reativar assinatura:', error);
        showAlert('Erro ao reativar assinatura: ' + error.message, 'danger');
    }
}

async function openBillingPortal() {
    try {
        // Busca customer do tenant
        const customersResponse = await apiRequest('/v1/customers?limit=1');
        
        if (!customersResponse.success || !customersResponse.data || customersResponse.data.length === 0) {
            showAlert('Cliente não encontrado. Entre em contato com o suporte.', 'warning');
            return;
        }

        const customer = customersResponse.data[0];
        
        // Cria sessão do billing portal
        const portalResponse = await apiRequest('/v1/billing-portal', {
            method: 'POST',
            body: JSON.stringify({
                customer_id: customer.id,
                return_url: window.location.href
            })
        });

        if (portalResponse.success && portalResponse.data?.url) {
            window.location.href = portalResponse.data.url;
        } else {
            showAlert('Erro ao abrir portal de cobrança.', 'danger');
        }
    } catch (error) {
        console.error('Erro ao abrir billing portal:', error);
        showAlert('Erro ao abrir portal de cobrança: ' + error.message, 'danger');
    }
}

async function loadInvoices() {
    try {
        document.getElementById('loadingInvoices').style.display = 'block';
        
        // Busca customer do tenant
        const customersResponse = await apiRequest('/v1/customers?limit=1');
        
        if (customersResponse.success && customersResponse.data && customersResponse.data.length > 0) {
            const customer = customersResponse.data[0];
            
            // Busca faturas do customer
            const invoicesResponse = await apiRequest(`/v1/customers/${customer.id}/invoices?limit=5`);
            
            if (invoicesResponse.success && invoicesResponse.data) {
                renderInvoices(invoicesResponse.data);
            }
        }
    } catch (error) {
        console.error('Erro ao carregar faturas:', error);
    } finally {
        document.getElementById('loadingInvoices').style.display = 'none';
    }
}

function renderInvoices(invoices) {
    const container = document.getElementById('invoicesContainer');
    
    if (!invoices || invoices.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">Nenhuma fatura encontrada.</p>';
        return;
    }

    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    ${invoices.map(invoice => `
                        <tr>
                            <td>${formatDate(invoice.created || invoice.date)}</td>
                            <td>${new Intl.NumberFormat('pt-BR', {
                                style: 'currency',
                                currency: (invoice.currency?.toUpperCase() || 'BRL'),
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }).format(invoice.amount_paid / 100)}</td>
                            <td>${getInvoiceStatusBadge(invoice.status)}</td>
                            <td>
                                ${invoice.hosted_invoice_url ? 
                                    `<a href="${invoice.hosted_invoice_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-receipt"></i> Ver Fatura
                                    </a>` : 
                                    ''
                                }
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Helper functions
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">Ativa</span>',
        'trialing': '<span class="badge bg-info">Período de Teste</span>',
        'past_due': '<span class="badge bg-warning">Pagamento Atrasado</span>',
        'canceled': '<span class="badge bg-danger">Cancelada</span>',
        'incomplete': '<span class="badge bg-secondary">Incompleta</span>'
    };
    return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
}

function getInvoiceStatusBadge(status) {
    const badges = {
        'paid': '<span class="badge bg-success">Paga</span>',
        'open': '<span class="badge bg-warning">Aberta</span>',
        'void': '<span class="badge bg-secondary">Anulada</span>',
        'uncollectible': '<span class="badge bg-danger">Inadimplente</span>'
    };
    return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
}

function getIntervalText(subscription) {
    // Tenta inferir do plan_id ou usa padrão
    return 'mês'; // Simplificado - pode melhorar buscando do Stripe
}

function formatCurrency(amount, currency = 'BRL') {
    // ✅ GARANTE que amount é um número
    let numAmount;
    if (typeof amount === 'string') {
        numAmount = parseFloat(amount.replace(',', '.')); // Suporta vírgula como separador decimal
    } else {
        numAmount = Number(amount);
    }
    
    // ✅ Validação: se não for um número válido, retorna erro
    if (isNaN(numAmount) || !isFinite(numAmount)) {
        console.error('Erro: amount não é um número válido:', amount, typeof amount);
        return 'R$ 0,00';
    }
    
    // ✅ LOG para debug
    console.log('formatCurrency chamado:', {
        amount_original: amount,
        amount_type_original: typeof amount,
        numAmount: numAmount,
        numAmount_type: typeof numAmount,
        currency: currency
    });
    
    // ✅ FORÇA formatação correta - garante que seja tratado como número
    // O problema pode ser que o Intl.NumberFormat está interpretando incorretamente
    // Vamos garantir que o valor seja um número puro
    try {
        // ✅ TESTE: Verifica se o problema está no Intl.NumberFormat
        const test29 = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(29);
        const test2900 = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(2900);
        
        console.log('TESTE Intl.NumberFormat:', {
            test_29: test29,
            test_2900: test2900,
            input_value: numAmount,
            input_type: typeof numAmount,
            isInteger: Number.isInteger(numAmount)
        });
        
        const formatted = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(numAmount);
        
        console.log('formatCurrency resultado:', {
            input: numAmount,
            input_type: typeof numAmount,
            output: formatted,
            test_29: test29,
            test_2900: test2900
        });
        
        // ✅ VALIDAÇÃO: Se o resultado estiver errado (contém "0," quando deveria ser maior), usa fallback
        if (formatted.includes('0,') && numAmount >= 1) {
            console.warn('AVISO: Formatação incorreta detectada, usando fallback:', {
                formatted: formatted,
                numAmount: numAmount
            });
            // Fallback: formatação manual
            return 'R$ ' + numAmount.toFixed(2).replace('.', ',');
        }
        
        return formatted;
    } catch (e) {
        console.error('Erro ao formatar moeda:', e, { amount, numAmount, currency });
        // Fallback: formatação manual
        return 'R$ ' + numAmount.toFixed(2).replace('.', ',');
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function showAlert(message, type) {
    const container = document.getElementById('alertContainer');
    container.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Auto-dismiss após 5 segundos
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        }
    }, 5000);
}
</script>

