<?php

namespace App\Middleware;

use App\Models\Subscription;
use App\Services\Logger;
use Flight;

/**
 * Middleware para verificar se o tenant tem assinatura ativa
 * 
 * Bloqueia acesso se:
 * - Não tiver assinatura
 * - Assinatura estiver cancelada
 * - Assinatura estiver past_due (pagamento atrasado)
 * - Assinatura estiver incomplete (pagamento não processado)
 * 
 * Permite acesso se:
 * - Assinatura estiver active
 * - Assinatura estiver trialing (período de teste)
 */
class SubscriptionMiddleware
{
    private Subscription $subscriptionModel;

    public function __construct()
    {
        $this->subscriptionModel = new Subscription();
    }

    /**
     * Verifica se tenant tem assinatura ativa
     * 
     * @return array|null Retorna null se tiver acesso, ou array com erro se bloquear
     */
    public function check(): ?array
    {
        $tenantId = Flight::get('tenant_id');
        
        // Master key sempre tem acesso
        if (Flight::get('is_master') === true) {
            return null;
        }

        // Se não tiver tenant_id, não pode verificar (deve ser tratado por AuthMiddleware)
        if (!$tenantId) {
            return null; // Deixa AuthMiddleware tratar
        }

        // Busca assinatura ativa
        $subscription = $this->subscriptionModel->findActiveByTenant($tenantId);

        if (!$subscription) {
            Logger::warning("Tentativa de acesso sem assinatura ativa", [
                'tenant_id' => $tenantId
            ]);
            
            return [
                'error' => true,
                'message' => 'Assinatura não encontrada ou inativa',
                'code' => 'SUBSCRIPTION_REQUIRED',
                'http_code' => 402 // Payment Required
            ];
        }

        // Verifica status da assinatura
        $status = $subscription['status'] ?? 'unknown';
        
        // Status que permitem acesso
        $allowedStatuses = ['active', 'trialing'];
        
        if (!in_array($status, $allowedStatuses)) {
            Logger::warning("Tentativa de acesso com assinatura em status inválido", [
                'tenant_id' => $tenantId,
                'subscription_id' => $subscription['id'],
                'status' => $status
            ]);
            
            $messages = [
                'canceled' => 'Sua assinatura foi cancelada. Renove para continuar usando o sistema.',
                'past_due' => 'Pagamento atrasado. Regularize sua assinatura para continuar usando o sistema.',
                'incomplete' => 'Pagamento pendente. Complete o pagamento para ativar sua assinatura.',
                'incomplete_expired' => 'Período de pagamento expirado. Renove sua assinatura.',
                'unpaid' => 'Pagamento não realizado. Regularize sua assinatura para continuar usando o sistema.'
            ];
            
            return [
                'error' => true,
                'message' => $messages[$status] ?? 'Sua assinatura não está ativa',
                'code' => 'SUBSCRIPTION_INACTIVE',
                'status' => $status,
                'subscription_id' => $subscription['id'],
                'http_code' => 402 // Payment Required
            ];
        }

        // Assinatura ativa - permite acesso
        Logger::debug("Verificação de assinatura bem-sucedida", [
            'tenant_id' => $tenantId,
            'subscription_id' => $subscription['id'],
            'status' => $status
        ]);

        return null; // null = acesso permitido
    }

    /**
     * Verifica se deve aplicar o middleware na rota
     * 
     * @param string $route Rota atual
     * @return bool True se deve verificar, false caso contrário
     */
    public function shouldCheck(string $route): bool
    {
        // Rotas que NÃO precisam de assinatura
        $excludedRoutes = [
            '/login',
            '/register',
            '/choose-plan',
            '/subscription-success',
            '/v1/auth/login',
            '/v1/auth/register',
            '/v1/auth/register-employee',
            '/v1/saas/plans',
            '/v1/saas/checkout',
            '/v1/webhook',
            '/health',
            '/health/detailed',
            '/',
            '/api-docs',
            '/api-docs/ui'
        ];

        foreach ($excludedRoutes as $excluded) {
            if ($route === $excluded || strpos($route, $excluded) === 0) {
                return false;
            }
        }

        return true;
    }
}

