<?php
/**
 * Script de Teste - Repositories (User, Exam)
 * 
 * Valida se a implementação do Repository Pattern não quebrou
 * a funcionalidade dos controllers
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\UserRepository;
use App\Repositories\ExamRepository;
use App\Models\User;
use App\Models\Exam;
use App\Models\Pet;
use App\Models\Client;
use App\Models\Professional;
use App\Utils\Database;

echo "🧪 TESTE DOS REPOSITORIES (User, Exam)\n";
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
// TESTES DO USER REPOSITORY
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📦 USER REPOSITORY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$userRepository = new UserRepository(new User());

// Busca ou cria um tenant para os testes
$tenantModel = new \App\Models\Tenant();
$tenants = $tenantModel->findAll([], [], 1);
if (empty($tenants)) {
    die("❌ Nenhum tenant encontrado. Crie um tenant primeiro.\n");
}
$testTenantId = (int)$tenants[0]['id'];
echo "📌 Usando tenant_id: $testTenantId\n\n";

test('UserRepository pode ser criado', function() use ($userRepository) {
    return $userRepository !== null;
});

test('UserRepository::findByTenant retorna array', function() use ($userRepository, $testTenantId) {
    $result = $userRepository->findByTenant($testTenantId);
    return is_array($result);
});

test('UserRepository::create() aceita dados válidos', function() use ($userRepository, $testTenantId) {
    $timestamp = substr(time(), -6);
    $data = [
        'email' => 'teste' . $timestamp . '@example.com',
        'password' => 'senha123',
        'name' => 'Usuário Teste ' . $timestamp,
        'role' => 'viewer'
    ];
    
    $userId = $userRepository->create($testTenantId, $data);
    return $userId > 0;
});

test('UserRepository::findById retorna dados ou null', function() use ($userRepository, $testTenantId) {
    // Cria um usuário
    $timestamp = substr(time(), -6);
    $data = [
        'email' => 'teste-find' . $timestamp . '@example.com',
        'password' => 'senha123',
        'name' => 'Usuário Find ' . $timestamp
    ];
    $userId = $userRepository->create($testTenantId, $data);
    
    // Busca
    $user = $userRepository->findById($userId);
    return $user !== null && isset($user['id']);
});

test('UserRepository::findByTenantAndId valida tenant', function() use ($userRepository, $testTenantId) {
    // Cria um usuário
    $timestamp = substr(time(), -6);
    $data = [
        'email' => 'teste-tenant' . $timestamp . '@example.com',
        'password' => 'senha123',
        'name' => 'Usuário Tenant ' . $timestamp
    ];
    $userId = $userRepository->create($testTenantId, $data);
    
    // Busca com tenant correto
    $user = $userRepository->findByTenantAndId($testTenantId, $userId);
    $found = $user !== null;
    
    // Busca com tenant incorreto
    $userWrong = $userRepository->findByTenantAndId(99999, $userId);
    $notFound = $userWrong === null;
    
    return $found && $notFound;
});

test('UserRepository::findByEmailAndTenant retorna usuário', function() use ($userRepository, $testTenantId) {
    // Cria um usuário
    $timestamp = substr(time(), -6);
    $email = 'teste-email' . $timestamp . '@example.com';
    $data = [
        'email' => $email,
        'password' => 'senha123',
        'name' => 'Usuário Email ' . $timestamp
    ];
    $userRepository->create($testTenantId, $data);
    
    // Busca por email
    $user = $userRepository->findByEmailAndTenant($email, $testTenantId);
    return $user !== null && $user['email'] === $email;
});

test('UserRepository::emailExists valida email', function() use ($userRepository, $testTenantId) {
    // Cria um usuário
    $timestamp = substr(time(), -6);
    $email = 'teste-exists' . $timestamp . '@example.com';
    $data = [
        'email' => $email,
        'password' => 'senha123',
        'name' => 'Usuário Exists ' . $timestamp
    ];
    $userRepository->create($testTenantId, $data);
    
    // Verifica se existe
    $exists = $userRepository->emailExists($email, $testTenantId);
    $notExists = !$userRepository->emailExists('naoexiste' . $timestamp . '@example.com', $testTenantId);
    
    return $exists && $notExists;
});

test('UserRepository::update() atualiza dados', function() use ($userRepository, $testTenantId) {
    // Cria um usuário
    $timestamp = substr(time(), -6);
    $data = [
        'email' => 'teste-update' . $timestamp . '@example.com',
        'password' => 'senha123',
        'name' => 'Usuário Update ' . $timestamp
    ];
    $userId = $userRepository->create($testTenantId, $data);
    
    if ($userId <= 0) {
        return false;
    }
    
    // Atualiza
    $newName = 'Usuário Atualizado ' . $timestamp;
    $updateData = ['name' => $newName];
    $success = $userRepository->update($userId, $updateData);
    
    if (!$success) {
        return false;
    }
    
    // Verifica se foi atualizado
    $updated = $userRepository->findById($userId);
    if ($updated === null) {
        return false;
    }
    
    return isset($updated['name']) && $updated['name'] === $newName;
});

test('UserRepository::updateRole() atualiza role', function() use ($userRepository, $testTenantId) {
    // Cria um usuário
    $timestamp = substr(time(), -6);
    $data = [
        'email' => 'teste-role' . $timestamp . '@example.com',
        'password' => 'senha123',
        'name' => 'Usuário Role ' . $timestamp,
        'role' => 'viewer'
    ];
    $userId = $userRepository->create($testTenantId, $data);
    
    // Atualiza role
    $success = $userRepository->updateRole($userId, 'editor');
    
    if (!$success) {
        return false;
    }
    
    // Verifica se foi atualizado
    $updated = $userRepository->findById($userId);
    return $updated !== null && $updated['role'] === 'editor';
});

test('UserRepository::isAdmin() verifica admin', function() use ($userRepository, $testTenantId) {
    // Cria um usuário admin
    $timestamp = substr(time(), -6);
    $data = [
        'email' => 'teste-admin' . $timestamp . '@example.com',
        'password' => 'senha123',
        'name' => 'Usuário Admin ' . $timestamp,
        'role' => 'admin'
    ];
    $adminId = $userRepository->create($testTenantId, $data);
    
    // Cria um usuário viewer
    $data2 = [
        'email' => 'teste-viewer' . $timestamp . '@example.com',
        'password' => 'senha123',
        'name' => 'Usuário Viewer ' . $timestamp,
        'role' => 'viewer'
    ];
    $viewerId = $userRepository->create($testTenantId, $data2);
    
    $isAdmin = $userRepository->isAdmin($adminId);
    $isNotAdmin = !$userRepository->isAdmin($viewerId);
    
    return $isAdmin && $isNotAdmin;
});

test('UserRepository::verifyPassword() valida senha', function() use ($userRepository, $testTenantId) {
    // Cria um usuário
    $timestamp = substr(time(), -6);
    $password = 'senha123';
    $data = [
        'email' => 'teste-password' . $timestamp . '@example.com',
        'password' => $password,
        'name' => 'Usuário Password ' . $timestamp
    ];
    $userId = $userRepository->create($testTenantId, $data);
    
    // Busca usuário para obter hash
    $user = $userRepository->findById($userId);
    if (!$user || !isset($user['password_hash'])) {
        return false;
    }
    
    // Verifica senha correta
    $correct = $userRepository->verifyPassword($password, $user['password_hash']);
    
    // Verifica senha incorreta
    $incorrect = !$userRepository->verifyPassword('senhaerrada', $user['password_hash']);
    
    return $correct && $incorrect;
});

// ============================================
// TESTES DO EXAM REPOSITORY
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📦 EXAM REPOSITORY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$examRepository = new ExamRepository(
    new Exam(),
    new Pet(),
    new Client(),
    new Professional()
);

// Cria um cliente e pet para os testes
$clientRepository = new \App\Repositories\ClientRepository(new Client(), new Pet());
$petRepository = new \App\Repositories\PetRepository(new Pet(), new Client());

$clientData = [
    'name' => 'Cliente Teste ' . time(),
    'email' => 'cliente' . time() . '@example.com',
    'phone' => '11999999999'
];
$testClientId = $clientRepository->create($testTenantId, $clientData);

$petData = [
    'client_id' => $testClientId,
    'name' => 'Pet Teste ' . time(),
    'species' => 'dog',
    'breed' => 'Labrador'
];
$testPetId = $petRepository->create($testTenantId, $petData);

echo "📌 Usando tenant_id: $testTenantId\n";
echo "📌 Usando client_id: $testClientId\n";
echo "📌 Usando pet_id: $testPetId\n\n";

test('ExamRepository pode ser criado', function() use ($examRepository) {
    return $examRepository !== null;
});

test('ExamRepository::findByTenant retorna array', function() use ($examRepository, $testTenantId) {
    $result = $examRepository->findByTenant($testTenantId);
    return is_array($result);
});

test('ExamRepository::create() aceita dados válidos', function() use ($examRepository, $testTenantId, $testClientId, $testPetId) {
    $data = [
        'pet_id' => $testPetId,
        'client_id' => $testClientId,
        'exam_date' => date('Y-m-d'),
        'status' => 'pending'
    ];
    
    $examId = $examRepository->create($testTenantId, $data);
    return $examId > 0;
});

test('ExamRepository::findById retorna dados ou null', function() use ($examRepository, $testTenantId, $testClientId, $testPetId) {
    // Cria um exame
    $data = [
        'pet_id' => $testPetId,
        'client_id' => $testClientId,
        'exam_date' => date('Y-m-d'),
        'status' => 'pending'
    ];
    $examId = $examRepository->create($testTenantId, $data);
    
    // Busca
    $exam = $examRepository->findById($examId);
    return $exam !== null && isset($exam['id']);
});

test('ExamRepository::findByTenantAndId valida tenant', function() use ($examRepository, $testTenantId, $testClientId, $testPetId) {
    // Cria um exame
    $data = [
        'pet_id' => $testPetId,
        'client_id' => $testClientId,
        'exam_date' => date('Y-m-d'),
        'status' => 'pending'
    ];
    $examId = $examRepository->create($testTenantId, $data);
    
    // Busca com tenant correto
    $exam = $examRepository->findByTenantAndId($testTenantId, $examId);
    $found = $exam !== null;
    
    // Busca com tenant incorreto
    $examWrong = $examRepository->findByTenantAndId(99999, $examId);
    $notFound = $examWrong === null;
    
    return $found && $notFound;
});

test('ExamRepository::findByPet retorna array', function() use ($examRepository, $testTenantId, $testClientId, $testPetId) {
    // Cria um exame
    $data = [
        'pet_id' => $testPetId,
        'client_id' => $testClientId,
        'exam_date' => date('Y-m-d'),
        'status' => 'pending'
    ];
    $examRepository->create($testTenantId, $data);
    
    // Busca por pet
    $exams = $examRepository->findByPet($testTenantId, $testPetId);
    return is_array($exams);
});

test('ExamRepository::petExists() valida pet', function() use ($examRepository, $testTenantId, $testPetId) {
    $exists = $examRepository->petExists($testTenantId, $testPetId);
    $notExists = !$examRepository->petExists($testTenantId, 99999);
    
    return $exists && $notExists;
});

test('ExamRepository::clientExists() valida cliente', function() use ($examRepository, $testTenantId, $testClientId) {
    $exists = $examRepository->clientExists($testTenantId, $testClientId);
    $notExists = !$examRepository->clientExists($testTenantId, 99999);
    
    return $exists && $notExists;
});

test('ExamRepository::update() atualiza dados', function() use ($examRepository, $testTenantId, $testClientId, $testPetId) {
    // Cria um exame
    $data = [
        'pet_id' => $testPetId,
        'client_id' => $testClientId,
        'exam_date' => date('Y-m-d'),
        'status' => 'pending'
    ];
    $examId = $examRepository->create($testTenantId, $data);
    
    if ($examId <= 0) {
        return false;
    }
    
    // Atualiza
    $updateData = ['status' => 'completed'];
    $success = $examRepository->update($examId, $updateData);
    
    if (!$success) {
        return false;
    }
    
    // Verifica se foi atualizado
    $updated = $examRepository->findById($examId);
    if ($updated === null) {
        return false;
    }
    
    return isset($updated['status']) && $updated['status'] === 'completed';
});

test('ExamRepository::delete() faz soft delete', function() use ($examRepository, $testTenantId, $testClientId, $testPetId) {
    // Cria um exame
    $data = [
        'pet_id' => $testPetId,
        'client_id' => $testClientId,
        'exam_date' => date('Y-m-d'),
        'status' => 'pending'
    ];
    $examId = $examRepository->create($testTenantId, $data);
    
    // Deleta
    $success = $examRepository->delete($examId);
    
    // Verifica se foi deletado (soft delete)
    $deleted = $examRepository->findByTenantAndId($testTenantId, $examId);
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

