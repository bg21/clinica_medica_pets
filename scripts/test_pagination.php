<?php

/**
 * Script para testar se o PaginationHelper está funcionando corretamente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

echo "🧪 TESTE DE PAGINAÇÃO PADRONIZADA\n";
echo str_repeat("=", 60) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

// Testa se PaginationHelper pode ser instanciado (métodos estáticos)
try {
    echo "Testando PaginationHelper::calculateOffset()... ";
    $offset = \App\Utils\PaginationHelper::calculateOffset(1, 20);
    
    if ($offset === 0) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU (esperado 0, recebido {$offset})\n";
        $failed++;
        $errors[] = "PaginationHelper::calculateOffset: Offset incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::calculateOffset: " . $e->getMessage();
}

// Testa cálculo de offset para página 2
try {
    echo "Testando PaginationHelper::calculateOffset() página 2... ";
    $offset = \App\Utils\PaginationHelper::calculateOffset(2, 20);
    
    if ($offset === 20) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU (esperado 20, recebido {$offset})\n";
        $failed++;
        $errors[] = "PaginationHelper::calculateOffset página 2: Offset incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::calculateOffset página 2: " . $e->getMessage();
}

// Testa formatResponse
try {
    echo "Testando PaginationHelper::formatResponse()... ";
    $data = [1, 2, 3, 4, 5];
    $total = 25;
    $page = 2;
    $perPage = 5;
    
    $result = \App\Utils\PaginationHelper::formatResponse($data, $total, $page, $perPage);
    
    if (isset($result['data']) && isset($result['pagination'])) {
        if ($result['pagination']['current_page'] === 2 &&
            $result['pagination']['total'] === 25 &&
            $result['pagination']['total_pages'] === 5 &&
            $result['pagination']['has_next'] === true &&
            $result['pagination']['has_prev'] === true) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (estrutura incorreta)\n";
            $failed++;
            $errors[] = "PaginationHelper::formatResponse: Estrutura incorreta";
        }
    } else {
        echo "❌ FALHOU (chaves faltando)\n";
        $failed++;
        $errors[] = "PaginationHelper::formatResponse: Chaves faltando";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::formatResponse: " . $e->getMessage();
}

// Testa paginateArray
try {
    echo "Testando PaginationHelper::paginateArray()... ";
    $allData = range(1, 50); // Array de 1 a 50
    $paginationParams = [
        'page' => 2,
        'limit' => 10,
        'offset' => 10,
        'errors' => []
    ];
    
    $result = \App\Utils\PaginationHelper::paginateArray($allData, $paginationParams);
    
    if (isset($result['data']) && count($result['data']) === 10 &&
        $result['data'][0] === 11 && // Primeiro item da página 2
        $result['pagination']['total'] === 50) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "PaginationHelper::paginateArray: Resultado incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::paginateArray: " . $e->getMessage();
}

// Testa isValidPage
try {
    echo "Testando PaginationHelper::isValidPage()... ";
    $isValid = \App\Utils\PaginationHelper::isValidPage(2, 50, 10);
    
    if ($isValid === true) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "PaginationHelper::isValidPage: Validação incorreta";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::isValidPage: " . $e->getMessage();
}

// Testa isValidPage com página inválida
try {
    echo "Testando PaginationHelper::isValidPage() página inválida... ";
    $isValid = \App\Utils\PaginationHelper::isValidPage(10, 50, 10); // Página 10 de 5 páginas
    
    if ($isValid === false) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU (deveria ser inválida)\n";
        $failed++;
        $errors[] = "PaginationHelper::isValidPage: Deveria rejeitar página inválida";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "PaginationHelper::isValidPage inválida: " . $e->getMessage();
}

echo "\n" . str_repeat("=", 60) . "\n";
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
    echo "✅ TODOS OS TESTES DE PAGINAÇÃO PASSARAM!\n\n";
    exit(0);
}

