# Guia de Uso: Stripe Connect com Múltiplas Contas

## Visão Geral

O `StripeService` agora suporta múltiplas contas Stripe:

1. **Conta Padrão**: Usa `STRIPE_SECRET` do `.env` (comportamento padrão)
2. **Conta por Tenant**: Cada tenant pode ter sua própria chave secreta
3. **Conta Connect**: Operações em nome de uma conta Connect específica

## Métodos Disponíveis

### 1. Uso Padrão (Compatibilidade)

```php
// Continua funcionando como antes
$stripeService = new StripeService();
// ou via container
$stripeService = $container->make(StripeService::class);
```

### 2. Para Tenant com Chave Própria

```php
// Busca chave do tenant no banco e cria instância
$stripeService = StripeService::forTenant($tenantId);

// Exemplo: Criar customer usando chave do tenant
$customer = $stripeService->createCustomer([
    'email' => 'cliente@example.com',
    'name' => 'Cliente Exemplo'
]);
```

**Quando usar:**
- Tenant configurou sua própria chave Stripe via `POST /v1/stripe-connect/api-key`
- Operações devem usar a conta do tenant, não da plataforma

### 3. Para Conta Connect (Stripe Connect)

```php
// Operações em nome de uma conta Connect específica
$stripeAccountId = 'acct_1234567890';
$stripeService = StripeService::forConnectAccount($stripeAccountId);

// Exemplo: Criar transferência para a conta Connect
$transfer = $stripeService->getClient()->transfers->create([
    'amount' => 1000,
    'currency' => 'brl',
    'destination' => $stripeAccountId
]);
```

**Quando usar:**
- Operações da plataforma em nome de uma conta Connect
- Transferências, verificações de saldo, etc.

## Exemplos Práticos

### Exemplo 1: Checkout usando chave do tenant

```php
// App/Controllers/CheckoutController.php

public function create(): void
{
    $tenantId = Flight::get('tenant_id');
    
    // Usa chave do tenant se disponível, senão usa padrão
    $stripeService = StripeService::forTenant($tenantId);
    
    $session = $stripeService->createCheckoutSession([
        'line_items' => $data['line_items'],
        'mode' => 'payment',
        'success_url' => $data['success_url'],
        'cancel_url' => $data['cancel_url']
    ]);
    
    // ...
}
```

### Exemplo 2: Verificar saldo de conta Connect

```php
// App/Services/StripeConnectService.php

public function getBalance(int $tenantId): array
{
    $account = $this->accountModel->findByTenant($tenantId);
    
    if (!$account || !$account['stripe_account_id']) {
        throw new \RuntimeException("Conta Connect não encontrada");
    }
    
    // Usa conta Connect
    $stripeService = StripeService::forConnectAccount($account['stripe_account_id']);
    $balance = $stripeService->getClient()->balance->retrieve();
    
    return [
        'available' => $balance->available[0]->amount ?? 0,
        'pending' => $balance->pending[0]->amount ?? 0,
        'currency' => $balance->available[0]->currency ?? 'brl'
    ];
}
```

### Exemplo 3: Detectar qual método usar

```php
// Verifica se tenant tem chave própria
$accountModel = new \App\Models\TenantStripeAccount();
$hasOwnKey = $accountModel->hasStripeSecretKey($tenantId);

if ($hasOwnKey) {
    // Usa chave do tenant
    $stripeService = StripeService::forTenant($tenantId);
} else {
    // Usa chave padrão da plataforma
    $stripeService = new StripeService();
}
```

## Verificações Úteis

```php
// Verifica se está usando conta Connect
if ($stripeService->isUsingConnectAccount()) {
    $accountId = $stripeService->getStripeAccountId();
    // ...
}
```

## Migração de Código Existente

### Antes

```php
$stripeService = new StripeService();
$customer = $stripeService->createCustomer($data);
```

### Depois (se precisar usar chave do tenant)

```php
$tenantId = Flight::get('tenant_id');
$stripeService = StripeService::forTenant($tenantId);
$customer = $stripeService->createCustomer($data);
```

**Nota:** O código antigo continua funcionando! Apenas use os novos métodos quando precisar de funcionalidade específica.

## Segurança

- Chaves secretas são sempre criptografadas no banco de dados
- Descriptografia acontece apenas na criação da instância
- Chaves nunca são expostas em logs ou respostas

## Troubleshooting

### Erro: "STRIPE_SECRET não configurado"
- Verifique se `STRIPE_SECRET` está no `.env`
- Para tenants, verifique se a chave foi salva via `POST /v1/stripe-connect/api-key`

### Erro: "Erro ao descriptografar chave do tenant"
- Verifique se `ENCRYPTION_KEY` está configurado no `.env`
- A chave de criptografia deve ser a mesma usada para criptografar

### Operações não funcionam com conta Connect
- Verifique se o `stripe_account_id` está correto
- Verifique se a conta Connect está ativa e tem as capabilities necessárias

