<?php

/**
 * Script para testar se os Services podem ser instanciados e funcionam corretamente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

use App\Core\Container;
use App\Core\ContainerBindings;

echo "🧪 TESTE DE SERVICES\n";
echo str_repeat("=", 60) . "\n\n";

$container = new Container();
ContainerBindings::register($container);

$services = [
    \App\Services\ClientService::class => 'ClientService',
    \App\Services\AppointmentService::class => 'AppointmentService',
    \App\Services\ProfessionalService::class => 'ProfessionalService',
];

$passed = 0;
$failed = 0;
$errors = [];

foreach ($services as $serviceClass => $name) {
    try {
        echo "Testando {$name}... ";
        
        $instance = $container->make($serviceClass);
        
        if ($instance instanceof $serviceClass) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (tipo incorreto)\n";
            $failed++;
            $errors[] = "{$name}: Tipo incorreto";
        }
    } catch (\Throwable $e) {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "{$name}: " . $e->getMessage();
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESULTADOS:\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: {$failed}\n";
echo "📈 Total: " . count($services) . "\n\n";

if ($failed > 0) {
    echo "❌ ERROS ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "✅ TODOS OS SERVICES PODEM SER INSTANCIADOS!\n\n";
    exit(0);
}

