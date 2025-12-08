<?php
/**
 * View - Sucesso após conectar Stripe
 */
?>
<?php include __DIR__ . '/layouts/base.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="mb-3">Stripe Conectado com Sucesso!</h2>
                    <p class="text-muted mb-4">
                        Sua conta Stripe foi conectada. Agora você pode receber pagamentos dos seus clientes.
                    </p>
                    
                    <div class="d-grid gap-2 col-md-6 mx-auto">
                        <a href="/stripe-connect" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Voltar para Configurações
                        </a>
                        <a href="/dashboard" class="btn btn-outline-secondary">
                            <i class="bi bi-speedometer2"></i> Ir para Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

