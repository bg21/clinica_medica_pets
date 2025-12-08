<?php

/**
 * Script para verificar sintaxe PHP de todos os arquivos criados/modificados
 */

echo "🔍 VERIFICAÇÃO DE SINTAXE PHP\n";
echo str_repeat("=", 60) . "\n\n";

$filesToCheck = [
    'App/Core/Container.php',
    'App/Core/ContainerBindings.php',
    'public/index.php',
];

$errors = [];
$passed = 0;

foreach ($filesToCheck as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    
    if (!file_exists($fullPath)) {
        $errors[] = "Arquivo não encontrado: {$file}";
        continue;
    }
    
    echo "Verificando {$file}... ";
    
    // Executa php -l (lint) no arquivo
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ ERRO\n";
        $errors[] = "{$file}: " . implode("\n", $output);
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESULTADOS:\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: " . count($errors) . "\n";
echo "📈 Total: " . count($filesToCheck) . "\n\n";

if (count($errors) > 0) {
    echo "❌ ERROS DE SINTAXE ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "✅ TODOS OS ARQUIVOS TÊM SINTAXE VÁLIDA!\n\n";
    exit(0);
}

