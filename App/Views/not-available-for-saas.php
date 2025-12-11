<?php
/**
 * View - Funcionalidade não disponível para administradores SaaS
 * Exibe mensagem informando que a funcionalidade é apenas para clínicas
 */
?>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-info-circle text-primary" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="h4 mb-3">Funcionalidade Disponível Apenas para Clínicas</h2>
                    <p class="text-muted mb-4">
                        Esta funcionalidade é exclusiva para clínicas veterinárias que possuem uma conta ativa no sistema.
                    </p>
                    <p class="text-muted mb-4">
                        Como administrador do SaaS, você pode gerenciar planos, módulos e configurações do sistema, mas não pode acessar as funcionalidades específicas de clínicas.
                    </p>
                    <div class="mt-4">
                        <a href="/admin-plans" class="btn btn-primary">
                            <i class="bi bi-grid-3x3-gap me-2"></i>
                            Gerenciar Planos e Módulos
                        </a>
                        <a href="/dashboard" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-speedometer2 me-2"></i>
                            Voltar ao Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

