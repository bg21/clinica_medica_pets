<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Customer;
use App\Models\StripeEvent;
use App\Utils\Database;
use App\Services\Logger;
use App\Services\EmailService;

/**
 * Service para gerenciar alertas do Stripe
 * 
 * Tipos de alertas:
 * - Falhas de pagamento
 * - Disputas/chargebacks
 * - Webhooks falhando
 * - Assinaturas canceladas
 * - Métricas de performance (tempo de resposta, taxa de erro)
 */
class StripeAlertService
{
    private Subscription $subscriptionModel;
    private Customer $customerModel;
    private StripeEvent $eventModel;
    private EmailService $emailService;
    private \PDO $db;

    // Thresholds configuráveis
    private int $failedPaymentThreshold = 3; // Número de falhas antes de alertar
    private int $webhookFailureThreshold = 5; // Número de webhooks falhando
    private float $errorRateThreshold = 5.0; // Taxa de erro em %

    public function __construct()
    {
        $this->subscriptionModel = new Subscription();
        $this->customerModel = new Customer();
        $this->eventModel = new StripeEvent();
        $this->emailService = new EmailService();
        $this->db = Database::getInstance();

        // Carrega thresholds do config
        $this->failedPaymentThreshold = (int)\Config::get('STRIPE_ALERT_FAILED_PAYMENT_THRESHOLD', '3');
        $this->webhookFailureThreshold = (int)\Config::get('STRIPE_ALERT_WEBHOOK_FAILURE_THRESHOLD', '5');
        $this->errorRateThreshold = (float)\Config::get('STRIPE_ALERT_ERROR_RATE_THRESHOLD', '5.0');
    }

