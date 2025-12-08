<?php

/**
 * Teste com modelo real do banco de dados
 * Testa findPaginated() do BaseModel
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

echo "🧪 TESTE COM MODELO REAL - PAGINAÇÃO\n";
echo str_repeat("=", 70) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

try {
    echo "Testando findPaginated() com modelo Tenant... ";
    
    $tenantModel = new \App\Models\Tenant();
    
    // Tenta usar findPaginated
    try {
        $paginationParams = [
            'page' => 1,
            'limit' => 5,
            'offset' => 0,
            'errors' => []
        ];
        
        $result = $tenantModel->findPaginated([], [], $paginationParams);
        
        // Verifica estrutura
        if (isset($result['data']) && is_array($result['data']) &&
            isset($result['pagination']) && is_array($result['pagination'])) {
            
            // Verifica metadados de paginação
            $pagination = $result['pagination'];
            if (isset($pagination['current_page']) &&
                isset($pagination['per_page']) &&
                isset($pagination['total']) &&
                isset($pagination['total_pages']) &&
                isset($pagination['has_next']) &&
                isset($pagination['has_prev'])) {
                
                echo "✅ OK (estrutura correta)\n";
                $passed++;
            } else {
                echo "❌ FALHOU (metadados incompletos)\n";
                $failed++;
                $errors[] = "findPaginated: Metadados de paginação incompletos";
            }
        } else {
            echo "❌ FALHOU (estrutura incorreta)\n";
            $failed++;
            $errors[] = "findPaginated: Estrutura de resposta incorreta";
        }
    } catch (\Throwable $e) {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "findPaginated: " . $e->getMessage();
    }
} catch (\Throwable $e) {
    echo "❌ ERRO ao criar modelo: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Criação de modelo: " . $e->getMessage();
}

// Testa com condições
try {
    echo "Testando findPaginated() com condições... ";
    
    $tenantModel = new \App\Models\Tenant();
    
    $paginationParams = [
        'page' => 1,
        'limit' => 10,
        'offset' => 0,
        'errors' => []
    ];
    
    // Tenta com condições vazias (deve funcionar)
    $result = $tenantModel->findPaginated([], [], $paginationParams);
    
    if (isset($result['data']) && isset($result['pagination'])) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "findPaginated com condições: Estrutura incorreta";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "findPaginated com condições: " . $e->getMessage();
}

// Testa com ordenação
try {
    echo "Testando findPaginated() com ordenação... ";
    
    $tenantModel = new \App\Models\Tenant();
    
    $paginationParams = [
        'page' => 1,
        'limit' => 5,
        'offset' => 0,
        'errors' => []
    ];
    
    $result = $tenantModel->findPaginated([], ['id' => 'DESC'], $paginationParams);
    
    if (isset($result['data']) && isset($result['pagination'])) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "findPaginated com ordenação: Estrutura incorreta";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "findPaginated com ordenação: " . $e->getMessage();
}

// Testa com erros de validação
try {
    echo "Testando findPaginated() com erros de validação... ";
    
    $tenantModel = new \App\Models\Tenant();
    
    $paginationParams = [
        'page' => 1,
        'limit' => 10,
        'offset' => 0,
        'errors' => ['page' => 'Página inválida']
    ];
    
    try {
        $tenantModel->findPaginated([], [], $paginationParams);
        echo "❌ FALHOU (deveria lançar exceção)\n";
        $failed++;
        $errors[] = "findPaginated: Deveria lançar exceção com erros";
    } catch (\InvalidArgumentException $e) {
        echo "✅ OK\n";
        $passed++;
    }
} catch (\Throwable $e) {
    if ($e instanceof \InvalidArgumentException) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "findPaginated erros: " . $e->getMessage();
    }
}

// Testa cálculo de offset em diferentes páginas
try {
    echo "Testando cálculo de offset em diferentes páginas... ";
    
    $testCases = [
        [1, 20, 0],    // Página 1, 20 por página = offset 0
        [2, 20, 20],   // Página 2, 20 por página = offset 20
        [3, 10, 20],   // Página 3, 10 por página = offset 20
        [5, 15, 60],   // Página 5, 15 por página = offset 60
    ];
    
    foreach ($testCases as [$page, $perPage, $expectedOffset]) {
        $offset = \App\Utils\PaginationHelper::calculateOffset($page, $perPage);
        if ($offset !== $expectedOffset) {
            throw new \Exception("Offset incorreto para página {$page}: esperado {$expectedOffset}, recebido {$offset}");
        }
    }
    
    echo "✅ OK\n";
    $passed++;
} catch (\Throwable $e) {
    echo "❌ FALHOU: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Cálculo de offset: " . $e->getMessage();
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 RESULTADOS:\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: {$failed}\n";
echo "📈 Total: " . ($passed + $failed) . "\n\n";

if ($failed > 0) {
    echo "❌ ERROS ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "✅ TODOS OS TESTES COM MODELO REAL PASSARAM!\n\n";
    exit(0);
}

