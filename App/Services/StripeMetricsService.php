<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Customer;
use App\Models\StripeEvent;
use App\Utils\Database;
use App\Services\Logger;

/**
 * Service para calcular métricas do Stripe
 * 
 * Métricas calculadas:
 * - MRR (Monthly Recurring Revenue)
 * - Churn Rate
 * - Conversion Rate
 * - ARR (Annual Recurring Revenue)
 * - Growth Rate
 * - LTV (Lifetime Value)
 */
class StripeMetricsService
{
    private Subscription $subscriptionModel;
    private Customer $customerModel;
    private StripeEvent $eventModel;
    private \PDO $db;

    public function __construct()
    {
        $this->subscriptionModel = new Subscription();
        $this->customerModel = new Customer();
        $this->eventModel = new StripeEvent();
        $this->db = Database::getInstance();
    }

    /**
     * Calcula MRR (Monthly Recurring Revenue)
     * 
     * @param int|null $tenantId ID do tenant (null para todos)
     * @param string|null $period 'current' ou 'previous' (padrão: 'current')
     * @return array Dados do MRR
     */
    public function getMRR(?int $tenantId = null, ?string $period = 'current'): array
    {
        try {
            $cacheKey = sprintf('stripe_metrics:mrr:%s:%s', $tenantId ?? 'all', $period);
            $cached = \App\Services\CacheService::getJson($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $whereClause = $tenantId ? "WHERE s.tenant_id = :tenant_id" : "";
            $params = $tenantId ? ['tenant_id' => $tenantId] : [];

            // Calcula MRR baseado em assinaturas ativas
            $sql = "
                SELECT 
                    COALESCE(SUM(
                        CASE 
                            WHEN s.currency = 'BRL' THEN s.amount
                            WHEN s.currency = 'USD' THEN s.amount * 5.0  -- Conversão aproximada
                            ELSE s.amount
                        END
                    ), 0) as mrr,
                    COUNT(*) as total_subscriptions,
                    COUNT(DISTINCT s.plan_id) as total_plans,
                    s.currency
                FROM subscriptions s
                {$whereClause}
                AND LOWER(s.status) IN ('active', 'trialing')
                GROUP BY s.currency
                ORDER BY mrr DESC
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            $mrr = round((float)($result['mrr'] ?? 0), 2);
            $currency = $result['currency'] ?? 'BRL';

            // MRR por plano
            $mrrByPlan = $this->getMRRByPlan($tenantId);

            $data = [
                'mrr' => $mrr,
                'currency' => strtoupper($currency),
                'total_subscriptions' => (int)($result['total_subscriptions'] ?? 0),
                'total_plans' => (int)($result['total_plans'] ?? 0),
                'by_plan' => $mrrByPlan,
                'period' => $period,
                'calculated_at' => date('Y-m-d H:i:s')
            ];

            // Cache por 5 minutos
            \App\Services\CacheService::setJson($cacheKey, $data, 300);

            return $data;
        } catch (\Exception $e) {
            Logger::error("Erro ao calcular MRR", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            return [
                'mrr' => 0,
                'currency' => 'BRL',
                'total_subscriptions' => 0,
                'total_plans' => 0,
                'by_plan' => [],
                'period' => $period,
                'calculated_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Calcula MRR por plano
     */
    private function getMRRByPlan(?int $tenantId): array
    {
        $whereClause = $tenantId ? "WHERE s.tenant_id = :tenant_id" : "";
        $params = $tenantId ? ['tenant_id' => $tenantId] : [];

        $sql = "
            SELECT 
                s.plan_id,
                COUNT(*) as subscriptions,
                COALESCE(SUM(s.amount), 0) as mrr
            FROM subscriptions s
            {$whereClause}
            AND LOWER(s.status) IN ('active', 'trialing')
            GROUP BY s.plan_id
            ORDER BY mrr DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function($row) {
            return [
                'plan_id' => $row['plan_id'],
                'subscriptions' => (int)$row['subscriptions'],
                'mrr' => round((float)$row['mrr'], 2)
            ];
        }, $results);
    }

    /**
     * Calcula taxa de churn
     * 
     * @param int|null $tenantId ID do tenant
     * @param array $period Período ['start' => timestamp, 'end' => timestamp]
     * @return array Dados do churn
     */
    public function getChurnRate(?int $tenantId = null, array $period = []): array
    {
        try {
            $cacheKey = sprintf(
                'stripe_metrics:churn:%s:%s',
                $tenantId ?? 'all',
                md5(json_encode($period))
            );
            $cached = \App\Services\CacheService::getJson($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            // Se não especificado, usa último mês
            if (empty($period)) {
                $period = [
                    'start' => strtotime('-1 month'),
                    'end' => time()
                ];
            }

            $whereClause = $tenantId ? "WHERE s.tenant_id = :tenant_id" : "";
            $params = $tenantId ? ['tenant_id' => $tenantId] : [];

            // Assinaturas ativas no início do período
            $activeStartSql = "
                SELECT COUNT(*) as count
                FROM subscriptions s
                {$whereClause}
                AND LOWER(s.status) = 'active'
                AND s.created_at <= :period_start
            ";

            $stmt = $this->db->prepare($activeStartSql);
            $params['period_start'] = date('Y-m-d H:i:s', $period['start']);
            $stmt->execute($params);
            $activeStart = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];

            // Assinaturas canceladas no período
            $canceledSql = "
                SELECT COUNT(*) as count
                FROM subscriptions s
                {$whereClause}
                AND LOWER(s.status) = 'canceled'
                AND s.updated_at >= :period_start
                AND s.updated_at <= :period_end
            ";

            $stmt = $this->db->prepare($canceledSql);
            $params['period_end'] = date('Y-m-d H:i:s', $period['end']);
            $stmt->execute($params);
            $canceled = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];

            // Calcula churn rate
            $churnRate = $activeStart > 0 ? ($canceled / $activeStart) * 100 : 0;
            $retentionRate = 100 - $churnRate;

            $data = [
                'churn_rate' => round($churnRate, 2),
                'retention_rate' => round($retentionRate, 2),
                'active_start' => $activeStart,
                'canceled' => $canceled,
                'period' => [
                    'start' => date('Y-m-d H:i:s', $period['start']),
                    'end' => date('Y-m-d H:i:s', $period['end'])
                ],
                'calculated_at' => date('Y-m-d H:i:s')
            ];

            // Cache por 5 minutos
            \App\Services\CacheService::setJson($cacheKey, $data, 300);

            return $data;
        } catch (\Exception $e) {
            Logger::error("Erro ao calcular churn rate", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            return [
                'churn_rate' => 0,
                'retention_rate' => 100,
                'active_start' => 0,
                'canceled' => 0,
                'period' => [],
                'calculated_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Calcula taxa de conversão
     * 
     * @param int|null $tenantId ID do tenant
     * @param array $period Período
     * @return array Dados de conversão
     */
    public function getConversionRate(?int $tenantId = null, array $period = []): array
    {
        try {
            $cacheKey = sprintf(
                'stripe_metrics:conversion:%s:%s',
                $tenantId ?? 'all',
                md5(json_encode($period))
            );
            $cached = \App\Services\CacheService::getJson($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            // Se não especificado, usa último mês
            if (empty($period)) {
                $period = [
                    'start' => strtotime('-1 month'),
                    'end' => time()
                ];
            }

            $whereClause = $tenantId ? "WHERE tenant_id = :tenant_id" : "";
            $params = $tenantId ? ['tenant_id' => $tenantId] : [];

            // Total de customers
            $customersSql = "
                SELECT COUNT(*) as count
                FROM customers
                {$whereClause}
                AND created_at >= :period_start
                AND created_at <= :period_end
            ";

            $stmt = $this->db->prepare($customersSql);
            $params['period_start'] = date('Y-m-d H:i:s', $period['start']);
            $params['period_end'] = date('Y-m-d H:i:s', $period['end']);
            $stmt->execute($params);
            $totalCustomers = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];

            // Total de assinaturas criadas no período
            $subscriptionsSql = "
                SELECT COUNT(*) as count
                FROM subscriptions
                {$whereClause}
                AND created_at >= :period_start
                AND created_at <= :period_end
            ";

            $stmt = $this->db->prepare($subscriptionsSql);
            $stmt->execute($params);
            $totalSubscriptions = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];

            // Taxa de conversão
            $conversionRate = $totalCustomers > 0 ? ($totalSubscriptions / $totalCustomers) * 100 : 0;

            $data = [
                'conversion_rate' => round($conversionRate, 2),
                'total_customers' => $totalCustomers,
                'total_subscriptions' => $totalSubscriptions,
                'period' => [
                    'start' => date('Y-m-d H:i:s', $period['start']),
                    'end' => date('Y-m-d H:i:s', $period['end'])
                ],
                'calculated_at' => date('Y-m-d H:i:s')
            ];

            // Cache por 5 minutos
            \App\Services\CacheService::setJson($cacheKey, $data, 300);

            return $data;
        } catch (\Exception $e) {
            Logger::error("Erro ao calcular conversion rate", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
            return [
                'conversion_rate' => 0,
                'total_customers' => 0,
                'total_subscriptions' => 0,
                'period' => [],
                'calculated_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Calcula ARR (Annual Recurring Revenue)
     * 
     * @param int|null $tenantId ID do tenant
     * @return array Dados do ARR
     */
    public function getARR(?int $tenantId = null): array
    {
        $mrr = $this->getMRR($tenantId);
        $arr = $mrr['mrr'] * 12;

        return [
            'arr' => round($arr, 2),
            'mrr' => $mrr['mrr'],
            'currency' => $mrr['currency'],
            'by_plan' => array_map(function($plan) {
                return [
                    'plan_id' => $plan['plan_id'],
                    'subscriptions' => $plan['subscriptions'],
                    'arr' => round($plan['mrr'] * 12, 2),
                    'mrr' => $plan['mrr']
                ];
            }, $mrr['by_plan']),
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Obtém todas as métricas principais
     * 
     * @param int|null $tenantId ID do tenant
     * @return array Todas as métricas
     */
    public function getAllMetrics(?int $tenantId = null): array
    {
        $period = [
            'start' => strtotime('-1 month'),
            'end' => time()
        ];

        return [
            'mrr' => $this->getMRR($tenantId),
            'arr' => $this->getARR($tenantId),
            'churn' => $this->getChurnRate($tenantId, $period),
            'conversion' => $this->getConversionRate($tenantId, $period),
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }
}

