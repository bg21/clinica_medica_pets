<?php

namespace App\Middleware;

use App\Services\PlanLimitsService;
use App\Services\Logger;
use Flight;

/**
 * Middleware para verificar acesso a módulos específicos
 * 
 * Bloqueia acesso se o tenant não tiver o módulo disponível no seu plano
 * 
 * Uso:
 *   $moduleMiddleware = new ModuleAccessMiddleware();
 *   $check = $moduleMiddleware->check('vaccines');
 *   if ($check) {
 *       Flight::json($check, 403);
 *       Flight::stop();
 *   }
 */
class ModuleAccessMiddleware
{
    private PlanLimitsService $planLimitsService;

    public function __construct()
    {
        $this->planLimitsService = new PlanLimitsService();
    }

    /**
     * Verifica se o tenant tem acesso a um módulo
     * 
     * @param string $moduleId ID do módulo (ex: 'vaccines', 'hospitalization')
     * @return array|null Retorna null se tiver acesso, ou array com erro se bloquear
     */
    public function check(string $moduleId): ?array
    {
        $tenantId = Flight::get('tenant_id');
        
        // Master key sempre tem acesso
        if (Flight::get('is_master') === true) {
            return null;
        }

        // Se não tiver tenant_id, não pode verificar
        if (!$tenantId) {
            return null; // Deixa AuthMiddleware tratar
        }

        // Verifica se o módulo está disponível no plano
        $hasModule = $this->planLimitsService->hasModule($tenantId, $moduleId);

        if (!$hasModule) {
            // Obtém informações do plano atual para mensagem
            $limits = $this->planLimitsService->getAllLimits($tenantId);
            $currentPlan = $limits['subscription']['plan_name'] ?? 'seu plano atual';
            
            // Obtém informações do módulo (primeiro tenta do banco, depois do arquivo)
            $moduleName = $moduleId;
            try {
                $moduleModel = new \App\Models\Module();
                $module = $moduleModel->findByModuleId($moduleId);
                if ($module && !empty($module['name'])) {
                    $moduleName = $module['name'];
                } else {
                    // Fallback: busca no arquivo de configuração
                    $config = require __DIR__ . '/../Config/plans.php';
                    $moduleInfo = $config['modules'][$moduleId] ?? null;
                    $moduleName = $moduleInfo['name'] ?? $moduleId;
                }
            } catch (\Exception $e) {
                // Se houver erro, usa fallback do arquivo
                $config = require __DIR__ . '/../Config/plans.php';
                $moduleInfo = $config['modules'][$moduleId] ?? null;
                $moduleName = $moduleInfo['name'] ?? $moduleId;
            }

            Logger::warning("Tentativa de acesso a módulo não disponível no plano", [
                'tenant_id' => $tenantId,
                'module_id' => $moduleId,
                'module_name' => $moduleName,
                'current_plan' => $currentPlan
            ]);

            return [
                'error' => true,
                'message' => "O módulo '{$moduleName}' não está disponível no {$currentPlan}.",
                'code' => 'MODULE_NOT_AVAILABLE',
                'module_id' => $moduleId,
                'module_name' => $moduleName,
                'current_plan' => $currentPlan,
                'upgrade_url' => '/my-subscription',
                'http_code' => 403
            ];
        }

        // Módulo disponível - permite acesso
        Logger::debug("Verificação de módulo bem-sucedida", [
            'tenant_id' => $tenantId,
            'module_id' => $moduleId
        ]);

        return null; // null = acesso permitido
    }

    /**
     * Verifica múltiplos módulos (todos devem estar disponíveis)
     * 
     * @param array $moduleIds Array de IDs de módulos
     * @return array|null Retorna null se todos estiverem disponíveis, ou array com erro
     */
    public function checkMultiple(array $moduleIds): ?array
    {
        foreach ($moduleIds as $moduleId) {
            $check = $this->check($moduleId);
            if ($check) {
                return $check;
            }
        }
        
        return null;
    }

    /**
     * Verifica se pelo menos um dos módulos está disponível
     * 
     * @param array $moduleIds Array de IDs de módulos
     * @return array|null Retorna null se pelo menos um estiver disponível, ou array com erro
     */
    public function checkAny(array $moduleIds): ?array
    {
        $tenantId = Flight::get('tenant_id');
        
        if (!$tenantId) {
            return null;
        }

        foreach ($moduleIds as $moduleId) {
            if ($this->planLimitsService->hasModule($tenantId, $moduleId)) {
                return null; // Pelo menos um está disponível
            }
        }

        // Nenhum módulo disponível
        $config = require __DIR__ . '/../Config/plans.php';
        $moduleNames = [];
        foreach ($moduleIds as $moduleId) {
            $moduleInfo = $config['modules'][$moduleId] ?? null;
            $moduleNames[] = $moduleInfo['name'] ?? $moduleId;
        }

        return [
            'error' => true,
            'message' => 'Nenhum dos módulos necessários está disponível no seu plano: ' . implode(', ', $moduleNames),
            'code' => 'MODULES_NOT_AVAILABLE',
            'module_ids' => $moduleIds,
            'upgrade_url' => '/my-subscription',
            'http_code' => 403
        ];
    }
}

