# 🐾 Rotas da Clínica Veterinária

**Data:** 2025-12-07  
**Status:** ✅ Rotas Registradas e Menu Atualizado

---

## 📋 RESUMO

Todas as rotas da API e views da clínica veterinária foram registradas no Flight Framework e adicionadas ao menu de navegação.

---

## 🔌 ROTAS DE API

### Pets (`/v1/clinic/pets`)

| Método | Rota | Controller | Método | Descrição |
|--------|------|------------|--------|-----------|
| POST | `/v1/clinic/pets` | PetController | `create()` | Criar pet |
| GET | `/v1/clinic/pets` | PetController | `list()` | Listar pets (paginado) |
| GET | `/v1/clinic/pets/:id` | PetController | `get()` | Obter pet |
| PUT | `/v1/clinic/pets/:id` | PetController | `update()` | Atualizar pet |
| DELETE | `/v1/clinic/pets/:id` | PetController | `delete()` | Deletar pet (soft delete) |
| GET | `/v1/clinic/pets/customer/:customer_id` | PetController | `listByCustomer()` | Listar pets por tutor |

### Profissionais (`/v1/clinic/professionals`)

| Método | Rota | Controller | Método | Descrição |
|--------|------|------------|--------|-----------|
| POST | `/v1/clinic/professionals` | ProfessionalController | `create()` | Criar profissional |
| GET | `/v1/clinic/professionals` | ProfessionalController | `list()` | Listar profissionais (paginado) |
| GET | `/v1/clinic/professionals/active` | ProfessionalController | `listActive()` | Listar profissionais ativos |
| GET | `/v1/clinic/professionals/:id` | ProfessionalController | `get()` | Obter profissional |
| PUT | `/v1/clinic/professionals/:id` | ProfessionalController | `update()` | Atualizar profissional |

### Agendamentos (`/v1/clinic/appointments`)

| Método | Rota | Controller | Método | Descrição |
|--------|------|------------|--------|-----------|
| POST | `/v1/clinic/appointments` | AppointmentController | `create()` | Criar agendamento |
| GET | `/v1/clinic/appointments` | AppointmentController | `list()` | Listar agendamentos (paginado) |
| GET | `/v1/clinic/appointments/:id` | AppointmentController | `get()` | Obter agendamento |
| PUT | `/v1/clinic/appointments/:id` | AppointmentController | `update()` | Atualizar agendamento |
| DELETE | `/v1/clinic/appointments/:id` | AppointmentController | `delete()` | Deletar agendamento (soft delete) |
| GET | `/v1/clinic/appointments/pet/:pet_id` | AppointmentController | `listByPet()` | Listar agendamentos por pet |
| GET | `/v1/clinic/appointments/professional/:professional_id` | AppointmentController | `listByProfessional()` | Listar agendamentos por profissional |

---

## 🌐 ROTAS DE VIEWS (HTML)

### Páginas da Clínica

| Rota | View | Descrição |
|------|------|-----------|
| `/clinic/pets` | `clinic/pets` | Página de gerenciamento de pets |
| `/clinic/professionals` | `clinic/professionals` | Página de gerenciamento de profissionais |
| `/clinic/appointments` | `clinic/appointments` | Página de gerenciamento de agendamentos |
| `/schedule` | `schedule` | Calendário de agendamentos (já existente) |

---

## 📱 MENU DE NAVEGAÇÃO

Foi adicionada uma nova seção no menu lateral:

### Seção: "Clínica Veterinária"

- 🐾 **Pets** (`/clinic/pets`)
- 👨‍⚕️ **Profissionais** (`/clinic/professionals`)
- 📅 **Agendamentos** (`/clinic/appointments`)
- 📆 **Calendário** (`/schedule`)

A seção aparece após "Relatórios" e antes de "Configurações".

---

## ✅ CONFIGURAÇÕES REALIZADAS

### 1. Rotas de API Registradas
- ✅ Todas as rotas de API foram registradas em `public/index.php`
- ✅ Controllers criados via Container de DI
- ✅ Rotas adicionadas à lista de rotas autenticadas

### 2. Rotas de Views Registradas
- ✅ Rotas HTML criadas para as páginas da clínica
- ✅ Verificação de autenticação implementada
- ✅ Rotas adicionadas à lista de rotas públicas (autenticadas)

### 3. Menu Atualizado
- ✅ Nova seção "Clínica Veterinária" adicionada em `App/Views/layouts/base.php`
- ✅ Ícones Bootstrap Icons utilizados
- ✅ Links com detecção de página ativa

### 4. Container de DI
- ✅ Bindings adicionados em `App/Core/ContainerBindings.php`
- ✅ Controllers registrados sem dependências (criam Models internamente)

### 5. Documentação da API
- ✅ Endpoints adicionados à rota raiz (`/`) para documentação

---

## 🔒 SEGURANÇA

Todas as rotas implementam:

1. **Autenticação**: Verificação de tenant_id via middleware
2. **Permissões**: Verificação via `PermissionHelper::require()`
3. **Proteção IDOR**: Validação de tenant_id em todos os métodos
4. **Validação de Dados**: Validação de entrada em todos os endpoints

---

## 📝 PERMISSÕES NECESSÁRIAS

Para usar as funcionalidades, os usuários precisam das seguintes permissões:

- `create_pets`, `view_pets`, `update_pets`, `delete_pets`
- `create_professionals`, `view_professionals`, `update_professionals`
- `create_appointments`, `view_appointments`, `update_appointments`, `delete_appointments`

---

## 🚀 PRÓXIMOS PASSOS

1. **Criar Views HTML** (`App/Views/clinic/`):
   - `pets.php` - Interface de gerenciamento de pets
   - `professionals.php` - Interface de gerenciamento de profissionais
   - `appointments.php` - Interface de gerenciamento de agendamentos

2. **Configurar Permissões**:
   - Adicionar as permissões listadas acima no sistema
   - Atribuir permissões aos usuários conforme necessário

3. **Testar Rotas**:
   - Testar todas as rotas via Postman ou similar
   - Verificar autenticação e permissões
   - Validar respostas JSON

---

## 📚 REFERÊNCIAS

- **[IMPLEMENTACAO_CLINICA_VETERINARIA.md](IMPLEMENTACAO_CLINICA_VETERINARIA.md)** - Documentação completa da implementação
- **[GUIA_CLINICA_VETERINARIA.md](GUIA_CLINICA_VETERINARIA.md)** - Guia de uso do sistema

---

**Última Atualização:** 2025-12-07

