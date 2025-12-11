<?php

namespace App\Controllers;

use App\Services\ReportService;
use App\Services\StripeService;
use App\Services\Logger;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;
use Config;

/**
 * Controller para gerenciar relatórios e analytics
 */
class ReportController
{
    private ReportService $reportService;
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
        $this->reportService = new ReportService($stripeService);
    }

    /**
     * Obtém receita por período
     * GET /v1/reports/revenue
     * 
     * Query params opcionais:
     *   - period: Período predefinido (today, week, month, year, last_month, last_year)
     *   - start_date: Data inicial (formato: YYYY-MM-DD) - requer end_date
     *   - end_date: Data final (formato: YYYY-MM-DD) - requer start_date
     */
    public function revenue(): void
    {
        try {
            // Verifica permissão (PermissionHelper já trata SaaS admins automaticamente)
            PermissionHelper::require('view_reports');

            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_revenue_report']);
                return;
            }
            
            // ✅ CORREÇÃO: Para SaaS admins, usa tenant_id = 0 (todos os tenants) ou null
            $effectiveTenantId = $isSaasAdmin ? null : $tenantId;

            $queryParams = Flight::request()->query->getData();
            $period = $this->reportService->processPeriodFilter($queryParams);
            
            // ✅ CORREÇÃO: Para SaaS admins, usa conta principal do Stripe
            if ($isSaasAdmin) {
                // Cria novo ReportService com conta principal
                $saasReportService = new ReportService($this->stripeService);
                $revenue = $saasReportService->getRevenueForSaasAdmin($period);
            } else {
                // Para clínicas, usa StripeService do tenant
                $tenantStripeService = \App\Services\StripeService::forTenant($tenantId);
                $tenantReportService = new ReportService($tenantStripeService);
                $revenue = $tenantReportService->getRevenue($tenantId, $period);
            }

            ResponseHelper::sendSuccess($revenue);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter relatório de receita',
                'REPORT_REVENUE_ERROR',
                ['action' => 'get_revenue_report', 'tenant_id' => Flight::get('tenant_id') ?? null]
            );
        }
    }

    /**
     * Obtém estatísticas de assinaturas
     * GET /v1/reports/subscriptions
     * 
     * Query params opcionais:
     *   - period: Período predefinido (today, week, month, year, last_month, last_year)
     *   - start_date: Data inicial (formato: YYYY-MM-DD) - requer end_date
     *   - end_date: Data final (formato: YYYY-MM-DD) - requer start_date
     */
    public function subscriptions(): void
    {
        try {
            PermissionHelper::require('view_reports');

            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_subscriptions_report']);
                return;
            }

            $queryParams = Flight::request()->query->getData();
            $period = $this->reportService->processPeriodFilter($queryParams);
            
            // ✅ CORREÇÃO: Para SaaS admins, busca dados de todas as clínicas
            if ($isSaasAdmin) {
                $saasReportService = new ReportService($this->stripeService);
                $stats = $saasReportService->getSubscriptionsStatsForSaasAdmin($period);
            } else {
                $tenantStripeService = \App\Services\StripeService::forTenant($tenantId);
                $tenantReportService = new ReportService($tenantStripeService);
                $stats = $tenantReportService->getSubscriptionsStats($tenantId, $period);
            }

            ResponseHelper::sendSuccess($stats);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter relatório de assinaturas',
                'REPORT_SUBSCRIPTIONS_ERROR',
                ['action' => 'get_subscriptions_report', 'tenant_id' => Flight::get('tenant_id') ?? null]
            );
        }
    }

    /**
     * Obtém taxa de churn
     * GET /v1/reports/churn
     * 
     * Query params opcionais:
     *   - period: Período predefinido (today, week, month, year, last_month, last_year)
     *   - start_date: Data inicial (formato: YYYY-MM-DD) - requer end_date
     *   - end_date: Data final (formato: YYYY-MM-DD) - requer start_date
     */
    public function churn(): void
    {
        try {
            PermissionHelper::require('view_reports');

            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_churn_report']);
                return;
            }

            $queryParams = Flight::request()->query->getData();
            $period = $this->reportService->processPeriodFilter($queryParams);
            
            // ✅ CORREÇÃO: Para SaaS admins, retorna dados básicos (pode ser melhorado depois)
            if ($isSaasAdmin) {
                ResponseHelper::sendSuccess(['message' => 'Funcionalidade em desenvolvimento para SaaS admins']);
                return;
            }
            
            $tenantStripeService = \App\Services\StripeService::forTenant($tenantId);
            $tenantReportService = new ReportService($tenantStripeService);
            $churn = $tenantReportService->getChurnRate($tenantId, $period);

            ResponseHelper::sendSuccess($churn);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter relatório de churn',
                'REPORT_CHURN_ERROR',
                ['action' => 'get_churn_report', 'tenant_id' => Flight::get('tenant_id') ?? null]
            );
        }
    }

    /**
     * Obtém estatísticas de clientes
     * GET /v1/reports/customers
     * 
     * Query params opcionais:
     *   - period: Período predefinido (today, week, month, year, last_month, last_year)
     *   - start_date: Data inicial (formato: YYYY-MM-DD) - requer end_date
     *   - end_date: Data final (formato: YYYY-MM-DD) - requer start_date
     */
    public function customers(): void
    {
        try {
            PermissionHelper::require('view_reports');

            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_customers_report']);
                return;
            }

            $queryParams = Flight::request()->query->getData();
            $period = $this->reportService->processPeriodFilter($queryParams);
            
            // ✅ CORREÇÃO: Para SaaS admins, retorna dados básicos (pode ser melhorado depois)
            if ($isSaasAdmin) {
                ResponseHelper::sendSuccess(['message' => 'Funcionalidade em desenvolvimento para SaaS admins']);
                return;
            }
            
            $tenantStripeService = \App\Services\StripeService::forTenant($tenantId);
            $tenantReportService = new ReportService($tenantStripeService);
            $stats = $tenantReportService->getCustomersStats($tenantId, $period);

            ResponseHelper::sendSuccess($stats);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter relatório de clientes',
                'REPORT_CUSTOMERS_ERROR',
                ['action' => 'get_customers_report', 'tenant_id' => Flight::get('tenant_id') ?? null]
            );
        }
    }

    /**
     * Obtém estatísticas de pagamentos
     * GET /v1/reports/payments
     * 
     * Query params opcionais:
     *   - period: Período predefinido (today, week, month, year, last_month, last_year)
     *   - start_date: Data inicial (formato: YYYY-MM-DD) - requer end_date
     *   - end_date: Data final (formato: YYYY-MM-DD) - requer start_date
     */
    public function payments(): void
    {
        try {
            PermissionHelper::require('view_reports');

            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_payments_report']);
                return;
            }

            $queryParams = Flight::request()->query->getData();
            $period = $this->reportService->processPeriodFilter($queryParams);
            
            // ✅ CORREÇÃO: Para SaaS admins, retorna dados básicos (pode ser melhorado depois)
            if ($isSaasAdmin) {
                ResponseHelper::sendSuccess(['message' => 'Funcionalidade em desenvolvimento para SaaS admins']);
                return;
            }
            
            $tenantStripeService = \App\Services\StripeService::forTenant($tenantId);
            $tenantReportService = new ReportService($tenantStripeService);
            $stats = $tenantReportService->getPaymentsStats($tenantId, $period);

            ResponseHelper::sendSuccess($stats);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter relatório de pagamentos',
                'REPORT_PAYMENTS_ERROR',
                ['action' => 'get_payments_report', 'tenant_id' => Flight::get('tenant_id') ?? null]
            );
        }
    }

    /**
     * Obtém MRR (Monthly Recurring Revenue)
     * GET /v1/reports/mrr
     */
    public function mrr(): void
    {
        try {
            PermissionHelper::require('view_reports');

            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_mrr_report']);
                return;
            }

            // ✅ CORREÇÃO: Para SaaS admins, retorna dados básicos (pode ser melhorado depois)
            if ($isSaasAdmin) {
                ResponseHelper::sendSuccess(['message' => 'Funcionalidade em desenvolvimento para SaaS admins']);
                return;
            }
            
            $tenantStripeService = \App\Services\StripeService::forTenant($tenantId);
            $tenantReportService = new ReportService($tenantStripeService);
            $mrr = $tenantReportService->getMRR($tenantId);

            ResponseHelper::sendSuccess($mrr);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter MRR',
                'REPORT_MRR_ERROR',
                ['action' => 'get_mrr_report', 'tenant_id' => Flight::get('tenant_id') ?? null]
            );
        }
    }

    /**
     * Obtém ARR (Annual Recurring Revenue)
     * GET /v1/reports/arr
     */
    public function arr(): void
    {
        try {
            PermissionHelper::require('view_reports');

            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_arr_report']);
                return;
            }

            // ✅ CORREÇÃO: Para SaaS admins, retorna dados básicos (pode ser melhorado depois)
            if ($isSaasAdmin) {
                ResponseHelper::sendSuccess(['message' => 'Funcionalidade em desenvolvimento para SaaS admins']);
                return;
            }
            
            $tenantStripeService = \App\Services\StripeService::forTenant($tenantId);
            $tenantReportService = new ReportService($tenantStripeService);
            $arr = $tenantReportService->getARR($tenantId);

            ResponseHelper::sendSuccess($arr);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter ARR',
                'REPORT_ARR_ERROR',
                ['action' => 'get_arr_report', 'tenant_id' => Flight::get('tenant_id') ?? null]
            );
        }
    }
}

