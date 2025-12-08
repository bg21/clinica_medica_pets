# 🏗️ Arquitetura Modular para SaaS Multi-Negócio

**Data:** 2025-12-02  
**Objetivo:** Tornar o SaaS genérico e reutilizável para diferentes tipos de negócios

---

## 📋 SUMÁRIO

1. [Problema Atual](#problema-atual)
2. [Solução Proposta](#solução-proposta)
3. [Arquitetura de Módulos](#arquitetura-de-módulos)
4. [Estrutura de Diretórios](#estrutura-de-diretórios)
5. [Fluxo de Funcionamento](#fluxo-de-funcionamento)
6. [Exemplo Prático: Diferentes Domínios](#exemplo-prático-diferentes-domínios)
7. [Migração e Compatibilidade](#migração-e-compatibilidade)
8. [Vantagens da Arquitetura](#vantagens-da-arquitetura)

---

## 🎯 PROBLEMA ATUAL

### Situação Atual

O sistema foi desenvolvido com lógica de negócio específica de um domínio, que foi removida para tornar o sistema genérico.

### Limitação Anterior

Para usar o SaaS em diferentes tipos de negócios, seria necessário:
- ❌ Reescrever grande parte do código
- ❌ Remover lógica específica do domínio
- ❌ Criar nova lógica do zero
- ❌ Manter múltiplas versões do código

---

## 💡 SOLUÇÃO PROPOSTA

### Conceito: Sistema de Módulos

Criar um **sistema modular** onde cada tipo de negócio é um **módulo independente**:

```
SaaS Core (Genérico)
    ├── Módulo Customizado (custom)
    ├── Módulo Academia (gym)
    ├── Módulo Salão (salon)
    └── Módulo Personalizado (custom)
```

### Princípios

1. **Core Genérico:** Pagamentos, assinaturas, usuários, permissões (comum a todos)
2. **Módulos Específicos:** Lógica de negócio isolada por tipo
3. **Carregamento Dinâmico:** Módulos carregados baseado no `business_type` do tenant
4. **Isolamento:** Cada módulo é independente e não interfere nos outros

---

## 🏗️ ARQUITETURA DE MÓDULOS

### 1. Interface de Módulo

Todos os módulos devem implementar a interface `ModuleInterface`:

```php
interface ModuleInterface
{
    public function getName(): string;              // Nome do módulo
    public function getBusinessType(): string;     // Tipo de negócio (clinic, gym, etc.)
    public function getVersion(): string;          // Versão do módulo
    public function registerRoutes(Engine $app): void;  // Registra rotas
    public function getControllers(): array;       // Retorna controllers
    public function getModels(): array;            // Retorna models
    public function isActive(): bool;              // Verifica se está ativo
    public function getInfo(): array;              // Informações do módulo
}
```

### 2. Estrutura de um Módulo

Cada módulo terá sua própria estrutura:

```
App/Modules/
    ├── Clinic/
    │   ├── ClinicModule.php          # Classe principal do módulo
    │   ├── Controllers/
    │   │   ├── ProfessionalController.php
    │   │   ├── PetController.php
    │   │   ├── ExamController.php
    │   │   └── ClinicController.php
    │   ├── Models/
    │   │   ├── Professional.php
    │   │   ├── Pet.php
    │   │   ├── Exam.php
    │   │   └── ClinicConfiguration.php
    │   ├── Repositories/
    │   │   ├── ProfessionalRepository.php
    │   │   └── PetRepository.php
    │   ├── Services/
    │   │   └── AppointmentService.php
    │   └── Views/
    │       ├── appointments.php
    │       ├── pets.php
    │       └── exams.php
    │
    └── Gym/
        ├── GymModule.php
        ├── Controllers/
        │   ├── InstructorController.php
        │   ├── StudentController.php
        │   └── ClassController.php
        ├── Models/
        │   ├── Instructor.php
        │   ├── Student.php
        │   └── GymClass.php
        └── Views/
            ├── classes.php
            └── students.php
```

### 3. Classe Base de Módulo

Todos os módulos herdam de `BaseModule`:

```php
abstract class BaseModule implements ModuleInterface
{
    protected string $name;
    protected string $businessType;
    protected string $version;
    protected bool $active = true;

    // Métodos comuns a todos os módulos
    public function getName(): string { return $this->name; }
    public function getBusinessType(): string { return $this->businessType; }
    public function getVersion(): string { return $this->version; }
    public function isActive(): bool { return $this->active; }
    
    // Métodos abstratos que cada módulo implementa
    abstract public function registerRoutes(Engine $app): void;
    abstract public function getControllers(): array;
    abstract public function getModels(): array;
}
```

---

## 📁 ESTRUTURA DE DIRETÓRIOS

### Estrutura Completa Proposta

```
saas-stripe/
├── App/
│   ├── Core/                          # ✅ NOVO: Core genérico do sistema
│   │   ├── ModuleInterface.php        # Interface para módulos
│   │   ├── BaseModule.php             # Classe base para módulos
│   │   ├── ModuleRegistry.php         # Registro e gerenciamento de módulos
│   │   └── ModuleLoader.php           # Carregador de módulos
│   │
│   ├── Modules/                       # ✅ NOVO: Módulos específicos
│   │   ├── Clinic/
│   │   │   ├── ClinicModule.php
│   │   │   ├── Controllers/
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   ├── Services/
│   │   │   └── Views/
│   │   │
│   │   └── Gym/
│   │       ├── GymModule.php
│   │       ├── Controllers/
│   │       ├── Models/
│   │       └── Views/
│   │
│   ├── Controllers/                   # ✅ Mantido: Controllers genéricos (Payment, User, etc.)
│   ├── Models/                       # ✅ Mantido: Models genéricos (User, Tenant, Subscription, etc.)
│   ├── Services/                     # ✅ Mantido: Services genéricos (Stripe, Payment, etc.)
│   └── Views/                        # ✅ Mantido: Views genéricas (dashboard, login, etc.)
│
├── db/
│   └── migrations/
│       └── 20251202000001_add_business_type_to_tenants.php  # ✅ NOVO
│
└── public/
    └── index.php                      # ✅ MODIFICADO: Carrega módulos dinamicamente
```

---

## 🔄 FLUXO DE FUNCIONAMENTO

### 1. Inicialização do Sistema

```
1. Sistema inicia (index.php)
2. Carrega configurações e FlightPHP
3. Identifica o tenant (via API Key ou Session)
4. Busca business_type do tenant no banco
5. Carrega módulo correspondente ao business_type
6. Registra rotas do módulo
7. Sistema pronto para uso
```

### 2. Requisição de API

```
Cliente faz requisição → /v1/appointments
    ↓
Middleware de autenticação identifica tenant
    ↓
Sistema verifica business_type do tenant
    ↓
Carrega ClinicModule (se business_type = 'clinic')
    ↓
Rota /v1/appointments registrada pelo ClinicModule
    ↓
ProfessionalController@list é executado
    ↓
Resposta retornada
```

### 3. Mudança de Tipo de Negócio

```
Tenant quer mudar de clínica para academia:
    ↓
1. Admin atualiza business_type na tabela tenants
    ↓
2. Próxima requisição carrega GymModule
    ↓
3. Rotas de clínica não estão mais disponíveis
    ↓
4. Rotas de academia estão disponíveis
    ↓
5. Dados antigos permanecem no banco (isolados por tenant_id)
```

---

## 🎯 EXEMPLO PRÁTICO: EMPRESA VS ACADEMIA

### Empresa de Serviços (business_type: 'company')

**Módulo:** `ClinicModule`

**Entidades:**
- `Product` (Produto/Serviço)
- `Customer` (Cliente)
- `Subscription` (Assinatura)
- `Invoice` (Fatura)

**Rotas:**
- `GET /v1/products` → Lista produtos
- `GET /v1/customers` → Lista clientes
- `GET /v1/subscriptions` → Lista assinaturas
- `GET /v1/invoices` → Lista faturas

**Views:**
- `/appointments` → Calendário de consultas
- `/pets` → Lista de animais
- `/exams` → Lista de exames

---

### Academia (business_type: 'gym')

**Módulo:** `GymModule`

**Entidades:**
- `Instructor` (Instrutor)
- `Student` (Aluno)
- `GymClass` (Aula)
- `Membership` (Plano de academia)
- `Schedule` (Horário de aulas)

**Rotas:**
- `GET /v1/instructors` → Lista instrutores
- `GET /v1/students` → Lista alunos
- `GET /v1/classes` → Lista aulas
- `GET /v1/memberships` → Lista planos

**Views:**
- `/classes` → Calendário de aulas
- `/students` → Lista de alunos
- `/memberships` → Planos disponíveis

---

### Comparação Visual

| Funcionalidade | Empresa | Academia |
|---------------|---------|----------|
| **Profissional** | Funcionário | Instrutor |
| **Cliente** | Cliente | Aluno |
| **Agendamento** | Serviço | Aula |
| **Entidade Específica** | Produto | Plano de Academia |
| **Documento** | Fatura | Ficha de Treino |
| **Especialidade** | Categoria | Musculação, Pilates |

**Mas ambos compartilham:**
- ✅ Sistema de pagamentos (Stripe)
- ✅ Assinaturas
- ✅ Usuários e permissões
- ✅ Multi-tenancy
- ✅ Dashboard genérico

---

## 🔧 IMPLEMENTAÇÃO TÉCNICA

### 1. Migration: Adicionar business_type

```sql
ALTER TABLE tenants 
ADD COLUMN business_type VARCHAR(50) NOT NULL DEFAULT 'clinic' 
AFTER slug;

CREATE INDEX idx_business_type ON tenants(business_type);
```

### 2. Model Tenant Atualizado

```php
class Tenant extends BaseModel
{
    // ... código existente ...
    
    /**
     * Busca business_type do tenant
     */
    public function getBusinessType(int $tenantId): ?string
    {
        $tenant = $this->findById($tenantId);
        return $tenant['business_type'] ?? 'clinic';
    }
    
    /**
     * Atualiza business_type do tenant
     */
    public function updateBusinessType(int $tenantId, string $businessType): bool
    {
        return $this->update($tenantId, ['business_type' => $businessType]);
    }
}
```

### 3. ModuleRegistry (Gerenciador de Módulos)

```php
class ModuleRegistry
{
    private array $modules = [];
    private ?ModuleInterface $activeModule = null;
    
    /**
     * Registra um módulo
     */
    public function register(ModuleInterface $module): void
    {
        $this->modules[$module->getBusinessType()] = $module;
    }
    
    /**
     * Carrega módulo baseado no business_type
     */
    public function loadModule(string $businessType): ?ModuleInterface
    {
        if (isset($this->modules[$businessType])) {
            $this->activeModule = $this->modules[$businessType];
            return $this->activeModule;
        }
        
        return null;
    }
    
    /**
     * Retorna módulo ativo
     */
    public function getActiveModule(): ?ModuleInterface
    {
        return $this->activeModule;
    }
}
```

### 4. ClinicModule (Exemplo)

```php
class ClinicModule extends BaseModule
{
    public function __construct()
    {
        $this->name = 'Empresa de Serviços';
        $this->businessType = 'clinic';
        $this->version = '1.0.0';
    }
    
    public function registerRoutes(Engine $app): void
    {
        // Registra rotas específicas de clínica
        $professionalController = new ProfessionalController();
        $app->route('GET /v1/professionals', [$professionalController, 'list']);
        $app->route('POST /v1/professionals', [$professionalController, 'create']);
        // ... mais rotas ...
        
        // Rotas de views
        $app->route('GET /appointments', function() {
            // Renderiza view de agendamentos
        });
        // ... mais views ...
    }
    
    public function getControllers(): array
    {
        return [
            new ProfessionalController(),
            new PetController(),
            new ExamController(),
            new ClinicController()
        ];
    }
    
    public function getModels(): array
    {
        return [
            Professional::class,
            Pet::class,
            Exam::class,
            ClinicConfiguration::class
        ];
    }
}
```

### 5. index.php Atualizado

```php
// ... código existente de autenticação ...

// ✅ NOVO: Carrega módulo baseado no business_type do tenant
$tenantId = Flight::get('tenant_id');
if ($tenantId) {
    $tenantModel = new \App\Models\Tenant();
    $tenant = $tenantModel->findById($tenantId);
    $businessType = $tenant['business_type'] ?? 'clinic';
    
    // Registra módulos disponíveis
    $moduleRegistry = new \App\Core\ModuleRegistry();
    $moduleRegistry->register(new \App\Modules\Clinic\ClinicModule());
    $moduleRegistry->register(new \App\Modules\Gym\GymModule());
    // ... mais módulos ...
    
    // Carrega módulo do tenant
    $module = $moduleRegistry->loadModule($businessType);
    if ($module) {
        $module->registerRoutes($app);
    }
}

// Rotas genéricas (sempre disponíveis)
$app->route('GET /v1/customers', [$customerController, 'list']);
// ... rotas de pagamento, assinaturas, etc. ...
```

---

## 🔄 MIGRAÇÃO E COMPATIBILIDADE

### Fase 1: Preparação (Sem Quebrar Nada)

1. ✅ Adicionar campo `business_type` na tabela `tenants` (default: 'clinic')
2. ✅ Criar estrutura de módulos (`App/Core/`, `App/Modules/`)
3. ✅ Criar `ClinicModule` movendo código existente
4. ✅ Manter rotas antigas funcionando (compatibilidade)

### Fase 2: Migração Gradual

1. ✅ Mover controllers de clínica para `App/Modules/Clinic/Controllers/`
2. ✅ Mover models de clínica para `App/Modules/Clinic/Models/`
3. ✅ Mover views de clínica para `App/Modules/Clinic/Views/`
4. ✅ Atualizar namespaces e imports

### Fase 3: Ativação do Sistema Modular

1. ✅ Atualizar `index.php` para carregar módulos dinamicamente
2. ✅ Remover rotas hardcoded de clínica
3. ✅ Testar com tenants existentes (todos com `business_type = 'clinic'`)

### Fase 4: Novos Módulos

1. ✅ Criar `GymModule` para academias
2. ✅ Implementar lógica específica de academia
3. ✅ Testar criação de tenant com `business_type = 'gym'`

---

## ✅ VANTAGENS DA ARQUITETURA

### 1. Reutilização de Código

- ✅ **Core genérico** (pagamentos, usuários) usado por todos
- ✅ **Módulos específicos** isolados e independentes
- ✅ **Novos negócios** = novo módulo, sem reescrever core

### 2. Manutenibilidade

- ✅ **Código organizado** por tipo de negócio
- ✅ **Fácil localizar** funcionalidades específicas
- ✅ **Testes isolados** por módulo

### 3. Escalabilidade

- ✅ **Adicionar novo tipo de negócio** = criar novo módulo
- ✅ **Não afeta** módulos existentes
- ✅ **Cada módulo evolui** independentemente

### 4. Flexibilidade

- ✅ **Tenant pode mudar** de tipo de negócio (atualizando `business_type`)
- ✅ **Múltiplos tipos** podem coexistir no mesmo sistema
- ✅ **Módulos opcionais** podem ser ativados/desativados

### 5. Isolamento

- ✅ **Bugs em um módulo** não afetam outros
- ✅ **Atualizações** podem ser feitas por módulo
- ✅ **Rollback** de módulo específico sem afetar sistema

---

## 📊 COMPARAÇÃO: ANTES vs DEPOIS

### Antes (Monolítico)

```
SaaS
├── Lógica de Clínica (hardcoded)
├── Lógica de Pagamentos
├── Lógica de Usuários
└── Tudo misturado
```

**Problema:** Para criar academia, precisa reescrever tudo.

---

### Depois (Modular)

```
SaaS Core (Genérico)
├── Pagamentos
├── Assinaturas
├── Usuários
└── Permissões

Módulos (Específicos)
├── ClinicModule
│   └── Lógica de Clínica
└── GymModule
    └── Lógica de Academia
```

**Solução:** Para criar academia, apenas cria `GymModule`.

---

## 🎓 EXEMPLO COMPLETO: CRIANDO UM NOVO MÓDULO

### Passo 1: Criar Estrutura

```
App/Modules/MyBusiness/
├── MyBusinessModule.php
├── Controllers/
├── Models/
└── Views/
```

### Passo 2: Implementar ModuleInterface

```php
class MyBusinessModule extends BaseModule
{
    public function __construct()
    {
        $this->name = 'Meu Negócio';
        $this->businessType = 'mybusiness';
        $this->version = '1.0.0';
    }
    
    public function registerRoutes(Engine $app): void
    {
        $controller = new MyBusinessController();
        $app->route('GET /v1/myentities', [$controller, 'list']);
    }
    
    // ... implementar outros métodos ...
}
```

### Passo 3: Registrar no index.php

```php
$moduleRegistry->register(new \App\Modules\MyBusiness\MyBusinessModule());
```

### Passo 4: Criar Tenant com business_type

```sql
INSERT INTO tenants (name, slug, business_type, api_key, status)
VALUES ('Minha Empresa', 'minha-empresa', 'mybusiness', '...', 'active');
```

**Pronto!** O sistema agora carrega `MyBusinessModule` automaticamente.

---

## 🔒 CONSIDERAÇÕES DE SEGURANÇA

### 1. Validação de business_type

- ✅ Validar que `business_type` existe antes de carregar módulo
- ✅ Fallback para módulo padrão se módulo não encontrado
- ✅ Log de tentativas de acesso a módulos não disponíveis

### 2. Isolamento de Dados

- ✅ **Mantém isolamento por tenant_id** (já existe)
- ✅ **Módulos não acessam dados de outros módulos**
- ✅ **Permissões específicas por módulo**

### 3. Validação de Rotas

- ✅ **Rotas de módulo só disponíveis** se módulo estiver ativo
- ✅ **Middleware verifica** se tenant tem acesso ao módulo
- ✅ **404 para rotas de módulos não carregados**

---

## 📝 PRÓXIMOS PASSOS

1. ✅ **Revisar e aprovar** esta arquitetura
2. ✅ **Criar migration** para `business_type`
3. ✅ **Criar estrutura de módulos** (`App/Core/`, `App/Modules/`)
4. ✅ **Migrar código de clínica** para `ClinicModule`
5. ✅ **Atualizar index.php** para carregar módulos
6. ✅ **Testar com tenants existentes**
7. ✅ **Criar GymModule** como exemplo de novo negócio
8. ✅ **Documentar** cada módulo criado

---

## ❓ PERGUNTAS FREQUENTES

### 1. E os dados antigos de clínica?

**R:** Permanecem no banco. O `tenant_id` já isola os dados. Ao mudar `business_type`, apenas as rotas mudam, os dados permanecem.

### 2. Posso ter múltiplos módulos ativos?

**R:** Não. Cada tenant tem **um business_type**, então **um módulo ativo** por vez. Mas diferentes tenants podem ter diferentes módulos.

### 3. E se eu quiser funcionalidades de clínica E academia?

**R:** Duas opções:
- Criar um módulo híbrido (`hybrid`)
- Ou criar um módulo customizado que combine funcionalidades

### 4. Como atualizar um módulo?

**R:** Atualizar código do módulo e incrementar versão. O sistema carrega automaticamente a nova versão.

### 5. E se o módulo tiver bugs?

**R:** O sistema pode ter fallback para módulo padrão ou desativar módulo específico sem afetar o core.

---

## 📚 REFERÊNCIAS

- **Padrão Strategy:** Módulos são estratégias diferentes de negócio
- **Padrão Factory:** ModuleRegistry cria instâncias de módulos
- **Plugin Architecture:** Sistema de plugins para extensibilidade
- **Multi-Tenancy:** Isolamento por tenant mantido

---

**Fim do Documento**

