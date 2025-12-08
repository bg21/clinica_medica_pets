<?php

namespace App\Controllers;

use App\Services\StripeConnectService;
use App\Utils\ResponseHelper;
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

            ResponseHelper::sendSuccess($result, 'Link de onboarding criado com sucesso');
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
     */
    public function getAccount(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if (!$tenantId) {
                ResponseHelper::sendUnauthorizedError('Não autenticado');
                return;
            }

            $account = $this->connectService->getAccount($tenantId);

            if (!$account) {
                ResponseHelper::sendError(
                    404,
                    'Conta Stripe não encontrada',
                    'Você ainda não conectou sua conta Stripe',
                    'ACCOUNT_NOT_FOUND'
                );
                return;
            }

            ResponseHelper::sendSuccess($account, 'Conta encontrada');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar conta',
                'GET_ACCOUNT_ERROR'
            );
        }
    }
}

