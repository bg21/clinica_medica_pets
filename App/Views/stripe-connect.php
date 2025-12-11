<?php
/**
 * View - Conectar Stripe Connect
 * 
 * ✅ CORREÇÃO: Não inclui base.php manualmente
 * O View::render() com useLayout=true já faz isso automaticamente
 */
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-link-45deg"></i> Conectar Stripe</h2>
            </div>

            <div id="alertContainer"></div>

            <div class="card">
                <div class="card-body">
                    <div id="loadingAccount" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>

                    <div id="accountInfo" style="display: none;">
                        <div class="alert alert-success">
                            <h5><i class="bi bi-check-circle-fill"></i> Conta Stripe Conectada</h5>
                            <p class="mb-0">Sua conta Stripe está conectada e pronta para receber pagamentos.</p>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <span id="accountStatus" class="badge bg-success">Ativa</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong>
                                <span id="accountEmail">-</span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="https://dashboard.stripe.com/connect/accounts/overview" target="_blank" class="btn btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right"></i> Abrir Dashboard Stripe
                            </a>
                        </div>
                    </div>

                    <div id="noAccount" style="display: none;">
                        <!-- Opção 1: Informar API Key diretamente -->
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-key"></i> Informar API Key do Stripe</h5>
                            <div class="alert alert-warning">
                                <small><strong>Como obter sua API Key:</strong></small>
                                <ul class="mb-0 mt-2">
                                    <li>Acesse <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard → API Keys</a></li>
                                    <li>Copie sua <strong>Secret Key</strong> (começa com <code>sk_test_</code> ou <code>sk_live_</code>)</li>
                                    <li>Cole abaixo e salve</li>
                                </ul>
                            </div>
                            
                            <form id="apiKeyForm" class="mb-4">
                                <div class="mb-3">
                                    <label for="stripeSecretKey" class="form-label">API Key Secreta do Stripe</label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="stripeSecretKey" 
                                        placeholder="sk_test_xxx ou sk_live_xxx"
                                        required
                                        pattern="^sk_(test|live)_[a-zA-Z0-9]+$"
                                    >
                                    <div class="form-text">
                                        Sua API key será criptografada e armazenada com segurança.
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Salvar API Key
                                </button>
                            </form>
                        </div>

                        <hr class="my-4">

                        <!-- Opção 2: Stripe Connect Express (alternativa) -->
                        <div>
                            <h5 class="mb-3"><i class="bi bi-link-45deg"></i> Ou use Stripe Connect Express</h5>
                            <div class="alert alert-info">
                                <p class="mb-0">Crie uma conta Stripe Connect Express através do nosso sistema.</p>
                            </div>
                            <div class="d-grid gap-2">
                                <button id="connectStripeBtn" class="btn btn-outline-primary">
                                    <i class="bi bi-link-45deg"></i> Conectar via Stripe Connect Express
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// API_URL já está definido no base.php, não precisa redeclarar
// Usa diretamente a variável global já definida

document.addEventListener('DOMContentLoaded', function() {
    loadAccountStatus();
    
    // Formulário de API Key
    const apiKeyForm = document.getElementById('apiKeyForm');
    if (apiKeyForm) {
        apiKeyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await saveApiKey();
        });
    }
    
    // Botão Stripe Connect Express
    const connectBtn = document.getElementById('connectStripeBtn');
    if (connectBtn) {
        connectBtn.addEventListener('click', function() {
            connectStripe();
        });
    }
});

async function loadAccountStatus() {
    try {
        const response = await fetch(API_URL + '/v1/stripe-connect/account', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('session_id')
            }
        });

        if (!response.ok) {
            // Se for 401, pode ser problema de autenticação
            if (response.status === 401) {
                throw new Error('Sessão expirada. Por favor, faça login novamente.');
            }
            throw new Error('Erro ao carregar conta');
        }

        const result = await response.json();
        const account = result.data;

        // ✅ CORREÇÃO: Trata quando account é null (não tem conta ainda)
        if (!account || account === null) {
            document.getElementById('loadingAccount').style.display = 'none';
            document.getElementById('noAccount').style.display = 'block';
            return;
        }

        // Tem conta - mostra informações
        document.getElementById('loadingAccount').style.display = 'none';
        document.getElementById('accountInfo').style.display = 'block';
        
        document.getElementById('accountEmail').textContent = account.email || '-';
        
        if (account.charges_enabled && account.onboarding_completed) {
            document.getElementById('accountStatus').textContent = 'Ativa';
            document.getElementById('accountStatus').className = 'badge bg-success';
        } else {
            document.getElementById('accountStatus').textContent = 'Pendente';
            document.getElementById('accountStatus').className = 'badge bg-warning';
        }
    } catch (error) {
        console.error('Erro ao carregar status da conta:', error);
        showAlert('Erro: ' + error.message, 'danger');
        document.getElementById('loadingAccount').style.display = 'none';
        document.getElementById('noAccount').style.display = 'block';
    }
}

async function connectStripe() {
    try {
        const returnUrl = window.location.origin + '/stripe-connect/success';
        
        const response = await fetch(API_URL + '/v1/stripe-connect/onboarding', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('session_id')
            },
            body: JSON.stringify({
                return_url: returnUrl
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Erro ao criar link de onboarding');
        }

        const result = await response.json();
        
        // Redireciona para Stripe
        window.location.href = result.data.onboarding_url;
    } catch (error) {
        showAlert('Erro: ' + error.message, 'danger');
    }
}

async function saveApiKey() {
    const apiKeyInput = document.getElementById('stripeSecretKey');
    const apiKey = apiKeyInput.value.trim();
    
    if (!apiKey) {
        showAlert('Por favor, informe sua API key do Stripe', 'warning');
        return;
    }

    // Valida formato básico
    if (!apiKey.match(/^sk_(test|live)_[a-zA-Z0-9]+$/)) {
        showAlert('API key inválida. Deve começar com "sk_test_" ou "sk_live_"', 'danger');
        return;
    }

    try {
        const submitBtn = document.querySelector('#apiKeyForm button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

        const response = await fetch(API_URL + '/v1/stripe-connect/api-key', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('session_id')
            },
            body: JSON.stringify({
                stripe_secret_key: apiKey
            })
        });

        const result = await response.json();

        if (!response.ok) {
            // Mostra mensagem de erro mais detalhada
            let errorMessage = result.message || 'Erro ao salvar API key';
            
            if (result.errors && typeof result.errors === 'object') {
                const errorList = Object.values(result.errors).join(', ');
                if (errorList) {
                    errorMessage += ': ' + errorList;
                }
            }
            
            if (result.code) {
                errorMessage += ' (Código: ' + result.code + ')';
            }
            
            console.error('Erro ao salvar API key:', {
                status: response.status,
                statusText: response.statusText,
                result: result
            });
            
            throw new Error(errorMessage);
        }

        showAlert('API key salva com sucesso!', 'success');
        apiKeyInput.value = ''; // Limpa o campo por segurança
        
        // Recarrega status da conta
        setTimeout(() => {
            loadAccountStatus();
        }, 1000);

    } catch (error) {
        showAlert('Erro: ' + error.message, 'danger');
    } finally {
        const submitBtn = document.querySelector('#apiKeyForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-save"></i> Salvar API Key';
        }
    }
}

function showAlert(message, type) {
    const container = document.getElementById('alertContainer');
    container.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}
</script>

