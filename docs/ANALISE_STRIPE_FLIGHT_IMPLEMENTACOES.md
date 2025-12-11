# Análise Completa: Integrações Stripe + FlightPHP

**Data:** 2025-01-09  
**Sistema:** Clínica Médica - SaaS com Stripe  
**Framework:** FlightPHP 1.3  
**Biblioteca Stripe:** stripe/stripe-php ^10.0

---

## 📋 Índice

1. [Resumo Executivo](#resumo-executivo)
2. [Arquitetura Atual](#arquitetura-atual)
3. [Análise de Integrações Stripe](#análise-de-integrações-stripe)
4. [Melhorias e Correções Necessárias](#melhorias-e-correções-necessárias)
5. [Implementações Recomendadas](#implementações-recomendadas)
6. [Segurança](#segurança)
7. [Performance e Otimização](#performance-e-otimização)
8. [Tratamento de Erros e Logs](#tratamento-de-erros-e-logs)
9. [Webhooks](#webhooks)
10. [Boas Práticas e Padrões](#boas-práticas-e-padrões)
11. [Checklist de Implementação](#checklist-de-implementação)

---

## Resumo Executivo

### ✅ Pontos Fortes Identificados

1. **Arquitetura bem estruturada** com separação clara de responsabilidades (Controllers, Services, Models, Repositories)
2. **Container de Injeção de Dependências** implementado corretamente
3. **Sistema de Webhooks** funcional com validação de assinatura
4. **Middleware robusto** para autenticação, rate limiting, CSRF, auditoria
5. **Cache implementado** em pontos críticos
6. **Logging estruturado** com Logger service
7. **Validação de dados** consistente usando Validator
8. **Tratamento de erros** padronizado com ResponseHelper

### ⚠️ Pontos de Atenção e Melhorias Necessárias

1. **StripeService** - Falta suporte para múltiplas contas Stripe (Stripe Connect)
2. **Webhooks** - Alguns eventos não estão sendo tratados
3. **Retry Logic** - Falta implementação de retry para falhas temporárias do Stripe
4. **Idempotência** - Melhorar controle de idempotência em operações críticas
5. **Testes** - Falta cobertura de testes para integrações Stripe
6. **Documentação** - Falta documentação técnica das integrações
7. **Monitoramento** - Falta alertas para falhas críticas do Stripe
8. **Rate Limiting do Stripe** - Não há tratamento específico para rate limits do Stripe

---

## Arquitetura Atual

### Estrutura de Diretórios

```
App/
├── Controllers/        # 40+ controllers (bem organizados)
├── Services/          # 21 services (incluindo StripeService, PaymentService)
├── Models/            # 30 models (bem estruturados)
├── Repositories/      # Repositories (UserRepository implementado)
├── Middleware/        # 12 middlewares (completo)
├── Utils/             # Helpers e utilitários
├── Core/              # Container, EventDispatcher
├── DTOs/              # Data Transfer Objects (vazio - oportunidade)
└── Views/             # Views HTML/PHP
```

### Fluxo de Requisição

```
Request → Middleware (Tracing, CSRF, Auth, Rate Limit) 
       → Controller 
       → Service 
       → StripeService 
       → Stripe API
```

### Container de Dependências

✅ **Bem implementado** em `App/Core/ContainerBindings.php`
- Singletons configurados corretamente
- Controllers com injeção de dependências
- Services com dependências resolvidas

---

## Análise de Integrações Stripe

### ✅ Implementações Existentes

#### 1. **StripeService** (`App/Services/StripeService.php`)

**Funcionalidades Implementadas:**
- ✅ Criação de Customers
- ✅ Checkout Sessions
- ✅ Subscriptions (criar, atualizar, cancelar, reativar)
- ✅ Payment Intents
- ✅ Invoices
- ✅ Payment Methods (listar, anexar, definir padrão)
- ✅ Setup Intents
- ✅ Products e Prices
- ✅ Coupons e Promotion Codes
- ✅ Tax Rates
- ✅ Invoice Items
- ✅ Balance Transactions
- ✅ Charges
- ✅ Disputes
- ✅ Payouts
- ✅ Webhook validation
- ✅ Subscription Schedules (criar, cancelar)
- ✅ Pause/Resume subscriptions

**Status:** ✅ **Muito completo e bem estruturado**

#### 2. **PaymentService** (`App/Services/PaymentService.php`)

**Funcionalidades Implementadas:**
- ✅ Criação de customers com persistência
- ✅ Criação de subscriptions com persistência
- ✅ Processamento de webhooks
- ✅ Rotação de métodos de pagamento
- ✅ Detecção de método preferido
- ✅ Remoção de métodos expirados
- ✅ Agendamento de mudanças de plano
- ✅ Pausa/Retomada de assinaturas

**Status:** ✅ **Bem implementado com lógica de negócio**

#### 3. **WebhookController** (`App/Controllers/WebhookController.php`)

**Funcionalidades Implementadas:**
- ✅ Validação de assinatura
- ✅ Verificação de idempotência
- ✅ Processamento de eventos

**Status:** ✅ **Seguro e funcional**

#### 4. **StripeConnectService** (`App/Services/StripeConnectService.php`)

**Funcionalidades Implementadas:**
- ✅ Criação de link de onboarding
- ✅ Atualização de conta via webhook
- ✅ Verificação de conta ativa

**Status:** ⚠️ **Básico - precisa melhorias**

---

## Melhorias e Correções Necessárias

### 🔴 CRÍTICO - Alta Prioridade

#### 1. **Suporte a Múltiplas Contas Stripe (Stripe Connect)**

**Problema:** O `StripeService` sempre usa a mesma chave secreta (`STRIPE_SECRET`). Para Stripe Connect, cada tenant precisa usar sua própria chave.

**Solução:**

```php
// App/Services/StripeService.php

public function __construct(?string $secretKey = null)
{
    // Se não fornecido, usa a chave padrão
    $secretKey = $secretKey ?? Config::get('STRIPE_SECRET');
    
    if (empty($secretKey)) {
        throw new \RuntimeException("STRIPE_SECRET não configurado");
    }

    $this->client = new StripeClient($secretKey, [
        'timeout' => 10,
        'connect_timeout' => 5
    ]);
}

/**
 * Cria cliente Stripe para um tenant específico (Stripe Connect)
 */
public static function forTenant(int $tenantId): self
{
    $accountModel = new \App\Models\TenantStripeAccount();
    $account = $accountModel->findByTenant($tenantId);
    
    if ($account && !empty($account['stripe_secret_key_encrypted'])) {
        // Descriptografa a chave
        $secretKey = \App\Utils\EncryptionHelper::decrypt($account['stripe_secret_key_encrypted']);
        return new self($secretKey);
    }
    
    // Fallback para chave padrão
    return new self();
}

/**
 * Cria cliente Stripe para uma conta Connect específica
 */
public static function forConnectAccount(string $stripeAccountId): self
{
    $client = new self();
    // Configura o cliente para usar a conta Connect
    $client->client = new StripeClient(
        Config::get('STRIPE_SECRET'),
        [
            'timeout' => 10,
            'connect_timeout' => 5,
            'stripe_account' => $stripeAccountId
        ]
    );
    return $client;
}
```

**Arquivo:** `App/Services/StripeService.php`  
**Prioridade:** 🔴 **CRÍTICA**

---

#### 2. **Retry Logic para Falhas Temporárias do Stripe**

**Problema:** Não há retry automático para erros temporários (rate limits, timeouts, etc).

**Solução:**

```php
// App/Services/StripeService.php

/**
 * Executa operação com retry automático
 */
private function executeWithRetry(callable $operation, int $maxRetries = 3): mixed
{
    $attempt = 0;
    $lastException = null;
    
    while ($attempt < $maxRetries) {
        try {
            return $operation();
        } catch (\Stripe\Exception\RateLimitException $e) {
            $lastException = $e;
            $attempt++;
            $waitTime = min(pow(2, $attempt), 10); // Exponential backoff, max 10s
            
            Logger::warning("Rate limit do Stripe, aguardando retry", [
                'attempt' => $attempt,
                'wait_time' => $waitTime,
                'error' => $e->getMessage()
            ]);
            
            sleep($waitTime);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $lastException = $e;
            $attempt++;
            $waitTime = min(pow(2, $attempt), 5);
            
            Logger::warning("Erro de conexão com Stripe, aguardando retry", [
                'attempt' => $attempt,
                'wait_time' => $waitTime,
                'error' => $e->getMessage()
            ]);
            
            sleep($waitTime);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Erros não recuperáveis - não tenta novamente
            throw $e;
        }
    }
    
    // Se chegou aqui, todos os retries falharam
    throw new \RuntimeException(
        "Falha após {$maxRetries} tentativas: " . ($lastException ? $lastException->getMessage() : 'Desconhecido'),
        0,
        $lastException
    );
}

// Exemplo de uso:
public function createCustomer(array $data): \Stripe\Customer
{
    return $this->executeWithRetry(function() use ($data) {
        $customer = $this->client->customers->create([
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'metadata' => $data['metadata'] ?? []
        ]);
        
        $this->invalidateCustomersListCache();
        Logger::info("Cliente Stripe criado", ['customer_id' => $customer->id]);
        return $customer;
    });
}
```

**Arquivo:** `App/Services/StripeService.php`  
**Prioridade:** 🔴 **CRÍTICA**

---

#### 3. **Tratamento de Rate Limits do Stripe**

**Problema:** Não há tratamento específico para rate limits do Stripe (429 errors).

**Solução:**

```php
// App/Services/StripeService.php

/**
 * Trata rate limit do Stripe
 */
private function handleRateLimit(\Stripe\Exception\RateLimitException $e): void
{
    $retryAfter = $e->getHttpHeaders()['retry-after'] ?? 1;
    
    Logger::warning("Rate limit do Stripe atingido", [
        'retry_after' => $retryAfter,
        'message' => $e->getMessage()
    ]);
    
    // Aguarda o tempo especificado pelo Stripe
    sleep((int)$retryAfter);
}

// Integrar no executeWithRetry acima
```

**Arquivo:** `App/Services/StripeService.php`  
**Prioridade:** 🔴 **CRÍTICA**

---

#### 4. **Idempotência em Operações Críticas**

**Problema:** Falta controle de idempotência em operações como criação de subscriptions, payment intents, etc.

**Solução:**

```php
// App/Services/StripeService.php

/**
 * Cria Payment Intent com idempotência
 */
public function createPaymentIntent(array $data, ?string $idempotencyKey = null): \Stripe\PaymentIntent
{
    try {
        $params = [
            'amount' => (int)$data['amount'],
            'currency' => strtolower($data['currency']),
            'payment_method_types' => $data['payment_method_types'] ?? ['card']
        ];
        
        // ... outros parâmetros ...
        
        // Gera idempotency key se não fornecido
        if (!$idempotencyKey) {
            $idempotencyKey = $this->generateIdempotencyKey($data);
        }
        
        $paymentIntent = $this->client->paymentIntents->create($params, [
            'idempotency_key' => $idempotencyKey
        ]);
        
        Logger::info("Payment Intent criado", [
            'payment_intent_id' => $paymentIntent->id,
            'idempotency_key' => $idempotencyKey
        ]);
        
        return $paymentIntent;
    } catch (ApiErrorException $e) {
        Logger::error("Erro ao criar Payment Intent", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Gera chave de idempotência baseada nos dados
 */
private function generateIdempotencyKey(array $data): string
{
    $keyData = [
        'amount' => $data['amount'] ?? null,
        'currency' => $data['currency'] ?? null,
        'customer_id' => $data['customer_id'] ?? null,
        'timestamp' => time()
    ];
    
    return 'pi_' . hash('sha256', json_encode($keyData));
}
```

**Arquivo:** `App/Services/StripeService.php`  
**Prioridade:** 🔴 **CRÍTICA**

---

### 🟡 IMPORTANTE - Média Prioridade

#### 5. **Tratamento de Eventos de Webhook Faltantes**

**Problema:** Alguns eventos importantes não estão sendo tratados.

**Eventos Faltantes:**
- `payment_intent.requires_action` - 3D Secure
- `customer.subscription.created` - Nova assinatura
- `customer.subscription.trial_will_end` - Trial terminando (já existe, mas pode melhorar)
- `invoice.finalized` - Fatura finalizada
- `invoice.voided` - Fatura cancelada
- `charge.succeeded` - Cobrança bem-sucedida
- `charge.failed` - Cobrança falhada
- `payment_method.attached` - Método anexado
- `payment_method.detached` - Método removido

**Solução:**

```php
// App/Services/PaymentService.php

public function processWebhook(\Stripe\Event $event): void
{
    // ... código existente ...
    
    switch ($eventType) {
        // ... casos existentes ...
        
        // ✅ NOVO: Payment Intent requer ação (3D Secure)
        case 'payment_intent.requires_action':
            $this->handlePaymentIntentRequiresAction($event);
            break;
        
        // ✅ NOVO: Subscription criada
        case 'customer.subscription.created':
            $this->handleSubscriptionCreated($event);
            break;
        
        // ✅ NOVO: Fatura finalizada
        case 'invoice.finalized':
            $this->handleInvoiceFinalized($event);
            break;
        
        // ✅ NOVO: Fatura cancelada
        case 'invoice.voided':
            $this->handleInvoiceVoided($event);
            break;
        
        // ✅ NOVO: Cobrança bem-sucedida
        case 'charge.succeeded':
            $this->handleChargeSucceeded($event);
            break;
        
        // ✅ NOVO: Cobrança falhada
        case 'charge.failed':
            $this->handleChargeFailed($event);
            break;
        
        // ✅ NOVO: Método de pagamento anexado
        case 'payment_method.attached':
            $this->handlePaymentMethodAttached($event);
            break;
        
        // ✅ NOVO: Método de pagamento removido
        case 'payment_method.detached':
            $this->handlePaymentMethodDetached($event);
            break;
    }
}

private function handlePaymentIntentRequiresAction(\Stripe\Event $event): void
{
    $paymentIntent = $event->data->object;
    
    Logger::info("Payment Intent requer ação (3D Secure)", [
        'payment_intent_id' => $paymentIntent->id,
        'next_action_type' => $paymentIntent->next_action->type ?? null
    ]);
    
    // Notifica o cliente que precisa completar a autenticação
    // (o frontend deve lidar com isso usando client_secret)
}

private function handleSubscriptionCreated(\Stripe\Event $event): void
{
    $stripeSubscription = $event->data->object;
    
    // Busca customer no banco
    $customer = $this->customerModel->findByStripeId($stripeSubscription->customer);
    
    if ($customer) {
        // Cria/atualiza subscription no banco
        $subscriptionId = $this->subscriptionModel->createOrUpdate(
            $customer['tenant_id'],
            $customer['id'],
            $stripeSubscription->toArray()
        );
        
        Logger::info("Subscription criada via webhook", [
            'subscription_id' => $subscriptionId,
            'stripe_subscription_id' => $stripeSubscription->id
        ]);
    }
}

// ... implementar outros handlers ...
```

**Arquivo:** `App/Services/PaymentService.php`  
**Prioridade:** 🟡 **IMPORTANTE**

---

#### 6. **Melhorias no StripeConnectService**

**Problema:** Funcionalidade básica, falta recursos avançados.

**Melhorias:**

```php
// App/Services/StripeConnectService.php

/**
 * Cria link de login para conta Connect existente
 */
public function createLoginLink(int $tenantId): array
{
    $account = $this->accountModel->findByTenant($tenantId);
    
    if (!$account || !$account['stripe_account_id']) {
        throw new \RuntimeException("Conta Stripe Connect não encontrada");
    }
    
    $loginLink = $this->stripeService->getClient()->accounts->createLoginLink(
        $account['stripe_account_id']
    );
    
    return [
        'login_url' => $loginLink->url,
        'expires_at' => $loginLink->expires_at
    ];
}

/**
 * Obtém saldo da conta Connect
 */
public function getBalance(int $tenantId): array
{
    $account = $this->accountModel->findByTenant($tenantId);
    
    if (!$account || !$account['stripe_account_id']) {
        throw new \RuntimeException("Conta Stripe Connect não encontrada");
    }
    
    $stripeService = StripeService::forConnectAccount($account['stripe_account_id']);
    $balance = $stripeService->getClient()->balance->retrieve();
    
    return [
        'available' => $balance->available[0]->amount ?? 0,
        'pending' => $balance->pending[0]->amount ?? 0,
        'currency' => $balance->available[0]->currency ?? 'brl'
    ];
}

/**
 * Lista transferências da conta Connect
 */
public function listTransfers(int $tenantId, array $options = []): array
{
    $account = $this->accountModel->findByTenant($tenantId);
    
    if (!$account || !$account['stripe_account_id']) {
        throw new \RuntimeException("Conta Stripe Connect não encontrada");
    }
    
    $stripeService = StripeService::forConnectAccount($account['stripe_account_id']);
    $transfers = $stripeService->getClient()->transfers->all($options);
    
    return array_map(function($transfer) {
        return [
            'id' => $transfer->id,
            'amount' => $transfer->amount,
            'currency' => $transfer->currency,
            'status' => $transfer->status,
            'created' => date('Y-m-d H:i:s', $transfer->created)
        ];
    }, $transfers->data);
}
```

**Arquivo:** `App/Services/StripeConnectService.php`  
**Prioridade:** 🟡 **IMPORTANTE**

---

#### 7. **Validação de Webhook Secret por Tenant**

**Problema:** Todos os tenants usam o mesmo webhook secret. Para Stripe Connect, cada tenant pode ter seu próprio endpoint.

**Solução:**

```php
// App/Controllers/WebhookController.php

public function handle(): void
{
    // ... código existente ...
    
    // Tenta identificar tenant do webhook
    $tenantId = $this->identifyTenantFromWebhook($payload);
    
    // Se identificou tenant, usa webhook secret específico
    if ($tenantId) {
        $webhookSecret = $this->getTenantWebhookSecret($tenantId);
    } else {
        $webhookSecret = Config::get('STRIPE_WEBHOOK_SECRET');
    }
    
    // Valida signature
    $event = $this->stripeService->validateWebhook($payload, $signature, $webhookSecret);
    
    // ... resto do código ...
}

private function identifyTenantFromWebhook(string $payload): ?int
{
    $data = json_decode($payload, true);
    
    // Tenta identificar via metadata do evento
    if (isset($data['data']['object']['metadata']['tenant_id'])) {
        return (int)$data['data']['object']['metadata']['tenant_id'];
    }
    
    // Tenta identificar via customer
    if (isset($data['data']['object']['customer'])) {
        $customerModel = new \App\Models\Customer();
        $customer = $customerModel->findByStripeId($data['data']['object']['customer']);
        
        if ($customer) {
            return (int)$customer['tenant_id'];
        }
    }
    
    return null;
}

private function getTenantWebhookSecret(int $tenantId): ?string
{
    $accountModel = new \App\Models\TenantStripeAccount();
    $account = $accountModel->findByTenant($tenantId);
    
    if ($account && !empty($account['webhook_secret_encrypted'])) {
        return \App\Utils\EncryptionHelper::decrypt($account['webhook_secret_encrypted']);
    }
    
    return null;
}
```

**Arquivo:** `App/Controllers/WebhookController.php`  
**Prioridade:** 🟡 **IMPORTANTE**

---

#### 8. **Monitoramento e Alertas para Falhas do Stripe**

**Problema:** Não há alertas quando ocorrem falhas críticas do Stripe.

**Solução:**

```php
// App/Services/StripeAlertService.php (já existe, mas pode melhorar)

/**
 * Verifica e envia alertas para falhas críticas
 */
public function checkCriticalFailures(): void
{
    // Verifica webhooks falhados nas últimas 24h
    $eventModel = new \App\Models\StripeEvent();
    $failedEvents = $eventModel->findFailedEvents(24);
    
    if (count($failedEvents) > 10) {
        $this->sendAlert('stripe_webhook_failures', [
            'count' => count($failedEvents),
            'period' => '24h'
        ]);
    }
    
    // Verifica rate limits
    $rateLimitErrors = $this->getRateLimitErrors(24);
    if (count($rateLimitErrors) > 5) {
        $this->sendAlert('stripe_rate_limits', [
            'count' => count($rateLimitErrors),
            'period' => '24h'
        ]);
    }
}

/**
 * Envia alerta
 */
private function sendAlert(string $type, array $data): void
{
    // Envia email, Slack, etc.
    Logger::critical("Alerta Stripe: {$type}", $data);
    
    // Pode integrar com serviços externos (Slack, PagerDuty, etc.)
}
```

**Arquivo:** `App/Services/StripeAlertService.php`  
**Prioridade:** 🟡 **IMPORTANTE**

---

### 🟢 MELHORIAS - Baixa Prioridade

#### 9. **Cache de Dados do Stripe**

**Problema:** Algumas operações fazem múltiplas chamadas ao Stripe que poderiam ser cacheadas.

**Solução:**

```php
// App/Services/StripeService.php

/**
 * Obtém customer com cache
 */
public function getCustomer(string $customerId, bool $useCache = true): \Stripe\Customer
{
    if ($useCache) {
        $cacheKey = "stripe:customer:{$customerId}";
        $cached = \App\Services\CacheService::getJson($cacheKey);
        
        if ($cached !== null) {
            // Reconstrói objeto Stripe (simplificado)
            return $this->client->customers->retrieve($customerId);
        }
    }
    
    $customer = $this->client->customers->retrieve($customerId);
    
    if ($useCache) {
        \App\Services\CacheService::setJson($cacheKey, $customer->toArray(), 300); // 5 min
    }
    
    return $customer;
}
```

**Arquivo:** `App/Services/StripeService.php`  
**Prioridade:** 🟢 **MELHORIA**

---

#### 10. **Logging Estruturado Melhorado**

**Problema:** Logs não têm contexto suficiente para debugging.

**Solução:**

```php
// App/Services/StripeService.php

private function logStripeOperation(string $operation, array $context, ?\Exception $error = null): void
{
    $logData = [
        'operation' => $operation,
        'timestamp' => date('Y-m-d H:i:s'),
        'context' => $context,
        'error' => $error ? [
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'type' => get_class($error)
        ] : null
    ];
    
    if ($error) {
        Logger::error("Operação Stripe falhou", $logData);
    } else {
        Logger::info("Operação Stripe executada", $logData);
    }
}
```

**Arquivo:** `App/Services/StripeService.php`  
**Prioridade:** 🟢 **MELHORIA**

---

#### 11. **Validação de Dados de Entrada Melhorada**

**Problema:** Algumas validações poderiam ser mais rigorosas.

**Solução:**

```php
// App/Utils/Validator.php (adicionar métodos)

public static function validateStripePriceId(string $priceId, string $field = 'price_id'): array
{
    $errors = [];
    
    if (empty($priceId)) {
        $errors[$field] = 'Price ID é obrigatório';
        return $errors;
    }
    
    if (!preg_match('/^price_[a-zA-Z0-9]+$/', $priceId)) {
        $errors[$field] = 'Price ID inválido (deve começar com "price_")';
    }
    
    return $errors;
}

public static function validateStripeCustomerId(string $customerId, string $field = 'customer_id'): array
{
    $errors = [];
    
    if (empty($customerId)) {
        $errors[$field] = 'Customer ID é obrigatório';
        return $errors;
    }
    
    if (!preg_match('/^cus_[a-zA-Z0-9]+$/', $customerId)) {
        $errors[$field] = 'Customer ID inválido (deve começar com "cus_")';
    }
    
    return $errors;
}
```

**Arquivo:** `App/Utils/Validator.php`  
**Prioridade:** 🟢 **MELHORIA**

---

## Segurança

### ✅ Boas Práticas Já Implementadas

1. ✅ Validação de webhook signature
2. ✅ Verificação de idempotência
3. ✅ Proteção IDOR (validação de tenant_id)
4. ✅ CSRF protection
5. ✅ Rate limiting
6. ✅ Headers de segurança (CSP, HSTS, etc)
7. ✅ Criptografia de chaves sensíveis (TenantStripeAccount)

### ⚠️ Melhorias de Segurança Necessárias

#### 1. **Validação de Webhook Secret por Ambiente**

**Problema:** Mesmo webhook secret para test e production.

**Solução:**

```php
// App/Services/StripeService.php

public function validateWebhook(string $payload, string $signature, ?string $webhookSecret = null): \Stripe\Event
{
    $webhookSecret = $webhookSecret ?? Config::get('STRIPE_WEBHOOK_SECRET');
    
    if (empty($webhookSecret)) {
        throw new \RuntimeException("STRIPE_WEBHOOK_SECRET não configurado");
    }
    
    try {
        return \Stripe\Webhook::constructEvent($payload, $signature, $webhookSecret);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        Logger::error("Webhook signature inválida", [
            'error' => $e->getMessage(),
            'environment' => Config::env()
        ]);
        throw $e;
    }
}
```

**Arquivo:** `App/Services/StripeService.php`  
**Prioridade:** 🔴 **CRÍTICA**

---

#### 2. **Sanitização de Dados do Stripe**

**Problema:** Dados do Stripe são salvos diretamente no banco sem sanitização.

**Solução:**

```php
// App/Models/Subscription.php

public function createOrUpdate(int $tenantId, int $customerId, array $stripeData): int
{
    // Sanitiza dados antes de salvar
    $sanitized = [
        'stripe_subscription_id' => $this->sanitizeStripeId($stripeData['id'] ?? ''),
        'status' => $this->sanitizeStatus($stripeData['status'] ?? ''),
        'plan_id' => $this->sanitizeStripeId($stripeData['items']['data'][0]['price']['id'] ?? ''),
        // ... outros campos ...
    ];
    
    // ... salva no banco ...
}

private function sanitizeStripeId(string $id): string
{
    return preg_replace('/[^a-zA-Z0-9_]/', '', $id);
}

private function sanitizeStatus(string $status): string
{
    $validStatuses = ['active', 'canceled', 'past_due', 'trialing', 'unpaid', 'incomplete', 'incomplete_expired', 'paused'];
    return in_array($status, $validStatuses) ? $status : 'unknown';
}
```

**Arquivo:** `App/Models/Subscription.php`  
**Prioridade:** 🟡 **IMPORTANTE**

---

## Performance e Otimização

### ✅ Otimizações Já Implementadas

1. ✅ Cache de autenticação (5 minutos)
2. ✅ Cache de listagens (60 segundos)
3. ✅ RequestCache para evitar múltiplas leituras de input
4. ✅ Timeout configurado no StripeClient (10s)
5. ✅ Compressão de resposta (gzip)

### ⚠️ Melhorias de Performance

#### 1. **Cache de Dados do Stripe**

Já mencionado na seção de melhorias.

#### 2. **Batch Operations**

**Problema:** Múltiplas operações individuais quando poderia ser batch.

**Solução:**

```php
// App/Services/StripeService.php

/**
 * Cria múltiplos customers em batch
 */
public function createCustomersBatch(array $customersData): array
{
    $results = [];
    
    foreach ($customersData as $data) {
        try {
            $customer = $this->createCustomer($data);
            $results[] = ['success' => true, 'customer' => $customer];
        } catch (\Exception $e) {
            $results[] = ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    return $results;
}
```

**Arquivo:** `App/Services/StripeService.php`  
**Prioridade:** 🟢 **MELHORIA**

---

## Tratamento de Erros e Logs

### ✅ Boas Práticas Já Implementadas

1. ✅ Logger service estruturado
2. ✅ ResponseHelper padronizado
3. ✅ ErrorHandler centralizado
4. ✅ Tratamento de exceções do Stripe

### ⚠️ Melhorias Necessárias

#### 1. **Categorização de Erros do Stripe**

**Solução:**

```php
// App/Utils/StripeErrorHandler.php (NOVO)

class StripeErrorHandler
{
    public static function categorize(\Stripe\Exception\ApiErrorException $e): array
    {
        $category = 'unknown';
        $action = 'retry';
        $userMessage = 'Erro ao processar pagamento';
        
        if ($e instanceof \Stripe\Exception\CardException) {
            $category = 'card_error';
            $action = 'no_retry';
            $userMessage = self::getCardErrorMessage($e);
        } elseif ($e instanceof \Stripe\Exception\RateLimitException) {
            $category = 'rate_limit';
            $action = 'retry_with_backoff';
        } elseif ($e instanceof \Stripe\Exception\InvalidRequestException) {
            $category = 'invalid_request';
            $action = 'no_retry';
            $userMessage = 'Dados inválidos';
        } elseif ($e instanceof \Stripe\Exception\AuthenticationException) {
            $category = 'authentication_error';
            $action = 'no_retry';
            $userMessage = 'Erro de autenticação';
        } elseif ($e instanceof \Stripe\Exception\ApiConnectionException) {
            $category = 'connection_error';
            $action = 'retry_with_backoff';
        }
        
        return [
            'category' => $category,
            'action' => $action,
            'user_message' => $userMessage,
            'stripe_code' => $e->getStripeCode(),
            'decline_code' => $e->getDeclineCode() ?? null
        ];
    }
    
    private static function getCardErrorMessage(\Stripe\Exception\CardException $e): string
    {
        $declineCode = $e->getDeclineCode();
        
        $messages = [
            'insufficient_funds' => 'Saldo insuficiente',
            'lost_card' => 'Cartão reportado como perdido',
            'stolen_card' => 'Cartão reportado como roubado',
            'expired_card' => 'Cartão expirado',
            'incorrect_cvc' => 'Código de segurança incorreto',
            'incorrect_number' => 'Número do cartão incorreto'
        ];
        
        return $messages[$declineCode] ?? 'Erro no cartão de crédito';
    }
}
```

**Arquivo:** `App/Utils/StripeErrorHandler.php` (NOVO)  
**Prioridade:** 🟡 **IMPORTANTE**

---

## Webhooks

### ✅ Implementação Atual

1. ✅ Validação de signature
2. ✅ Verificação de idempotência
3. ✅ Processamento de eventos principais
4. ✅ Logging estruturado

### ⚠️ Melhorias Necessárias

#### 1. **Queue para Processamento de Webhooks**

**Problema:** Webhooks são processados sincronamente, podendo causar timeout.

**Solução:**

```php
// App/Services/WebhookQueueService.php (NOVO)

class WebhookQueueService
{
    public function queueWebhook(\Stripe\Event $event): void
    {
        // Salva evento em fila (Redis, banco de dados, etc)
        $queueModel = new \App\Models\WebhookQueue();
        $queueModel->enqueue([
            'event_id' => $event->id,
            'event_type' => $event->type,
            'event_data' => $event->toArray(),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function processQueue(): void
    {
        $queueModel = new \App\Models\WebhookQueue();
        $pending = $queueModel->getPending(10); // Processa 10 por vez
        
        foreach ($pending as $item) {
            try {
                $event = \Stripe\Event::constructFrom($item['event_data']);
                $paymentService = new \App\Services\PaymentService(...);
                $paymentService->processWebhook($event);
                
                $queueModel->markAsProcessed($item['id']);
            } catch (\Exception $e) {
                $queueModel->markAsFailed($item['id'], $e->getMessage());
            }
        }
    }
}
```

**Arquivo:** `App/Services/WebhookQueueService.php` (NOVO)  
**Prioridade:** 🟡 **IMPORTANTE**

---

## Boas Práticas e Padrões

### ✅ Padrões Já Seguidos

1. ✅ PSR-4 autoloading
2. ✅ Namespaces organizados
3. ✅ Injeção de dependências
4. ✅ Separação de responsabilidades
5. ✅ Validação de dados
6. ✅ Tratamento de erros padronizado

### ⚠️ Melhorias

#### 1. **DTOs para Dados do Stripe**

**Solução:**

```php
// App/DTOs/Stripe/CreateCustomerDTO.php (NOVO)

class CreateCustomerDTO
{
    public function __construct(
        public readonly ?string $email,
        public readonly ?string $name,
        public readonly array $metadata = []
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? null,
            name: $data['name'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }
    
    public function toStripeArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'metadata' => $this->metadata
        ];
    }
}
```

**Arquivo:** `App/DTOs/Stripe/` (NOVO)  
**Prioridade:** 🟢 **MELHORIA**

---

## Checklist de Implementação

### Fase 1: Crítico (1-2 semanas)

- [ ] **1.1** Implementar suporte a múltiplas contas Stripe (Stripe Connect)
- [ ] **1.2** Implementar retry logic para falhas temporárias
- [ ] **1.3** Implementar tratamento de rate limits do Stripe
- [ ] **1.4** Implementar idempotência em operações críticas
- [ ] **1.5** Melhorar validação de webhook secret por ambiente

### Fase 2: Importante (2-3 semanas)

- [ ] **2.1** Implementar tratamento de eventos de webhook faltantes
- [ ] **2.2** Melhorar StripeConnectService com recursos avançados
- [ ] **2.3** Implementar validação de webhook secret por tenant
- [ ] **2.4** Implementar monitoramento e alertas
- [ ] **2.5** Implementar categorização de erros do Stripe
- [ ] **2.6** Implementar queue para processamento de webhooks

### Fase 3: Melhorias (3-4 semanas)

- [ ] **3.1** Implementar cache de dados do Stripe
- [ ] **3.2** Melhorar logging estruturado
- [ ] **3.3** Implementar validações mais rigorosas
- [ ] **3.4** Implementar DTOs para dados do Stripe
- [ ] **3.5** Implementar batch operations

### Fase 4: Testes e Documentação (2 semanas)

- [ ] **4.1** Criar testes unitários para StripeService
- [ ] **4.2** Criar testes de integração para webhooks
- [ ] **4.3** Documentar todas as integrações Stripe
- [ ] **4.4** Criar guia de troubleshooting
- [ ] **4.5** Documentar fluxos de pagamento

---

## Conclusão

O sistema está **bem estruturado** e com uma **base sólida** de integrações Stripe. As principais melhorias necessárias são:

1. **Suporte completo a Stripe Connect** (múltiplas contas)
2. **Resiliência** (retry logic, rate limits)
3. **Monitoramento** (alertas, métricas)
4. **Completude** (eventos de webhook faltantes)

Com as implementações sugeridas, o sistema estará **pronto para produção** em escala, com alta disponibilidade e resiliência.

---

**Documento criado em:** 2025-01-09  
**Última atualização:** 2025-01-09  
**Versão:** 1.0

