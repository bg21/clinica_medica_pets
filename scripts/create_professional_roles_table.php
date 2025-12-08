<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

try {
    // Verifica se a tabela já existe
    $stmt = $pdo->query('SHOW TABLES LIKE "professional_roles"');
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabela professional_roles já existe\n";
    } else {
        // Cria a tabela
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `professional_roles` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `tenant_id` INT(11) UNSIGNED NOT NULL,
                `name` VARCHAR(100) NOT NULL COMMENT 'Nome da role (ex: Profissional, Atendente)',
                `description` TEXT NULL COMMENT 'Descrição da role',
                `permissions` JSON NULL COMMENT 'Permissões específicas da role (opcional)',
                `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se a role está ativa',
                `sort_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Ordem de exibição',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_tenant_id` (`tenant_id`),
                KEY `idx_is_active` (`is_active`),
                KEY `idx_deleted_at` (`deleted_at`),
                UNIQUE KEY `idx_tenant_name_unique` (`tenant_id`, `name`),
                CONSTRAINT `fk_professional_roles_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Roles específicas dos profissionais da empresa'
        ");
        echo "✅ Tabela professional_roles criada\n";
    }
    
    // Verifica se o campo professional_role_id existe
    $stmt = $pdo->query('SHOW COLUMNS FROM professionals LIKE "professional_role_id"');
    if ($stmt->rowCount() > 0) {
        echo "✅ Campo professional_role_id já existe em professionals\n";
    } else {
        // Adiciona o campo
        $pdo->exec("
            ALTER TABLE `professionals`
            ADD COLUMN `professional_role_id` INT(11) UNSIGNED NULL COMMENT 'Role específica do profissional na clínica' AFTER `user_id`,
            ADD KEY `idx_professional_role_id` (`professional_role_id`),
            ADD CONSTRAINT `fk_professionals_professional_role_id` FOREIGN KEY (`professional_role_id`) REFERENCES `professional_roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ");
        echo "✅ Campo professional_role_id adicionado em professionals\n";
    }
    
    echo "\n✅ Estrutura criada com sucesso!\n";
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

