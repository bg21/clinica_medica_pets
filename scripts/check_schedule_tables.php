<?php
/**
 * Script para verificar se as tabelas de agenda de profissionais existem
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Utils\Database;

$db = Database::getInstance();

echo "=== VERIFICAÇÃO DE TABELAS DE AGENDA ===\n\n";

// Verifica professional_schedules
$stmt = $db->query("SHOW TABLES LIKE 'professional_schedules'");
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($result) > 0) {
    echo "✅ Tabela 'professional_schedules' EXISTE\n";
    
    // Mostra estrutura
    $stmt = $db->query("DESCRIBE professional_schedules");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nEstrutura:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})" . 
             ($col['Null'] === 'NO' ? ' NOT NULL' : '') .
             ($col['Key'] ? " [{$col['Key']}]" : '') . "\n";
    }
    
    // Conta registros
    $stmt = $db->query("SELECT COUNT(*) as total FROM professional_schedules");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal de registros: {$count['total']}\n";
} else {
    echo "❌ Tabela 'professional_schedules' NÃO EXISTE\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Verifica schedule_blocks
$stmt = $db->query("SHOW TABLES LIKE 'schedule_blocks'");
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($result) > 0) {
    echo "✅ Tabela 'schedule_blocks' EXISTE\n";
    
    // Mostra estrutura
    $stmt = $db->query("DESCRIBE schedule_blocks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nEstrutura:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})" . 
             ($col['Null'] === 'NO' ? ' NOT NULL' : '') .
             ($col['Key'] ? " [{$col['Key']}]" : '') . "\n";
    }
    
    // Conta registros
    $stmt = $db->query("SELECT COUNT(*) as total FROM schedule_blocks");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal de registros: {$count['total']}\n";
} else {
    echo "❌ Tabela 'schedule_blocks' NÃO EXISTE\n";
}

echo "\n=== FIM DA VERIFICAÇÃO ===\n";
