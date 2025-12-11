<?php

namespace App\Controllers;

use App\Models\Commission;
use App\Models\CommissionConfig;
use App\Services\CommissionService;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use App\Traits\HasModuleAccess;
use Flight;

/**
 * Controller para gerenciar comissões
 */
class CommissionController
{
    use HasModuleAccess;
    
    private CommissionService $commissionService;
    private Commission $commissionModel;
    private CommissionConfig $commissionConfigModel;
    private const MODULE_ID = 'financial';

    public function __construct(
        CommissionService $commissionService,
        Commission $commissionModel,
        CommissionConfig $commissionConfigModel
    ) {
        $this->commissionService = $commissionService;
        $this->commissionModel = $commissionModel;
        $this->commissionConfigModel = $commissionConfigModel;
    }

    /**
     * Lista comissões do tenant
     * GET /v1/clinic/commissions
     */
    public function list(): void
    {
        try {
            PermissionHelper::require('view_commissions');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_commissions']);
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
            if (!empty($queryParams['user_id'])) {
                $filters['user_id'] = (int)$queryParams['user_id'];
            }
            if (!empty($queryParams['status'])) {
                $filters['status'] = $queryParams['status'];
            }
            if (!empty($queryParams['start_date'])) {
                $filters['start_date'] = $queryParams['start_date'];
            }
            if (!empty($queryParams['end_date'])) {
                $filters['end_date'] = $queryParams['end_date'];
            }
            
            $result = $this->commissionModel->findByTenant($tenantId, $page, $limit, $filters);
            
            ResponseHelper::sendSuccess($result);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar comissões',
                'COMMISSION_LIST_ERROR',
                ['action' => 'list_commissions', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Busca comissão por ID
     * GET /v1/clinic/commissions/@id
     */
    public function get($id): void
    {
        $tenantId = null;
        try {
            // Converte string para int (Flight passa parâmetros como string)
            $id = (int)$id;
            
            if ($id <= 0) {
                ResponseHelper::sendValidationError(
                    'ID inválido',
                    ['id' => 'O ID deve ser um número inteiro positivo'],
                    ['action' => 'get_commission']
                );
                return;
            }
            
            PermissionHelper::require('view_commissions');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_commission']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $commission = $this->commissionModel->findByTenantAndId($tenantId, $id);
            
            if (!$commission) {
                ResponseHelper::sendNotFoundError('Comissão não encontrada', ['action' => 'get_commission']);
                return;
            }
            
            ResponseHelper::sendSuccess($commission);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar comissão',
                'COMMISSION_GET_ERROR',
                ['action' => 'get_commission', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Marca comissão como paga
     * POST /v1/clinic/commissions/@id/mark-paid
     */
    public function markPaid($id): void
    {
        $tenantId = null;
        try {
            // Converte string para int (Flight passa parâmetros como string)
            $id = (int)$id;
            
            if ($id <= 0) {
                ResponseHelper::sendValidationError(
                    'ID inválido',
                    ['id' => 'O ID deve ser um número inteiro positivo'],
                    ['action' => 'mark_commission_paid']
                );
                return;
            }
            
            PermissionHelper::require('update_commissions');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'mark_commission_paid']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            $paymentReference = $data['payment_reference'] ?? null;
            $notes = $data['notes'] ?? null;
            
            $commission = $this->commissionService->markAsPaid($tenantId, $id, $paymentReference, $notes);
            
            ResponseHelper::sendSuccess($commission, 'Comissão marcada como paga com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'COMMISSION_MARK_PAID_ERROR',
                ['action' => 'mark_commission_paid', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao marcar comissão como paga',
                'COMMISSION_MARK_PAID_ERROR',
                ['action' => 'mark_commission_paid', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Busca estatísticas de comissões por funcionário
     * GET /v1/clinic/commissions/stats/user/@user_id
     */
    public function getUserStats($userId): void
    {
        $tenantId = null;
        try {
            // Converte string para int (Flight passa parâmetros como string)
            $userId = (int)$userId;
            
            if ($userId <= 0) {
                ResponseHelper::sendValidationError(
                    'ID de usuário inválido',
                    ['user_id' => 'O ID do usuário deve ser um número inteiro positivo'],
                    ['action' => 'get_user_commission_stats']
                );
                return;
            }
            
            PermissionHelper::require('view_commissions');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_user_commission_stats']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $stats = $this->commissionService->getStatsByUser($tenantId, $userId);
            
            ResponseHelper::sendSuccess($stats);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar estatísticas de comissão',
                'COMMISSION_STATS_ERROR',
                ['action' => 'get_user_commission_stats', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Busca estatísticas gerais de comissões
     * GET /v1/clinic/commissions/stats
     */
    public function getGeneralStats(): void
    {
        $tenantId = null;
        try {
            // Verifica permissão sem lançar exceção fatal
            if (!PermissionHelper::check('view_commissions')) {
                ResponseHelper::sendForbiddenError(
                    'Você não tem permissão para visualizar estatísticas de comissão',
                    ['action' => 'get_commission_stats']
                );
                return;
            }
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_commission_stats']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $stats = [
                'total_count' => 0,
                'total_amount' => 0.0,
                'paid_amount' => 0.0,
                'pending_amount' => 0.0,
                'total_users' => 0
            ];
            
            try {
                $stats = $this->commissionService->getGeneralStats($tenantId);
            } catch (\PDOException $e) {
                // Se a tabela não existir ou houver erro de SQL, retorna estatísticas vazias
                \App\Services\Logger::error("Erro ao buscar estatísticas de comissão no banco", [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage()
                ]);
                // stats já está com valores padrão
            } catch (\Exception $e) {
                \App\Services\Logger::error("Erro inesperado ao buscar estatísticas de comissão", [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage()
                ]);
                // stats já está com valores padrão
            }
            
            ResponseHelper::sendSuccess($stats);
        } catch (\Throwable $e) {
            \App\Services\Logger::error("Erro ao buscar estatísticas de comissão", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar estatísticas de comissão',
                'COMMISSION_STATS_ERROR',
                ['action' => 'get_commission_stats', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Atualiza configuração de comissão do tenant
     * PUT /v1/clinic/commissions/config
     */
    public function updateConfig(): void
    {
        try {
            PermissionHelper::require('update_commission_config');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_commission_config']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_commission_config']);
                return;
            }
            
            if (!isset($data['commission_percentage'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['commission_percentage' => 'Obrigatório'],
                    ['action' => 'update_commission_config']
                );
                return;
            }
            
            $percentage = (float)$data['commission_percentage'];
            $isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;
            
            $config = $this->commissionService->updateConfig($tenantId, $percentage, $isActive);
            
            ResponseHelper::sendSuccess($config, 'Configuração de comissão atualizada com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'COMMISSION_CONFIG_UPDATE_ERROR',
                ['action' => 'update_commission_config', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar configuração de comissão',
                'COMMISSION_CONFIG_UPDATE_ERROR',
                ['action' => 'update_commission_config', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Busca configuração de comissão do tenant
     * GET /v1/clinic/commissions/config
     */
    public function getConfig(): void
    {
        $tenantId = null;
        try {
            \App\Services\Logger::debug("Iniciando getConfig", []);
            
            // Verifica permissão sem lançar exceção fatal
            $hasPermission = false;
            try {
                $hasPermission = PermissionHelper::check('view_commission_config');
            } catch (\Throwable $e) {
                \App\Services\Logger::error("Erro ao verificar permissão", [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                ResponseHelper::sendGenericError(
                    $e,
                    'Erro ao verificar permissões',
                    'PERMISSION_CHECK_ERROR',
                    ['action' => 'get_commission_config']
                );
                return;
            }
            
            if (!$hasPermission) {
                ResponseHelper::sendForbiddenError(
                    'Você não tem permissão para visualizar a configuração de comissão',
                    ['action' => 'get_commission_config']
                );
                return;
            }
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_commission_config']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            // Busca a configuração usando o modelo
            $config = null;
            try {
                $config = $this->commissionConfigModel->findByTenant($tenantId);
            } catch (\PDOException $e) {
                // Se a tabela não existir ou houver erro de SQL, retorna configuração padrão
                \App\Services\Logger::error("Erro ao buscar configuração de comissão no banco", [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage()
                ]);
                $config = null;
            } catch (\Exception $e) {
                \App\Services\Logger::error("Erro inesperado ao buscar configuração de comissão", [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage()
                ]);
                $config = null;
            }
            
            if (!$config) {
                // Retorna configuração padrão se não existir
                $config = [
                    'tenant_id' => $tenantId,
                    'commission_percentage' => 0.00,
                    'is_active' => false
                ];
            } else {
                // Garante que is_active é boolean
                $config['is_active'] = (bool)($config['is_active'] ?? false);
                $config['commission_percentage'] = (float)($config['commission_percentage'] ?? 0.00);
            }
            
            ResponseHelper::sendSuccess($config);
        } catch (\Throwable $e) {
            \App\Services\Logger::error("Erro fatal ao buscar configuração de comissão", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar configuração de comissão',
                'COMMISSION_CONFIG_GET_ERROR',
                ['action' => 'get_commission_config', 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

