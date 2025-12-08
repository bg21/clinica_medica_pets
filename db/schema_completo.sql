-- =====================================================
-- Script de Criação Completo do Banco de Dados
-- Sistema SaaS Genérico - Base de Pagamentos
-- Banco: clinica_medica
-- =====================================================

-- Criar banco de dados se não existir
CREATE DATABASE IF NOT EXISTS `clinica_medica` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `clinica_medica`;

-- =====================================================
-- TABELA: tenants
-- =====================================================
CREATE TABLE IF NOT EXISTS `tenants` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COMMENT 'Nome do tenant (ex: Empresa ABC)',
  `slug` VARCHAR(100) NOT NULL COMMENT 'Slug único do tenant (ex: empresa-abc)',
  `api_key` VARCHAR(64) NOT NULL COMMENT 'Chave única para autenticação',
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active' COMMENT 'Status do tenant',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data de exclusão lógica (soft delete)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_api_key` (`api_key`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de tenants (clientes SaaS)';

-- =====================================================
-- TABELA: users
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID do tenant',
  `email` VARCHAR(255) NOT NULL COMMENT 'Email do usuário',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hash da senha (bcrypt)',
  `name` VARCHAR(255) DEFAULT NULL COMMENT 'Nome do usuário',
  `cpf` VARCHAR(14) DEFAULT NULL COMMENT 'CPF do usuário (formato: 000.000.000-00)',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT 'Status do usuário',
  `role` ENUM('admin', 'viewer', 'editor') NOT NULL DEFAULT 'viewer' COMMENT 'Role do usuário: admin (todas permissões), editor (editar), viewer (apenas visualizar)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tenant_email` (`tenant_id`, `email`),
  KEY `idx_email` (`email`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_users_cpf` (`cpf`),
  CONSTRAINT `fk_users_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de usuários';

-- =====================================================
-- TABELA: customers
-- =====================================================
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID do tenant',
  `stripe_customer_id` VARCHAR(255) NOT NULL COMMENT 'ID do cliente no Stripe',
  `email` VARCHAR(255) DEFAULT NULL COMMENT 'Email do cliente',
  `name` VARCHAR(255) DEFAULT NULL COMMENT 'Nome do cliente',
  `metadata` JSON DEFAULT NULL COMMENT 'Metadados adicionais (JSON)',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data de exclusão lógica (soft delete)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stripe_customer` (`stripe_customer_id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_email` (`email`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_customers_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de clientes Stripe';

-- =====================================================
-- TABELA: subscriptions
-- =====================================================
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID do tenant',
  `customer_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID do cliente',
  `stripe_subscription_id` VARCHAR(255) NOT NULL COMMENT 'ID da assinatura no Stripe',
  `stripe_customer_id` VARCHAR(255) NOT NULL COMMENT 'ID do cliente no Stripe',
  `status` VARCHAR(50) NOT NULL COMMENT 'Status da assinatura',
  `plan_id` VARCHAR(255) DEFAULT NULL COMMENT 'ID do plano (price_id)',
  `plan_name` VARCHAR(255) DEFAULT NULL COMMENT 'Nome do plano',
  `amount` DECIMAL(10,2) DEFAULT NULL COMMENT 'Valor da assinatura',
  `currency` VARCHAR(3) NOT NULL DEFAULT 'usd' COMMENT 'Moeda',
  `current_period_start` DATETIME DEFAULT NULL COMMENT 'Início do período atual',
  `current_period_end` DATETIME DEFAULT NULL COMMENT 'Fim do período atual',
  `cancel_at_period_end` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Cancelar ao fim do período',
  `metadata` JSON DEFAULT NULL COMMENT 'Metadados adicionais (JSON)',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data de exclusão lógica (soft delete)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stripe_subscription` (`stripe_subscription_id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_subscriptions_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_subscriptions_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de assinaturas';

-- =====================================================
-- TABELA: subscription_history
-- =====================================================
CREATE TABLE IF NOT EXISTS `subscription_history` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID da assinatura (FK para subscriptions)',
  `tenant_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID do tenant (para filtros rápidos)',
  `user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID do usuário que fez a mudança (quando via API com autenticação de usuário)',
  `change_type` VARCHAR(50) NOT NULL COMMENT 'Tipo de mudança: created, updated, canceled, reactivated, plan_changed, status_changed',
  `changed_by` VARCHAR(50) DEFAULT NULL COMMENT 'Origem da mudança: api, webhook, admin',
  `old_status` VARCHAR(50) DEFAULT NULL COMMENT 'Status anterior da assinatura',
  `new_status` VARCHAR(50) DEFAULT NULL COMMENT 'Status novo da assinatura',
  `old_plan_id` VARCHAR(255) DEFAULT NULL COMMENT 'ID do plano anterior (price_id)',
  `new_plan_id` VARCHAR(255) DEFAULT NULL COMMENT 'ID do plano novo (price_id)',
  `old_amount` DECIMAL(10,2) DEFAULT NULL COMMENT 'Valor anterior (em formato monetário)',
  `new_amount` DECIMAL(10,2) DEFAULT NULL COMMENT 'Valor novo (em formato monetário)',
  `old_currency` VARCHAR(3) DEFAULT NULL COMMENT 'Moeda anterior',
  `new_currency` VARCHAR(3) DEFAULT NULL COMMENT 'Moeda nova',
  `old_current_period_end` DATETIME DEFAULT NULL COMMENT 'Fim do período anterior',
  `new_current_period_end` DATETIME DEFAULT NULL COMMENT 'Fim do período novo',
  `old_cancel_at_period_end` BOOLEAN DEFAULT NULL COMMENT 'Cancelar ao fim do período anterior',
  `new_cancel_at_period_end` BOOLEAN DEFAULT NULL COMMENT 'Cancelar ao fim do período novo',
  `metadata` JSON DEFAULT NULL COMMENT 'Metadados adicionais da mudança (JSON)',
  `description` TEXT DEFAULT NULL COMMENT 'Descrição da mudança (opcional)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data e hora da mudança',
  PRIMARY KEY (`id`),
  KEY `idx_subscription_id` (`subscription_id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_change_type` (`change_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_subscription_created` (`subscription_id`, `created_at`),
  KEY `idx_tenant_created` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_subscription_history_subscription_id` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_subscription_history_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_subscription_history_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de mudanças de assinaturas - auditoria de alterações';

-- =====================================================
-- TABELA: stripe_events
-- =====================================================
CREATE TABLE IF NOT EXISTS `stripe_events` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` VARCHAR(255) NOT NULL COMMENT 'ID do evento no Stripe',
  `event_type` VARCHAR(100) NOT NULL COMMENT 'Tipo do evento',
  `processed` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Se o evento foi processado',
  `data` JSON DEFAULT NULL COMMENT 'Dados do evento (JSON)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_event_id` (`event_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de eventos Stripe (idempotência de webhooks)';

-- =====================================================
-- TABELA: rate_limits
-- =====================================================
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier_key` VARCHAR(255) NOT NULL COMMENT 'Chave de identificação (IP, API Key, etc)',
  `request_count` INT(11) NOT NULL DEFAULT 1 COMMENT 'Contador de requisições',
  `reset_at` INT(11) NOT NULL COMMENT 'Timestamp de reset',
  `created_at` INT(11) NOT NULL COMMENT 'Timestamp de criação',
  `updated_at` INT(11) NOT NULL COMMENT 'Timestamp de atualização',
  PRIMARY KEY (`id`),
  KEY `idx_identifier_key` (`identifier_key`),
  KEY `idx_reset_at` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de rate limits (fallback quando Redis não está disponível)';

-- =====================================================
-- TABELA: tenant_rate_limits
-- =====================================================
CREATE TABLE IF NOT EXISTS `tenant_rate_limits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID do tenant',
  `endpoint` VARCHAR(255) DEFAULT NULL COMMENT 'Endpoint específico (NULL para limite global)',
  `method` VARCHAR(10) DEFAULT NULL COMMENT 'Método HTTP (GET, POST, PUT, DELETE, etc.) - NULL para todos os métodos',
  `limit_per_minute` INT(11) NOT NULL DEFAULT 60 COMMENT 'Limite de requisições por minuto',
  `limit_per_hour` INT(11) NOT NULL DEFAULT 1000 COMMENT 'Limite de requisições por hora',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tenant_endpoint_method` (`tenant_id`, `endpoint`, `method`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_method` (`method`),
  CONSTRAINT `fk_tenant_rate_limits_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Limites de rate limiting customizados por tenant';

-- =====================================================
-- TABELA: user_sessions
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` VARCHAR(64) NOT NULL COMMENT 'Token de sessão (hash)',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID do usuário',
  `tenant_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID do tenant',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP do cliente',
  `user_agent` TEXT DEFAULT NULL COMMENT 'User-Agent do cliente',
  `expires_at` DATETIME NOT NULL COMMENT 'Data de expiração da sessão',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_user_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_sessions_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessões de usuários autenticados - tokens de acesso';

-- =====================================================
-- TABELA: user_permissions
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT 'ID do usuário',
  `permission` VARCHAR(100) NOT NULL COMMENT 'Nome da permissão (ex: view_subscriptions, create_subscriptions)',
  `granted` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Se a permissão está concedida (true) ou negada (false)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_permission` (`user_id`, `permission`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_user_permissions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permissões específicas de usuários - controle granular além das roles';

-- =====================================================
-- TABELA: audit_logs
-- =====================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` VARCHAR(32) DEFAULT NULL COMMENT 'ID único da requisição para tracing',
  `tenant_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID do tenant (null para master key)',
  `user_id` INT(11) DEFAULT NULL COMMENT 'ID do usuário (quando aplicável)',
  `endpoint` VARCHAR(255) NOT NULL COMMENT 'Endpoint/URL acessada',
  `method` VARCHAR(10) NOT NULL COMMENT 'Método HTTP (GET, POST, PUT, DELETE, etc)',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'Endereço IP do cliente (suporta IPv4 e IPv6)',
  `user_agent` TEXT DEFAULT NULL COMMENT 'User-Agent do cliente',
  `request_body` TEXT DEFAULT NULL COMMENT 'Corpo da requisição (JSON, limitado a 10KB)',
  `response_status` INT(3) NOT NULL COMMENT 'Status HTTP da resposta',
  `response_time` INT(11) NOT NULL COMMENT 'Tempo de resposta em milissegundos',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data e hora da requisição',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_method` (`method`),
  KEY `idx_response_status` (`response_status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_tenant_created` (`tenant_id`, `created_at`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_tenant_request_id` (`tenant_id`, `request_id`),
  CONSTRAINT `fk_audit_logs_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de logs de auditoria - rastreabilidade de ações';

-- =====================================================
-- TABELA: application_logs
-- =====================================================
CREATE TABLE IF NOT EXISTS `application_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` VARCHAR(32) DEFAULT NULL COMMENT 'ID único da requisição para tracing',
  `level` VARCHAR(20) NOT NULL COMMENT 'Nível do log (DEBUG, INFO, WARNING, ERROR, etc)',
  `level_value` INT(11) NOT NULL COMMENT 'Valor numérico do nível (para ordenação)',
  `message` TEXT NOT NULL COMMENT 'Mensagem do log',
  `context` TEXT DEFAULT NULL COMMENT 'Contexto do log (JSON)',
  `channel` VARCHAR(50) NOT NULL DEFAULT 'saas_payments' COMMENT 'Canal do log (channel do Monolog)',
  `tenant_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID do tenant (se disponível)',
  `user_id` INT(11) DEFAULT NULL COMMENT 'ID do usuário (se disponível)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data e hora do log',
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_level` (`level`),
  KEY `idx_level_value` (`level_value`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_request_created` (`request_id`, `created_at`),
  KEY `idx_tenant_created` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_application_logs_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs da aplicação (Monolog) com request_id para tracing';

-- =====================================================
-- TABELA: performance_metrics
-- =====================================================
CREATE TABLE IF NOT EXISTS `performance_metrics` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `endpoint` VARCHAR(255) NOT NULL COMMENT 'Endpoint da requisição',
  `method` VARCHAR(10) NOT NULL COMMENT 'Método HTTP (GET, POST, PUT, DELETE, etc)',
  `duration_ms` INT(11) NOT NULL COMMENT 'Duração da requisição em milissegundos',
  `memory_mb` DECIMAL(10,2) NOT NULL COMMENT 'Memória utilizada em megabytes',
  `tenant_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID do tenant (NULL para requisições não autenticadas)',
  `user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID do usuário (NULL para requisições não autenticadas)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data e hora da requisição',
  PRIMARY KEY (`id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_method` (`method`),
  KEY `idx_endpoint_method` (`endpoint`, `method`),
  CONSTRAINT `fk_performance_metrics_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_performance_metrics_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Métricas de performance das requisições';

-- =====================================================
-- TABELA: backup_logs
-- =====================================================
CREATE TABLE IF NOT EXISTS `backup_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL COMMENT 'Nome do arquivo de backup',
  `file_path` TEXT DEFAULT NULL COMMENT 'Caminho completo do arquivo de backup',
  `file_size` BIGINT(20) NOT NULL DEFAULT 0 COMMENT 'Tamanho do arquivo em bytes',
  `status` ENUM('success', 'failed') NOT NULL DEFAULT 'success' COMMENT 'Status do backup',
  `duration_seconds` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Duração do backup em segundos',
  `compressed` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Se o backup foi comprimido (gzip)',
  `error_message` TEXT DEFAULT NULL COMMENT 'Mensagem de erro (se houver)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação do backup',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de backups do banco de dados';

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================

