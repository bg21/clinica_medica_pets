# Documentação Completa: Integração Stripe com FlightPHP

Bem-vindo à documentação completa da integração Stripe no sistema. Esta documentação cobre desde conceitos básicos até implementações avançadas.

---


## 📚 Documentos Disponíveis

### 1. [Guia Completo Stripe FlightPHP](./GUIA_COMPLETO_STRIPE_FLIGHTPHP.md)
**Guia principal e abrangente** que cobre:
- ✅ Arquitetura e estrutura de diretórios
- ✅ Configuração inicial
- ✅ StripeService - Serviço principal
- ✅ Controllers e rotas
- ✅ Segurança e boas práticas
- ✅ Webhooks
- ✅ Tratamento de erros
- ✅ Testes

**Quando usar:** Comece por aqui se você é novo na integração ou precisa entender a arquitetura completa.

---

### 2. [Exemplos Práticos](./EXEMPLOS_PRATICOS_STRIPE.md)
**Exemplos de código completos e funcionais** para:
- ✅ Checkout de assinatura
- ✅ Pagamento único
- ✅ Salvar cartão
- ✅ Gerenciar assinatura
- ✅ Reembolso
- ✅ Cupons e descontos
- ✅ Trial period
- ✅ Múltiplos itens
- ✅ 3D Secure
- ✅ Webhook completo

**Quando usar:** Quando você precisa de código pronto para copiar e adaptar para seu caso de uso específico.

---

### 3. [Troubleshooting](./TROUBLESHOOTING_STRIPE.md)
**Guia de resolução de problemas** cobrindo:
- ✅ Problemas de configuração
- ✅ Problemas de autenticação
- ✅ Problemas de pagamento
- ✅ Problemas de webhook
- ✅ Problemas de multi-tenant
- ✅ Problemas de performance
- ✅ Erros comuns do Stripe

**Quando usar:** Quando você encontrar erros ou comportamentos inesperados.

---

### 4. [Arquitetura Stripe](./ARQUITETURA_STRIPE.md)
**Documentação da arquitetura multi-tenant** explicando:
- ✅ Contas Stripe (plataforma vs tenant)
- ✅ Fluxo de registro
- ✅ Assinaturas do SaaS
- ✅ Pagamentos dos clientes
- ✅ Taxa de plataforma
- ✅ Verificações de segurança

**Quando usar:** Para entender como o sistema gerencia múltiplas contas Stripe e o fluxo de pagamentos.

---

## 🚀 Início Rápido

### 1. Configuração Inicial

```bash
# 1. Instalar dependências
composer install

# 2. Configurar variáveis de ambiente
cp env.template .env
# Edite .env e adicione:
# STRIPE_SECRET=sk_test_xxx
# STRIPE_PUBLISHABLE_KEY=pk_test_xxx
# STRIPE_WEBHOOK_SECRET=whsec_xxx

# 3. Testar conexão
php -r "
require 'vendor/autoload.php';
\$stripe = new \Stripe\StripeClient(getenv('STRIPE_SECRET'));
echo 'Conexão OK: ' . \$stripe->accounts->retrieve()->id;
"
```

### 2. Criar Primeiro Pagamento

```php
// Controller
$tenantId = Flight::get('tenant_id');
$stripeService = StripeService::forTenant($tenantId);

$paymentIntent = $stripeService->createPaymentIntent([
    'amount' => 10000, // R$ 100,00
    'currency' => 'brl',
    'description' => 'Primeiro pagamento'
]);

// Retorna client_secret para frontend
ResponseHelper::sendCreated([
    'client_secret' => $paymentIntent->client_secret
]);
```

### 3. Frontend (JavaScript)

```javascript
const stripe = Stripe('pk_test_xxx');

// Confirma pagamento
const { error, paymentIntent } = await stripe.confirmCardPayment(
    clientSecret,
    {
        payment_method: {
            card: cardElement
        }
    }
);

if (error) {
    console.error('Erro:', error);
} else {
    console.log('Sucesso:', paymentIntent);
}
```

---

## 🏗️ Arquitetura do Sistema

### Estrutura de Camadas

