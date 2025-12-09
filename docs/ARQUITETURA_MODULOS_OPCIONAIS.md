# 🏪 Arquitetura de Módulos Opcionais - Clínica Veterinária + Petshop

**Data:** 2025-12-09  
**Objetivo:** Explicar como implementar módulos opcionais (add-ons) como o Petshop para a Clínica Veterinária

---

## 📋 CONCEITO

### Modelo de Negócio

```
┌─────────────────────────────────────────┐
│   CLÍNICA VETERINÁRIA (Base)            │
│   ✅ Sempre incluído                    │
│   - Pets, Profissionais, Agendamentos   │
│   - Exames, Vacinações, Prontuários     │
│   - Dashboard, Relatórios               │
└─────────────────────────────────────────┘
              │
              │ + R$ X/mês
              ▼
┌─────────────────────────────────────────┐
│   PETSHOP (Módulo Opcional)            │
│   ⚠️ Adicional - Pago separadamente    │
│   - Produtos (rações, brinquedos)      │
│   - Estoque, Fornecedores               │
│   - Vendas, Carrinho                    │
│   - Serviços (banho, tosa)              │
└─────────────────────────────────────────┘
```

### Princípios

1. **Clínica Veterinária = Base:** Sempre ativa, funcionalidade principal
2. **Petshop = Add-on:** Módulo opcional que pode ser ativado por valor adicional
3. **Pagamento Separado:** Cada módulo tem seu próprio preço no Stripe
4. **Ativação Dinâmica:** Módulos são ativados/desativados via Subscription Items

---

## 🏗️ ARQUITETURA TÉCNICA

### 1. Estrutura de Banco de Dados

#### Tabela: `modules`
Armazena informações sobre os módulos disponíveis:

