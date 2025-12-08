<?php

/**
 * Script para testar o fluxo completo de criação de profissional
 * 1. Verifica se há usuários disponíveis
 * 2. Verifica se há funções profissionais disponíveis
 * 3. Testa a criação de um profissional
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\User;
use App\Models\ProfessionalRole;
use App\Models\Professional;

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

echo "=== TESTE DO FLUXO DE CRIAÇÃO DE PROFISSIONAL ===\n\n";

// 1. Verifica usuários disponíveis
echo "1. Verificando usuários disponíveis...\n";
$userModel = new User();
// Verifica se a coluna deleted_at existe
$stmt = $pdo->query('SHOW COLUMNS FROM users LIKE "deleted_at"');
$hasDeletedAt = $stmt->rowCount() > 0;
$whereClause = $hasDeletedAt ? 'WHERE deleted_at IS NULL' : '';
$stmt = $pdo->query("SELECT id, tenant_id, name, email, role FROM users {$whereClause} ORDER BY id DESC LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "   ⚠️  Nenhum usuário encontrado. Crie um usuário primeiro!\n";
    echo "   Para criar um usuário, acesse: /users\n\n";
} else {
    echo "   ✅ Encontrados " . count($users) . " usuário(s):\n";
    foreach ($users as $user) {
        echo "      - ID: {$user['id']} | Nome: {$user['name']} | Email: {$user['email']} | Role: {$user['role']}\n";
    }
    echo "\n";
}

// 2. Verifica funções profissionais disponíveis
echo "2. Verificando funções profissionais disponíveis...\n";
$roleModel = new ProfessionalRole();
$stmt = $pdo->query('SELECT id, tenant_id, name, description, is_active FROM professional_roles WHERE (deleted_at IS NULL OR deleted_at = "") AND is_active = 1 ORDER BY sort_order');
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($roles)) {
    echo "   ❌ Nenhuma função profissional encontrada!\n";
    echo "   Execute: php vendor/bin/phinx seed:run -s ProfessionalRolesSeed\n\n";
} else {
    echo "   ✅ Encontradas " . count($roles) . " função(ões):\n";
    $veterinarioRole = null;
    foreach ($roles as $role) {
        echo "      - ID: {$role['id']} | Tenant: {$role['tenant_id']} | Nome: {$role['name']} | Ativa: " . ($role['is_active'] ? 'Sim' : 'Não') . "\n";
        if (stripos($role['name'], 'veterinário') !== false || stripos($role['name'], 'veterinario') !== false) {
            $veterinarioRole = $role;
        }
    }
    echo "\n";
    
    if ($veterinarioRole) {
        echo "   ✅ Função 'Veterinário' encontrada (ID: {$veterinarioRole['id']})\n\n";
    } else {
        echo "   ⚠️  Função 'Veterinário' não encontrada. Verifique o seed.\n\n";
    }
}

// 3. Verifica profissionais existentes
echo "3. Verificando profissionais existentes...\n";
$professionalModel = new Professional();
// Verifica se a coluna deleted_at existe
$stmt = $pdo->query('SHOW COLUMNS FROM professionals LIKE "deleted_at"');
$hasDeletedAt = $stmt->rowCount() > 0;
$whereClause = $hasDeletedAt ? 'WHERE p.deleted_at IS NULL' : '';
$stmt = $pdo->query("SELECT p.id, p.tenant_id, p.user_id, p.professional_role_id, p.name, p.crmv, pr.name as role_name FROM professionals p LEFT JOIN professional_roles pr ON p.professional_role_id = pr.id {$whereClause} ORDER BY p.id DESC LIMIT 5");
$professionals = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($professionals)) {
    echo "   ℹ️  Nenhum profissional cadastrado ainda.\n\n";
} else {
    echo "   ✅ Encontrados " . count($professionals) . " profissional(is):\n";
    foreach ($professionals as $prof) {
        $roleName = $prof['role_name'] ?? 'Sem função';
        $crmv = $prof['crmv'] ? "CRMV: {$prof['crmv']}" : 'Sem CRMV';
        echo "      - ID: {$prof['id']} | Nome: {$prof['name']} | Função: {$roleName} | {$crmv}\n";
    }
    echo "\n";
}

// 4. Verifica estrutura da tabela professionals
echo "4. Verificando estrutura da tabela professionals...\n";
$stmt = $pdo->query('SHOW COLUMNS FROM professionals');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$columnNames = array_column($columns, 'Field');

$requiredColumns = ['id', 'tenant_id', 'user_id', 'professional_role_id', 'name', 'crmv', 'email'];
$missingColumns = [];

foreach ($requiredColumns as $col) {
    if (!in_array($col, $columnNames)) {
        $missingColumns[] = $col;
    }
}

if (empty($missingColumns)) {
    echo "   ✅ Todos os campos necessários estão presentes.\n";
    echo "   Campos encontrados: " . implode(', ', $columnNames) . "\n\n";
} else {
    echo "   ❌ Campos faltando: " . implode(', ', $missingColumns) . "\n\n";
}

// 5. Resumo e instruções
echo "=== RESUMO ===\n\n";
echo "Para testar o fluxo completo:\n";
echo "1. Acesse o sistema e crie um usuário (se ainda não tiver)\n";
echo "2. Vá em 'Profissionais' → 'Novo Profissional'\n";
echo "3. Selecione um usuário da lista\n";
echo "4. Selecione a função 'Veterinário'\n";
echo "5. Verifique se o campo CRMV aparece e fica obrigatório\n";
echo "6. Preencha o CRMV e crie o profissional\n\n";

echo "✅ Teste concluído!\n";

