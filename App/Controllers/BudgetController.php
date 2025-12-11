<?php

namespace App\Controllers;

use App\Models\Budget;
use App\Models\CommissionConfig;
use App\Models\Commission;
use App\Models\User;
use App\Services\BudgetService;
use App\Services\CommissionService;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use App\Traits\HasModuleAccess;
use Flight;

/**
 * Controller para gerenciar orçamentos
 */
class BudgetController
{
    use HasModuleAccess;
    
    private BudgetService $budgetService;
    private const MODULE_ID = 'financial';

    public function __construct(BudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
    }

    /**
     * Cria um novo orçamento
     * POST /v1/clinic/budgets
     */
    public function create(): void
    {
        try {
            PermissionHelper::require('create_budgets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_budget']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'create_budget']);
                return;
            }
            
            // Validação básica
            if (empty($data['customer_id']) || empty($data['created_by_user_id'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    [
                        'customer_id' => 'Obrigatório',
                        'created_by_user_id' => 'Obrigatório'
                    ],
                    ['action' => 'create_budget']
                );
                return;
            }
            
            $budget = $this->budgetService->createBudget($tenantId, $data);
            
            ResponseHelper::sendCreated($budget, 'Orçamento criado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'BUDGET_CREATE_ERROR',
                ['action' => 'create_budget', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar orçamento',
                'BUDGET_CREATE_ERROR',
                ['action' => 'create_budget', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista orçamentos do tenant
     * GET /v1/clinic/budgets
     */
    public function list(): void
    {
        try {
            PermissionHelper::require('view_budgets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_budgets']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $queryParams = Flight::request()->query;
            $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
            $limit = isset($queryParams['limit']) ? min(100, max(1, (int)$queryParams['limit'])) : 20;
            
            $filters = [];
            if (!empty($queryParams['status'])) {
                $filters['status'] = $queryParams['status'];
            }
            if (!empty($queryParams['user_id'])) {
                $filters['user_id'] = (int)$queryParams['user_id'];
            }
            if (!empty($queryParams['customer_id'])) {
                $filters['customer_id'] = (int)$queryParams['customer_id'];
            }
            if (!empty($queryParams['search'])) {
                $filters['search'] = $queryParams['search'];
            }
            
            $budgetModel = new Budget();
            $result = $budgetModel->findByTenant($tenantId, $page, $limit, $filters);
            
            ResponseHelper::sendSuccess($result);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar orçamentos',
                'BUDGET_LIST_ERROR',
                ['action' => 'list_budgets', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Busca orçamento por ID
     * GET /v1/clinic/budgets/@id
     */
    public function get(int $id): void
    {
        try {
            PermissionHelper::require('view_budgets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_budget']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $budgetModel = new Budget();
            $budget = $budgetModel->findByTenantAndId($tenantId, $id);
            
            if (!$budget) {
                ResponseHelper::sendNotFoundError('Orçamento não encontrado', ['action' => 'get_budget']);
                return;
            }
            
            // Decodifica items JSON se existir
            if (!empty($budget['items'])) {
                $budget['items'] = json_decode($budget['items'], true);
            }
            
            ResponseHelper::sendSuccess($budget);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar orçamento',
                'BUDGET_GET_ERROR',
                ['action' => 'get_budget', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Atualiza orçamento
     * PUT /v1/clinic/budgets/@id
     */
    public function update(int $id): void
    {
        try {
            PermissionHelper::require('update_budgets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_budget']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_budget']);
                return;
            }
            
            $budget = $this->budgetService->updateBudget($tenantId, $id, $data);
            
            // Decodifica items JSON se existir
            if (!empty($budget['items'])) {
                $budget['items'] = json_decode($budget['items'], true);
            }
            
            ResponseHelper::sendSuccess($budget, 'Orçamento atualizado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'BUDGET_UPDATE_ERROR',
                ['action' => 'update_budget', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar orçamento',
                'BUDGET_UPDATE_ERROR',
                ['action' => 'update_budget', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Marca orçamento como convertido (fechado) e calcula comissão
     * POST /v1/clinic/budgets/@id/convert
     */
    public function convert(int $id): void
    {
        try {
            PermissionHelper::require('update_budgets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'convert_budget']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            $invoiceId = $data['invoice_id'] ?? null;
            
            $result = $this->budgetService->convertBudget($tenantId, $id, $invoiceId);
            
            // Decodifica items JSON se existir
            if (!empty($result['budget']['items'])) {
                $result['budget']['items'] = json_decode($result['budget']['items'], true);
            }
            
            ResponseHelper::sendSuccess($result, 'Orçamento convertido e comissão calculada com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'BUDGET_CONVERT_ERROR',
                ['action' => 'convert_budget', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao converter orçamento',
                'BUDGET_CONVERT_ERROR',
                ['action' => 'convert_budget', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Deleta orçamento (soft delete)
     * DELETE /v1/clinic/budgets/@id
     */
    public function delete(int $id): void
    {
        try {
            PermissionHelper::require('delete_budgets');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_budget']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $budgetModel = new Budget();
            $budget = $budgetModel->findByTenantAndId($tenantId, $id);
            
            if (!$budget) {
                ResponseHelper::sendNotFoundError('Orçamento não encontrado', ['action' => 'delete_budget']);
                return;
            }
            
            if ($budget['status'] === 'converted') {
                ResponseHelper::sendValidationError(
                    'Não é possível excluir um orçamento já convertido',
                    [],
                    ['action' => 'delete_budget']
                );
                return;
            }
            
            $budgetModel->delete($id);
            
            ResponseHelper::sendSuccess(null, 'Orçamento excluído com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao excluir orçamento',
                'BUDGET_DELETE_ERROR',
                ['action' => 'delete_budget', 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

