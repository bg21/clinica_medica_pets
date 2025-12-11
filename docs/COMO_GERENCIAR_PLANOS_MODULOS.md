# Como Gerenciar Planos e Módulos - Guia Completo

**Data:** 2025-12-10  
**Status:** ✅ Implementado

---

## 🎯 Resumo

Agora você, como **dono do SaaS**, pode gerenciar planos e módulos **diretamente pela interface web**, sem precisar editar código!

---

## 🚀 Passo a Passo

### 1. Executar Migration e Seed

Primeiro, crie as tabelas e popule com dados iniciais:

```bash
# Criar tabelas
vendor/bin/phinx migrate

# Popular dados iniciais (do arquivo plans.php)
vendor/bin/phinx seed:run -s SeedPlansAndModules
```

### 2. Acessar Interface Administrativa

1. Faça login como **admin**
2. No menu lateral, vá em **"Administração"**
3. Clique em **"Planos e Módulos"**
4. Você será redirecionado para `/admin-plans`

---

## 📋 Interface Administrativa

### Aba "Planos"

Aqui você pode:

- ✅ **Ver todos os planos** cadastrados
- ✅ **Criar novo plano** (botão "Novo Plano")
- ✅ **Editar plano existente** (ícone de lápis)
- ✅ **Remover plano** (ícone de lixeira)
- ✅ **Vincular módulos** ao plano (ao criar/editar)

**Campos ao criar/editar plano:**
- **ID do Plano**: Identificador único (ex: `basic`, `professional`)
- **Nome**: Nome exibido (ex: "Básico", "Profissional")
- **Descrição**: Descrição do plano
- **Preço Mensal**: Em centavos (ex: 4900 = R$ 49,00)
- **Preço Anual**: Em centavos (ex: 49000 = R$ 490,00)
- **Limite de Usuários**: Número ou deixe vazio para ilimitado
- **Stripe Price IDs**: IDs dos preços criados no Stripe
- **Módulos**: Selecione quais módulos o plano terá acesso
- **Features**: Uma feature por linha

### Aba "Módulos"

Aqui você pode:

- ✅ **Ver todos os módulos** cadastrados
- ✅ **Criar novo módulo** (botão "Novo Módulo")
- ✅ **Editar módulo existente** (ícone de lápis)
- ✅ **Remover módulo** (ícone de lixeira)

**Campos ao criar/editar módulo:**
- **ID do Módulo**: Identificador único (ex: `vaccines`, `hospitalization`)
- **Nome**: Nome exibido (ex: "Vacinas", "Internação")
- **Descrição**: Descrição do módulo
- **Ícone**: Bootstrap Icons (ex: `bi-shield-check`, `bi-hospital`)

---

## ✅ Validação Automática

O sistema **automaticamente valida** se o dono da clínica tem acesso a um módulo:

### Como Funciona

1. **Quando o dono da clínica acessa um módulo** (ex: `/clinic/vaccines`)
2. **O sistema verifica:**
   - Qual plano ele tem assinado
   - Quais módulos estão disponíveis nesse plano
   - Se o módulo acessado está na lista

3. **Se não tiver acesso:**
   - Retorna erro 403
   - Mostra mensagem: "O módulo 'Vacinas' não está disponível no seu plano atual"
   - Sugere upgrade

### Exemplo de Uso

```php
// No código, você pode verificar assim:
$planLimitsService = new PlanLimitsService();

// Verifica se tem acesso ao módulo "vaccines"
if (!$planLimitsService->hasModule($tenantId, 'vaccines')) {
    // Bloqueia acesso ou mostra mensagem
}
```

---

## 🔧 Usando Middleware

Para bloquear automaticamente acesso a módulos:

```php
// No public/index.php
$moduleMiddleware = new ModuleAccessMiddleware();

// Bloqueia acesso a /clinic/vaccines se não tiver o módulo
$app->before('GET|POST|PUT|DELETE', '/clinic/vaccines*', function() use ($moduleMiddleware) {
    $check = $moduleMiddleware->check('vaccines');
    if ($check) {
        Flight::json($check, 403);
        Flight::stop();
    }
});
```

---

## 📊 Fluxo Completo

### 1. Você cria módulos
- Acessa `/admin-plans`
- Vai na aba "Módulos"
- Cria módulos (ex: "Vacinas", "Internação")

### 2. Você cria planos
- Vai na aba "Planos"
- Cria planos (ex: "Básico", "Premium")
- Vincula módulos a cada plano

### 3. Clínica assina plano
- Clínica acessa `/my-subscription`
- Escolhe um plano
- Faz checkout no Stripe

### 4. Sistema valida automaticamente
- Quando clínica tenta acessar `/clinic/vaccines`
- Sistema verifica se o plano dela tem o módulo "vaccines"
- Permite ou bloqueia acesso

---

## 🎯 Endpoints API Disponíveis

### Para Você (Admin)

- `GET /v1/admin/plans` - Lista todos os planos
- `GET /v1/admin/plans/:id` - Detalhes de um plano
- `POST /v1/admin/plans` - Cria novo plano
- `PUT /v1/admin/plans/:id` - Atualiza plano
- `DELETE /v1/admin/plans/:id` - Remove plano
- `GET /v1/admin/modules` - Lista todos os módulos
- `POST /v1/admin/modules` - Cria novo módulo
- `PUT /v1/admin/modules/:id` - Atualiza módulo
- `DELETE /v1/admin/modules/:id` - Remove módulo

### Para Clínicas

- `GET /v1/plan-limits` - Limites do plano atual
- `GET /v1/plan-limits/modules` - Módulos disponíveis
- `GET /v1/plan-limits/check-module/:moduleId` - Verifica se módulo está disponível

---

## ⚠️ Importante

1. **Stripe Price IDs**: Após criar produtos/preços no Stripe, atualize os `stripe_price_id_monthly` e `stripe_price_id_yearly` nos planos
2. **Cache**: Mudanças são aplicadas imediatamente (cache de 5 minutos)
3. **Validação**: O sistema valida automaticamente acesso a módulos quando aplicado o `ModuleAccessMiddleware`

---

## 🎉 Pronto!

Agora você pode:
- ✅ Criar módulos pela interface web
- ✅ Criar planos pela interface web
- ✅ Vincular módulos aos planos
- ✅ O sistema valida automaticamente o acesso

**Tudo sem precisar editar código!**

---

**Última Atualização:** 2025-12-10