```
┌─────────────────────────────────────┐
│         Frontend (JS/HTML)          │
└─────────────────┬───────────────────┘
                  │
┌─────────────────▼───────────────────┐
│      Controllers (FlightPHP)        │
│  - Validação                        │
│  - Respostas HTTP                   │
└─────────────────┬───────────────────┘
                  │
┌─────────────────▼───────────────────┐
│         Services (Lógica)           │
│  - StripeService                    │
│  - PaymentService                   │
└─────────────────┬───────────────────┘
                  │
┌─────────────────▼───────────────────┐
│         Models (Dados)              │
│  - Customer                         │
│  - Subscription                     │
└─────────────────┬───────────────────┘
                  │
┌─────────────────▼───────────────────┐
│         Stripe API                   │
└───────────────────────────────────────┘
```

### Fluxo de Dados

```
1. Frontend → Controller (validação)
2. Controller → Service (lógica)
3. Service → Stripe API (comunicação)
4. Stripe API → Service (resposta)
5. Service → Controller (processamento)
6. Controller → Frontend (resposta JSON)
```

---

## 🔑 Conceitos Importantes

### 1. Multi-Tenant com Stripe

O sistema suporta duas contas Stripe:

- **Conta da Plataforma** (`STRIPE_SECRET` no `.env`)
  - Usada para: Assinaturas SaaS que as clínicas pagam
  - Como usar: `new StripeService()`

- **Conta do Tenant** (Stripe Connect)
  - Usada para: Pagamentos que a clínica recebe de seus clientes
  - Como usar: `StripeService::forTenant($tenantId)`

### 2. Regra de Ouro

**Sempre use a conta correta:**
- ✅ Assinaturas SaaS → Conta da plataforma
- ✅ Pagamentos da clínica → Conta do tenant

### 3. Segurança

- ✅ **Nunca** exponha chaves secretas no frontend
- ✅ **Sempre** valide dados de entrada
- ✅ **Sempre** verifique permissões (tenant, usuário)
- ✅ **Sempre** adicione metadata para rastreabilidade
- ✅ **Sempre** use idempotency keys para operações críticas

---

## 📖 Guias por Caso de Uso

### Quero criar um checkout de assinatura

