# Implementação de Idempotência em Operações Críticas

## ✅ Implementado

Implementação completa de idempotência em operações críticas do Stripe para evitar duplicação em caso de retry ou falhas de rede.

## Métodos Modificados

### 1. `createPaymentIntent()`

**Arquivo:** `App/Services/StripeService.php`

**Mudanças:**
- Adicionado parâmetro opcional `?string $idempotencyKey = null`
- Gera automaticamente chave de idempotência se não fornecida
- Suporta `idempotency_key` no array `$data`
- Registra idempotency key nos logs

**Uso:**
```php
// Geração automática
$paymentIntent = $stripeService->createPaymentIntent([
    'amount' => 10000,
    'currency' => 'brl',
    'customer_id' => 'cus_xxx'
]);

// Com chave explícita
$paymentIntent = $stripeService->createPaymentIntent([
    'amount' => 10000,
    'currency' => 'brl'
], 'minha_chave_custom');

// Via array
$paymentIntent = $stripeService->createPaymentIntent([
    'amount' => 10000,
    'currency' => 'brl',
    'idempotency_key' => 'minha_chave_custom'
]);
```

### 2. `createSubscription()`

**Arquivo:** `App/Services/StripeService.php`

**Mudanças:**
- Adicionado parâmetro opcional `?string $idempotencyKey = null`
- Gera automaticamente chave de idempotência se não fornecida
- Suporta `idempotency_key` no array `$data`
- Registra idempotency key nos logs

**Uso:**
```php
// Geração automática
$subscription = $stripeService->createSubscription([
    'customer_id' => 'cus_xxx',
    'price_id' => 'price_xxx'
]);

// Com chave explícita
$subscription = $stripeService->createSubscription([
    'customer_id' => 'cus_xxx',
    'price_id' => 'price_xxx'
], 'minha_chave_custom');
```

### 3. `createCheckoutSession()`

**Arquivo:** `App/Services/StripeService.php`

**Mudanças:**
- Adicionado parâmetro opcional `?string $idempotencyKey = null`
- Gera automaticamente chave de idempotência se não fornecida
- Suporta `idempotency_key` no array `$data`
- Registra idempotency key nos logs

**Uso:**
```php
// Geração automática
$session = $stripeService->createCheckoutSession([
    'line_items' => [['price' => 'price_xxx', 'quantity' => 1]],
    'mode' => 'subscription',
    'success_url' => 'https://...',
    'cancel_url' => 'https://...'
]);

// Com chave explícita
$session = $stripeService->createCheckoutSession([
    'line_items' => [['price' => 'price_xxx', 'quantity' => 1]],
    'mode' => 'subscription',
    'success_url' => 'https://...',
    'cancel_url' => 'https://...'
], 'minha_chave_custom');
```

### 4. `createCustomer()`

**Arquivo:** `App/Services/StripeService.php`

**Mudanças:**
- Adicionado parâmetro opcional `?string $idempotencyKey = null`
- Gera automaticamente chave de idempotência se não fornecida
- Suporta `idempotency_key` no array `$data`
- Registra idempotency key nos logs

**Uso:**
```php
// Geração automática
$customer = $stripeService->createCustomer([
    'email' => 'cliente@example.com',
    'name' => 'Nome do Cliente'
]);

// Com chave explícita
$customer = $stripeService->createCustomer([
    'email' => 'cliente@example.com',
    'name' => 'Nome do Cliente'
], 'minha_chave_custom');
```

## Método Auxiliar: `generateIdempotencyKey()`

**Arquivo:** `App/Services/StripeService.php`

**Descrição:**
Método privado que gera chaves de idempotência únicas baseadas nos dados da operação.

**Características:**
- Gera hash SHA256 dos dados relevantes
- Inclui tipo de operação, dados principais e timestamp
- Prefixo baseado no tipo de operação:
  - `pi_` para Payment Intent
  - `sub_` para Subscription
  - `cs_` para Checkout Session
  - `cus_` para Customer
  - `op_` para outros tipos
- Limita a 200 caracteres (máximo 255 do Stripe)
- Remove valores null para garantir consistência

**Exemplo de chave gerada:**
```
pi_a1b2c3d4e5f6... (200 caracteres)
```

## Benefícios

1. **Prevenção de Duplicação:**
   - Retries automáticos não criam recursos duplicados
   - Falhas de rede não resultam em cobranças duplicadas

2. **Transparência:**
   - Todas as chaves são registradas nos logs
   - Facilita debugging e auditoria

3. **Flexibilidade:**
   - Geração automática para uso simples
   - Suporte a chaves customizadas para casos especiais

4. **Compatibilidade:**
   - Código existente continua funcionando (parâmetro opcional)
   - Não quebra implementações atuais

## Compatibilidade com Código Existente

✅ **100% Compatível:** Todos os métodos mantêm compatibilidade retroativa. O parâmetro `idempotencyKey` é opcional, então código existente continua funcionando sem modificações.

## Logs

Todas as operações com idempotência registram:
- `idempotency_key`: Chave usada na operação
- `operation_type`: Tipo da operação (para geração de chave)
- `key_length`: Tamanho da chave gerada

## Exemplo de Log

```json
{
  "message": "Payment Intent criado",
  "payment_intent_id": "pi_xxx",
  "amount": 10000,
  "currency": "brl",
  "status": "requires_payment_method",
  "idempotency_key": "pi_a1b2c3d4e5f6..."
}
```

## Próximos Passos (Opcional)

Operações que podem se beneficiar de idempotência no futuro:
- `createRefund()` - Reembolsos
- `createTransfer()` - Transferências
- `createInvoice()` - Faturas
- `createInvoiceItem()` - Itens de fatura

## Referências

- [Stripe Idempotency Keys Documentation](https://stripe.com/docs/api/idempotent_requests)
- [ANALISE_STRIPE_FLIGHT_IMPLEMENTACOES.md - Seção 4](docs/ANALISE_STRIPE_FLIGHT_IMPLEMENTACOES.md#4-idempotência-em-operações-críticas)

