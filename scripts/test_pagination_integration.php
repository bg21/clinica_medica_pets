<?php

/**
 * Teste de integração completo do PaginationHelper
 * Testa com modelos reais e banco de dados
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

echo "🧪 TESTE DE INTEGRAÇÃO - PAGINAÇÃO PADRONIZADA\n";
echo str_repeat("=", 70) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

// Testa se BaseModel tem o método findPaginated
try {
    echo "Testando se BaseModel tem método findPaginated()... ";
    
    $reflection = new ReflectionClass(\App\Models\BaseModel::class);
    if ($reflection->hasMethod('findPaginated')) {
        $method = $reflection->getMethod('findPaginated');
        if ($method->isPublic()) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (método não é público)\n";
            $failed++;
            $errors[] = "BaseModel::findPaginated: Método não é público";
        }
    } else {
        echo "❌ FALHOU (método não existe)\n";
        $failed++;
        $errors[] = "BaseModel::findPaginated: Método não existe";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "BaseModel::findPaginated: " . $e->getMessage();
}

// Testa getPaginationParams com parâmetros fornecidos
try {
    echo "Testando PaginationHelper::getPaginationParams() com parâmetros... ";
    
    $params = [
        'page' => '2',
        'limit' => '15'
    ];
    
    $result = \App\Utils\PaginationHelper::getPaginationParams($params);
    
    if (isset($result['page']) && $result['page'] === 2 &&
        isset($result['limit']) && $result['limit'] === 15 &&
        isset($result['offset']) && $result['offset'] === 15 &&
        isset($result['errors']) && is_array($result['errors'])) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "PaginationHelper::getPaginationParams: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::getPaginationParams: " . $e->getMessage();
}

// Testa getPaginationParams com limite máximo
try {
    echo "Testando PaginationHelper::getPaginationParams() com limite máximo... ";
    
    $params = [
        'page' => '1',
        'limit' => '200' // Acima do máximo padrão (100)
    ];
    
    $result = \App\Utils\PaginationHelper::getPaginationParams($params);
    
    if ($result['limit'] === 100 && !empty($result['errors']['limit'])) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU (deveria limitar a 100)\n";
        $failed++;
        $errors[] = "PaginationHelper::getPaginationParams: Não limitou corretamente";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::getPaginationParams limite: " . $e->getMessage();
}

// Testa getPaginationParams com limite máximo customizado
try {
    echo "Testando PaginationHelper::getPaginationParams() com limite customizado... ";
    
    $params = [
        'page' => '1',
        'limit' => '50'
    ];
    
    $result = \App\Utils\PaginationHelper::getPaginationParams($params, 30); // max 30
    
    if ($result['limit'] === 30 && !empty($result['errors']['limit'])) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU (deveria limitar a 30)\n";
        $failed++;
        $errors[] = "PaginationHelper::getPaginationParams: Não respeitou limite customizado";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::getPaginationParams customizado: " . $e->getMessage();
}

// Testa formatResponse com diferentes cenários
try {
    echo "Testando PaginationHelper::formatResponse() cenários diversos... ";
    
    // Cenário 1: Primeira página
    $result1 = \App\Utils\PaginationHelper::formatResponse([1, 2, 3], 25, 1, 10);
    if ($result1['pagination']['has_prev'] !== false || $result1['pagination']['has_next'] !== true) {
        throw new \Exception("Cenário 1 falhou");
    }
    
    // Cenário 2: Última página
    $result2 = \App\Utils\PaginationHelper::formatResponse([21, 22, 23, 24, 25], 25, 3, 10);
    if ($result2['pagination']['has_prev'] !== true || $result2['pagination']['has_next'] !== false) {
        throw new \Exception("Cenário 2 falhou");
    }
    
    // Cenário 3: Página do meio
    $result3 = \App\Utils\PaginationHelper::formatResponse([11, 12, 13, 14, 15], 25, 2, 10);
    if ($result3['pagination']['has_prev'] !== true || $result3['pagination']['has_next'] !== true) {
        throw new \Exception("Cenário 3 falhou");
    }
    
    // Cenário 4: Sem dados
    $result4 = \App\Utils\PaginationHelper::formatResponse([], 0, 1, 10);
    if ($result4['pagination']['total'] !== 0 || $result4['pagination']['total_pages'] !== 0) {
        throw new \Exception("Cenário 4 falhou");
    }
    
    echo "✅ OK\n";
    $passed++;
} catch (\Throwable $e) {
    echo "❌ FALHOU: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::formatResponse cenários: " . $e->getMessage();
}

// Testa paginate com callbacks
try {
    echo "Testando PaginationHelper::paginate() com callbacks... ";
    
    $allData = range(1, 50);
    $paginationParams = [
        'page' => 2,
        'limit' => 10,
        'offset' => 10,
        'errors' => []
    ];
    
    $result = \App\Utils\PaginationHelper::paginate(
        function($limit, $offset) use ($allData) {
            return array_slice($allData, $offset, $limit);
        },
        function() use ($allData) {
            return count($allData);
        },
        $paginationParams
    );
    
    if (isset($result['data']) && count($result['data']) === 10 &&
        $result['data'][0] === 11 &&
        $result['pagination']['total'] === 50 &&
        $result['pagination']['total_pages'] === 5) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "PaginationHelper::paginate: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::paginate: " . $e->getMessage();
}

// Testa paginate com erros de validação
try {
    echo "Testando PaginationHelper::paginate() com erros de validação... ";
    
    $paginationParams = [
        'page' => 1,
        'limit' => 10,
        'offset' => 0,
        'errors' => ['page' => 'Página inválida']
    ];
    
    try {
        \App\Utils\PaginationHelper::paginate(
            function($limit, $offset) { return []; },
            function() { return 0; },
            $paginationParams
        );
        echo "❌ FALHOU (deveria lançar exceção)\n";
        $failed++;
        $errors[] = "PaginationHelper::paginate: Deveria lançar exceção com erros";
    } catch (\InvalidArgumentException $e) {
        echo "✅ OK\n";
        $passed++;
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::paginate erros: " . $e->getMessage();
}

// Testa isValidPage com diferentes cenários
try {
    echo "Testando PaginationHelper::isValidPage() cenários diversos... ";
    
    // Página válida
    if (!\App\Utils\PaginationHelper::isValidPage(2, 50, 10)) {
        throw new \Exception("Página válida rejeitada");
    }
    
    // Página inválida (muito alta)
    if (\App\Utils\PaginationHelper::isValidPage(10, 50, 10)) {
        throw new \Exception("Página inválida aceita");
    }
    
    // Página 0 (inválida)
    if (\App\Utils\PaginationHelper::isValidPage(0, 50, 10)) {
        throw new \Exception("Página 0 aceita");
    }
    
    // Página negativa (inválida)
    if (\App\Utils\PaginationHelper::isValidPage(-1, 50, 10)) {
        throw new \Exception("Página negativa aceita");
    }
    
    // PerPage 0 (inválido)
    if (\App\Utils\PaginationHelper::isValidPage(1, 50, 0)) {
        throw new \Exception("PerPage 0 aceito");
    }
    
    echo "✅ OK\n";
    $passed++;
} catch (\Throwable $e) {
    echo "❌ FALHOU: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::isValidPage: " . $e->getMessage();
}

// Testa integração com Validator
try {
    echo "Testando integração com Validator::validatePagination()... ";
    
    $queryParams = [
        'page' => '3',
        'limit' => '25'
    ];
    
    // Valida diretamente
    $validatorResult = \App\Utils\Validator::validatePagination($queryParams);
    
    // Usa no PaginationHelper
    $helperResult = \App\Utils\PaginationHelper::getPaginationParams($queryParams);
    
    if ($validatorResult['page'] === $helperResult['page'] &&
        $validatorResult['limit'] === $helperResult['limit']) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU (valores não coincidem)\n";
        $failed++;
        $errors[] = "Integração Validator: Valores não coincidem";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Integração Validator: " . $e->getMessage();
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