👉 [Exemplos Práticos - Checkout de Assinatura](./EXEMPLOS_PRATICOS_STRIPE.md#checkout-de-assinatura)

### Quero processar um pagamento único

👉 [Exemplos Práticos - Pagamento Único](./EXEMPLOS_PRATICOS_STRIPE.md#pagamento-único)

### Quero salvar cartão do cliente

👉 [Exemplos Práticos - Salvar Cartão](./EXEMPLOS_PRATICOS_STRIPE.md#salvar-cartão)

### Quero gerenciar assinatura (atualizar, cancelar)

👉 [Exemplos Práticos - Gerenciar Assinatura](./EXEMPLOS_PRATICOS_STRIPE.md#gerenciar-assinatura)

### Quero processar reembolso

👉 [Exemplos Práticos - Reembolso](./EXEMPLOS_PRATICOS_STRIPE.md#reembolso)

### Quero implementar webhooks

👉 [Guia Completo - Webhooks](./GUIA_COMPLETO_STRIPE_FLIGHTPHP.md#webhooks)

### Estou com erro "Stripe Connect não configurado"

👉 [Troubleshooting - Problemas de Pagamento](./TROUBLESHOOTING_STRIPE.md#erro-stripe-connect-não-configurado)

### Estou com erro "Signature inválida" no webhook

👉 [Troubleshooting - Problemas de Webhook](./TROUBLESHOOTING_STRIPE.md#erro-signature-inválida)

---

## 🛠️ Ferramentas Úteis

### Stripe CLI

Teste webhooks localmente:

```bash
stripe listen --forward-to localhost:8000/v1/webhook
stripe trigger payment_intent.succeeded
```

### Dashboard do Stripe

- **Logs de API:** Veja todas as requisições
- **Webhooks:** Configure endpoints
- **Eventos:** Veja eventos recebidos
- **Test Mode:** Teste sem cobranças reais

### Logs do Sistema

```bash
# Ver logs em tempo real
tail -f storage/logs/app.log

# Filtrar erros Stripe
tail -f storage/logs/app.log | grep -i stripe
```

---

## ✅ Checklist de Implementação

### Antes de Começar

- [ ] Configurar variáveis de ambiente
- [ ] Instalar dependências (`composer install`)
- [ ] Testar conexão com Stripe
- [ ] Configurar webhook no Dashboard

### Ao Implementar

- [ ] Escolher conta Stripe correta (plataforma vs tenant)
- [ ] Validar todos os dados de entrada
- [ ] Verificar permissões (tenant, usuário)
- [ ] Adicionar metadata para rastreabilidade
- [ ] Implementar idempotência
- [ ] Tratar todos os tipos de erro
- [ ] Adicionar logging
- [ ] Testar em modo test primeiro

### Após Implementar

- [ ] Testar fluxo completo
- [ ] Verificar logs
- [ ] Testar tratamento de erros
- [ ] Validar webhooks
- [ ] Documentar endpoints
- [ ] Revisar segurança

---

## 🔍 Busca Rápida

### Por Funcionalidade

| Funcionalidade | Documento | Seção |
|---------------|-----------|-------|
| Checkout | [Exemplos Práticos](./EXEMPLOS_PRATICOS_STRIPE.md) | Checkout de Assinatura |
| Pagamento Único | [Exemplos Práticos](./EXEMPLOS_PRATICOS_STRIPE.md) | Pagamento Único |
| Salvar Cartão | [Exemplos Práticos](./EXEMPLOS_PRATICOS_STRIPE.md) | Salvar Cartão |
| Assinatura | [Exemplos Práticos](./EXEMPLOS_PRATICOS_STRIPE.md) | Gerenciar Assinatura |
| Reembolso | [Exemplos Práticos](./EXEMPLOS_PRATICOS_STRIPE.md) | Reembolso |
| Webhook | [Guia Completo](./GUIA_COMPLETO_STRIPE_FLIGHTPHP.md) | Webhooks |
| Multi-Tenant | [Arquitetura](./ARQUITETURA_STRIPE.md) | Visão Geral |

### Por Problema

| Problema | Documento | Seção |
|----------|-----------|-------|
| Configuração | [Troubleshooting](./TROUBLESHOOTING_STRIPE.md) | Problemas de Configuração |
| Autenticação | [Troubleshooting](./TROUBLESHOOTING_STRIPE.md) | Problemas de Autenticação |
| Pagamento | [Troubleshooting](./TROUBLESHOOTING_STRIPE.md) | Problemas de Pagamento |
| Webhook | [Troubleshooting](./TROUBLESHOOTING_STRIPE.md) | Problemas de Webhook |
| Multi-Tenant | [Troubleshooting](./TROUBLESHOOTING_STRIPE.md) | Problemas de Multi-Tenant |

---

## 📞 Suporte

### Recursos

1. **Documentação Oficial Stripe**
   - [API Reference](https://stripe.com/docs/api)
   - [PHP SDK](https://github.com/stripe/stripe-php)

2. **Documentação do Projeto**
   - [Guia Completo](./GUIA_COMPLETO_STRIPE_FLIGHTPHP.md)
   - [Exemplos Práticos](./EXEMPLOS_PRATICOS_STRIPE.md)
   - [Troubleshooting](./TROUBLESHOOTING_STRIPE.md)

3. **Logs e Debug**
   - Logs do sistema: `storage/logs/app.log`
   - Dashboard Stripe: Logs de API
   - Stripe CLI: Teste local de webhooks

### Quando Pedir Ajuda

Inclua sempre:
- ✅ Descrição do problema
- ✅ Código relevante
- ✅ Logs de erro
- ✅ Passos para reproduzir
- ✅ Ambiente (test vs live)

---

## 🎯 Próximos Passos

1. **Leia o [Guia Completo](./GUIA_COMPLETO_STRIPE_FLIGHTPHP.md)** para entender a arquitetura
2. **Consulte [Exemplos Práticos](./EXEMPLOS_PRATICOS_STRIPE.md)** para código pronto
3. **Use [Troubleshooting](./TROUBLESHOOTING_STRIPE.md)** quando encontrar problemas
4. **Revise [Arquitetura](./ARQUITETURA_STRIPE.md)** para entender multi-tenant

---

**Última atualização:** Dezembro 2024

**Versão:** 1.0.0

