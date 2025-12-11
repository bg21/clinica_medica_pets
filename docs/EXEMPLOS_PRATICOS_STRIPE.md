# Exemplos Práticos: Integração Stripe com FlightPHP

Este documento contém exemplos práticos e completos de implementação Stripe no FlightPHP.

---


## 📋 Índice

1. [Checkout de Assinatura](#checkout-de-assinatura)
2. [Pagamento Único](#pagamento-único)
3. [Salvar Cartão](#salvar-cartão)
4. [Gerenciar Assinatura](#gerenciar-assinatura)
5. [Reembolso](#reembolso)
6. [Cupons e Descontos](#cupons-e-descontos)
7. [Trial Period](#trial-period)
8. [Múltiplos Itens](#múltiplos-itens)
9. [Pagamento com 3D Secure](#pagamento-com-3d-secure)
10. [Webhook Completo](#webhook-completo)

---

## Checkout de Assinatura

### Cenário
Clínica assina um plano mensal da plataforma SaaS.

### Controller Completo

```php
<?php

namespace App\Controllers;

use App\Services\StripeService;
use App\Utils\ResponseHelper;
use App\Utils\Validator;
use Flight;

class SaasController
{
    private StripeService $stripeService;
    private PaymentService $paymentService;

    public function __construct(
        StripeService $stripeService,
        PaymentService $paymentService
    ) {
        $this->stripeService = $stripeService;
        $this->paymentService = $paymentService;
    }

    /**
     * Cria checkout para assinatura SaaS
     * POST /v1/saas/checkout
     */
    public function createCheckout(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado');
                return;
            }

            $data = \App\Utils\RequestCache::getJsonInput();
            
            // Validação
            $errors = [];
            if (empty($data['price_id'])) {
                $errors['price_id'] = 'Obrigatório';
            }
            if (empty($data['success_url'])) {
                $errors['success_url'] = 'Obrigatório';
            }
            if (empty($data['cancel_url'])) {
                $errors['cancel_url'] = 'Obrigatório';
            }
            
            if (!empty($errors)) {
                ResponseHelper::sendValidationError('Dados inválidos', $errors);
                return;
            }

            // ✅ SEMPRE usa conta padrão (plataforma recebe)
            $stripeService = new StripeService();

            // Busca tenant
            $tenantModel = new \App\Models\Tenant();
            $tenant = $tenantModel->findById($tenantId);
            
            if (!$tenant) {
                ResponseHelper::sendNotFoundError('Tenant não encontrado');
                return;
            }

            // Busca ou cria customer no Stripe
            $customerModel = new \App\Models\Customer();
            $customer = $customerModel->findByTenant($tenantId);
            
            $stripeCustomerId = null;
            
            if ($customer && !empty($customer['stripe_customer_id'])) {
                $stripeCustomerId = $customer['stripe_customer_id'];
            } else {
                // Busca usuário admin do tenant
                $userModel = new \App\Models\User();
                $adminUser = $userModel->findBy([
                    'tenant_id' => $tenantId,
                    'role' => 'admin'
                ], ['created_at' => 'ASC'], 1);
                
                $adminEmail = $adminUser ? ($adminUser[0]['email'] ?? null) : null;
                
                // Cria customer no Stripe
                $stripeCustomer = $stripeService->createCustomer([
                    'email' => $adminEmail,
                    'name' => $tenant['name'],
                    'metadata' => [
                        'tenant_id' => $tenantId,
                        'type' => 'saas_subscription'
                    ]
                ]);
                
                $stripeCustomerId = $stripeCustomer->id;
                
                // Salva no banco
                if ($customer) {
                    $customerModel->update($customer['id'], [
                        'stripe_customer_id' => $stripeCustomerId
                    ]);
                } else {
                    $customerModel->create([
                        'tenant_id' => $tenantId,
                        'stripe_customer_id' => $stripeCustomerId,
                        'email' => $adminEmail,
                        'name' => $tenant['name']
                    ]);
                }
            }

            // Cria sessão de checkout
            $session = $stripeService->createCheckoutSession([
                'customer' => $stripeCustomerId,
                'line_items' => [
                    [
                        'price' => $data['price_id'],
                        'quantity' => 1
                    ]
                ],
                'mode' => 'subscription',
                'success_url' => $data['success_url'],
                'cancel_url' => $data['cancel_url'],
                'metadata' => [
                    'tenant_id' => $tenantId,
                    'type' => 'saas_subscription'
                ],
                'subscription_data' => [
                    'metadata' => [
                        'tenant_id' => $tenantId
                    ]
                ]
            ]);

            ResponseHelper::sendCreated([
                'session_id' => $session->id,
                'url' => $session->url
            ], 'Checkout criado com sucesso');
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ResponseHelper::sendStripeError(
                $e,
                'Erro ao criar checkout',
                ['action' => 'create_saas_checkout']
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar checkout',
                'SAAS_CHECKOUT_ERROR'
            );
        }
    }
}
```

### Frontend (JavaScript)

```javascript
async function subscribeToPlan(priceId) {
    try {
        const response = await fetch('/v1/saas/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`
            },
            body: JSON.stringify({
                price_id: priceId,
                success_url: `${window.location.origin}/my-subscription?success=true`,
                cancel_url: `${window.location.origin}/my-subscription?canceled=true`
            })
        });

        const data = await response.json();
        
        if (data.success) {
            // Redireciona para checkout do Stripe
            window.location.href = data.data.url;
        } else {
            alert('Erro ao criar checkout: ' + data.error.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao processar requisição');
    }
}
```

---

## Pagamento Único

### Cenário
Cliente da clínica paga por uma consulta veterinária.

### Controller Completo

```php
<?php

namespace App\Controllers;

use App\Services\StripeService;
use App\Utils\ResponseHelper;
use Flight;

class PaymentController
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Cria payment intent para pagamento único
     * POST /v1/payment-intents
     */
    public function createPaymentIntent(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado');
                return;
            }

            // ✅ SEMPRE usa conta do tenant (clínica recebe)
            $stripeService = StripeService::forTenant($tenantId);
            
            // Verifica se tenant tem Stripe configurado
            $stripeAccountModel = new \App\Models\TenantStripeAccount();
            $stripeAccount = $stripeAccountModel->findByTenant($tenantId);
            
            if (!$stripeAccount || empty($stripeAccount['stripe_secret_key'])) {
                ResponseHelper::sendError(
                    402,
                    'Stripe Connect não configurado',
                    'Configure sua conta Stripe antes de receber pagamentos',
                    'STRIPE_CONNECT_REQUIRED'
                );
                return;
            }

            $data = \App\Utils\RequestCache::getJsonInput();
            
            // Validação
            $errors = \App\Utils\Validator::validatePaymentIntentCreate($data);
            if (!empty($errors)) {
                ResponseHelper::sendValidationError('Dados inválidos', $errors);
                return;
            }

            // Busca customer se fornecido (ID do nosso banco)
            if (!empty($data['customer_id']) && is_numeric($data['customer_id'])) {
                $customerModel = new \App\Models\Customer();
                $customer = $customerModel->findByTenantAndId(
                    $tenantId,
                    (int)$data['customer_id']
                );
                
                if (!$customer) {
                    ResponseHelper::sendNotFoundError('Cliente não encontrado');
                    return;
                }
                
                // Se não tem stripe_customer_id, cria no Stripe
                if (empty($customer['stripe_customer_id'])) {
                    $stripeCustomer = $stripeService->createCustomer([
                        'email' => $customer['email'],
                        'name' => $customer['name'],
                        'metadata' => [
                            'tenant_id' => $tenantId,
                            'customer_id' => $customer['id']
                        ]
                    ]);
                    
                    $customerModel->update($customer['id'], [
                        'stripe_customer_id' => $stripeCustomer->id
                    ]);
                    
                    $data['customer'] = $stripeCustomer->id;
                } else {
                    $data['customer'] = $customer['stripe_customer_id'];
                }
                
                unset($data['customer_id']); // Remove ID do nosso banco
            }

            // Adiciona metadata
            $data['metadata'] = array_merge($data['metadata'] ?? [], [
                'tenant_id' => $tenantId,
                'appointment_id' => $data['appointment_id'] ?? null
            ]);

            // Cria payment intent
            $paymentIntent = $stripeService->createPaymentIntent($data);

            ResponseHelper::sendCreated([
                'id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $paymentIntent->amount,
                'currency' => strtoupper($paymentIntent->currency),
                'status' => $paymentIntent->status,
                'description' => $paymentIntent->description
            ], 'Payment intent criado com sucesso');
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ResponseHelper::sendStripeError(
                $e,
                'Erro ao criar payment intent',
                ['action' => 'create_payment_intent']
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar payment intent',
                'PAYMENT_INTENT_CREATE_ERROR'
            );
        }
    }
}
```

### Frontend (JavaScript com Stripe.js)

```javascript
// Inicializa Stripe
const stripe = Stripe('pk_live_xxx'); // Chave pública do tenant

async function payForAppointment(appointmentId, amount) {
    try {
        // 1. Cria payment intent no backend
        const response = await fetch('/v1/payment-intents', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`
            },
            body: JSON.stringify({
                amount: amount, // em centavos
                currency: 'brl',
                customer_id: customerId, // ID do nosso banco
                description: `Consulta veterinária #${appointmentId}`,
                appointment_id: appointmentId
            })
        });

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error.message);
        }

        // 2. Confirma pagamento no frontend
        const { error, paymentIntent } = await stripe.confirmCardPayment(
            data.data.client_secret,
            {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: customerName,
                        email: customerEmail
                    }
                }
            }
        );

        if (error) {
            console.error('Erro no pagamento:', error);
            alert('Erro: ' + error.message);
        } else if (paymentIntent.status === 'succeeded') {
            alert('Pagamento realizado com sucesso!');
            // Atualiza status do agendamento
            updateAppointmentStatus(appointmentId, 'paid');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao processar pagamento');
    }
}
```

---

## Salvar Cartão

### Cenário
Cliente salva cartão para pagamentos futuros.

### Controller Completo

```php
<?php

