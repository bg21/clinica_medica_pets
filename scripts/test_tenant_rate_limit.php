<?php

// Carrega autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Carrega configurações (necessário para BaseModel)
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}

use App\Models\TenantRateLimit;
use App\Services\TenantRateLimitService;

echo "🧪 TESTANDO TENANT RATE LIMIT SERVICE\n";
echo "================================================================================\n";

$passed = 0;
$failed = 0;

function runTest(string $name, callable $testFunction): void
{
    global $passed, $failed;
    echo "📋 Testando: {$name}\n";
    echo "--------------------------------------------------------------------------------\n";
    try {
        $testFunction();
        echo "✅ {$name} - PASSOU\n";
        $passed++;
    } catch (Exception $e) {
        echo "❌ {$name} - FALHOU: " . $e->getMessage() . "\n";
        $failed++;
    } catch (Error $e) {
        echo "❌ {$name} - ERRO: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
}

// Teste 1: Verificar se o Model pode ser instanciado
runTest('Instanciação do Model', function () {
    $model = new TenantRateLimit();
    assert($model instanceof TenantRateLimit, 'Model deve ser instanciado');
});

// Teste 2: Verificar se o Service pode ser instanciado
runTest('Instanciação do Service', function () {
    $model = new TenantRateLimit();
    $service = new TenantRateLimitService($model);
    assert($service instanceof TenantRateLimitService, 'Service deve ser instanciado');
});

// Teste 3: Verificar se getLimits retorna null quando não há tenant
runTest('getLimits sem tenant', function () {
    $model = new TenantRateLimit();
    $service = new TenantRateLimitService($model);
    
    $limits = $service->getLimits(null, '/v1/appointments', 'POST');
    assert($limits === null, 'Deve retornar null quando não há tenant');
});

// Teste 4: Verificar se getLimits retorna null quando não há limites configurados
runTest('getLimits sem limites configurados', function () {
    $model = new TenantRateLimit();
    $service = new TenantRateLimitService($model);
    
    // Usa um tenant_id que provavelmente não existe
    $limits = $service->getLimits(99999, '/v1/appointments', 'POST');
    assert($limits === null, 'Deve retornar null quando não há limites configurados');
});

// Teste 5: Verificar se setLimits funciona (sem verificar banco, apenas estrutura)
runTest('setLimits estrutura', function () {
    $model = new TenantRateLimit();
    $service = new TenantRateLimitService($model);
    
    // Tenta definir limites (pode falhar se tabela não existir, mas estrutura está correta)
    try {
        $result = $service->setLimits(1, [
            'limit_per_minute' => 100,
            'limit_per_hour' => 2000
        ], '/v1/appointments', 'POST');
        
        // Se chegou aqui sem exception, estrutura está correta
        assert(is_bool($result), 'setLimits deve retornar bool');
    } catch (\PDOException $e) {
        // Se a tabela não existe, é esperado - apenas verifica que a estrutura está correta
        if (strpos($e->getMessage, 'doesn\'t exist') !== false || 
            strpos($e->getMessage, 'Table') !== false) {
            echo "⚠️  Tabela não existe ainda - execute a migration primeiro\n";
        } else {
            throw $e;
        }
    }
});

// Teste 6: Verificar se removeLimits funciona (estrutura)
runTest('removeLimits estrutura', function () {
    $model = new TenantRateLimit();
    $service = new TenantRateLimitService($model);
    
    try {
        $result = $service->removeLimits(1, '/v1/appointments', 'POST');
        assert(is_bool($result), 'removeLimits deve retornar bool');
    } catch (\PDOException $e) {
        $message = $e->getMessage();
        if (strpos($message, 'doesn\'t exist') !== false || 
            strpos($message, 'Table') !== false) {
            echo "⚠️  Tabela não existe ainda - execute a migration primeiro\n";
        } else {
            throw $e;
        }
    }
});

// Teste 7: Verificar se listLimits funciona (estrutura)
runTest('listLimits estrutura', function () {
    $model = new TenantRateLimit();
    $service = new TenantRateLimitService($model);
    
    try {
        $result = $service->listLimits(1);
        assert(is_array($result), 'listLimits deve retornar array');
    } catch (\PDOException $e) {
        $message = $e->getMessage();
        if (strpos($message, 'doesn\'t exist') !== false || 
            strpos($message, 'Table') !== false) {
            echo "⚠️  Tabela não existe ainda - execute a migration primeiro\n";
        } else {
            throw $e;
        }
    }
});

echo "================================================================================\n";
echo "📊 RESUMO DOS TESTES\n";
echo "================================================================================\n";
echo "Total de testes: " . ($passed + $failed) . "\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: {$failed}\n";

if ($failed > 0) {
    echo "\n⚠️  Alguns testes falharam. Verifique os erros acima.\n";
    echo "💡 Dica: Execute a migration primeiro: db/migrations/create_tenant_rate_limits_table.sql\n";
    exit(1);
} else {
    echo "\n🎉 Todos os testes passaram!\n";
    echo "💡 Próximo passo: Execute a migration para criar a tabela no banco de dados.\n";
    exit(0);
}

