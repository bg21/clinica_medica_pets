<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\StripeEvent;
use App\Services\StripeService;
use App\Services\EmailService;
use App\Services\Logger;

/**
 * Serviço central de pagamentos
 * Coordena lógica de negócio entre Stripe e banco de dados
 */
class PaymentService
{
    private StripeService $stripeService;
    private Customer $customerModel;
    private Subscription $subscriptionModel;
    private StripeEvent $eventModel;
    private EmailService $emailService;

    public function __construct(
        StripeService $stripeService,
        Customer $customerModel,
        Subscription $subscriptionModel,
        StripeEvent $eventModel
    ) {
        $this->stripeService = $stripeService;
        $this->customerModel = $customerModel;
        $this->subscriptionModel = $subscriptionModel;
        $this->eventModel = $eventModel;
        $this->emailService = new EmailService();
    }

    /**
     * Busca ou cria cliente para o tenant
     * Útil para assinaturas do SaaS onde o tenant precisa ter um customer
     */
    public function getOrCreateCustomer(int $tenantId, ?string $email = null, ?string $name = null): array
    {
        // Busca customer existente do tenant
        $result = $this->customerModel->findByTenant($tenantId, 1, 1);
        $existingCustomers = $result['data'] ?? [];
        
        if (!empty($existingCustomers)) {
            // Retorna o primeiro customer encontrado
            $customer = $existingCustomers[0];
            $stripeCustomerId = $customer['stripe_customer_id'];
            
            // ✅ VALIDAÇÃO: Verifica se o customer existe no Stripe
            // Se o ID for um exemplo (cus_exemplo001) ou não existir, cria um novo
            if (!empty($stripeCustomerId) && !str_starts_with($stripeCustomerId, 'cus_exemplo')) {
                try {
                    // Tenta buscar o customer no Stripe para validar
                    $stripeCustomer = $this->stripeService->getCustomer($stripeCustomerId);
                    
                    // Se encontrou, retorna o customer existente
                    Logger::info("Customer existente validado no Stripe", [
                        'tenant_id' => $tenantId,
                        'customer_id' => $customer['id'],
                        'stripe_customer_id' => $stripeCustomerId
                    ]);
                    
                    return [
                        'id' => (int)$customer['id'],
                        'stripe_customer_id' => $stripeCustomerId,
                        'email' => $customer['email'],
                        'name' => $customer['name']
                    ];
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    // Customer não existe no Stripe (pode ser ID antigo ou inválido)
                    Logger::warning("Customer não encontrado no Stripe, criando novo", [
                        'tenant_id' => $tenantId,
                        'old_stripe_customer_id' => $stripeCustomerId,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Remove o customer inválido do banco (soft delete)
                    $this->customerModel->delete($customer['id']);
                    
                    // Continua para criar um novo customer
                } catch (\Exception $e) {
                    Logger::error("Erro ao validar customer no Stripe", [
                        'tenant_id' => $tenantId,
                        'stripe_customer_id' => $stripeCustomerId,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Em caso de erro inesperado, também cria um novo
                }
            } else {
                // ID de exemplo ou vazio, remove e cria novo
                Logger::warning("Customer com ID de exemplo encontrado, criando novo", [
                    'tenant_id' => $tenantId,
                    'old_stripe_customer_id' => $stripeCustomerId
                ]);
                
                if (!empty($customer['id'])) {
                    $this->customerModel->delete($customer['id']);
                }
            }
        }

        // Se não existe ou é inválido, cria novo
        $data = [];
        if ($email) {
            $data['email'] = $email;
        }
        if ($name) {
            $data['name'] = $name;
        }

        return $this->createCustomer($tenantId, $data);
    }

    /**
     * Cria cliente e persiste no banco
     * 
     * IMPORTANTE: Para assinaturas SaaS, deve usar a conta da PLATAFORMA (não a do tenant)
     * O StripeService injetado já usa a conta padrão (STRIPE_SECRET do .env)
     */
    public function createCustomer(int $tenantId, array $data): array
    {
        // ✅ Cria no Stripe usando a conta da PLATAFORMA
        // O StripeService injetado já usa a conta padrão (STRIPE_SECRET do .env)
        $stripeCustomer = $this->stripeService->createCustomer($data);

        // Persiste no banco
        $customerId = $this->customerModel->createOrUpdate(
            $tenantId,
            $stripeCustomer->id,
            [
                'email' => $stripeCustomer->email,
                'name' => $stripeCustomer->name,
                'metadata' => $stripeCustomer->metadata->toArray()
            ]
        );

        Logger::info("Cliente criado na conta da plataforma", [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'stripe_customer_id' => $stripeCustomer->id,
            'using_platform_account' => true
        ]);

        return [
            'id' => $customerId,
            'stripe_customer_id' => $stripeCustomer->id,
            'email' => $stripeCustomer->email,
            'name' => $stripeCustomer->name
        ];
    }

    /**
     * Cria assinatura e persiste no banco
     * 
     * @param int $tenantId ID do tenant
     * @param int $customerId ID do customer no banco
     * @param string $priceId ID do preço no Stripe
     * @param array $metadata Metadados adicionais
     * @param int|null $trialPeriodDays Dias de trial period (opcional)
     * @param string|null $paymentBehavior Comportamento de pagamento (opcional)
     */
    public function createSubscription(
        int $tenantId, 
        int $customerId, 
        string $priceId, 
        array $metadata = [],
        ?int $trialPeriodDays = null,
        ?string $paymentBehavior = null
    ): array {
        $customer = $this->customerModel->findById($customerId);
        if (!$customer || $customer['tenant_id'] != $tenantId) {
            throw new \RuntimeException("Cliente não encontrado");
        }

        // Prepara dados para criar no Stripe
        $subscriptionData = [
            'customer_id' => $customer['stripe_customer_id'],
            'price_id' => $priceId,
            'metadata' => array_merge($metadata, ['tenant_id' => $tenantId])
        ];

        // Adiciona trial_period_days se fornecido
        if ($trialPeriodDays !== null) {
            $subscriptionData['trial_period_days'] = $trialPeriodDays;
        }

        // Adiciona payment_behavior se fornecido
        if ($paymentBehavior !== null) {
            $subscriptionData['payment_behavior'] = $paymentBehavior;
        }

        // Cria no Stripe
        $stripeSubscription = $this->stripeService->createSubscription($subscriptionData);

        // Persiste no banco
        $subscriptionId = $this->subscriptionModel->createOrUpdate(
            $tenantId,
            $customerId,
            $stripeSubscription->toArray()
        );

        // Prepara dados novos para histórico
        $newData = [
            'status' => $stripeSubscription->status,
            'plan_id' => $stripeSubscription->items->data[0]->price->id ?? null,
            'amount' => ($stripeSubscription->items->data[0]->price->unit_amount ?? 0) / 100,
            'currency' => strtoupper($stripeSubscription->currency ?? 'usd'),
            'current_period_end' => $stripeSubscription->current_period_end 
                ? date('Y-m-d H:i:s', $stripeSubscription->current_period_end) 
                : null,
            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end ? 1 : 0,
            'metadata' => $stripeSubscription->metadata->toArray()
        ];

        // Registra no histórico
        // Tenta obter user_id do Flight (pode ser null se for API Key)
        $userId = null;
        if (class_exists('\Flight')) {
            $userId = \Flight::get('user_id');
        }
        
        $historyModel = new \App\Models\SubscriptionHistory();
        $historyModel->recordChange(
            $subscriptionId,
            $tenantId,
            \App\Models\SubscriptionHistory::CHANGE_TYPE_CREATED,
            [],
            $newData,
            \App\Models\SubscriptionHistory::CHANGED_BY_API,
            "Assinatura criada com plano {$newData['plan_id']}",
            $userId
        );

        Logger::info("Assinatura criada", [
            'tenant_id' => $tenantId,
            'subscription_id' => $subscriptionId,
            'stripe_subscription_id' => $stripeSubscription->id
        ]);

        return [
            'id' => $subscriptionId,
            'stripe_subscription_id' => $stripeSubscription->id,
            'status' => $stripeSubscription->status,
            'plan_id' => $stripeSubscription->items->data[0]->price->id ?? null
        ];
    }

    /**
     * Processa webhook do Stripe
     */
    public function processWebhook(\Stripe\Event $event): void
    {
        $eventId = $event->id;
        $eventType = $event->type;

        // Verifica idempotência
        if ($this->eventModel->isProcessed($eventId)) {
            Logger::info("Evento já processado", ['event_id' => $eventId]);
            return;
        }

        // Registra evento
        $this->eventModel->register($eventId, $eventType, $event->toArray());

        try {
            // Processa evento
            switch ($eventType) {
                // Checkout
                case 'checkout.session.completed':
                    $this->handleCheckoutCompleted($event);
                    break;

                // Payment Intents
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event);
                    break;

                // Invoices
                case 'invoice.paid':
                    $this->handleInvoicePaid($event);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event);
                    break;

                case 'invoice.upcoming':
                    $this->handleInvoiceUpcoming($event);
                    break;

                // Subscriptions
                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionUpdate($event);
                    break;

                // Stripe Connect
                case 'account.updated':
                    $this->handleAccountUpdated($event);
                    break;

                case 'customer.subscription.trial_will_end':
                    $this->handleSubscriptionTrialWillEnd($event);
                    break;

                // Charges
                case 'charge.dispute.created':
                    $this->handleChargeDisputeCreated($event);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event);
                    break;

                default:
                    Logger::debug("Evento não tratado", ['event_type' => $eventType]);
            }

            // Marca como processado
            $this->eventModel->markAsProcessed($eventId, $eventType, $event->toArray());

            Logger::info("Webhook processado com sucesso", [
                'event_id' => $eventId,
                'event_type' => $eventType
            ]);
        } catch (\Exception $e) {
            Logger::error("Erro ao processar webhook", [
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Trata checkout.session.completed
     * Salva método de pagamento e define como padrão
     */
    private function handleCheckoutCompleted(\Stripe\Event $event): void
    {
        $session = $event->data->object;
        $customerId = $session->customer;

        if (!$customerId) {
            Logger::warning("Checkout session sem customer", ['session_id' => $session->id]);
            return;
        }

        // Busca customer no banco
        $customer = $this->customerModel->findByStripeId($customerId);
        if (!$customer) {
            Logger::warning("Customer não encontrado no banco", [
                'stripe_customer_id' => $customerId,
                'session_id' => $session->id
            ]);
            return;
        }

        // Obtém a sessão completa com dados expandidos
        $fullSession = $this->stripeService->getCheckoutSession($session->id);

        // Obtém o payment method da sessão
        $paymentMethodId = null;

        // Tenta obter payment method diretamente da sessão (se disponível)
        if (isset($fullSession->payment_method)) {
            $paymentMethodId = is_string($fullSession->payment_method)
                ? $fullSession->payment_method
                : $fullSession->payment_method->id;
        }

        // Para modo subscription, o payment method está na subscription
        if (!$paymentMethodId && $fullSession->mode === 'subscription' && $fullSession->subscription) {
            $subscription = is_string($fullSession->subscription)
                ? $this->stripeService->getSubscription($fullSession->subscription)
                : $fullSession->subscription;
            
            // Tenta obter do default_payment_method da subscription
            if ($subscription && isset($subscription->default_payment_method)) {
                $paymentMethodId = is_string($subscription->default_payment_method)
                    ? $subscription->default_payment_method
                    : $subscription->default_payment_method->id;
            }
            
            // Se não encontrou, tenta obter do customer da subscription
            if (!$paymentMethodId && $subscription && isset($subscription->customer)) {
                $stripeCustomer = is_string($subscription->customer)
                    ? $this->stripeService->getCustomer($subscription->customer)
                    : $subscription->customer;
                
                if ($stripeCustomer && isset($stripeCustomer->invoice_settings->default_payment_method)) {
                    $paymentMethodId = is_string($stripeCustomer->invoice_settings->default_payment_method)
                        ? $stripeCustomer->invoice_settings->default_payment_method
                        : $stripeCustomer->invoice_settings->default_payment_method->id;
                }
            }
        }

        // Para modo payment, o payment method está no payment_intent
        if (!$paymentMethodId && $fullSession->mode === 'payment' && $fullSession->payment_intent) {
            $paymentIntent = is_string($fullSession->payment_intent)
                ? $this->stripeService->getPaymentIntent($fullSession->payment_intent)
                : $fullSession->payment_intent;
            
            if ($paymentIntent && isset($paymentIntent->payment_method)) {
                $paymentMethodId = is_string($paymentIntent->payment_method)
                    ? $paymentIntent->payment_method
                    : $paymentIntent->payment_method->id;
            }
        }

        // Se encontrou payment method, anexa ao customer e define como padrão
        if ($paymentMethodId) {
            try {
                $this->stripeService->attachPaymentMethodToCustomer($paymentMethodId, $customerId);
                
                Logger::info("Payment method salvo e definido como padrão", [
                    'payment_method_id' => $paymentMethodId,
                    'customer_id' => $customer['id'],
                    'stripe_customer_id' => $customerId,
                    'session_id' => $session->id
                ]);
            } catch (\Exception $e) {
                Logger::error("Erro ao salvar payment method", [
                    'error' => $e->getMessage(),
                    'payment_method_id' => $paymentMethodId,
                    'customer_id' => $customerId
                ]);
            }
        } else {
            Logger::warning("Payment method não encontrado na sessão", [
                'session_id' => $session->id,
                'mode' => $fullSession->mode
            ]);
        }

        // Se for modo subscription, cria/atualiza assinatura no banco
        if ($fullSession->mode === 'subscription' && $fullSession->subscription) {
            Logger::info("Processando subscription do checkout", [
                'mode' => $fullSession->mode,
                'has_subscription' => !empty($fullSession->subscription),
                'subscription_id' => is_string($fullSession->subscription) 
                    ? $fullSession->subscription 
                    : ($fullSession->subscription->id ?? 'N/A'),
                'customer_id' => $customer['id'],
                'tenant_id' => $customer['tenant_id']
            ]);
            
            $subscription = is_string($fullSession->subscription)
                ? $this->stripeService->getSubscription($fullSession->subscription)
                : $fullSession->subscription;

            if ($subscription) {
                Logger::info("Subscription obtida do Stripe", [
                    'stripe_subscription_id' => $subscription->id,
                    'status' => $subscription->status ?? 'N/A',
                    'customer_id' => $customer['id']
                ]);
                
                try {
                    $subscriptionId = $this->subscriptionModel->createOrUpdate(
                        $customer['tenant_id'],
                        $customer['id'],
                        $subscription->toArray()
                    );

                    Logger::info("Assinatura criada/atualizada após checkout", [
                        'subscription_id' => $subscriptionId,
                        'stripe_subscription_id' => $subscription->id,
                        'customer_id' => $customer['id'],
                        'tenant_id' => $customer['tenant_id']
                    ]);

                    // Envia email de nova assinatura criada
                    try {
                        $this->emailService->enviarNotificacaoAssinaturaCriada(
                            $subscription->toArray(),
                            $customer
                        );
                    } catch (\Exception $e) {
                        // Log erro, mas não falha o processamento do checkout
                        Logger::error('Erro ao enviar email de assinatura criada', [
                            'error' => $e->getMessage(),
                            'subscription_id' => $subscriptionId,
                            'customer_id' => $customer['id']
                        ]);
                    }
                } catch (\Exception $e) {
                    Logger::error("Erro ao salvar subscription no banco", [
                        'error' => $e->getMessage(),
                        'stripe_subscription_id' => $subscription->id,
                        'customer_id' => $customer['id'],
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            } else {
                Logger::error("Subscription não encontrada no Stripe", [
                    'subscription_id' => is_string($fullSession->subscription) 
                        ? $fullSession->subscription 
                        : 'N/A',
                    'session_id' => $session->id
                ]);
            }
        } else {
            Logger::warning("Checkout não é modo subscription ou não tem subscription", [
                'mode' => $fullSession->mode ?? 'N/A',
                'has_subscription' => !empty($fullSession->subscription),
                'session_id' => $session->id
            ]);
        }

        Logger::info("Checkout completado e processado", [
            'session_id' => $session->id,
            'customer_id' => $customer['id'],
            'mode' => $fullSession->mode
        ]);
    }

    /**
     * Trata invoice.paid
     */
    private function handleInvoicePaid(\Stripe\Event $event): void
    {
        $invoice = $event->data->object;
        Logger::info("Fatura paga", [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer
        ]);

        // ✅ NOVO: Atualiza status de agendamento se invoice estiver vinculada a um agendamento
        try {
            // Verifica se invoice tem metadata com appointment_id
            $metadata = $invoice->metadata ?? null;
            if ($metadata && isset($metadata->appointment_id)) {
                $appointmentService = new \App\Services\AppointmentService(
                    $this->stripeService,
                    new \App\Models\Appointment(),
                    $this->customerModel,
                    new \App\Models\Pet()
                );
                
                $updated = $appointmentService->updateAppointmentStatusFromInvoice($invoice->id);
                
                if ($updated) {
                    Logger::info("Status do agendamento atualizado via webhook", [
                        'invoice_id' => $invoice->id,
                        'appointment_id' => $metadata->appointment_id
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log erro, mas não falha o processamento do webhook
            Logger::error("Erro ao atualizar status do agendamento via invoice.paid", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Trata atualização de assinatura
     */
    private function handleSubscriptionUpdate(\Stripe\Event $event): void
    {
        $stripeSubscription = $event->data->object;
        $subscription = $this->subscriptionModel->findByStripeId($stripeSubscription->id);

        if ($subscription) {
            // Prepara dados antigos para histórico
            $oldData = [
                'status' => $subscription['status'],
                'plan_id' => $subscription['plan_id'],
                'amount' => $subscription['amount'],
                'currency' => $subscription['currency'],
                'current_period_end' => $subscription['current_period_end'],
                'cancel_at_period_end' => $subscription['cancel_at_period_end'],
                'metadata' => $subscription['metadata'] ? json_decode($subscription['metadata'], true) : null
            ];

            // Determina tipo de evento
            $eventType = $event->type;
            $changeType = \App\Models\SubscriptionHistory::CHANGE_TYPE_UPDATED;
            $description = "Assinatura atualizada via webhook: {$eventType}";

            if ($eventType === 'customer.subscription.deleted') {
                $changeType = \App\Models\SubscriptionHistory::CHANGE_TYPE_CANCELED;
                $description = "Assinatura cancelada via webhook";
            } elseif ($oldData['status'] !== $stripeSubscription->status) {
                $changeType = \App\Models\SubscriptionHistory::CHANGE_TYPE_STATUS_CHANGED;
                $description = "Status alterado de {$oldData['status']} para {$stripeSubscription->status}";
            }

            // Atualiza no banco
            $this->subscriptionModel->createOrUpdate(
                $subscription['tenant_id'],
                $subscription['customer_id'],
                $stripeSubscription->toArray()
            );

            // Busca assinatura atualizada
            $updatedSubscription = $this->subscriptionModel->findById($subscription['id']);

            // Prepara dados novos para histórico
            $newData = [
                'status' => $updatedSubscription['status'],
                'plan_id' => $updatedSubscription['plan_id'],
                'amount' => $updatedSubscription['amount'],
                'currency' => $updatedSubscription['currency'],
                'current_period_end' => $updatedSubscription['current_period_end'],
                'cancel_at_period_end' => $updatedSubscription['cancel_at_period_end'],
                'metadata' => $updatedSubscription['metadata'] ? json_decode($updatedSubscription['metadata'], true) : null
            ];

            // Registra no histórico
            // Webhooks não têm user_id (são eventos do Stripe)
            $historyModel = new \App\Models\SubscriptionHistory();
            $historyModel->recordChange(
                $subscription['id'],
                $subscription['tenant_id'],
                $changeType,
                $oldData,
                $newData,
                \App\Models\SubscriptionHistory::CHANGED_BY_WEBHOOK,
                $description,
                null // Webhooks não têm user_id
            );

            Logger::info("Assinatura atualizada", [
                'subscription_id' => $subscription['id'],
                'status' => $stripeSubscription->status,
                'event_type' => $eventType
            ]);

            // Envia email quando assinatura é cancelada
            if ($eventType === 'customer.subscription.deleted') {
                try {
                    $customer = $this->customerModel->findById($subscription['customer_id']);
                    if ($customer) {
                        $this->emailService->enviarNotificacaoAssinaturaCancelada(
                            $stripeSubscription->toArray(),
                            $customer
                        );
                    }
                } catch (\Exception $e) {
                    // Log erro, mas não falha o processamento do webhook
                    Logger::error('Erro ao enviar email de assinatura cancelada', [
                        'error' => $e->getMessage(),
                        'subscription_id' => $subscription['id'],
                        'customer_id' => $subscription['customer_id']
                    ]);
                }
            }
        }
    }

    /**
     * Obtém métodos de pagamento disponíveis para um país/região
     * 
     * @param string $country Código do país (ex: 'br', 'us')
     * @param string $currency Moeda (ex: 'brl', 'usd')
     * @return array Lista de tipos de métodos de pagamento disponíveis
     */
    public function getAvailablePaymentMethods(string $country = 'br', string $currency = 'brl'): array
    {
        $availableMethods = [];
        
        // Métodos sempre disponíveis
        $availableMethods[] = 'card';
        
        // Métodos específicos por país
        if (strtolower($country) === 'br' && strtolower($currency) === 'brl') {
            // Brasil - métodos locais
            $availableMethods[] = 'boleto';
            $availableMethods[] = 'pix';
        }
        
        // Outros países podem ter métodos específicos
        // Ex: 'us_bank_account' para EUA, 'sepa_debit' para Europa, etc.
        
        Logger::info("Métodos de pagamento disponíveis", [
            'country' => $country,
            'currency' => $currency,
            'methods' => $availableMethods
        ]);
        
        return $availableMethods;
    }

    /**
     * Detecta método de pagamento preferido do customer
     * Baseado em histórico de uso, país, e preferências salvas
     * 
     * @param string $customerId ID do customer no Stripe
     * @param string $country Código do país (opcional)
     * @param string $currency Moeda (opcional)
     * @return string|null Tipo de método de pagamento preferido
     */
    public function detectPreferredPaymentMethod(string $customerId, ?string $country = null, ?string $currency = null): ?string
    {
        try {
            // 1. Tenta obter método padrão do customer
            $stripeCustomer = $this->stripeService->getCustomer($customerId);
            
            if ($stripeCustomer && isset($stripeCustomer->invoice_settings->default_payment_method)) {
                $defaultPaymentMethodId = is_string($stripeCustomer->invoice_settings->default_payment_method)
                    ? $stripeCustomer->invoice_settings->default_payment_method
                    : $stripeCustomer->invoice_settings->default_payment_method->id;
                
                $paymentMethod = $this->stripeService->getPaymentMethod($defaultPaymentMethodId);
                if ($paymentMethod && isset($paymentMethod->type)) {
                    Logger::info("Método de pagamento preferido detectado (default do customer)", [
                        'customer_id' => $customerId,
                        'type' => $paymentMethod->type
                    ]);
                    return $paymentMethod->type;
                }
            }
            
            // 2. Analisa histórico de métodos de pagamento usados com sucesso
            $paymentMethods = $this->stripeService->listPaymentMethods($customerId, ['limit' => 100]);
            
            if (!empty($paymentMethods->data)) {
                // Conta uso de cada tipo
                $typeCount = [];
                foreach ($paymentMethods->data as $pm) {
                    $type = $pm->type ?? 'unknown';
                    $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
                }
                
                // Retorna o tipo mais usado
                if (!empty($typeCount)) {
                    arsort($typeCount);
                    $preferredType = array_key_first($typeCount);
                    
                    Logger::info("Método de pagamento preferido detectado (histórico)", [
                        'customer_id' => $customerId,
                        'type' => $preferredType,
                        'usage_count' => $typeCount[$preferredType]
                    ]);
                    return $preferredType;
                }
            }
            
            // 3. Fallback baseado em país/região
            if ($country && $currency) {
                $availableMethods = $this->getAvailablePaymentMethods($country, $currency);
                
                // Prioridade: card > pix > boleto (para Brasil)
                if (in_array('pix', $availableMethods)) {
                    Logger::info("Método de pagamento preferido detectado (fallback: PIX)", [
                        'customer_id' => $customerId,
                        'country' => $country
                    ]);
                    return 'pix';
                }
                
                if (in_array('boleto', $availableMethods)) {
                    Logger::info("Método de pagamento preferido detectado (fallback: Boleto)", [
                        'customer_id' => $customerId,
                        'country' => $country
                    ]);
                    return 'boleto';
                }
            }
            
            // 4. Padrão final: card
            Logger::info("Método de pagamento preferido detectado (padrão: card)", [
                'customer_id' => $customerId
            ]);
            return 'card';
            
        } catch (\Exception $e) {
            Logger::error("Erro ao detectar método de pagamento preferido", [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            // Retorna card como fallback seguro
            return 'card';
        }
    }

    /**
     * Tenta pagamento com rotação de métodos em caso de falha
     * Tenta primeiro com método preferido, depois com alternativos
     * 
     * @param array $data Dados do pagamento:
     *   - amount (obrigatório): Valor em centavos
     *   - currency (obrigatório): Moeda
     *   - customer_id (obrigatório): ID do customer no Stripe
     *   - description (opcional): Descrição
     *   - metadata (opcional): Metadados
     *   - preferred_method (opcional): Método preferido (se não fornecido, detecta automaticamente)
     *   - country (opcional): Código do país para detecção
     * @return array Resultado com payment_intent e método usado
     */
    public function processPaymentWithRotation(array $data): array
    {
        $customerId = $data['customer_id'] ?? null;
        if (!$customerId) {
            throw new \InvalidArgumentException("customer_id é obrigatório");
        }
        
        $amount = $data['amount'] ?? null;
        $currency = $data['currency'] ?? 'brl';
        
        if (!$amount) {
            throw new \InvalidArgumentException("amount é obrigatório");
        }
        
        // Detecta método preferido se não fornecido
        $preferredMethod = $data['preferred_method'] ?? null;
        if (!$preferredMethod) {
            $country = $data['country'] ?? 'br';
            $preferredMethod = $this->detectPreferredPaymentMethod($customerId, $country, $currency);
        }
        
        // Obtém métodos disponíveis
        $country = $data['country'] ?? 'br';
        $availableMethods = $this->getAvailablePaymentMethods($country, $currency);
        
        // Ordena métodos: preferido primeiro, depois outros
        $methodsToTry = [$preferredMethod];
        foreach ($availableMethods as $method) {
            if ($method !== $preferredMethod && !in_array($method, $methodsToTry)) {
                $methodsToTry[] = $method;
            }
        }
        
        Logger::info("Iniciando pagamento com rotação de métodos", [
            'customer_id' => $customerId,
            'amount' => $amount,
            'currency' => $currency,
            'methods_to_try' => $methodsToTry
        ]);
        
        $lastError = null;
        $attempts = [];
        
        // Tenta cada método até um funcionar
        foreach ($methodsToTry as $method) {
            try {
                Logger::info("Tentando pagamento com método", [
                    'method' => $method,
                    'attempt' => count($attempts) + 1
                ]);
                
                $paymentIntentData = [
                    'amount' => $amount,
                    'currency' => $currency,
                    'customer_id' => $customerId,
                    'payment_method_types' => [$method],
                    'description' => $data['description'] ?? null,
                    'metadata' => array_merge(
                        $data['metadata'] ?? [],
                        [
                            'payment_method_rotation' => 'true',
                            'attempt_number' => count($attempts) + 1,
                            'preferred_method' => $preferredMethod
                        ]
                    )
                ];
                
                $paymentIntent = $this->stripeService->createPaymentIntent($paymentIntentData);
                
                // Se chegou aqui, o método funcionou
                Logger::info("Pagamento criado com sucesso usando método", [
                    'payment_intent_id' => $paymentIntent->id,
                    'method' => $method,
                    'attempts' => count($attempts) + 1
                ]);
                
                return [
                    'success' => true,
                    'payment_intent' => $paymentIntent,
                    'method_used' => $method,
                    'attempts' => count($attempts) + 1,
                    'all_attempts' => $attempts
                ];
                
            } catch (\Stripe\Exception\CardException $e) {
                // Erro de cartão - não tenta outros métodos de cartão
                $errorData = [
                    'method' => $method,
                    'error' => $e->getMessage(),
                    'error_code' => $e->getStripeCode(),
                    'decline_code' => $e->getDeclineCode()
                ];
                $attempts[] = $errorData;
                $lastError = $e;
                
                Logger::warning("Falha no pagamento com método (erro de cartão)", $errorData);
                
                // Se é erro de cartão e já tentou card, não tenta outros métodos de cartão
                if ($method === 'card') {
                    // Remove outros métodos de cartão da lista
                    $methodsToTry = array_filter($methodsToTry, function($m) {
                        return $m !== 'card';
                    });
                }
                
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Método não suportado ou inválido - tenta próximo
                $errorData = [
                    'method' => $method,
                    'error' => $e->getMessage(),
                    'error_code' => $e->getStripeCode()
                ];
                $attempts[] = $errorData;
                $lastError = $e;
                
                Logger::warning("Método de pagamento não suportado ou inválido", $errorData);
                
            } catch (\Exception $e) {
                // Outro erro - tenta próximo método
                $errorData = [
                    'method' => $method,
                    'error' => $e->getMessage()
                ];
                $attempts[] = $errorData;
                $lastError = $e;
                
                Logger::warning("Erro ao processar pagamento com método", $errorData);
            }
        }
        
        // Se chegou aqui, todos os métodos falharam
        Logger::error("Todos os métodos de pagamento falharam", [
            'customer_id' => $customerId,
            'amount' => $amount,
            'attempts' => $attempts
        ]);
        
        throw new \RuntimeException(
            "Não foi possível processar o pagamento com nenhum método disponível. " .
            "Tentativas: " . count($attempts) . ". " .
            "Último erro: " . ($lastError ? $lastError->getMessage() : 'Desconhecido'),
            0,
            $lastError
        );
    }

    /**
     * Retenta pagamento de invoice com rotação de métodos
     * Usado quando invoice.payment_failed é recebido
     * 
     * @param string $invoiceId ID da invoice no Stripe
     * @param string|null $preferredMethod Método preferido (opcional)
     * @return array Resultado do retry
     */
    public function retryInvoicePaymentWithRotation(string $invoiceId, ?string $preferredMethod = null): array
    {
        try {
            $invoice = $this->stripeService->getInvoice($invoiceId);
            
            if (!$invoice) {
                throw new \RuntimeException("Invoice não encontrada: {$invoiceId}");
            }
            
            $customerId = $invoice->customer;
            if (!$customerId) {
                throw new \RuntimeException("Invoice não tem customer associado");
            }
            
            // Obtém customer do banco para pegar país/região se necessário
            $customer = $this->customerModel->findByStripeId($customerId);
            $country = $customer['country'] ?? 'br';
            $currency = strtolower($invoice->currency ?? 'brl');
            
            // Detecta método preferido se não fornecido
            if (!$preferredMethod) {
                $preferredMethod = $this->detectPreferredPaymentMethod($customerId, $country, $currency);
            }
            
            Logger::info("Retentando pagamento de invoice com rotação", [
                'invoice_id' => $invoiceId,
                'customer_id' => $customerId,
                'amount' => $invoice->amount_due,
                'preferred_method' => $preferredMethod
            ]);
            
            // Tenta pagar a invoice usando rotação
            $result = $this->processPaymentWithRotation([
                'amount' => $invoice->amount_due,
                'currency' => $currency,
                'customer_id' => $customerId,
                'description' => "Retry payment for invoice {$invoiceId}",
                'metadata' => [
                    'invoice_id' => $invoiceId,
                    'retry' => 'true',
                    'original_attempt_count' => $invoice->attempt_count ?? 0
                ],
                'preferred_method' => $preferredMethod,
                'country' => $country
            ]);
            
            // Se payment intent foi criado, tenta confirmar
            if ($result['success'] && isset($result['payment_intent'])) {
                $paymentIntent = $result['payment_intent'];
                
                // Se o payment intent precisa de confirmação, retorna para o cliente confirmar
                if ($paymentIntent->status === 'requires_confirmation' || $paymentIntent->status === 'requires_payment_method') {
                    return [
                        'success' => true,
                        'requires_action' => true,
                        'payment_intent' => $paymentIntent,
                        'method_used' => $result['method_used'],
                        'client_secret' => $paymentIntent->client_secret
                    ];
                }
                
                // Se já está succeeded, ótimo
                if ($paymentIntent->status === 'succeeded') {
                    return [
                        'success' => true,
                        'payment_intent' => $paymentIntent,
                        'method_used' => $result['method_used']
                    ];
                }
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error("Erro ao retentar pagamento de invoice com rotação", [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Trata payment_intent.succeeded
     * Pagamento confirmado com sucesso
     */
    private function handlePaymentIntentSucceeded(\Stripe\Event $event): void
    {
        $paymentIntent = $event->data->object;
        
        Logger::info("Payment Intent bem-sucedido", [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'customer_id' => $paymentIntent->customer ?? null,
            'metadata' => $paymentIntent->metadata->toArray() ?? []
        ]);

        // Aqui você pode adicionar lógica adicional, como:
        // - Atualizar status de pedido
        // - Enviar notificação ao cliente
        // - Registrar em histórico de transações
    }

    /**
     * Trata payment_intent.payment_failed
     * Falha no pagamento
     */
    private function handlePaymentIntentFailed(\Stripe\Event $event): void
    {
        $paymentIntent = $event->data->object;
        
        Logger::warning("Payment Intent falhou", [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'customer_id' => $paymentIntent->customer ?? null,
            'last_payment_error' => $paymentIntent->last_payment_error ? [
                'type' => $paymentIntent->last_payment_error->type ?? null,
                'code' => $paymentIntent->last_payment_error->code ?? null,
                'message' => $paymentIntent->last_payment_error->message ?? null,
                'decline_code' => $paymentIntent->last_payment_error->decline_code ?? null
            ] : null,
            'metadata' => $paymentIntent->metadata->toArray() ?? []
        ]);

        // Aqui você pode adicionar lógica adicional, como:
        // - Notificar o cliente sobre a falha
        // - Tentar método de pagamento alternativo
        // - Registrar tentativa de pagamento falhada
    }

    /**
     * Trata invoice.payment_failed
     * Falha no pagamento de fatura
     */
    private function handleInvoicePaymentFailed(\Stripe\Event $event): void
    {
        $invoice = $event->data->object;
        
        Logger::warning("Falha no pagamento de fatura", [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer,
            'subscription_id' => $invoice->subscription ?? null,
            'amount_due' => $invoice->amount_due,
            'currency' => $invoice->currency,
            'attempt_count' => $invoice->attempt_count ?? 0,
            'next_payment_attempt' => $invoice->next_payment_attempt 
                ? date('Y-m-d H:i:s', $invoice->next_payment_attempt) 
                : null
        ]);

        // Busca subscription relacionada se existir
        if ($invoice->subscription) {
            $subscription = $this->subscriptionModel->findByStripeId($invoice->subscription);
            
            if ($subscription) {
                // Registra no histórico da assinatura
                $historyModel = new \App\Models\SubscriptionHistory();
                $historyModel->recordChange(
                    $subscription['id'],
                    $subscription['tenant_id'],
                    \App\Models\SubscriptionHistory::CHANGE_TYPE_STATUS_CHANGED,
                    ['status' => $subscription['status']],
                    ['status' => 'past_due'],
                    \App\Models\SubscriptionHistory::CHANGED_BY_WEBHOOK,
                    "Falha no pagamento da fatura {$invoice->id}",
                    null
                );
            }
        }

        // Envia email de notificação de pagamento falhado
        try {
            if ($invoice->customer) {
                $customer = $this->customerModel->findByStripeId($invoice->customer);
                if ($customer) {
                    $this->emailService->enviarNotificacaoPagamentoFalhado(
                        $invoice->toArray(),
                        $customer
                    );
                }
            }
        } catch (\Exception $e) {
            // Log erro, mas não falha o processamento do webhook
            Logger::error('Erro ao enviar email de pagamento falhado', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer
            ]);
        }
    }

    /**
     * Trata invoice.upcoming
     * Fatura próxima (para notificações)
     */
    private function handleInvoiceUpcoming(\Stripe\Event $event): void
    {
        $invoice = $event->data->object;
        
        Logger::info("Fatura próxima", [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer,
            'subscription_id' => $invoice->subscription ?? null,
            'amount_due' => $invoice->amount_due,
            'currency' => $invoice->currency,
            'period_end' => $invoice->period_end 
                ? date('Y-m-d H:i:s', $invoice->period_end) 
                : null,
            'due_date' => $invoice->due_date 
                ? date('Y-m-d H:i:s', $invoice->due_date) 
                : null
        ]);

        // ✅ NOVO: Envia email de fatura próxima
        try {
            if ($invoice->customer) {
                $customer = $this->customerModel->findByStripeId($invoice->customer);
                if ($customer) {
                    $this->emailService->enviarNotificacaoFaturaProxima(
                        $invoice->toArray(),
                        $customer
                    );
                }
            }
        } catch (\Exception $e) {
            // Log erro, mas não falha o processamento do webhook
            Logger::error('Erro ao enviar email de fatura próxima', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer
            ]);
        }
    }

    /**
     * Trata customer.subscription.trial_will_end
     * Trial terminando em breve
     */
    private function handleSubscriptionTrialWillEnd(\Stripe\Event $event): void
    {
        $stripeSubscription = $event->data->object;
        $subscription = $this->subscriptionModel->findByStripeId($stripeSubscription->id);

        if ($subscription) {
            Logger::info("Trial da assinatura terminando em breve", [
                'subscription_id' => $subscription['id'],
                'stripe_subscription_id' => $stripeSubscription->id,
                'trial_end' => $stripeSubscription->trial_end 
                    ? date('Y-m-d H:i:s', $stripeSubscription->trial_end) 
                    : null,
                'status' => $stripeSubscription->status
            ]);

            // Registra no histórico
            $historyModel = new \App\Models\SubscriptionHistory();
            $historyModel->recordChange(
                $subscription['id'],
                $subscription['tenant_id'],
                \App\Models\SubscriptionHistory::CHANGE_TYPE_UPDATED,
                [],
                [
                    'status' => $stripeSubscription->status,
                    'trial_end' => $stripeSubscription->trial_end 
                        ? date('Y-m-d H:i:s', $stripeSubscription->trial_end) 
                        : null
                ],
                \App\Models\SubscriptionHistory::CHANGED_BY_WEBHOOK,
                "Trial terminando em " . ($stripeSubscription->trial_end 
                    ? date('Y-m-d H:i:s', $stripeSubscription->trial_end) 
                    : 'breve'),
                null
            );
        }

        // Aqui você pode adicionar lógica adicional, como:
        // - Enviar notificação ao cliente sobre fim do trial
        // - Verificar se há método de pagamento configurado
        // - Oferecer desconto para conversão
    }

    /**
     * Trata charge.dispute.created
     * Disputa/chargeback criada
     */
    private function handleChargeDisputeCreated(\Stripe\Event $event): void
    {
        $dispute = $event->data->object;
        $charge = $dispute->charge;
        
        Logger::warning("Disputa/chargeback criada", [
            'dispute_id' => $dispute->id,
            'charge_id' => $charge,
            'amount' => $dispute->amount,
            'currency' => $dispute->currency,
            'reason' => $dispute->reason,
            'status' => $dispute->status,
            'evidence_due_by' => $dispute->evidence_details->due_by 
                ? date('Y-m-d H:i:s', $dispute->evidence_details->due_by) 
                : null
        ]);

        // Tenta encontrar customer relacionado através do charge
        try {
            $chargeObj = $this->stripeService->getCharge($charge);
            if ($chargeObj->customer) {
                $customer = $this->customerModel->findByStripeId($chargeObj->customer);
                
                if ($customer) {
                    Logger::info("Disputa associada a customer", [
                        'dispute_id' => $dispute->id,
                        'customer_id' => $customer['id'],
                        'tenant_id' => $customer['tenant_id']
                    ]);
                }
            }
        } catch (\Exception $e) {
            Logger::warning("Não foi possível obter charge para disputa", [
                'dispute_id' => $dispute->id,
                'charge_id' => $charge,
                'error' => $e->getMessage()
            ]);
        }

        // Aqui você pode adicionar lógica adicional, como:
        // - Notificar equipe sobre a disputa
        // - Criar ticket de suporte
        // - Preparar evidências automaticamente
    }

    /**
     * Trata charge.refunded
     * Reembolso processado
     */
    private function handleChargeRefunded(\Stripe\Event $event): void
    {
        $charge = $event->data->object;
        
        Logger::info("Reembolso processado", [
            'charge_id' => $charge->id,
            'amount' => $charge->amount,
            'amount_refunded' => $charge->amount_refunded,
            'currency' => $charge->currency,
            'customer_id' => $charge->customer ?? null,
            'refunded' => $charge->refunded,
            'metadata' => $charge->metadata->toArray() ?? []
        ]);

        // Tenta encontrar customer relacionado
        if ($charge->customer) {
            $customer = $this->customerModel->findByStripeId($charge->customer);
            
            if ($customer) {
                Logger::info("Reembolso associado a customer", [
                    'charge_id' => $charge->id,
                    'customer_id' => $customer['id'],
                    'tenant_id' => $customer['tenant_id'],
                    'amount_refunded' => $charge->amount_refunded
                ]);
            }
        }

        // Aqui você pode adicionar lógica adicional, como:
        // - Atualizar status de pedido para reembolsado
        // - Notificar o cliente sobre o reembolso
        // - Registrar em histórico financeiro
    }

    /**
     * Trata account.updated (Stripe Connect)
     */
    private function handleAccountUpdated(\Stripe\Event $event): void
    {
        $account = $event->data->object;
        $stripeAccountId = $account->id;

        Logger::info("Conta Stripe Connect atualizada", [
            'stripe_account_id' => $stripeAccountId,
            'charges_enabled' => $account->charges_enabled ?? false,
            'payouts_enabled' => $account->payouts_enabled ?? false
        ]);

        // Atualiza dados da conta no banco
        $connectService = new \App\Services\StripeConnectService(
            $this->stripeService,
            new \App\Models\TenantStripeAccount()
        );

        $connectService->updateAccountFromStripe($stripeAccountId, [
            'charges_enabled' => $account->charges_enabled ?? false,
            'payouts_enabled' => $account->payouts_enabled ?? false,
            'details_submitted' => $account->details_submitted ?? false,
            'email' => $account->email ?? null,
            'country' => $account->country ?? null
        ]);
    }

    /**
     * Remove métodos de pagamento expirados de um customer
     * Verifica cartões expirados e remove automaticamente
     * 
     * @param string $customerId ID do customer no Stripe
     * @return array Resultado com métodos removidos
     */
    public function removeExpiredPaymentMethods(string $customerId): array
    {
        try {
            $removed = [];
            $currentYear = (int)date('Y');
            $currentMonth = (int)date('m');
            
            // Lista todos os métodos de pagamento do customer
            $paymentMethods = $this->stripeService->listPaymentMethods($customerId, ['limit' => 100]);
            
            foreach ($paymentMethods->data as $pm) {
                // Só verifica cartões
                if ($pm->type === 'card' && isset($pm->card)) {
                    $expYear = (int)($pm->card->exp_year ?? 0);
                    $expMonth = (int)($pm->card->exp_month ?? 0);
                    
                    // Verifica se está expirado
                    $isExpired = false;
                    if ($expYear < $currentYear) {
                        $isExpired = true;
                    } elseif ($expYear === $currentYear && $expMonth < $currentMonth) {
                        $isExpired = true;
                    }
                    
                    if ($isExpired) {
                        try {
                            // Remove método expirado
                            $this->stripeService->detachPaymentMethod($pm->id);
                            
                            $removed[] = [
                                'id' => $pm->id,
                                'type' => $pm->type,
                                'last4' => $pm->card->last4 ?? 'N/A',
                                'exp_month' => $expMonth,
                                'exp_year' => $expYear
                            ];
                            
                            Logger::info("Método de pagamento expirado removido", [
                                'payment_method_id' => $pm->id,
                                'customer_id' => $customerId,
                                'exp_month' => $expMonth,
                                'exp_year' => $expYear
                            ]);
                        } catch (\Exception $e) {
                            Logger::error("Erro ao remover método de pagamento expirado", [
                                'payment_method_id' => $pm->id,
                                'customer_id' => $customerId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            return [
                'removed_count' => count($removed),
                'removed_methods' => $removed
            ];
            
        } catch (\Exception $e) {
            Logger::error("Erro ao verificar métodos de pagamento expirados", [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Agenda mudança de plano para uma assinatura
     * 
     * @param string $subscriptionId ID da assinatura no Stripe
     * @param string $newPriceId ID do novo preço
     * @param int|null $startDate Timestamp de quando aplicar a mudança (null = fim do período atual)
     * @param array $metadata Metadados opcionais
     * @return \Stripe\SubscriptionSchedule
     */
    public function schedulePlanChange(string $subscriptionId, string $newPriceId, ?int $startDate = null, array $metadata = []): \Stripe\SubscriptionSchedule
    {
        try {
            // Obtém assinatura atual
            $subscription = $this->stripeService->getSubscription($subscriptionId);
            
            // Se já existe schedule, cancela
            $existingSchedule = $this->stripeService->getSubscriptionSchedule($subscriptionId);
            if ($existingSchedule) {
                $this->stripeService->cancelSubscriptionSchedule($existingSchedule->id);
            }

            // Se não especificou data, agenda para o fim do período atual
            if ($startDate === null) {
                $startDate = $subscription->current_period_end;
            }

            // Obtém item atual da assinatura
            $currentItem = null;
            if (!empty($subscription->items->data)) {
                $currentItem = $subscription->items->data[0];
            }

            // Cria schedule com duas fases:
            // Fase 1: Período atual (até startDate)
            // Fase 2: Novo plano (a partir de startDate)
            $phases = [];

            // Fase 1: Período atual
            if ($currentItem) {
                $phases[] = [
                    'items' => [[
                        'price' => $currentItem->price->id,
                        'quantity' => $currentItem->quantity ?? 1
                    ]],
                    'end_date' => $startDate
                ];
            }

            // Fase 2: Novo plano
            $phases[] = [
                'items' => [[
                    'price' => $newPriceId,
                    'quantity' => $currentItem->quantity ?? 1
                ]],
                'start_date' => $startDate
            ];

            $schedule = $this->stripeService->createSubscriptionSchedule($subscriptionId, [
                'phases' => $phases,
                'metadata' => array_merge($metadata, [
                    'scheduled_plan_change' => 'true',
                    'old_price_id' => $currentItem->price->id ?? null,
                    'new_price_id' => $newPriceId
                ])
            ]);

            Logger::info("Mudança de plano agendada", [
                'subscription_id' => $subscriptionId,
                'schedule_id' => $schedule->id,
                'new_price_id' => $newPriceId,
                'start_date' => date('Y-m-d H:i:s', $startDate)
            ]);

            return $schedule;
        } catch (\Exception $e) {
            Logger::error("Erro ao agendar mudança de plano", [
                'subscription_id' => $subscriptionId,
                'new_price_id' => $newPriceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Agenda cancelamento de uma assinatura
     * 
     * @param string $subscriptionId ID da assinatura no Stripe
     * @param int|null $cancelAt Timestamp de quando cancelar (null = fim do período atual)
     * @param array $metadata Metadados opcionais
     * @return \Stripe\Subscription
     */
    public function scheduleCancellation(string $subscriptionId, ?int $cancelAt = null): \Stripe\Subscription
    {
        try {
            // Se não especificou data, cancela no fim do período atual
            if ($cancelAt === null) {
                $subscription = $this->stripeService->getSubscription($subscriptionId);
                $cancelAt = $subscription->current_period_end;
            }

            // Marca para cancelar no fim do período
            $subscription = $this->stripeService->updateSubscription($subscriptionId, [
                'cancel_at_period_end' => true,
                'metadata' => [
                    'scheduled_cancellation' => 'true',
                    'cancel_at' => (string)$cancelAt
                ]
            ]);

            Logger::info("Cancelamento de assinatura agendado", [
                'subscription_id' => $subscriptionId,
                'cancel_at' => date('Y-m-d H:i:s', $cancelAt)
            ]);

            return $subscription;
        } catch (\Exception $e) {
            Logger::error("Erro ao agendar cancelamento", [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Pausa uma assinatura
     * 
     * @param string $subscriptionId ID da assinatura no Stripe
     * @param array $options Opções de pausa
     * @return \Stripe\Subscription
     */
    public function pauseSubscription(string $subscriptionId, array $options = []): \Stripe\Subscription
    {
        try {
            $subscription = $this->stripeService->pauseSubscription($subscriptionId, $options);

            Logger::info("Assinatura pausada", [
                'subscription_id' => $subscriptionId
            ]);

            return $subscription;
        } catch (\Exception $e) {
            Logger::error("Erro ao pausar assinatura", [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Retoma uma assinatura pausada
     * 
     * @param string $subscriptionId ID da assinatura no Stripe
     * @return \Stripe\Subscription
     */
    public function resumeSubscription(string $subscriptionId): \Stripe\Subscription
    {
        try {
            $subscription = $this->stripeService->resumeSubscription($subscriptionId);

            Logger::info("Assinatura retomada", [
                'subscription_id' => $subscriptionId
            ]);

            return $subscription;
        } catch (\Exception $e) {
            Logger::error("Erro ao retomar assinatura", [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

