<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

echo "=== VERIFICAÇÃO DE ESPECIALIDADES E PREÇOS ===\n\n";

// Verifica especialidades
echo "1. Especialidades cadastradas:\n";
$stmt = $pdo->query("SELECT id, name, price_id, is_active FROM clinic_specialties WHERE deleted_at IS NULL ORDER BY id");
$specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($specialties)) {
    echo "   ⚠️  Nenhuma especialidade cadastrada ainda.\n";
} else {
    foreach ($specialties as $spec) {
        $priceStatus = $spec['price_id'] ? "✅ Preço: {$spec['price_id']}" : "❌ Sem preço (NULL)";
        $activeStatus = $spec['is_active'] ? 'Ativa' : 'Inativa';
        echo "   - ID: {$spec['id']} | Nome: '{$spec['name']}' | {$priceStatus} | Status: {$activeStatus}\n";
    }
}

echo "\n";

// Verifica estrutura da tabela
echo "2. Estrutura da tabela clinic_specialties:\n";
$stmt = $pdo->query("DESCRIBE clinic_specialties");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    if ($col['Field'] === 'price_id') {
        echo "   ✅ Campo 'price_id' existe: {$col['Type']} | Null: {$col['Null']}\n";
    }
}

echo "\n";

// Verifica professional_roles
echo "3. Funções profissionais (professional_roles):\n";
$stmt = $pdo->query("SELECT id, name FROM professional_roles WHERE deleted_at IS NULL ORDER BY id LIMIT 5");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($roles)) {
    echo "   ⚠️  Nenhuma função cadastrada.\n";
} else {
    foreach ($roles as $role) {
        echo "   - ID: {$role['id']} | Nome: '{$role['name']}'\n";
    }
}

// Verifica se professional_roles tem campo price_id
echo "\n4. Verificando se professional_roles tem campo price_id:\n";
$stmt = $pdo->query("DESCRIBE professional_roles");
$roleColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$hasPriceId = false;
foreach ($roleColumns as $col) {
    if ($col['Field'] === 'price_id') {
        $hasPriceId = true;
        echo "   ✅ Campo 'price_id' existe em professional_roles: {$col['Type']}\n";
        break;
    }
}

if (!$hasPriceId) {
    echo "   ❌ Campo 'price_id' NÃO existe em professional_roles\n";
    echo "   💡 Se quiser adicionar preço às funções, preciso criar uma migration.\n";
}

echo "\n";

echo "=== EXPLICAÇÃO ===\n";
echo "📋 clinic_specialties = Especialidades médicas (Clínica Geral, Cirurgia, etc.)\n";
echo "   - TEM campo price_id (pode ser NULL)\n";
echo "   - Cada especialidade pode ter um preço padrão\n\n";

echo "👤 professional_roles = Funções dos profissionais (Veterinário, Atendente, etc.)\n";
echo "   - NÃO tem campo price_id atualmente\n";
echo "   - Define o cargo/função do profissional\n\n";

echo "💡 DIFERENÇA:\n";
echo "   - Função (role) = O QUE o profissional É (Veterinário, Atendente)\n";
echo "   - Especialidade = O QUE a clínica ATENDE (Clínica Geral, Cirurgia)\n";
echo "   - Um Veterinário pode atender várias especialidades\n";
echo "   - Cada especialidade pode ter um preço diferente\n\n";

echo "❓ Se você quiser que cada FUNÇÃO também tenha um preço padrão,\n";
echo "   posso adicionar o campo price_id em professional_roles também.\n";

