<?php
/**
 * View - Módulo Não Disponível
 * Exibida quando o tenant tenta acessar um módulo que não está disponível no seu plano
 */
?>
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-lock-fill text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="card-title mb-3">Módulo Não Disponível</h2>
                    <p class="text-muted mb-4">
                        O módulo <strong><?php echo htmlspecialchars($moduleName ?? 'solicitado'); ?></strong> 
                        não está disponível no <strong><?php echo htmlspecialchars($currentPlan ?? 'seu plano atual'); ?></strong>.
                    </p>
                    <p class="text-muted mb-4">
                        Para acessar este módulo, você precisa fazer upgrade do seu plano de assinatura.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="<?php echo htmlspecialchars($upgradeUrl ?? '/my-subscription'); ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-up-circle me-2"></i>
                            Fazer Upgrade do Plano
                        </a>
                        <a href="/dashboard" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-arrow-left me-2"></i>
                            Voltar ao Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 16px;
}

.card-body {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.bi-lock-fill {
    color: #ffc107;
    opacity: 0.8;
}
</style>

