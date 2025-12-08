<?php

namespace App\Controllers;

use App\Models\Pet;
use App\Models\Customer;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use App\Utils\Validator;
use Flight;

/**
 * Controller para gerenciar pets (animais dos tutores)
 */
class PetController
{
    /**
     * Cria um novo pet
     * POST /v1/clinic/pets
     */
    public function create(): void
    {
        try {
            PermissionHelper::require('create_pets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_pet']);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'create_pet']);
                return;
            }
            
            // Validação básica
            if (empty($data['customer_id']) || empty($data['name'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['customer_id' => 'Obrigatório', 'name' => 'Obrigatório'],
                    ['action' => 'create_pet']
                );
                return;
            }
            
            $petModel = new Pet();
            $petId = $petModel->create($tenantId, $data);
            $pet = $petModel->findById($petId);
            
            ResponseHelper::sendCreated($pet, 'Pet criado com sucesso');
        } catch (\InvalidArgumentException $e) {
            ResponseHelper::sendValidationError(
                $e->getMessage(),
                [],
                ['action' => 'create_pet']
            );
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'PET_CREATE_ERROR',
                ['action' => 'create_pet', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar pet',
                'PET_CREATE_ERROR',
                ['action' => 'create_pet', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista pets do tenant
     * GET /v1/clinic/pets
     */
    public function list(): void
    {
        try {
            PermissionHelper::require('view_pets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_pets']);
                return;
            }
            
            $queryParams = Flight::request()->query;
            $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
            $limit = isset($queryParams['limit']) ? min(100, max(1, (int)$queryParams['limit'])) : 20;
            
            $filters = [];
            if (!empty($queryParams['search'])) {
                $filters['search'] = $queryParams['search'];
            }
            if (!empty($queryParams['customer_id'])) {
                $filters['customer_id'] = (int)$queryParams['customer_id'];
            }
            if (!empty($queryParams['species'])) {
                $filters['species'] = $queryParams['species'];
            }
            if (!empty($queryParams['sort'])) {
                $filters['sort'] = $queryParams['sort'];
                $filters['direction'] = $queryParams['direction'] ?? 'ASC';
            }
            
            $petModel = new Pet();
            $result = $petModel->findByTenant($tenantId, $page, $limit, $filters);

            $responseData = [
                'pets' => $result['data'],
                'meta' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total_pages' => $result['total_pages']
                ]
            ];
            
            ResponseHelper::sendSuccess($responseData['pets'], 200, null, $responseData['meta']);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar pets',
                'PET_LIST_ERROR',
                ['action' => 'list_pets', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém pet por ID
     * GET /v1/clinic/pets/:id
     */
    public function get(string $id): void
    {
        try {
            PermissionHelper::require('view_pets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_pet', 'pet_id' => $id]);
                return;
            }

            $petModel = new Pet();
            $pet = $petModel->findByTenantAndId($tenantId, (int)$id);

            if (!$pet) {
                ResponseHelper::sendNotFoundError('Pet', ['action' => 'get_pet', 'pet_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }

            ResponseHelper::sendSuccess($pet);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter pet',
                'PET_GET_ERROR',
                ['action' => 'get_pet', 'pet_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Atualiza um pet
     * PUT /v1/clinic/pets/:id
     */
    public function update(string $id): void
    {
        try {
            PermissionHelper::require('update_pets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_pet', 'pet_id' => $id]);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_pet', 'pet_id' => $id]);
                return;
            }
            
            $petModel = new Pet();
            $petModel->updatePet($tenantId, (int)$id, $data);
            $pet = $petModel->findById((int)$id);
            
            ResponseHelper::sendSuccess($pet, 'Pet atualizado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'PET_UPDATE_ERROR',
                ['action' => 'update_pet', 'pet_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar pet',
                'PET_UPDATE_ERROR',
                ['action' => 'update_pet', 'pet_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Deleta um pet (soft delete)
     * DELETE /v1/clinic/pets/:id
     */
    public function delete(string $id): void
    {
        try {
            PermissionHelper::require('delete_pets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_pet', 'pet_id' => $id]);
                return;
            }

            $petModel = new Pet();
            $pet = $petModel->findByTenantAndId($tenantId, (int)$id);

            if (!$pet) {
                ResponseHelper::sendNotFoundError('Pet', ['action' => 'delete_pet', 'pet_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }
            
            $petModel->delete((int)$id);
            
            ResponseHelper::sendSuccess(null, 'Pet deletado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao deletar pet',
                'PET_DELETE_ERROR',
                ['action' => 'delete_pet', 'pet_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista pets por customer (tutor)
     * GET /v1/clinic/pets/customer/:customer_id
     */
    public function listByCustomer(string $customerId): void
    {
        try {
            PermissionHelper::require('view_pets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_pets_by_customer', 'customer_id' => $customerId]);
                return;
            }
            
            // Verifica se customer existe e pertence ao tenant
            $customerModel = new Customer();
            $customer = $customerModel->findByTenantAndId($tenantId, (int)$customerId);
            
            if (!$customer) {
                ResponseHelper::sendNotFoundError('Customer', ['action' => 'list_pets_by_customer', 'customer_id' => $customerId]);
                return;
            }
            
            $petModel = new Pet();
            $pets = $petModel->findByCustomer($tenantId, (int)$customerId);
            
            ResponseHelper::sendSuccess($pets);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar pets do customer',
                'PET_LIST_BY_CUSTOMER_ERROR',
                ['action' => 'list_pets_by_customer', 'customer_id' => $customerId, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

