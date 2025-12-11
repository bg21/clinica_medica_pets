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
     * IMPORTANTE: Usa a conta da PLATAFORMA (STRIPE_SECRET do .env), não a conta do tenant
     */
    public function listPlans(): void
    {
        try {
            // ✅ GARANTE que está usando a conta da PLATAFORMA (não a do tenant)
            // O StripeService injetado já usa a conta padrão (STRIPE_SECRET do .env)
            // Mas vamos garantir explicitamente que não está usando forTenant()
            
            // Busca produtos e preços do Stripe da conta da PLATAFORMA
            // Estes são os planos que VOCÊ vende para as clínicas
            try {
                // ✅ Invalida cache para garantir dados atualizados
                // (pode ser removido depois, mas ajuda na depuração)
                $queryParams = Flight::request()->query ?? [];
                $forceRefresh = isset($queryParams['refresh']) && filter_var($queryParams['refresh'], FILTER_VALIDATE_BOOLEAN);
                
                if ($forceRefresh) {
                    // Invalida cache de produtos e preços usando métodos do StripeService
                    $this->stripeService->invalidateProductsCache();
                    $this->stripeService->invalidatePricesCache();
                }
                
                $products = $this->stripeService->listProducts(['active' => true]);
                $prices = $this->stripeService->listPrices(['active' => true]);
                
                \App\Services\Logger::info("Planos SaaS buscados da conta da plataforma", [
                    'products_count' => isset($products->data) ? count($products->data) : 0,
                    'prices_count' => isset($prices->data) ? count($prices->data) : 0,
                    'using_platform_account' => true,
                    'force_refresh' => $forceRefresh,
                    'product_ids' => isset($products->data) ? array_map(function($p) { return $p->id; }, $products->data) : [],
                    'price_ids' => isset($prices->data) ? array_map(function($p) { return $p->id; }, $prices->data) : []
                ]);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                \App\Services\Logger::error("Erro ao buscar produtos/preços do Stripe", [
                    'error' => $e->getMessage(),
                    'stripe_error_code' => $e->getStripeCode(),
                    'trace' => $e->getTraceAsString(),
                    'using_platform_account' => true
                ]);
                ResponseHelper::sendStripeError($e, 'Erro ao buscar planos do Stripe', ['action' => 'list_plans']);
                return;
            }

            // Verifica se os dados foram retornados corretamente
            if (!isset($prices->data) || !is_array($prices->data)) {
                \App\Services\Logger::error("Dados inválidos retornados do Stripe", [
                    'prices_type' => gettype($prices),
                    'prices_data' => isset($prices->data) ? gettype($prices->data) : 'not_set'
                ]);
                ResponseHelper::sendError('Dados inválidos retornados do Stripe', 'INVALID_STRIPE_DATA', 500);
                return;
            }

            // Filtra apenas preços recorrentes (assinaturas)
            $recurringPrices = array_filter($prices->data, function($price) {
                return isset($price->recurring) && $price->recurring !== null;
            });
            
            // ✅ LOG DETALHADO: Verifica cada preço para entender o problema
            $priceDetails = [];
            foreach ($prices->data as $price) {
                $priceDetails[] = [
                    'id' => $price->id,
                    'active' => $price->active ?? false,
                    'type' => $price->type ?? 'unknown',
                    'has_recurring' => isset($price->recurring) && $price->recurring !== null,
                    'recurring_interval' => isset($price->recurring) ? ($price->recurring->interval ?? null) : null,
                    'product_id' => is_string($price->product) ? $price->product : ($price->product->id ?? null)
                ];
            }
            
            \App\Services\Logger::info("Preços filtrados", [
                'total_prices' => count($prices->data),
                'recurring_prices' => count($recurringPrices),
                'recurring_price_ids' => array_map(function($p) { return $p->id; }, $recurringPrices),
                'all_prices_details' => $priceDetails
            ]);
            
            // ✅ Se não há preços recorrentes, retorna array vazio (não erro)
            if (count($recurringPrices) === 0) {
                \App\Services\Logger::warning("Nenhum preço recorrente encontrado", [
                    'total_prices' => count($prices->data),
                    'price_details' => $priceDetails
                ]);
                
                // Retorna array vazio, não erro
                ResponseHelper::sendSuccess([], 'Nenhum plano de assinatura encontrado. Certifique-se de que os produtos têm preços recorrentes (mensais ou anuais) ativos.');
                return;
            }

            // Agrupa preços por produto
            $plans = [];
            foreach ($recurringPrices as $price) {
                try {
                    $productId = is_string($price->product) ? $price->product : $price->product->id;
                    
                    // Busca produto
                    $product = null;
                    if (isset($products->data) && is_array($products->data)) {
                        foreach ($products->data as $p) {
                            if ($p->id === $productId) {
                                $product = $p;
                                break;
                            }
                        }
                    }
                    
                    // ✅ Se produto não encontrado, pula este preço (não mostra "Produto não encontrado")
                    if (!$product) {
                        \App\Services\Logger::warning("Produto não encontrado para preço - preço será ignorado", [
                            'price_id' => $price->id,
                            'product_id' => $productId,
                            'available_product_ids' => array_map(function($p) { return $p->id; }, $products->data ?? [])
                        ]);
                        continue; // Pula este preço, não adiciona à lista
                    }

                    // ✅ GARANTE que unit_amount é um número inteiro ANTES de converter para array
                    // O Stripe retorna unit_amount em centavos (ex: R$ 29,00 = 2900 centavos)
                    // IMPORTANTE: Captura o valor DIRETO do objeto Stripe antes de toArray()
                    $unitAmountRaw = $price->unit_amount ?? null;
                    $unitAmount = $unitAmountRaw !== null ? (int)$unitAmountRaw : null;
                    
                    // ✅ LOG CRÍTICO: Verifica o valor antes de processar
                    \App\Services\Logger::info("Preço antes de processar", [
                        'price_id' => $price->id,
                        'unit_amount_raw' => $unitAmountRaw,
                        'unit_amount_raw_type' => gettype($unitAmountRaw),
                        'unit_amount_converted' => $unitAmount,
                        'currency' => $price->currency ?? null
                    ]);
                    
                    // Adiciona metadata do produto ao preço
                    $priceData = $price->toArray();
                    
                    // ✅ FORÇA unit_amount como inteiro (garante que não seja string)
                    // IMPORTANTE: O Stripe SEMPRE retorna unit_amount em centavos
                    // Se o valor no toArray() estiver diferente, usa o valor direto do objeto
                    if ($unitAmount !== null) {
                        $priceData['unit_amount'] = $unitAmount;
                    } else {
                        // Se não tinha unit_amount, verifica se toArray() retornou algo diferente
                        if (isset($priceData['unit_amount'])) {
                            $priceData['unit_amount'] = (int)$priceData['unit_amount'];
                        }
                    }
                    
                    // ✅ LOG FINAL: Verifica o valor que será enviado
                    \App\Services\Logger::info("Preço processado - valor final", [
                        'price_id' => $price->id,
                        'unit_amount_final' => $priceData['unit_amount'] ?? null,
                        'unit_amount_final_type' => gettype($priceData['unit_amount'] ?? null),
                        'currency' => $priceData['currency'] ?? null,
                        'product_name' => $product->name ?? null,
                        'valor_em_reais' => $unitAmount !== null ? ($unitAmount / 100) : null
                    ]);
                    
                    // Adiciona produto ao preço
                    $priceData['product'] = $product->toArray();

                    $plans[] = $priceData;
                } catch (\Exception $e) {
                    \App\Services\Logger::warning("Erro ao processar preço", [
                        'price_id' => $price->id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continua processando outros preços
                }
            }
            
            \App\Services\Logger::info("Planos processados", [
                'total_plans' => count($plans),
                'plan_ids' => array_map(function($p) { return $p['id'] ?? 'unknown'; }, $plans)
            ]);

            // Ordena por valor (menor para maior)
            usort($plans, function($a, $b) {
                return ($a['unit_amount'] ?? 0) - ($b['unit_amount'] ?? 0);
            });
            
            // ✅ LOG FINAL: Verifica os valores que serão enviados
            \App\Services\Logger::info("Planos finais preparados para envio", [
                'total_plans' => count($plans),
                'plans_summary' => array_map(function($plan) {
                    return [
                        'id' => $plan['id'] ?? 'unknown',
                        'unit_amount' => $plan['unit_amount'] ?? null,
                        'unit_amount_type' => gettype($plan['unit_amount'] ?? null),
                        'currency' => $plan['currency'] ?? null,
                        'product_name' => $plan['product']['name'] ?? 'N/A'
                    ];
                }, $plans)
            ]);

            ResponseHelper::sendSuccess($plans, 'Planos carregados com sucesso');
        } catch (\Exception $e) {
            \App\Services\Logger::error("Erro ao listar planos", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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

            // ✅ GARANTE que está usando a conta da PLATAFORMA para criar checkout
            // O StripeService injetado já usa a conta padrão (STRIPE_SECRET do .env)
            // Isso garante que a assinatura será criada na conta da plataforma
            
            // Cria sessão de checkout na conta da PLATAFORMA
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
            
            Logger::info("Checkout SaaS criado na conta da plataforma", [
                'tenant_id' => $tenantId,
                'session_id' => $checkoutSession->id,
                'price_id' => $data['price_id'],
                'using_platform_account' => true
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

