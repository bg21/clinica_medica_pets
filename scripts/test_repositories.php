<?php
/**
 * Script de Teste - Repositories (Client, Pet, Professional)
 * 
 * Valida se a implementação do Repository Pattern não quebrou
 * a funcionalidade dos controllers
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\ClientRepository;
use App\Repositories\PetRepository;
use App\Repositories\ProfessionalRepository;
use App\Models\Client;
use App\Models\Pet;
use App\Models\Professional;
use App\Models\User;
use App\Utils\Database;

echo "🧪 TESTE DOS REPOSITORIES (Client, Pet, Professional)\n";
echo "============================================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// Função auxiliar para registrar testes
function test($name, $callback) {
    global $tests, $passed, $failed;
    
    echo "📋 Testando: $name\n";
    try {
        $result = $callback();
        if ($result === true || (is_array($result) && !empty($result['success']))) {
            $tests[] = ['name' => $name, 'status' => 'passed'];
            $passed++;
            echo "   ✅ PASSOU\n\n";
            return true;
        } else {
            $tests[] = ['name' => $name, 'status' => 'failed', 'error' => 'Retornou false ou array vazio'];
            $failed++;
            echo "   ❌ FALHOU: Retornou false ou array vazio\n\n";
            return false;
        }
    } catch (\Exception $e) {
        $tests[] = ['name' => $name, 'status' => 'failed', 'error' => $e->getMessage()];
        $failed++;
        echo "   ❌ FALHOU: " . $e->getMessage() . "\n\n";
        return false;
    }
}

// Conecta ao banco
try {
    require_once __DIR__ . '/../config/config.php';
    \Config::load();
    Database::getInstance(); // Inicializa conexão
    echo "✅ Conexão com banco de dados estabelecida\n\n";
} catch (\Exception $e) {
    die("❌ Erro ao conectar ao banco: " . $e->getMessage() . "\n");
}

// ============================================
// TESTES DO CLIENT REPOSITORY
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📦 CLIENT REPOSITORY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$clientRepository = new ClientRepository(new Client(), new Pet());

// Busca ou cria um tenant para os testes
$tenantModel = new \App\Models\Tenant();
$tenant = $tenantModel->findById(1);
if (!$tenant) {
    // Tenta buscar qualquer tenant existente
    $tenants = $tenantModel->findAll([], [], 1);
    if (!empty($tenants)) {
        $testTenantId = (int)$tenants[0]['id'];
    } else {
        // Cria um tenant de teste
        $testTenantId = $tenantModel->insert([
            'name' => 'Tenant Teste ' . time(),
            'api_key' => bin2hex(random_bytes(32)),
            'status' => 'active'
        ]);
    }
} else {
    $testTenantId = 1;
}
echo "📌 Usando tenant_id: $testTenantId\n\n";

test('ClientRepository pode ser criado', function() use ($clientRepository) {
    return $clientRepository !== null;
});

test('ClientRepository::findByTenant retorna array', function() use ($clientRepository, $testTenantId) {
    $result = $clientRepository->findByTenant($testTenantId);
    return is_array($result);
});

test('ClientRepository::create() aceita dados válidos', function() use ($clientRepository, $testTenantId) {
    $data = [
        'name' => 'Cliente Teste ' . time(),
        'email' => 'teste' . time() . '@example.com',
        'phone' => '(11) 99999-9999'
    ];
    
    $clientId = $clientRepository->create($testTenantId, $data);
    return $clientId > 0;
});

test('ClientRepository::findById retorna dados ou null', function() use ($clientRepository, $testTenantId) {
    // Primeiro cria um cliente
    $data = [
        'name' => 'Cliente Teste Find ' . time(),
        'email' => 'find' . time() . '@example.com'
    ];
    $clientId = $clientRepository->create($testTenantId, $data);
    
    // Depois busca
    $client = $clientRepository->findById($clientId);
    return $client !== null && isset($client['id']);
});

test('ClientRepository::findByTenantAndId valida tenant', function() use ($clientRepository, $testTenantId) {
    // Cria um cliente
    $data = [
        'name' => 'Cliente Teste Tenant ' . time(),
        'email' => 'tenant' . time() . '@example.com'
    ];
    $clientId = $clientRepository->create($testTenantId, $data);
    
    // Busca com tenant correto
    $client = $clientRepository->findByTenantAndId($testTenantId, $clientId);
    $found = $client !== null;
    
    // Busca com tenant incorreto (deve retornar null)
    $clientWrong = $clientRepository->findByTenantAndId(99999, $clientId);
    $notFound = $clientWrong === null;
    
    return $found && $notFound;
});

test('ClientRepository::update() atualiza dados', function() use ($clientRepository, $testTenantId) {
    // Cria um cliente
    $data = [
        'name' => 'Cliente Teste Update ' . time(),
        'email' => 'update' . time() . '@example.com'
    ];
    $clientId = $clientRepository->create($testTenantId, $data);
    
    // Atualiza
    $updateData = ['name' => 'Cliente Atualizado ' . time()];
    $success = $clientRepository->update($clientId, $updateData);
    
    // Verifica se foi atualizado
    $updated = $clientRepository->findById($clientId);
    return $success && $updated['name'] === $updateData['name'];
});

test('ClientRepository::delete() faz soft delete', function() use ($clientRepository, $testTenantId) {
    // Cria um cliente
    $data = [
        'name' => 'Cliente Teste Delete ' . time(),
        'email' => 'delete' . time() . '@example.com'
    ];
    $clientId = $clientRepository->create($testTenantId, $data);
    
    // Deleta
    $success = $clientRepository->delete($clientId);
    
    // Verifica se foi deletado (soft delete - findByTenantAndId deve retornar null)
    $deleted = $clientRepository->findByTenantAndId($testTenantId, $clientId);
    return $success && $deleted === null;
});

test('ClientRepository::getPets() retorna array', function() use ($clientRepository, $testTenantId) {
    // Cria um cliente
    $data = [
        'name' => 'Cliente Teste Pets ' . time(),
        'email' => 'pets' . time() . '@example.com'
    ];
    $clientId = $clientRepository->create($testTenantId, $data);
    
    // Busca pets (pode estar vazio, mas deve retornar array)
    $pets = $clientRepository->getPets($testTenantId, $clientId);
    return is_array($pets);
});

// ============================================
// TESTES DO PET REPOSITORY
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📦 PET REPOSITORY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$petRepository = new PetRepository(new Pet(), new Client());

// Cria um cliente para os testes de pet
$clientData = [
    'name' => 'Cliente para Pet Teste ' . time(),
    'email' => 'petclient' . time() . '@example.com'
];
$testClientId = $clientRepository->create($testTenantId, $clientData);

test('PetRepository pode ser criado', function() use ($petRepository) {
    return $petRepository !== null;
});

test('PetRepository::findByTenant retorna array', function() use ($petRepository, $testTenantId) {
    $result = $petRepository->findByTenant($testTenantId);
    return is_array($result);
});

test('PetRepository::create() aceita dados válidos', function() use ($petRepository, $testTenantId, $testClientId) {
    $data = [
        'client_id' => $testClientId,
        'name' => 'Pet Teste ' . time(),
        'species' => 'Cão',
        'breed' => 'Labrador'
    ];
    
    $petId = $petRepository->create($testTenantId, $data);
    return $petId > 0;
});

test('PetRepository::findById retorna dados ou null', function() use ($petRepository, $testTenantId, $testClientId) {
    // Cria um pet
    $data = [
        'client_id' => $testClientId,
        'name' => 'Pet Teste Find ' . time()
    ];
    $petId = $petRepository->create($testTenantId, $data);
    
    // Busca
    $pet = $petRepository->findById($petId);
    return $pet !== null && isset($pet['id']);
});

test('PetRepository::findByTenantAndId valida tenant', function() use ($petRepository, $testTenantId, $testClientId) {
    // Cria um pet
    $data = [
        'client_id' => $testClientId,
        'name' => 'Pet Teste Tenant ' . time()
    ];
    $petId = $petRepository->create($testTenantId, $data);
    
    // Busca com tenant correto
    $pet = $petRepository->findByTenantAndId($testTenantId, $petId);
    $found = $pet !== null;
    
    // Busca com tenant incorreto
    $petWrong = $petRepository->findByTenantAndId(99999, $petId);
    $notFound = $petWrong === null;
    
    return $found && $notFound;
});

test('PetRepository::findByClient retorna array', function() use ($petRepository, $testTenantId, $testClientId) {
    // Cria um pet para o cliente
    $data = [
        'client_id' => $testClientId,
        'name' => 'Pet Teste Client ' . time()
    ];
    $petRepository->create($testTenantId, $data);
    
    // Busca pets do cliente
    $pets = $petRepository->findByClient($testTenantId, $testClientId);
    return is_array($pets);
});

test('PetRepository::clientExists() valida cliente', function() use ($petRepository, $testTenantId, $testClientId) {
    // Cliente existe
    $exists = $petRepository->clientExists($testTenantId, $testClientId);
    
    // Cliente não existe
    $notExists = !$petRepository->clientExists($testTenantId, 99999);
    
    return $exists && $notExists;
});

test('PetRepository::belongsToClient() valida relacionamento', function() use ($petRepository, $testTenantId, $testClientId) {
    // Cria um pet
    $data = [
        'client_id' => $testClientId,
        'name' => 'Pet Teste Belongs ' . time()
    ];
    $petId = $petRepository->create($testTenantId, $data);
    
    // Verifica se pertence ao cliente
    $belongs = $petRepository->belongsToClient($testTenantId, $petId, $testClientId);
    
    // Verifica se não pertence a outro cliente
    $notBelongs = !$petRepository->belongsToClient($testTenantId, $petId, 99999);
    
    return $belongs && $notBelongs;
});

test('PetRepository::update() atualiza dados', function() use ($petRepository, $testTenantId, $testClientId) {
    // Cria um pet
    $data = [
        'client_id' => $testClientId,
        'name' => 'Pet Teste Update ' . time()
    ];
    $petId = $petRepository->create($testTenantId, $data);
    
    // Atualiza
    $updateData = ['name' => 'Pet Atualizado ' . time()];
    $success = $petRepository->update($petId, $updateData);
    
    // Verifica se foi atualizado
    $updated = $petRepository->findById($petId);
    return $success && $updated['name'] === $updateData['name'];
});

test('PetRepository::delete() faz soft delete', function() use ($petRepository, $testTenantId, $testClientId) {
    // Cria um pet
    $data = [
        'client_id' => $testClientId,
        'name' => 'Pet Teste Delete ' . time()
    ];
    $petId = $petRepository->create($testTenantId, $data);
    
    // Deleta
    $success = $petRepository->delete($petId);
    
    // Verifica se foi deletado
    $deleted = $petRepository->findByTenantAndId($testTenantId, $petId);
    return $success && $deleted === null;
});

// ============================================
// TESTES DO PROFESSIONAL REPOSITORY
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📦 PROFESSIONAL REPOSITORY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$professionalRepository = new ProfessionalRepository(new Professional());

// Cria um usuário para os testes de profissional
$userModel = new User();
$userData = [
    'tenant_id' => $testTenantId,
    'name' => 'Usuário Profissional Teste ' . time(),
    'email' => 'prof' . time() . '@example.com',
    'password_hash' => password_hash('teste123', PASSWORD_DEFAULT),
    'role' => 'professional'
];
$testUserId = $userModel->insert($userData);

test('ProfessionalRepository pode ser criado', function() use ($professionalRepository) {
    return $professionalRepository !== null;
});

test('ProfessionalRepository::findByTenant retorna array', function() use ($professionalRepository, $testTenantId) {
    $result = $professionalRepository->findByTenant($testTenantId);
    return is_array($result);
});

test('ProfessionalRepository::create() aceita dados válidos', function() use ($professionalRepository, $testTenantId, $testUserId) {
    // ✅ CORREÇÃO: CRMV tem limite de 20 caracteres
    $timestamp = substr(time(), -6);
    $data = [
        'user_id' => $testUserId,
        'crmv' => 'CRMV-' . $timestamp,
        'status' => 'active',
        'default_consultation_duration' => 30
    ];
    
    $professionalId = $professionalRepository->create($testTenantId, $data);
    return $professionalId > 0;
});

test('ProfessionalRepository::findById retorna dados ou null', function() use ($professionalRepository, $testTenantId, $testUserId) {
    // Cria um profissional
    // ✅ CORREÇÃO: CRMV tem limite de 20 caracteres
    $timestamp = substr(time(), -6);
    $data = [
        'user_id' => $testUserId,
        'crmv' => 'CRMV-F-' . $timestamp,
        'status' => 'active'
    ];
    $professionalId = $professionalRepository->create($testTenantId, $data);
    
    // Busca
    $professional = $professionalRepository->findById($professionalId);
    return $professional !== null && isset($professional['id']);
});

test('ProfessionalRepository::findByTenantAndId valida tenant', function() use ($professionalRepository, $testTenantId, $testUserId) {
    // Cria um profissional
    // ✅ CORREÇÃO: CRMV tem limite de 20 caracteres
    $timestamp = substr(time(), -6);
    $data = [
        'user_id' => $testUserId,
        'crmv' => 'CRMV-T-' . $timestamp,
        'status' => 'active'
    ];
    $professionalId = $professionalRepository->create($testTenantId, $data);
    
    // Busca com tenant correto
    $professional = $professionalRepository->findByTenantAndId($testTenantId, $professionalId);
    $found = $professional !== null;
    
    // Busca com tenant incorreto
    $professionalWrong = $professionalRepository->findByTenantAndId(99999, $professionalId);
    $notFound = $professionalWrong === null;
    
    return $found && $notFound;
});

test('ProfessionalRepository::findByUserAndTenant retorna profissional', function() use ($professionalRepository, $testTenantId, $testUserId) {
    // Cria um profissional
    // ✅ CORREÇÃO: CRMV tem limite de 20 caracteres
    $timestamp = substr(time(), -6);
    $data = [
        'user_id' => $testUserId,
        'crmv' => 'CRMV-U-' . $timestamp,
        'status' => 'active'
    ];
    $professionalRepository->create($testTenantId, $data);
    
    // Busca por user_id
    $professional = $professionalRepository->findByUserAndTenant($testTenantId, $testUserId);
    return $professional !== null && $professional['user_id'] == $testUserId;
});

test('ProfessionalRepository::findByTenantWithUser retorna array', function() use ($professionalRepository, $testTenantId, $testUserId) {
    // Cria um profissional
    // ✅ CORREÇÃO: CRMV tem limite de 20 caracteres
    $timestamp = substr(time(), -6);
    $data = [
        'user_id' => $testUserId,
        'crmv' => 'CRMV-W-' . $timestamp,
        'status' => 'active'
    ];
    $professionalRepository->create($testTenantId, $data);
    
    // Busca com dados do usuário
    $professionals = $professionalRepository->findByTenantWithUser($testTenantId);
    return is_array($professionals);
});

test('ProfessionalRepository::update() atualiza dados', function() use ($professionalRepository, $testTenantId, $testUserId) {
    // Cria um profissional
    // ✅ CORREÇÃO: CRMV tem limite de 20 caracteres, então usamos um valor mais curto
    $timestamp = substr(time(), -6); // Últimos 6 dígitos do timestamp
    $data = [
        'user_id' => $testUserId,
        'crmv' => 'CRMV-U-' . $timestamp,
        'status' => 'active'
    ];
    $professionalId = $professionalRepository->create($testTenantId, $data);
    
    if ($professionalId <= 0) {
        return false; // Falhou ao criar
    }
    
    // Atualiza com um CRMV diferente (também dentro do limite de 20 caracteres)
    $newTimestamp = substr(time() + 1, -6); // Garante que seja diferente
    $newCrmv = 'CRMV-N-' . $newTimestamp; // Máximo 15 caracteres
    $updateData = ['crmv' => $newCrmv];
    $success = $professionalRepository->update($professionalId, $updateData);
    
    if (!$success) {
        return false; // Falhou ao atualizar
    }
    
    // Verifica se foi atualizado
    $updated = $professionalRepository->findById($professionalId);
    if ($updated === null) {
        return false; // Não encontrou o profissional
    }
    
    // Verifica se o campo foi atualizado
    return isset($updated['crmv']) && $updated['crmv'] === $newCrmv;
});

test('ProfessionalRepository::delete() faz soft delete', function() use ($professionalRepository, $testTenantId, $testUserId) {
    // Cria um profissional
    // ✅ CORREÇÃO: CRMV tem limite de 20 caracteres
    $timestamp = substr(time(), -6);
    $data = [
        'user_id' => $testUserId,
        'crmv' => 'CRMV-D-' . $timestamp,
        'status' => 'active'
    ];
    $professionalId = $professionalRepository->create($testTenantId, $data);
    
    // Deleta
    $success = $professionalRepository->delete($professionalId);
    
    // Verifica se foi deletado
    $deleted = $professionalRepository->findByTenantAndId($testTenantId, $professionalId);
    return $success && $deleted === null;
});

// ============================================
// RESUMO FINAL
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RESUMO DOS TESTES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 2) : 0;

echo "Total de testes: $total\n";
echo "✅ Passou: $passed\n";
echo "❌ Falhou: $failed\n";
echo "📊 Taxa de sucesso: $percentage%\n\n";

if ($failed === 0) {
    echo "🎉 TODOS OS TESTES PASSARAM! Os repositories estão funcionando corretamente.\n";
    exit(0);
} else {
    echo "⚠️  Alguns testes falharam. Verifique os erros acima.\n";
    exit(1);
}