```sql
CREATE TABLE `modules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL COMMENT 'Código único (clinic, petshop)',
  `name` VARCHAR(255) NOT NULL COMMENT 'Nome do módulo',
  `description` TEXT COMMENT 'Descrição',
  `is_base` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Se é módulo base (sempre ativo)',
  `stripe_price_id_monthly` VARCHAR(255) COMMENT 'Price ID mensal no Stripe',
  `stripe_price_id_yearly` VARCHAR(255) COMMENT 'Price ID anual no Stripe',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Se está disponível',
  `metadata` JSON COMMENT 'Metadados adicionais',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`),
  KEY `idx_is_base` (`is_base`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Tabela: `tenant_modules`
Vincula módulos aos tenants (quais módulos cada tenant tem ativo):

```sql
CREATE TABLE `tenant_modules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL,
  `module_id` INT(11) UNSIGNED NOT NULL,
  `stripe_subscription_item_id` VARCHAR(255) COMMENT 'ID do Subscription Item no Stripe',
  `status` ENUM('active', 'inactive', 'cancelled', 'pending') NOT NULL DEFAULT 'pending',
  `activated_at` TIMESTAMP NULL COMMENT 'Data de ativação',
  `cancelled_at` TIMESTAMP NULL COMMENT 'Data de cancelamento',
  `metadata` JSON COMMENT 'Metadados',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tenant_module` (`tenant_id`, `module_id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_module_id` (`module_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_tenant_modules_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_modules_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Models

#### `App/Models/Module.php`

```php
<?php

namespace App\Models;

use App\Core\BaseModel;

class Module extends BaseModel
{
    protected string $table = 'modules';
    
    /**
     * Busca módulo por código
     */
    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE code = :code AND is_active = 1
        ");
        $stmt->execute(['code' => $code]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Lista módulos disponíveis (não base)
     */
    public function findAvailableAddons(): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE is_base = 0 AND is_active = 1
            ORDER BY name
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca módulo base
     */
    public function findBaseModule(): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE is_base = 1 AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
```

#### `App/Models/TenantModule.php`

```php
<?php

namespace App\Models;

use App\Core\BaseModel;

class TenantModule extends BaseModel
{
    protected string $table = 'tenant_modules';
    
    /**
     * Verifica se tenant tem módulo ativo
     */
    public function hasActiveModule(int $tenantId, string $moduleCode): bool
    {
        $stmt = $this->db->prepare("
            SELECT tm.* FROM {$this->table} tm
            INNER JOIN modules m ON tm.module_id = m.id
            WHERE tm.tenant_id = :tenant_id 
            AND m.code = :module_code
            AND tm.status = 'active'
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'module_code' => $moduleCode
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Lista módulos ativos do tenant
     */
    public function findActiveByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, tm.status, tm.activated_at, tm.stripe_subscription_item_id
            FROM {$this->table} tm
            INNER JOIN modules m ON tm.module_id = m.id
            WHERE tm.tenant_id = :tenant_id 
            AND tm.status = 'active'
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Ativa módulo para tenant
     */
    public function activateModule(
        int $tenantId, 
        int $moduleId, 
        string $stripeSubscriptionItemId
    ): int {
        // Verifica se já existe
        $existing = $this->db->prepare("
            SELECT id FROM {$this->table}
            WHERE tenant_id = :tenant_id AND module_id = :module_id
        ");
        $existing->execute(['tenant_id' => $tenantId, 'module_id' => $moduleId]);
        $row = $existing->fetch(\PDO::FETCH_ASSOC);
        
        if ($row) {
            // Atualiza existente
            $stmt = $this->db->prepare("
                UPDATE {$this->table}
                SET status = 'active',
                    stripe_subscription_item_id = :stripe_id,
                    activated_at = NOW(),
                    cancelled_at = NULL
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $row['id'],
                'stripe_id' => $stripeSubscriptionItemId
            ]);
            return (int)$row['id'];
        } else {
            // Cria novo
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} 
                (tenant_id, module_id, stripe_subscription_item_id, status, activated_at)
                VALUES (:tenant_id, :module_id, :stripe_id, 'active', NOW())
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'module_id' => $moduleId,
                'stripe_id' => $stripeSubscriptionItemId
            ]);
            return (int)$this->db->lastInsertId();
        }
    }
    
    /**
     * Desativa módulo
     */
    public function deactivateModule(int $tenantId, int $moduleId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'cancelled',
                cancelled_at = NOW()
            WHERE tenant_id = :tenant_id AND module_id = :module_id
        ");
        return $stmt->execute([
            'tenant_id' => $tenantId,
            'module_id' => $moduleId
        ]);
    }
}
```

### 3. Service: `ModuleService.php`

```php
<?php

namespace App\Services;

use App\Models\Module;
use App\Models\TenantModule;
use App\Models\Subscription;
use App\Services\StripeService;
use App\Services\Logger;

class ModuleService
{
    private Module $moduleModel;
    private TenantModule $tenantModuleModel;
    private Subscription $subscriptionModel;
    private StripeService $stripeService;
    
    public function __construct(
        Module $moduleModel,
        TenantModule $tenantModuleModel,
        Subscription $subscriptionModel,
        StripeService $stripeService
    ) {
        $this->moduleModel = $moduleModel;
        $this->tenantModuleModel = $tenantModuleModel;
        $this->subscriptionModel = $subscriptionModel;
        $this->stripeService = $stripeService;
    }
    
    /**
     * Adiciona módulo opcional ao tenant (cria Subscription Item)
     */
    public function addModuleToTenant(
        int $tenantId, 
        string $moduleCode, 
        string $billingInterval = 'month'
    ): array {
        // 1. Busca módulo
        $module = $this->moduleModel->findByCode($moduleCode);
        if (!$module) {
            throw new \RuntimeException("Módulo '{$moduleCode}' não encontrado");
        }
        
        // 2. Verifica se já está ativo
        if ($this->tenantModuleModel->hasActiveModule($tenantId, $moduleCode)) {
            throw new \RuntimeException("Módulo '{$moduleCode}' já está ativo para este tenant");
        }
        
        // 3. Busca assinatura ativa do tenant
        $subscription = $this->subscriptionModel->findActiveByTenant($tenantId);
        if (!$subscription) {
            throw new \RuntimeException("Tenant não possui assinatura ativa");
        }
        
        // 4. Seleciona price_id baseado no intervalo
        $priceId = $billingInterval === 'year' 
            ? $module['stripe_price_id_yearly']
            : $module['stripe_price_id_monthly'];
            
        if (!$priceId) {
            throw new \RuntimeException("Price ID não configurado para módulo '{$moduleCode}'");
        }
        
        // 5. Cria Subscription Item no Stripe
        $subscriptionItem = $this->stripeService->createSubscriptionItem(
            $subscription['stripe_subscription_id'],
            [
                'price_id' => $priceId,
                'metadata' => [
                    'tenant_id' => $tenantId,
                    'module_code' => $moduleCode,
                    'module_id' => $module['id']
                ]
            ]
        );
        
        // 6. Ativa módulo no banco
        $this->tenantModuleModel->activateModule(
            $tenantId,
            $module['id'],
            $subscriptionItem->id
        );
        
        Logger::info("Módulo adicionado ao tenant", [
            'tenant_id' => $tenantId,
            'module_code' => $moduleCode,
            'subscription_item_id' => $subscriptionItem->id
        ]);
        
        return [
            'module' => $module,
            'subscription_item' => [
                'id' => $subscriptionItem->id,
                'price_id' => $priceId
            ]
        ];
    }
    
    /**
     * Remove módulo opcional do tenant (remove Subscription Item)
     */
    public function removeModuleFromTenant(int $tenantId, string $moduleCode): bool
    {
        // 1. Busca módulo
        $module = $this->moduleModel->findByCode($moduleCode);
        if (!$module) {
            throw new \RuntimeException("Módulo '{$moduleCode}' não encontrado");
        }
        
        // 2. Busca tenant_module
        $tenantModule = $this->tenantModuleModel->findByTenantAndModule($tenantId, $module['id']);
        if (!$tenantModule || $tenantModule['status'] !== 'active') {
            throw new \RuntimeException("Módulo '{$moduleCode}' não está ativo para este tenant");
        }
        
        // 3. Remove Subscription Item no Stripe
        if ($tenantModule['stripe_subscription_item_id']) {
            $this->stripeService->deleteSubscriptionItem(
                $tenantModule['stripe_subscription_item_id']
            );
        }
        
        // 4. Desativa módulo no banco
        $this->tenantModuleModel->deactivateModule($tenantId, $module['id']);
        
        Logger::info("Módulo removido do tenant", [
            'tenant_id' => $tenantId,
            'module_code' => $moduleCode
        ]);
        
        return true;
    }
    
    /**
     * Verifica se tenant tem módulo ativo
     */
    public function hasModule(int $tenantId, string $moduleCode): bool
    {
        return $this->tenantModuleModel->hasActiveModule($tenantId, $moduleCode);
    }
    
    /**
     * Lista módulos disponíveis para adicionar
     */
    public function getAvailableModules(int $tenantId): array
    {
        $allModules = $this->moduleModel->findAvailableAddons();
        $activeModules = $this->tenantModuleModel->findActiveByTenant($tenantId);
        $activeModuleCodes = array_column($activeModules, 'code');
        
        return array_map(function($module) use ($activeModuleCodes) {
            $module['is_active'] = in_array($module['code'], $activeModuleCodes);
            return $module;
        }, $allModules);
    }
}
```

### 4. Middleware: `ModuleMiddleware.php`

```php
<?php

namespace App\Middleware;

use App\Services\ModuleService;
use App\Utils\ResponseHelper;
use Flight;

/**
 * Middleware para verificar se tenant tem módulo ativo
 * 
 * Usado em rotas que requerem módulos opcionais (ex: petshop)
 */
class ModuleMiddleware
{
    private ModuleService $moduleService;
    private string $requiredModule;
    
    public function __construct(ModuleService $moduleService, string $requiredModule)
    {
        $this->moduleService = $moduleService;
        $this->requiredModule = $requiredModule;
    }
    
    /**
     * Verifica se tenant tem módulo ativo
     */
    public function check(): ?array
    {
        $tenantId = Flight::get('tenant_id');
        
        if (!$tenantId) {
            return ResponseHelper::sendUnauthorizedError('Não autenticado');
        }
        
        // Master key sempre tem acesso
        if (Flight::get('is_master') === true) {
            return null;
        }
        
        if (!$this->moduleService->hasModule($tenantId, $this->requiredModule)) {
            return [
                'error' => true,
                'message' => "Módulo '{$this->requiredModule}' não está ativo. Ative o módulo para acessar esta funcionalidade.",
                'code' => 'MODULE_NOT_ACTIVE',
                'http_code' => 402, // Payment Required
                'module_code' => $this->requiredModule
            ];
        }
        
        return null;
    }
}
```

### 5. Controller: `ModuleController.php`

```php
<?php

namespace App\Controllers;

use App\Services\ModuleService;
use App\Utils\ResponseHelper;
use App\Utils\PermissionHelper;
use Flight;

class ModuleController
{
    private ModuleService $moduleService;
    
    public function __construct(ModuleService $moduleService)
    {
        $this->moduleService = $moduleService;
    }
    
    /**
     * Lista módulos disponíveis
     * GET /v1/modules
     */
    public function list(): void
    {
        try {
            PermissionHelper::require('view_modules');
            $tenantId = Flight::get('tenant_id');
            
            $modules = $this->moduleService->getAvailableModules($tenantId);
            
            ResponseHelper::sendSuccess($modules, 'Módulos listados com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Adiciona módulo ao tenant
     * POST /v1/modules/:code/activate
     */
    public function activate(string $code): void
    {
        try {
            PermissionHelper::require('manage_modules');
            $tenantId = Flight::get('tenant_id');
            $data = Flight::request()->data->getData();
            
            $billingInterval = $data['billing_interval'] ?? 'month';
            
            $result = $this->moduleService->addModuleToTenant(
                $tenantId,
                $code,
                $billingInterval
            );
            
            ResponseHelper::sendSuccess($result, 'Módulo ativado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendError($e->getMessage(), 400);
        }
    }
    
    /**
     * Remove módulo do tenant
     * POST /v1/modules/:code/deactivate
     */
    public function deactivate(string $code): void
    {
        try {
            PermissionHelper::require('manage_modules');
            $tenantId = Flight::get('tenant_id');
            
            $this->moduleService->removeModuleFromTenant($tenantId, $code);
            
            ResponseHelper::sendSuccess(null, 'Módulo desativado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendError($e->getMessage(), 400);
        }
    }
}
```

---

## 🔄 FLUXO DE FUNCIONAMENTO

### 1. Configuração Inicial (Setup)

```sql
-- Inserir módulo base (Clínica Veterinária)
INSERT INTO modules (code, name, description, is_base, is_active) VALUES
('clinic', 'Clínica Veterinária', 'Módulo base - sempre ativo', TRUE, TRUE);

-- Inserir módulo opcional (Petshop)
INSERT INTO modules (code, name, description, is_base, is_active, stripe_price_id_monthly, stripe_price_id_yearly) VALUES
('petshop', 'Petshop', 'Módulo opcional - gestão de produtos e vendas', FALSE, TRUE, 'price_XXXXX', 'price_YYYYY');
```

### 2. Tenant Assina Plano Base

```
1. Tenant cria conta
2. Assina plano base (Clínica Veterinária)
3. Sistema cria Subscription no Stripe
4. Módulo base é automaticamente ativado
```

### 3. Tenant Adiciona Petshop

```
1. Tenant acessa "Módulos" no painel
2. Vê "Petshop" disponível (não ativo)
3. Clica em "Ativar Petshop"
4. Sistema:
   a. Busca assinatura ativa do tenant
   b. Cria Subscription Item no Stripe (com price_id do petshop)
   c. Ativa módulo no banco (tenant_modules)
5. Rotas de petshop ficam disponíveis
```

### 4. Uso do Sistema

```
Tenant com Clínica + Petshop:
├── Rotas de Clínica: ✅ Sempre disponíveis
├── Rotas de Petshop: ✅ Disponíveis (módulo ativo)
└── Middleware verifica: hasModule('petshop') antes de acessar rotas

Tenant só com Clínica:
├── Rotas de Clínica: ✅ Sempre disponíveis
├── Rotas de Petshop: ❌ Bloqueadas (ModuleMiddleware retorna 402)
└── Usuário vê mensagem: "Ative o módulo Petshop para acessar"
```

### 5. Cancelamento de Módulo

```
1. Tenant cancela módulo Petshop
2. Sistema:
   a. Remove Subscription Item no Stripe
   b. Marca módulo como 'cancelled' no banco
3. Rotas de petshop ficam bloqueadas
4. Dados permanecem no banco (soft delete)
```

---

## 🛣️ REGISTRO DE ROTAS

### Rotas de Módulos

```php
// public/index.php

// API - Gerenciamento de Módulos
Flight::route('GET /v1/modules', [$moduleController, 'list']);
Flight::route('POST /v1/modules/:code/activate', [$moduleController, 'activate']);
Flight::route('POST /v1/modules/:code/deactivate', [$moduleController, 'deactivate']);
```

### Rotas de Petshop (com Middleware)

```php
// Rotas de Petshop - requerem módulo ativo
$petshopMiddleware = new ModuleMiddleware($moduleService, 'petshop');

Flight::route('GET /v1/petshop/products', [$petshopController, 'list'])
    ->addMiddleware($petshopMiddleware->check());

Flight::route('POST /v1/petshop/products', [$petshopController, 'create'])
    ->addMiddleware($petshopMiddleware->check());
```

---

## 📊 ESTRUTURA DE DIRETÓRIOS

```
App/
├── Models/
│   ├── Module.php              # Model de módulos
│   └── TenantModule.php         # Model de tenant_modules
├── Services/
│   └── ModuleService.php       # Service de gerenciamento
├── Controllers/
│   ├── ModuleController.php    # CRUD de módulos
│   └── PetshopController.php   # Controller do petshop
├── Middleware/
│   └── ModuleMiddleware.php    # Verificação de módulo
└── Modules/                     # (Futuro) Módulos isolados
    └── Petshop/
        ├── Controllers/
        ├── Models/
        └── Views/
```

---

## 🎯 VANTAGENS DESTA ARQUITETURA

1. **Flexibilidade:** Tenants pagam apenas pelo que usam
2. **Escalabilidade:** Fácil adicionar novos módulos (ex: hotel para pets)
3. **Isolamento:** Cada módulo é independente
4. **Integração Stripe:** Pagamento automático via Subscription Items
5. **Controle de Acesso:** Middleware bloqueia rotas automaticamente
6. **Histórico:** Mantém registro de ativações/cancelamentos

---

## 📝 PRÓXIMOS PASSOS

1. ✅ Criar migrations para `modules` e `tenant_modules`
2. ✅ Criar Models (`Module`, `TenantModule`)
3. ✅ Criar Service (`ModuleService`)
4. ✅ Criar Controller (`ModuleController`)
5. ✅ Criar Middleware (`ModuleMiddleware`)
6. ✅ Registrar rotas
7. ✅ Criar interface de gerenciamento de módulos (view)
8. ✅ Implementar módulo Petshop completo
9. ✅ Integrar com webhooks do Stripe (atualização automática)

---

**Última Atualização:** 2025-12-09

