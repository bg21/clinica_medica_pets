# 💰 Sistema de Preços para Consultas - Clínica Veterinária

**Data:** 2025-12-07  
**Status:** 📋 Proposta de Implementação

---

## 📋 SITUAÇÃO ATUAL

### Como Funciona Hoje

1. **Preços são cadastrados no Stripe**
   - Via menu: **Produtos/Preços**
   - Cada preço é um produto no Stripe (ex: "Consulta Veterinária", "Cirurgia", "Vacinação")
   - Valor é definido em centavos (ex: R$ 150,00 = 15000 centavos)

2. **Ao criar agendamento:**
   - Usuário precisa **selecionar manualmente** um preço da lista
   - Não há sugestão automática baseada em:
     - Tipo de consulta (consulta, cirurgia, vacinação)
     - Especialidade do profissional
     - Profissional selecionado

3. **Não há relação entre:**
   - Tipo de consulta → Preço
   - Especialidade → Preço
   - Profissional → Preço

---

## 🎯 PROPOSTA DE SOLUÇÃO

### Opção 1: Tabela de Configuração de Preços (Recomendada)

Criar uma tabela `appointment_price_config` para mapear preços:

```sql
CREATE TABLE appointment_price_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    appointment_type VARCHAR(50), -- 'consulta', 'cirurgia', 'vacinação', etc.
    specialty VARCHAR(100), -- 'Clínica Geral', 'Cirurgia', 'Dermatologia', etc.
    professional_id INT NULL, -- NULL = preço padrão, ou ID específico do profissional
    price_id VARCHAR(255) NOT NULL, -- ID do preço no Stripe
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (professional_id) REFERENCES professionals(id),
    INDEX idx_tenant_type (tenant_id, appointment_type),
    INDEX idx_tenant_specialty (tenant_id, specialty),
    INDEX idx_tenant_professional (tenant_id, professional_id)
);
```

**Como funcionaria:**
1. Admin cadastra preços padrão por tipo de consulta
2. Pode definir preços específicos por especialidade
3. Pode definir preços específicos por profissional
4. Ao criar agendamento, sistema sugere preço automaticamente:
   - Primeiro tenta: preço do profissional específico
   - Depois: preço da especialidade
   - Por último: preço padrão do tipo de consulta

---

### Opção 2: Usar Metadata do Stripe (Mais Simples)

Usar os metadados dos preços no Stripe para associar:

```json
{
  "appointment_type": "consulta",
  "specialty": "Clínica Geral",
  "professional_id": "123"
}
```

**Como funcionaria:**
1. Ao criar preço no Stripe, adiciona metadados
2. Ao criar agendamento, busca preços filtrados por metadados
3. Sugere preço mais específico primeiro

**Vantagens:**
- Não precisa criar nova tabela
- Usa estrutura existente do Stripe

**Desvantagens:**
- Menos flexível
- Depende de metadados bem organizados

---

### Opção 3: Campo de Preço no Profissional (Mais Simples Ainda)

Adicionar campo `default_price_id` na tabela `professionals`:

```sql
ALTER TABLE professionals 
ADD COLUMN default_price_id VARCHAR(255) NULL;
```

**Como funcionaria:**
1. Cada profissional pode ter um preço padrão
2. Ao selecionar profissional, sugere seu preço padrão
3. Usuário pode alterar se necessário

**Vantagens:**
- Muito simples de implementar
- Cobre 80% dos casos de uso

**Desvantagens:**
- Não cobre preços por tipo de consulta
- Não cobre preços por especialidade

---

## 🚀 RECOMENDAÇÃO

**Implementar Opção 3 primeiro** (mais simples) e depois evoluir para Opção 1 (mais completa).

### Fase 1: Preço Padrão por Profissional
- Adicionar `default_price_id` em `professionals`
- Ao selecionar profissional, sugerir preço automaticamente
- Permitir alteração manual

### Fase 2: Configuração Completa
- Criar tabela `appointment_price_config`
- Interface para configurar preços por tipo/especialidade/profissional
- Sistema de prioridade (profissional > especialidade > tipo)

---

## 📝 COMO CADASTRAR PREÇOS HOJE

### Passo a Passo

1. **Acesse:** Menu → **Produtos/Preços**

2. **Crie um Produto:**
   - Nome: "Consulta Veterinária"
   - Descrição: "Consulta clínica geral"

3. **Crie um Preço para o Produto:**
   - Produto: Selecione o produto criado
   - Valor: 15000 (R$ 150,00 em centavos)
   - Moeda: BRL
   - Tipo: Pagamento único (não recorrente)

4. **Repita para outros serviços:**
   - Cirurgia: R$ 500,00
   - Vacinação: R$ 80,00
   - Exame: R$ 120,00
   - etc.

5. **Ao criar agendamento:**
   - Selecione o preço correspondente manualmente

---

## 🔧 IMPLEMENTAÇÃO SUGERIDA

### 1. Adicionar Campo no Profissional

```php
// Migration
ALTER TABLE professionals 
ADD COLUMN default_price_id VARCHAR(255) NULL 
COMMENT 'ID do preço padrão no Stripe para este profissional';
```

### 2. Atualizar View de Profissionais

Adicionar campo de seleção de preço padrão no formulário.

### 3. Atualizar View de Agendamentos

- Ao selecionar profissional, buscar `default_price_id`
- Se existir, preencher automaticamente o campo de preço
- Permitir alteração manual

### 4. Endpoint para Buscar Preço Sugerido

```php
GET /v1/clinic/appointments/suggested-price
Query params:
  - professional_id (opcional)
  - appointment_type (opcional)
  - specialty (opcional)
```

---

## ✅ PRÓXIMOS PASSOS

1. **Decidir qual opção implementar**
2. **Criar migration** (se Opção 1 ou 3)
3. **Atualizar views** para sugestão automática
4. **Testar fluxo completo**

---

**Última Atualização:** 2025-12-07

