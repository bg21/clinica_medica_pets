# Gerenciar Planos e Módulos via Banco de Dados

**Data:** 2025-12-10  
**Status:** ✅ Implementado

---

## 📋 Resumo

Agora você pode gerenciar planos e módulos **diretamente no banco de dados**, sem precisar editar código PHP!

## 🚀 Como Ativar

### 1. Executar Migration

Execute a migration para criar as tabelas:

```bash
vendor/bin/phinx migrate
```

### 2. Popular Dados Iniciais

Execute o seed para popular com os dados do `App/Config/plans.php`:

```bash
vendor/bin/phinx seed:run -s SeedPlansAndModules
```

Isso irá:
- Criar todas as tabelas (`modules`, `plans`, `plan_modules`)
- Popular com os dados do arquivo `App/Config/plans.php`
- Criar os relacionamentos entre planos e módulos

---

## 📊 Estrutura das Tabelas

### Tabela `modules`
Armazena todos os módulos disponíveis no sistema.

**Campos:**
- `id` - ID único
- `module_id` - ID único do módulo (ex: 'vaccines')
- `name` - Nome do módulo
- `description` - Descrição
- `icon` - Ícone Bootstrap Icons
- `is_active` - Se está ativo
- `sort_order` - Ordem de exibição

### Tabela `plans`
Armazena todos os planos disponíveis.

**Campos:**
- `id` - ID único
- `plan_id` - ID único do plano (ex: 'basic')
- `name` - Nome do plano
- `description` - Descrição
- `monthly_price` - Preço mensal (em centavos)
- `yearly_price` - Preço anual (em centavos)
- `max_users` - Limite de usuários (null = ilimitado)
- `features` - Array de features (JSON)
- `stripe_price_id_monthly` - Price ID do Stripe (mensal)
- `stripe_price_id_yearly` - Price ID do Stripe (anual)
- `is_active` - Se está ativo
- `sort_order` - Ordem de exibição

### Tabela `plan_modules`
Relacionamento muitos-para-muitos entre planos e módulos.

**Campos:**
- `plan_id` - ID do plano
- `module_id` - ID do módulo

---

## 🎯 Como Gerenciar

### Opção 1: Via Interface Administrativa (Recomendado)

**Em desenvolvimento** - Será criada uma interface web para gerenciar planos e módulos.

### Opção 2: Via SQL Direto

Você pode editar diretamente no banco de dados:

#### Adicionar módulo a um plano:

```sql
-- 1. Encontre o ID do plano
SELECT id, plan_id, name FROM plans WHERE plan_id = 'basic';

-- 2. Encontre o ID do módulo
SELECT id, module_id, name FROM modules WHERE module_id = 'vaccines';

-- 3. Adicione o relacionamento
INSERT INTO plan_modules (plan_id, module_id) 
VALUES (1, 5); -- Substitua pelos IDs reais
```

#### Remover módulo de um plano:

```sql
DELETE FROM plan_modules 
WHERE plan_id = 1 AND module_id = 5;
```

#### Criar novo plano:

```sql
INSERT INTO plans (
    plan_id, name, description, monthly_price, yearly_price,
    max_users, features, is_active
) VALUES (
    'novo_plano',
    'Novo Plano',
    'Descrição do novo plano',
    9900,  -- R$ 99,00 em centavos
    99000, -- R$ 990,00 em centavos
    3,     -- 3 usuários
    '["Feature 1", "Feature 2"]', -- JSON array
    1      -- Ativo
);
```

#### Criar novo módulo:

```sql
INSERT INTO modules (
    module_id, name, description, icon, is_active
) VALUES (
    'novo_modulo',
    'Novo Módulo',
    'Descrição do módulo',
    'bi-icon-name',
    1
);
```

### Opção 3: Via Models PHP

Use os Models `App\Models\Plan` e `App\Models\Module`:

```php
use App\Models\Plan;
use App\Models\Module;

// Criar módulo
$moduleModel = new Module();
$moduleId = $moduleModel->create([
    'module_id' => 'novo_modulo',
    'name' => 'Novo Módulo',
    'description' => 'Descrição',
    'icon' => 'bi-icon'
]);

// Criar plano
$planModel = new Plan();
$planId = $planModel->create([
    'plan_id' => 'novo_plano',
    'name' => 'Novo Plano',
    'monthly_price' => 9900,
    'max_users' => 3
]);

// Adicionar módulo ao plano
$planModel->addModule($planId, $moduleId);
```

---

## ⚙️ Atualizar PlanLimitsService

O `PlanLimitsService` precisa ser atualizado para ler do banco de dados ao invés do arquivo PHP.

**Status:** ⚠️ Em desenvolvimento

Quando atualizado, o sistema automaticamente:
- Lerá planos e módulos do banco de dados
- Invalidará cache quando houver mudanças
- Funcionará normalmente com a nova estrutura

---

## 📝 Notas Importantes

1. **Compatibilidade:** O arquivo `App/Config/plans.php` ainda existe e pode ser usado como fallback
2. **Cache:** Após editar no banco, limpe o cache Redis ou aguarde 5 minutos
3. **Stripe:** Lembre-se de atualizar `stripe_price_id_monthly` e `stripe_price_id_yearly` após criar produtos no Stripe

---

**Última Atualização:** 2025-12-10

