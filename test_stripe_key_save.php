<?php
/**
 * Script de teste para verificar salvamento de API key do Stripe
 * Execute: php test_stripe_key_save.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\TenantStripeAccount;
use App\Utils\EncryptionHelper;
use App\Utils\Database;

// Carrega configurações
Config::load();

echo "=== Teste de Salvamento de API Key do Stripe ===\n\n";

// Teste 1: Verificar se a tabela existe
echo "1. Verificando se a tabela existe...\n";
try {
    $db = Database::getInstance();
    $stmt = $db->query("SHOW TABLES LIKE 'tenant_stripe_accounts'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "   ✅ Tabela 'tenant_stripe_accounts' existe\n";
    } else {
        echo "   ❌ Tabela 'tenant_stripe_accounts' NÃO existe!\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ❌ Erro ao verificar tabela: " . $e->getMessage() . "\n";
    exit(1);
}

// Teste 2: Verificar se a coluna stripe_secret_key existe
echo "\n2. Verificando se a coluna 'stripe_secret_key' existe...\n";
try {
    $stmt = $db->query("SHOW COLUMNS FROM tenant_stripe_accounts LIKE 'stripe_secret_key'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✅ Coluna 'stripe_secret_key' existe\n";
        echo "   Tipo: " . $column['Type'] . "\n";
        echo "   Null: " . $column['Null'] . "\n";
    } else {
        echo "   ❌ Coluna 'stripe_secret_key' NÃO existe!\n";
        echo "   Execute a migration: db/migrations/20251210000001_add_stripe_secret_key_to_tenant_stripe_accounts.php\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ❌ Erro ao verificar coluna: " . $e->getMessage() . "\n";
    exit(1);
}

// Teste 3: Verificar criptografia
echo "\n3. Testando criptografia...\n";
try {
    // IMPORTANTE: NÃO use chaves reais do Stripe neste arquivo!
    // Para testar a criptografia, use uma variável de ambiente ou uma string de teste
    // que não seja detectada como chave secreta pelo GitHub
    
    // Opção 1: Use variável de ambiente (recomendado)
    $testKey = getenv('STRIPE_TEST_KEY');
    
    // Opção 2: Se não tiver variável de ambiente, use uma string de teste genérica
    // (não começa com "sk_test_" para evitar detecção)
    if (empty($testKey)) {
        $testKey = "TEST_ENCRYPTION_KEY_" . str_repeat("X", 80);
        echo "   ⚠️  Usando string de teste genérica (não é uma chave Stripe real)\n";
        echo "   💡 Para usar uma chave real de teste, defina: export STRIPE_TEST_KEY=sk_test_xxx\n";
    } else {
        echo "   ℹ️  Usando chave de teste da variável de ambiente STRIPE_TEST_KEY\n";
    }
    
    $encrypted = EncryptionHelper::encrypt($testKey);
    $decrypted = EncryptionHelper::decrypt($encrypted);
    
    if ($decrypted === $testKey) {
        echo "   ✅ Criptografia funcionando corretamente\n";
        echo "   Tamanho original: " . strlen($testKey) . " bytes\n";
        echo "   Tamanho criptografado: " . strlen($encrypted) . " bytes\n";
    } else {
        echo "   ❌ Criptografia falhou! Dados não coincidem após descriptografia\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ❌ Erro na criptografia: " . $e->getMessage() . "\n";
    exit(1);
}

// Teste 4: Verificar se há algum tenant para testar
echo "\n4. Verificando tenants disponíveis...\n";
try {
    $stmt = $db->query("SELECT id, name FROM tenants LIMIT 5");
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tenants)) {
        echo "   ⚠️  Nenhum tenant encontrado. Crie um tenant primeiro.\n";
    } else {
        echo "   ✅ Tenants encontrados:\n";
        foreach ($tenants as $tenant) {
            echo "      - ID: {$tenant['id']}, Nome: {$tenant['name']}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Erro ao buscar tenants: " . $e->getMessage() . "\n";
}

// Teste 5: Verificar se há registros na tabela
echo "\n5. Verificando registros existentes...\n";
try {
    $stmt = $db->query("SELECT id, tenant_id, stripe_account_id, account_type, 
                        CASE WHEN stripe_secret_key IS NOT NULL THEN 'SIM' ELSE 'NÃO' END as tem_key
                        FROM tenant_stripe_accounts LIMIT 10");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "   ℹ️  Nenhum registro na tabela 'tenant_stripe_accounts'\n";
    } else {
        echo "   ✅ Registros encontrados:\n";
        foreach ($accounts as $account) {
            echo "      - ID: {$account['id']}, Tenant: {$account['tenant_id']}, " .
                 "Account: {$account['stripe_account_id']}, Tipo: {$account['account_type']}, " .
                 "Tem Key: {$account['tem_key']}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Erro ao buscar registros: " . $e->getMessage() . "\n";
}

echo "\n=== Teste concluído ===\n";
echo "\nPróximos passos:\n";
echo "1. Verifique os logs em app.log para ver se há erros ao salvar\n";
echo "2. Tente salvar a API key novamente pela interface\n";
echo "3. Verifique o console do navegador (F12) para erros JavaScript\n";
echo "4. Verifique a resposta da API no Network tab do navegador\n";

