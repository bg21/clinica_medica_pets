<?php

/**
 * Testa atualização de especialidade simulando exatamente o que o controller faz
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\ClinicSpecialty;
use App\Models\Tenant;

echo "=== TESTE DIRETO DE ATUALIZAÇÃO (SIMULANDO CONTROLLER) ===\n\n";

try {
    $tenantModel = new Tenant();
    $tenants = $tenantModel->findAll(['status' => 'active'], ['id' => 'ASC'], 1);
    
    if (empty($tenants)) {
        echo "❌ Nenhum tenant ativo\n";
        exit(1);
    }
    
    $tenant = $tenants[0];
    $tenantId = (int)$tenant['id'];
    echo "✅ Tenant ID: {$tenantId}\n\n";
    
    $specialtyModel = new ClinicSpecialty();
    
    // Busca primeira especialidade
    $specialties = $specialtyModel->findAll([
        'tenant_id' => $tenantId
    ], ['id' => 'ASC'], 1);
    
    if (empty($specialties)) {
        echo "❌ Nenhuma especialidade encontrada\n";
        exit(1);
    }
    
    $specialty = $specialties[0];
    $specialtyId = (int)$specialty['id'];
    
    echo "✅ Especialidade encontrada:\n";
    echo "   - ID: {$specialty['id']}\n";
    echo "   - Nome: '{$specialty['name']}'\n";
    echo "   - Price ID: " . ($specialty['price_id'] ?? 'NULL') . "\n\n";
    
    // Simula dados que vêm do JSON (como o controller recebe)
    $data = [
        'name' => $specialty['name'], // Mesmo nome
        'description' => $specialty['description'] ?? null,
        'price_id' => '', // String vazia (como vem do form)
        'sort_order' => (int)($specialty['sort_order'] ?? 0),
        'is_active' => true
    ];
    
    echo "📥 Dados recebidos (simulando request):\n";
    print_r($data);
    echo "\n";
    
    // Processa como o controller faz
    $updateData = [];
    $allowedFields = ['name', 'description', 'price_id', 'is_active', 'sort_order'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if ($field === 'is_active') {
                $updateData[$field] = (bool)$data[$field];
            } elseif ($field === 'sort_order') {
                $updateData[$field] = (int)$data[$field];
            } elseif ($field === 'name') {
                $updateData[$field] = trim($data[$field]);
            } elseif ($field === 'price_id') {
                // Converte string vazia para NULL
                $updateData[$field] = !empty($data[$field]) ? trim($data[$field]) : null;
            } elseif ($field === 'description') {
                // Converte string vazia para NULL
                $updateData[$field] = !empty(trim($data[$field])) ? trim($data[$field]) : null;
            } else {
                $updateData[$field] = $data[$field];
            }
        }
    }
    
    echo "📤 Dados processados para update:\n";
    print_r($updateData);
    echo "\n";
    
    if (empty($updateData)) {
        echo "⚠️  Nenhum dado para atualizar\n";
        exit(0);
    }
    
    echo "🔄 Executando update...\n";
    $result = $specialtyModel->update($specialtyId, $updateData);
    
    if ($result) {
        echo "✅ Update executado com sucesso\n\n";
        
        // Busca atualizada
        $updated = $specialtyModel->findByTenantAndId($tenantId, $specialtyId);
        
        if ($updated) {
            echo "✅ Especialidade atualizada:\n";
            echo "   - ID: {$updated['id']}\n";
            echo "   - Nome: '{$updated['name']}'\n";
            echo "   - Price ID: " . ($updated['price_id'] ?? 'NULL') . "\n";
            echo "   - Descrição: " . ($updated['description'] ?? 'NULL') . "\n";
            echo "   - Ativa: " . ($updated['is_active'] ? 'Sim' : 'Não') . "\n";
        } else {
            echo "❌ Especialidade não encontrada após update\n";
        }
    } else {
        echo "❌ Update retornou false\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

