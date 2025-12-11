# Sistema de Administradores Master do SaaS

## Visão Geral

O sistema de administradores master permite que você (dono do SaaS) tenha múltiplos administradores que podem gerenciar planos, módulos e outras configurações do sistema. Esses administradores **não** pertencem a nenhum tenant específico - eles são do próprio SaaS.

## Estrutura


Administrador master criado com sucesso!
   Email: admin@saas.local
   Senha: admin123


   
### Tabelas Criadas

1. **`saas_admins`**: Armazena os administradores master
   - `id`: ID único
   - `email`: Email do administrador (único)
   - `password_hash`: Hash da senha (bcrypt)
   - `name`: Nome do administrador
   - `is_active`: Se está ativo
   - `last_login_at`: Último login
   - `last_login_ip`: IP do último login

2. **`saas_admin_sessions`**: Sessões dos administradores
   - `id`: Session ID (token)
   - `admin_id`: FK para `saas_admins.id`
   - `ip_address`: IP do cliente
   - `user_agent`: User-Agent
   - `expires_at`: Data de expiração

## Como Usar

### 1. Executar Migrations e Seeds

```bash
# Executar migration para criar as tabelas
vendor/bin/phinx migrate

# Criar primeiro administrador master
vendor/bin/phinx seed:run -s SeedSaasAdmin
```

O seed criará um administrador padrão:
- **Email**: `admin@saas.local`
- **Senha**: `admin123`
- ⚠️ **IMPORTANTE**: Altere a senha após o primeiro login!

### 2. Login de Administrador SaaS

**⚠️ IMPORTANTE**: Administradores SaaS **NÃO** têm tenant. Eles fazem login em uma página separada.

#### Opção 1: Login via Interface Web (Recomendado)

Acesse: **`http://localhost:8080/saas-admin/login`**

- **Email**: `admin@saas.local`
- **Senha**: `admin123` (altere após o primeiro login!)

Após o login, você será redirecionado para `/admin-plans` automaticamente.

#### Opção 2: Login via API

**Endpoint**: `POST /v1/saas-admin/login`

**Body**:
```json
{
  "email": "admin@saas.local",
  "password": "admin123"
}
```

**Resposta**:
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "session_id": "abc123...",
    "admin": {
      "id": 1,
      "email": "admin@saas.local",
      "name": "Administrador Master"
    }
  }
}
```

Use o `session_id` retornado no header `Authorization: Bearer {session_id}` para autenticar nas próximas requisições.

### 3. Acessar Interface Web

Após fazer login (via web ou API), você pode acessar:
- **`http://localhost:8080/admin-plans`**: Interface para gerenciar planos e módulos

O sistema detecta automaticamente se você é um `saas_admin` através do `session_id`.

### 4. Criar Novos Administradores

**Endpoint**: `POST /v1/saas-admin/admins`

**Headers**:
```
Authorization: Bearer {session_id_do_admin_logado}
```

**Body**:
```json
{
  "email": "novo@admin.com",
  "password": "senhaSegura123",
  "name": "Novo Administrador"
}
```

### 5. Listar Administradores

**Endpoint**: `GET /v1/saas-admin/admins`

**Headers**:
```
Authorization: Bearer {session_id_do_admin_logado}
```

### 6. Atualizar Administrador

**Endpoint**: `PUT /v1/saas-admin/admins/{id}`

**Headers**:
```
Authorization: Bearer {session_id_do_admin_logado}
```

**Body** (campos opcionais):
```json
{
  "email": "novoemail@admin.com",
  "name": "Nome Atualizado",
  "password": "novaSenha123",
  "is_active": true
}
```

### 7. Desativar Administrador

**Endpoint**: `DELETE /v1/saas-admin/admins/{id}`

**Headers**:
```
Authorization: Bearer {session_id_do_admin_logado}
```

⚠️ **Nota**: Você não pode desativar sua própria conta.

## Diferenças entre Administradores

### Administrador SaaS (`saas_admin`)
- **Pertence**: Ao próprio SaaS (**SEM tenant**)
- **Login**: `http://localhost:8080/saas-admin/login` (não precisa de tenant_slug)
- **Pode**: Criar/editar planos, módulos, gerenciar outros admins
- **Acesso**: `/admin-plans`, `/v1/admin/*`, `/v1/saas-admin/*`
- **Sessão**: Armazenada em `saas_admin_sessions`
- **Session ID**: Salvo como `saas_admin_session_id` (diferente de `session_id`)

### Administrador de Clínica (`admin` role)
- **Pertence**: A um tenant específico (clínica)
- **Login**: `http://localhost:8080/login` (precisa de `tenant_slug`)
- **Pode**: Gerenciar usuários da clínica, configurações da clínica
- **Acesso**: `/users`, `/permissions`, etc. (dentro do tenant)
- **Sessão**: Armazenada em `user_sessions`
- **Session ID**: Salvo como `session_id`

## Segurança

1. **Senhas**: Sempre armazenadas com hash bcrypt
2. **Sessões**: Expiração automática (padrão: 24 horas)
3. **Validação**: Todas as rotas administrativas verificam `is_saas_admin`
4. **Logs**: Todas as ações são registradas no sistema de logs

## Fluxo de Autenticação

1. Administrador faz login via `POST /v1/saas-admin/login`
2. Sistema retorna `session_id`
3. `session_id` é usado no header `Authorization: Bearer {session_id}`
4. `getAuthenticatedUserData()` detecta automaticamente se é `saas_admin` ou usuário normal
5. Se for `saas_admin`, define `Flight::set('is_saas_admin', true)`
6. Controllers verificam `Flight::get('is_saas_admin')` antes de permitir acesso

## Exemplo de Uso Completo

```javascript
// 1. Login
const loginResponse = await fetch('/v1/saas-admin/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'admin@saas.local',
    password: 'admin123'
  })
});

const { data } = await loginResponse.json();
const sessionId = data.session_id;

// 2. Criar novo plano
const createPlanResponse = await fetch('/v1/admin/plans', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${sessionId}`
  },
  body: JSON.stringify({
    plan_id: 'premium',
    name: 'Plano Premium',
    monthly_price: 9900,
    yearly_price: 99000,
    max_users: 50,
    modules: [1, 2, 3, 4, 5]
  })
});
```

## Troubleshooting

### "Acesso negado" ao acessar `/admin-plans`
- Verifique se você fez login como `saas_admin` (não como `admin` de tenant)
- Verifique se o `session_id` está sendo enviado corretamente
- Verifique se a sessão não expirou

### "Sessão inválida ou expirada"
- Faça login novamente via `POST /v1/saas-admin/login`
- Sessões expiram após 24 horas por padrão

### Não consigo criar administrador
- Verifique se você está logado como `saas_admin`
- Verifique se o email já não existe
- Verifique se a senha tem no mínimo 8 caracteres

