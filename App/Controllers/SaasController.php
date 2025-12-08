<?php

namespace App\Controllers;

use App\Services\StripeService;
use App\Services\PaymentService;
use App\Services\Logger;
use App\Utils\ResponseHelper;
use App\Utils\ErrorHandler;
use Flight;
use Config;

/**
 * Controller para gerenciar planos e checkout do SaaS
 * Este controller gerencia os planos que VOCÊ cobra das clínicas
 */
class SaasController
{
    private StripeService $stripeService;
    private PaymentService $paymentService;

    public function __construct(StripeService $stripeService, PaymentService $paymentService)
    {
        $this->stripeService = $stripeService;
        $this->paymentService = $paymentService;
    }

    /**
     * Lista planos disponíveis para assinatura do SaaS
     * GET /v1/saas/plans
     * 
     * Retorna os planos que você cobra das clínicas
     */
    public function listPlans(): void
    {
        try {
            // Busca produtos e preços do Stripe
            // Estes são os planos que VOCÊ vende para as clínicas
            $products = $this->stripeService->listProducts(['active' => true]);
            $prices = $this->stripeService->listPrices(['active' => true]);

            // Filtra apenas preços recorrentes (assinaturas)
            $recurringPrices = array_filter($prices->data, function($price) {
                return isset($price->recurring) && $price->recurring !== null;
            });

            // Agrupa preços por produto
            $plans = [];
            foreach ($recurringPrices as $price) {
                $productId = is_string($price->product) ? $price->product : $price->product->id;
                
                // Busca produto
                $product = null;
                foreach ($products->data as $p) {
                    if ($p->id === $productId) {
                        $product = $p;
                        break;
                    }
                }

                // Adiciona metadata do produto ao preço
                $priceData = $price->toArray();
                if ($product) {
                    $priceData['product'] = $product->toArray();
                }

                $plans[] = $priceData;
            }

            // Ordena por valor (menor para maior)
            usort($plans, function($a, $b) {
                return ($a['unit_amount'] ?? 0) - ($b['unit_amount'] ?? 0);
            });

            ResponseHelper::sendSuccess($plans, 'Planos carregados com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar planos',
                'LIST_PLANS_ERROR'
            );
        }
    }

    /**
     * Cria sessão de checkout para assinatura do SaaS
     * POST /v1/saas/checkout
     * 
     * Body:
     * {
     *   "price_id": "price_xxx",
     *   "success_url": "https://...",
     *   "cancel_url": "https://..."
     * }
     */
    public function createCheckout(): void
    {
        try {
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    ResponseHelper::sendInvalidJsonError(['action' => 'create_saas_checkout']);
                    return;
                }
                $data = [];
            }

            // Validação
            if (empty($data['price_id'])) {
                ResponseHelper::sendValidationError(
                    'price_id é obrigatório',
                    ['price_id' => 'O ID do preço é obrigatório'],
                    ['action' => 'create_saas_checkout']
                );
                return;
            }

            if (empty($data['success_url']) || empty($data['cancel_url'])) {
                ResponseHelper::sendValidationError(
                    'URLs são obrigatórias',
                    [
                        'success_url' => 'URL de sucesso é obrigatória',
                        'cancel_url' => 'URL de cancelamento é obrigatória'
                    ],
                    ['action' => 'create_saas_checkout']
                );
                return;
            }

            // Obtém tenant_id da sessão (usuário já está logado após registro)
            $tenantId = Flight::get('tenant_id');
            if (!$tenantId) {
                ResponseHelper::sendError(
                    401,
                    'Não autenticado',
                    'Você precisa estar logado para assinar um plano',
                    'NOT_AUTHENTICATED'
                );
                return;
            }

            // Busca ou cria customer no Stripe para este tenant
            // O customer representa a CLÍNICA que está assinando o SaaS
            $customer = $this->paymentService->getOrCreateCustomer(
                $tenantId,
                Flight::get('user')['email'] ?? null,
                Flight::get('user')['name'] ?? null
            );

            // Cria sessão de checkout
            $checkoutSession = $this->stripeService->createCheckoutSession([
                'customer_id' => $customer['stripe_customer_id'],
                'line_items' => [[
                    'price' => $data['price_id'],
                    'quantity' => 1
                ]],
                'mode' => 'subscription',
                'success_url' => $data['success_url'],
                'cancel_url' => $data['cancel_url'],
                'metadata' => [
                    'tenant_id' => $tenantId,
                    'type' => 'saas_subscription'
                ],
                'payment_method_collection' => 'always'
            ]);

            Logger::info("Checkout SaaS criado", [
                'tenant_id' => $tenantId,
                'session_id' => $checkoutSession->id,
                'price_id' => $data['price_id']
            ]);

            ResponseHelper::sendSuccess([
                'session_id' => $checkoutSession->id,
                'url' => $checkoutSession->url
            ], 'Checkout criado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar checkout',
                'CREATE_CHECKOUT_ERROR',
                ['action' => 'create_saas_checkout']
            );
        }
    }
}

