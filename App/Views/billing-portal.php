<?php
/**
 * View de Portal de Cobrança
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-door-open text-primary"></i>
                Portal de Cobrança
            </h1>
            <p class="text-muted mb-0">Gere links para o portal de cobrança do Stripe</p>
        </div>
    </div>

    <div id="alertContainer"></div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Sobre o Portal de Cobrança
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle me-2"></i>
                <strong>O que é o Portal de Cobrança?</strong>
                <p class="mb-0 mt-2">
                    O portal de cobrança do Stripe permite que seus clientees gerenciem suas assinaturas, métodos de pagamento e faturas de forma autônoma, 
                    sem precisar entrar em contato com o suporte.
                </p>
            </div>

            <form id="billingPortalForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cliente ID *</label>
                        <input type="number" class="form-control" name="customer_id" required>
                        <small class="text-muted">ID do cliente no sistema</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">URL de Retorno</label>
                        <input type="url" class="form-control" name="return_url" value="<?php echo htmlspecialchars(($apiUrl ?? '') . '/dashboard', ENT_QUOTES); ?>">
                        <small class="text-muted">URL para onde o cliente será redirecionado após usar o portal</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-box-arrow-up-right"></i> Gerar Link do Portal
                </button>
            </form>

            <div id="portalResult" class="mt-4" style="display: none;">
                <div class="alert alert-success border-0 shadow-sm">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-check-circle-fill fs-3 me-3 text-success"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">Link do Portal Gerado com Sucesso!</h5>
                            <p class="mb-3">O link está pronto para ser compartilhado com o cliente. Clique no botão abaixo para abrir o portal de cobrança:</p>
                            <div class="d-flex gap-2">
                                <a href="#" id="portalLink" class="btn btn-success" target="_blank">
                                    <i class="bi bi-box-arrow-up-right"></i> Abrir Portal de Cobrança
                                </a>
                                <button class="btn btn-outline-secondary" onclick="copyPortalLink()" id="copyLinkBtn">
                                    <i class="bi bi-clipboard"></i> Copiar Link
                                </button>
                            </div>
                            <div class="mt-3">
                                <label class="form-label small text-muted">Link do Portal:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" id="portalLinkInput" readonly>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyPortalLink()">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('billingPortalForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            customer_id: parseInt(formData.get('customer_id')),
            return_url: formData.get('return_url') || window.location.origin + '/dashboard'
        };
        
        try {
            const response = await apiRequest('/v1/billing-portal', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            if (response.success && response.data.url) {
                const portalUrl = response.data.url;
                document.getElementById('portalLink').href = portalUrl;
                document.getElementById('portalLinkInput').value = portalUrl;
                document.getElementById('portalResult').style.display = 'block';
                
                // Scroll suave até o resultado
                document.getElementById('portalResult').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });
});

function copyPortalLink() {
    const input = document.getElementById('portalLinkInput');
    if (input && input.value) {
        input.select();
        input.setSelectionRange(0, 99999); // Para mobile
        
        try {
            document.execCommand('copy');
            
            const btn = document.getElementById('copyLinkBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i> Copiado!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-secondary');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
            
            showAlert('Link copiado para a área de transferência!', 'success');
        } catch (err) {
            showAlert('Erro ao copiar link. Tente selecionar e copiar manualmente.', 'warning');
        }
    }
}
</script>

