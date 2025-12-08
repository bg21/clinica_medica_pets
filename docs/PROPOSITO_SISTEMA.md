# 🎯 Propósito e Funcionalidades do Sistema

**Data:** 2025-01-21  
**Versão:** 1.0.5  
**Tipo:** Sistema Base SaaS Multi-Tenant para Pagamentos

---

## 📋 O QUE É ESTE SISTEMA?

Este é um **sistema base SaaS multi-tenant** completo para gerenciar **pagamentos, assinaturas e clientes** via **Stripe**. É uma **base reutilizável** que pode ser integrada em qualquer SaaS, fornecendo um núcleo robusto de funcionalidades de pagamento.

---

## 🎯 PROPÓSITO PRINCIPAL

Fornecer um **backend completo de pagamentos e assinaturas** que pode ser integrado em qualquer SaaS, sem precisar construir do zero.

### Problema que Resolve

- ❌ **Evita construir integração com Stripe do zero** - Tudo já está implementado
- ❌ **Evita implementar multi-tenancy manualmente** - Sistema completo de isolamento
- ❌ **Evita criar sistema de permissões e autenticação** - RBAC completo
- ❌ **Evita gerenciar webhooks, rate limiting e auditoria** - Tudo já está pronto

---

## 🚀 FUNCIONALIDADES PRINCIPAIS

### 1. Multi-Tenancy (SaaS)

- ✅ Cada cliente (tenant) possui sua própria **API Key**
- ✅ **Isolamento completo de dados** por tenant
- ✅ Cada tenant pode ter múltiplos usuários
- ✅ Sistema de slugs para identificação única

**Exemplo:**
```
Tenant 1: "Empresa ABC" → API Key: abc123...
Tenant 2: "Empresa XYZ" → API Key: xyz789...
```

### 2. Integração Completa com Stripe

- ✅ **60+ endpoints** da API Stripe implementados
- ✅ Gerenciamento completo de:
  - **Clientes (Customers)** - Criar, listar, atualizar clientes
  - **Assinaturas (Subscriptions)** - Criar, cancelar, reativar assinaturas
  - **Faturas (Invoices)** - Visualizar e gerenciar faturas
  - **Pagamentos (Charges, Payment Intents)** - Processar pagamentos
  - **Produtos e Preços** - Gerenciar catálogo
  - **Cupons e Códigos Promocionais** - Aplicar descontos
  - **Reembolsos (Refunds)** - Processar reembolsos
  - **Disputas (Chargebacks)** - Gerenciar disputas
  - **Webhooks Seguros** - Receber eventos do Stripe

### 3. Sistema de Usuários e Permissões (RBAC)

- ✅ **Roles:** Admin, Editor, Viewer
- ✅ **Permissões granulares** por funcionalidade
- ✅ Autenticação via **Session ID**
- ✅ Controle de acesso por endpoint
- ✅ Sistema de permissões customizadas por usuário

**Hierarquia de Permissões:**
```
Admin → Todas as permissões
Editor → Criar, editar, visualizar
Viewer → Apenas visualizar
```

### 4. Segurança e Performance

- ✅ **Rate Limiting** (Redis + MySQL fallback)
- ✅ **Logs de Auditoria** completos
- ✅ **Validação robusta** de inputs
- ✅ **Prepared statements** (proteção SQL Injection)
- ✅ **Webhooks seguros** com validação de assinatura
- ✅ **Idempotência** em eventos de webhook
- ✅ **CORS configurado** para frontend separado

### 5. Operações Administrativas

- ✅ **Backup automático** do banco de dados
- ✅ **Health checks** (DB, Redis, Stripe)
- ✅ **Relatórios e estatísticas**
- ✅ **Métricas de performance**
- ✅ **Detecção de anomalias**
- ✅ **Sistema de cache** (Redis com fallback)

---

## 🏗️ ARQUITETURA DO SISTEMA

