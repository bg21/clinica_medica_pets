<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\ClinicSpecialty;
use App\Models\Tenant;

echo "=== TESTE DIRETO DO MODEL ===\n\n";

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
    
    $specialtyModel = new ClinicSpecialty();
    echo "✅ Model ClinicSpecialty instanciado\n\n";
    
    echo "Testando findAll()...\n";
    $specialties = $specialtyModel->findAll([
        'tenant_id' => $tenantId
    ], ['sort_order' => 'ASC', 'name' => 'ASC']);
    
    echo "✅ findAll() executado com sucesso\n";
    echo "   - Retornou: " . count($specialties) . " especialidades\n";
    
    if (!empty($specialties)) {
        foreach ($specialties as $spec) {
            echo "   - ID: {$spec['id']} | Nome: '{$spec['name']}' | Ativa: " . ($spec['is_active'] ? 'Sim' : 'Não') . "\n";
        }
    }
    
    echo "\n✅ Teste concluído sem erros!\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