    /**
     * Verifica alertas de falhas de pagamento
     * 
     * @param int|null $tenantId ID do tenant (null para todos)
     * @param int $hours Últimas N horas para verificar (padrão: 24)
     * @return array Alertas encontrados
     */
    public function checkFailedPayments(?int $tenantId = null, int $hours = 24): array
    {
        $alerts = [];

        try {
            $dateFrom = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

            // Busca eventos de falha de pagamento
            // Nota: stripe_events não tem tenant_id, então filtramos por metadata depois
            $whereClause = "WHERE event_type = 'invoice.payment_failed' AND created_at >= :date_from";
            
            $params = ['date_from' => $dateFrom];

            $sql = "
                SELECT 
                    event_id,
                    event_type,
                    created_at,
                    metadata
                FROM stripe_events
                {$whereClause}
                ORDER BY created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $failedPayments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Agrupa por customer para identificar padrões
            $customerFailures = [];
            foreach ($failedPayments as $failure) {
                // Extrai metadata do campo data (JSON)
                $eventData = json_decode($failure['data'] ?? '{}', true);
                $objectData = $eventData['data']['object'] ?? [];
                $customerId = $objectData['customer'] ?? null;

                // Se temos tenant_id, filtra por tenant através do customer
                if ($tenantId && $customerId) {
                    // Busca customer no banco para verificar tenant
                    $customer = $this->customerModel->findByStripeId($customerId);
                    if (!$customer || $customer['tenant_id'] != $tenantId) {
                        continue; // Pula se não for do tenant
                    }
                }

                if ($customerId) {
                    if (!isset($customerFailures[$customerId])) {
                        $customerFailures[$customerId] = [];
                    }
                    $customerFailures[$customerId][] = $failure;
                }
            }

            // Gera alertas para customers com múltiplas falhas
            foreach ($customerFailures as $customerId => $failures) {
                if (count($failures) >= $this->failedPaymentThreshold) {
                    $alerts[] = [
                        'type' => 'failed_payment',
                        'severity' => 'warning',
                        'customer_id' => $customerId,
                        'failure_count' => count($failures),
                        'threshold' => $this->failedPaymentThreshold,
                        'message' => "Customer {$customerId} teve " . count($failures) . " falhas de pagamento nas últimas {$hours} horas",
                        'failures' => array_slice($failures, 0, 5), // Últimas 5 falhas
                        'detected_at' => date('Y-m-d H:i:s')
                    ];

                    Logger::warning("Alerta: Múltiplas falhas de pagamento", [
                        'customer_id' => $customerId,
                        'failure_count' => count($failures),
                        'tenant_id' => $tenantId
                    ]);
                }
            }

        } catch (\Exception $e) {
            Logger::error("Erro ao verificar alertas de falhas de pagamento", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
        }

        return $alerts;
    }

    /**
     * Verifica alertas de disputas/chargebacks
     * 
     * @param int|null $tenantId ID do tenant
     * @param int $hours Últimas N horas
     * @return array Alertas encontrados
     */
    public function checkDisputes(?int $tenantId = null, int $hours = 24): array
    {
        $alerts = [];

        try {
            $dateFrom = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

            // Busca eventos de disputas
            // Nota: stripe_events não tem tenant_id, então filtramos por metadata depois
            $whereClause = "WHERE event_type = 'charge.dispute.created' AND created_at >= :date_from";

            $params = ['date_from' => $dateFrom];

            $sql = "
                SELECT 
                    event_id,
                    event_type,
                    created_at,
                    data
                FROM stripe_events
                {$whereClause}
                ORDER BY created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $disputes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($disputes as $dispute) {
                // Extrai dados do evento (campo 'data' contém o JSON completo)
                $eventData = json_decode($dispute['data'] ?? '{}', true);
                $objectData = $eventData['data']['object'] ?? [];
                $chargeId = $objectData['charge'] ?? 'unknown';
                $amount = $objectData['amount'] ?? 0;

                // Se temos tenant_id, filtra por tenant através do charge
                if ($tenantId && $chargeId !== 'unknown') {
                    // Busca charge no Stripe para obter customer, depois verifica tenant
                    // Por enquanto, apenas loga todos (pode ser otimizado depois)
                }

                $alerts[] = [
                    'type' => 'dispute',
                    'severity' => 'critical',
                    'event_id' => $dispute['event_id'],
                    'charge_id' => $chargeId,
                    'amount' => $amount,
                    'message' => "Nova disputa/chargeback detectada: Charge {$chargeId}, Valor: " . number_format($amount / 100, 2, ',', '.'),
                    'created_at' => $dispute['created_at'],
                    'detected_at' => date('Y-m-d H:i:s')
                ];

                Logger::warning("Alerta: Disputa/chargeback detectada", [
                    'event_id' => $dispute['event_id'],
                    'charge_id' => $chargeId,
                    'amount' => $amount,
                    'tenant_id' => $tenantId
                ]);
            }

        } catch (\Exception $e) {
            Logger::error("Erro ao verificar alertas de disputas", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
        }

        return $alerts;
    }

    /**
     * Verifica alertas de webhooks falhando
     * 
     * @param int|null $tenantId ID do tenant
     * @param int $hours Últimas N horas
     * @return array Alertas encontrados
     */
    public function checkWebhookFailures(?int $tenantId = null, int $hours = 24): array
    {
        $alerts = [];

        try {
            $dateFrom = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

            // Busca webhooks que falharam (não processados)
            // Nota: stripe_events não tem tenant_id
            $whereClause = "WHERE created_at >= :date_from AND (processed = 0 OR processed IS NULL)";

            $params = ['date_from' => $dateFrom];

            $sql = "
                SELECT 
                    COUNT(*) as failure_count,
                    event_type
                FROM stripe_events
                {$whereClause}
                GROUP BY event_type
                HAVING failure_count >= :threshold
            ";

            $stmt = $this->db->prepare($sql);
            $params['threshold'] = $this->webhookFailureThreshold;
            $stmt->execute($params);
            $failures = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($failures as $failure) {
                $alerts[] = [
                    'type' => 'webhook_failure',
                    'severity' => 'critical',
                    'event_type' => $failure['event_type'],
                    'failure_count' => (int)$failure['failure_count'],
                    'threshold' => $this->webhookFailureThreshold,
                    'message' => "Webhooks do tipo '{$failure['event_type']}' falhando: {$failure['failure_count']} eventos não processados",
                    'detected_at' => date('Y-m-d H:i:s')
                ];

                Logger::error("Alerta: Webhooks falhando", [
                    'event_type' => $failure['event_type'],
                    'failure_count' => $failure['failure_count'],
                    'tenant_id' => $tenantId
                ]);
            }

        } catch (\Exception $e) {
            Logger::error("Erro ao verificar alertas de webhooks falhando", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
        }

        return $alerts;
    }

    /**
     * Verifica alertas de assinaturas canceladas
     * 
     * @param int|null $tenantId ID do tenant
     * @param int $hours Últimas N horas
     * @return array Alertas encontrados
     */
    public function checkCanceledSubscriptions(?int $tenantId = null, int $hours = 24): array
    {
        $alerts = [];

        try {
            $dateFrom = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

            // Busca assinaturas canceladas recentemente
            $whereClause = $tenantId
                ? "WHERE status = 'canceled' AND updated_at >= :date_from AND tenant_id = :tenant_id"
                : "WHERE status = 'canceled' AND updated_at >= :date_from";

            $params = ['date_from' => $dateFrom];
            if ($tenantId) {
                $params['tenant_id'] = $tenantId;
            }

            $sql = "
                SELECT 
                    id,
                    stripe_subscription_id,
                    customer_id,
                    plan_id,
                    amount,
                    currency,
                    updated_at
                FROM subscriptions
                {$whereClause}
                ORDER BY updated_at DESC
                LIMIT 50
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $canceled = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $totalCanceled = count($canceled);
            $totalRevenueLost = array_sum(array_column($canceled, 'amount'));

            if ($totalCanceled > 0) {
                $alerts[] = [
                    'type' => 'subscription_canceled',
                    'severity' => 'warning',
                    'total_canceled' => $totalCanceled,
                    'revenue_lost' => round($totalRevenueLost, 2),
                    'currency' => $canceled[0]['currency'] ?? 'BRL',
                    'message' => "{$totalCanceled} assinatura(s) cancelada(s) nas últimas {$hours} horas. Receita perdida: " . number_format($totalRevenueLost, 2, ',', '.'),
                    'subscriptions' => array_slice($canceled, 0, 10), // Primeiras 10
                    'detected_at' => date('Y-m-d H:i:s')
                ];

                Logger::warning("Alerta: Assinaturas canceladas", [
                    'total_canceled' => $totalCanceled,
                    'revenue_lost' => $totalRevenueLost,
                    'tenant_id' => $tenantId
                ]);
            }

        } catch (\Exception $e) {
            Logger::error("Erro ao verificar alertas de assinaturas canceladas", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
        }

        return $alerts;
    }

    /**
     * Verifica métricas de performance do Stripe
     * 
     * @param int|null $tenantId ID do tenant
     * @param int $hours Últimas N horas
     * @return array Alertas encontrados
     */
    public function checkPerformanceMetrics(?int $tenantId = null, int $hours = 24): array
    {
        $alerts = [];

        try {
            // Busca métricas de performance relacionadas ao Stripe
            $dateFrom = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

            $whereClause = $tenantId
                ? "WHERE endpoint LIKE '%stripe%' OR endpoint LIKE '%payment%' OR endpoint LIKE '%subscription%' AND created_at >= :date_from AND tenant_id = :tenant_id"
                : "WHERE (endpoint LIKE '%stripe%' OR endpoint LIKE '%payment%' OR endpoint LIKE '%subscription%') AND created_at >= :date_from";

            $params = ['date_from' => $dateFrom];
            if ($tenantId) {
                $params['tenant_id'] = $tenantId;
            }

            $sql = "
                SELECT 
                    endpoint,
                    method,
                    AVG(duration_ms) as avg_duration,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN duration_ms > 1000 THEN 1 ELSE 0 END) as slow_requests
                FROM performance_metrics
                {$whereClause}
                GROUP BY endpoint, method
                HAVING avg_duration > 1000 OR (slow_requests / total_requests * 100) > :error_rate
            ";

            $stmt = $this->db->prepare($sql);
            $params['error_rate'] = $this->errorRateThreshold;
            $stmt->execute($params);
            $metrics = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($metrics as $metric) {
                $errorRate = $metric['total_requests'] > 0 
                    ? ($metric['slow_requests'] / $metric['total_requests']) * 100 
                    : 0;

                if ($metric['avg_duration'] > 1000 || $errorRate > $this->errorRateThreshold) {
                    $alerts[] = [
                        'type' => 'performance',
                        'severity' => $metric['avg_duration'] > 2000 ? 'critical' : 'warning',
                        'endpoint' => $metric['endpoint'],
                        'method' => $metric['method'],
                        'avg_duration_ms' => round((float)$metric['avg_duration'], 2),
                        'total_requests' => (int)$metric['total_requests'],
                        'slow_requests' => (int)$metric['slow_requests'],
                        'error_rate' => round($errorRate, 2),
                        'message' => "Endpoint {$metric['method']} {$metric['endpoint']} está lento: média de {$metric['avg_duration']}ms, taxa de erro: {$errorRate}%",
                        'detected_at' => date('Y-m-d H:i:s')
                    ];
                }
            }

        } catch (\Exception $e) {
            Logger::error("Erro ao verificar métricas de performance", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);
        }

        return $alerts;
    }

    /**
     * Verifica todos os tipos de alertas
     * 
     * @param int|null $tenantId ID do tenant
     * @param int $hours Últimas N horas
     * @return array Todos os alertas encontrados
     */
    public function checkAllAlerts(?int $tenantId = null, int $hours = 24): array
    {
        return [
            'failed_payments' => $this->checkFailedPayments($tenantId, $hours),
            'disputes' => $this->checkDisputes($tenantId, $hours),
            'webhook_failures' => $this->checkWebhookFailures($tenantId, $hours),
            'canceled_subscriptions' => $this->checkCanceledSubscriptions($tenantId, $hours),
            'performance' => $this->checkPerformanceMetrics($tenantId, $hours),
            'summary' => [
                'total' => 0, // Será calculado
                'critical' => 0,
                'warnings' => 0,
                'checked_at' => date('Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Envia notificações de alertas críticos por email
     * 
     * @param array $alerts Alertas a notificar
     * @param string|null $email Email para enviar (null para usar config)
     */
    public function sendAlertNotifications(array $alerts, ?string $email = null): void
    {
        try {
            $criticalAlerts = [];
            foreach ($alerts as $type => $typeAlerts) {
                if ($type === 'summary') continue;
                foreach ($typeAlerts as $alert) {
                    if (($alert['severity'] ?? 'warning') === 'critical') {
                        $criticalAlerts[] = $alert;
                    }
                }
            }

            if (empty($criticalAlerts)) {
                return; // Nenhum alerta crítico
            }

            // Aqui você pode integrar com EmailService para enviar notificações
            // Por enquanto, apenas loga
            Logger::warning("Alertas críticos detectados - notificação deveria ser enviada", [
                'critical_count' => count($criticalAlerts),
                'alerts' => $criticalAlerts
            ]);

        } catch (\Exception $e) {
            Logger::error("Erro ao enviar notificações de alertas", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verifica e envia alertas para falhas críticas
     * 
     * Verifica webhooks falhados e rate limits nas últimas 24h
     * e envia alertas se os thresholds forem ultrapassados.
     * 
     * @param int $hours Últimas N horas para verificar (padrão: 24)
     * @return array Alertas encontrados
     */
    public function checkCriticalFailures(int $hours = 24): array
    {
        $alerts = [];

        try {
            // Verifica webhooks falhados nas últimas N horas
            $failedEvents = $this->eventModel->findFailedEvents($hours);
            
            if (count($failedEvents) > 10) {
                $alertData = [
                    'count' => count($failedEvents),
                    'period' => "{$hours}h",
                    'event_types' => $this->eventModel->countFailedEventsByType($hours)
                ];
                
                $this->sendAlert('stripe_webhook_failures', $alertData);
                
                $alerts[] = [
                    'type' => 'stripe_webhook_failures',
                    'severity' => 'critical',
                    'count' => count($failedEvents),
                    'period' => "{$hours}h",
                    'message' => count($failedEvents) . " webhooks falhados nas últimas {$hours} horas",
                    'detected_at' => date('Y-m-d H:i:s')
                ];
            }
            
            // Verifica rate limits
            $rateLimitErrors = $this->getRateLimitErrors($hours);
            if (count($rateLimitErrors) > 5) {
                $alertData = [
                    'count' => count($rateLimitErrors),
                    'period' => "{$hours}h",
                    'errors' => array_slice($rateLimitErrors, 0, 10) // Primeiros 10
                ];
                
                $this->sendAlert('stripe_rate_limits', $alertData);
                
                $alerts[] = [
                    'type' => 'stripe_rate_limits',
                    'severity' => 'critical',
                    'count' => count($rateLimitErrors),
                    'period' => "{$hours}h",
                    'message' => count($rateLimitErrors) . " erros de rate limit nas últimas {$hours} horas",
                    'detected_at' => date('Y-m-d H:i:s')
                ];
            }

        } catch (\Exception $e) {
            Logger::error("Erro ao verificar falhas críticas do Stripe", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $alerts;
    }

    /**
     * Obtém erros de rate limit do Stripe nas últimas N horas
     * 
     * Busca nos logs por erros relacionados a rate limits do Stripe.
     * Verifica tanto eventos de alta frequência quanto erros específicos de rate limit.
     * 
     * @param int $hours Últimas N horas para buscar (padrão: 24)
     * @return array Lista de erros de rate limit encontrados
     */
    private function getRateLimitErrors(int $hours = 24): array
    {
        $errors = [];
        
        try {
            $dateFrom = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            // 1. Verifica se há muitos eventos do mesmo tipo em um curto período
            // (pode indicar rate limiting ou problemas de processamento)
            $sql = "
                SELECT 
                    event_type,
                    COUNT(*) as event_count,
                    MIN(created_at) as first_event,
                    MAX(created_at) as last_event
                FROM stripe_events
                WHERE created_at >= :date_from
                GROUP BY event_type
                HAVING event_count > 100
                ORDER BY event_count DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['date_from' => $dateFrom]);
            $highFrequencyEvents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($highFrequencyEvents as $event) {
                // Se há muitos eventos do mesmo tipo em pouco tempo, pode ser rate limit
                $firstEvent = strtotime($event['first_event']);
                $lastEvent = strtotime($event['last_event']);
                $timeSpan = $lastEvent - $firstEvent;
                
                // Se mais de 100 eventos em menos de 1 hora, pode indicar problema
                if ($timeSpan < 3600 && $event['event_count'] > 100) {
                    $errors[] = [
                        'type' => 'high_frequency_events',
                        'event_type' => $event['event_type'],
                        'event_count' => $event['event_count'],
                        'time_span_seconds' => $timeSpan,
                        'events_per_minute' => round($event['event_count'] / max($timeSpan / 60, 1), 2),
                        'first_event' => $event['first_event'],
                        'last_event' => $event['last_event']
                    ];
                }
            }

            // 2. Busca por erros específicos de rate limit nos logs de erro
            // (se houver uma tabela de logs de erros do Stripe)
            // Por enquanto, verifica se há muitos eventos não processados
            // que podem indicar problemas de processamento devido a rate limits
            
            $sql = "
                SELECT 
                    COUNT(*) as unprocessed_count,
                    COUNT(DISTINCT event_type) as event_types_count
                FROM stripe_events
                WHERE (processed = 0 OR processed IS NULL)
                    AND created_at >= :date_from
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['date_from' => $dateFrom]);
            $unprocessed = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Se há muitos eventos não processados, pode indicar problemas
            if ($unprocessed && $unprocessed['unprocessed_count'] > 50) {
                $errors[] = [
                    'type' => 'unprocessed_events',
                    'unprocessed_count' => (int)$unprocessed['unprocessed_count'],
                    'event_types_affected' => (int)$unprocessed['event_types_count'],
                    'message' => "Muitos eventos não processados podem indicar problemas de rate limit ou processamento"
                ];
            }

        } catch (\Exception $e) {
            Logger::error("Erro ao buscar erros de rate limit", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $errors;
    }

    /**
     * Envia alerta
     * 
     * Registra alerta crítico no log e pode integrar com serviços externos
     * (Slack, PagerDuty, email, etc.)
     * 
     * @param string $type Tipo do alerta (ex: 'stripe_webhook_failures', 'stripe_rate_limits')
     * @param array $data Dados do alerta
     */
    private function sendAlert(string $type, array $data): void
    {
        // Registra no log como crítico
        Logger::critical("Alerta Stripe: {$type}", $data);
        
        // ✅ FUTURO: Pode integrar com serviços externos aqui
        // - Slack webhook
        // - PagerDuty
        // - Email (usando EmailService)
        // - SMS
        // - etc.
        
        // Exemplo de integração futura:
        /*
        try {
            $slackWebhook = \Config::get('SLACK_WEBHOOK_URL');
            if ($slackWebhook) {
                $this->sendSlackAlert($type, $data, $slackWebhook);
            }
        } catch (\Exception $e) {
            Logger::error("Erro ao enviar alerta para Slack", [
                'error' => $e->getMessage()
            ]);
        }
        */
    }
}

