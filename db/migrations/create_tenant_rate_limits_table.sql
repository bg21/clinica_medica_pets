-- Migration: Criar tabela tenant_rate_limits
-- Data: 2025-12-02
-- Descrição: Tabela para armazenar limites de rate limiting customizados por tenant

CREATE TABLE IF NOT EXISTS `tenant_rate_limits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL,
  `endpoint` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Endpoint específico (NULL para limite global)',
  `method` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Método HTTP (GET, POST, PUT, DELETE, etc.) - NULL para todos os métodos',
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

-- Exemplo de uso:
-- Limite global para tenant 3: 100/min, 2000/hora
-- INSERT INTO tenant_rate_limits (tenant_id, endpoint, method, limit_per_minute, limit_per_hour) 
-- VALUES (3, NULL, NULL, 100, 2000);

-- Limite específico para endpoint /v1/appointments POST do tenant 3: 50/min, 500/hora
-- INSERT INTO tenant_rate_limits (tenant_id, endpoint, method, limit_per_minute, limit_per_hour) 
-- VALUES (3, '/v1/appointments', 'POST', 50, 500);