```
┌─────────────────────────────────────────┐
│     Frontend (Views ou SPA externo)      │
│  (Dashboard, Formulários, Relatórios)   │
└──────────────────┬────────────────────────┘
                   │
                   │ HTTP/REST API
                   │
┌──────────────────▼────────────────────────┐
│         FlightPHP (Router)                │
│  ┌────────────────────────────────────┐  │
│  │      Middleware Stack               │  │
│  │  • Autenticação (API Key/Session) │  │
│  │  • Rate Limiting                   │  │
│  │  • Permissões (RBAC)               │  │
│  │  • Audit Logging                   │  │
│  └────────────────────────────────────┘  │
│  ┌────────────────────────────────────┐  │
│  │      Controllers (26 controllers)  │  │
│  │  • CustomerController              │  │
│  │  • SubscriptionController           │  │
│  │  • InvoiceController                │  │
│  │  • PaymentController                │  │
│  │  • ... (e mais 21 controllers)     │  │
│  └────────────────────────────────────┘  │
└──────────────────┬────────────────────────┘
                   │
    ┌──────────────┼──────────────┐
    │              │              │
┌───▼───┐    ┌─────▼─────┐   ┌───▼────┐
│Services│    │  Models   │   │  Utils  │
│        │    │(ActiveRecord)│   │         │
│Stripe  │    │Customer     │   │Database │
│Payment │    │Subscription │   │Validator│
│Cache   │    │Tenant       │   │Logger   │
│Logger  │    │User         │   │Security │
└───┬───┘    └─────┬─────┘   └───┬────┘
    │              │              │
    └──────────────┼──────────────┘
                   │
    ┌──────────────┼──────────────┐
    │              │              │
┌───▼───┐    ┌─────▼─────┐   ┌───▼────┐
│ MySQL │    │   Redis   │   │ Stripe │
│       │    │  (Cache)  │   │  API   │
└───────┘    └───────────┘   └────────┘
```

---

## 💼 CASOS DE USO

### 1. SaaS de Gestão

**Exemplo:** Sistema de gestão para empresas

- ✅ Gerenciar assinaturas mensais/anuais
- ✅ Cobrar clientes automaticamente
- ✅ Emitir faturas
- ✅ Gerenciar upgrades/downgrades de planos

### 2. Marketplace

**Exemplo:** Plataforma de marketplace

- ✅ Processar pagamentos de múltiplos vendedores
- ✅ Dividir receitas automaticamente
- ✅ Gerenciar comissões

### 3. Plataforma de Serviços

**Exemplo:** Plataforma de serviços online

- ✅ Aceitar pagamentos recorrentes
- ✅ Gerenciar trial periods
- ✅ Aplicar descontos e promoções

### 4. E-commerce

**Exemplo:** Loja online

- ✅ Processar pagamentos únicos
- ✅ Gerenciar métodos de pagamento
- ✅ Processar reembolsos

---

## 📁 ESTRUTURA DE PASTAS (App/)

