<?php

namespace App\Controllers;

use App\Services\PlanLimitsService;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;

/**
 * Controller para gerenciar limites de planos
 */
class PlanLimitsController
{
    private PlanLimitsService $planLimitsService;

    public function __construct(PlanLimitsService $planLimitsService)
    {
        $this->planLimitsService = $planLimitsService;
    }

    /**
     * Obtém todos os limites do plano atual do tenant
     * GET /v1/plan-limits
     */
    public function getAll(): void
    {
        try {
            // Verifica permissão (só verifica se for autenticação de usuário)
            PermissionHelper::require('view_subscriptions');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_plan_limits']);
                return;
            }

            $limits = $this->planLimitsService->getAllLimits($tenantId);

            ResponseHelper::sendSuccess($limits, 'Limites do plano obtidos com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter limites do plano',
                'PLAN_LIMITS_ERROR',
                ['action' => 'get_plan_limits', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * GET /v1/plan-limits/plans
     * Retorna todos os planos disponíveis com módulos
     */
    public function getAllPlans(): void
    {
        try {
            $plans = $this->planLimitsService->getAllPlans();
            ResponseHelper::sendSuccess($plans, 'Planos carregados com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar planos',
                'PLAN_LIMITS_LIST_ERROR',
                ['action' => 'get_all_plans']
            );
        }
    }

    /**
     * GET /v1/plan-limits/modules
     * Retorna módulos disponíveis no plano atual do tenant
     */
    public function getAvailableModules(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_available_modules']);
                return;
            }

            $modules = $this->planLimitsService->getAvailableModules($tenantId);
            ResponseHelper::sendSuccess($modules, 'Módulos carregados com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter módulos disponíveis',
                'PLAN_LIMITS_MODULES_ERROR',
                ['action' => 'get_available_modules', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * GET /v1/plan-limits/check-module/:moduleId
     * Verifica se um módulo específico está disponível
     */
    public function checkModule(string $moduleId): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'check_module']);
                return;
            }

            $hasModule = $this->planLimitsService->hasModule($tenantId, $moduleId);
            
            ResponseHelper::sendSuccess([
                'module_id' => $moduleId,
                'available' => $hasModule
            ]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao verificar módulo',
                'PLAN_LIMITS_CHECK_MODULE_ERROR',
                ['action' => 'check_module', 'module_id' => $moduleId, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

