<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\ClinicSpecialty;
use App\Models\Tenant;

echo "=== TESTE DE ATUALIZAÇÃO DE ESPECIALIDADE ===\n\n";

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
    
    // Busca primeira especialidade
    $specialties = $specialtyModel->findAll([
        'tenant_id' => $tenantId
    ], ['id' => 'ASC'], 1);
    
    if (empty($specialties)) {
        echo "❌ Nenhuma especialidade encontrada para testar\n";
        exit(1);
    }
    
    $specialty = $specialties[0];
    $specialtyId = (int)$specialty['id'];
    
    echo "✅ Especialidade encontrada:\n";
    echo "   - ID: {$specialty['id']}\n";
    echo "   - Nome: '{$specialty['name']}'\n";
    echo "   - Price ID atual: " . ($specialty['price_id'] ?? 'NULL') . "\n\n";
    
    // Testa atualização com price_id = null
    echo "Testando atualização com price_id = null...\n";
    $updateData = [
        'price_id' => null
    ];
    
    $result = $specialtyModel->update($specialtyId, $updateData);
    
    if ($result) {
        echo "✅ Update executado com sucesso\n";
        
        // Verifica se foi atualizado
        $updated = $specialtyModel->findById($specialtyId);
        if ($updated) {
            echo "✅ Especialidade atualizada:\n";
            echo "   - Price ID: " . ($updated['price_id'] ?? 'NULL') . "\n";
        } else {
            echo "❌ Especialidade não encontrada após update\n";
        }
    } else {
        echo "❌ Update retornou false\n";
    }
    
    // Testa atualização com price_id = string vazia (deve converter para null)
    echo "\nTestando atualização com price_id = '' (string vazia)...\n";
    $updateData2 = [
        'price_id' => ''
    ];
    
    // Simula o que o controller faz
    $updateData2['price_id'] = !empty($updateData2['price_id']) ? trim($updateData2['price_id']) : null;
    
    $result2 = $specialtyModel->update($specialtyId, $updateData2);
    
    if ($result2) {
        echo "✅ Update com string vazia executado\n";
        $updated2 = $specialtyModel->findById($specialtyId);
        if ($updated2) {
            echo "   - Price ID: " . ($updated2['price_id'] ?? 'NULL') . "\n";
        }
    }
    
    echo "\n✅ Teste concluído!\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

