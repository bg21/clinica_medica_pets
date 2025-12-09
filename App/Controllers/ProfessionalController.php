<?php

namespace App\Controllers;

use App\Models\Professional;
use App\Models\ClinicSpecialty;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;
use Config;

/**
 * Controller para gerenciar profissionais
 */
class ProfessionalController
{
    /**
     * Lista profissionais do tenant
     * GET /v1/clinic/professionals
     */
    public function list(): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_professionals']);
                return;
            }
            
            $queryParams = Flight::request()->query->getData();
            $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20;
            
            $filters = [];
            if (isset($queryParams['search'])) {
                $filters['search'] = $queryParams['search'];
            }
            if (isset($queryParams['status'])) {
                $filters['status'] = $queryParams['status'];
            }
            if (isset($queryParams['specialty'])) {
                $filters['specialty'] = $queryParams['specialty'];
            }
            if (isset($queryParams['sort'])) {
                // Suporta formato "name:1" ou "name:ASC" ou apenas "name"
                $sortValue = $queryParams['sort'];
                if (strpos($sortValue, ':') !== false) {
                    list($sortField, $sortDir) = explode(':', $sortValue, 2);
                    $filters['sort'] = $sortField;
                    // Converte 1 para ASC, -1 para DESC
                    $filters['direction'] = ($sortDir == '1' || strtoupper($sortDir) == 'ASC') ? 'ASC' : 'DESC';
                } else {
                    $filters['sort'] = $sortValue;
                    $filters['direction'] = $queryParams['direction'] ?? 'ASC';
                }
            }
            
            $professionalModel = new Professional();
            $result = $professionalModel->findByTenant($tenantId, $page, $limit, $filters);
            
            // Formata resposta com meta de paginação (mesmo padrão usado em outros controllers)
            $meta = [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total_pages' => $result['total_pages']
            ];
            
            Flight::json([
                'success' => true,
                'data' => $result['data'],
                'meta' => $meta
            ]);
        } catch (\Throwable $e) {
            // Log detalhado do erro em desenvolvimento
            if (Config::isDevelopment()) {
                error_log("ERRO ao listar profissionais: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar profissionais',
                'PROFESSIONAL_LIST_ERROR',
                ['action' => 'list_professionals', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista profissionais ativos
     * GET /v1/clinic/professionals/active
     */
    public function listActive(): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_active_professionals']);
                return;
            }
            
            $professionalModel = new Professional();
            $professionals = $professionalModel->findActiveByTenant($tenantId);
            
            ResponseHelper::sendSuccess($professionals);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar profissionais ativos',
                'PROFESSIONAL_LIST_ACTIVE_ERROR',
                ['action' => 'list_active_professionals', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém profissional por ID
     * GET /v1/clinic/professionals/:id
     */
    public function get(string $id): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_professional', 'professional_id' => $id]);
                return;
            }

            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, (int)$id);

            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional', ['action' => 'get_professional', 'professional_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }

            ResponseHelper::sendSuccess($professional);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter profissional',
                'PROFESSIONAL_GET_ERROR',
                ['action' => 'get_professional', 'professional_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Cria um novo profissional
     * POST /v1/clinic/professionals
     */
    public function create(): void
    {
        try {
            PermissionHelper::require('create_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_professional']);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'create_professional']);
                return;
            }

            // Validação básica
            if (empty($data['name'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['name' => 'Obrigatório'],
                    ['action' => 'create_professional']
                );
                return;
            }

            // Validação condicional para CRMV no controller (redundante com model, mas bom para feedback rápido)
            if (!empty($data['professional_role_id'])) {
                $roleModel = new \App\Models\ProfessionalRole();
                $role = $roleModel->findByTenantAndId($tenantId, (int)$data['professional_role_id']);
                if ($role) {
                    $roleNameLower = strtolower($role['name'] ?? '');
                    $isVeterinario = strpos($roleNameLower, 'veterinário') !== false || strpos($roleNameLower, 'veterinario') !== false;
                    if ($isVeterinario && empty($data['crmv'])) {
                        ResponseHelper::sendValidationError(
                            'Dados inválidos',
                            ['crmv' => 'CRMV é obrigatório para veterinários'],
                            ['action' => 'create_professional']
                        );
                        return;
                    }
                }
            }
            
            $professionalModel = new Professional();
            $professionalId = $professionalModel->create($tenantId, $data);
            $professional = $professionalModel->findById($professionalId);
            
            ResponseHelper::sendCreated($professional, 'Profissional criado com sucesso');
        } catch (\InvalidArgumentException $e) {
            ResponseHelper::sendValidationError(
                'Dados inválidos',
                ['error' => $e->getMessage()],
                ['action' => 'create_professional']
            );
        } catch (\RuntimeException $e) {
            ResponseHelper::sendValidationError(
                'Erro de validação',
                ['error' => $e->getMessage()],
                ['action' => 'create_professional']
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar profissional',
                'PROFESSIONAL_CREATE_ERROR',
                ['action' => 'create_professional', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Atualiza um profissional
     * PUT /v1/clinic/professionals/:id
     */
    public function update(string $id): void
    {
        try {
            PermissionHelper::require('update_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_professional', 'professional_id' => $id]);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_professional', 'professional_id' => $id]);
                return;
            }
            
            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, (int)$id);
            
            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional', ['action' => 'update_professional', 'professional_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }
            
            // Validação condicional para CRMV
            if (!empty($data['professional_role_id'])) {
                $roleModel = new \App\Models\ProfessionalRole();
                $role = $roleModel->findByTenantAndId($tenantId, (int)$data['professional_role_id']);
                if ($role) {
                    $roleNameLower = strtolower($role['name'] ?? '');
                    $isVeterinario = strpos($roleNameLower, 'veterinário') !== false || strpos($roleNameLower, 'veterinario') !== false;
                    if ($isVeterinario && empty($data['crmv'])) {
                        ResponseHelper::sendValidationError(
                            'Dados inválidos',
                            ['crmv' => 'CRMV é obrigatório para veterinários'],
                            ['action' => 'update_professional', 'professional_id' => $id]
                        );
                        return;
                    }
                }
            }
            
            $professionalModel->updateProfessional($tenantId, (int)$id, $data);
            $updatedProfessional = $professionalModel->findById((int)$id);
            
            ResponseHelper::sendSuccess($updatedProfessional, 'Profissional atualizado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar profissional',
                'PROFESSIONAL_UPDATE_ERROR',
                ['action' => 'update_professional', 'professional_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Deleta um profissional
     * DELETE /v1/clinic/professionals/:id
     */
    public function delete(string $id): void
    {
        try {
            PermissionHelper::require('delete_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_professional', 'professional_id' => $id]);
                return;
            }

            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, (int)$id);

            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional', ['action' => 'delete_professional', 'professional_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }
            
            $professionalModel->delete((int)$id);
            
            ResponseHelper::sendSuccess(null, 'Profissional deletado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao deletar profissional',
                'PROFESSIONAL_DELETE_ERROR',
                ['action' => 'delete_professional', 'professional_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista funções profissionais (roles)
     * GET /v1/clinic/professionals/roles
     */
    public function listRoles(): void
    {
        try {
            PermissionHelper::require('view_professionals'); // Usa mesma permissão
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_professional_roles']);
                return;
            }

            $roleModel = new \App\Models\ProfessionalRole();
            $roles = $roleModel->findActiveByTenant($tenantId);

            ResponseHelper::sendSuccess($roles);
        } catch (\Throwable $e) {
            // Log detalhado do erro em desenvolvimento
            if (Config::isDevelopment()) {
                error_log("ERRO ao listar funções profissionais: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar funções profissionais',
                'PROFESSIONAL_ROLES_LIST_ERROR',
                ['action' => 'list_professional_roles', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Sugere preço para um profissional
     * GET /v1/clinic/professionals/:id/suggested-price
     * 
     * Lógica simplificada:
     * 1. Preço do profissional (default_price_id)
     * 2. Preço da especialidade do profissional (clinic_specialties)
     */
    public function getSuggestedPrice(string $id): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_suggested_price', 'professional_id' => $id]);
                return;
            }

            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, (int)$id);

            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional', ['action' => 'get_suggested_price', 'professional_id' => $id]);
                return;
            }

            $suggestedPriceId = null;

            // Prioridade 1: Preço do profissional
            if (!empty($professional['default_price_id'])) {
                $suggestedPriceId = $professional['default_price_id'];
            } else {
                // Prioridade 2: Preço da especialidade
                if (!empty($professional['specialty'])) {
                    $specialtyModel = new ClinicSpecialty();
                    $specialty = $specialtyModel->findByName($tenantId, $professional['specialty']);
                    if ($specialty && !empty($specialty['price_id'])) {
                        $suggestedPriceId = $specialty['price_id'];
                    }
                }
            }

            ResponseHelper::sendSuccess([
                'price_id' => $suggestedPriceId,
                'suggested' => $suggestedPriceId !== null,
                'source' => !empty($professional['default_price_id']) ? 'professional' : ($suggestedPriceId ? 'specialty' : null)
            ]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter preço sugerido',
                'PROFESSIONAL_SUGGESTED_PRICE_ERROR',
                ['action' => 'get_suggested_price', 'professional_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}
