<?php

/**
 * Script master para executar todos os testes do container
 * 
 * Executa todos os testes em sequência e gera um relatório final
 */

echo "🚀 EXECUTANDO TODOS OS TESTES DO CONTAINER\n";
echo str_repeat("=", 70) . "\n\n";

$tests = [
    'test_container_integration.php' => 'Teste de Integração',
    'test_controllers_instantiation.php' => 'Teste de Instanciação',
    'test_container_singleton.php' => 'Teste de Singleton',
    'test_all_endpoints.php' => 'Teste de Endpoints',
    'test_syntax_check.php' => 'Verificação de Sintaxe',
];

$results = [];
$totalPassed = 0;
$totalFailed = 0;

foreach ($tests as $script => $name) {
    echo "▶️  Executando: {$name}...\n";
    echo str_repeat("-", 70) . "\n";
    
    $output = [];
    $returnVar = 0;
    exec("php scripts/{$script} 2>&1", $output, $returnVar);
    
    echo implode("\n", $output) . "\n\n";
    
    $results[$name] = [
        'passed' => $returnVar === 0,
        'output' => $output
    ];
    
    if ($returnVar === 0) {
        $totalPassed++;
    } else {
        $totalFailed++;
    }
}

echo str_repeat("=", 70) . "\n";
echo "📊 RELATÓRIO FINAL\n";
echo str_repeat("=", 70) . "\n\n";

foreach ($results as $name => $result) {
    $status = $result['passed'] ? '✅ PASSOU' : '❌ FALHOU';
    echo "{$status} - {$name}\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "📈 RESUMO:\n";
echo "✅ Testes que passaram: {$totalPassed}\n";
echo "❌ Testes que falharam: {$totalFailed}\n";
echo "📊 Total de testes: " . count($tests) . "\n";
echo "📈 Taxa de sucesso: " . round(($totalPassed / count($tests)) * 100, 2) . "%\n\n";

if ($totalFailed > 0) {
    echo "⚠️  ALGUNS TESTES FALHARAM!\n\n";
    exit(1);
} else {
    echo "✅ TODOS OS TESTES PASSARAM COM SUCESSO!\n\n";
    exit(0);
}

