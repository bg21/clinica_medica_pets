<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

echo "=== VERIFICAÇÃO DE ESPECIALIDADES ===\n\n";

// Verifica especialidades no banco
echo "1. Especialidades no banco de dados:\n";
$stmt = $pdo->query("SELECT id, tenant_id, name, price_id, is_active, deleted_at FROM clinic_specialties ORDER BY id");
$specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($specialties)) {
    echo "   ❌ Nenhuma especialidade encontrada no banco!\n";
} else {
    foreach ($specialties as $spec) {
        $deleted = $spec['deleted_at'] ? 'DELETADO' : 'ATIVO';
        $price = $spec['price_id'] ? "Preço: {$spec['price_id']}" : "Sem preço";
        echo "   - ID: {$spec['id']} | Tenant: {$spec['tenant_id']} | Nome: '{$spec['name']}' | {$price} | Status: {$deleted}\n";
    }
}

echo "\n";

// Verifica tenant
echo "2. Verificando tenants:\n";
$stmt = $pdo->query("SELECT id, name, status FROM tenants WHERE status = 'active' LIMIT 5");
$tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($tenants as $tenant) {
    echo "   - ID: {$tenant['id']} | Nome: '{$tenant['name']}' | Status: {$tenant['status']}\n";
}

echo "\n";

// Testa o Model
echo "3. Testando Model ClinicSpecialty:\n";
use App\Models\ClinicSpecialty;
use App\Models\Tenant;

$tenantModel = new Tenant();
$tenants = $tenantModel->findAll(['status' => 'active'], ['id' => 'ASC'], 1);

if (empty($tenants)) {
    echo "   ❌ Nenhum tenant ativo encontrado!\n";
} else {
    $tenant = $tenants[0];
    $tenantId = (int)$tenant['id'];
    echo "   ✅ Tenant encontrado: ID {$tenantId}\n";
    
    $specialtyModel = new ClinicSpecialty();
    
    // Testa findAll
    $allSpecialties = $specialtyModel->findAll([
        'tenant_id' => $tenantId
    ], ['sort_order' => 'ASC', 'name' => 'ASC']);
    
    echo "   ✅ findAll() retornou: " . count($allSpecialties) . " especialidades\n";
    
    if (!empty($allSpecialties)) {
        echo "   Especialidades encontradas:\n";
        foreach ($allSpecialties as $spec) {
            echo "      - ID: {$spec['id']} | Nome: '{$spec['name']}' | Ativa: " . ($spec['is_active'] ? 'Sim' : 'Não') . "\n";
        }
    }
    
    // Testa findActiveByTenant
    $activeSpecialties = $specialtyModel->findActiveByTenant($tenantId);
    echo "   ✅ findActiveByTenant() retornou: " . count($activeSpecialties) . " especialidades ativas\n";
}

echo "\n";

echo "=== DIAGNÓSTICO ===\n";
if (empty($specialties)) {
    echo "❌ Problema: Não há especialidades no banco de dados\n";
} else {
    $hasActive = false;
    foreach ($specialties as $spec) {
        if (!$spec['deleted_at'] && $spec['is_active']) {
            $hasActive = true;
            break;
        }
    }
    
    if (!$hasActive) {
        echo "⚠️  Problema: Todas as especialidades estão inativas ou deletadas\n";
    } else {
        echo "✅ Especialidades existem no banco\n";
        echo "💡 Verifique:\n";
        echo "   1. Se o tenant_id da especialidade corresponde ao tenant logado\n";
        echo "   2. Se há erros no console do navegador (F12)\n";
        echo "   3. Se a API está retornando os dados corretamente\n";
    }
}

