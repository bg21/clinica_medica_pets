# Arquitetura Stripe - Sistema Multi-Tenant

## Visão Geral

Este sistema implementa uma arquitetura multi-tenant com Stripe, onde:

1. **Você (Plataforma SaaS)** tem uma conta Stripe (`STRIPE_SECRET` no `.env`)
2. **Cada Clínica (Tenant)** pode ter sua própria conta Stripe Connect
3. **Assinaturas do SaaS** são pagas para você (sua conta Stripe)
4. **Pagamentos dos clientes da clínica** vão para a conta Stripe da clínica

## Contas Stripe

### 1. Conta da Plataforma (Sua Conta)

**Onde:** `STRIPE_SECRET` no arquivo `.env`

**Pertence a:** Você (vendedor do SaaS)

**Usada para:**
- ✅ Assinaturas mensais que as clínicas pagam para você
- ✅ Planos SaaS (Básico, Premium, Enterprise)
- ✅ Checkout de assinatura (`/v1/saas/checkout`)
- ✅ Página `/my-subscription` (clínica gerencia sua assinatura do SaaS)

**Como usar:**
```php
// Usa conta padrão (sua conta)
$stripeService = new StripeService();
```

### 2. Conta da Clínica (Stripe Connect)

**Onde:** Banco de dados (`tenant_stripe_accounts`)

**Pertence a:** Clínica (tenant)

**Usada para:**
- ✅ Pagamentos que a clínica recebe dos seus clientes
- ✅ PaymentIntents criados pela clínica
- ✅ Transações dos clientes da clínica

**Como usar:**
```php
// Usa conta da clínica
$stripeService = StripeService::forTenant($tenantId);
```

## Fluxo de Registro

### 1. Clínica se Registra

```
POST /v1/auth/register
```

**O que acontece:**
1. Cria tenant no banco
2. Cria usuário admin
3. Cria sessão
4. **Retorna flag `requires_stripe_connect: true`**

### 2. Clínica DEVE Configurar Stripe Connect

**Obrigatório:** Após registro, a clínica DEVE configurar sua conta Stripe em `/stripe-connect`

**Opções:**
- **Opção A:** Informar API Key diretamente (`stripe_secret_key`)
- **Opção B:** Usar Stripe Connect Express (onboarding automático)

### 3. Bloqueio de Acesso

O `SubscriptionMiddleware` bloqueia acesso se:
- ❌ Não tiver Stripe Connect configurado
- ❌ Não tiver assinatura ativa do SaaS

**Rotas permitidas sem Stripe Connect:**
- `/login`
- `/register`
- `/stripe-connect`
- `/v1/stripe-connect/*`

## Assinaturas do SaaS

### Como Funciona

1. Clínica acessa `/my-subscription`
2. Escolhe um plano (Básico, Premium, etc.)
3. Cria checkout usando **sua conta Stripe** (`STRIPE_SECRET`)
4. Você recebe o pagamento mensal
5. Clínica tem acesso ao sistema

### Código

```php
// Em SaasController::createCheckout()
// SEMPRE usa conta padrão (sua conta)
$stripeService = new StripeService(); // Usa STRIPE_SECRET do .env
```

## Pagamentos dos Clientes da Clínica

### Como Funciona

1. Clínica cria PaymentIntent para receber pagamento de um cliente
2. Usa **conta Stripe da clínica** (Stripe Connect)
3. Clínica recebe o pagamento diretamente
4. Você pode cobrar taxa de plataforma (se ativado)

### Código

```php
// Em PaymentController::createPaymentIntent()
// SEMPRE usa conta da clínica
$stripeService = StripeService::forTenant($tenantId);
```

## Taxa de Plataforma

### Configuração

No arquivo `.env`:

```env
# Desativado por padrão
PLATFORM_FEE_ENABLED=false
PLATFORM_FEE_PERCENTAGE=2.5
```

### Como Ativar

1. Edite `.env`:
   ```env
   PLATFORM_FEE_ENABLED=true
   PLATFORM_FEE_PERCENTAGE=2.5  # 2.5% de taxa
   ```

2. Reinicie o servidor

### Como Funciona

Quando um pagamento é criado pela clínica:
1. Calcula taxa: `(valor * porcentagem) / 100`
2. Registra no log
3. **Nota:** Para aplicar a taxa, você precisa usar "destination charges" ou criar um Transfer manual

### Exemplo

```php
// Pagamento de R$ 100,00 (10000 centavos)
// Taxa de 2.5%
$fee = PlatformFeeService::calculateFee(10000); // Retorna 250 centavos (R$ 2,50)
$netAmount = PlatformFeeService::calculateNetAmount(10000); // Retorna 9750 centavos (R$ 97,50)
```

## Resumo das Contas

| Operação | Conta Usada | Quem Recebe |
|----------|-------------|-------------|
| Assinatura do SaaS | Sua conta (`STRIPE_SECRET`) | Você |
| Pagamento de cliente da clínica | Conta da clínica (Stripe Connect) | Clínica |
| Taxa de plataforma | Sua conta (se ativado) | Você |

## Verificações de Segurança

### SubscriptionMiddleware

Bloqueia acesso se:
- ❌ Não tiver Stripe Connect configurado
- ❌ Não tiver assinatura ativa

### PaymentController

Verifica antes de criar PaymentIntent:
- ✅ Tenant tem Stripe Connect configurado
- ✅ Usa conta Stripe da clínica (não sua conta)

### SaasController

Sempre usa:
- ✅ Sua conta Stripe (`STRIPE_SECRET`)
- ✅ Para assinaturas que você vende

## Exemplos de Uso

### Criar Assinatura do SaaS (Clínica paga para você)

```php
// Sempre usa sua conta
$stripeService = new StripeService();
$checkout = $stripeService->createCheckoutSession([
    'customer_id' => $customerId,
    'line_items' => [['price' => 'price_xxx', 'quantity' => 1]],
    'mode' => 'subscription'
]);
```

### Criar Pagamento (Clínica recebe de cliente)

```php
// Usa conta da clínica
$stripeService = StripeService::forTenant($tenantId);
$paymentIntent = $stripeService->createPaymentIntent([
    'amount' => 10000,
    'currency' => 'brl'
]);
```

## Troubleshooting

### Erro: "Stripe Connect não configurado"

**Causa:** Clínica não configurou Stripe Connect

**Solução:** Acesse `/stripe-connect` e configure

### Erro: "Assinatura não encontrada"

**Causa:** Clínica não tem assinatura ativa do SaaS

**Solução:** Acesse `/my-subscription` e assine um plano

### Pagamento vai para conta errada

**Causa:** Usou conta errada no código

**Solução:**
- Assinaturas SaaS: Use `new StripeService()` (sua conta)
- Pagamentos da clínica: Use `StripeService::forTenant($tenantId)` (conta da clínica)

