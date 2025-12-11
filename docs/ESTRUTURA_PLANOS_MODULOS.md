# Estrutura de Planos e Módulos - Sistema Clínica Veterinária

**Data:** 2025-12-10  
**Sistema:** Clínica Médica - SaaS  
**Status:** 📋 Documentação de Planejamento

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Módulos Disponíveis](#módulos-disponíveis)
3. [Estrutura de Planos Proposta](#estrutura-de-planos-proposta)
4. [Mapeamento de Módulos por Plano](#mapeamento-de-módulos-por-plano)
5. [Valores dos Planos](#valores-dos-planos)
6. [Implementação Técnica](#implementação-técnica)
7. [Middleware de Verificação](#middleware-de-verificação)
8. [Próximos Passos](#próximos-passos)

---

## Visão Geral

O sistema precisa de uma estrutura clara de planos que defina quais módulos/recursos estão disponíveis em cada plano de assinatura. Atualmente, o sistema possui um `PlanLimitsService` básico que apenas controla limites de usuários, mas **não há controle de módulos específicos**.

### Situação Atual

✅ **Implementado:**
- `PlanLimitsService` com controle de `max_users`
- Sistema de assinaturas via Stripe
- Middleware de verificação de assinatura ativa

❌ **Não Implementado:**
- Controle de módulos por plano (ex: Vacinas só no plano X)
- Verificação de acesso a módulos específicos
- Documentação da estrutura de planos
- Interface para visualizar módulos disponíveis por plano

---

## Módulos Disponíveis

Baseado na análise do código, os seguintes módulos foram identificados:

### 1. **Módulo de Clientes** (`customers`)
- Gerenciamento de clientes (tutores)
- CRUD completo de clientes
- Histórico de clientes

### 2. **Módulo de Pacientes** (`pets`)
- Gerenciamento de pets/animais
- Cadastro de pets vinculados a clientes
- Histórico médico dos pets

### 3. **Módulo de Agenda** (`appointments`)
- Agendamento de consultas
- Calendário de atendimentos
- Tipos de atendimento (consulta, vacinação, etc.)

### 4. **Módulo de Vacinas** (`vaccines`)
- Controle de vacinação
- Carteira de vacinação
- Relatórios de vacinas pendentes
- Lembretes de vacinação

### 5. **Módulo de Internação** (`hospitalization`)
- Controle de internações
- Leitos e quartos
- Acompanhamento de pacientes internados

### 6. **Módulo Financeiro** (`financial`)
- Controle financeiro
- Receitas e despesas
- Relatórios financeiros
- Integração com pagamentos

### 7. **Módulo de Vendas** (`sales`)
- Vendas de produtos e serviços
- Carrinho de compras
- Notas fiscais

### 8. **Módulo de Atendimentos** (`services`)
- Registro de atendimentos
- Prontuários eletrônicos
- Histórico de atendimentos

### 9. **Módulo de Exames** (`exams`)
- Controle de exames
- Resultados de exames
- Laudos

### 10. **Módulo de Receitas** (`prescriptions`)
- Prescrições médicas
- Receitas veterinárias
- Controle de medicamentos

### 11. **Módulo de Documentos** (`documents`)
- Armazenamento de documentos
- Documentos dos pets
- Certificados

### 12. **Módulo de Produtos & Serviços** (`products`)
- Cadastro de produtos
- Cadastro de serviços
- Catálogo

### 13. **Módulo de Relatórios** (`reports`)
- Relatórios gerenciais
- Relatórios de vacinações
- Relatórios financeiros
- Exportação de dados

### 14. **Módulo de Gerenciamento de Usuários** (`users`)
- Gestão de usuários
- Permissões e papéis
- Controle de acesso

### 15. **Módulo Fiscal** (`fiscal`) ⚠️
- Integração fiscal
- Emissão de notas fiscais
- Controle tributário
- **Nota:** Pode ser um módulo adicional (não incluído em planos básicos)

---

## Estrutura de Planos Proposta

### Opção 1: Baseada na Imagem (Loopvet)

Baseado na imagem fornecida, sugere-se a seguinte estrutura:

#### **PLANO START** (Básico)
- **Valor:** R$ 97,90/mês
- **Usuários:** 1 usuário
- **Módulos Incluídos:**
  - Clientes
  - Pacientes
  - Agenda (básica)
  - Atendimentos (básico)
  - Produtos & Serviços (básico)

#### **PLANO PLUS** (Intermediário)
- **Valor:** R$ 177,90/mês
- **Usuários:** 3 usuários
- **Módulos Incluídos:**
  - Todos do START
  - Vacinas
  - Exames
  - Receitas
  - Relatórios (básico)

#### **PLANO PRO** (Profissional)
- **Valor:** R$ 237,90/mês
- **Usuários:** 6 usuários
- **Módulos Incluídos:**
  - Todos do PLUS
  - Internação
  - Vendas
  - Documentos
  - Relatórios (completo)

#### **PLANO ULTRA** (Avançado)
- **Valor:** R$ 357,90/mês
- **Usuários:** 12 usuários
- **Módulos Incluídos:**
  - Todos do PRO
  - Financeiro (completo)
  - Gerenciamento de Usuários (avançado)

#### **PLANO PRIME** (Premium)
- **Valor:** R$ 497,90/mês
- **Usuários:** Ilimitado
- **Módulos Incluídos:**
  - Todos os módulos
  - Módulo Fiscal (opcional)
  - Suporte prioritário
  - Recursos avançados

### ✅ Opção 2: Estrutura Simplificada (IMPLEMENTADA)

#### **PLANO BÁSICO**
- **Valor:** R$ 49,00/mês (ou R$ 490,00/ano - 17% desconto)
- **Usuários:** 1 usuário
- **Módulos:**
  - Clientes
  - Pacientes
  - Agenda
  - Atendimentos básicos
- **Features:**
  - Atendimento básico
  - Gestão de clientes e pacientes
  - Agenda simples
  - Suporte por email

#### **PLANO PROFISSIONAL**
- **Valor:** R$ 99,00/mês (ou R$ 990,00/ano - 17% desconto)
- **Usuários:** 3 usuários
- **Módulos:**
  - Todos do Básico
  - Vacinas
  - Exames
  - Receitas
  - Relatórios
- **Features:**
  - Tudo do Básico
  - Controle de vacinação
  - Exames e receitas
  - Relatórios básicos
  - Suporte prioritário

#### **PLANO PREMIUM**
- **Valor:** R$ 199,00/mês (ou R$ 1.990,00/ano - 17% desconto)
- **Usuários:** 6 usuários
- **Módulos:**
  - Todos do Profissional
  - Internação
  - Financeiro
  - Vendas
  - Documentos
  - Produtos & Serviços
  - Gerenciamento de Usuários
- **Features:**
  - Tudo do Profissional
  - Controle de internações
  - Módulo financeiro completo
  - Vendas e produtos
  - Gestão de documentos
  - Relatórios avançados
  - Suporte prioritário 24/7

#### **PLANO ENTERPRISE**
- **Valor:** R$ 399,00/mês (ou R$ 3.990,00/ano - 17% desconto)
- **Usuários:** Ilimitado
- **Módulos:**
  - Todos os módulos (incluindo Fiscal)
- **Features:**
  - Tudo do Premium
  - Módulo fiscal completo
  - API avançada
  - Integrações personalizadas
  - Suporte dedicado
  - Treinamento personalizado
  - SLA garantido

---

## Mapeamento de Módulos por Plano

### Tabela de Disponibilidade de Módulos

| Módulo | Básico | Profissional | Premium | Enterprise |
|--------|--------|--------------|---------|------------|
| Clientes | ✅ | ✅ | ✅ | ✅ |
| Pacientes | ✅ | ✅ | ✅ | ✅ |
| Agenda | ✅ | ✅ | ✅ | ✅ |
| Atendimentos | ✅ (básico) | ✅ | ✅ | ✅ |
| Vacinas | ❌ | ✅ | ✅ | ✅ |
| Exames | ❌ | ✅ | ✅ | ✅ |
| Receitas | ❌ | ✅ | ✅ | ✅ |
| Internação | ❌ | ❌ | ✅ | ✅ |
| Financeiro | ❌ | ❌ | ✅ | ✅ |
| Vendas | ❌ | ❌ | ✅ | ✅ |
| Documentos | ❌ | ❌ | ✅ | ✅ |
| Relatórios | ❌ | ✅ (básico) | ✅ | ✅ |
| Produtos & Serviços | ✅ (básico) | ✅ | ✅ | ✅ |
| Gerenciamento de Usuários | ✅ (básico) | ✅ | ✅ | ✅ |
| Fiscal | ❌ | ❌ | ❌ | ✅ |

---

## Valores dos Planos

### Estrutura de Preços Proposta

#### **Opção 1: Valores da Imagem (Loopvet)**
```
START:    R$ 97,90/mês  (R$ 1.174,80/ano)
PLUS:     R$ 177,90/mês (R$ 2.134,80/ano)
PRO:      R$ 237,90/mês (R$ 2.854,80/ano)
ULTRA:    R$ 357,90/mês (R$ 4.294,80/ano)
PRIME:    R$ 497,90/mês (R$ 5.974,80/ano)
```

#### **Opção 2: Valores Simplificados (Recomendado)**
```
BÁSICO:       R$ 29,00/mês  (R$ 290,00/ano - 17% desconto)
PROFISSIONAL: R$ 69,00/mês  (R$ 690,00/ano - 17% desconto)
PREMIUM:      R$ 149,00/mês (R$ 1.490,00/ano - 17% desconto)
ENTERPRISE:   R$ 299,00/mês (R$ 2.990,00/ano - 17% desconto)
```

### Observações sobre Preços

1. **Desconto Anual:** Recomenda-se oferecer 15-20% de desconto para pagamento anual
2. **Teste Grátis:** Todos os planos devem ter período de teste (ex: 14-30 dias)
3. **Valores em Centavos:** No Stripe, valores devem ser em centavos (ex: R$ 29,00 = 2900 centavos)

---

## Implementação Técnica

### 1. Atualizar `PlanLimitsService`

O serviço atual precisa ser expandido para incluir módulos:

```php
private function getPlanLimits(string $priceId): array
{
    $planLimits = [
        'price_xxx_BASICO' => [
            'max_users' => 1,
            'modules' => [
                'customers' => true,
                'pets' => true,
                'appointments' => true,
                'services' => true,
                'vaccines' => false,
                'exams' => false,
                'prescriptions' => false,
                'hospitalization' => false,
                'financial' => false,
                'sales' => false,
                'documents' => false,
                'reports' => false,
                'fiscal' => false
            ],
            'plan_name' => 'Plano Básico',
            'billing_interval' => 'month'
        ],
        // ... outros planos
    ];
    
    return $planLimits[$priceId] ?? $this->getDefaultLimits();
}
```

### 2. Criar Middleware de Verificação de Módulo

```php
class ModuleAccessMiddleware
{
    public function check(string $module): ?array
    {
        $tenantId = Flight::get('tenant_id');
        $planLimitsService = new PlanLimitsService();
        $limits = $planLimitsService->getAllLimits($tenantId);
        
        if (!$limits['has_subscription']) {
            return ['error' => 'Assinatura necessária'];
        }
        
        $hasModule = $limits['limits']['modules'][$module] ?? false;
        
        if (!$hasModule) {
            return [
                'error' => true,
                'message' => "Módulo '{$module}' não disponível no seu plano",
                'code' => 'MODULE_NOT_AVAILABLE',
                'upgrade_url' => '/my-subscription'
            ];
        }
        
        return null; // Acesso permitido
    }
}
```

### 3. Aplicar Middleware nas Rotas

```php
// Exemplo: Rota de vacinas
$app->route('GET /clinic/vaccines', function() use ($app) {
    $moduleMiddleware = new ModuleAccessMiddleware();
    $check = $moduleMiddleware->check('vaccines');
    
    if ($check) {
        Flight::json($check, 403);
        return;
    }
    
    // Continua processamento...
});
```

---

## Middleware de Verificação

### Estrutura Proposta

1. **Verificação Global:** Middleware aplicado em todas as rotas de módulos
2. **Cache:** Cachear verificação de módulos por tenant (5 minutos)
3. **Logs:** Registrar tentativas de acesso a módulos não disponíveis
4. **Mensagens:** Mensagens amigáveis sugerindo upgrade

### Exemplo de Uso

```php
// No public/index.php
$moduleMiddleware = new \App\Middleware\ModuleAccessMiddleware();

// Rotas de vacinas
$app->before('GET|POST|PUT|DELETE', '/clinic/vaccines*', function() use ($moduleMiddleware) {
    $check = $moduleMiddleware->check('vaccines');
    if ($check) {
        Flight::json($check, 403);
        Flight::stop();
    }
});

// Rotas de internação
$app->before('GET|POST|PUT|DELETE', '/clinic/hospitalization*', function() use ($moduleMiddleware) {
    $check = $moduleMiddleware->check('hospitalization');
    if ($check) {
        Flight::json($check, 403);
        Flight::stop();
    }
});
```

---

## Próximos Passos

### Fase 1: Documentação e Planejamento ✅
- [x] Criar documentação de estrutura de planos
- [ ] Definir valores finais dos planos
- [ ] Definir quais módulos estarão em cada plano

### Fase 2: Implementação Técnica
- [ ] Atualizar `PlanLimitsService` com mapeamento de módulos
- [ ] Criar `ModuleAccessMiddleware`
- [ ] Aplicar middleware em todas as rotas de módulos
- [ ] Criar endpoint `/v1/plan-limits/modules` para frontend

### Fase 3: Interface do Usuário
- [ ] Criar página de comparação de planos
- [ ] Mostrar módulos disponíveis por plano
- [ ] Adicionar indicadores visuais de módulos bloqueados
- [ ] Criar modal de upgrade quando tentar acessar módulo bloqueado

### Fase 4: Testes e Validação
- [ ] Testar verificação de módulos em cada plano
- [ ] Validar mensagens de erro
- [ ] Testar fluxo de upgrade
- [ ] Validar cache de verificação

---

## Observações Importantes

1. **Compatibilidade:** Planos existentes devem continuar funcionando
2. **Migração:** Clientes com planos antigos devem ser migrados para nova estrutura
3. **Flexibilidade:** Estrutura deve permitir fácil adição de novos módulos
4. **Performance:** Verificação de módulos deve ser rápida (usar cache)
5. **UX:** Mensagens devem ser claras e sugerir upgrade quando necessário

---

## Decisões Pendentes

⚠️ **Aguardando Definição:**

1. **Valores Finais:** Quais serão os valores exatos dos planos?
2. **Estrutura de Planos:** Usar estrutura da imagem (5 planos) ou simplificada (4 planos)?
3. **Módulo Fiscal:** Será incluído em algum plano ou apenas como addon?
4. **Teste Grátis:** Quantos dias de teste grátis?
5. **Desconto Anual:** Qual percentual de desconto para pagamento anual?

---

**Última Atualização:** 2025-12-10  
**Próxima Revisão:** Após definição dos valores e estrutura de planos

