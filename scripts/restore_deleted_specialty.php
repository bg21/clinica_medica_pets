<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

echo "=== RESTAURAR ESPECIALIDADES DELETADAS ===\n\n";

// Lista especialidades deletadas
echo "1. Especialidades deletadas (com deleted_at):\n";
$stmt = $pdo->query("SELECT id, tenant_id, name, deleted_at FROM clinic_specialties WHERE deleted_at IS NOT NULL ORDER BY id");
$deleted = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($deleted)) {
    echo "   ✅ Nenhuma especialidade deletada encontrada\n";
} else {
    foreach ($deleted as $spec) {
        echo "   - ID: {$spec['id']} | Tenant: {$spec['tenant_id']} | Nome: '{$spec['name']}' | Deletado em: {$spec['deleted_at']}\n";
    }
    
    echo "\n2. Restaurando especialidades deletadas...\n";
    $stmt = $pdo->prepare("UPDATE clinic_specialties SET deleted_at = NULL WHERE deleted_at IS NOT NULL");
    $stmt->execute();
    $restored = $stmt->rowCount();
    echo "   ✅ {$restored} especialidade(s) restaurada(s)\n";
}

echo "\n3. Verificando especialidades ativas agora:\n";
$stmt = $pdo->query("SELECT id, tenant_id, name, is_active, deleted_at FROM clinic_specialties WHERE deleted_at IS NULL ORDER BY id");
$active = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($active)) {
    echo "   ⚠️  Nenhuma especialidade ativa encontrada\n";
    echo "   💡 Crie uma nova especialidade em /clinic/specialties\n";
} else {
    foreach ($active as $spec) {
        $status = $spec['is_active'] ? 'Ativa' : 'Inativa';
        echo "   ✅ ID: {$spec['id']} | Tenant: {$spec['tenant_id']} | Nome: '{$spec['name']}' | Status: {$status}\n";
    }
}

echo "\n✅ Concluído! Agora acesse /clinic/specialties para ver as especialidades.\n";