namespace App\Controllers;

use App\Services\StripeService;
use App\Utils\ResponseHelper;
use Flight;

class SetupIntentController
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Cria setup intent para salvar cartão
     * POST /v1/setup-intents
     */
    public function create(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado');
                return;
            }

            $stripeService = StripeService::forTenant($tenantId);
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            // Validação
            if (empty($data['customer_id'])) {
                ResponseHelper::sendValidationError(
                    'customer_id é obrigatório',
                    ['customer_id' => 'Obrigatório']
                );
                return;
            }

            // Busca customer
            $customerModel = new \App\Models\Customer();
            $customer = $customerModel->findByTenantAndId(
                $tenantId,
                (int)$data['customer_id']
            );
            
            if (!$customer) {
                ResponseHelper::sendNotFoundError('Cliente não encontrado');
                return;
            }

            // Se não tem stripe_customer_id, cria no Stripe
            if (empty($customer['stripe_customer_id'])) {
                $stripeCustomer = $stripeService->createCustomer([
                    'email' => $customer['email'],
                    'name' => $customer['name'],
                    'metadata' => [
                        'tenant_id' => $tenantId,
                        'customer_id' => $customer['id']
                    ]
                ]);
                
                $customerModel->update($customer['id'], [
                    'stripe_customer_id' => $stripeCustomer->id
                ]);
                
                $stripeCustomerId = $stripeCustomer->id;
            } else {
                $stripeCustomerId = $customer['stripe_customer_id'];
            }

            // Cria setup intent
            $setupIntent = $stripeService->getClient()->setupIntents->create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'usage' => 'off_session', // Para usar depois
                'metadata' => [
                    'tenant_id' => $tenantId,
                    'customer_id' => $customer['id']
                ]
            ]);

            ResponseHelper::sendCreated([
                'id' => $setupIntent->id,
                'client_secret' => $setupIntent->client_secret,
                'customer' => $setupIntent->customer
            ], 'Setup intent criado com sucesso');
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ResponseHelper::sendStripeError(
                $e,
                'Erro ao criar setup intent',
                ['action' => 'create_setup_intent']
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar setup intent',
                'SETUP_INTENT_CREATE_ERROR'
            );
        }
    }

    /**
     * Lista métodos de pagamento salvos
     * GET /v1/customers/:id/payment-methods
     */
    public function listPaymentMethods(string $customerId): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado');
                return;
            }

            $stripeService = StripeService::forTenant($tenantId);
            
            // Busca customer
            $customerModel = new \App\Models\Customer();
            $customer = $customerModel->findByTenantAndId($tenantId, (int)$customerId);
            
            if (!$customer || empty($customer['stripe_customer_id'])) {
                ResponseHelper::sendNotFoundError('Cliente não encontrado');
                return;
            }

            // Lista métodos de pagamento
            $paymentMethods = $stripeService->getClient()->paymentMethods->all([
                'customer' => $customer['stripe_customer_id'],
                'type' => 'card'
            ]);

            $methods = [];
            foreach ($paymentMethods->data as $pm) {
                $methods[] = [
                    'id' => $pm->id,
                    'type' => $pm->type,
                    'card' => [
                        'brand' => $pm->card->brand,
                        'last4' => $pm->card->last4,
                        'exp_month' => $pm->card->exp_month,
                        'exp_year' => $pm->card->exp_year
                    ],
                    'is_default' => $pm->id === $customer['default_payment_method_id']
                ];
            }

            ResponseHelper::sendSuccess([
                'payment_methods' => $methods
            ]);
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ResponseHelper::sendStripeError(
                $e,
                'Erro ao listar métodos de pagamento',
                ['action' => 'list_payment_methods']
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar métodos de pagamento',
                'LIST_PAYMENT_METHODS_ERROR'
            );
        }
    }
}
```

### Frontend (JavaScript)

```javascript
async function saveCard(customerId) {
    try {
        // 1. Cria setup intent
        const response = await fetch('/v1/setup-intents', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`
            },
            body: JSON.stringify({
                customer_id: customerId
            })
        });

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error.message);
        }

        // 2. Confirma setup intent
        const { error, setupIntent } = await stripe.confirmCardSetup(
            data.data.client_secret,
            {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: customerName,
                        email: customerEmail
                    }
                }
            }
        );

        if (error) {
            console.error('Erro:', error);
            alert('Erro ao salvar cartão: ' + error.message);
        } else {
            alert('Cartão salvo com sucesso!');
            // Atualiza lista de cartões
            loadPaymentMethods(customerId);
        }
        
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao processar requisição');
    }
}
```

---

## Gerenciar Assinatura

### Cenário
Cliente atualiza, cancela ou reativa assinatura.

### Controller Completo

```php
<?php

namespace App\Controllers;

use App\Services\StripeService;
use App\Services\PaymentService;
use App\Utils\ResponseHelper;
use Flight;

class SubscriptionController
{
    private PaymentService $paymentService;
    private StripeService $stripeService;

    public function __construct(
        PaymentService $paymentService,
        StripeService $stripeService
    ) {
        $this->paymentService = $paymentService;
        $this->stripeService = $stripeService;
    }

    /**
     * Atualiza assinatura (muda plano)
     * PUT /v1/subscriptions/:id
     */
    public function update(string $id): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado');
                return;
            }

            // ✅ Para assinaturas SaaS, usa conta padrão
            $stripeService = new StripeService();
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            // Busca assinatura no banco
            $subscriptionModel = new \App\Models\Subscription();
            $subscription = $subscriptionModel->findByStripeId($id);
            
            if (!$subscription || $subscription['tenant_id'] !== $tenantId) {
                ResponseHelper::sendNotFoundError('Assinatura não encontrada');
                return;
            }

            // Atualiza assinatura no Stripe
            $updateData = [];
            
            if (!empty($data['price_id'])) {
                // Atualiza item da assinatura
                $stripeSubscription = $stripeService->getClient()->subscriptions->retrieve($id);
                
                $stripeService->getClient()->subscriptionItems->update(
                    $stripeSubscription->items->data[0]->id,
                    ['price' => $data['price_id']]
                );
            }
            
            if (isset($data['cancel_at_period_end'])) {
                // Agenda cancelamento ou remove
                $stripeService->getClient()->subscriptions->update($id, [
                    'cancel_at_period_end' => $data['cancel_at_period_end']
                ]);
            }

            // Busca assinatura atualizada
            $updatedSubscription = $stripeService->getClient()->subscriptions->retrieve($id);
            
            // Atualiza no banco
            $this->paymentService->syncSubscription($updatedSubscription);

            ResponseHelper::sendSuccess([
                'id' => $updatedSubscription->id,
                'status' => $updatedSubscription->status,
                'current_period_end' => date('Y-m-d H:i:s', $updatedSubscription->current_period_end)
            ], 'Assinatura atualizada com sucesso');
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ResponseHelper::sendStripeError(
                $e,
                'Erro ao atualizar assinatura',
                ['action' => 'update_subscription']
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar assinatura',
                'SUBSCRIPTION_UPDATE_ERROR'
            );
        }
    }

    /**
     * Cancela assinatura
     * DELETE /v1/subscriptions/:id
     */
    public function cancel(string $id): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado');
                return;
            }

            $stripeService = new StripeService();
            
            // Busca assinatura
            $subscriptionModel = new \App\Models\Subscription();
            $subscription = $subscriptionModel->findByStripeId($id);
            
            if (!$subscription || $subscription['tenant_id'] !== $tenantId) {
                ResponseHelper::sendNotFoundError('Assinatura não encontrada');
                return;
            }

            // Cancela no Stripe
            $canceledSubscription = $stripeService->getClient()->subscriptions->cancel($id);

            // Atualiza no banco
            $this->paymentService->syncSubscription($canceledSubscription);

            ResponseHelper::sendSuccess([
                'id' => $canceledSubscription->id,
                'status' => $canceledSubscription->status,
                'canceled_at' => $canceledSubscription->canceled_at 
                    ? date('Y-m-d H:i:s', $canceledSubscription->canceled_at) 
                    : null
            ], 'Assinatura cancelada com sucesso');
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ResponseHelper::sendStripeError(
                $e,
                'Erro ao cancelar assinatura',
                ['action' => 'cancel_subscription']
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao cancelar assinatura',
                'SUBSCRIPTION_CANCEL_ERROR'
            );
        }
    }
}
```

---

## Reembolso

### Controller Completo

```php
/**
 * Reembolsa um pagamento
 * POST /v1/refunds
 */
public function createRefund(): void
{
    try {
        $tenantId = Flight::get('tenant_id');
        
        if ($tenantId === null) {
            ResponseHelper::sendUnauthorizedError('Não autenticado');
            return;
        }

        $stripeService = StripeService::forTenant($tenantId);
        
        $data = \App\Utils\RequestCache::getJsonInput();
        
        // Validação
        if (empty($data['payment_intent_id'])) {
            ResponseHelper::sendValidationError(
                'payment_intent_id é obrigatório',
                ['payment_intent_id' => 'Obrigatório']
            );
            return;
        }

        // Verifica se payment intent pertence ao tenant
        $paymentIntent = $stripeService->getClient()->paymentIntents->retrieve(
            $data['payment_intent_id']
        );
        
        if (isset($paymentIntent->metadata->tenant_id) && 
            (int)$paymentIntent->metadata->tenant_id !== $tenantId) {
            ResponseHelper::sendForbiddenError('Payment intent não pertence ao tenant');
            return;
        }

        // Prepara opções
        $options = [];
        
        if (isset($data['amount'])) {
            $options['amount'] = (int)$data['amount'];
        }
        
        if (!empty($data['reason'])) {
            $allowedReasons = ['duplicate', 'fraudulent', 'requested_by_customer'];
            if (in_array($data['reason'], $allowedReasons, true)) {
                $options['reason'] = $data['reason'];
            }
        }
        
        $options['metadata'] = array_merge($data['metadata'] ?? [], [
            'tenant_id' => $tenantId,
            'refunded_by' => Flight::get('user_id')
        ]);

        // Cria reembolso
        $refund = $stripeService->refundPayment(
            $data['payment_intent_id'],
            $options
        );

        ResponseHelper::sendCreated([
            'id' => $refund->id,
            'amount' => $refund->amount,
            'currency' => strtoupper($refund->currency),
            'status' => $refund->status,
            'reason' => $refund->reason,
            'payment_intent' => $refund->payment_intent
        ], 'Reembolso criado com sucesso');
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        ResponseHelper::sendStripeError(
            $e,
            'Erro ao criar reembolso',
            ['action' => 'create_refund']
        );
    } catch (\Exception $e) {
        ResponseHelper::sendGenericError(
            $e,
            'Erro ao criar reembolso',
            'REFUND_CREATE_ERROR'
        );
    }
}
```

---

## Cupons e Descontos

### Aplicar Cupom no Checkout

```php
$session = $stripeService->createCheckoutSession([
    'customer' => $customerId,
    'line_items' => [
        ['price' => $priceId, 'quantity' => 1]
    ],
    'mode' => 'subscription',
    'discounts' => [
        ['coupon' => $couponId] // ou ['promotion_code' => $promoCodeId]
    ],
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl
]);
```

### Aplicar Cupom em Assinatura Existente

```php
$stripeService->getClient()->subscriptions->update($subscriptionId, [
    'coupon' => $couponId
]);
```

---

## Trial Period

### Checkout com Trial

```php
$session = $stripeService->createCheckoutSession([
    'customer' => $customerId,
    'line_items' => [
        ['price' => $priceId, 'quantity' => 1]
    ],
    'mode' => 'subscription',
    'subscription_data' => [
        'trial_period_days' => 14,
        'trial_settings' => [
            'end_behavior' => [
                'missing_payment_method' => 'cancel'
            ]
        ]
    ],
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl
]);
```

### Assinatura Direta com Trial

```php
$subscription = $stripeService->createSubscription([
    'customer_id' => $customerId,
    'price_id' => $priceId,
    'trial_period_days' => 14
]);
```

---

## Múltiplos Itens

### Checkout com Múltiplos Itens

```php
$session = $stripeService->createCheckoutSession([
    'customer' => $customerId,
    'line_items' => [
        [
            'price' => 'price_plan_basico',
            'quantity' => 1
        ],
        [
            'price' => 'price_addon_extra',
            'quantity' => 2
        ]
    ],
    'mode' => 'subscription',
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl
]);
```

---

## Pagamento com 3D Secure

### Payment Intent com Confirmação Manual

```php
$paymentIntent = $stripeService->createPaymentIntent([
    'amount' => 10000,
    'currency' => 'brl',
    'customer' => $customerId,
    'payment_method' => $paymentMethodId,
    'confirmation_method' => 'manual',
    'confirm' => true,
    'return_url' => 'https://example.com/return'
]);

// Se requer autenticação
if ($paymentIntent->status === 'requires_action') {
    // Retorna client_secret para frontend fazer autenticação
    return [
        'requires_action' => true,
        'client_secret' => $paymentIntent->client_secret
    ];
}
```

### Frontend

```javascript
const { error, paymentIntent } = await stripe.confirmCardPayment(
    clientSecret,
    {
        payment_method: {
            card: cardElement
        }
    }
);

if (error) {
    // Trata erro
} else if (paymentIntent.status === 'succeeded') {
    // Pagamento confirmado
}
```

---

## Webhook Completo

### PaymentService::processWebhook()

```php
public function processWebhook(\Stripe\Event $event): void
{
    // Salva evento no banco
    $eventModel = new \App\Models\StripeEvent();
    $eventModel->create([
        'stripe_event_id' => $event->id,
        'type' => $event->type,
        'data' => json_encode($event->data->toArray()),
        'processed' => false
    ]);

    // Processa evento
    switch ($event->type) {
        case 'payment_intent.succeeded':
            $this->handlePaymentSucceeded($event->data->object);
            break;
            
        case 'payment_intent.payment_failed':
            $this->handlePaymentFailed($event->data->object);
            break;
            
        case 'customer.subscription.created':
        case 'customer.subscription.updated':
            $this->handleSubscriptionChange($event->data->object);
            break;
            
        case 'customer.subscription.deleted':
            $this->handleSubscriptionDeleted($event->data->object);
            break;
            
        case 'invoice.payment_succeeded':
            $this->handleInvoicePaid($event->data->object);
            break;
            
        case 'invoice.payment_failed':
            $this->handleInvoiceFailed($event->data->object);
            break;
    }

    // Marca como processado
    $eventModel->markAsProcessed($event->id);
}

private function handlePaymentSucceeded(\Stripe\PaymentIntent $paymentIntent): void
{
    $tenantId = $paymentIntent->metadata->tenant_id ?? null;
    $appointmentId = $paymentIntent->metadata->appointment_id ?? null;
    
    if ($appointmentId) {
        // Atualiza status do agendamento
        $appointmentModel = new \App\Models\Appointment();
        $appointmentModel->update($appointmentId, [
            'payment_status' => 'paid',
            'payment_intent_id' => $paymentIntent->id
        ]);
    }
    
    Logger::info("Pagamento confirmado", [
        'payment_intent_id' => $paymentIntent->id,
        'tenant_id' => $tenantId,
        'amount' => $paymentIntent->amount
    ]);
}

private function handleSubscriptionChange(\Stripe\Subscription $subscription): void
{
    $this->syncSubscription($subscription);
    
    Logger::info("Assinatura atualizada", [
        'subscription_id' => $subscription->id,
        'status' => $subscription->status
    ]);
}
```

---

## Conclusão

Estes exemplos cobrem os casos de uso mais comuns de integração Stripe com FlightPHP. Sempre:

1. ✅ Valide dados de entrada
2. ✅ Verifique permissões (tenant, usuário)
3. ✅ Use a conta Stripe correta (plataforma vs tenant)
4. ✅ Adicione metadata para rastreabilidade
5. ✅ Trate todos os tipos de erro
6. ✅ Implemente logging
7. ✅ Teste em modo de teste primeiro

Para mais informações, consulte o [Guia Completo](./GUIA_COMPLETO_STRIPE_FLIGHTPHP.md).

