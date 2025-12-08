# 📦 Script de Criação do Banco de Dados

Este diretório contém o script SQL completo para criar todas as tabelas do sistema.

## 🚀 Como Usar

### Opção 1: Via Script PHP (Recomendado)

Execute o script PHP que cria o banco e todas as tabelas automaticamente:

```bash
php scripts/create_database.php
```

O script irá:
1. Ler as configurações do arquivo `.env`
2. Criar o banco de dados `clinica_medica` se não existir
3. Criar todas as tabelas necessárias
4. Exibir um resumo das tabelas criadas

### Opção 2: Via MySQL Diretamente

Execute o arquivo SQL diretamente no MySQL:

```bash
mysql -u root -p < db/schema_completo.sql
```

Ou via linha de comando do MySQL:

```sql
mysql -u root -p
source db/schema_completo.sql;
```

### Opção 3: Via phpMyAdmin

1. Acesse o phpMyAdmin
2. Selecione ou crie o banco `clinica_medica`
3. Vá em "Importar"
4. Selecione o arquivo `db/schema_completo.sql`
5. Clique em "Executar"

## 📋 Tabelas Criadas

O script cria as seguintes tabelas:

1. **tenants** - Tenants (clientes SaaS)
2. **users** - Usuários do sistema
3. **customers** - Clientes Stripe
4. **subscriptions** - Assinaturas
5. **subscription_history** - Histórico de mudanças de assinaturas
6. **stripe_events** - Eventos do Stripe (idempotência)
7. **rate_limits** - Rate limits (fallback quando Redis não está disponível)
8. **tenant_rate_limits** - Limites customizados por tenant
9. **user_sessions** - Sessões de usuários autenticados
10. **user_permissions** - Permissões específicas de usuários
11. **audit_logs** - Logs de auditoria
12. **application_logs** - Logs da aplicação (Monolog)
13. **performance_metrics** - Métricas de performance
14. **backup_logs** - Logs de backups

## ⚙️ Configuração

Certifique-se de que o arquivo `.env` está configurado corretamente:

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=clinica_medica
```

## ⚠️ Importante

- **Backup**: Se você já tem dados no banco, faça backup antes de executar o script
- **Dados Existentes**: O script usa `CREATE TABLE IF NOT EXISTS`, então não apagará dados existentes
- **Foreign Keys**: Todas as foreign keys são criadas automaticamente
- **Índices**: Todos os índices necessários são criados

## 🔍 Verificação

Após executar o script, você pode verificar se todas as tabelas foram criadas:

```sql
USE clinica_medica;
SHOW TABLES;
```

Ou via script PHP:

```bash
php scripts/create_database.php
```

O script exibirá uma lista de todas as tabelas criadas.

## 📝 Notas

- O banco de dados usa `utf8mb4` como charset padrão
- Todas as tabelas usam o engine `InnoDB`
- Soft deletes estão implementados nas tabelas principais
- Todas as foreign keys têm `ON DELETE CASCADE` ou `ON DELETE SET NULL` conforme apropriado

