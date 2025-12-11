<?php

namespace App\Controllers;

use App\Models\Plan;
use App\Models\Module;
use App\Utils\ResponseHelper;
use App\Utils\PermissionHelper;
use Flight;

/**
 * Controller administrativo para gerenciar planos e módulos
 * 
 * Apenas para dono do SaaS (admin/master)
 */
class AdminPlansController
{
    private Plan $planModel;
    private Module $moduleModel;

    public function __construct()
    {
        $this->planModel = new Plan();
        $this->moduleModel = new Module();
    }

    /**
     * GET /admin/plans
     * Lista todos os planos
     */
    public function listPlans(): void
    {
        try {
            // Verifica se é administrador SaaS
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado. Apenas administradores do SaaS podem acessar.', [], 403);
                return;
            }
            
            $plans = $this->planModel->findAll(['is_active' => true]);
            
            // Adiciona módulos de cada plano
            foreach ($plans as &$plan) {
                $plan['modules'] = $this->planModel->getModules($plan['id']);
                $plan['features'] = json_decode($plan['features'] ?? '[]', true);
            }
            
            ResponseHelper::sendSuccess($plans, 'Planos carregados com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao listar planos', 'LIST_PLANS_ERROR');
        }
    }

    /**
     * GET /admin/plans/:id
     * Obtém detalhes de um plano
     */
    public function getPlan(string $id): void
    {
        try {
            // Verifica se é administrador SaaS
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado. Apenas administradores do SaaS podem acessar.', [], 403);
                return;
            }
            
            $plan = $this->planModel->findById((int) $id);
            
            if (!$plan) {
                ResponseHelper::sendError('Plano não encontrado', 'PLAN_NOT_FOUND', 404);
                return;
            }
            
            $plan['modules'] = $this->planModel->getModules($plan['id']);
            $plan['features'] = json_decode($plan['features'] ?? '[]', true);
            
            ResponseHelper::sendSuccess($plan);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao obter plano', 'GET_PLAN_ERROR');
        }
    }

    /**
     * POST /admin/plans
     * Cria um novo plano
     */
    public function createPlan(): void
    {
        try {
            // Verifica se é administrador SaaS
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado. Apenas administradores do SaaS podem acessar.', [], 403);
                return;
            }
            
            $data = Flight::request()->data->getData();
            
            // Validação básica
            if (empty($data['plan_id']) || empty($data['name'])) {
                ResponseHelper::sendError('plan_id e name são obrigatórios', 'VALIDATION_ERROR', 400);
                return;
            }
            
            // Verifica se plan_id já existe
            $existing = $this->planModel->findByPlanId($data['plan_id']);
            if ($existing) {
                ResponseHelper::sendError('Já existe um plano com este plan_id', 'PLAN_ID_EXISTS', 400);
                return;
            }
            
            $planId = $this->planModel->create($data);
            
            // Adiciona módulos se fornecidos
            if (!empty($data['modules']) && is_array($data['modules'])) {
                $this->planModel->setModules($planId, $data['modules']);
            }
            
            $plan = $this->planModel->findById($planId);
            $plan['modules'] = $this->planModel->getModules($planId);
            $plan['features'] = json_decode($plan['features'] ?? '[]', true);
            
            ResponseHelper::sendSuccess($plan, 'Plano criado com sucesso', 201);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao criar plano', 'CREATE_PLAN_ERROR');
        }
    }

    /**
     * PUT /admin/plans/:id
     * Atualiza um plano
     */
    public function updatePlan(string $id): void
    {
        try {
            // Verifica se é administrador SaaS
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado. Apenas administradores do SaaS podem acessar.', [], 403);
                return;
            }
            
            $plan = $this->planModel->findById((int) $id);
            if (!$plan) {
                ResponseHelper::sendError('Plano não encontrado', 'PLAN_NOT_FOUND', 404);
                return;
            }
            
            $data = Flight::request()->data->getData();
            
            // Atualiza plano
            $this->planModel->update((int) $id, $data);
            
            // Atualiza módulos se fornecidos
            if (isset($data['modules']) && is_array($data['modules'])) {
                $this->planModel->setModules((int) $id, $data['modules']);
            }
            
            $updatedPlan = $this->planModel->findById((int) $id);
            $updatedPlan['modules'] = $this->planModel->getModules((int) $id);
            $updatedPlan['features'] = json_decode($updatedPlan['features'] ?? '[]', true);
            
            ResponseHelper::sendSuccess($updatedPlan, 'Plano atualizado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao atualizar plano', 'UPDATE_PLAN_ERROR');
        }
    }

    /**
     * DELETE /admin/plans/:id
     * Remove um plano (soft delete)
     */
    public function deletePlan(string $id): void
    {
        try {
            // Verifica se é administrador SaaS
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado. Apenas administradores do SaaS podem acessar.', [], 403);
                return;
            }
            
            $plan = $this->planModel->findById((int) $id);
            if (!$plan) {
                ResponseHelper::sendError('Plano não encontrado', 'PLAN_NOT_FOUND', 404);
                return;
            }
            
            $this->planModel->delete((int) $id);
            
            ResponseHelper::sendSuccess(null, 'Plano removido com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao remover plano', 'DELETE_PLAN_ERROR');
        }
    }

    /**
     * GET /admin/modules
     * Lista todos os módulos
     */
    public function listModules(): void
    {
        try {
            // Verifica se é administrador SaaS
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado. Apenas administradores do SaaS podem acessar.', [], 403);
                return;
            }
            
            $modules = $this->moduleModel->findAll(['is_active' => true]);
            
            ResponseHelper::sendSuccess($modules, 'Módulos carregados com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao listar módulos', 'LIST_MODULES_ERROR');
        }
    }

    /**
     * POST /admin/modules
     * Cria um novo módulo
     */
    public function createModule(): void
    {
        try {
            // Verifica se é administrador SaaS
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado. Apenas administradores do SaaS podem acessar.', [], 403);
                return;
            }
            
            $data = Flight::request()->data->getData();
            
            // Validação básica
            if (empty($data['module_id']) || empty($data['name'])) {
                ResponseHelper::sendError('module_id e name são obrigatórios', 'VALIDATION_ERROR', 400);
                return;
            }
            
            // Verifica se module_id já existe
            $existing = $this->moduleModel->findByModuleId($data['module_id']);
            if ($existing) {
                ResponseHelper::sendError('Já existe um módulo com este module_id', 'MODULE_ID_EXISTS', 400);
                return;
            }
            
            $moduleId = $this->moduleModel->create($data);
            $module = $this->moduleModel->findById($moduleId);
            
            ResponseHelper::sendSuccess($module, 'Módulo criado com sucesso', 201);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao criar módulo', 'CREATE_MODULE_ERROR');
        }
    }

    /**
     * PUT /admin/modules/:id
     * Atualiza um módulo
     */
    public function updateModule(string $id): void
    {
        try {
            // Verifica se é administrador SaaS
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado. Apenas administradores do SaaS podem acessar.', [], 403);
                return;
            }
            
            $module = $this->moduleModel->findById((int) $id);
            if (!$module) {
                ResponseHelper::sendError('Módulo não encontrado', 'MODULE_NOT_FOUND', 404);
                return;
            }
            
            $data = Flight::request()->data->getData();
            $this->moduleModel->update((int) $id, $data);
            
            $updatedModule = $this->moduleModel->findById((int) $id);
            
            ResponseHelper::sendSuccess($updatedModule, 'Módulo atualizado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao atualizar módulo', 'UPDATE_MODULE_ERROR');
        }
    }

    /**
     * DELETE /admin/modules/:id
     * Remove um módulo (soft delete)
     */
    public function deleteModule(string $id): void
    {
        try {
            // Verifica se é administrador SaaS
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado. Apenas administradores do SaaS podem acessar.', [], 403);
                return;
            }
            
            $module = $this->moduleModel->findById((int) $id);
            if (!$module) {
                ResponseHelper::sendError('Módulo não encontrado', 'MODULE_NOT_FOUND', 404);
                return;
            }
            
            $this->moduleModel->delete((int) $id);
            
            ResponseHelper::sendSuccess(null, 'Módulo removido com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao remover módulo', 'DELETE_MODULE_ERROR');
        }
    }
}