```
App/
├── Controllers/     # 26 controllers REST
│   ├── CustomerController.php
│   ├── SubscriptionController.php
│   ├── InvoiceController.php
│   ├── PaymentController.php
│   ├── CheckoutController.php
│   ├── BillingPortalController.php
│   ├── ProductController.php
│   ├── PriceController.php
│   ├── CouponController.php
│   ├── PromotionCodeController.php
│   ├── TaxRateController.php
│   ├── ChargeController.php
│   ├── RefundController.php
│   ├── DisputeController.php
│   ├── PayoutController.php
│   ├── BalanceTransactionController.php
│   ├── InvoiceItemController.php
│   ├── SubscriptionItemController.php
│   ├── SetupIntentController.php
│   ├── WebhookController.php
│   ├── AuthController.php
│   ├── UserController.php
│   ├── PermissionController.php
│   ├── AuditLogController.php
│   ├── ReportController.php
│   ├── StatsController.php
│   ├── PerformanceController.php
│   ├── TraceController.php
│   ├── HealthCheckController.php
│   ├── PlanLimitsController.php
│   └── SwaggerController.php
│
├── Models/          # ActiveRecord (PDO)
│   ├── BaseModel.php
│   ├── Customer.php
│   ├── Subscription.php
│   ├── SubscriptionHistory.php
│   ├── Tenant.php
│   ├── User.php
│   ├── UserPermission.php
│   ├── UserSession.php
│   ├── AuditLog.php
│   ├── StripeEvent.php
│   ├── PerformanceMetric.php
│   ├── ApplicationLog.php
│   ├── BackupLog.php
│   ├── TenantRateLimit.php
│   └── QueryBuilder.php
│
├── Services/        # Lógica de negócio
│   ├── StripeService.php      # Wrapper Stripe API
│   ├── PaymentService.php     # Lógica de pagamentos
│   ├── UserService.php        # Lógica de usuários
│   ├── RateLimiterService.php # Rate limiting
│   ├── TenantRateLimitService.php
│   ├── PlanLimitsService.php
│   ├── CacheService.php
│   ├── EmailService.php
│   ├── Logger.php
│   ├── BackupService.php
│   ├── ReportService.php
│   ├── PerformanceAlertService.php
│   └── AnomalyDetectionService.php
│
├── Repositories/   # Abstração de dados
│   └── (camada opcional para complexidade futura)
│
├── Middleware/      # Interceptadores
│   ├── AuthMiddleware.php
│   ├── RateLimitMiddleware.php
│   ├── PermissionMiddleware.php
│   ├── AuditMiddleware.php
│   └── CorsMiddleware.php
│
├── Views/           # Interface web (30+ views)
│   ├── dashboard.php
│   ├── customers.php
│   ├── customer-details.php
│   ├── customer-invoices.php
│   ├── subscriptions.php
│   ├── subscription-details.php
│   ├── subscription-history.php
│   ├── invoices.php
│   ├── invoice-details.php
│   ├── invoice-items.php
│   ├── charges.php
│   ├── refunds.php
│   ├── disputes.php
│   ├── transactions.php
│   ├── transaction-details.php
│   ├── payouts.php
│   ├── products.php
│   ├── product-details.php
│   ├── prices.php
│   ├── price-details.php
│   ├── coupons.php
│   ├── coupon-details.php
│   ├── promotion-codes.php
│   ├── tax-rates.php
│   ├── payment-methods.php
│   ├── billing-portal.php
│   ├── checkout.php
│   ├── success.php
│   ├── cancel.php
│   ├── users.php
│   ├── user-details.php
│   ├── permissions.php
│   ├── audit-logs.php
│   ├── reports.php
│   ├── performance-metrics.php
│   ├── traces.php
│   ├── settings.php
│   ├── login.php
│   ├── register.php
│   └── layouts/
│       └── base.php
│
├── Utils/           # Utilitários
│   ├── Database.php
│   ├── Validator.php
│   ├── Sanitizer.php
│   ├── Logger.php
│   ├── SlugHelper.php
│   ├── ResponseHelper.php
│   └── PermissionHelper.php
│
├── Core/            # Núcleo do sistema
│   ├── Container.php          # DI Container
│   ├── ContainerBindings.php  # Bindings de dependências
│   ├── EventDispatcher.php    # Sistema de eventos
│   ├── EventListeners.php     # Listeners de eventos
│   └── Config.php             # Gerenciamento de configuração
│
├── DTOs/            # Data Transfer Objects
│   └── (DTOs para validação de dados)
│
├── Traits/          # Traits reutilizáveis
│   └── (traits para funcionalidades compartilhadas)
│
├── Templates/       # Templates de email
│   └── (templates HTML para emails)
│
└── Handlers/        # Handlers de eventos
    └── (handlers para processar eventos)
```

---

## 🔌 ENDPOINTS PRINCIPAIS

### Autenticação
- `POST /v1/auth/login` - Login de usuário
- `POST /v1/auth/logout` - Logout
- `GET /v1/auth/me` - Informações do usuário logado

### Clientes
- `POST /v1/customers` - Criar cliente
- `GET /v1/customers` - Listar clientes
- `GET /v1/customers/:id` - Obter cliente
- `PUT /v1/customers/:id` - Atualizar cliente

### Assinaturas
- `POST /v1/subscriptions` - Criar assinatura
- `GET /v1/subscriptions` - Listar assinaturas
- `GET /v1/subscriptions/:id` - Obter assinatura
- `PUT /v1/subscriptions/:id` - Atualizar assinatura
- `DELETE /v1/subscriptions/:id` - Cancelar assinatura
- `POST /v1/subscriptions/:id/reactivate` - Reativar assinatura

### Checkout
- `POST /v1/checkout` - Criar sessão de checkout
- `GET /v1/checkout/:id` - Obter sessão de checkout

### Faturas
- `GET /v1/invoices/:id` - Obter fatura
- `GET /v1/customers/:id/invoices` - Listar faturas do cliente

