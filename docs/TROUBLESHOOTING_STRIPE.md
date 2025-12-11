# Troubleshooting: Integração Stripe com FlightPHP

Este guia ajuda a resolver problemas comuns na integração Stripe.

---


## 📋 Índice

1. [Problemas de Configuração](#problemas-de-configuração)
2. [Problemas de Autenticação](#problemas-de-autenticação)
3. [Problemas de Pagamento](#problemas-de-pagamento)
4. [Problemas de Webhook](#problemas-de-webhook)
5. [Problemas de Multi-Tenant](#problemas-de-multi-tenant)
6. [Problemas de Performance](#problemas-de-performance)
7. [Erros Comuns do Stripe](#erros-comuns-do-stripe)

---

## Problemas de Configuração

### Erro: "STRIPE_SECRET não configurado"

**Sintoma:**
```
RuntimeException: STRIPE_SECRET não configurado
```

**Causa:** Variável de ambiente não configurada ou vazia.

**Solução:**

1. **Verifique o arquivo `.env` na raiz do projeto:**
```env
STRIPE_SECRET=sk_test_xxx
# ou para produção:
# STRIPE_SECRET=sk_live_xxx
```

2. **Verifique se o arquivo `.env` existe:**
```bash
# Na raiz do projeto
ls -la .env
# ou no Windows
dir .env
```

3. **Verifique se o Config está sendo carregado:**
   - O `Config` é carregado automaticamente em `public/index.php` (linha 164)
   - A classe `Config` está em `config/config.php`
   - O método `Config::get()` carrega o `.env` automaticamente na primeira chamada

4. **Teste se a variável está sendo lida:**
```php
// Em qualquer controller ou service
use Config;

$secret = Config::get('STRIPE_SECRET');
if (empty($secret)) {
    Logger::error("STRIPE_SECRET não configurado");
    // Verifique se o arquivo .env existe e tem a variável
}
```

5. **Verifique o formato do arquivo `.env`:**
   - Não deve ter espaços ao redor do `=`
   - Não deve ter aspas (a menos que necessário)
   - Linhas comentadas começam com `#`
```env
# ✅ CORRETO
STRIPE_SECRET=sk_test_xxx

# ❌ ERRADO
STRIPE_SECRET = sk_test_xxx  # Espaços ao redor do =
STRIPE_SECRET="sk_test_xxx"  # Aspas desnecessárias
```

6. **Verifique se está na raiz do projeto:**
   - O arquivo `.env` deve estar na mesma pasta que `composer.json`
   - O `config/config.php` carrega de `__DIR__ . '/..'` (raiz do projeto)

---

### Erro: "Invalid API Key provided"

**Sintoma:**
```
Stripe\Exception\AuthenticationException: Invalid API Key provided
```

**Causa:** Chave API inválida, incorreta ou com formato errado.

**Solução:**

1. **Verifique se está usando a chave correta:**
   - **Test Mode:** `sk_test_xxx` (começa com `sk_test_`)
   - **Live Mode:** `sk_live_xxx` (começa com `sk_live_`)
   - **Publishable Key:** `pk_test_xxx` ou `pk_live_xxx` (não use no backend!)

2. **Verifique se não há espaços ou caracteres extras:**
```php
// O Config já faz trim automaticamente, mas você pode verificar:
$secret = Config::get('STRIPE_SECRET');
$secret = trim($secret); // Remove espaços

if (empty($secret) || !preg_match('/^sk_(test|live)_/', $secret)) {
    throw new \RuntimeException("Chave Stripe inválida");
}
```

3. **Teste a chave diretamente:**

Use o script de teste incluído no projeto:
```bash
php scripts/test_stripe_config.php
```

Este script verifica:
- ✅ Se `STRIPE_SECRET` está configurado
- ✅ Se a chave tem formato válido
- ✅ Se a chave funciona com a API Stripe
- ✅ Se `STRIPE_WEBHOOK_SECRET` está configurado (opcional)

**Ou teste manualmente:**
```php
// Script de teste rápido
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

$secret = Config::get('STRIPE_SECRET');

if (empty($secret)) {
    die("❌ STRIPE_SECRET não configurado no .env\n");
}

try {
    $stripe = new \Stripe\StripeClient($secret);
    $account = $stripe->accounts->retrieve();
    echo "✅ Chave válida! Conta: " . $account->id . "\n";
    echo "   Modo: " . (strpos($secret, 'sk_test_') === 0 ? 'TEST' : 'LIVE') . "\n";
} catch (\Stripe\Exception\AuthenticationException $e) {
    die("❌ Chave inválida: " . $e->getMessage() . "\n");
} catch (\Exception $e) {
    die("❌ Erro: " . $e->getMessage() . "\n");
}
```

4. **Verifique se não está usando Publishable Key por engano:**
   - Publishable Key (`pk_xxx`) é para frontend
   - Secret Key (`sk_xxx`) é para backend
   - Nunca use Publishable Key no `StripeService`

5. **Verifique se a chave não expirou ou foi revogada:**
   - Acesse o [Dashboard do Stripe](https://dashboard.stripe.com/apikeys)
   - Verifique se a chave está ativa
   - Gere uma nova chave se necessário

---

### Erro: "Webhook secret não configurado"

**Sintoma:**
```
RuntimeException: STRIPE_WEBHOOK_SECRET não configurado
```

**Causa:** `STRIPE_WEBHOOK_SECRET` não configurado no arquivo `.env`.

**Solução:**

1. **Configure no arquivo `.env` na raiz do projeto:**
```env
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

2. **Obtenha o secret no Dashboard do Stripe:**
   - Acesse: [Dashboard Stripe > Developers > Webhooks](https://dashboard.stripe.com/webhooks)
   - Selecione seu endpoint (ou crie um novo)
   - Clique em **Reveal** ao lado de **Signing secret**
   - Copie o secret (começa com `whsec_`)

3. **Verifique se o secret está correto:**
   - Deve começar com `whsec_`
   - Não deve ter espaços ou caracteres extras
   - Cada endpoint tem seu próprio secret único

4. **Teste se está sendo lido corretamente:**
```php
// Em qualquer controller ou service
use Config;

$webhookSecret = Config::get('STRIPE_WEBHOOK_SECRET');
if (empty($webhookSecret)) {
    Logger::error("STRIPE_WEBHOOK_SECRET não configurado");
    // Verifique se o arquivo .env existe e tem a variável
}

// Verifica formato
if (!empty($webhookSecret) && !preg_match('/^whsec_/', $webhookSecret)) {
    Logger::warning("STRIPE_WEBHOOK_SECRET pode estar incorreto (não começa com whsec_)");
}
```

5. **Importante:**
   - Cada endpoint de webhook tem seu próprio secret
   - Se você tiver múltiplos endpoints (test e live), configure ambos
   - O secret é usado para validar que o webhook realmente veio do Stripe
   - Sem o secret correto, todos os webhooks serão rejeitados

---

## Problemas de Autenticação

### Erro: "Não autenticado"

**Sintoma:**
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Não autenticado"
  }
}
```

**Causa:** Token de autenticação ausente, inválido ou expirado.

**Solução:**

1. Verifique se o token está sendo enviado:
```javascript
headers: {
    'Authorization': `Bearer ${token}`
}
```

2. Verifique se o middleware de autenticação está funcionando:
```php
// Middleware deve definir tenant_id
$tenantId = Flight::get('tenant_id');
if ($tenantId === null) {
    ResponseHelper::sendUnauthorizedError('Não autenticado');
    return;
}
```

3. Verifique logs de autenticação:
```php
Logger::info("Autenticação", [
    'has_token' => !empty($_SERVER['HTTP_AUTHORIZATION']),
    'tenant_id' => Flight::get('tenant_id')
]);
```

---

### Erro: "Forbidden - Recurso não encontrado"

**Sintoma:**
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Recurso não encontrado"
  }
}
```

**Causa:** Tentativa de acessar recurso de outro tenant (IDOR).

**Solução:**

Sempre use métodos que filtram por tenant:
```php
// ❌ ERRADO
$customer = $customerModel->findById($customerId);

// ✅ CORRETO
$customer = $customerModel->findByTenantAndId($tenantId, $customerId);
```

---

## Problemas de Pagamento

### Erro: "Stripe Connect não configurado"

**Sintoma:**
```json
{
  "success": false,
  "error": {
    "code": "STRIPE_CONNECT_REQUIRED",
    "message": "Stripe Connect não configurado"
  }
}
```

**Causa:** Tenant não configurou conta Stripe Connect.

**Solução:**

1. Verifique se o tenant tem conta Stripe:
```php
$stripeAccountModel = new \App\Models\TenantStripeAccount();
$account = $stripeAccountModel->findByTenant($tenantId);

if (!$account || empty($account['stripe_secret_key'])) {
    // Redireciona para página de configuração
    ResponseHelper::sendError(402, 'Stripe Connect não configurado');
}
```

2. Oriente o tenant a configurar:
   - Acesse `/stripe-connect`
   - Configure API Key ou faça onboarding

---

### Erro: "Your card was declined"

**Sintoma:**
```
Stripe\Exception\CardException: Your card was declined
```

**Causa:** Cartão recusado pelo banco.

**Solução:**

1. Trate o erro adequadamente:
```php
try {
    $paymentIntent = $stripeService->createPaymentIntent($data);
} catch (\Stripe\Exception\CardException $e) {
    $errorCode = $e->getDeclineCode();
    
    switch ($errorCode) {
        case 'insufficient_funds':
            $message = 'Saldo insuficiente';
            break;
        case 'expired_card':
            $message = 'Cartão expirado';
            break;
        case 'lost_card':
        case 'stolen_card':
            $message = 'Cartão inválido';
            break;
        default:
            $message = 'Cartão recusado. Tente outro método de pagamento.';
    }
    
    ResponseHelper::sendError(402, $message, $errorCode);
}
```

2. Informe o cliente sobre o erro de forma clara.

---

### Erro: "Payment intent não encontrado"

**Sintoma:**
```
Stripe\Exception\InvalidRequestException: No such payment_intent
```

**Causa:** ID do payment intent inválido ou de outra conta.

**Solução:**

1. Verifique se o ID está correto:
```php
if (!preg_match('/^pi_[a-zA-Z0-9]+$/', $paymentIntentId)) {
    ResponseHelper::sendValidationError('ID inválido');
    return;
}
```

2. Verifique se pertence ao tenant:
```php
$paymentIntent = $stripeService->getClient()->paymentIntents->retrieve($paymentIntentId);

if (isset($paymentIntent->metadata->tenant_id) && 
    (int)$paymentIntent->metadata->tenant_id !== $tenantId) {
    ResponseHelper::sendForbiddenError('Payment intent não pertence ao tenant');
    return;
}
```

---

## Problemas de Webhook

### Erro: "Signature inválida"

**Sintoma:**
```
Stripe\Exception\SignatureVerificationException: Signature inválida
```

**Causa:** Webhook secret incorreto ou payload modificado.

**Solução:**

1. Verifique o webhook secret:
```php
$webhookSecret = Config::get('STRIPE_WEBHOOK_SECRET');
if (empty($webhookSecret)) {
    Logger::error("Webhook secret não configurado");
}
```

2. Verifique se o payload está sendo lido corretamente:
```php
$payload = @file_get_contents('php://input');
if (empty($payload)) {
    Logger::error("Payload vazio");
}
```

3. Teste com Stripe CLI:
```bash
stripe listen --forward-to localhost:8000/v1/webhook
```

---

### Erro: "Webhook já processado"

**Sintoma:**
```json
{
  "success": true,
  "data": {
    "already_processed": true
  }
}
```

**Causa:** Webhook foi processado anteriormente (idempotência).

**Solução:**

Isso é **normal** e **esperado**. O Stripe pode reenviar webhooks, e o sistema deve ser idempotente:

```php
$eventModel = new \App\Models\StripeEvent();
if ($eventModel->isProcessed($event->id)) {
    // Retorna sucesso para evitar reenvio
    ResponseHelper::sendSuccess(['already_processed' => true]);
    return;
}
```

**Não é um erro!** O sistema está funcionando corretamente.

---

### Webhook não está sendo recebido

**Sintoma:** Webhooks não chegam ao servidor.

**Solução:**

1. Verifique se o endpoint está acessível:
```bash
curl -X POST https://seu-dominio.com/v1/webhook
```

2. Verifique logs:
```php
Logger::info("Webhook recebido", [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI']
]);
```

3. Configure no Dashboard do Stripe:
   - **Developers > Webhooks**
   - Adicione endpoint: `https://seu-dominio.com/v1/webhook`
   - Selecione eventos

4. Teste com Stripe CLI:
```bash
stripe trigger payment_intent.succeeded
```

---

## Problemas de Multi-Tenant

### Erro: "Usando conta Stripe errada"

**Sintoma:** Pagamentos vão para conta errada.

**Causa:** Uso incorreto de `StripeService`.

**Solução:**

**Regra de Ouro:**
- **Assinaturas SaaS** → `new StripeService()` (conta padrão)
- **Pagamentos da clínica** → `StripeService::forTenant($tenantId)` (conta do tenant)

```php
// ✅ CORRETO - Assinatura SaaS
$stripeService = new StripeService(); // Plataforma recebe

// ✅ CORRETO - Pagamento da clínica
$stripeService = StripeService::forTenant($tenantId); // Clínica recebe

// ❌ ERRADO - Nunca misture
$stripeService = new StripeService(); // Para pagamento da clínica
```

---

### Erro: "Tenant não tem chave Stripe"

**Sintoma:**
```
RuntimeException: Tenant não tem chave Stripe configurada
```

**Causa:** Tenant não configurou `stripe_secret_key`.

**Solução:**

1. Verifique no banco:
```sql
SELECT * FROM tenant_stripe_accounts WHERE tenant_id = ?;
```

2. Oriente o tenant a configurar:
```php
if (!$stripeAccount || empty($stripeAccount['stripe_secret_key'])) {
    ResponseHelper::sendError(
        402,
        'Stripe Connect não configurado',
        'Configure em Configurações > Conectar Stripe'
    );
    return;
}
```

---

## Problemas de Performance

### Erro: "Timeout ao conectar com Stripe"

**Sintoma:**
```
cURL error 28: Operation timed out
```

**Causa:** Timeout muito baixo ou problemas de rede.

**Solução:**

1. Aumente timeout no `StripeService`:
```php
$clientOptions = [
    'timeout' => 30, // 30 segundos
    'connect_timeout' => 10
];

$this->client = new StripeClient($secretKey, $clientOptions);
```

2. Verifique conectividade:
```bash
curl -I https://api.stripe.com
```

3. Implemente retry com backoff:
```php
$maxRetries = 3;
$retry = 0;

while ($retry < $maxRetries) {
    try {
        $result = $stripeService->createPaymentIntent($data);
        break;
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        $retry++;
        if ($retry >= $maxRetries) {
            throw $e;
        }
        sleep(pow(2, $retry)); // Exponential backoff
    }
}
```

---

### Erro: "Rate limit excedido"

**Sintoma:**
```
Stripe\Exception\RateLimitException: Too many requests
```

**Causa:** Muitas requisições em pouco tempo.

**Solução:**

1. Implemente rate limiting:
```php
use App\Services\RateLimiterService;

$rateLimiter = new RateLimiterService();
if (!$rateLimiter->check($endpoint, ['limit' => 100, 'window' => 60])) {
    ResponseHelper::sendError(429, 'Muitas requisições');
    return;
}
```

2. Use idempotency keys:
```php
$idempotencyKey = $this->generateIdempotencyKey('payment', $data);
$paymentIntent = $stripeService->createPaymentIntent($data, $idempotencyKey);
```

3. Implemente cache quando apropriado:
```php
// Cache de produtos/preços (não mudam frequentemente)
$cacheKey = "stripe_price_{$priceId}";
$price = Cache::get($cacheKey);
if (!$price) {
    $price = $stripeService->getClient()->prices->retrieve($priceId);
    Cache::set($cacheKey, $price, 3600); // 1 hora
}
```

---

## Erros Comuns do Stripe

### Erro: "No such customer"

**Causa:** Customer ID inválido ou de outra conta.

**Solução:**

```php
try {
    $customer = $stripeService->getClient()->customers->retrieve($customerId);
} catch (\Stripe\Exception\InvalidRequestException $e) {
    if ($e->getStripeCode() === 'resource_missing') {
        // Cria novo customer
        $customer = $stripeService->createCustomer([
            'email' => $email,
            'name' => $name
        ]);
    } else {
        throw $e;
    }
}
```

---

### Erro: "Invalid price"

**Causa:** Price ID inválido ou de outra conta.

**Solução:**

```php
// Valida formato
if (!preg_match('/^price_[a-zA-Z0-9]+$/', $priceId)) {
    ResponseHelper::sendValidationError('Price ID inválido');
    return;
}

// Verifica se existe
try {
    $price = $stripeService->getClient()->prices->retrieve($priceId);
} catch (\Stripe\Exception\InvalidRequestException $e) {
    ResponseHelper::sendNotFoundError('Preço não encontrado');
    return;
}
```

---

### Erro: "Subscription already exists"

**Causa:** Tentativa de criar assinatura duplicada.

**Solução:**

```php
// Verifica se já existe assinatura ativa
$subscriptionModel = new \App\Models\Subscription();
$existing = $subscriptionModel->findActiveByTenant($tenantId);

if ($existing) {
    ResponseHelper::sendError(
        409,
        'Assinatura já existe',
        'Você já possui uma assinatura ativa'
    );
    return;
}
```

---

## Checklist de Debug

Ao encontrar um problema, siga este checklist:

### 1. Verificar Logs

```php
// Procure por erros recentes
tail -f storage/logs/app.log | grep -i error
```

### 2. Verificar Configuração

- [ ] Variáveis de ambiente configuradas
- [ ] Chaves Stripe corretas (test vs live)
- [ ] Webhook secret configurado
- [ ] Endpoint de webhook acessível
- [ ] Execute o script de teste: `php scripts/test_stripe_config.php`

### 3. Verificar Autenticação

- [ ] Token sendo enviado
- [ ] Token válido
- [ ] Tenant ID definido no Flight

### 4. Verificar Conta Stripe

- [ ] Conta correta sendo usada (plataforma vs tenant)
- [ ] Tenant tem Stripe Connect configurado (se necessário)
- [ ] Chave Stripe válida e ativa

### 5. Verificar Dados

- [ ] Dados de entrada válidos
- [ ] IDs no formato correto (ex: `pi_xxx`, `cus_xxx`)
- [ ] Valores numéricos corretos (centavos)

### 6. Testar em Modo Test

Sempre teste primeiro em modo test:

```env
STRIPE_SECRET=sk_test_xxx
```

### 7. Verificar Webhooks

- [ ] Webhook configurado no Dashboard
- [ ] Endpoint acessível
- [ ] Signature sendo validada
- [ ] Eventos sendo processados

---

## Ferramentas Úteis

### Script de Teste de Configuração

O projeto inclui um script para testar a configuração do Stripe:

```bash
php scripts/test_stripe_config.php
```

Este script verifica:
- ✅ Se `STRIPE_SECRET` está configurado e válido
- ✅ Se a chave funciona com a API Stripe
- ✅ Se `STRIPE_WEBHOOK_SECRET` está configurado (opcional)
- ✅ Conectividade com a API Stripe

**Use este script sempre que:**
- Configurar o sistema pela primeira vez
- Trocar chaves Stripe
- Verificar se a configuração está correta
- Depurar problemas de autenticação

### Stripe CLI

Instale e use para testar webhooks localmente:

```bash
# Instalar
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Escutar webhooks
stripe listen --forward-to localhost:8000/v1/webhook

# Disparar evento de teste
stripe trigger payment_intent.succeeded
```

### Dashboard do Stripe

- **Logs de API:** Veja todas as requisições
- **Webhooks:** Configure e teste endpoints
- **Eventos:** Veja eventos recebidos
- **Test Mode:** Teste sem cobranças reais

### Logs do Sistema

```php
// Adicione logs detalhados
Logger::info("Operação Stripe", [
    'action' => 'create_payment_intent',
    'tenant_id' => $tenantId,
    'amount' => $amount,
    'customer_id' => $customerId
]);
```

---

## Suporte Adicional

Se o problema persistir:

1. **Consulte a documentação oficial:**
   - [Stripe API Reference](https://stripe.com/docs/api)
   - [Stripe PHP SDK](https://github.com/stripe/stripe-php)

2. **Verifique logs detalhados:**
   - Logs do sistema
   - Logs do Stripe Dashboard
   - Logs de erro do PHP

3. **Teste isoladamente:**
   - Crie script de teste simples
   - Teste diretamente com Stripe SDK
   - Verifique se o problema é no código ou na API

4. **Contate suporte:**
   - [Stripe Support](https://support.stripe.com/)
   - Inclua logs e detalhes do erro

---

**Última atualização:** Dezembro 2024

