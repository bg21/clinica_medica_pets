<?php

namespace App\Models;

use App\Utils\EncryptionHelper;

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

    /**
     * Salva ou atualiza a API key secreta do Stripe do tenant (criptografada)
     * 
     * @param int $tenantId ID do tenant
     * @param string $stripeSecretKey API key secreta do Stripe (será criptografada)
     * @return bool Sucesso da operação
     */
    public function saveStripeSecretKey(int $tenantId, string $stripeSecretKey): bool
    {
        try {
            // Valida formato básico da API key
            if (empty($stripeSecretKey) || !preg_match('/^sk_(test|live)_[a-zA-Z0-9]+$/', $stripeSecretKey)) {
                throw new \InvalidArgumentException("API key do Stripe inválida. Deve começar com 'sk_test_' ou 'sk_live_'");
            }

            // Criptografa a API key
            $encryptedKey = EncryptionHelper::encrypt($stripeSecretKey);

            // Busca conta existente
            $account = $this->findByTenant($tenantId);

            if ($account) {
                // Atualiza API key existente
                return $this->update($account['id'], [
                    'stripe_secret_key' => $encryptedKey
                ]);
            } else {
                // Cria nova conta com API key
                // Se não tiver stripe_account_id, cria um placeholder
                $stripeAccountId = 'manual_' . $tenantId . '_' . time();
                
                $insertData = [
                    'tenant_id' => $tenantId,
                    'stripe_account_id' => $stripeAccountId,
                    'stripe_secret_key' => $encryptedKey,
                    'account_type' => 'standard', // Conta própria do tenant
                    'charges_enabled' => 1, // Assumimos que está ativa se tem API key
                    'payouts_enabled' => 1,
                    'details_submitted' => 1,
                    'onboarding_completed' => 1
                ];
                
                \App\Services\Logger::debug("Tentando inserir conta Stripe", [
                    'tenant_id' => $tenantId,
                    'stripe_account_id' => $stripeAccountId,
                    'has_encrypted_key' => !empty($encryptedKey),
                    'encrypted_key_length' => strlen($encryptedKey)
                ]);
                
                $insertId = $this->insert($insertData);
                
                \App\Services\Logger::debug("Resultado do insert", [
                    'tenant_id' => $tenantId,
                    'insert_id' => $insertId,
                    'success' => $insertId > 0
                ]);
                
                return $insertId > 0;
            }
        } catch (\Exception $e) {
            \App\Services\Logger::error("Erro ao salvar API key do Stripe", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtém a API key secreta do Stripe do tenant (descriptografada)
     * 
     * @param int $tenantId ID do tenant
     * @return string|null API key descriptografada ou null se não existir
     */
    public function getStripeSecretKey(int $tenantId): ?string
    {
        try {
            $account = $this->findByTenant($tenantId);
            
            if (!$account || empty($account['stripe_secret_key'])) {
                return null;
            }

            // Descriptografa a API key
            return EncryptionHelper::decrypt($account['stripe_secret_key']);
        } catch (\Exception $e) {
            \App\Services\Logger::error("Erro ao recuperar API key do Stripe", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Verifica se o tenant tem API key configurada
     * 
     * @param int $tenantId ID do tenant
     * @return bool True se tem API key configurada
     */
    public function hasStripeSecretKey(int $tenantId): bool
    {
        $account = $this->findByTenant($tenantId);
        return $account && !empty($account['stripe_secret_key']);
    }
}

