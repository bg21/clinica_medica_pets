<?php

/**
 * Script para testar se a lógica de exibição do CRMV está funcionando
 * Simula o comportamento do JavaScript handleRoleSelection
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\ProfessionalRole;

$pdo = new PDO(
    'mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'),
    Config::get('DB_USER'),
    Config::get('DB_PASS')
);

echo "=== TESTE DE EXIBIÇÃO DO CAMPO CRMV ===\n\n";

// 1. Busca todas as funções profissionais
echo "1. Buscando funções profissionais...\n";
$roleModel = new ProfessionalRole();
$stmt = $pdo->query('SELECT id, tenant_id, name FROM professional_roles WHERE is_active = 1 AND (deleted_at IS NULL OR deleted_at = "") ORDER BY sort_order');
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($roles)) {
    echo "   ❌ Nenhuma função profissional encontrada!\n";
    exit(1);
}

echo "   ✅ Encontradas " . count($roles) . " função(ões):\n\n";

// 2. Testa cada função para ver se deve mostrar CRMV
echo "2. Testando lógica de exibição do CRMV para cada função:\n\n";

foreach ($roles as $role) {
    $roleName = $role['name'] ?? '';
    $roleNameLower = mb_strtolower($roleName, 'UTF-8');
    
    // Normaliza a string removendo acentos (simula JavaScript normalize)
    $roleNameNormalized = $roleNameLower;
    $roleNameNormalized = str_replace(
        ['á', 'à', 'ã', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'õ', 'ô', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ'],
        ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n'],
        $roleNameNormalized
    );
    
    // Verifica se é veterinário (múltiplas formas)
    $isVeterinario = (
        strpos($roleNameNormalized, 'veterinario') !== false || 
        strpos($roleNameLower, 'veterinário') !== false ||
        strpos($roleNameLower, 'veterinario') !== false ||
        strpos($roleNameLower, 'veterinã') !== false || // Para encoding incorreto
        stripos($roleName, 'Veterin') !== false // Case insensitive parcial
    );
    
    $status = $isVeterinario ? '✅ DEVE MOSTRAR CRMV' : '❌ NÃO mostra CRMV';
    $icon = $isVeterinario ? '🔴' : '⚪';
    
    echo "   {$icon} ID: {$role['id']} | Nome: '{$roleName}'\n";
    echo "      Status: {$status}\n";
    echo "      Verificações:\n";
    echo "         - Normalizado: '{$roleNameNormalized}'\n";
    echo "         - Contém 'veterinario' (normalizado): " . (strpos($roleNameNormalized, 'veterinario') !== false ? 'SIM' : 'NÃO') . "\n";
    echo "         - Contém 'veterinário' (com acento): " . (strpos($roleNameLower, 'veterinário') !== false ? 'SIM' : 'NÃO') . "\n";
    echo "         - Contém 'veterinario' (sem acento): " . (strpos($roleNameLower, 'veterinario') !== false ? 'SIM' : 'NÃO') . "\n";
    echo "         - Contém 'Veterin' (case insensitive): " . (stripos($roleName, 'Veterin') !== false ? 'SIM' : 'NÃO') . "\n";
    echo "\n";
}

// 3. Testa especificamente a função "Veterinário"
echo "3. Teste específico para função 'Veterinário':\n\n";
$veterinarioRole = null;
foreach ($roles as $role) {
    $roleName = $role['name'] ?? '';
    $roleNameLower = mb_strtolower($roleName, 'UTF-8');
    $roleNameNormalized = str_replace(
        ['á', 'à', 'ã', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'õ', 'ô', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ'],
        ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n'],
        $roleNameLower
    );
    
    if (strpos($roleNameNormalized, 'veterinario') !== false || 
        stripos($roleName, 'Veterin') !== false) {
        $veterinarioRole = $role;
        break;
    }
}

if ($veterinarioRole) {
    echo "   ✅ Função 'Veterinário' encontrada!\n";
    echo "      - ID: {$veterinarioRole['id']}\n";
    echo "      - Nome: '{$veterinarioRole['name']}'\n";
    echo "      - CRMV deve aparecer: SIM ✅\n\n";
    
    // Simula a função JavaScript handleRoleSelection
    echo "4. Simulando handleRoleSelection(roleId={$veterinarioRole['id']}, formType='create'):\n";
    echo "   ✅ crmvRow.style.display = 'block'\n";
    echo "   ✅ professionalCrmv.required = true\n";
    echo "   ✅ professionalUserRoleHint.textContent = '{$veterinarioRole['name']} - CRMV obrigatório'\n\n";
} else {
    echo "   ❌ Função 'Veterinário' NÃO encontrada!\n";
    echo "   Verifique se o seed foi executado corretamente.\n\n";
}

// 5. Verifica encoding
echo "5. Verificando encoding dos nomes:\n";
foreach ($roles as $role) {
    $roleName = $role['name'] ?? '';
    $encoding = mb_detect_encoding($roleName, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    $bytes = bin2hex($roleName);
    echo "   - '{$roleName}': encoding={$encoding}, bytes={$bytes}\n";
}

echo "\n=== RESUMO ===\n";
echo "✅ Teste concluído!\n";
echo "Se a função 'Veterinário' foi encontrada, o campo CRMV deve aparecer no formulário.\n";
echo "Verifique o console do navegador para logs de debug.\n";