### Produtos e Preços
- `POST /v1/products` - Criar produto
- `GET /v1/products/:id` - Obter produto
- `POST /v1/prices` - Criar preço
- `GET /v1/prices` - Listar preços

### Webhooks
- `POST /v1/webhook` - Receber webhooks do Stripe

**Eventos tratados:**
- `checkout.session.completed`
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `invoice.paid`
- `invoice.payment_failed`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- E mais 10+ eventos

### Documentação
- `GET /api-docs` - Especificação OpenAPI (JSON)
- `GET /api-docs/ui` - Interface Swagger UI

---

## 🎯 RESUMO

Este sistema é uma **base de pagamentos SaaS pronta para uso**, que oferece:

✅ **Multi-tenancy completo** - Isolamento total de dados  
✅ **Integração completa com Stripe** - 60+ endpoints  
✅ **Sistema de usuários e permissões** - RBAC completo  
✅ **Segurança e auditoria** - Rate limiting, logs, validações  
✅ **Interface web funcional** - Dashboard completo  
✅ **API REST documentada** - Swagger/OpenAPI  

### Você pode:

1. **Usar como está** - Para gerenciar pagamentos do seu SaaS
   - Acesse o dashboard web em `http://localhost:8080`
   - Use a interface completa para gerenciar clientes, assinaturas e pagamentos
   - Ideal para quem quer uma solução completa pronta

2. **Integrar em outro SaaS** - Via API REST
   - Este sistema roda como um **serviço separado** (microserviço)
   - Seu SaaS principal faz chamadas HTTP para este sistema
   - Você não precisa reescrever código de pagamentos
   - **Exemplo:** Seu SaaS de gestão chama este sistema para processar pagamentos
   
   **Cenário prático:**
   ```
   Seu SaaS (gestão de clientes)  →  HTTP API  →  Este Sistema (pagamentos)
   ```
   
   **Como funciona:**
   - Você cria um tenant neste sistema e obtém uma API Key
   - No seu SaaS, você faz requisições HTTP para este sistema
   - Use o SDK PHP (`sdk/PaymentsClient.php`) ou faça requisições diretas
   - Este sistema processa tudo e retorna os resultados
   
   **Exemplo de código no seu SaaS:**
   ```php
   // No seu SaaS (outro sistema)
   $payments = new PaymentsClient(
       'https://pagamentos.seudominio.com',
       'sua_api_key_aqui'
   );
   
   // Criar cliente no sistema de pagamentos
   $customer = $payments->createCustomer('email@example.com', 'Nome');
   
   // Criar checkout
   $checkout = $payments->createCheckout($customer['id'], 'price_xxx', ...);
   ```

3. **Estender com módulos** - Adicionar funcionalidades específicas
   - Adicione módulos customizados para seu tipo de negócio
   - Mantenha o core de pagamentos intacto
   - Exemplo: Módulo de agendamentos, módulo de produtos, etc.

4. **Usar apenas backend** - Criar seu próprio frontend
   - Use apenas a API REST
   - Crie seu próprio frontend (React, Vue, Angular, etc.)
   - Ideal para quem quer controle total da interface

---

## 📚 DOCUMENTAÇÃO RELACIONADA

- **[README.md](../README.md)** - Guia rápido de instalação
- **[DOCUMENTACAO_COMPLETA_SISTEMA.md](DOCUMENTACAO_COMPLETA_SISTEMA.md)** - Documentação técnica completa
- **[GUIA_INTEGRACAO_SAAS.md](GUIA_INTEGRACAO_SAAS.md)** - Como integrar no seu SaaS
- **[GUIA_CLINICA_VETERINARIA.md](GUIA_CLINICA_VETERINARIA.md)** - 🐾 Como usar para clínica veterinária
- **[INTEGRACAO_FRONTEND.md](INTEGRACAO_FRONTEND.md)** - Integração com frontend separado
- **[MULTI_TENANCY_TENANT.md](MULTI_TENANCY_TENANT.md)** - Documentação de multi-tenancy
- **[ARQUITETURA_MODULAR_SAAS.md](ARQUITETURA_MODULAR_SAAS.md)** - Arquitetura modular

---

**Versão:** 1.0.5  
**Última Atualização:** 2025-01-21

