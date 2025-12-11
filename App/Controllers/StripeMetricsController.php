<?php

namespace App\Controllers;

use App\Services\StripeMetricsService;
use App\Services\StripeAlertService;
use App\Utils\ResponseHelper;
use App\Utils\PermissionHelper;
use Flight;

/**
 * Controller para dashboard de métricas Stripe
 * 
 * Endpoints:
 * - GET /v1/stripe-metrics - Todas as métricas
 * - GET /v1/stripe-metrics/mrr - MRR
 * - GET /v1/stripe-metrics/churn - Churn Rate
 * - GET /v1/stripe-metrics/conversion - Conversion Rate
 * - GET /v1/stripe-metrics/arr - ARR
 * - GET /v1/stripe-metrics/alerts - Alertas
 */
class StripeMetricsController
{
    private StripeMetricsService $metricsService;
    private StripeAlertService $alertService;

    public function __construct(
        StripeMetricsService $metricsService,
        StripeAlertService $alertService
    ) {
        $this->metricsService = $metricsService;
        $this->alertService = $alertService;
    }

    /**
     * Obtém todas as métricas
     * GET /v1/stripe-metrics
     * 
     * Query params opcionais:
     *   - period: Período para cálculos (padrão: último mês)
     */
    public function getAll(): void
    {
        try {
            PermissionHelper::require('view_performance_metrics');
            
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_stripe_metrics']);
                return;
            }

            $queryParams = Flight::request()->query;
            $hours = isset($queryParams['hours']) ? (int)$queryParams['hours'] : 24;

            // ✅ CORREÇÃO: Para SaaS admins, usa null como tenant_id (busca de todas as clínicas)
            $effectiveTenantId = $isSaasAdmin ? null : $tenantId;
            $metrics = $this->metricsService->getAllMetrics($effectiveTenantId);
            $alerts = $this->alertService->checkAllAlerts($effectiveTenantId, $hours);

            // Calcula summary dos alertas
            $totalAlerts = 0;
            $criticalAlerts = 0;
            $warningAlerts = 0;

            foreach ($alerts as $type => $typeAlerts) {
                if ($type === 'summary') continue;
                foreach ($typeAlerts as $alert) {
                    $totalAlerts++;
                    if (($alert['severity'] ?? 'warning') === 'critical') {
                        $criticalAlerts++;
                    } else {
                        $warningAlerts++;
                    }
                }
            }

            $alerts['summary'] = [
                'total' => $totalAlerts,
                'critical' => $criticalAlerts,
                'warnings' => $warningAlerts,
                'checked_at' => date('Y-m-d H:i:s')
            ];

            ResponseHelper::sendSuccess([
                'metrics' => $metrics,
                'alerts' => $alerts
            ]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter métricas Stripe',
                'STRIPE_METRICS_ERROR',
                ['action' => 'get_stripe_metrics', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém MRR
     * GET /v1/stripe-metrics/mrr
     */
    public function getMRR(): void
    {
        try {
            PermissionHelper::require('view_performance_metrics');
            
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_mrr']);
                return;
            }

            $queryParams = Flight::request()->query;
            $period = $queryParams['period'] ?? 'current';

            // ✅ CORREÇÃO: Para SaaS admins, usa null como tenant_id
            $effectiveTenantId = $isSaasAdmin ? null : $tenantId;
            $mrr = $this->metricsService->getMRR($effectiveTenantId, $period);

            ResponseHelper::sendSuccess($mrr);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter MRR',
                'MRR_ERROR',
                ['action' => 'get_mrr', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém Churn Rate
     * GET /v1/stripe-metrics/churn
     */
    public function getChurn(): void
    {
        try {
            PermissionHelper::require('view_performance_metrics');
            
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_churn']);
                return;
            }

            $queryParams = Flight::request()->query;
            $period = [];

            if (isset($queryParams['start']) && isset($queryParams['end'])) {
                $period = [
                    'start' => strtotime($queryParams['start']),
                    'end' => strtotime($queryParams['end'])
                ];
            }

            // ✅ CORREÇÃO: Para SaaS admins, usa null como tenant_id
            $effectiveTenantId = $isSaasAdmin ? null : $tenantId;
            $churn = $this->metricsService->getChurnRate($effectiveTenantId, $period);

            ResponseHelper::sendSuccess($churn);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter churn rate',
                'CHURN_ERROR',
                ['action' => 'get_churn', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém Conversion Rate
     * GET /v1/stripe-metrics/conversion
     */
    public function getConversion(): void
    {
        try {
            PermissionHelper::require('view_performance_metrics');
            
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_conversion']);
                return;
            }

            $queryParams = Flight::request()->query;
            $period = [];

            if (isset($queryParams['start']) && isset($queryParams['end'])) {
                $period = [
                    'start' => strtotime($queryParams['start']),
                    'end' => strtotime($queryParams['end'])
                ];
            }

            // ✅ CORREÇÃO: Para SaaS admins, usa null como tenant_id
            $effectiveTenantId = $isSaasAdmin ? null : $tenantId;
            $conversion = $this->metricsService->getConversionRate($effectiveTenantId, $period);

            ResponseHelper::sendSuccess($conversion);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter conversion rate',
                'CONVERSION_ERROR',
                ['action' => 'get_conversion', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém ARR
     * GET /v1/stripe-metrics/arr
     */
    public function getARR(): void
    {
        try {
            PermissionHelper::require('view_performance_metrics');
            
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_arr']);
                return;
            }

            // ✅ CORREÇÃO: Para SaaS admins, usa null como tenant_id
            $effectiveTenantId = $isSaasAdmin ? null : $tenantId;
            $arr = $this->metricsService->getARR($effectiveTenantId);

            ResponseHelper::sendSuccess($arr);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter ARR',
                'ARR_ERROR',
                ['action' => 'get_arr', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém alertas
     * GET /v1/stripe-metrics/alerts
     * 
     * Query params opcionais:
     *   - hours: Últimas N horas (padrão: 24)
     *   - type: Tipo de alerta específico (opcional)
     */
    public function getAlerts(): void
    {
        try {
            PermissionHelper::require('view_performance_metrics');
            
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_alerts']);
                return;
            }

            $queryParams = Flight::request()->query;
            $hours = isset($queryParams['hours']) ? (int)$queryParams['hours'] : 24;
            $type = $queryParams['type'] ?? null;

            // ✅ CORREÇÃO: Para SaaS admins, usa null como tenant_id
            $effectiveTenantId = $isSaasAdmin ? null : $tenantId;
            $alerts = [];

            if ($type === null || $type === 'failed_payments') {
                $alerts['failed_payments'] = $this->alertService->checkFailedPayments($effectiveTenantId, $hours);
            }
            if ($type === null || $type === 'disputes') {
                $alerts['disputes'] = $this->alertService->checkDisputes($effectiveTenantId, $hours);
            }
            if ($type === null || $type === 'webhook_failures') {
                $alerts['webhook_failures'] = $this->alertService->checkWebhookFailures($effectiveTenantId, $hours);
            }
            if ($type === null || $type === 'canceled_subscriptions') {
                $alerts['canceled_subscriptions'] = $this->alertService->checkCanceledSubscriptions($effectiveTenantId, $hours);
            }
            if ($type === null || $type === 'performance') {
                $alerts['performance'] = $this->alertService->checkPerformanceMetrics($effectiveTenantId, $hours);
            }

            // Calcula summary
            $totalAlerts = 0;
            $criticalAlerts = 0;
            $warningAlerts = 0;

            foreach ($alerts as $typeAlerts) {
                foreach ($typeAlerts as $alert) {
                    $totalAlerts++;
                    if (($alert['severity'] ?? 'warning') === 'critical') {
                        $criticalAlerts++;
                    } else {
                        $warningAlerts++;
                    }
                }
            }

            $alerts['summary'] = [
                'total' => $totalAlerts,
                'critical' => $criticalAlerts,
                'warnings' => $warningAlerts,
                'checked_at' => date('Y-m-d H:i:s')
            ];

            ResponseHelper::sendSuccess($alerts);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter alertas',
                'ALERTS_ERROR',
                ['action' => 'get_alerts', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Verifica e retorna falhas críticas do Stripe
     * GET /v1/stripe-metrics/critical-failures
     * 
     * Query params:
     *   - hours: Número de horas para verificar (padrão: 24, máximo: 168)
     * 
     * Verifica webhooks falhados e rate limits e retorna alertas críticos.
     */
    public function getCriticalFailures(): void
    {
        try {
            PermissionHelper::require('view_performance_metrics');
            
            $tenantId = Flight::get('tenant_id');
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            // ✅ CORREÇÃO: Permite acesso de SaaS admins
            if (!$isSaasAdmin && $tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_critical_failures']);
                return;
            }

            $queryParams = Flight::request()->query;
            $hours = isset($queryParams['hours']) ? (int)$queryParams['hours'] : 24;
            $hours = max(1, min($hours, 168)); // Entre 1 e 168 horas (7 dias)
            
            // ✅ CORREÇÃO: checkCriticalFailures não precisa de tenant_id (verifica globalmente)
            $alerts = $this->alertService->checkCriticalFailures($hours);
            
            ResponseHelper::sendSuccess([
                'alerts' => $alerts,
                'total' => count($alerts),
                'period' => "{$hours}h",
                'checked_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao verificar falhas críticas',
                'CHECK_CRITICAL_FAILURES_ERROR',
                ['action' => 'get_critical_failures', 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

