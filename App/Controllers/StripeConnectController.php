<?php

namespace App\Controllers;

use App\Services\StripeConnectService;
use App\Models\TenantStripeAccount;
use App\Utils\ResponseHelper;
use App\Utils\Validator;
use App\Utils\ErrorHandler;
use Flight;

/**
 * Controller para gerenciar Stripe Connect
 */
class StripeConnectController
{
    private StripeConnectService $connectService;

    public function __construct(StripeConnectService $connectService)
    {
        $this->connectService = $connectService;
    }

    /**
     * Cria link de onboarding do Stripe Connect
     * POST /v1/stripe-connect/onboarding
     */
    public function createOnboarding(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if (!$tenantId) {
                ResponseHelper::sendUnauthorizedError('Não autenticado');
                return;
            }

            $data = \App\Utils\RequestCache::getJsonInput();
            $returnUrl = $data['return_url'] ?? getBaseUrl() . '/stripe-connect/success';

            $result = $this->connectService->createOnboardingLink($tenantId, $returnUrl);

            ResponseHelper::sendSuccess($result, 200, 'Link de onboarding criado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar link de onboarding',
                'CREATE_ONBOARDING_ERROR'
            );
        }
    }

    /**
     * Busca status da conta Stripe Connect
     * GET /v1/stripe-connect/account
     * 
     * Retorna 200 com data null se não encontrar (em vez de 404)
     * para facilitar tratamento no frontend
     */
    public function getAccount(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if (!$tenantId) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', [
                    'action' => 'get_stripe_connect_account'
                ]);
                return;
            }

            $account = $this->connectService->getAccount($tenantId);

            // ✅ CORREÇÃO: Retorna 200 com data null em vez de 404
            // Facilita tratamento no frontend (já trata 404, mas 200 é mais semântico)
            if (!$account) {
                ResponseHelper::sendSuccess(null, 200, 'Conta Stripe não encontrada');
                return;
            }

            ResponseHelper::sendSuccess($account, 200, 'Conta encontrada');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar conta',
                'GET_ACCOUNT_ERROR',
                ['action' => 'get_stripe_connect_account', 'tenant_id' => Flight::get('tenant_id')]
            );
        }
    }

    /**
     * Salva ou atualiza a API key secreta do Stripe do tenant
     * POST /v1/stripe-connect/api-key
     * 
     * Body:
     * {
     *   "stripe_secret_key": "sk_test_xxx" ou "sk_live_xxx"
     * }
     */
    public function saveApiKey(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if (!$tenantId) {
                ResponseHelper::sendUnauthorizedError('Não autenticado');
                return;
            }

            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    ResponseHelper::sendInvalidJsonError(['action' => 'save_stripe_api_key']);
                    return;
                }
                $data = [];
            }

            // Validação
            if (empty($data['stripe_secret_key'])) {
                ResponseHelper::sendValidationError(
                    'stripe_secret_key é obrigatório',
                    ['stripe_secret_key' => 'A API key secreta do Stripe é obrigatória'],
                    ['action' => 'save_stripe_api_key', 'tenant_id' => $tenantId]
                );
                return;
            }

            // Valida formato da API key
            if (!preg_match('/^sk_(test|live)_[a-zA-Z0-9]+$/', $data['stripe_secret_key'])) {
                ResponseHelper::sendValidationError(
                    'API key inválida',
                    ['stripe_secret_key' => 'A API key deve começar com "sk_test_" ou "sk_live_"'],
                    ['action' => 'save_stripe_api_key', 'tenant_id' => $tenantId]
                );
                return;
            }

            // Salva API key (já criptografada pelo model)
            $accountModel = new TenantStripeAccount();
            
            \App\Services\Logger::info("Tentando salvar API key", [
                'tenant_id' => $tenantId,
                'key_length' => strlen($data['stripe_secret_key']),
                'key_prefix' => substr($data['stripe_secret_key'], 0, 10) . '...'
            ]);
            
            try {
                $success = $accountModel->saveStripeSecretKey($tenantId, $data['stripe_secret_key']);
                
                \App\Services\Logger::info("Resultado do saveStripeSecretKey", [
                    'tenant_id' => $tenantId,
                    'success' => $success
                ]);
            } catch (\Exception $e) {
                \App\Services\Logger::error("Exceção ao salvar API key", [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            if (!$success) {
                \App\Services\Logger::error("saveStripeSecretKey retornou false", [
                    'tenant_id' => $tenantId
                ]);
                
                ResponseHelper::sendError(
                    500,
                    'Erro ao salvar API key',
                    'Não foi possível salvar a API key. Verifique os logs para mais detalhes.',
                    'SAVE_API_KEY_ERROR'
                );
                return;
            }

            \App\Services\Logger::info("API key do Stripe salva com sucesso", [
                'tenant_id' => $tenantId,
                'key_type' => strpos($data['stripe_secret_key'], 'sk_test_') === 0 ? 'test' : 'live'
            ]);

            ResponseHelper::sendSuccess([
                'message' => 'API key salva com sucesso',
                'key_type' => strpos($data['stripe_secret_key'], 'sk_test_') === 0 ? 'test' : 'live'
            ], 'API key do Stripe salva com sucesso');
        } catch (\InvalidArgumentException $e) {
            ResponseHelper::sendValidationError(
                $e->getMessage(),
                ['stripe_secret_key' => $e->getMessage()],
                ['action' => 'save_stripe_api_key', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao salvar API key',
                'SAVE_API_KEY_ERROR',
                ['action' => 'save_stripe_api_key', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Cria link de login para conta Connect existente
     * POST /v1/stripe-connect/login-link
     * 
     * Permite que o tenant acesse o dashboard Stripe da sua conta Connect
     * sem precisar fazer login manualmente.
     */
    public function createLoginLink(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if (!$tenantId) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', [
                    'action' => 'create_login_link'
                ]);
                return;
            }

            $result = $this->connectService->createLoginLink($tenantId);

            ResponseHelper::sendSuccess($result, 200, 'Link de login criado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendError(
                404,
                'Conta não encontrada',
                $e->getMessage(),
                'ACCOUNT_NOT_FOUND',
                ['action' => 'create_login_link', 'tenant_id' => Flight::get('tenant_id')]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar link de login',
                'CREATE_LOGIN_LINK_ERROR',
                ['action' => 'create_login_link', 'tenant_id' => Flight::get('tenant_id')]
            );
        }
    }

    /**
     * Obtém saldo da conta Connect
     * GET /v1/stripe-connect/balance
     * 
     * Retorna o saldo disponível e pendente da conta Stripe Connect do tenant.
     */
    public function getBalance(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if (!$tenantId) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', [
                    'action' => 'get_balance'
                ]);
                return;
            }

            $balance = $this->connectService->getBalance($tenantId);

            ResponseHelper::sendSuccess($balance, 200, 'Saldo obtido com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendError(
                404,
                'Conta não encontrada',
                $e->getMessage(),
                'ACCOUNT_NOT_FOUND',
                ['action' => 'get_balance', 'tenant_id' => Flight::get('tenant_id')]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter saldo',
                'GET_BALANCE_ERROR',
                ['action' => 'get_balance', 'tenant_id' => Flight::get('tenant_id')]
            );
        }
    }

    /**
     * Lista transferências da conta Connect
     * GET /v1/stripe-connect/transfers
     * 
     * Query params opcionais:
     *   - limit: Número máximo de resultados (padrão: 10)
     *   - starting_after: ID da transferência para paginação
     *   - ending_before: ID da transferência para paginação reversa
     * 
     * Retorna as transferências realizadas para a conta Stripe Connect do tenant.
     */
    public function listTransfers(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if (!$tenantId) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', [
                    'action' => 'list_transfers'
                ]);
                return;
            }

            // Obtém query params
            try {
                $queryParams = Flight::request()->query->getData();
                if (!is_array($queryParams)) {
                    $queryParams = [];
                }
            } catch (\Exception $e) {
                \App\Services\Logger::warning("Erro ao obter query params: " . $e->getMessage());
                $queryParams = [];
            }

            $options = [];
            
            if (isset($queryParams['limit'])) {
                $options['limit'] = min((int)$queryParams['limit'], 100); // Máximo 100
            } else {
                $options['limit'] = 10; // Padrão: 10 itens
            }
            
            if (!empty($queryParams['starting_after'])) {
                $options['starting_after'] = $queryParams['starting_after'];
            }
            
            if (!empty($queryParams['ending_before'])) {
                $options['ending_before'] = $queryParams['ending_before'];
            }

            $transfers = $this->connectService->listTransfers($tenantId, $options);

            ResponseHelper::sendSuccess($transfers, 200, 'Transferências listadas com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendError(
                404,
                'Conta não encontrada',
                $e->getMessage(),
                'ACCOUNT_NOT_FOUND',
                ['action' => 'list_transfers', 'tenant_id' => Flight::get('tenant_id')]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar transferências',
                'LIST_TRANSFERS_ERROR',
                ['action' => 'list_transfers', 'tenant_id' => Flight::get('tenant_id')]
            );
        }
    }
}

