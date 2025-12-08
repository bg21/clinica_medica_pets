# 💳 Integração de Pagamentos - Clínica Veterinária

**Data:** 2025-12-07  
**Status:** ✅ Implementado

---

## 📋 RESUMO

Foi implementada a integração completa entre agendamentos e o sistema de pagamentos Stripe. Agora é possível criar invoices automaticamente ao criar agendamentos e processar pagamentos.

---

## ✅ O QUE FOI IMPLEMENTADO

### 1. **AppointmentService** (`App/Services/AppointmentService.php`)

Service responsável por integrar agendamentos com pagamentos:

**Métodos principais:**
- `createAppointmentWithPayment()` - Cria agendamento e invoice automaticamente
- `processAppointmentPayment()` - Processa pagamento de agendamento existente
- `getAppointmentInvoice()` - Obtém invoice de um agendamento
- `updateAppointmentStatusFromInvoice()` - Atualiza status via webhook

**Funcionalidades:**
- ✅ Cria invoice no Stripe ao criar agendamento (se `price_id` fornecido)
- ✅ Vincula `stripe_invoice_id` ao agendamento
- ✅ Adiciona invoice item com descrição do agendamento
- ✅ Suporta cobrança automática ou manual
- ✅ Atualiza status do agendamento quando invoice é pago (via webhook)

---

### 2. **Métodos no StripeService** (`App/Services/StripeService.php`)

Adicionados métodos para gerenciar invoices:

- `createInvoice()` - Cria invoice no Stripe
- `finalizeInvoice()` - Finaliza invoice (torna cobrável)

---

### 3. **AppointmentController Atualizado**

**Novos endpoints:**
- `POST /v1/clinic/appointments/:id/pay` - Processar pagamento de agendamento
- `GET /v1/clinic/appointments/:id/invoice` - Obter invoice do agendamento

**Endpoint modificado:**
- `POST /v1/clinic/appointments` - Agora aceita `price_id` e `auto_charge` opcionais

---

### 4. **Integração com Webhooks**

Modificado `PaymentService::handleInvoicePaid()` para:
- Detectar quando invoice está vinculada a um agendamento (via metadata)
- Atualizar status do agendamento automaticamente quando invoice é paga
- Logs detalhados para rastreamento

---

## 🔌 ENDPOINTS DA API

### Criar Agendamento com Pagamento

```http
POST /v1/clinic/appointments
Content-Type: application/json
Authorization: Bearer {session_id}

{
  "pet_id": 1,
  "customer_id": 123,
  "professional_id": 5,
  "appointment_date": "2025-12-15 14:00:00",
  "duration_minutes": 30,
  "type": "consulta",
  "notes": "Consulta de rotina",
  "price_id": "price_xxxxx",  // ✅ NOVO: ID do preço no Stripe
  "auto_charge": true          // ✅ NOVO: Se true, cobra automaticamente
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "pet_id": 1,
    "customer_id": 123,
    "appointment_date": "2025-12-15 14:00:00",
    "status": "scheduled",
    "stripe_invoice_id": "in_xxxxx",
    "invoice": {
      "id": "in_xxxxx",
      "status": "paid",
      "amount_due": 150.00,
      "amount_paid": 150.00,
      "currency": "BRL",
      "hosted_invoice_url": "https://invoice.stripe.com/...",
      "invoice_pdf": "https://pay.stripe.com/..."
    }
  },
  "message": "Agendamento criado com sucesso"
}
```

---

### Processar Pagamento de Agendamento Existente

```http
POST /v1/clinic/appointments/:id/pay
Content-Type: application/json
Authorization: Bearer {session_id}

{
  "price_id": "price_xxxxx",
  "auto_charge": true
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "invoice": {
      "id": "in_xxxxx",
      "status": "paid",
      "amount_due": 150.00,
      "amount_paid": 150.00,
      "currency": "BRL",
      "hosted_invoice_url": "https://invoice.stripe.com/...",
      "invoice_pdf": "https://pay.stripe.com/..."
    }
  },
  "message": "Pagamento processado com sucesso"
}
```

---

### Obter Invoice do Agendamento

```http
GET /v1/clinic/appointments/:id/invoice
Authorization: Bearer {session_id}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id": "in_xxxxx",
    "status": "paid",
    "amount_due": 150.00,
    "amount_paid": 150.00,
    "currency": "BRL",
    "hosted_invoice_url": "https://invoice.stripe.com/...",
    "invoice_pdf": "https://pay.stripe.com/...",
    "paid": true,
    "created": "2025-12-07 10:30:00"
  }
}
```

---

## 🔄 FLUXO DE INTEGRAÇÃO

### Fluxo 1: Criar Agendamento com Pagamento Automático

```
1. Cliente cria agendamento via API
   POST /v1/clinic/appointments
   { price_id: "price_xxx", auto_charge: true }

2. AppointmentService:
   - Cria agendamento no banco
   - Cria invoice no Stripe
   - Adiciona invoice item (consulta)
   - Finaliza invoice (cobra automaticamente)
   - Vincula stripe_invoice_id ao agendamento

3. Stripe processa pagamento
   - Se sucesso: webhook invoice.paid
   - PaymentService atualiza status do agendamento para "confirmed"

4. Resposta retorna agendamento + invoice
```

### Fluxo 2: Criar Agendamento e Pagar Depois

```
1. Cliente cria agendamento sem pagamento
   POST /v1/clinic/appointments
   { ... } (sem price_id)

2. Agendamento criado com status "scheduled"

3. Cliente paga depois:
   POST /v1/clinic/appointments/:id/pay
   { price_id: "price_xxx", auto_charge: true }

4. Invoice criada e cobrada
5. Webhook atualiza status para "confirmed"
```

