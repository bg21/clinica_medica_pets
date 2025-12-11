<?php

namespace App\Controllers;

use App\Services\StripeService;
use App\Services\Logger;
use App\Utils\ResponseHelper;
use App\Utils\ErrorHandler;
use Flight;
use Config;

/**
 * Controller para gerenciar cupons de desconto
 */
class CouponController
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Cria um novo cupom
     * POST /v1/coupons
     * 
     * Body:
     *   - id (opcional): ID customizado
     *   - percent_off (opcional): Desconto percentual (0-100)
     *   - amount_off (opcional): Desconto em valor fixo (em centavos)
     *   - currency (opcional): Moeda para amount_off
     *   - duration (obrigatório): 'once', 'repeating', ou 'forever'
     *   - duration_in_months (opcional): Número de meses se duration for 'repeating'
     *   - max_redemptions (opcional): Número máximo de resgates
     *   - redeem_by (opcional): Data limite para resgate
     *   - name (opcional): Nome do cupom
     *   - metadata (opcional): Metadados
     */
    public function create(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Determina qual StripeService usar
            if ($isSaasAdmin && $tenantId === null) {
                $stripeService = $this->stripeService; // Conta principal
            } elseif ($tenantId !== null) {
                $stripeService = \App\Services\StripeService::forTenant($tenantId); // Conta da clínica
            } else {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_coupon']);
                return;
            }

            // ✅ OTIMIZAÇÃO: Usa RequestCache para evitar múltiplas leituras
            $data = \App\Utils\RequestCache::getJsonInput();
            
            // ✅ SEGURANÇA: Valida se JSON foi decodificado corretamente
            if ($data === null) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    ResponseHelper::sendInvalidJsonError(['action' => 'create_coupon']);
                    return;
                }
                $data = [];
            }

            // Validações obrigatórias
            $errors = [];
            if (empty($data['duration'])) {
                $errors['duration'] = 'Campo duration é obrigatório';
            }

            if (!isset($data['percent_off']) && !isset($data['amount_off'])) {
                $errors['discount'] = 'É necessário fornecer percent_off ou amount_off';
            }
            
            if (!empty($errors)) {
                ResponseHelper::sendValidationError('Dados inválidos', $errors, ['action' => 'create_coupon', 'tenant_id' => $tenantId]);
                return;
            }

            // Adiciona tenant_id aos metadados se não existir (apenas para clínicas)
            if (!$isSaasAdmin && $tenantId !== null) {
                if (!isset($data['metadata'])) {
                    $data['metadata'] = [];
                }
                $data['metadata']['tenant_id'] = $tenantId;
            }

            $coupon = $stripeService->createCoupon($data);

            ResponseHelper::sendCreated([
                'id' => $coupon->id,
                'name' => $coupon->name,
                'percent_off' => $coupon->percent_off,
                'amount_off' => $coupon->amount_off,
                'currency' => $coupon->currency,
                'duration' => $coupon->duration,
                'duration_in_months' => $coupon->duration_in_months,
                'max_redemptions' => $coupon->max_redemptions,
                'times_redeemed' => $coupon->times_redeemed,
                'redeem_by' => $coupon->redeem_by ? date('Y-m-d H:i:s', $coupon->redeem_by) : null,
                'valid' => $coupon->valid,
                'created' => date('Y-m-d H:i:s', $coupon->created),
                'metadata' => $coupon->metadata->toArray()
            ], 'Cupom criado com sucesso');
        } catch (\InvalidArgumentException $e) {
            ResponseHelper::sendValidationError($e->getMessage(), [], ['action' => 'create_coupon', 'tenant_id' => $tenantId ?? null]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            ResponseHelper::sendStripeError($e, 'Erro ao criar cupom', ['action' => 'create_coupon', 'tenant_id' => $tenantId ?? null]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao criar cupom', 'COUPON_CREATE_ERROR', ['action' => 'create_coupon', 'tenant_id' => $tenantId ?? null]);
        }
    }

    /**
     * Lista cupons disponíveis
     * GET /v1/coupons
     * 
     * Query params opcionais:
     *   - limit: Número máximo de resultados
     *   - starting_after: ID do cupom para paginação
     *   - ending_before: ID do cupom para paginação reversa
     */
    public function list(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Determina qual StripeService usar
            if ($isSaasAdmin && $tenantId === null) {
                $stripeService = $this->stripeService; // Conta principal
            } elseif ($tenantId !== null) {
                $stripeService = \App\Services\StripeService::forTenant($tenantId); // Conta da clínica
            } else {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_coupons']);
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
            
            if (isset($queryParams['limit'])) {
                $options['limit'] = (int)$queryParams['limit'];
            }
            
            if (!empty($queryParams['starting_after'])) {
                $options['starting_after'] = $queryParams['starting_after'];
            }
            
            if (!empty($queryParams['ending_before'])) {
                $options['ending_before'] = $queryParams['ending_before'];
            }
            
            $coupons = $stripeService->listCoupons($options);
            
            // Formata resposta
            $formattedCoupons = [];
            foreach ($coupons->data as $coupon) {
                // ✅ CORREÇÃO: Filtra apenas cupons do tenant (via metadata) - apenas para clínicas
                if (!$isSaasAdmin && $tenantId !== null) {
                    if (isset($coupon->metadata->tenant_id) && 
                        (string)$coupon->metadata->tenant_id !== (string)$tenantId) {
                        continue; // Pula cupons de outros tenants
                    }
                }
                
                $formattedCoupons[] = [
                    'id' => $coupon->id,
                    'name' => $coupon->name,
                    'percent_off' => $coupon->percent_off,
                    'amount_off' => $coupon->amount_off,
                    'currency' => $coupon->currency,
                    'duration' => $coupon->duration,
                    'duration_in_months' => $coupon->duration_in_months,
                    'max_redemptions' => $coupon->max_redemptions,
                    'times_redeemed' => $coupon->times_redeemed,
                    'redeem_by' => $coupon->redeem_by ? date('Y-m-d H:i:s', $coupon->redeem_by) : null,
                    'valid' => $coupon->valid,
                    'created' => date('Y-m-d H:i:s', $coupon->created),
                    'metadata' => $coupon->metadata->toArray()
                ];
            }
            
            // ✅ CORREÇÃO: Retorna array diretamente, meta separado
            Flight::json([
                'success' => true,
                'data' => $formattedCoupons,
                'meta' => [
                    'has_more' => $coupons->has_more,
                    'count' => count($formattedCoupons)
                ]
            ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            ResponseHelper::sendStripeError(
                $e,
                'Erro ao listar cupons',
                ['action' => 'list_coupons', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar cupons',
                'COUPONS_LIST_ERROR',
                ['action' => 'list_coupons', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém cupom por ID
     * GET /v1/coupons/:id
     */
    public function get(string $id): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Determina qual StripeService usar
            if ($isSaasAdmin && $tenantId === null) {
                $stripeService = $this->stripeService; // Conta principal
            } elseif ($tenantId !== null) {
                $stripeService = \App\Services\StripeService::forTenant($tenantId); // Conta da clínica
            } else {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_coupon']);
                return;
            }

            $coupon = $stripeService->getCoupon($id);
            
            // ✅ CORREÇÃO: Valida se pertence ao tenant (via metadata) - apenas para clínicas
            if (!$isSaasAdmin && $tenantId !== null) {
                if (isset($coupon->metadata->tenant_id) && 
                    (string)$coupon->metadata->tenant_id !== (string)$tenantId) {
                    ResponseHelper::sendNotFoundError('Cupom', ['action' => 'get_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId]);
                    return;
                }
            }

            ResponseHelper::sendSuccess([
                'id' => $coupon->id,
                'name' => $coupon->name,
                'percent_off' => $coupon->percent_off,
                'amount_off' => $coupon->amount_off,
                'currency' => $coupon->currency,
                'duration' => $coupon->duration,
                'duration_in_months' => $coupon->duration_in_months,
                'max_redemptions' => $coupon->max_redemptions,
                'times_redeemed' => $coupon->times_redeemed,
                'redeem_by' => $coupon->redeem_by ? date('Y-m-d H:i:s', $coupon->redeem_by) : null,
                'valid' => $coupon->valid,
                'created' => date('Y-m-d H:i:s', $coupon->created),
                'metadata' => $coupon->metadata->toArray()
            ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($e->getStripeCode() === 'resource_missing') {
                ResponseHelper::sendNotFoundError('Cupom', ['action' => 'get_coupon', 'coupon_id' => $id]);
            } else {
                ResponseHelper::sendStripeError(
                    $e,
                    'Erro ao obter cupom',
                    ['action' => 'get_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId ?? null]
                );
            }
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter cupom',
                'COUPON_GET_ERROR',
                ['action' => 'get_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Atualiza cupom
     * PUT /v1/coupons/:id
     * 
     * Nota: O Stripe não permite alterar campos principais de um cupom (percent_off, amount_off, duration, etc.).
     * Apenas é possível atualizar: name e metadata.
     * 
     * Body:
     *   - name (opcional): Nome do cupom
     *   - metadata (opcional): Metadados
     */
    public function update(string $id): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Determina qual StripeService usar
            if ($isSaasAdmin && $tenantId === null) {
                $stripeService = $this->stripeService; // Conta principal
            } elseif ($tenantId !== null) {
                $stripeService = \App\Services\StripeService::forTenant($tenantId); // Conta da clínica
            } else {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_coupon']);
                return;
            }

            // Primeiro, verifica se o cupom existe
            $coupon = $stripeService->getCoupon($id);
            
            // ✅ CORREÇÃO: Valida se pertence ao tenant (via metadata) - apenas para clínicas
            if (!$isSaasAdmin && $tenantId !== null) {
                if (isset($coupon->metadata->tenant_id) && 
                    (string)$coupon->metadata->tenant_id !== (string)$tenantId) {
                    ResponseHelper::sendNotFoundError('Cupom', ['action' => 'update_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId]);
                    return;
                }
            }

            // ✅ OTIMIZAÇÃO: Usa RequestCache para evitar múltiplas leituras
            $data = \App\Utils\RequestCache::getJsonInput();
            
            // ✅ SEGURANÇA: Valida se JSON foi decodificado corretamente
            if ($data === null) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    ResponseHelper::sendInvalidJsonError(['action' => 'create_coupon']);
                    return;
                }
                $data = [];
            }

            // Preserva tenant_id nos metadados se metadata for atualizado (apenas para clínicas)
            if (!$isSaasAdmin && $tenantId !== null && isset($data['metadata'])) {
                $data['metadata']['tenant_id'] = $tenantId;
            }

            $coupon = $stripeService->updateCoupon($id, $data);

            ResponseHelper::sendSuccess([
                'id' => $coupon->id,
                'name' => $coupon->name,
                'percent_off' => $coupon->percent_off,
                'amount_off' => $coupon->amount_off,
                'currency' => $coupon->currency,
                'duration' => $coupon->duration,
                'duration_in_months' => $coupon->duration_in_months,
                'max_redemptions' => $coupon->max_redemptions,
                'times_redeemed' => $coupon->times_redeemed,
                'redeem_by' => $coupon->redeem_by ? date('Y-m-d H:i:s', $coupon->redeem_by) : null,
                'valid' => $coupon->valid,
                'created' => date('Y-m-d H:i:s', $coupon->created),
                'metadata' => $coupon->metadata->toArray()
            ], 200, 'Cupom atualizado com sucesso');
        } catch (\InvalidArgumentException $e) {
            ResponseHelper::sendValidationError(
                $e->getMessage(),
                [],
                ['action' => 'update_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($e->getStripeCode() === 'resource_missing') {
                ResponseHelper::sendNotFoundError('Cupom', ['action' => 'update_coupon', 'coupon_id' => $id]);
            } else {
                ResponseHelper::sendStripeError(
                    $e,
                    'Erro ao atualizar cupom',
                    ['action' => 'update_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId ?? null]
                );
            }
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar cupom',
                'COUPON_UPDATE_ERROR',
                ['action' => 'update_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Deleta cupom
     * DELETE /v1/coupons/:id
     */
    public function delete(string $id): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Determina qual StripeService usar
            if ($isSaasAdmin && $tenantId === null) {
                $stripeService = $this->stripeService; // Conta principal
            } elseif ($tenantId !== null) {
                $stripeService = \App\Services\StripeService::forTenant($tenantId); // Conta da clínica
            } else {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_coupon']);
                return;
            }
            
            // Primeiro, verifica se o cupom existe e pertence ao tenant
            $coupon = $stripeService->getCoupon($id);
            
            // ✅ CORREÇÃO: Valida se pertence ao tenant (via metadata) - apenas para clínicas
            if (!$isSaasAdmin && $tenantId !== null) {
                if (isset($coupon->metadata->tenant_id) && 
                    (string)$coupon->metadata->tenant_id !== (string)$tenantId) {
                    ResponseHelper::sendNotFoundError('Cupom', ['action' => 'delete_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId]);
                    return;
                }
            }

            $coupon = $stripeService->deleteCoupon($id);

            ResponseHelper::sendSuccess([
                'id' => $coupon->id,
                'deleted' => $coupon->deleted
            ], 200, 'Cupom deletado com sucesso');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($e->getStripeCode() === 'resource_missing') {
                ResponseHelper::sendNotFoundError('Cupom', ['action' => 'delete_coupon', 'coupon_id' => $id]);
            } else {
                ResponseHelper::sendStripeError(
                    $e,
                    'Erro ao deletar cupom',
                    ['action' => 'delete_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId ?? null]
                );
            }
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao deletar cupom',
                'COUPON_DELETE_ERROR',
                ['action' => 'delete_coupon', 'coupon_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

