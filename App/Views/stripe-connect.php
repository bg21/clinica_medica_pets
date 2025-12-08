<?php
/**
 * View - Conectar Stripe Connect
 */
?>
<?php include __DIR__ . '/layouts/base.php'; ?>

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
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle"></i> Conecte sua Conta Stripe</h5>
                            <p>Para receber pagamentos dos seus clientes, você precisa conectar sua conta Stripe.</p>
                            <ul>
                                <li>Receba pagamentos diretamente na sua conta</li>
                                <li>Controle total sobre seus recebimentos</li>
                                <li>Processamento seguro e rápido</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2">
                            <button id="connectStripeBtn" class="btn btn-primary btn-lg">
                                <i class="bi bi-link-45deg"></i> Conectar Conta Stripe
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const API_URL = '<?= $apiUrl ?? "http://localhost:8080" ?>';

document.addEventListener('DOMContentLoaded', function() {
    loadAccountStatus();
    
    document.getElementById('connectStripeBtn').addEventListener('click', function() {
        connectStripe();
    });
});

async function loadAccountStatus() {
    try {
        const response = await fetch(API_URL + '/v1/stripe-connect/account', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('session_id')
            }
        });

        if (response.status === 404) {
            // Não tem conta ainda
            document.getElementById('loadingAccount').style.display = 'none';
            document.getElementById('noAccount').style.display = 'block';
            return;
        }

        if (!response.ok) {
            throw new Error('Erro ao carregar conta');
        }

        const result = await response.json();
        const account = result.data;

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

