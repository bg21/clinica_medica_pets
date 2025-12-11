# Implementação de Planos e Módulos - Guia Técnico

**Data:** 2025-12-10  
**Status:** ✅ Implementado

---

## 📋 Resumo

Foi implementado um sistema flexível e editável de planos e módulos que permite:

- ✅ Definir planos e módulos em um arquivo de configuração (`App/Config/plans.php`)
- ✅ Verificar acesso a módulos por plano
- ✅ Middleware para bloquear acesso a módulos não disponíveis
- ✅ Endpoints API para consultar planos e módulos
- ✅ Fácil edição: modifique `plans.php` para alterar planos/módulos

---

## 🗂️ Estrutura de Arquivos

```
App/
├── Config/
│   └── plans.php                    # ✅ Configuração de planos e módulos (EDITÁVEL)
├── Services/
│   └── PlanLimitsService.php        # ✅ Service para verificar limites e módulos
├── Middleware/
│   └── ModuleAccessMiddleware.php   # ✅ Middleware para verificar acesso a módulos
└── Controllers/
    └── PlanLimitsController.php     # ✅ Controller com endpoints API
```

---

## 📝 Arquivo de Configuração: `App/Config/plans.php`

Este é o arquivo principal que você deve editar para modificar planos, valores e módulos.

### Estrutura

```php
return [
    'modules' => [
        'vaccines' => [
            'id' => 'vaccines',
            'name' => 'Vacinas',
            'description' => 'Controle de vacinação',
            'icon' => 'bi-shield-check'
        ],
        // ... outros módulos
    ],
    
    'plans' => [
        'basic' => [
            'id' => 'basic',
            'name' => 'Básico',
            'monthly_price' => 4900,  // R$ 49,00 em centavos
            'yearly_price' => 49000,  // R$ 490,00 em centavos
            'max_users' => 1,
            'modules' => ['customers', 'pets', 'appointments'],
            'stripe_price_ids' => [
                'monthly' => null,  // Preencher quando criar no Stripe
                'yearly' => null
            ]
        ],
        // ... outros planos
    ],
    
    'settings' => [
        'trial_days' => 14,
        'yearly_discount_percentage' => 17
    ]
];
```

### Como Editar

1. **Adicionar um novo módulo:**
   ```php
   'modules' => [
       'novo_modulo' => [
           'id' => 'novo_modulo',
           'name' => 'Novo Módulo',
           'description' => 'Descrição do módulo',
           'icon' => 'bi-icon-name'
       ]
   ]
   ```

2. **Adicionar um novo plano:**
   ```php
   'plans' => [
       'novo_plano' => [
           'id' => 'novo_plano',
           'name' => 'Novo Plano',
           'monthly_price' => 9900,  // R$ 99,00
           'modules' => ['customers', 'pets', 'novo_modulo']
       ]
   ]
   ```

3. **Alterar valores:**
   ```php
   'monthly_price' => 5900,  // Altera para R$ 59,00
   ```

4. **Adicionar módulo a um plano:**
   ```php
   'modules' => ['customers', 'pets', 'vaccines']  // Adiciona 'vaccines'
   ```

---

## 🔧 Uso do Sistema

### 1. Verificar se um módulo está disponível

```php
$planLimitsService = new PlanLimitsService();
$hasModule = $planLimitsService->hasModule($tenantId, 'vaccines');

if (!$hasModule) {
    // Módulo não disponível
}
```

### 2. Obter módulos disponíveis

```php
$modules = $planLimitsService->getAvailableModules($tenantId);
// Retorna array com informações dos módulos
```

### 3. Usar Middleware em Rotas

```php
// No public/index.php
$moduleMiddleware = new ModuleAccessMiddleware();

$app->before('GET|POST|PUT|DELETE', '/clinic/vaccines*', function() use ($moduleMiddleware) {
    $check = $moduleMiddleware->check('vaccines');
    if ($check) {
        Flight::json($check, 403);
        Flight::stop();
    }
});
```

### 4. Endpoints API

- `GET /v1/plan-limits` - Limites do plano atual
- `GET /v1/plan-limits/plans` - Todos os planos disponíveis
- `GET /v1/plan-limits/modules` - Módulos disponíveis no plano atual
- `GET /v1/plan-limits/check-module/:moduleId` - Verifica se módulo está disponível

---

## 🔗 Mapeamento Stripe

Para que o sistema funcione corretamente, você precisa mapear os `price_id` do Stripe aos planos:

1. Crie os produtos e preços no Stripe
2. Copie os `price_id` (ex: `price_1ABC...`)
3. Atualize `App/Config/plans.php`:

```php
'stripe_price_ids' => [
    'monthly' => 'price_1ABC...',  // Price ID mensal do Stripe
    'yearly' => 'price_1XYZ...'    // Price ID anual do Stripe
]
```

---

## 📊 Planos Atuais (Implementados)

### Básico
- **Valor:** R$ 49,00/mês ou R$ 490,00/ano
- **Usuários:** 1
- **Módulos:** Clientes, Pacientes, Agenda, Atendimentos

### Profissional
- **Valor:** R$ 99,00/mês ou R$ 990,00/ano
- **Usuários:** 3
- **Módulos:** Todos do Básico + Vacinas, Exames, Receitas, Relatórios

### Premium
- **Valor:** R$ 199,00/mês ou R$ 1.990,00/ano
- **Usuários:** 6
- **Módulos:** Todos do Profissional + Internação, Financeiro, Vendas, Documentos, Produtos, Usuários

### Enterprise
- **Valor:** R$ 399,00/mês ou R$ 3.990,00/ano
- **Usuários:** Ilimitado
- **Módulos:** Todos os módulos (incluindo Fiscal)

---

## ⚠️ Importante

1. **Cache:** O sistema usa cache (5 minutos). Após editar `plans.php`, pode ser necessário limpar o cache.

2. **Stripe Price IDs:** Após criar produtos/preços no Stripe, atualize os `stripe_price_ids` em `plans.php`.

3. **Compatibilidade:** O sistema mantém compatibilidade com planos antigos (mapeamento legado).

4. **Valores em Centavos:** Todos os preços devem ser em centavos (ex: R$ 49,00 = 4900).

---

## 🚀 Próximos Passos

1. Criar produtos e preços no Stripe
2. Atualizar `stripe_price_ids` em `plans.php`
3. Aplicar `ModuleAccessMiddleware` nas rotas dos módulos
4. Testar verificação de acesso
5. Criar interface para visualizar planos e módulos

---

**Última Atualização:** 2025-12-10

