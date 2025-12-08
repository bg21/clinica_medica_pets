<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Models\Professional;
use App\Models\Tenant;

echo "=== VERIFICAÇÃO DE PROFISSIONAIS ===\n\n";

try {
    $tenantModel = new Tenant();
    $tenants = $tenantModel->findAll(['status' => 'active'], ['id' => 'ASC'], 1);
    
    if (empty($tenants)) {
        echo "❌ Nenhum tenant ativo encontrado\n";
        exit(1);
    }
    
    $tenant = $tenants[0];
    $tenantId = (int)$tenant['id'];
    echo "✅ Tenant encontrado: ID {$tenantId}\n\n";
    
    $professionalModel = new Professional();
    
    // Testa findByTenant
    echo "Testando findByTenant...\n";
    $result = $professionalModel->findByTenant($tenantId, 1, 20, []);
    
    echo "✅ Total de profissionais: {$result['total']}\n";
    echo "✅ Página: {$result['page']}\n";
    echo "✅ Total de páginas: {$result['total_pages']}\n";
    echo "✅ Profissionais retornados: " . count($result['data']) . "\n\n";
    
    if (count($result['data']) > 0) {
        echo "Profissionais encontrados:\n";
        foreach ($result['data'] as $prof) {
            echo "  - ID: {$prof['id']}, Nome: '{$prof['name']}', Status: '{$prof['status']}', CRMV: " . ($prof['crmv'] ?? 'NULL') . "\n";
        }
    } else {
        echo "⚠️  Nenhum profissional retornado, mas total = {$result['total']}\n";
    }
    
    // Verifica diretamente no banco
    echo "\nVerificando diretamente no banco...\n";
    $db = \App\Utils\Database::getInstance();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM professionals WHERE tenant_id = :tenant_id");
    $stmt->execute(['tenant_id' => $tenantId]);
    $dbTotal = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "✅ Total no banco (tenant {$tenantId}): {$dbTotal}\n";
    
    if ($dbTotal > 0) {
        $stmt2 = $db->prepare("SELECT id, name, status, crmv FROM professionals WHERE tenant_id = :tenant_id LIMIT 5");
        $stmt2->execute(['tenant_id' => $tenantId]);
        $profs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        echo "Primeiros 5 profissionais no banco:\n";
        foreach ($profs as $p) {
            echo "  - ID: {$p['id']}, Nome: '{$p['name']}', Status: '{$p['status']}', CRMV: " . ($p['crmv'] ?? 'NULL') . "\n";
        }
    }
    
    echo "\n✅ Teste concluído!\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

