<?php

namespace App\Models;

/**
 * Model para gerenciar contas Stripe Connect dos tenants
 */
class TenantStripeAccount extends BaseModel
{
    protected string $table = 'tenant_stripe_accounts';

    /**
     * Busca conta Stripe por tenant
     */
    public function findByTenant(int $tenantId): ?array
    {
        return $this->findBy('tenant_id', $tenantId);
    }

    /**
     * Busca conta Stripe por Stripe Account ID
     */
    public function findByStripeAccountId(string $stripeAccountId): ?array
    {
        return $this->findBy('stripe_account_id', $stripeAccountId);
    }

    /**
     * Cria ou atualiza conta Stripe do tenant
     */
    public function createOrUpdate(int $tenantId, string $stripeAccountId, array $data): int
    {
        $existing = $this->findByTenant($tenantId);

        $accountData = [
            'tenant_id' => $tenantId,
            'stripe_account_id' => $stripeAccountId,
            'account_type' => $data['account_type'] ?? 'express',
            'charges_enabled' => isset($data['charges_enabled']) ? ($data['charges_enabled'] ? 1 : 0) : 0,
            'payouts_enabled' => isset($data['payouts_enabled']) ? ($data['payouts_enabled'] ? 1 : 0) : 0,
            'details_submitted' => isset($data['details_submitted']) ? ($data['details_submitted'] ? 1 : 0) : 0,
            'onboarding_completed' => isset($data['onboarding_completed']) ? ($data['onboarding_completed'] ? 1 : 0) : 0,
            'email' => $data['email'] ?? null,
            'country' => $data['country'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
        ];

        if ($existing) {
            $this->update($existing['id'], $accountData);
            return $existing['id'];
        }

        return $this->insert($accountData);
    }

    /**
     * Verifica se tenant tem conta Stripe configurada e ativa
     */
    public function isActive(int $tenantId): bool
    {
        $account = $this->findByTenant($tenantId);
        
        if (!$account) {
            return false;
        }

        return $account['charges_enabled'] == 1 && $account['onboarding_completed'] == 1;
    }
}

