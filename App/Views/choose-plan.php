<?php
/**
 * View - Escolha de Plano após Registro
 * Página onde a clínica escolhe o plano de assinatura do SaaS
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha seu Plano - Sistema Clínica Veterinária</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .plan-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid transparent;
            height: 100%;
        }
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #007bff;
        }
        .plan-card.featured {
            border-color: #007bff;
            position: relative;
        }
        .plan-card.featured::before {
            content: 'Mais Popular';
            position: absolute;
            top: -10px;
            right: 20px;
            background: #007bff;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .plan-card .card-body {
            display: flex;
            flex-direction: column;
        }
        .plan-card .features {
            flex-grow: 1;
            margin: 20px 0;
        }
        .plan-card .features ul {
            list-style: none;
            padding: 0;
        }
        .plan-card .features li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .plan-card .features li:last-child {
            border-bottom: none;
        }
        .plan-card .features li i {
            color: #28a745;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-heart-pulse"></i> Sistema Clínica Veterinária
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="text-center mb-5 text-white">
            <h1 class="display-4 mb-3">Escolha seu Plano</h1>
            <p class="lead">Selecione o plano ideal para sua clínica veterinária</p>
        </div>

        <div id="alertContainer"></div>

        <!-- Loading -->
        <div id="loadingPlans" class="text-center py-5">
            <div class="spinner-border text-white" role="status">
                <span class="visually-hidden">Carregando planos...</span>
            </div>
        </div>

        <!-- Planos -->
        <div id="plansContainer" class="row g-4" style="display: none;">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_URL = '<?= $apiUrl ?? "http://localhost:8080" ?>';
        let plans = [];
        let selectedPlan = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadPlans();
        });

        async function loadPlans() {
            try {
                // Busca produtos e preços do Stripe (da SUA conta)
                // Estes são os planos que VOCÊ cobra das clínicas
                const response = await fetch(API_URL + '/v1/saas/plans', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Erro ao carregar planos');
                }

                const result = await response.json();
                plans = result.data || [];

                renderPlans();
            } catch (error) {
                showAlert('Erro ao carregar planos: ' + error.message, 'danger');
            } finally {
                document.getElementById('loadingPlans').style.display = 'none';
                document.getElementById('plansContainer').style.display = 'flex';
            }
        }

        function renderPlans() {
            const container = document.getElementById('plansContainer');
            
            if (plans.length === 0) {
                container.innerHTML = '<div class="col-12 text-center text-white"><p>Nenhum plano disponível no momento. Entre em contato com o suporte.</p></div>';
                return;
            }
            
            container.innerHTML = plans.map((plan, index) => {
                const isFeatured = index === 1; // Segundo plano como featured
                const amount = plan.unit_amount / 100;
                const currency = plan.currency.toUpperCase();
                const interval = plan.recurring?.interval === 'month' ? 'mês' : 'ano';
                
                return `
                    <div class="col-md-4">
                        <div class="card plan-card ${isFeatured ? 'featured' : ''}">
                            <div class="card-body text-center">
                                <h3 class="card-title">${plan.product?.name || 'Plano'}</h3>
                                <p class="text-muted">${plan.product?.description || ''}</p>
                                
                                <div class="my-4">
                                    <h2 class="text-primary">R$ ${amount.toFixed(2).replace('.', ',')}</h2>
                                    <small class="text-muted">/${interval}</small>
                                </div>

                                <div class="features text-start">
                                    <ul>
                                        ${(plan.metadata?.features || '').split(',').map(f => f.trim()).filter(f => f).map(f => 
                                            `<li><i class="bi bi-check-circle-fill"></i> ${f}</li>`
                                        ).join('')}
                                    </ul>
                                </div>

                                <button class="btn btn-primary w-100 mt-auto" onclick="selectPlan('${plan.id}')">
                                    <i class="bi bi-check-circle"></i> Escolher Plano
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function selectPlan(priceId) {
            try {
                // Cria checkout session para assinatura
                const response = await fetch(API_URL + '/v1/saas/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        price_id: priceId,
                        success_url: window.location.origin + '/subscription-success?session_id={CHECKOUT_SESSION_ID}',
                        cancel_url: window.location.origin + '/choose-plan'
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Erro ao criar checkout');
                }

                const result = await response.json();
                
                // Redireciona para Stripe Checkout
                if (result.data?.url) {
                    window.location.href = result.data.url;
                } else {
                    throw new Error('URL de checkout não retornada');
                }
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
</body>
</html>

