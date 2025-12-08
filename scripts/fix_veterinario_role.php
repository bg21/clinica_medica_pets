<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

echo "Corrigindo nome da função 'Veterinário'...\n";

$stmt = $pdo->prepare('UPDATE professional_roles SET name = ? WHERE name LIKE ?');
$stmt->execute(['Veterinário', 'Veterin%']);

$affected = $stmt->rowCount();
echo "✅ {$affected} função(ões) corrigida(s)!\n";

// Verifica resultado
$stmt = $pdo->query('SELECT id, tenant_id, name FROM professional_roles WHERE name LIKE "Veterin%"');
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nFunções 'Veterinário' encontradas:\n";
foreach ($roles as $role) {
    echo "  - ID: {$role['id']} | Tenant: {$role['tenant_id']} | Nome: {$role['name']}\n";
}

