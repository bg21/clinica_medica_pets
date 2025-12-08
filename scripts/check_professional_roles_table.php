<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

try {
    $stmt = $pdo->query('SHOW TABLES LIKE "professional_roles"');
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "✅ Tabela professional_roles existe\n";
        
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM professional_roles');
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📊 Total de roles: {$count}\n";
        
        if ($count > 0) {
            $stmt = $pdo->query('SELECT id, tenant_id, name, is_active FROM professional_roles ORDER BY tenant_id, sort_order');
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\nRoles encontradas:\n";
            foreach ($roles as $role) {
                echo "  - ID: {$role['id']} | Tenant: {$role['tenant_id']} | Nome: {$role['name']} | Ativa: " . ($role['is_active'] ? 'Sim' : 'Não') . "\n";
            }
        }
    } else {
        echo "❌ Tabela professional_roles NÃO existe\n";
        echo "Execute: php vendor/bin/phinx migrate\n";
    }
    
    // Verifica se o campo professional_role_id existe em professionals
    $stmt = $pdo->query('SHOW COLUMNS FROM professionals LIKE "professional_role_id"');
    $fieldExists = $stmt->rowCount() > 0;
    
    if ($fieldExists) {
        echo "\n✅ Campo professional_role_id existe na tabela professionals\n";
    } else {
        echo "\n❌ Campo professional_role_id NÃO existe na tabela professionals\n";
    }
    
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

