<?php

/**
 * Teste completo do QueryBuilder
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

echo "🧪 TESTE DE QUERY BUILDER AVANÇADO\n";
echo str_repeat("=", 70) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

// Testa se QueryBuilder pode ser instanciado
try {
    echo "Testando instanciação do QueryBuilder... ";
    
    $tenantModel = new \App\Models\Tenant();
    $queryBuilder = $tenantModel->query();
    
    if ($queryBuilder instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder: Instanciação falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder instanciação: " . $e->getMessage();
}

// Testa método where
try {
    echo "Testando QueryBuilder::where()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->where('id', 1);
    
    // Verifica se retorna self (fluent interface)
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::where: Não retorna self";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::where: " . $e->getMessage();
}

// Testa método whereIn
try {
    echo "Testando QueryBuilder::whereIn()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->whereIn('id', [1, 2, 3]);
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::whereIn: Não retorna self";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::whereIn: " . $e->getMessage();
}

// Testa método whereBetween
try {
    echo "Testando QueryBuilder::whereBetween()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->whereBetween('id', 1, 10);
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::whereBetween: Não retorna self";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::whereBetween: " . $e->getMessage();
}

// Testa método orderBy
try {
    echo "Testando QueryBuilder::orderBy()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->orderBy('id', 'DESC');
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::orderBy: Não retorna self";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::orderBy: " . $e->getMessage();
}

// Testa método limit
try {
    echo "Testando QueryBuilder::limit()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->limit(10);
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::limit: Não retorna self";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::limit: " . $e->getMessage();
}

// Testa método offset
try {
    echo "Testando QueryBuilder::offset()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->offset(5);
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::offset: Não retorna self";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::offset: " . $e->getMessage();
}

// Testa método select
try {
    echo "Testando QueryBuilder::select()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->select(['id', 'name']);
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::select: Não retorna self";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::select: " . $e->getMessage();
}

// Testa método with (eager loading placeholder)
try {
    echo "Testando QueryBuilder::with()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->with('users');
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::with: Não retorna self";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::with: " . $e->getMessage();
}

// Testa encadeamento de métodos
try {
    echo "Testando encadeamento de métodos... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()
        ->where('id', 1)
        ->where('status', 'active')
        ->whereIn('id', [1, 2, 3])
        ->orderBy('id', 'DESC')
        ->limit(10);
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder encadeamento: Falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder encadeamento: " . $e->getMessage();
}

// Testa método get() com query simples
try {
    echo "Testando QueryBuilder::get() com query simples... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->limit(5)
        ->get();
    
    if (is_array($results)) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::get: Não retorna array";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::get: " . $e->getMessage();
}

// Testa método first()
try {
    echo "Testando QueryBuilder::first()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $result = $tenantModel->query()->first();
    
    if ($result === null || is_array($result)) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::first: Retorno incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::first: " . $e->getMessage();
}

// Testa método count()
try {
    echo "Testando QueryBuilder::count()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $count = $tenantModel->query()->count();
    
    if (is_int($count) && $count >= 0) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::count: Retorno incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::count: " . $e->getMessage();
}

// Testa método where com operador
try {
    echo "Testando QueryBuilder::where() com operador... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->where('id', '>', 5);
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::where operador: Falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::where operador: " . $e->getMessage();
}

// Testa método whereNull
try {
    echo "Testando QueryBuilder::whereNull()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->whereNull('deleted_at');
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::whereNull: Falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::whereNull: " . $e->getMessage();
}

// Testa método whereNotNull
try {
    echo "Testando QueryBuilder::whereNotNull()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->whereNotNull('name');
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::whereNotNull: Falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::whereNotNull: " . $e->getMessage();
}

// Testa método whereNotIn
try {
    echo "Testando QueryBuilder::whereNotIn()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()->whereNotIn('id', [1, 2, 3]);
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::whereNotIn: Falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::whereNotIn: " . $e->getMessage();
}

// Testa método orWhere
try {
    echo "Testando QueryBuilder::orWhere()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $qb = $tenantModel->query()
        ->where('id', 1)
        ->orWhere('id', 2);
    
    if ($qb instanceof \App\Models\QueryBuilder) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::orWhere: Falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::orWhere: " . $e->getMessage();
}

// Testa método paginate()
try {
    echo "Testando QueryBuilder::paginate()... ";
    
    $tenantModel = new \App\Models\Tenant();
    $paginationParams = [
        'page' => 1,
        'limit' => 5,
        'offset' => 0,
        'errors' => []
    ];
    
    $result = $tenantModel->query()->paginate($paginationParams);
    
    if (isset($result['data']) && isset($result['pagination'])) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder::paginate: Estrutura incorreta";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder::paginate: " . $e->getMessage();
}

// Testa query complexa completa
try {
    echo "Testando query complexa completa... ";
    
    $tenantModel = new \App\Models\Tenant();
    $results = $tenantModel->query()
        ->where('id', '>', 0)
        ->whereNotNull('name')
        ->whereIn('id', [1, 2, 3, 4, 5])
        ->orderBy('id', 'DESC')
        ->limit(3)
        ->get();
    
    if (is_array($results) && count($results) <= 3) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "QueryBuilder query complexa: Falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "QueryBuilder query complexa: " . $e->getMessage();
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
    echo "✅ TODOS OS TESTES DO QUERY BUILDER PASSARAM!\n\n";
    exit(0);
}

