<?php

/**
 * Script para testar se as transações estão funcionando corretamente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

echo "🧪 TESTE DE TRANSAÇÕES\n";
echo str_repeat("=", 60) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

// Testa se Transaction::execute funciona
try {
    echo "Testando Transaction::execute() básico... ";
    $result = \App\Utils\Transaction::execute(function($db) {
        return "OK";
    });
    
    if ($result === "OK") {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "Transaction::execute: Retorno incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Transaction::execute: " . $e->getMessage();
}

// Testa se Transaction::execute faz rollback em caso de erro
try {
    echo "Testando Transaction::execute() com rollback... ";
    try {
        \App\Utils\Transaction::execute(function($db) {
            throw new \RuntimeException("Erro de teste");
        });
        echo "❌ FALHOU (deveria lançar exceção)\n";
        $failed++;
        $errors[] = "Transaction::execute: Não lançou exceção";
    } catch (\RuntimeException $e) {
        if ($e->getMessage() === "Erro de teste") {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (exceção incorreta)\n";
            $failed++;
            $errors[] = "Transaction::execute: Exceção incorreta";
        }
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Transaction::execute rollback: " . $e->getMessage();
}

// Testa se Transaction::executeMultiple funciona
try {
    echo "Testando Transaction::executeMultiple()... ";
    $results = \App\Utils\Transaction::executeMultiple([
        function($db) { return 1; },
        function($db) { return 2; },
        function($db) { return 3; }
    ]);
    
    if ($results === [1, 2, 3]) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "Transaction::executeMultiple: Resultados incorretos";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "Transaction::executeMultiple: " . $e->getMessage();
}

// Testa se AppointmentRepository pode ser instanciado
try {
    echo "Testando AppointmentRepository com transações... ";
    $container = new \App\Core\Container();
    \App\Core\ContainerBindings::register($container);
    
    $repository = $container->make(\App\Repositories\AppointmentRepository::class);
    
    if ($repository instanceof \App\Repositories\AppointmentRepository) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "AppointmentRepository: Instanciação falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "AppointmentRepository: " . $e->getMessage();
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
    echo "✅ TODAS AS TRANSAÇÕES FUNCIONAM CORRETAMENTE!\n\n";
    exit(0);
}

