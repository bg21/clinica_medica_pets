<?php

namespace App\Controllers;

use App\Services\StripeService;
use App\Services\Logger;
use App\Utils\ResponseHelper;
use App\Utils\ErrorHandler;
use Flight;
use Config;

/**
 * Controller para gerenciar preços (prices) do Stripe
 */
class PriceController
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Lista preços disponíveis
     * GET /v1/prices
     * 
     * Query params opcionais:
     *   - limit: Número máximo de resultados (padrão: 10)
     *   - starting_after: ID do preço para paginação
     *   - ending_before: ID do preço para paginação reversa
     *   - active: true/false para filtrar apenas preços ativos/inativos
     *   - type: 'one_time' ou 'recurring' para filtrar por tipo
     *   - product: ID do produto para filtrar preços de um produto específico
     *   - currency: Código da moeda (ex: 'brl', 'usd')
     */
    public function list(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // Se for administrador SaaS, busca diretamente do Stripe (conta principal)
            if ($isSaasAdmin && $tenantId === null) {
                // ✅ CORREÇÃO: Flight::request()->query retorna Collection, precisa converter para array
                try {
                    $queryParams = Flight::request()->query->getData();
                    if (!is_array($queryParams)) {
                        $queryParams = [];
                    }
                } catch (\Exception $e) {
                    error_log("Erro ao obter query params: " . $e->getMessage());
                    $queryParams = [];
                }
                
                $options = [];
                
                // Processa query params
                if (isset($queryParams['limit'])) {
                    $options['limit'] = (int)$queryParams['limit'];
                }
                
                if (!empty($queryParams['starting_after'])) {
                    $options['starting_after'] = $queryParams['starting_after'];
                }
                
                if (!empty($queryParams['ending_before'])) {
                    $options['ending_before'] = $queryParams['ending_before'];
                }
                
                if (isset($queryParams['active'])) {
                    $options['active'] = filter_var($queryParams['active'], FILTER_VALIDATE_BOOLEAN);
                }
                
                if (!empty($queryParams['type'])) {
                    $options['type'] = $queryParams['type'];
                }
                
                if (!empty($queryParams['product'])) {
                    $options['product'] = $queryParams['product'];
                }
                
                if (!empty($queryParams['currency'])) {
                    $options['currency'] = $queryParams['currency'];
                }
                
                // Busca diretamente do Stripe usando conta principal
                $prices = $this->stripeService->listPrices($options);
                
                $pricesData = [];
                foreach ($prices->data as $price) {
                    $productId = is_string($price->product) ? $price->product : $price->product->id;
                    $productName = 'Produto não encontrado';
                    
                    // Tenta buscar nome do produto se não estiver expandido
                    if (is_string($price->product)) {
                        try {
                            $product = $this->stripeService->getProduct($productId);
                            $productName = $product->name ?? $productName;
                        } catch (\Exception $e) {
                            // Ignora erro ao buscar produto
                        }
                    } elseif (isset($price->product->name)) {
                        $productName = $price->product->name;
                    }
                    
                    $pricesData[] = [
                        'id' => $price->id,
                        'product' => $productId,
                        'product_name' => $productName,
                        'active' => $price->active,
                        'currency' => strtoupper($price->currency),
                        'unit_amount' => $price->unit_amount ? (float)($price->unit_amount / 100) : 0, // ✅ CORREÇÃO: Garante que é float, já convertido para reais
                        'amount' => $price->unit_amount ? (float)($price->unit_amount / 100) : 0, // ✅ CORREÇÃO: Adiciona amount para compatibilidade
                        'type' => $price->type,
                        'recurring' => $price->recurring ? [
                            'interval' => $price->recurring->interval,
                            'interval_count' => $price->recurring->interval_count
                        ] : null,
                        'metadata' => (array)$price->metadata,
                        'created' => date('Y-m-d H:i:s', $price->created)
                    ];
                }
                
                ResponseHelper::sendSuccess($pricesData, 200, null, [
                    'has_more' => $prices->has_more,
                    'total' => count($pricesData)
                ]);
                return;
            }
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_prices']);
                return;
            }
            
            // ✅ CORREÇÃO: Flight::request()->query retorna Collection, precisa converter para array
            try {
                $queryParams = Flight::request()->query->getData();
                if (!is_array($queryParams)) {
                    $queryParams = [];
                }
            } catch (\Exception $e) {
                error_log("Erro ao obter query params: " . $e->getMessage());
                $queryParams = [];
            }
            
            $options = [];
            
            // Processa query params
            if (isset($queryParams['limit'])) {
                $options['limit'] = (int)$queryParams['limit'];
            }
            
            if (!empty($queryParams['starting_after'])) {
                $options['starting_after'] = $queryParams['starting_after'];
            }
            
            if (!empty($queryParams['ending_before'])) {
                $options['ending_before'] = $queryParams['ending_before'];
            }
            
            if (isset($queryParams['active'])) {
                $options['active'] = filter_var($queryParams['active'], FILTER_VALIDATE_BOOLEAN);
            }
            
            if (!empty($queryParams['type'])) {
                $options['type'] = $queryParams['type'];
            }
            
            if (!empty($queryParams['product'])) {
                $options['product'] = $queryParams['product'];
            }
            
            if (!empty($queryParams['currency'])) {
                $options['currency'] = $queryParams['currency'];
            }
            
            // Opção para ignorar filtro de tenant (útil para formulários de criação)
            $ignoreTenantFilter = isset($queryParams['all_tenants']) && filter_var($queryParams['all_tenants'], FILTER_VALIDATE_BOOLEAN);
            
            // ✅ CACHE: Gera chave única baseada em parâmetros (incluindo tenant_id)
            $cacheKey = sprintf(
                'prices:list:%d:%s:%s:%s:%s:%s:%s:%s:%s',
                $tenantId ?? 0,
                $options['limit'] ?? 10,
                md5($options['starting_after'] ?? ''),
                md5($options['ending_before'] ?? ''),
                ($options['active'] ?? '') === true ? '1' : (($options['active'] ?? '') === false ? '0' : ''),
                $options['type'] ?? '',
                $options['product'] ?? '',
                $options['currency'] ?? '',
                $ignoreTenantFilter ? '1' : '0'
            );
            
            // ✅ CORREÇÃO: Parâmetro para forçar refresh do cache
            $forceRefresh = isset($queryParams['refresh']) && filter_var($queryParams['refresh'], FILTER_VALIDATE_BOOLEAN);
            
            // ✅ Tenta obter do cache (TTL: 60 segundos) - apenas se não forçar refresh
            $cached = null;
            if (!$forceRefresh) {
                $cached = \App\Services\CacheService::getJson($cacheKey);
            }
            
            if ($cached !== null) {
                // ✅ CORREÇÃO: Se cache tem formato antigo {data: [...], meta: {...}}, converte
                if (isset($cached['data']) && isset($cached['meta'])) {
                    Flight::json([
                        'success' => true,
                        'data' => $cached['data'],
                        'meta' => $cached['meta']
                    ]);
                } else {
                    // Formato novo (já é array direto)
                    Flight::json([
                        'success' => true,
                        'data' => $cached,
                        'meta' => []
                    ]);
                }
                return;
            }
            
            // ✅ CORREÇÃO: Usa conta Stripe da clínica para listar preços
            $clinicStripeService = \App\Services\StripeService::forTenant($tenantId);
            
            // ✅ CORREÇÃO: Não usa expand (causa erro no Stripe)
            // Busca o produto individualmente quando necessário (já tem fallback no código)
            // O Stripe não permite expandir 'product' ou 'data.product' na listagem de prices
            // Removemos o expand e deixamos o código buscar o produto quando necessário
            
            $prices = $clinicStripeService->listPrices($options);
            
            Logger::debug("Preços retornados do Stripe", [
                'tenant_id' => $tenantId,
                'count' => count($prices->data),
                'has_more' => $prices->has_more ?? false,
                'force_refresh' => $forceRefresh ?? false
            ]);
            
            // Formata resposta e filtra por tenant_id (via metadata do produto associado)
            $formattedPrices = [];
            foreach ($prices->data as $price) {
                // ✅ CORREÇÃO: Filtra prices por tenant_id do produto associado
                if (!$ignoreTenantFilter && $tenantId !== null) {
                    // Obtém o produto associado ao price
                    $product = null;
                    if (isset($price->product) && is_object($price->product)) {
                        $product = $price->product;
                    } elseif (is_string($price->product)) {
                        // Se produto não foi expandido, busca
                        try {
                            $product = $clinicStripeService->getProduct($price->product);
                        } catch (\Exception $e) {
                            // Se não conseguir buscar o produto, pula o price
                            Logger::warning("Produto não encontrado para price", [
                                'price_id' => $price->id,
                                'product_id' => $price->product,
                                'error' => $e->getMessage()
                            ]);
                            continue;
                        }
                    }
                    
                    // Se produto não foi encontrado, pula o price
                    if (!$product) {
                        continue;
                    }
                    
                    // Filtra por tenant_id do produto (se tiver metadata tenant_id)
                    $metadata = $product->metadata->toArray();
                    if (!empty($metadata) && isset($metadata['tenant_id']) && $metadata['tenant_id'] !== null && $metadata['tenant_id'] !== '') {
                        // Só filtra se tiver tenant_id definido e for diferente
                        if ((string)$metadata['tenant_id'] !== (string)$tenantId) {
                            continue; // Pula prices de produtos de outros tenants
                        }
                    }
                    // Se não tiver tenant_id nos metadados, inclui o price (prices antigos ou compartilhados)
                    
                    // ✅ CORREÇÃO: Se estiver filtrando por active=true, também verifica se o produto está ativo
                    // Isso garante consistência: se o price aparece, o produto também deve aparecer na lista de produtos
                    if (isset($options['active']) && $options['active'] === true) {
                        if (!$product->active) {
                            continue; // Pula prices de produtos inativos quando filtrando apenas ativos
                        }
                    }
                }
                $priceData = [
                    'id' => $price->id,
                    'active' => $price->active,
                    'currency' => strtoupper($price->currency),
                    'type' => $price->type,
                    'unit_amount' => $price->unit_amount,
                    'unit_amount_decimal' => $price->unit_amount_decimal,
                    'formatted_amount' => number_format($price->unit_amount / 100, 2, ',', '.'),
                    'created' => date('Y-m-d H:i:s', $price->created),
                    'metadata' => $price->metadata->toArray()
                ];
                
                // Adiciona informações de recorrência se for recurring
                if ($price->type === 'recurring' && isset($price->recurring)) {
                    $priceData['recurring'] = [
                        'interval' => $price->recurring->interval,
                        'interval_count' => $price->recurring->interval_count,
                        'trial_period_days' => $price->recurring->trial_period_days ?? null
                    ];
                }
                
                // Adiciona informações do produto se expandido
                if (isset($price->product) && is_object($price->product)) {
                    $priceData['product'] = [
                        'id' => $price->product->id,
                        'name' => $price->product->name ?? null,
                        'description' => $price->product->description ?? null,
                        'active' => $price->product->active ?? null
                    ];
                } elseif (is_string($price->product)) {
                    $priceData['product_id'] = $price->product;
                }
                
                $formattedPrices[] = $priceData;
            }
            
            // ✅ CORREÇÃO: Retorna array diretamente, meta separado
            $meta = [
                'has_more' => $prices->has_more,
                'count' => count($formattedPrices)
            ];
            
            // ✅ Salva no cache (formato completo para compatibilidade)
            $cacheData = [
                'data' => $formattedPrices,
                'meta' => $meta
            ];
            \App\Services\CacheService::setJson($cacheKey, $cacheData, 60);
            
            // ✅ Retorna array diretamente em data
            Flight::json([
                'success' => true,
                'data' => $formattedPrices,
                'meta' => $meta
            ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            ResponseHelper::sendStripeError(
                $e,
                'Erro ao listar preços',
                ['action' => 'list_prices', 'query_params' => $queryParams ?? [], 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar preços',
                'PRICES_LIST_ERROR',
                ['action' => 'list_prices', 'query_params' => $queryParams ?? [], 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Cria um novo preço
     * POST /v1/prices
     * 
     * Body:
     *   - product (obrigatório): ID do produto
     *   - unit_amount (obrigatório): Valor em centavos (ex: 2000 = $20.00)
     *   - currency (obrigatório): Código da moeda (ex: 'brl', 'usd')
     *   - recurring (opcional): Para preços recorrentes { interval: 'month'|'year'|'week'|'day', interval_count: int, trial_period_days: int }
     *   - active (opcional): Se o preço está ativo (padrão: true)
     *   - metadata (opcional): Metadados
     *   - nickname (opcional): Apelido do preço
     */
    public function create(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_price']);
                return;
            }

            // ✅ OTIMIZAÇÃO: Usa RequestCache para evitar múltiplas leituras
            $data = \App\Utils\RequestCache::getJsonInput();
            
            // ✅ SEGURANÇA: Valida se JSON foi decodificado corretamente
            if ($data === null) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    ResponseHelper::sendInvalidJsonError(['action' => 'create_price']);
                    return;
                }
                $data = [];
            }

            // Validações obrigatórias
            $errors = [];
            if (empty($data['product'])) {
                $errors['product'] = 'Campo product é obrigatório';
            }

            if (!isset($data['unit_amount'])) {
                $errors['unit_amount'] = 'Campo unit_amount é obrigatório';
            }

            if (empty($data['currency'])) {
                $errors['currency'] = 'Campo currency é obrigatório';
            }
            
            if (!empty($errors)) {
                ResponseHelper::sendValidationError('Dados inválidos', $errors, ['action' => 'create_price', 'tenant_id' => $tenantId]);
                return;
            }

            // Adiciona tenant_id aos metadados se não existir
            if (!isset($data['metadata'])) {
                $data['metadata'] = [];
            }
            $data['metadata']['tenant_id'] = $tenantId;

            // ✅ CORREÇÃO: Usa conta Stripe da clínica (não a conta do usuário)
            // Preços criados pela clínica devem ir para a conta Stripe da clínica
            $clinicStripeService = \App\Services\StripeService::forTenant($tenantId);
            $price = $clinicStripeService->createPrice($data);
            
            Logger::info("Preço criado na conta Stripe da clínica", [
                'tenant_id' => $tenantId,
                'price_id' => $price->id,
                'product_id' => $price->product
            ]);

            // ✅ CORREÇÃO: Invalida cache de preços após criar
            $clinicStripeService->invalidatePricesCache();
            
            // ✅ CORREÇÃO: Limpa cache do CacheService (Redis/Memória) para este tenant
            try {
                $redis = \App\Services\CacheService::getRedisClient();
                if ($redis) {
                    // Busca todas as chaves de cache de preços deste tenant
                    $keys = $redis->keys('prices:list:' . $tenantId . ':*');
                    if (!empty($keys)) {
                        $redis->del($keys);
                        Logger::debug("Cache de preços invalidado após criação", [
                            'tenant_id' => $tenantId,
                            'price_id' => $price->id,
                            'keys_deleted' => count($keys)
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Logger::warning("Erro ao invalidar cache de preços (Redis)", [
                    'tenant_id' => $tenantId,
                    'price_id' => $price->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Formata resposta
            $priceData = [
                'id' => $price->id,
                'active' => $price->active,
                'currency' => strtoupper($price->currency),
                'type' => $price->type,
                'unit_amount' => $price->unit_amount,
                'unit_amount_decimal' => $price->unit_amount_decimal,
                'formatted_amount' => number_format($price->unit_amount / 100, 2, ',', '.'),
                'nickname' => $price->nickname ?? null,
                'created' => date('Y-m-d H:i:s', $price->created),
                'metadata' => $price->metadata->toArray()
            ];

            // Adiciona informações de recorrência se for recurring
            if ($price->type === 'recurring' && isset($price->recurring)) {
                $priceData['recurring'] = [
                    'interval' => $price->recurring->interval,
                    'interval_count' => $price->recurring->interval_count,
                    'trial_period_days' => $price->recurring->trial_period_days ?? null
                ];
            }

            // Adiciona informações do produto
            if (is_string($price->product)) {
                $priceData['product_id'] = $price->product;
            } elseif (is_object($price->product)) {
                $priceData['product'] = [
                    'id' => $price->product->id,
                    'name' => $price->product->name ?? null,
                    'description' => $price->product->description ?? null,
                    'active' => $price->product->active ?? null
                ];
            }

            ResponseHelper::sendCreated($priceData, 'Preço criado com sucesso');
        } catch (\InvalidArgumentException $e) {
            ResponseHelper::sendValidationError($e->getMessage(), [], ['action' => 'create_price', 'tenant_id' => $tenantId ?? null]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ResponseHelper::sendStripeError($e, 'Erro ao criar preço', ['action' => 'create_price', 'tenant_id' => $tenantId ?? null]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao criar preço', 'PRICE_CREATE_ERROR', ['action' => 'create_price', 'tenant_id' => $tenantId ?? null]);
        }
    }

    /**
     * Obtém preço específico
     * GET /v1/prices/:id
     */
    public function get(string $id): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // Se for administrador SaaS, busca diretamente do Stripe (conta principal)
            if ($isSaasAdmin && $tenantId === null) {
                // Busca diretamente do Stripe usando conta principal
                try {
                    $price = $this->stripeService->getPrice($id);
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    if ($e->getStripeCode() === 'resource_missing') {
                        ResponseHelper::sendNotFoundError('Preço', ['action' => 'get_price', 'price_id' => $id]);
                        return;
                    }
                    throw $e;
                }
                
                // Busca nome do produto
                $productId = is_string($price->product) ? $price->product : $price->product->id;
                $productName = 'Produto não encontrado';
                if (is_string($price->product)) {
                    try {
                        $product = $this->stripeService->getProduct($productId);
                        $productName = $product->name ?? $productName;
                    } catch (\Exception $e) {
                        // Ignora erro ao buscar produto
                    }
                } elseif (isset($price->product->name)) {
                    $productName = $price->product->name;
                }
                
                // Formata resposta
                $priceData = [
                    'id' => $price->id,
                    'active' => $price->active,
                    'currency' => strtoupper($price->currency),
                    'type' => $price->type,
                    'unit_amount' => (float)($price->unit_amount / 100), // ✅ CORREÇÃO: Já convertido para reais
                    'amount' => (float)($price->unit_amount / 100), // ✅ CORREÇÃO: Adiciona amount para compatibilidade
                    'unit_amount_decimal' => $price->unit_amount_decimal,
                    'formatted_amount' => number_format($price->unit_amount / 100, 2, ',', '.'),
                    'nickname' => $price->nickname ?? null,
                    'created' => date('Y-m-d H:i:s', $price->created),
                    'metadata' => (array)$price->metadata
                ];
                
                // Adiciona informações de recorrência se for recurring
                if ($price->type === 'recurring' && isset($price->recurring)) {
                    $priceData['recurring'] = [
                        'interval' => $price->recurring->interval,
                        'interval_count' => $price->recurring->interval_count,
                        'trial_period_days' => $price->recurring->trial_period_days ?? null
                    ];
                }
                
                // Adiciona informações do produto
                $priceData['product'] = [
                    'id' => $productId,
                    'name' => $productName
                ];
                $priceData['product_id'] = $productId;
                $priceData['product_name'] = $productName;
                
                ResponseHelper::sendSuccess($priceData);
                return;
            }
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_price']);
                return;
            }

            // ✅ CORREÇÃO: Usa conta Stripe da clínica
            $clinicStripeService = \App\Services\StripeService::forTenant($tenantId);
            
            // ✅ CORREÇÃO: Tenta obter o preço do Stripe
            try {
                $price = $clinicStripeService->getPrice($id);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Se o preço não existe no Stripe, retorna erro
                if ($e->getStripeCode() === 'resource_missing') {
                    error_log("Preço não encontrado no Stripe: {$id} (tenant_id: {$tenantId})");
                    ResponseHelper::sendNotFoundError('Preço', ['action' => 'get_price', 'price_id' => $id, 'tenant_id' => $tenantId]);
                    return;
                }
                throw $e; // Re-lança outras exceções do Stripe
            }

            // ✅ CORREÇÃO: Valida se o preço pertence ao tenant (via metadata)
            // Se o preço tem tenant_id nos metadados, valida se é do tenant atual
            // Se não tem tenant_id, permite acesso (preços antigos ou compartilhados)
            $metadata = $price->metadata->toArray();
            if (!empty($metadata) && isset($metadata['tenant_id']) && $metadata['tenant_id'] !== null && $metadata['tenant_id'] !== '') {
                // Preço tem tenant_id definido, valida se pertence ao tenant atual
                if ((string)$metadata['tenant_id'] !== (string)$tenantId) {
                    error_log("Preço pertence a outro tenant: {$id} (tenant_id do preço: {$metadata['tenant_id']}, tenant_id atual: {$tenantId})");
                    ResponseHelper::sendNotFoundError('Preço', ['action' => 'get_price', 'price_id' => $id, 'tenant_id' => $tenantId]);
                    return;
                }
            }
            // Se não tem tenant_id, permite acesso (preços antigos ou compartilhados)

            // Formata resposta
            $priceData = [
                'id' => $price->id,
                'active' => $price->active,
                'currency' => strtoupper($price->currency),
                'type' => $price->type,
                'unit_amount' => $price->unit_amount,
                'unit_amount_decimal' => $price->unit_amount_decimal,
                'formatted_amount' => number_format($price->unit_amount / 100, 2, ',', '.'),
                'nickname' => $price->nickname ?? null,
                'created' => date('Y-m-d H:i:s', $price->created),
                'metadata' => $metadata
            ];

            // Adiciona informações de recorrência se for recurring
            if ($price->type === 'recurring' && isset($price->recurring)) {
                $priceData['recurring'] = [
                    'interval' => $price->recurring->interval,
                    'interval_count' => $price->recurring->interval_count,
                    'trial_period_days' => $price->recurring->trial_period_days ?? null
                ];
            }

            // Adiciona informações do produto
            if (is_string($price->product)) {
                $priceData['product_id'] = $price->product;
            } elseif (is_object($price->product)) {
                $priceData['product'] = [
                    'id' => $price->product->id,
                    'name' => $price->product->name ?? null,
                    'description' => $price->product->description ?? null,
                    'active' => $price->product->active ?? null
                ];
            }

            ResponseHelper::sendSuccess($priceData);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Tratamento adicional para outras exceções do Stripe
            if ($e->getStripeCode() === 'resource_missing') {
                error_log("Preço não encontrado no Stripe (catch externo): {$id} (tenant_id: {$tenantId})");
                ResponseHelper::sendNotFoundError('Preço', ['action' => 'get_price', 'price_id' => $id, 'tenant_id' => $tenantId]);
            } else {
                error_log("Erro do Stripe ao obter preço: {$e->getMessage()} (price_id: {$id}, tenant_id: {$tenantId})");
                ResponseHelper::sendStripeError($e, 'Erro ao obter preço', ['action' => 'get_price', 'price_id' => $id, 'tenant_id' => $tenantId]);
            }
        } catch (\Exception $e) {
            error_log("Erro genérico ao obter preço: {$e->getMessage()} (price_id: {$id}, tenant_id: {$tenantId})");
            ResponseHelper::sendGenericError($e, 'Erro ao obter preço', 'PRICE_GET_ERROR', ['action' => 'get_price', 'price_id' => $id, 'tenant_id' => $tenantId]);
        }
    }

    /**
     * Atualiza preço
     * PUT /v1/prices/:id
     * 
     * Nota: O Stripe não permite alterar o valor (unit_amount) ou moeda de um preço existente.
     * Apenas é possível atualizar: active, metadata, nickname.
     * 
     * Body:
     *   - active (opcional): Se o preço está ativo
     *   - metadata (opcional): Metadados
     *   - nickname (opcional): Apelido do preço
     */
    public function update(string $id): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // Se for administrador SaaS, usa conta principal
            if ($isSaasAdmin && $tenantId === null) {
                $data = \App\Utils\RequestCache::getJsonInput();
                
                if ($data === null) {
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        ResponseHelper::sendInvalidJsonError(['price_id' => $id]);
                        return;
                    }
                    $data = [];
                }
                
                // Atualiza preço usando conta principal
                $price = $this->stripeService->updatePrice($id, $data);
                
                // Invalida cache
                $this->stripeService->invalidatePricesCache();
                
                // Busca nome do produto
                $productId = is_string($price->product) ? $price->product : $price->product->id;
                $productName = 'Produto não encontrado';
                if (is_string($price->product)) {
                    try {
                        $product = $this->stripeService->getProduct($productId);
                        $productName = $product->name ?? $productName;
                    } catch (\Exception $e) {
                        // Ignora erro ao buscar produto
                    }
                } elseif (isset($price->product->name)) {
                    $productName = $price->product->name;
                }
                
                ResponseHelper::sendSuccess([
                    'id' => $price->id,
                    'active' => $price->active,
                    'currency' => strtoupper($price->currency),
                    'type' => $price->type,
                    'unit_amount' => (float)($price->unit_amount / 100),
                    'amount' => (float)($price->unit_amount / 100),
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'recurring' => $price->recurring ? [
                        'interval' => $price->recurring->interval,
                        'interval_count' => $price->recurring->interval_count
                    ] : null,
                    'metadata' => (array)$price->metadata
                ], 200, 'Preço atualizado com sucesso');
                return;
            }
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_price', 'price_id' => $id]);
                return;
            }

            // ✅ CORREÇÃO: Usa conta Stripe da clínica
            $clinicStripeService = \App\Services\StripeService::forTenant($tenantId);
            
            // Primeiro, verifica se o preço existe e pertence ao tenant
            $price = $clinicStripeService->getPrice($id);
            
            if (isset($price->metadata->tenant_id) && (string)$price->metadata->tenant_id !== (string)$tenantId) {
                ResponseHelper::sendNotFoundError('Preço', ['action' => 'update_price', 'price_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }

            // ✅ OTIMIZAÇÃO: Usa RequestCache para evitar múltiplas leituras
            $data = \App\Utils\RequestCache::getJsonInput();
            
            // ✅ SEGURANÇA: Valida se JSON foi decodificado corretamente
            if ($data === null) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    ResponseHelper::sendInvalidJsonError(['action' => 'create_price']);
                    return;
                }
                $data = [];
            }

            // Preserva tenant_id nos metadados se metadata for atualizado
            if (isset($data['metadata'])) {
                $data['metadata']['tenant_id'] = $tenantId;
            }

            $price = $clinicStripeService->updatePrice($id, $data);

            // ✅ CORREÇÃO: Invalida cache de preços após atualizar
            $clinicStripeService->invalidatePricesCache();
            
            // ✅ CORREÇÃO: Limpa cache do CacheService (Redis/Memória) para este tenant
            try {
                $redis = \App\Services\CacheService::getRedisClient();
                if ($redis) {
                    // Busca todas as chaves de cache de preços deste tenant
                    $keys = $redis->keys('prices:list:' . $tenantId . ':*');
                    if (!empty($keys)) {
                        $redis->del($keys);
                        Logger::debug("Cache de preços invalidado após atualização", [
                            'tenant_id' => $tenantId,
                            'price_id' => $id,
                            'keys_deleted' => count($keys)
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Logger::warning("Erro ao invalidar cache de preços (Redis)", [
                    'tenant_id' => $tenantId,
                    'price_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }

            // Formata resposta
            $priceData = [
                'id' => $price->id,
                'active' => $price->active,
                'currency' => strtoupper($price->currency),
                'type' => $price->type,
                'unit_amount' => $price->unit_amount,
                'unit_amount_decimal' => $price->unit_amount_decimal,
                'formatted_amount' => number_format($price->unit_amount / 100, 2, ',', '.'),
                'nickname' => $price->nickname ?? null,
                'created' => date('Y-m-d H:i:s', $price->created),
                'metadata' => $price->metadata->toArray()
            ];

            // Adiciona informações de recorrência se for recurring
            if ($price->type === 'recurring' && isset($price->recurring)) {
                $priceData['recurring'] = [
                    'interval' => $price->recurring->interval,
                    'interval_count' => $price->recurring->interval_count,
                    'trial_period_days' => $price->recurring->trial_period_days ?? null
                ];
            }

            // Adiciona informações do produto
            if (is_string($price->product)) {
                $priceData['product_id'] = $price->product;
            } elseif (is_object($price->product)) {
                $priceData['product'] = [
                    'id' => $price->product->id,
                    'name' => $price->product->name ?? null,
                    'description' => $price->product->description ?? null,
                    'active' => $price->product->active ?? null
                ];
            }

            ResponseHelper::sendSuccess($priceData, 200, 'Preço atualizado com sucesso');
        } catch (\InvalidArgumentException $e) {
            ResponseHelper::sendValidationError($e->getMessage(), [], ['action' => 'update_price', 'price_id' => $id, 'tenant_id' => $tenantId ?? null]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($e->getStripeCode() === 'resource_missing') {
                ResponseHelper::sendNotFoundError('Preço', ['action' => 'update_price', 'price_id' => $id, 'tenant_id' => $tenantId]);
            } else {
                ResponseHelper::sendStripeError($e, 'Erro ao atualizar preço', ['action' => 'update_price', 'price_id' => $id, 'tenant_id' => $tenantId]);
            }
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao atualizar preço', 'PRICE_UPDATE_ERROR', ['action' => 'update_price', 'price_id' => $id, 'tenant_id' => $tenantId ?? null]);
        }
    }

    /**
     * Deleta preço (desativa)
     * DELETE /v1/prices/:id
     * 
     * Nota: O Stripe não permite deletar preços completamente, apenas desativar.
     * Preços com assinaturas ativas não podem ser desativados.
     */
    public function delete(string $id): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // Se for administrador SaaS, usa conta principal
            if ($isSaasAdmin && $tenantId === null) {
                // Desativa preço usando conta principal (Stripe não permite deletar, apenas desativar)
                $this->stripeService->updatePrice($id, ['active' => false]);
                
                // Invalida cache
                $this->stripeService->invalidatePricesCache();
                
                ResponseHelper::sendSuccess(null, 200, 'Preço desativado com sucesso');
                return;
            }
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_price', 'price_id' => $id]);
                return;
            }

            // ✅ CORREÇÃO: Usa conta Stripe da clínica
            $clinicStripeService = \App\Services\StripeService::forTenant($tenantId);
            
            // Primeiro, verifica se o preço existe e pertence ao tenant
            $price = $clinicStripeService->getPrice($id);
            
            // Verifica se o preço pertence ao tenant (via produto)
            if ($price->product && is_string($price->product)) {
                $product = $clinicStripeService->getProduct($price->product);
                if (isset($product->metadata->tenant_id) && (string)$product->metadata->tenant_id !== (string)$tenantId) {
                    ResponseHelper::sendNotFoundError('Preço', ['price_id' => $id, 'tenant_id' => $tenantId]);
                    return;
                }
            }

            // Desativa o preço (Stripe não permite deletar completamente)
            $price = $clinicStripeService->updatePrice($id, ['active' => false]);

            // ✅ CORREÇÃO: Invalida cache de preços após desativar
            $clinicStripeService->invalidatePricesCache();
            
            // ✅ CORREÇÃO: Limpa cache do CacheService (Redis/Memória) para este tenant
            try {
                $redis = \App\Services\CacheService::getRedisClient();
                if ($redis) {
                    // Busca todas as chaves de cache de preços deste tenant
                    $keys = $redis->keys('prices:list:' . $tenantId . ':*');
                    if (!empty($keys)) {
                        $redis->del($keys);
                        Logger::debug("Cache de preços invalidado após exclusão", [
                            'tenant_id' => $tenantId,
                            'price_id' => $id,
                            'keys_deleted' => count($keys)
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Logger::warning("Erro ao invalidar cache de preços (Redis)", [
                    'tenant_id' => $tenantId,
                    'price_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }

            ResponseHelper::sendSuccess([
                'id' => $price->id,
                'active' => false
            ], 200, 'Preço desativado com sucesso');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($e->getStripeCode() === 'resource_missing') {
                ResponseHelper::sendNotFoundError('Preço', ['price_id' => $id, 'tenant_id' => $tenantId ?? null]);
            } else {
                ResponseHelper::sendStripeError(
                    $e,
                    'Erro ao desativar preço',
                    ['price_id' => $id, 'tenant_id' => $tenantId ?? null, 'action' => 'delete_price']
                );
            }
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao desativar preço',
                'PRICE_DELETE_ERROR',
                ['price_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