### Fluxo 3: Webhook de Pagamento

```
1. Stripe envia webhook invoice.paid
   POST /v1/webhook

2. PaymentService::handleInvoicePaid()
   - Verifica metadata do invoice
   - Se tem appointment_id, chama AppointmentService

3. AppointmentService::updateAppointmentStatusFromInvoice()
   - Busca agendamento pelo stripe_invoice_id
   - Atualiza status para "confirmed"
   - Log da atualização
```

---

## 📊 METADADOS DO INVOICE

O invoice criado contém os seguintes metadados:

```json
{
  "appointment_id": "1",
  "tenant_id": "2",
  "pet_id": "1",
  "appointment_date": "2025-12-15 14:00:00",
  "appointment_type": "consulta"
}
```

Isso permite rastrear e atualizar agendamentos via webhooks.

---

## 🔒 SEGURANÇA E VALIDAÇÕES

### Validações Implementadas

1. **Customer com Stripe ID**: Verifica se customer tem `stripe_customer_id` antes de criar invoice
2. **Proteção IDOR**: Todos os métodos verificam `tenant_id`
3. **Validação de Relacionamentos**: Verifica se pet, customer e professional existem
4. **Idempotência**: Webhooks verificam se evento já foi processado

### Tratamento de Erros

- Se criação de invoice falhar, agendamento ainda é criado (pode ser pago depois)
- Erros de webhook não quebram o processamento principal
- Logs detalhados para debugging

---

## 📝 CONFIGURAÇÃO NECESSÁRIA

### 1. Criar Produtos/Preços no Stripe

Antes de usar, é necessário criar produtos e preços no Stripe:

```http
POST /v1/products
{
  "name": "Consulta Veterinária",
  "description": "Consulta clínica geral"
}

POST /v1/prices
{
  "product": "prod_xxxxx",
  "unit_amount": 15000,  // R$ 150,00 em centavos
  "currency": "brl"
}
```

### 2. Garantir que Customers tenham Stripe Customer ID

Customers precisam ter `stripe_customer_id` para receber invoices. Isso é criado automaticamente quando:
- Customer é criado via API de customers
- Customer faz checkout pela primeira vez

---

## 🧪 EXEMPLOS DE USO

### Exemplo 1: Criar Agendamento com Cobrança Automática

```javascript
const response = await fetch('/v1/clinic/appointments', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + sessionId
  },
  body: JSON.stringify({
    pet_id: 1,
    customer_id: 123,
    professional_id: 5,
    appointment_date: '2025-12-15 14:00:00',
    duration_minutes: 30,
    type: 'consulta',
    price_id: 'price_xxxxx',  // Preço da consulta
    auto_charge: true          // Cobra automaticamente
  })
});

const data = await response.json();
// data.data.invoice contém dados do invoice criado
```

### Exemplo 2: Pagar Agendamento Existente

```javascript
const response = await fetch('/v1/clinic/appointments/1/pay', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + sessionId
  },
  body: JSON.stringify({
    price_id: 'price_xxxxx',
    auto_charge: true
  })
});
```

### Exemplo 3: Verificar Status do Pagamento

```javascript
const response = await fetch('/v1/clinic/appointments/1/invoice', {
  headers: {
    'Authorization': 'Bearer ' + sessionId
  }
});

const data = await response.json();
if (data.data.paid) {
  console.log('Agendamento pago!');
}
```

---

## 🔗 ARQUIVOS MODIFICADOS/CRIADOS

### Criados
- ✅ `App/Services/AppointmentService.php` - Service de integração

### Modificados
- ✅ `App/Services/StripeService.php` - Adicionados métodos `createInvoice()` e `finalizeInvoice()`
- ✅ `App/Controllers/AppointmentController.php` - Integrado com AppointmentService e novos endpoints
- ✅ `App/Services/PaymentService.php` - Integrado webhook `invoice.paid` com agendamentos
- ✅ `App/Core/ContainerBindings.php` - Registrado AppointmentService e atualizado AppointmentController
- ✅ `public/index.php` - Adicionadas rotas de pagamento

---

## ✅ CHECKLIST DE IMPLEMENTAÇÃO

- [x] AppointmentService criado
- [x] Métodos createInvoice e finalizeInvoice no StripeService
- [x] AppointmentController atualizado para usar service
- [x] Endpoints de pagamento adicionados
- [x] Integração com webhooks implementada
- [x] Container de DI atualizado
- [x] Rotas registradas
- [x] Validações e tratamento de erros
- [x] Logs detalhados

---

## 🚀 PRÓXIMOS PASSOS

1. **Testar integração** - Criar agendamento com price_id e verificar invoice
2. **Configurar webhook** - Garantir que webhook do Stripe está configurado
3. **Atualizar views** - Adicionar campos de price_id nos formulários
4. **Criar produtos/preços** - Cadastrar serviços da clínica no Stripe

---

## 📚 REFERÊNCIAS

- **[FALTANTES_CLINICA_VETERINARIA.md](FALTANTES_CLINICA_VETERINARIA.md)** - Lista completa do que falta
- **[GUIA_CLINICA_VETERINARIA.md](GUIA_CLINICA_VETERINARIA.md)** - Guia completo
- **[ROTAS_CLINICA_VETERINARIA.md](ROTAS_CLINICA_VETERINARIA.md)** - Rotas da API

---

**Última Atualização:** 2025-12-07

