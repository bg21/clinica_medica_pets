<?php

namespace App\Controllers;

use App\Models\AppointmentPriceConfig;
use App\Models\Professional;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;

/**
 * Controller para gerenciar configurações de preços de agendamentos
 */
class AppointmentPriceConfigController
{
    private AppointmentPriceConfig $configModel;
    private Professional $professionalModel;

    public function __construct(
        AppointmentPriceConfig $configModel,
        Professional $professionalModel
    ) {
        $this->configModel = $configModel;
        $this->professionalModel = $professionalModel;
    }

    /**
     * Lista configurações de preço do tenant
     * GET /v1/clinic/appointment-price-config
     */
    public function list(): void
    {
        try {
            PermissionHelper::require('view_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_price_config']);
                return;
            }
            
            $queryParams = Flight::request()->query;
            $filters = [];
            
            if (!empty($queryParams['appointment_type'])) {
                $filters['appointment_type'] = $queryParams['appointment_type'];
            }
            if (!empty($queryParams['specialty'])) {
                $filters['specialty'] = $queryParams['specialty'];
            }
            if (isset($queryParams['professional_id'])) {
                $filters['professional_id'] = (int)$queryParams['professional_id'];
            }
            
            $configs = $this->configModel->findByTenant($tenantId, $filters);
            
            ResponseHelper::sendSuccess($configs);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar configurações de preço',
                'PRICE_CONFIG_LIST_ERROR',
                ['action' => 'list_price_config', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém preço sugerido para um agendamento
     * GET /v1/clinic/appointment-price-config/suggested-price
     */
    public function getSuggestedPrice(): void
    {
        try {
            PermissionHelper::require('view_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_suggested_price']);
                return;
            }
            
            $queryParams = Flight::request()->query;
            $appointmentType = $queryParams['appointment_type'] ?? null;
            $specialty = $queryParams['specialty'] ?? null;
            $professionalId = isset($queryParams['professional_id']) ? (int)$queryParams['professional_id'] : null;
            
            // Se professional_id fornecido, busca especialidade do profissional
            if ($professionalId !== null && empty($specialty)) {
                $professional = $this->professionalModel->findByTenantAndId($tenantId, $professionalId);
                if ($professional && !empty($professional['specialty'])) {
                    $specialty = $professional['specialty'];
                }
            }
            
            $suggestedPriceId = $this->configModel->findSuggestedPrice(
                $tenantId,
                $appointmentType,
                $specialty,
                $professionalId
            );
            
            ResponseHelper::sendSuccess([
                'price_id' => $suggestedPriceId,
                'suggested' => $suggestedPriceId !== null
            ]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter preço sugerido',
                'SUGGESTED_PRICE_ERROR',
                ['action' => 'get_suggested_price', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém configuração por ID
     * GET /v1/clinic/appointment-price-config/:id
     */
    public function get(string $id): void
    {
        try {
            PermissionHelper::require('view_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_price_config', 'config_id' => $id]);
                return;
            }

            $config = $this->configModel->findByTenantAndId($tenantId, (int)$id);

            if (!$config) {
                ResponseHelper::sendNotFoundError('Configuração de preço', ['action' => 'get_price_config', 'config_id' => $id]);
                return;
            }

            ResponseHelper::sendSuccess($config);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter configuração de preço',
                'PRICE_CONFIG_GET_ERROR',
                ['action' => 'get_price_config', 'config_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Cria uma nova configuração de preço
     * POST /v1/clinic/appointment-price-config
     */
    public function create(): void
    {
        try {
            PermissionHelper::require('create_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_price_config']);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'create_price_config']);
                return;
            }
            
            // Validação
            if (empty($data['price_id'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['price_id' => 'Obrigatório'],
                    ['action' => 'create_price_config']
                );
                return;
            }
            
            // Pelo menos um dos campos deve ser preenchido
            if (empty($data['appointment_type']) && empty($data['specialty']) && empty($data['professional_id'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    [
                        'appointment_type' => 'Pelo menos um campo deve ser preenchido (appointment_type, specialty ou professional_id)',
                        'specialty' => 'Pelo menos um campo deve ser preenchido',
                        'professional_id' => 'Pelo menos um campo deve ser preenchido'
                    ],
                    ['action' => 'create_price_config']
                );
                return;
            }
            
            $configId = $this->configModel->create($tenantId, $data);
            $config = $this->configModel->findById($configId);
            
            ResponseHelper::sendCreated($config, 'Configuração de preço criada com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'PRICE_CONFIG_CREATE_ERROR',
                ['action' => 'create_price_config', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar configuração de preço',
                'PRICE_CONFIG_CREATE_ERROR',
                ['action' => 'create_price_config', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Atualiza uma configuração de preço
     * PUT /v1/clinic/appointment-price-config/:id
     */
    public function update(string $id): void
    {
        try {
            PermissionHelper::require('update_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_price_config', 'config_id' => $id]);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_price_config', 'config_id' => $id]);
                return;
            }
            
            $this->configModel->updateConfig($tenantId, (int)$id, $data);
            $config = $this->configModel->findById((int)$id);
            
            ResponseHelper::sendSuccess($config, 'Configuração de preço atualizada com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'PRICE_CONFIG_UPDATE_ERROR',
                ['action' => 'update_price_config', 'config_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar configuração de preço',
                'PRICE_CONFIG_UPDATE_ERROR',
                ['action' => 'update_price_config', 'config_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Deleta uma configuração de preço
     * DELETE /v1/clinic/appointment-price-config/:id
     */
    public function delete(string $id): void
    {
        try {
            PermissionHelper::require('delete_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_price_config', 'config_id' => $id]);
                return;
            }

            $config = $this->configModel->findByTenantAndId($tenantId, (int)$id);

            if (!$config) {
                ResponseHelper::sendNotFoundError('Configuração de preço', ['action' => 'delete_price_config', 'config_id' => $id]);
                return;
            }
            
            $this->configModel->delete((int)$id);
            
            ResponseHelper::sendSuccess(null, 'Configuração de preço deletada com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao deletar configuração de preço',
                'PRICE_CONFIG_DELETE_ERROR',
                ['action' => 'delete_price_config', 'config_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

