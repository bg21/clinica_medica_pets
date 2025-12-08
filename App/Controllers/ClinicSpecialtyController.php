<?php

namespace App\Controllers;

use App\Models\ClinicSpecialty;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;
use Config;

/**
 * Controller para gerenciar especialidades da clínica
 */
class ClinicSpecialtyController
{
    /**
     * Lista especialidades do tenant
     * GET /v1/clinic/specialties
     */
    public function list(): void
    {
        try {
            PermissionHelper::require('view_professionals'); // Usa mesma permissão
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_specialties']);
                return;
            }
            
            try {
                $queryParams = Flight::request()->query->getData();
            } catch (\Exception $e) {
                // Fallback se getData() falhar
                $queryParams = [];
            }
            $activeOnly = isset($queryParams['active']) && $queryParams['active'] === 'true';
            
            $specialtyModel = new ClinicSpecialty();
            
            if ($activeOnly) {
                $specialties = $specialtyModel->findActiveByTenant($tenantId);
            } else {
                // Busca todas as especialidades (incluindo inativas, mas não deletadas)
                // O Model já filtra automaticamente deleted_at porque usesSoftDeletes = true
                $specialties = $specialtyModel->findAll([
                    'tenant_id' => $tenantId
                ], ['sort_order' => 'ASC', 'name' => 'ASC']);
            }
            
            ResponseHelper::sendSuccess($specialties);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar especialidades',
                'SPECIALTY_LIST_ERROR',
                ['action' => 'list_specialties', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém especialidade por ID
     * GET /v1/clinic/specialties/:id
     */
    public function get(string $id): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_specialty', 'specialty_id' => $id]);
                return;
            }

            $specialtyModel = new ClinicSpecialty();
            $specialty = $specialtyModel->findByTenantAndId($tenantId, (int)$id);

            if (!$specialty) {
                ResponseHelper::sendNotFoundError('Especialidade', ['action' => 'get_specialty', 'specialty_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }

            ResponseHelper::sendSuccess($specialty);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter especialidade',
                'SPECIALTY_GET_ERROR',
                ['action' => 'get_specialty', 'specialty_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Cria uma nova especialidade
     * POST /v1/clinic/specialties
     */
    public function create(): void
    {
        try {
            PermissionHelper::require('create_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_specialty']);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'create_specialty']);
                return;
            }
            
            // Validação básica
            if (empty($data['name'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['name' => 'Nome da especialidade é obrigatório'],
                    ['action' => 'create_specialty']
                );
                return;
            }
            
            // Verifica se já existe especialidade com mesmo nome
            $specialtyModel = new ClinicSpecialty();
            $existing = $specialtyModel->findByName($tenantId, $data['name']);
            
            if ($existing) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['name' => 'Já existe uma especialidade com este nome'],
                    ['action' => 'create_specialty']
                );
                return;
            }
            
            $specialtyData = [
                'tenant_id' => $tenantId,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'price_id' => $data['price_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0
            ];
            
            $specialtyId = $specialtyModel->insert($specialtyData);
            $specialty = $specialtyModel->findById($specialtyId);
            
            ResponseHelper::sendCreated($specialty, 'Especialidade criada com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar especialidade',
                'SPECIALTY_CREATE_ERROR',
                ['action' => 'create_specialty', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Atualiza uma especialidade
     * PUT /v1/clinic/specialties/:id
     */
    public function update(string $id): void
    {
        try {
            PermissionHelper::require('view_professionals'); // Usa mesma permissão que list/create
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_specialty', 'specialty_id' => $id]);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_specialty', 'specialty_id' => $id]);
                return;
            }
            
            $specialtyModel = new ClinicSpecialty();
            $specialty = $specialtyModel->findByTenantAndId($tenantId, (int)$id);
            
            if (!$specialty) {
                ResponseHelper::sendNotFoundError('Especialidade', ['action' => 'update_specialty', 'specialty_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }
            
            // Valida se $data é um array
            if (!is_array($data)) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['data' => 'Os dados devem ser um objeto JSON válido'],
                    ['action' => 'update_specialty', 'specialty_id' => $id]
                );
                return;
            }
            
            // Se o nome foi alterado, verifica se não existe duplicata
            if (isset($data['name']) && is_string($data['name']) && trim($data['name']) !== $specialty['name']) {
                $existing = $specialtyModel->findByName($tenantId, trim($data['name']));
                if ($existing && $existing['id'] != $id) {
                    ResponseHelper::sendValidationError(
                        'Dados inválidos',
                        ['name' => 'Já existe uma especialidade com este nome'],
                        ['action' => 'update_specialty', 'specialty_id' => $id]
                    );
                    return;
                }
            }
            
            $updateData = [];
            $allowedFields = ['name', 'description', 'price_id', 'is_active', 'sort_order'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'is_active') {
                        $updateData[$field] = (bool)$data[$field];
                    } elseif ($field === 'sort_order') {
                        $updateData[$field] = isset($data[$field]) ? (int)$data[$field] : 0;
                    } elseif ($field === 'name') {
                        $updateData[$field] = is_string($data[$field]) ? trim($data[$field]) : '';
                    } elseif ($field === 'price_id') {
                        // Converte string vazia para NULL
                        if (empty($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                            $updateData[$field] = null;
                        } else {
                            $updateData[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                        }
                    } elseif ($field === 'description') {
                        // Converte string vazia para NULL
                        if (empty($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                            $updateData[$field] = null;
                        } else {
                            $updateData[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                        }
                    } else {
                        $updateData[$field] = $data[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                ResponseHelper::sendSuccess($specialty, 'Nenhuma alteração realizada');
                return;
            }
            
            $updateResult = $specialtyModel->update((int)$id, $updateData);
            
            if (!$updateResult) {
                ResponseHelper::sendError(
                    500,
                    'Erro ao atualizar',
                    'Falha ao atualizar especialidade no banco de dados',
                    'UPDATE_FAILED',
                    [],
                    ['action' => 'update_specialty', 'specialty_id' => $id, 'tenant_id' => $tenantId]
                );
                return;
            }
            
            // Busca especialidade atualizada (usando findByTenantAndId para garantir segurança)
            $updatedSpecialty = $specialtyModel->findByTenantAndId($tenantId, (int)$id);
            
            if (!$updatedSpecialty) {
                ResponseHelper::sendError(
                    500,
                    'Erro ao buscar especialidade atualizada',
                    'Especialidade atualizada mas não foi possível recuperar os dados',
                    'FETCH_AFTER_UPDATE_FAILED',
                    [],
                    ['action' => 'update_specialty', 'specialty_id' => $id, 'tenant_id' => $tenantId]
                );
                return;
            }
            
            ResponseHelper::sendSuccess($updatedSpecialty, 'Especialidade atualizada com sucesso');
        } catch (\Throwable $e) {
            // Log detalhado do erro em desenvolvimento
            if (Config::isDevelopment()) {
                error_log("ERRO ao atualizar especialidade: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar especialidade',
                'SPECIALTY_UPDATE_ERROR',
                ['action' => 'update_specialty', 'specialty_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Deleta uma especialidade (soft delete)
     * DELETE /v1/clinic/specialties/:id
     */
    public function delete(string $id): void
    {
        try {
            PermissionHelper::require('delete_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_specialty', 'specialty_id' => $id]);
                return;
            }

            $specialtyModel = new ClinicSpecialty();
            $specialty = $specialtyModel->findByTenantAndId($tenantId, (int)$id);

            if (!$specialty) {
                ResponseHelper::sendNotFoundError('Especialidade', ['action' => 'delete_specialty', 'specialty_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }
            
            // Verifica se há profissionais usando esta especialidade
            $professionalModel = new \App\Models\Professional();
            $professionals = $professionalModel->findBy([
                'tenant_id' => $tenantId,
                'specialty' => $specialty['name']
            ]);
            
            if (!empty($professionals)) {
                ResponseHelper::sendConflictError(
                    'Não é possível deletar a especialidade. Existem profissionais cadastrados com esta especialidade.',
                    'SPECIALTY_IN_USE',
                    ['specialty_id' => $id, 'tenant_id' => $tenantId, 'professionals_count' => count($professionals)]
                );
                return;
            }
            
            $specialtyModel->delete((int)$id);
            
            ResponseHelper::sendSuccess(null, 'Especialidade deletada com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao deletar especialidade',
                'SPECIALTY_DELETE_ERROR',
                ['action' => 'delete_specialty', 'specialty_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

