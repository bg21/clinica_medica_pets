<?php
/**
 * View - Assinatura Necessária
 * Exibida quando o tenant não tem assinatura ativa
 */
$reason = $_GET['reason'] ?? 'SUBSCRIPTION_REQUIRED';
$messages = [
    'SUBSCRIPTION_REQUIRED' => 'Você precisa de uma assinatura ativa para acessar o sistema.',
    'SUBSCRIPTION_INACTIVE' => 'Sua assinatura não está ativa. Renove para continuar usando o sistema.',
    'SUBSCRIPTION_CANCELED' => 'Sua assinatura foi cancelada. Renove para continuar usando o sistema.',
    'SUBSCRIPTION_PAST_DUE' => 'Pagamento atrasado. Regularize sua assinatura para continuar usando o sistema.',
    'SUBSCRIPTION_INCOMPLETE' => 'Pagamento pendente. Complete o pagamento para ativar sua assinatura.'
];
$message = $messages[$reason] ?? $messages['SUBSCRIPTION_REQUIRED'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura Necessária - Sistema Clínica Veterinária</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .subscription-card {
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card subscription-card">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 5rem;"></i>
                        </div>
                        <h2 class="mb-3">Assinatura Necessária</h2>
                        <p class="text-muted mb-4">
                            <?= htmlspecialchars($message) ?>
                        </p>
                        
                        <div class="alert alert-info text-start mb-4">
                            <h6 class="alert-heading"><i class="bi bi-info-circle"></i> O que fazer?</h6>
                            <ul class="mb-0">
                                <li>Escolha um plano adequado para sua clínica</li>
                                <li>Complete o pagamento da assinatura</li>
                                <li>Após confirmação, você terá acesso completo ao sistema</li>
                            </ul>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="/my-subscription" class="btn btn-primary btn-lg">
                                <i class="bi bi-credit-card"></i> Escolher Plano
                            </a>
                            <a href="/subscriptions" class="btn btn-outline-secondary">
                                <i class="bi bi-list-ul"></i> Ver Minhas Assinaturas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

