<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\ProfessionalRole;

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

$tenantId = 2;
$roleId = 1;

echo "Testando busca de role...\n";
echo "Tenant ID: {$tenantId}\n";
echo "Role ID: {$roleId}\n\n";

$roleModel = new ProfessionalRole();
$role = $roleModel->findByTenantAndId($tenantId, $roleId);

if ($role) {
    echo "✅ Role encontrada:\n";
    echo "   - ID: {$role['id']}\n";
    echo "   - Nome: {$role['name']}\n";
    echo "   - Tenant ID: {$role['tenant_id']}\n\n";
    
    $roleName = $role['name'] ?? '';
    $roleNameLower = mb_strtolower($roleName, 'UTF-8');
    echo "Nome em minúsculas: '{$roleNameLower}'\n";
    
    $isVeterinario = (strpos($roleNameLower, 'veterinário') !== false) || 
                   (strpos($roleNameLower, 'veterinario') !== false);
    
    echo "É veterinário? " . ($isVeterinario ? 'SIM' : 'NÃO') . "\n";
    
    // Testa com diferentes variações
    echo "\nTestando variações:\n";
    echo "   'veterinário' encontrado? " . (strpos($roleNameLower, 'veterinário') !== false ? 'SIM' : 'NÃO') . "\n";
    echo "   'veterinario' encontrado? " . (strpos($roleNameLower, 'veterinario') !== false ? 'SIM' : 'NÃO') . "\n";
    
    // Verifica encoding
    echo "\nEncoding:\n";
    echo "   mb_detect_encoding: " . mb_detect_encoding($roleName) . "\n";
    echo "   Bytes do nome: " . bin2hex($roleName) . "\n";
    
} else {
    echo "❌ Role não encontrada!\n";
    
    // Tenta buscar diretamente
    $stmt = $pdo->prepare('SELECT * FROM professional_roles WHERE id = ? AND tenant_id = ?');
    $stmt->execute([$roleId, $tenantId]);
    $directRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($directRole) {
        echo "Mas encontrada via query direta:\n";
        print_r($directRole);
    }
}

