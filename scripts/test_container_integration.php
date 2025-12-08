<?php

/**
 * Script de teste de integração do Container
 * 
 * Testa se todos os controllers podem ser instanciados corretamente
 * e se as dependências são resolvidas adequadamente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

use App\Core\Container;
use App\Core\ContainerBindings;

echo "🧪 TESTE DE INTEGRAÇÃO DO CONTAINER\n";
echo str_repeat("=", 60) . "\n\n";

$container = new Container();
ContainerBindings::register($container);

$tests = [
    // Services
    ['class' => \App\Services\StripeService::class, 'name' => 'StripeService'],
    ['class' => \App\Services\PaymentService::class, 'name' => 'PaymentService'],
    ['class' => \App\Services\EmailService::class, 'name' => 'EmailService'],
    ['class' => \App\Services\RateLimiterService::class, 'name' => 'RateLimiterService'],
    ['class' => \App\Services\PlanLimitsService::class, 'name' => 'PlanLimitsService'],
    
    // Repositories
    ['class' => \App\Repositories\AppointmentRepository::class, 'name' => 'AppointmentRepository'],
    ['class' => \App\Repositories\ClientRepository::class, 'name' => 'ClientRepository'],
    ['class' => \App\Repositories\PetRepository::class, 'name' => 'PetRepository'],
    ['class' => \App\Repositories\ProfessionalRepository::class, 'name' => 'ProfessionalRepository'],
    ['class' => \App\Repositories\UserRepository::class, 'name' => 'UserRepository'],
    ['class' => \App\Repositories\ExamRepository::class, 'name' => 'ExamRepository'],
    
    // Controllers principais
    ['class' => \App\Controllers\AppointmentController::class, 'name' => 'AppointmentController'],
    ['class' => \App\Controllers\ClientController::class, 'name' => 'ClientController'],
    ['class' => \App\Controllers\PetController::class, 'name' => 'PetController'],
    ['class' => \App\Controllers\ProfessionalController::class, 'name' => 'ProfessionalController'],
    ['class' => \App\Controllers\UserController::class, 'name' => 'UserController'],
    ['class' => \App\Controllers\ExamController::class, 'name' => 'ExamController'],
    ['class' => \App\Controllers\SubscriptionController::class, 'name' => 'SubscriptionController'],
    ['class' => \App\Controllers\CustomerController::class, 'name' => 'CustomerController'],
    ['class' => \App\Controllers\AuthController::class, 'name' => 'AuthController'],
    ['class' => \App\Controllers\HealthCheckController::class, 'name' => 'HealthCheckController'],
];

$passed = 0;
$failed = 0;
$errors = [];

foreach ($tests as $test) {
    try {
        echo "Testando {$test['name']}... ";
        
        $instance = $container->make($test['class']);
        
        if ($instance instanceof $test['class']) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (tipo incorreto)\n";
            $failed++;
            $errors[] = "{$test['name']}: Tipo incorreto";
        }
    } catch (\Throwable $e) {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "{$test['name']}: " . $e->getMessage();
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESULTADOS:\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: {$failed}\n";
echo "📈 Total: " . count($tests) . "\n\n";

if ($failed > 0) {
    echo "❌ ERROS ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "✅ TODOS OS TESTES PASSARAM!\n\n";
    exit(0);
}

