# 🐾 Guia: Usando o Sistema para Clínica Veterinária

**Data:** 2025-01-21  
**Objetivo:** Explicar como usar este sistema base de pagamentos para criar uma clínica veterinária completa

---

## 📋 SUMÁRIO

1. [O Que o Sistema Já Oferece](#o-que-o-sistema-já-oferece)
2. [O Que Precisa Ser Adicionado](#o-que-precisa-ser-adicionado)
3. [Arquitetura Proposta](#arquitetura-proposta)
4. [Passo a Passo de Implementação](#passo-a-passo-de-implementação)
5. [Exemplos Práticos](#exemplos-práticos)
6. [Casos de Uso](#casos-de-uso)

---

## ✅ O QUE O SISTEMA JÁ OFERECE

O sistema atual já fornece uma **base sólida** que você pode usar imediatamente:

### 1. Sistema de Pagamentos Completo

- ✅ **Cobrança de Consultas** - Processar pagamentos de consultas veterinárias
- ✅ **Assinaturas de Planos** - Planos mensais/anuais para tutores (ex: plano de saúde animal)
- ✅ **Faturas Automáticas** - Emitir faturas para serviços prestados
- ✅ **Múltiplos Métodos de Pagamento** - Cartão, PIX, boleto (via Stripe)
- ✅ **Portal de Cobrança** - Cliente gerencia seus próprios pagamentos
- ✅ **Reembolsos** - Processar reembolsos quando necessário

### 2. Gestão de Clientes

- ✅ **Cadastro de Tutores** - Usar a tabela `customers` para cadastrar tutores
- ✅ **Histórico de Pagamentos** - Ver todas as transações de cada tutor
- ✅ **Métodos de Pagamento** - Cliente pode salvar cartões para pagamentos futuros

### 3. Sistema Multi-Tenant

- ✅ **Isolamento de Dados** - Cada clínica tem seus próprios dados
- ✅ **Múltiplos Usuários** - Veterinários, atendentes, administradores
- ✅ **Permissões** - Controle de acesso por função

### 4. Interface Web

- ✅ **Dashboard** - Visão geral de pagamentos e assinaturas
- ✅ **Relatórios** - Relatórios financeiros
- ✅ **Gestão de Produtos/Serviços** - Cadastrar serviços (consulta, cirurgia, exames)

---

## 🆕 O QUE PRECISA SER ADICIONADO

Para uma clínica veterinária completa, você precisa adicionar:

### 1. Módulo de Clínica Veterinária

Funcionalidades específicas que não estão no core:

- 🆕 **Cadastro de Pets** - Animais dos tutores
- 🆕 **Agendamentos** - Sistema de agendamento de consultas
- 🆕 **Profissionais** - Cadastro de veterinários e suas especialidades
- 🆕 **Exames** - Cadastro e resultado de exames
- 🆕 **Prontuários** - Histórico médico dos animais
- 🆕 **Calendário** - Visualização de agendamentos
- 🆕 **Configurações da Clínica** - Horários, duração de consultas, etc.

### 2. Integração com Pagamentos

Conectar as funcionalidades de clínica com o sistema de pagamentos:

- 🆕 **Cobrança Automática** - Ao criar agendamento, criar cobrança
- 🆕 **Produtos/Serviços** - Vincular serviços (consulta, exame) com produtos Stripe
- 🆕 **Assinaturas de Planos** - Planos de saúde animal recorrentes

---

## 🏗️ ARQUITETURA PROPOSTA

```
┌─────────────────────────────────────────────────────┐
│         Sistema Base (Já Existe)                    │
│  ✅ Pagamentos, Assinaturas, Clientes, Usuários     │
└──────────────────┬──────────────────────────────────┘
                   │
                   │ Extensão via Módulo
                   │
┌──────────────────▼──────────────────────────────────┐
│      Módulo Clínica Veterinária (A Criar)            │
│                                                       │
│  🆕 Pets (Animais)                                   │
│  🆕 Agendamentos                                     │
│  🆕 Profissionais (Veterinários)                     │
│  🆕 Exames                                           │
│  🆕 Prontuários                                      │
│  🆕 Calendário                                       │
│                                                       │
│  🔗 Integração com Sistema Base:                     │
│     - Criar Customer (Tutor) → Criar Pet            │
│     - Criar Agendamento → Criar Charge/Invoice      │
│     - Criar Exame → Criar Invoice Item              │
└──────────────────────────────────────────────────────┘
```

---

## 📝 PASSO A PASSO DE IMPLEMENTAÇÃO

### Fase 1: Usar o Sistema Base (Imediato)

Você pode começar a usar o sistema **agora mesmo** para gerenciar pagamentos:

#### 1.1. Criar Tenant da Clínica

```bash
php scripts/setup_tenant.php "Clínica Veterinária ABC"
```

Isso cria:
- Um tenant com API Key
- Isolamento de dados da sua clínica

#### 1.2. Cadastrar Tutores como Customers

```php
// Via API ou Dashboard
POST /v1/customers
{
  "email": "joao@email.com",
  "name": "João Silva",
  "metadata": {
    "phone": "(11) 98765-4321",
    "cpf": "123.456.789-00"
  }
}
```

#### 1.3. Criar Produtos/Serviços no Stripe

```php
// Via Dashboard ou API
POST /v1/products
{
  "name": "Consulta Veterinária",
  "description": "Consulta clínica geral",
  "metadata": {
    "tipo": "consulta",
    "duracao_minutos": 30
  }
}

POST /v1/prices
{
  "product": "prod_xxx",
  "unit_amount": 15000, // R$ 150,00 (em centavos)
  "currency": "brl",
  "recurring": null // Pagamento único
}
```

#### 1.4. Processar Pagamentos

```php
// Criar checkout para consulta
POST /v1/checkout
{
  "customer": "cus_xxx",
  "line_items": [{
    "price": "price_xxx",
    "quantity": 1
  }],
  "mode": "payment",
  "success_url": "https://clinica.com/success",
  "cancel_url": "https://clinica.com/cancel"
}
```

**✅ Neste ponto, você já pode:**
- Cadastrar tutores
- Criar serviços (consultas, exames, cirurgias)
- Processar pagamentos
- Emitir faturas
- Gerenciar assinaturas de planos

---

### Fase 2: Criar Módulo de Clínica (Extensão)

Para adicionar funcionalidades específicas de clínica veterinária:

#### 2.1. Criar Estrutura do Módulo

```
App/Modules/Clinic/
├── ClinicModule.php
├── Controllers/
│   ├── PetController.php
│   ├── AppointmentController.php
│   ├── ProfessionalController.php
│   └── ExamController.php
├── Models/
│   ├── Pet.php
│   ├── Appointment.php
│   ├── Professional.php
│   └── Exam.php
├── Services/
│   ├── AppointmentService.php
│   └── ExamService.php
└── Views/
    ├── pets.php
    ├── appointments.php
    └── calendar.php
```

#### 2.2. Criar Tabelas no Banco

```sql
-- Tabela de Pets
CREATE TABLE pets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    customer_id INT NOT NULL, -- FK para customers (tutor)
    name VARCHAR(255) NOT NULL,
    species VARCHAR(100), -- cão, gato, etc.
    breed VARCHAR(100),
    birth_date DATE,
    gender ENUM('macho', 'femea'),
    weight DECIMAL(5,2),
    color VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Tabela de Agendamentos
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    pet_id INT NOT NULL,
    professional_id INT,
    customer_id INT NOT NULL, -- Tutor
    appointment_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    type VARCHAR(100), -- consulta, cirurgia, exame
    notes TEXT,
    stripe_invoice_id VARCHAR(255), -- Vincular com fatura
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (pet_id) REFERENCES pets(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Tabela de Profissionais
CREATE TABLE professionals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    user_id INT, -- FK para users (se for usuário do sistema)
    name VARCHAR(255) NOT NULL,
    crmv VARCHAR(50), -- CRMV do veterinário
    specialty VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela de Exames
CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    pet_id INT NOT NULL,
    appointment_id INT,
    professional_id INT,
    exam_type VARCHAR(100), -- hemograma, raio-x, etc.
    exam_date DATE NOT NULL,
    result TEXT,
    notes TEXT,
    stripe_invoice_item_id VARCHAR(255), -- Vincular com item de fatura
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (pet_id) REFERENCES pets(id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id)
);
```

#### 2.3. Criar Models

```php
// App/Modules/Clinic/Models/Pet.php
<?php

namespace App\Modules\Clinic\Models;

use App\Models\BaseModel;

class Pet extends BaseModel
{
    protected string $table = 'pets';
    protected bool $usesSoftDeletes = true;

    public function findByCustomer(int $tenantId, int $customerId): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'deleted_at' => null
        ]);
    }
}
```

#### 2.4. Criar Controllers

```php
// App/Modules/Clinic/Controllers/PetController.php
<?php

namespace App\Modules\Clinic\Controllers;

use App\Modules\Clinic\Models\Pet;
use App\Models\Customer;

class PetController
{
    public function create(): void
    {
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validações
        if (empty($data['customer_id']) || empty($data['name'])) {
            Flight::json(['error' => 'Dados inválidos'], 400);
            return;
        }
        
        // Verifica se customer existe e pertence ao tenant
        $customerModel = new Customer();
        $customer = $customerModel->findById($data['customer_id']);
        
        if (!$customer || $customer['tenant_id'] != $tenantId) {
            Flight::json(['error' => 'Cliente não encontrado'], 404);
            return;
        }
        
        // Cria pet
        $petModel = new Pet();
        $petId = $petModel->insert([
            'tenant_id' => $tenantId,
            'customer_id' => $data['customer_id'],
            'name' => $data['name'],
            'species' => $data['species'] ?? null,
            'breed' => $data['breed'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'weight' => $data['weight'] ?? null,
            'color' => $data['color'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);
        
        Flight::json([
            'success' => true,
            'data' => $petModel->findById($petId)
        ], 201);
    }
    
    public function list(): void
    {
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $customerId = Flight::request()->query['customer_id'] ?? null;
        
        $petModel = new Pet();
        
        if ($customerId) {
            $pets = $petModel->findByCustomer($tenantId, $customerId);
        } else {
            $pets = $petModel->findAll(['tenant_id' => $tenantId]);
        }
        
        Flight::json([
            'success' => true,
            'count' => count($pets),
            'data' => $pets
        ]);
    }
}
```

#### 2.5. Integrar com Sistema de Pagamentos

```php
// App/Modules/Clinic/Services/AppointmentService.php
<?php

namespace App\Modules\Clinic\Services;

use App\Services\StripeService;
use App\Models\Customer;
use App\Modules\Clinic\Models\Appointment;

class AppointmentService
{
    public function createAppointmentWithPayment(
        int $tenantId,
        array $appointmentData,
        string $priceId // ID do preço no Stripe
    ): array {
        // 1. Criar agendamento
        $appointmentModel = new Appointment();
        $appointmentId = $appointmentModel->insert([
            'tenant_id' => $tenantId,
            'pet_id' => $appointmentData['pet_id'],
            'customer_id' => $appointmentData['customer_id'],
            'appointment_date' => $appointmentData['appointment_date'],
            'status' => 'scheduled',
            // ... outros campos
        ]);
        
        // 2. Criar invoice no Stripe
        $stripeService = new StripeService();
        $customer = (new Customer())->findById($appointmentData['customer_id']);
        
        $invoice = $stripeService->createInvoice([
            'customer' => $customer['stripe_customer_id'],
            'auto_advance' => true, // Cobrar automaticamente
            'collection_method' => 'charge_automatically',
            'metadata' => [
                'appointment_id' => $appointmentId,
                'tenant_id' => $tenantId
            ]
        ]);
        
        // 3. Adicionar item à invoice
        $stripeService->createInvoiceItem([
            'customer' => $customer['stripe_customer_id'],
            'invoice' => $invoice->id,
            'price' => $priceId,
            'metadata' => [
                'appointment_id' => $appointmentId
            ]
        ]);
        
        // 4. Finalizar invoice (cobra automaticamente)
        $finalizedInvoice = $stripeService->finalizeInvoice($invoice->id);
        
        // 5. Atualizar agendamento com invoice_id
        $appointmentModel->update($appointmentId, [
            'stripe_invoice_id' => $finalizedInvoice->id
        ]);
        
        return [
            'appointment' => $appointmentModel->findById($appointmentId),
            'invoice' => $finalizedInvoice->toArray()
        ];
    }
}
```

---

## 💡 EXEMPLOS PRÁTICOS

### Exemplo 1: Fluxo Completo de Consulta

```php
// 1. Cliente (tutor) já cadastrado como Customer
$customerId = 123; // ID do customer no sistema

// 2. Cadastrar pet do tutor
POST /v1/clinic/pets
{
  "customer_id": 123,
  "name": "Rex",
  "species": "cão",
  "breed": "Golden Retriever",
  "birth_date": "2020-05-15",
  "gender": "macho"
}

// 3. Criar agendamento com pagamento
POST /v1/clinic/appointments
{
  "pet_id": 1,
  "customer_id": 123,
  "professional_id": 5,
  "appointment_date": "2025-01-25 14:00:00",
  "type": "consulta",
  "price_id": "price_xxx" // Preço da consulta no Stripe
}

// O sistema automaticamente:
// - Cria o agendamento
// - Cria invoice no Stripe
// - Cobra o cliente
// - Envia email de confirmação
```

### Exemplo 2: Plano de Saúde Animal

```php
// 1. Criar produto de plano no Stripe
POST /v1/products
{
  "name": "Plano Saúde Animal - Básico",
  "description": "4 consultas por mês + exames com desconto"
}

POST /v1/prices
{
  "product": "prod_xxx",
  "unit_amount": 9900, // R$ 99,00/mês
  "currency": "brl",
  "recurring": {
    "interval": "month"
  }
}

// 2. Cliente assina o plano
POST /v1/subscriptions
{
  "customer": "cus_xxx",
  "items": [{
    "price": "price_xxx"
  }]
}

// 3. Sistema cobra automaticamente todo mês
// 4. Cliente pode usar as consultas incluídas
```

### Exemplo 3: Exame com Cobrança Separada

```php
// 1. Criar exame após consulta
POST /v1/clinic/exams
{
  "pet_id": 1,
  "appointment_id": 10,
  "exam_type": "hemograma",
  "exam_date": "2025-01-25",
  "price_id": "price_exame_xxx"
}

// Sistema cria invoice item e cobra separadamente
```

---

## 🎯 CASOS DE USO

### Caso 1: Clínica Pequena (1-2 Veterinários)

**O que usar:**
- ✅ Sistema base de pagamentos
- ✅ Cadastro de tutores (customers)
- ✅ Produtos/Serviços (consultas, exames)
- ✅ Faturas automáticas
- 🆕 Módulo básico: Pets + Agendamentos simples

**Fluxo:**
1. Tutor agenda consulta pelo sistema
2. Sistema cria agendamento + cobrança
3. Tutor paga online
4. Veterinário confirma consulta
5. Após consulta, sistema pode criar exames adicionais

### Caso 2: Clínica Média (3-5 Veterinários)

**O que usar:**
- ✅ Tudo do caso 1
- 🆕 Módulo completo: Pets, Agendamentos, Profissionais, Exames
- 🆕 Calendário de agendamentos
- 🆕 Prontuários eletrônicos

**Fluxo:**
1. Atendente agenda consulta para veterinário específico
2. Sistema verifica disponibilidade
3. Cria agendamento + cobrança
4. Veterinário acessa prontuário do pet
5. Após consulta, adiciona exames/medicamentos
6. Sistema gera fatura com todos os itens

### Caso 3: Clínica Grande (5+ Veterinários)

**O que usar:**
- ✅ Tudo dos casos anteriores
- 🆕 Módulo avançado: Especialidades, Horários, Bloqueios
- 🆕 Relatórios específicos de clínica
- 🆕 Integração com laboratórios

**Fluxo:**
1. Sistema gerencia múltiplos profissionais
2. Cada veterinário tem sua agenda
3. Planos de saúde animal recorrentes
4. Integração com exames externos
5. Relatórios financeiros e clínicos

---

## 🚀 PRÓXIMOS PASSOS

1. **Comece usando o sistema base** - Já funcional para pagamentos
2. **Cadastre seus tutores** - Use a funcionalidade de customers
3. **Crie seus serviços** - Produtos e preços no Stripe
4. **Processe pagamentos** - Use checkout ou invoices
5. **Adicione módulo de clínica** - Quando precisar de funcionalidades específicas

---

## 📚 DOCUMENTAÇÃO RELACIONADA

- **[PROPOSITO_SISTEMA.md](PROPOSITO_SISTEMA.md)** - Visão geral do sistema
- **[ARQUITETURA_MODULAR_SAAS.md](ARQUITETURA_MODULAR_SAAS.md)** - Como criar módulos
- **[GUIA_INTEGRACAO_SAAS.md](GUIA_INTEGRACAO_SAAS.md)** - Integração via API
- **[README.md](../README.md)** - Instalação e setup

---

**Versão:** 1.0.0  
**Última Atualização:** 2025-01-21

