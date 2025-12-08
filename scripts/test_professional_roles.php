<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\ProfessionalRole;
use App\Models\Tenant;

echo "=== TESTE DE PROFESSIONAL ROLES ===\n\n";

try {
    $tenantModel = new Tenant();
    $tenants = $tenantModel->findAll(['status' => 'active'], ['id' => 'ASC'], 1);
    
    if (empty($tenants)) {
        echo "❌ Nenhum tenant ativo encontrado\n";
        exit(1);
    }
    
    $tenant = $tenants[0];
    $tenantId = (int)$tenant['id'];
    echo "✅ Tenant encontrado: ID {$tenantId}\n\n";
    
    $roleModel = new ProfessionalRole();
    
    echo "Testando findActiveByTenant...\n";
    $roles = $roleModel->findActiveByTenant($tenantId);
    
    echo "✅ Roles encontradas: " . count($roles) . "\n";
    foreach ($roles as $role) {
        echo "   - ID: {$role['id']}, Nome: '{$role['name']}', Tenant: {$role['tenant_id']}\n";
    }
    
    echo "\n✅ Teste concluído!\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

