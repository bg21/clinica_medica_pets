<?php

namespace App\Services;

use App\Models\TenantStripeAccount;
use App\Services\StripeService;
use App\Services\Logger;
use Flight;

/**
 * Service para gerenciar Stripe Connect
 * Permite que tenants conectem suas próprias contas Stripe para receber pagamentos
 */
class StripeConnectService
{
    private StripeService $stripeService;
    private TenantStripeAccount $accountModel;

    public function __construct(StripeService $stripeService, TenantStripeAccount $accountModel)
    {
        $this->stripeService = $stripeService;
        $this->accountModel = $accountModel;
    }

    /**
     * Cria link de onboarding do Stripe Connect Express
     * 
     * @param int $tenantId ID do tenant
     * @param string $returnUrl URL de retorno após completar onboarding
     * @return array Dados do link de onboarding
     */
    public function createOnboardingLink(int $tenantId, string $returnUrl): array
    {
        // Busca tenant para obter dados
        $tenantModel = new \App\Models\Tenant();
        $tenant = $tenantModel->findById($tenantId);
        
        if (!$tenant) {
            throw new \RuntimeException("Tenant não encontrado");
        }
        
        // Busca conta existente
        $existingAccount = $this->accountModel->findByTenant($tenantId);
        
        $stripeAccountId = null;
        
        if ($existingAccount) {
            $stripeAccountId = $existingAccount['stripe_account_id'];
        } else {
            // Cria nova conta Express
            // Busca email do usuário admin do tenant
            $userModel = new \App\Models\User();
            $adminUser = $userModel->findBy([
                'tenant_id' => $tenantId,
                'role' => 'admin'
            ], ['created_at' => 'ASC'], 1);
            
            $accountEmail = $adminUser ? ($adminUser[0]['email'] ?? null) : null;
            
            $account = $this->stripeService->getClient()->accounts->create([
                'type' => 'express',
                'country' => 'BR', // Brasil por padrão
                'email' => $accountEmail,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'metadata' => [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant['name'] ?? ''
                ]
            ]);
            
            $stripeAccountId = $account->id;
            
            // Salva no banco
            $this->accountModel->createOrUpdate($tenantId, $stripeAccountId, [
                'account_type' => 'express',
                'charges_enabled' => false,
                'payouts_enabled' => false,
                'details_submitted' => false,
                'onboarding_completed' => false,
                'email' => $account->email ?? null,
                'country' => $account->country ?? 'BR'
            ]);
        }

        // Cria link de onboarding
        $accountLink = $this->stripeService->getClient()->accountLinks->create([
            'account' => $stripeAccountId,
            'refresh_url' => $returnUrl . '?refresh=true',
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        Logger::info("Link de onboarding Stripe Connect criado", [
            'tenant_id' => $tenantId,
            'stripe_account_id' => $stripeAccountId,
            'link_url' => $accountLink->url
        ]);

        return [
            'stripe_account_id' => $stripeAccountId,
            'onboarding_url' => $accountLink->url,
            'expires_at' => $accountLink->expires_at
        ];
    }

    /**
     * Atualiza dados da conta Stripe Connect após webhook
     * 
     * @param string $stripeAccountId ID da conta Stripe
     * @param array $accountData Dados da conta do Stripe
     */
    public function updateAccountFromStripe(string $stripeAccountId, array $accountData): void
    {
        $existing = $this->accountModel->findByStripeAccountId($stripeAccountId);
        
        if (!$existing) {
            Logger::warning("Tentativa de atualizar conta Stripe inexistente", [
                'stripe_account_id' => $stripeAccountId
            ]);
            return;
        }

        $updateData = [
            'charges_enabled' => $accountData['charges_enabled'] ?? false,
            'payouts_enabled' => $accountData['payouts_enabled'] ?? false,
            'details_submitted' => $accountData['details_submitted'] ?? false,
            'email' => $accountData['email'] ?? null,
            'country' => $accountData['country'] ?? null,
            'onboarding_completed' => ($accountData['charges_enabled'] ?? false) && ($accountData['details_submitted'] ?? false)
        ];

        $this->accountModel->update($existing['id'], $updateData);

        Logger::info("Conta Stripe Connect atualizada", [
            'tenant_id' => $existing['tenant_id'],
            'stripe_account_id' => $stripeAccountId,
            'charges_enabled' => $updateData['charges_enabled'],
            'payouts_enabled' => $updateData['payouts_enabled']
        ]);
    }

    /**
     * Busca conta Stripe do tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array|null Dados da conta ou null
     */
    public function getAccount(int $tenantId): ?array
    {
        return $this->accountModel->findByTenant($tenantId);
    }

    /**
     * Verifica se tenant tem conta Stripe ativa
     * 
     * @param int $tenantId ID do tenant
     * @return bool True se tem conta ativa
     */
    public function hasActiveAccount(int $tenantId): bool
    {
        return $this->accountModel->isActive($tenantId);
    }

    /**
     * Obtém Stripe Account ID do tenant
     * 
     * @param int $tenantId ID do tenant
     * @return string|null Stripe Account ID ou null
     */
    public function getStripeAccountId(int $tenantId): ?string
    {
        $account = $this->accountModel->findByTenant($tenantId);
        return $account ? $account['stripe_account_id'] : null;
    }
}

