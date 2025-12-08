<?php
/**
 * Script para criar as tabelas de agenda de profissionais diretamente
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Utils\Database;

$db = Database::getInstance();

echo "=== CRIANDO TABELAS DE AGENDA ===\n\n";

// Cria professional_schedules
try {
    $sql = "CREATE TABLE IF NOT EXISTS `professional_schedules` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `tenant_id` int(11) unsigned NOT NULL,
        `professional_id` int(11) unsigned NOT NULL,
        `day_of_week` tinyint(1) NOT NULL COMMENT 'Dia da semana: 0=domingo, 1=segunda, ..., 6=sábado',
        `start_time` time NOT NULL COMMENT 'Hora de início do trabalho',
        `end_time` time NOT NULL COMMENT 'Hora de fim do trabalho',
        `is_available` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Se o horário está disponível/ativo',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_tenant_id` (`tenant_id`),
        KEY `idx_professional_id` (`professional_id`),
        UNIQUE KEY `unique_professional_day` (`professional_id`, `day_of_week`),
        CONSTRAINT `fk_professional_schedules_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_professional_schedules_professional_id` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Horários de trabalho dos profissionais por dia da semana'";
    
    $db->exec($sql);
    echo "✅ Tabela 'professional_schedules' criada com sucesso!\n";
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "ℹ️  Tabela 'professional_schedules' já existe.\n";
    } else {
        echo "❌ Erro ao criar 'professional_schedules': " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Cria schedule_blocks
try {
    $sql = "CREATE TABLE IF NOT EXISTS `schedule_blocks` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `tenant_id` int(11) unsigned NOT NULL,
        `professional_id` int(11) unsigned NOT NULL,
        `start_datetime` datetime NOT NULL COMMENT 'Data e hora de início do bloqueio',
        `end_datetime` datetime NOT NULL COMMENT 'Data e hora de fim do bloqueio',
        `reason` varchar(255) DEFAULT NULL COMMENT 'Motivo do bloqueio (ex: férias, almoço)',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_tenant_id` (`tenant_id`),
        KEY `idx_professional_id` (`professional_id`),
        KEY `idx_datetime` (`start_datetime`, `end_datetime`),
        CONSTRAINT `fk_schedule_blocks_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_schedule_blocks_professional_id` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bloqueios de agenda dos profissionais'";
    
    $db->exec($sql);
    echo "✅ Tabela 'schedule_blocks' criada com sucesso!\n";
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "ℹ️  Tabela 'schedule_blocks' já existe.\n";
    } else {
        echo "❌ Erro ao criar 'schedule_blocks': " . $e->getMessage() . "\n";
    }
}

echo "\n=== VERIFICAÇÃO FINAL ===\n\n";

// Verifica novamente
$stmt = $db->query("SHOW TABLES LIKE 'professional_schedules'");
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo count($result) > 0 ? "✅ professional_schedules existe\n" : "❌ professional_schedules NÃO existe\n";

$stmt = $db->query("SHOW TABLES LIKE 'schedule_blocks'");
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo count($result) > 0 ? "✅ schedule_blocks existe\n" : "❌ schedule_blocks NÃO existe\n";

echo "\n=== CONCLUÍDO ===\n";

