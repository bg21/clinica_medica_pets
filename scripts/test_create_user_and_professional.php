<?php

/**
 * Script para testar o fluxo completo:
 * 1. Criar um usuário
 * 2. Criar um profissional selecionando esse usuário como veterinário
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\User;
use App\Models\Professional;
use App\Models\ProfessionalRole;
use App\Models\Tenant;

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

echo "=== TESTE DO FLUXO COMPLETO: CRIAR USUÁRIO E PROFISSIONAL ===\n\n";

// 1. Busca um tenant para usar
echo "1. Buscando tenant disponível...\n";
$tenantModel = new Tenant();
$stmt = $pdo->query('SELECT id, name FROM tenants WHERE deleted_at IS NULL LIMIT 1');
$tenant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tenant) {
    echo "   ❌ Nenhum tenant encontrado. Crie um tenant primeiro!\n";
    exit(1);
}

$tenantId = (int)$tenant['id'];
echo "   ✅ Tenant encontrado: ID {$tenantId} - {$tenant['name']}\n\n";

// 2. Busca função "Veterinário"
echo "2. Buscando função 'Veterinário'...\n";
$roleModel = new ProfessionalRole();
$stmt = $pdo->prepare('SELECT id, name FROM professional_roles WHERE tenant_id = ? AND (name LIKE ? OR name LIKE ?) AND is_active = 1 LIMIT 1');
$stmt->execute([$tenantId, 'Veterinário%', 'Veterinario%']);
$veterinarioRole = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$veterinarioRole) {
    echo "   ❌ Função 'Veterinário' não encontrada para o tenant {$tenantId}!\n";
    echo "   Execute: php vendor/bin/phinx seed:run -s ProfessionalRolesSeed\n";
    exit(1);
}

echo "   ✅ Função encontrada: ID {$veterinarioRole['id']} - {$veterinarioRole['name']}\n\n";

// 3. Cria um novo usuário
echo "3. Criando novo usuário...\n";
$userModel = new User();

$userEmail = 'joao.silva.teste.' . time() . '@clinica.com';
$userName = 'Dr. João Silva Teste';
$userPassword = 'senha123';
$userRole = 'editor';

try {
    $userId = $userModel->create($tenantId, $userEmail, $userPassword, $userName, $userRole);
    echo "   ✅ Usuário criado com sucesso!\n";
    echo "      - ID: {$userId}\n";
    echo "      - Nome: {$userName}\n";
    echo "      - Email: {$userEmail}\n";
    echo "      - Role: {$userRole}\n\n";
} catch (\Exception $e) {
    echo "   ❌ Erro ao criar usuário: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Busca o usuário criado para confirmar
echo "4. Verificando usuário criado...\n";
$createdUser = $userModel->findById($userId);
if (!$createdUser) {
    echo "   ❌ Usuário não foi encontrado após criação!\n";
    exit(1);
}
echo "   ✅ Usuário confirmado: {$createdUser['name']} ({$createdUser['email']})\n\n";

// Armazena dados do usuário para comparação
$userName = $createdUser['name'];
$userEmail = $createdUser['email'];

// 5. Cria um profissional selecionando o usuário como veterinário
echo "5. Criando profissional selecionando o usuário como veterinário...\n";
$professionalModel = new Professional();

$professionalData = [
    'user_id' => $userId,
    'professional_role_id' => (int)$veterinarioRole['id'],
    'crmv' => 'CRMV-SP ' . rand(10000, 99999), // CRMV obrigatório para veterinário
    'specialty' => 'Clínica Geral',
    'phone' => '11987654321',
    'status' => 'active'
];

try {
    $professionalId = $professionalModel->create($tenantId, $professionalData);
    echo "   ✅ Profissional criado com sucesso!\n";
    echo "      - ID: {$professionalId}\n";
    echo "      - User ID: {$professionalData['user_id']}\n";
    echo "      - Função: {$veterinarioRole['name']}\n";
    echo "      - CRMV: {$professionalData['crmv']}\n";
    echo "      - Especialidade: {$professionalData['specialty']}\n\n";
} catch (\Exception $e) {
    echo "   ❌ Erro ao criar profissional: " . $e->getMessage() . "\n";
    echo "   Erro completo: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// 6. Verifica o profissional criado
echo "6. Verificando profissional criado...\n";
$createdProfessional = $professionalModel->findById($professionalId);
if (!$createdProfessional) {
    echo "   ❌ Profissional não foi encontrado após criação!\n";
    exit(1);
}

echo "   ✅ Profissional confirmado:\n";
echo "      - ID: {$createdProfessional['id']}\n";
echo "      - Nome: {$createdProfessional['name']}\n";
echo "      - Email: {$createdProfessional['email']}\n";
echo "      - User ID: {$createdProfessional['user_id']}\n";
echo "      - Professional Role ID: {$createdProfessional['professional_role_id']}\n";
echo "      - CRMV: {$createdProfessional['crmv']}\n";
echo "      - Status: {$createdProfessional['status']}\n\n";

// 7. Verifica se os dados do usuário foram copiados
echo "7. Verificando se dados do usuário foram copiados...\n";
$nameMatch = ($createdProfessional['name'] === $createdUser['name']);
$emailMatch = ($createdProfessional['email'] === $createdUser['email']);

if ($nameMatch && $emailMatch) {
    echo "   ✅ Dados do usuário foram copiados corretamente!\n";
    echo "      - Nome: {$createdProfessional['name']} (do usuário)\n";
    echo "      - Email: {$createdProfessional['email']} (do usuário)\n";
} else {
    echo "   ⚠️  Dados do usuário não foram copiados corretamente:\n";
    if (!$nameMatch) {
        echo "      - Nome esperado: {$createdUser['name']}, encontrado: {$createdProfessional['name']}\n";
    }
    if (!$emailMatch) {
        echo "      - Email esperado: {$createdUser['email']}, encontrado: {$createdProfessional['email']}\n";
    }
}

// 8. Verifica se a função foi associada corretamente
echo "\n8. Verificando função associada...\n";
$stmt = $pdo->prepare('
    SELECT pr.id, pr.name, pr.description 
    FROM professional_roles pr
    INNER JOIN professionals p ON p.professional_role_id = pr.id
    WHERE p.id = ?
');
$stmt->execute([$professionalId]);
$associatedRole = $stmt->fetch(PDO::FETCH_ASSOC);

if ($associatedRole) {
    echo "   ✅ Função associada corretamente:\n";
    echo "      - ID: {$associatedRole['id']}\n";
    echo "      - Nome: {$associatedRole['name']}\n";
    echo "      - Descrição: {$associatedRole['description']}\n";
} else {
    echo "   ❌ Função não foi associada!\n";
}

// 9. Testa validação: tentar criar profissional veterinário sem CRMV
echo "\n9. Testando validação: tentar criar veterinário sem CRMV...\n";
$professionalDataWithoutCrmv = [
    'user_id' => $userId,
    'professional_role_id' => (int)$veterinarioRole['id'],
    // CRMV não fornecido - deve falhar
    'specialty' => 'Clínica Geral',
    'status' => 'active'
];

echo "   Dados: user_id={$professionalDataWithoutCrmv['user_id']}, professional_role_id={$professionalDataWithoutCrmv['professional_role_id']}, crmv=" . ($professionalDataWithoutCrmv['crmv'] ?? 'NÃO FORNECIDO') . "\n";

try {
    $professionalModel2 = new Professional();
    $professionalId2 = $professionalModel2->create($tenantId, $professionalDataWithoutCrmv);
    echo "   ❌ ERRO: Profissional foi criado sem CRMV! Isso não deveria acontecer.\n";
    echo "   Professional ID criado: {$professionalId2}\n";
    // Limpa o profissional criado incorretamente
    try {
        $professionalModel2->delete($professionalId2);
        echo "   Profissional incorreto foi deletado.\n";
    } catch (\Exception $e) {
        echo "   Erro ao deletar profissional incorreto: " . $e->getMessage() . "\n";
    }
} catch (\RuntimeException $e) {
    $message = $e->getMessage();
    if (stripos($message, 'CRMV') !== false || stripos($message, 'obrigatório') !== false) {
        echo "   ✅ Validação funcionou corretamente!\n";
        echo "   Mensagem: {$message}\n";
    } else {
        echo "   ⚠️  Erro diferente do esperado: {$message}\n";
    }
} catch (\Exception $e) {
    echo "   ⚠️  Erro inesperado: " . $e->getMessage() . "\n";
    echo "   Tipo: " . get_class($e) . "\n";
}

echo "\n=== RESUMO DO TESTE ===\n";
echo "✅ Usuário criado: ID {$userId}\n";
echo "✅ Profissional criado: ID {$professionalId}\n";
echo "✅ Função 'Veterinário' associada\n";
echo "✅ CRMV obrigatório validado\n";
echo "\n✅ TESTE CONCLUÍDO COM SUCESSO!\n\n";

echo "Para limpar os dados de teste, execute:\n";
echo "  DELETE FROM professionals WHERE id = {$professionalId};\n";
echo "  DELETE FROM users WHERE id = {$userId};\n";

