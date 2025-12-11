# Guia Completo: Integração Stripe com FlightPHP

## 📋 Índice

1. [Visão Geral da Arquitetura](#visão-geral-da-arquitetura)
2. [Estrutura de Diretórios](#estrutura-de-diretórios)
3. [Configuração Inicial](#configuração-inicial)
4. [StripeService - Serviço Principal](#stripeservice---serviço-principal)
5. [Controllers e Rotas](#controllers-e-rotas)
6. [Casos de Uso Comuns](#casos-de-uso-comuns)
7. [Segurança e Boas Práticas](#segurança-e-boas-práticas)
8. [Webhooks](#webhooks)
9. [Tratamento de Erros](#tratamento-de-erros)
10. [Testes](#testes)

---


## Visão Geral da Arquitetura

### Arquitetura Multi-Tenant com Stripe

O sistema implementa uma arquitetura multi-tenant onde:

- **Plataforma SaaS**: Usa conta Stripe principal (`STRIPE_SECRET`) para receber assinaturas mensais das clínicas
- **Clínicas (Tenants)**: Podem ter suas próprias contas Stripe Connect para receber pagamentos de seus clientes

### Fluxo de Dados

```
┌─────────────────┐
│   Frontend      │
│   (Cliente)     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   FlightPHP     │
│   Controller    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Service       │
│   (StripeService)│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Stripe API    │
└─────────────────┘
```

### Separação de Responsabilidades

```
Controller → Service → Stripe API
    ↓           ↓          ↓
Validação   Lógica    Comunicação
Resposta    Negócio   Externa
```

---

## Estrutura de Diretórios

```
App/
├── Controllers/          # Camada de controle HTTP
│   ├── CheckoutController.php
│   ├── PaymentController.php
│   ├── SubscriptionController.php
│   ├── CustomerController.php
│   └── WebhookController.php
│
├── Services/            # Camada de lógica de negócio
│   ├── StripeService.php      # Wrapper principal do Stripe
│   ├── PaymentService.php     # Lógica de pagamentos
│   └── StripeConnectService.php
│
├── Models/              # Camada de acesso a dados
│   ├── Customer.php
│   ├── Subscription.php
│   └── StripeEvent.php
│
├── Middleware/          # Middlewares de autenticação, validação, etc.
│
├── Utils/               # Utilitários
│   ├── ResponseHelper.php
│   ├── Validator.php
│   └── EncryptionHelper.php
│
└── Core/                # Core do sistema
    ├── Container.php
    └── ContainerBindings.php
```

---

## Configuração Inicial

### 1. Instalação da Biblioteca Stripe

```bash
composer require stripe/stripe-php
```

### 2. Variáveis de Ambiente

No arquivo `.env`:

```env
# Conta Stripe da Plataforma (para assinaturas SaaS)
STRIPE_SECRET=sk_live_xxx
STRIPE_PUBLISHABLE_KEY=pk_live_xxx

# Webhook Secret (para validação de webhooks)
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Configurações opcionais
STRIPE_API_VERSION=2023-10-16
```

### 3. Configuração no Config.php

```php
// config/config.php
return [
    'STRIPE_SECRET' => getenv('STRIPE_SECRET'),
    'STRIPE_PUBLISHABLE_KEY' => getenv('STRIPE_PUBLISHABLE_KEY'),
    'STRIPE_WEBHOOK_SECRET' => getenv('STRIPE_WEBHOOK_SECRET'),
];
```

---

## StripeService - Serviço Principal

### Conceito

O `StripeService` é um wrapper que encapsula todas as interações com a API do Stripe, fornecendo:

- ✅ Abstração da API Stripe
- ✅ Suporte a múltiplas contas (multi-tenant)
- ✅ Tratamento de erros padronizado
- ✅ Logging automático
- ✅ Idempotência
- ✅ Cache quando apropriado

### Inicialização

#### 1. Conta Padrão (Plataforma)

```php
use App\Services\StripeService;

// Usa STRIPE_SECRET do .env
$stripeService = new StripeService();
```

**Uso:** Assinaturas SaaS que as clínicas pagam para a plataforma.

#### 2. Conta por Tenant (Stripe Connect)

```php
use App\Services\StripeService;

// Busca chave do tenant no banco de dados
$stripeService = StripeService::forTenant($tenantId);
```

**Uso:** Pagamentos que a clínica recebe de seus clientes.

#### 3. Conta Connect (stripe_account)

```php
use App\Services\StripeService;

// Usa chave da plataforma, mas opera em nome de outra conta
$stripeService = StripeService::forConnectAccount('acct_xxx');
```

**Uso:** Operações em nome de uma conta Connect específica.

### Métodos Principais

#### Criar Cliente

```php
$customer = $stripeService->createCustomer([
    'email' => 'cliente@example.com',
    'name' => 'João Silva',
    'metadata' => [
        'tenant_id' => $tenantId,
        'user_id' => $userId
    ]
], $idempotencyKey);
```

#### Criar Sessão de Checkout

```php
$session = $stripeService->createCheckoutSession([
    'customer_id' => $customerId,
    'line_items' => [
        [
            'price' => 'price_xxx',
            'quantity' => 1
        ]
    ],
    'mode' => 'subscription', // ou 'payment'
    'success_url' => 'https://example.com/success',
    'cancel_url' => 'https://example.com/cancel',
    'metadata' => [
        'tenant_id' => $tenantId
    ]
]);
```

#### Criar Payment Intent

```php
$paymentIntent = $stripeService->createPaymentIntent([
    'amount' => 10000, // R$ 100,00 em centavos
    'currency' => 'brl',
    'customer' => $customerId,
    'description' => 'Consulta veterinária',
    'metadata' => [
        'appointment_id' => $appointmentId
    ]
]);
```

#### Criar Assinatura

```php
$subscription = $stripeService->createSubscription([
    'customer_id' => $customerId,
    'price_id' => 'price_xxx',
    'trial_period_days' => 14,
    'metadata' => [
        'tenant_id' => $tenantId
    ]
]);
```

---

## Controllers e Rotas

### Padrão de Controller

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

    public function createPaymentIntent(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            // Determina qual conta Stripe usar
            $stripeService = StripeService::forTenant($tenantId);
            
            // Valida entrada
            $data = \App\Utils\RequestCache::getJsonInput();
            $errors = \App\Utils\Validator::validatePaymentIntentCreate($data);
            
            if (!empty($errors)) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    $errors,
                    ['action' => 'create_payment_intent']
                );
                return;
            }
            
            // Adiciona metadata do tenant
            $data['metadata']['tenant_id'] = $tenantId;
            
            // Cria payment intent
            $paymentIntent = $stripeService->createPaymentIntent($data);
            
            // Retorna resposta
            ResponseHelper::sendCreated([
                'id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'status' => $paymentIntent->status
            ]);
            
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

### Registro de Rotas

```php
// public/index.php

use App\Core\Container;
use App\Core\ContainerBindings;

// Inicializa container
$container = new Container();
ContainerBindings::register($container);

// Cria controller via container
$paymentController = $container->make(\App\Controllers\PaymentController::class);

// Registra rotas
$app->route('POST /v1/payment-intents', [$paymentController, 'createPaymentIntent']);
```

---

## Casos de Uso Comuns

### 1. Checkout de Assinatura (SaaS)

**Cenário:** Clínica assina um plano mensal da plataforma.

```php
// Controller: SaasController
public function createCheckout(): void
{
    $data = \App\Utils\RequestCache::getJsonInput();
    $tenantId = Flight::get('tenant_id');
    
    // ✅ SEMPRE usa conta padrão (plataforma recebe)
    $stripeService = new StripeService();
    
    // Busca customer no Stripe ou cria novo
    $customerModel = new \App\Models\Customer();
    $customer = $customerModel->findByTenant($tenantId);
    
    if (!$customer || !$customer['stripe_customer_id']) {
        // Cria customer no Stripe
        $stripeCustomer = $stripeService->createCustomer([
            'email' => $user['email'],
            'name' => $tenant['name'],
            'metadata' => ['tenant_id' => $tenantId]
        ]);
        
        // Salva no banco
        $customerModel->create([
            'tenant_id' => $tenantId,
            'stripe_customer_id' => $stripeCustomer->id
        ]);
    }
    
    // Cria sessão de checkout
    $session = $stripeService->createCheckoutSession([
        'customer_id' => $customer['stripe_customer_id'],
        'line_items' => [
            ['price' => $data['price_id'], 'quantity' => 1]
        ],
        'mode' => 'subscription',
        'success_url' => 'https://example.com/my-subscription?success=true',
        'cancel_url' => 'https://example.com/my-subscription?canceled=true',
        'metadata' => ['tenant_id' => $tenantId]
    ]);
    
    ResponseHelper::sendCreated([
        'session_id' => $session->id,
        'url' => $session->url
    ]);
}
```

### 2. Pagamento Único (Clínica recebe)

**Cenário:** Cliente da clínica paga por uma consulta.

```php
// Controller: PaymentController
public function createPaymentIntent(): void
{
    $tenantId = Flight::get('tenant_id');
    
    // ✅ SEMPRE usa conta do tenant (clínica recebe)
    $stripeService = StripeService::forTenant($tenantId);
    
    $data = \App\Utils\RequestCache::getJsonInput();
    
    // Validação
    $errors = \App\Utils\Validator::validatePaymentIntentCreate($data);
    if (!empty($errors)) {
        ResponseHelper::sendValidationError('Dados inválidos', $errors);
        return;
    }
    
    // Adiciona metadata
    $data['metadata']['tenant_id'] = $tenantId;
    $data['metadata']['appointment_id'] = $data['appointment_id'];
    
    // Cria payment intent
    $paymentIntent = $stripeService->createPaymentIntent($data);
    
    ResponseHelper::sendCreated([
        'id' => $paymentIntent->id,
        'client_secret' => $paymentIntent->client_secret,
        'amount' => $paymentIntent->amount,
        'currency' => $paymentIntent->currency
    ]);
}
```

### 3. Salvar Cartão (Setup Intent)

**Cenário:** Cliente salva cartão para pagamentos futuros.

```php
// Controller: SetupIntentController
public function create(): void
{
    $tenantId = Flight::get('tenant_id');
    $stripeService = StripeService::forTenant($tenantId);
    
    $data = \App\Utils\RequestCache::getJsonInput();
    
    // Busca ou cria customer
    $customerModel = new \App\Models\Customer();
    $customer = $customerModel->findByTenantAndId($tenantId, $data['customer_id']);
    
    if (!$customer['stripe_customer_id']) {
        // Cria customer no Stripe
        $stripeCustomer = $stripeService->createCustomer([
            'email' => $customer['email'],
            'metadata' => ['tenant_id' => $tenantId]
        ]);
        
        $customerModel->update($customer['id'], [
            'stripe_customer_id' => $stripeCustomer->id
        ]);
    }
    
    // Cria setup intent
    $setupIntent = $stripeService->getClient()->setupIntents->create([
        'customer' => $customer['stripe_customer_id'],
        'payment_method_types' => ['card'],
        'metadata' => ['tenant_id' => $tenantId]
    ]);
    
    ResponseHelper::sendCreated([
        'id' => $setupIntent->id,
        'client_secret' => $setupIntent->client_secret
    ]);
}
```

### 4. Portal do Cliente (Billing Portal)

**Cenário:** Cliente gerencia assinatura, métodos de pagamento, etc.

```php
// Controller: BillingPortalController
public function create(): void
{
    $tenantId = Flight::get('tenant_id');
    
    // ✅ Para assinaturas SaaS, usa conta padrão
    $stripeService = new StripeService();
    
    $data = \App\Utils\RequestCache::getJsonInput();
    
    // Busca customer
    $customerModel = new \App\Models\Customer();
    $customer = $customerModel->findByTenant($tenantId);
    
    if (!$customer || !$customer['stripe_customer_id']) {
        ResponseHelper::sendNotFoundError('Cliente não encontrado');
        return;
    }
    
    // Cria sessão do portal
    $session = $stripeService->getClient()->billingPortal->sessions->create([
        'customer' => $customer['stripe_customer_id'],
        'return_url' => $data['return_url']
    ]);
    
    ResponseHelper::sendCreated([
        'url' => $session->url
    ]);
}
```

### 5. Reembolso

**Cenário:** Clínica reembolsa um pagamento.

```php
// Controller: PaymentController
public function createRefund(): void
{
    $tenantId = Flight::get('tenant_id');
    $stripeService = StripeService::forTenant($tenantId);
    
    $data = \App\Utils\RequestCache::getJsonInput();
    
    // Validação
    if (empty($data['payment_intent_id'])) {
        ResponseHelper::sendValidationError('payment_intent_id é obrigatório');
        return;
    }
    
    // Cria reembolso
    $refund = $stripeService->refundPayment(
        $data['payment_intent_id'],
        [
            'amount' => $data['amount'] ?? null, // null = reembolso total
            'reason' => $data['reason'] ?? null,
            'metadata' => ['tenant_id' => $tenantId]
        ]
    );
    
    ResponseHelper::sendCreated([
        'id' => $refund->id,
        'amount' => $refund->amount,
        'status' => $refund->status
    ]);
}
```

---

## Segurança e Boas Práticas

### 1. Validação de Entrada

Sempre valide dados de entrada antes de enviar para o Stripe:

```php
use App\Utils\Validator;

$errors = Validator::validatePaymentIntentCreate($data);
if (!empty($errors)) {
    ResponseHelper::sendValidationError('Dados inválidos', $errors);
    return;
}
```

### 2. Proteção IDOR (Insecure Direct Object Reference)

Sempre verifique se o recurso pertence ao tenant:

```php
$customer = $customerModel->findByTenantAndId($tenantId, $customerId);
if (!$customer) {
    ResponseHelper::sendForbiddenError('Recurso não encontrado');
    return;
}
```

### 3. Idempotência

Use idempotency keys para operações críticas:

```php
$idempotencyKey = $this->generateIdempotencyKey('payment', $data);
$paymentIntent = $stripeService->createPaymentIntent($data, $idempotencyKey);
```

### 4. Metadata

Sempre adicione metadata para rastreabilidade:

```php
$data['metadata'] = [
    'tenant_id' => $tenantId,
    'user_id' => $userId,
    'appointment_id' => $appointmentId
];
```

### 5. Logging

Todas as operações são logadas automaticamente pelo `StripeService`:

```php
Logger::info("Payment intent criado", [
    'payment_intent_id' => $paymentIntent->id,
    'tenant_id' => $tenantId,
    'amount' => $paymentIntent->amount
]);
```

### 6. Tratamento de Erros

Use `ResponseHelper` para respostas padronizadas:

```php
try {
    // Operação Stripe
} catch (\Stripe\Exception\CardException $e) {
    // Erro de cartão (ex: cartão recusado)
    ResponseHelper::sendStripeError($e, 'Erro no pagamento');
} catch (\Stripe\Exception\RateLimitException $e) {
    // Rate limit excedido
    ResponseHelper::sendStripeError($e, 'Muitas requisições');
} catch (\Stripe\Exception\InvalidRequestException $e) {
    // Requisição inválida
    ResponseHelper::sendStripeError($e, 'Dados inválidos');
} catch (\Stripe\Exception\AuthenticationException $e) {
    // Erro de autenticação
    ResponseHelper::sendStripeError($e, 'Erro de autenticação');
} catch (\Stripe\Exception\ApiConnectionException $e) {
    // Erro de conexão
    ResponseHelper::sendStripeError($e, 'Erro de conexão');
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Outros erros da API
    ResponseHelper::sendStripeError($e, 'Erro na API Stripe');
} catch (\Exception $e) {
    // Erros genéricos
    ResponseHelper::sendGenericError($e, 'Erro inesperado');
}
```

### 7. Chaves Secretas

**NUNCA** exponha chaves secretas no frontend:

```php
// ❌ ERRADO - NUNCA faça isso
return ['secret_key' => Config::get('STRIPE_SECRET')];

// ✅ CORRETO - Apenas chave pública
return ['publishable_key' => Config::get('STRIPE_PUBLISHABLE_KEY')];
```

### 8. Validação de URLs

Valide URLs de redirecionamento para prevenir SSRF:

```php
private function validateRedirectUrl(string $url): bool
{
    $parsed = parse_url($url);
    
    // Apenas HTTPS (exceto desenvolvimento)
    if ($parsed['scheme'] !== 'https' && !Config::isDevelopment()) {
        return false;
    }
    
    // Bloqueia esquemas perigosos
    $dangerousSchemes = ['file', 'ftp', 'javascript', 'data'];
    if (in_array($parsed['scheme'], $dangerousSchemes)) {
        return false;
    }
    
    return true;
}
```

---

## Webhooks

### Configuração

1. Configure webhook no Dashboard do Stripe
2. URL: `https://seu-dominio.com/v1/webhook`
3. Eventos: Selecione os eventos necessários
4. Copie o `webhook secret` para `.env`

### Implementação

```php
// Controller: WebhookController
public function handle(): void
{
    $payload = \App\Utils\RequestCache::getInput();
    $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
    
    if (!$signature) {
        ResponseHelper::sendValidationError('Signature não fornecida');
        return;
    }
    
    // Valida signature
    $event = $this->stripeService->validateWebhook($payload, $signature);
    
    // Verifica idempotência
    $eventModel = new \App\Models\StripeEvent();
    if ($eventModel->isProcessed($event->id)) {
        ResponseHelper::sendSuccess(['already_processed' => true]);
        return;
    }
    
    // Processa evento
    $this->paymentService->processWebhook($event);
    
    ResponseHelper::sendSuccess(['received' => true]);
}
```

### Eventos Comuns

```php
// PaymentService::processWebhook()
switch ($event->type) {
    case 'payment_intent.succeeded':
        $this->handlePaymentSucceeded($event->data->object);
        break;
        
    case 'payment_intent.payment_failed':
        $this->handlePaymentFailed($event->data->object);
        break;
        
    case 'customer.subscription.created':
        $this->handleSubscriptionCreated($event->data->object);
        break;
        
    case 'customer.subscription.updated':
        $this->handleSubscriptionUpdated($event->data->object);
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
```

---

## Tratamento de Erros

### Códigos HTTP Padronizados

```php
// Sucesso
200 OK - Operação bem-sucedida
201 Created - Recurso criado

// Erros do Cliente
400 Bad Request - Dados inválidos
401 Unauthorized - Não autenticado
403 Forbidden - Sem permissão
404 Not Found - Recurso não encontrado
422 Unprocessable Entity - Validação falhou

// Erros do Servidor
500 Internal Server Error - Erro genérico
502 Bad Gateway - Erro na API Stripe
503 Service Unavailable - Serviço indisponível
```

### Resposta Padronizada

```php
// ResponseHelper::sendSuccess()
{
    "success": true,
    "message": "Operação realizada com sucesso",
    "data": { ... }
}

// ResponseHelper::sendError()
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Mensagem de erro",
        "details": { ... }
    }
}
```

---

## Testes

### Teste Unitário

```php
use PHPUnit\Framework\TestCase;
use App\Services\StripeService;

class StripeServiceTest extends TestCase
{
    public function testCreateCustomer()
    {
        $stripeService = new StripeService();
        
        $customer = $stripeService->createCustomer([
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);
        
        $this->assertInstanceOf(\Stripe\Customer::class, $customer);
        $this->assertEquals('test@example.com', $customer->email);
    }
}
```

### Teste de Integração

```php
use PHPUnit\Framework\TestCase;

class PaymentControllerTest extends TestCase
{
    public function testCreatePaymentIntent()
    {
        // Mock do StripeService
        $stripeService = $this->createMock(StripeService::class);
        
        // Configura controller
        $controller = new PaymentController($stripeService);
        
        // Testa criação
        // ...
    }
}
```

---

## Checklist de Implementação

### ✅ Antes de Implementar

- [ ] Configurar variáveis de ambiente
- [ ] Instalar dependências (`composer install`)
- [ ] Configurar webhook no Stripe Dashboard
- [ ] Testar conexão com Stripe (test mode)

### ✅ Ao Implementar

- [ ] Validar todos os dados de entrada
- [ ] Verificar permissões (tenant, usuário)
- [ ] Adicionar metadata para rastreabilidade
- [ ] Implementar idempotência para operações críticas
- [ ] Tratar todos os tipos de erro
- [ ] Adicionar logging
- [ ] Testar em modo de teste primeiro

### ✅ Após Implementar

- [ ] Testar fluxo completo
- [ ] Verificar logs
- [ ] Testar tratamento de erros
- [ ] Validar webhooks
- [ ] Documentar endpoints
- [ ] Revisar segurança

---

## Recursos Adicionais

- [Documentação Oficial Stripe](https://stripe.com/docs/api)
- [Stripe PHP SDK](https://github.com/stripe/stripe-php)
- [FlightPHP Documentation](https://flightphp.com/)
- [Arquitetura Stripe do Projeto](./ARQUITETURA_STRIPE.md)

---

## Suporte

Para dúvidas ou problemas:

1. Consulte a documentação oficial do Stripe
2. Verifique os logs do sistema
3. Revise este guia
4. Consulte a documentação de arquitetura

---

**Última atualização:** Dezembro 2024

