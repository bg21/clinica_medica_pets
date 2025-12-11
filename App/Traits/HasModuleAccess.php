<?php

namespace App\Traits;

use App\Middleware\ModuleAccessMiddleware;
use App\Utils\ResponseHelper;
use Flight;

/**
 * Trait para adicionar verificação de acesso a módulos em controllers
 * 
 * Uso:
 *   use HasModuleAccess;
 *   
 *   private const MODULE_ID = 'pets';
 *   
 *   public function create(): void {
 *       $this->checkModuleAccess();
 *       // ... resto do código
 *   }
 */
trait HasModuleAccess
{
    /**
     * Verifica se o tenant tem acesso ao módulo definido em MODULE_ID
     * 
     * @return bool Retorna true se tiver acesso, false se bloqueou
     */
    protected function checkModuleAccess(): bool
    {
        if (!defined('static::MODULE_ID')) {
            return true; // Se não definir MODULE_ID, não verifica
        }

        $tenantId = Flight::get('tenant_id');
        if ($tenantId === null) {
            return true; // Deixa outros middlewares tratarem
        }

        $moduleMiddleware = new ModuleAccessMiddleware();
        $check = $moduleMiddleware->check(static::MODULE_ID);
        
        if ($check) {
            ResponseHelper::sendError(
                $check['message'] ?? 'Módulo não disponível no seu plano',
                $check['http_code'] ?? 403,
                $check['code'] ?? 'MODULE_NOT_AVAILABLE',
                ['module_id' => static::MODULE_ID, 'module_name' => $check['module_name'] ?? ucfirst(static::MODULE_ID)]
            );
            return false;
        }
        
        return true;
    }
}

