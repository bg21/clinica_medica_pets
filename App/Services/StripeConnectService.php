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

    /**
     * Cria link de login para conta Connect existente
     * 
     * Permite que o tenant acesse o dashboard Stripe da sua conta Connect
     * sem precisar fazer login manualmente.
     * 
     * @param int $tenantId ID do tenant
     * @return array Dados do link de login
     * @throws \RuntimeException Se a conta Connect não for encontrada
     */
    public function createLoginLink(int $tenantId): array
    {
        $account = $this->accountModel->findByTenant($tenantId);
        
        if (!$account || !$account['stripe_account_id']) {
            throw new \RuntimeException("Conta Stripe Connect não encontrada");
        }
        
        try {
            $loginLink = $this->stripeService->getClient()->accounts->createLoginLink(
                $account['stripe_account_id']
            );
            
            Logger::info("Link de login Stripe Connect criado", [
                'tenant_id' => $tenantId,
                'stripe_account_id' => $account['stripe_account_id'],
                'expires_at' => $loginLink->expires_at
            ]);
            
            return [
                'login_url' => $loginLink->url,
                'expires_at' => $loginLink->expires_at
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Logger::error("Erro ao criar link de login Stripe Connect", [
                'tenant_id' => $tenantId,
                'stripe_account_id' => $account['stripe_account_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Erro ao criar link de login: " . $e->getMessage());
        }
    }

    /**
     * Obtém saldo da conta Connect
     * 
     * Retorna o saldo disponível e pendente da conta Stripe Connect do tenant.
     * 
     * @param int $tenantId ID do tenant
     * @return array Dados do saldo (available, pending, currency)
     * @throws \RuntimeException Se a conta Connect não for encontrada
     */
    public function getBalance(int $tenantId): array
    {
        $account = $this->accountModel->findByTenant($tenantId);
        
        if (!$account || !$account['stripe_account_id']) {
            throw new \RuntimeException("Conta Stripe Connect não encontrada");
        }
        
        try {
            // ✅ CORREÇÃO: Usa forConnectAccount para operar em nome da conta Connect
            $stripeService = StripeService::forConnectAccount($account['stripe_account_id']);
            $balance = $stripeService->getClient()->balance->retrieve();
            
            Logger::debug("Saldo da conta Stripe Connect obtido", [
                'tenant_id' => $tenantId,
                'stripe_account_id' => $account['stripe_account_id'],
                'available' => $balance->available[0]->amount ?? 0,
                'pending' => $balance->pending[0]->amount ?? 0
            ]);
            
            return [
                'available' => $balance->available[0]->amount ?? 0,
                'pending' => $balance->pending[0]->amount ?? 0,
                'currency' => $balance->available[0]->currency ?? 'brl'
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Logger::error("Erro ao obter saldo da conta Stripe Connect", [
                'tenant_id' => $tenantId,
                'stripe_account_id' => $account['stripe_account_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Erro ao obter saldo: " . $e->getMessage());
        }
    }

    /**
     * Lista transferências da conta Connect
     * 
     * Retorna as transferências realizadas para a conta Stripe Connect do tenant.
     * 
     * @param int $tenantId ID do tenant
     * @param array $options Opções de filtro (limit, starting_after, ending_before, etc)
     * @return array Lista de transferências formatadas
     * @throws \RuntimeException Se a conta Connect não for encontrada
     */
    public function listTransfers(int $tenantId, array $options = []): array
    {
        $account = $this->accountModel->findByTenant($tenantId);
        
        if (!$account || !$account['stripe_account_id']) {
            throw new \RuntimeException("Conta Stripe Connect não encontrada");
        }
        
        try {
            // ✅ CORREÇÃO: Usa forConnectAccount para operar em nome da conta Connect
            $stripeService = StripeService::forConnectAccount($account['stripe_account_id']);
            $transfers = $stripeService->getClient()->transfers->all($options);
            
            Logger::debug("Transferências da conta Stripe Connect listadas", [
                'tenant_id' => $tenantId,
                'stripe_account_id' => $account['stripe_account_id'],
                'count' => count($transfers->data)
            ]);
            
            return array_map(function($transfer) {
                return [
                    'id' => $transfer->id,
                    'amount' => $transfer->amount,
                    'currency' => $transfer->currency,
                    'status' => $transfer->status,
                    'destination' => $transfer->destination ?? null,
                    'description' => $transfer->description ?? null,
                    'created' => date('Y-m-d H:i:s', $transfer->created),
                    'metadata' => $transfer->metadata->toArray() ?? []
                ];
            }, $transfers->data);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Logger::error("Erro ao listar transferências da conta Stripe Connect", [
                'tenant_id' => $tenantId,
                'stripe_account_id' => $account['stripe_account_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Erro ao listar transferências: " . $e->getMessage());
        }
    }
}

