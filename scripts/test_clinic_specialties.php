<?php

/**
 * Script para testar o sistema de especialidades da clínica
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\ClinicSpecialty;
use App\Models\Tenant;

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

echo "=== TESTE DO SISTEMA DE ESPECIALIDADES ===\n\n";

// 1. Verifica se a tabela existe
echo "1. Verificando estrutura da tabela...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'clinic_specialties'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "   ✅ Tabela 'clinic_specialties' existe\n";
        
        // Verifica colunas
        $stmt = $pdo->query("DESCRIBE clinic_specialties");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   ✅ Colunas encontradas: " . count($columns) . "\n";
        
        $requiredColumns = ['id', 'tenant_id', 'name', 'description', 'price_id', 'is_active', 'sort_order', 'created_at', 'updated_at', 'deleted_at'];
        $foundColumns = array_column($columns, 'Field');
        
        foreach ($requiredColumns as $col) {
            if (in_array($col, $foundColumns)) {
                echo "      ✅ Coluna '{$col}' existe\n";
            } else {
                echo "      ❌ Coluna '{$col}' NÃO encontrada\n";
            }
        }
    } else {
        echo "   ❌ Tabela 'clinic_specialties' NÃO existe!\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ❌ Erro ao verificar tabela: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 2. Busca um tenant para teste
echo "2. Buscando tenant para teste...\n";
$tenantModel = new Tenant();
$tenants = $tenantModel->findAll(['status' => 'active'], ['id' => 'ASC'], 1);

if (empty($tenants)) {
    echo "   ❌ Nenhum tenant ativo encontrado!\n";
    exit(1);
}

$tenant = $tenants[0];
$tenantId = (int)$tenant['id'];
echo "   ✅ Tenant encontrado: ID {$tenantId}, Nome: '{$tenant['name']}'\n";

echo "\n";

// 3. Testa o Model
echo "3. Testando Model ClinicSpecialty...\n";
try {
    $specialtyModel = new ClinicSpecialty();
    
    // Testa findActiveByTenant
    $activeSpecialties = $specialtyModel->findActiveByTenant($tenantId);
    echo "   ✅ findActiveByTenant() funcionando: " . count($activeSpecialties) . " especialidades ativas\n";
    
    // Testa findAll
    $allSpecialties = $specialtyModel->findAll(['tenant_id' => $tenantId]);
    echo "   ✅ findAll() funcionando: " . count($allSpecialties) . " especialidades totais\n";
    
} catch (\Exception $e) {
    echo "   ❌ Erro no Model: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 4. Testa criação de especialidade
echo "4. Testando criação de especialidade...\n";
try {
    $specialtyModel = new ClinicSpecialty();
    
    // Verifica se já existe uma especialidade de teste
    $existing = $specialtyModel->findByName($tenantId, 'Teste - Clínica Geral');
    
    if ($existing) {
        echo "   ⚠️  Especialidade de teste já existe (ID: {$existing['id']}), deletando...\n";
        $specialtyModel->delete((int)$existing['id']);
    }
    
    // Cria nova especialidade
    $testData = [
        'tenant_id' => $tenantId,
        'name' => 'Teste - Clínica Geral',
        'description' => 'Especialidade de teste criada pelo script',
        'price_id' => null, // Sem preço para teste
        'is_active' => true,
        'sort_order' => 0
    ];
    
    $specialtyId = $specialtyModel->insert($testData);
    echo "   ✅ Especialidade criada com sucesso! ID: {$specialtyId}\n";
    
    // Busca a especialidade criada
    $created = $specialtyModel->findById($specialtyId);
    if ($created) {
        echo "   ✅ Especialidade encontrada após criação:\n";
        echo "      - Nome: '{$created['name']}'\n";
        echo "      - Descrição: '{$created['description']}'\n";
        echo "      - Ativa: " . ($created['is_active'] ? 'Sim' : 'Não') . "\n";
        echo "      - Tenant ID: {$created['tenant_id']}\n";
    } else {
        echo "   ❌ Especialidade não foi encontrada após criação!\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Erro ao criar especialidade: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 5. Testa atualização
echo "5. Testando atualização de especialidade...\n";
try {
    $specialtyModel = new ClinicSpecialty();
    
    $updateData = [
        'name' => 'Teste - Clínica Geral (Atualizado)',
        'description' => 'Descrição atualizada',
        'sort_order' => 10
    ];
    
    $specialtyModel->update($specialtyId, $updateData);
    echo "   ✅ Especialidade atualizada com sucesso!\n";
    
    $updated = $specialtyModel->findById($specialtyId);
    if ($updated && $updated['name'] === $updateData['name']) {
        echo "   ✅ Dados atualizados corretamente:\n";
        echo "      - Nome: '{$updated['name']}'\n";
        echo "      - Ordem: {$updated['sort_order']}\n";
    } else {
        echo "   ❌ Dados não foram atualizados corretamente!\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Erro ao atualizar especialidade: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 6. Testa findByTenantAndId
echo "6. Testando findByTenantAndId()...\n";
try {
    $specialtyModel = new ClinicSpecialty();
    
    $found = $specialtyModel->findByTenantAndId($tenantId, $specialtyId);
    
    if ($found) {
        echo "   ✅ findByTenantAndId() funcionando corretamente\n";
        echo "      - ID encontrado: {$found['id']}\n";
    } else {
        echo "   ❌ findByTenantAndId() não encontrou a especialidade!\n";
    }
    
    // Testa proteção IDOR (deve retornar null para outro tenant)
    $wrongTenant = $specialtyModel->findByTenantAndId(999999, $specialtyId);
    if ($wrongTenant === null) {
        echo "   ✅ Proteção IDOR funcionando (não retorna dados de outro tenant)\n";
    } else {
        echo "   ❌ FALHA DE SEGURANÇA: Proteção IDOR não está funcionando!\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Erro ao testar findByTenantAndId(): " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 7. Testa validação de duplicatas
echo "7. Testando validação de duplicatas...\n";
try {
    $specialtyModel = new ClinicSpecialty();
    
    $duplicate = $specialtyModel->findByName($tenantId, 'Teste - Clínica Geral (Atualizado)');
    
    if ($duplicate && $duplicate['id'] == $specialtyId) {
        echo "   ✅ findByName() encontrou a especialidade correta\n";
    } else {
        echo "   ⚠️  findByName() não encontrou ou retornou especialidade diferente\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Erro ao testar validação: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 8. Testa soft delete
echo "8. Testando soft delete...\n";
try {
    $specialtyModel = new ClinicSpecialty();
    
    // Deleta a especialidade de teste
    $specialtyModel->delete($specialtyId);
    echo "   ✅ Soft delete executado\n";
    
    // Verifica se ainda existe no banco (deve existir com deleted_at preenchido)
    $stmt = $pdo->prepare("SELECT * FROM clinic_specialties WHERE id = :id");
    $stmt->execute(['id' => $specialtyId]);
    $deleted = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($deleted && !empty($deleted['deleted_at'])) {
        echo "   ✅ Soft delete funcionando (deleted_at preenchido)\n";
    } else {
        echo "   ❌ Soft delete não está funcionando corretamente!\n";
    }
    
    // Verifica se findById não retorna mais (deve retornar null)
    $shouldBeNull = $specialtyModel->findById($specialtyId);
    if ($shouldBeNull === null) {
        echo "   ✅ findById() não retorna registros deletados (correto)\n";
    } else {
        echo "   ❌ findById() ainda retorna registro deletado!\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Erro ao testar soft delete: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 9. Verifica rotas no index.php
echo "9. Verificando rotas no index.php...\n";
$indexContent = file_get_contents(__DIR__ . '/../public/index.php');

$routes = [
    'POST /v1/clinic/specialties',
    'GET /v1/clinic/specialties',
    'GET /v1/clinic/specialties/@id',
    'PUT /v1/clinic/specialties/@id',
    'DELETE /v1/clinic/specialties/@id',
    'GET /clinic/specialties'
];

$allRoutesFound = true;
foreach ($routes as $route) {
    if (strpos($indexContent, $route) !== false) {
        echo "   ✅ Rota encontrada: {$route}\n";
    } else {
        echo "   ❌ Rota NÃO encontrada: {$route}\n";
        $allRoutesFound = false;
    }
}

if ($allRoutesFound) {
    echo "   ✅ Todas as rotas estão registradas!\n";
} else {
    echo "   ⚠️  Algumas rotas não foram encontradas\n";
}

echo "\n";

// 10. Verifica view
echo "10. Verificando view...\n";
$viewPath = __DIR__ . '/../App/Views/clinic/specialties.php';
if (file_exists($viewPath)) {
    echo "   ✅ View existe: App/Views/clinic/specialties.php\n";
    
    $viewContent = file_get_contents($viewPath);
    $requiredElements = [
        'createSpecialtyForm',
        'editSpecialtyForm',
        'loadSpecialties',
        '/v1/clinic/specialties'
    ];
    
    $allElementsFound = true;
    foreach ($requiredElements as $element) {
        if (strpos($viewContent, $element) !== false) {
            echo "   ✅ Elemento encontrado: {$element}\n";
        } else {
            echo "   ❌ Elemento NÃO encontrado: {$element}\n";
            $allElementsFound = false;
        }
    }
} else {
    echo "   ❌ View não encontrada!\n";
}

echo "\n";

// 11. Verifica controller
echo "11. Verificando controller...\n";
$controllerPath = __DIR__ . '/../App/Controllers/ClinicSpecialtyController.php';
if (file_exists($controllerPath)) {
    echo "   ✅ Controller existe: App/Controllers/ClinicSpecialtyController.php\n";
    
    $controllerContent = file_get_contents($controllerPath);
    $requiredMethods = ['list', 'get', 'create', 'update', 'delete'];
    
    foreach ($requiredMethods as $method) {
        if (strpos($controllerContent, "public function {$method}") !== false) {
            echo "   ✅ Método encontrado: {$method}()\n";
        } else {
            echo "   ❌ Método NÃO encontrado: {$method}()\n";
        }
    }
} else {
    echo "   ❌ Controller não encontrado!\n";
}

echo "\n";

// 12. Verifica menu
echo "12. Verificando menu...\n";
$baseLayoutPath = __DIR__ . '/../App/Views/layouts/base.php';
if (file_exists($baseLayoutPath)) {
    $baseContent = file_get_contents($baseLayoutPath);
    
    if (strpos($baseContent, '/clinic/specialties') !== false && strpos($baseContent, 'clinic-specialties') !== false) {
        echo "   ✅ Link de especialidades encontrado no menu\n";
    } else {
        echo "   ❌ Link de especialidades NÃO encontrado no menu!\n";
    }
} else {
    echo "   ⚠️  Layout base não encontrado\n";
}

echo "\n";

echo "=== RESUMO ===\n";
echo "✅ Teste concluído!\n";
echo "Se todos os testes passaram, o sistema de especialidades está funcionando corretamente.\n";
echo "Acesse: /clinic/specialties para usar a interface.\n";

