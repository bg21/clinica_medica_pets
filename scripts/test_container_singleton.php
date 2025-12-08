<?php

/**
 * Script para testar se os singletons estão funcionando corretamente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

use App\Core\Container;
use App\Core\ContainerBindings;

echo "🧪 TESTE DE SINGLETON PATTERN\n";
echo str_repeat("=", 60) . "\n\n";

$container = new Container();
ContainerBindings::register($container);

// Services que devem ser singletons
$singletonClasses = [
    \App\Services\StripeService::class,
    \App\Services\PaymentService::class,
    \App\Services\EmailService::class,
    \App\Models\Appointment::class,
    \App\Models\Client::class,
    \App\Repositories\AppointmentRepository::class,
];

$passed = 0;
$failed = 0;

foreach ($singletonClasses as $className) {
    try {
        $shortName = basename(str_replace('\\', '/', $className));
        echo "Testando singleton {$shortName}... ";
        
        $instance1 = $container->make($className);
        $instance2 = $container->make($className);
        
        if ($instance1 === $instance2) {
            echo "✅ OK (mesma instância)\n";
            $passed++;
        } else {
            echo "❌ FALHOU (instâncias diferentes)\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        $failed++;
    }
}

// Controllers que NÃO devem ser singletons
$nonSingletonClasses = [
    \App\Controllers\AppointmentController::class,
    \App\Controllers\ClientController::class,
    \App\Controllers\PetController::class,
];

echo "\nTestando que controllers NÃO são singletons...\n";

foreach ($nonSingletonClasses as $className) {
    try {
        $shortName = basename(str_replace('\\', '/', $className));
        echo "Testando {$shortName}... ";
        
        $instance1 = $container->make($className);
        $instance2 = $container->make($className);
        
        if ($instance1 !== $instance2) {
            echo "✅ OK (instâncias diferentes)\n";
            $passed++;
        } else {
            echo "❌ FALHOU (mesma instância - deveria ser diferente)\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESULTADOS:\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: {$failed}\n\n";

if ($failed > 0) {
    echo "❌ ALGUNS TESTES FALHARAM!\n\n";
    exit(1);
} else {
    echo "✅ TODOS OS TESTES DE SINGLETON PASSARAM!\n\n";
    exit(0);
}

