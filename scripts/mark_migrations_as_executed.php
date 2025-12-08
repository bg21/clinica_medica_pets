<?php

/**
 * Script para marcar migrations como executadas no phinxlog
 * 
 * Use este script quando as tabelas já existem no banco mas o Phinx
 * não tem registro de que as migrations foram executadas.
 * 
 * Uso: php scripts/mark_migrations_as_executed.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use PDO;
use PDOException;

// Carrega configurações
Config::load();

$dbHost = Config::get('DB_HOST', '127.0.0.1');
$dbName = Config::get('DB_NAME', 'clinica_medica');
$dbUser = Config::get('DB_USER', 'root');
$dbPass = Config::get('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "✅ Conectado ao banco de dados: {$dbName}\n\n";

    // Lista de migrations para marcar como executadas
    // Ajuste conforme necessário - apenas as que já foram aplicadas manualmente
    $migrations = [
        '20250115000001' => 'InitialSchema',
        '20250116000001' => 'CreateBackupLogsTable',
        '20250118000001' => 'AddCompositeIndexes',
        '20250118000002' => 'AddAuditLogsIndexes',
        '20250118000003' => 'AddStatsIndexes',
        '20251114195137' => 'CreateAuditLogsTable',
        '20251114230642' => 'CreateSubscriptionHistoryTable',
        '20251115000545' => 'AddUserAuthAndPermissions',
        '20251115012954' => 'AddUserIdToSubscriptionHistory',
        '20251118023816' => 'AddDatabaseConstraints',
        '20251121164525' => 'AddSoftDeletesToModels',
        '20251122033041' => 'CreateProfessionalRolesTable',
        '20251123000001' => 'AddCpfToUsers',
        '20251123000002' => 'AddCpfToProfessionals',
        '20251126230115' => 'CreateProfessionalForVeterinarian',
        '20251127022056' => 'CreateExamTypesTable',
        '20251127022553' => 'SeedExamTypes',
        '20251127023000' => 'CreateExamsTable',
        '20251127024000' => 'SeedExams',
        '20251128021253' => 'AddResultsFileToExams',
        '20251129024639' => 'AddAppointmentStatusFields',
        '20251129031755' => 'CreateProfessionalSchedulesTable',
        '20251129031800' => 'CreateScheduleBlocksTable',
        '20251129032457' => 'CreatePerformanceMetricsTable',
        '20251129033442' => 'CreateClinicConfigurationsTable',
        '20251129043141' => 'AddSoftDeletesToAppointmentsPetsClients',
        '20251129055914' => 'AddPerformanceIndexes',
        '20251129200206' => 'AddRequestIdToAuditLogs',
        '20251129202116' => 'CreateApplicationLogsTable',
        '20251129203600' => 'AddClinicBasicInfoFields',
        '20251129225023' => 'AddSlugToTenants',
        '20251201000001' => 'AddAppointmentCheckinFields',
    ];

    // Verifica se a tabela phinxlog existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'phinxlog'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Tabela phinxlog não existe. Criando...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `phinxlog` (
                `version` BIGINT NOT NULL,
                `migration_name` VARCHAR(100) NULL,
                `start_time` TIMESTAMP NULL,
                `end_time` TIMESTAMP NULL,
                `breakpoint` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "✅ Tabela phinxlog criada.\n\n";
    }

    $now = date('Y-m-d H:i:s');
    $marked = 0;
    $skipped = 0;

    foreach ($migrations as $version => $name) {
        // Verifica se já está marcada
        $stmt = $pdo->prepare("SELECT version FROM phinxlog WHERE version = :version");
        $stmt->execute(['version' => $version]);
        
        if ($stmt->rowCount() > 0) {
            echo "⏭️  Migration {$version} ({$name}) já está marcada como executada.\n";
            $skipped++;
            continue;
        }

        // Marca como executada
        $stmt = $pdo->prepare("
            INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
            VALUES (:version, :name, :start_time, :end_time, 0)
        ");
        
        $stmt->execute([
            'version' => $version,
            'name' => $name,
            'start_time' => $now,
            'end_time' => $now
        ]);

        echo "✅ Migration {$version} ({$name}) marcada como executada.\n";
        $marked++;
    }

    echo "\n";
    echo "📊 Resumo:\n";
    echo "   - Marcadas: {$marked}\n";
    echo "   - Já existentes: {$skipped}\n";
    echo "   - Total: " . count($migrations) . "\n\n";
    echo "✅ Concluído! Agora você pode executar: vendor/bin/phinx migrate\n";

} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

