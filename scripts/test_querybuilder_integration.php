<?php

/**
 * Teste de integração completo do QueryBuilder com modelos reais
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

echo "🧪 TESTE DE INTEGRAÇÃO - QUERY BUILDER\n";
echo str_repeat("=", 70) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

// Testa query simples com get()
try {
    echo "Testando query simples com get()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->limit(5)
        ->get();
    
    if (is_array($results) && count($results) <= 5) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "Query simples get: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Query simples get: " . $e->getMessage();
}

// Testa query com where e get()
try {
    echo "Testando query com where() e get()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->where('id', '>', 0)
        ->limit(3)
        ->get();
    
    if (is_array($results)) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "Query com where: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Query com where: " . $e->getMessage();
}

// Testa query com whereIn
try {
    echo "Testando query com whereIn()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->whereIn('id', [1, 2, 3])
        ->get();
    
    if (is_array($results)) {
        // Verifica se todos os resultados têm IDs no array
        $allValid = true;
        foreach ($results as $result) {
            if (!in_array($result['id'], [1, 2, 3])) {
                $allValid = false;
                break;
            }
        }
        
        if ($allValid) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (IDs fora do esperado)\n";
            $failed++;
            $errors[] = "Query com whereIn: IDs incorretos";
        }
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "Query com whereIn: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Query com whereIn: " . $e->getMessage();
}

// Testa query com whereBetween
try {
    echo "Testando query com whereBetween()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->whereBetween('id', 1, 5)
        ->get();
    
    if (is_array($results)) {
        // Verifica se todos os IDs estão entre 1 e 5
        $allValid = true;
        foreach ($results as $result) {
            $id = (int)$result['id'];
            if ($id < 1 || $id > 5) {
                $allValid = false;
                break;
            }
        }
        
        if ($allValid) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (IDs fora do range)\n";
            $failed++;
            $errors[] = "Query com whereBetween: IDs fora do range";
        }
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "Query com whereBetween: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Query com whereBetween: " . $e->getMessage();
}

// Testa query com orderBy
try {
    echo "Testando query com orderBy()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->orderBy('id', 'DESC')
        ->limit(3)
        ->get();
    
    if (is_array($results) && count($results) > 0) {
        // Verifica se está ordenado DESC
        $ordered = true;
        $prevId = null;
        foreach ($results as $result) {
            $id = (int)$result['id'];
            if ($prevId !== null && $id > $prevId) {
                $ordered = false;
                break;
            }
            $prevId = $id;
        }
        
        if ($ordered) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (não está ordenado DESC)\n";
            $failed++;
            $errors[] = "Query com orderBy: Ordenação incorreta";
        }
    } else {
        echo "✅ OK (sem dados para ordenar)\n";
        $passed++;
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Query com orderBy: " . $e->getMessage();
}

// Testa query com múltiplas condições
try {
    echo "Testando query com múltiplas condições... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->where('id', '>', 0)
        ->whereNotNull('name')
        ->whereIn('id', [1, 2, 3, 4, 5])
        ->orderBy('id', 'ASC')
        ->limit(3)
        ->get();
    
    if (is_array($results)) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "Query múltiplas condições: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Query múltiplas condições: " . $e->getMessage();
}

// Testa count() com condições
try {
    echo "Testando count() com condições... ";
    
    $tenantModel = new \App\Models\Tenant();
    $count = $tenantModel->query()
        ->where('id', '>', 0)
        ->count();
    
    if (is_int($count) && $count >= 0) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "Count com condições: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Count com condições: " . $e->getMessage();
}

// Testa first() com condições
try {
    echo "Testando first() com condições... ";
    
    $tenantModel = new \App\Models\Tenant();
    $result = $tenantModel->query()
        ->where('id', '>', 0)
        ->orderBy('id', 'ASC')
        ->first();
    
    if ($result === null || (is_array($result) && isset($result['id']))) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "First com condições: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "First com condições: " . $e->getMessage();
}

// Testa select() com campos específicos
try {
    echo "Testando select() com campos específicos... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->select(['id', 'name'])
        ->limit(1)
        ->get();
    
    if (is_array($results) && !empty($results)) {
        $first = $results[0];
        // Verifica se tem apenas os campos selecionados (ou pelo menos esses campos)
        if (isset($first['id']) && isset($first['name'])) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (campos faltando)\n";
            $failed++;
            $errors[] = "Select campos específicos: Campos faltando";
        }
    } else {
        echo "✅ OK (sem dados)\n";
        $passed++;
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Select campos específicos: " . $e->getMessage();
}

// Testa paginate() completo
try {
    echo "Testando paginate() completo... ";
    
    $tenantModel = new \App\Models\Tenant();
    $paginationParams = [
        'page' => 1,
        'limit' => 5,
        'offset' => 0,
        'errors' => []
    ];
    
    $result = $tenantModel->query()->paginate($paginationParams);
    
    if (isset($result['data']) && is_array($result['data']) &&
        isset($result['pagination']) && is_array($result['pagination']) &&
        isset($result['pagination']['current_page']) &&
        isset($result['pagination']['total']) &&
        isset($result['pagination']['total_pages'])) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU (estrutura incorreta)\n";
        $failed++;
        $errors[] = "Paginate completo: Estrutura incorreta";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Paginate completo: " . $e->getMessage();
}

// Testa query complexa real (simulando uso real)
try {
    echo "Testando query complexa real (simulando uso)... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->where('id', '>', 0)
        ->whereNotNull('name')
        ->whereIn('id', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
        ->orderBy('id', 'DESC')
        ->limit(5)
        ->offset(0)
        ->get();
    
    if (is_array($results) && count($results) <= 5) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "Query complexa real: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Query complexa real: " . $e->getMessage();
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
    echo "✅ TODOS OS TESTES DE INTEGRAÇÃO PASSARAM!\n\n";
    exit(0);
}

