<?php

/**
 * Script para testar se os endpoints principais ainda funcionam
 * após a implementação do container
 * 
 * Este script verifica se os controllers podem ser instanciados
 * e se os métodos principais existem
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

use App\Core\Container;
use App\Core\ContainerBindings;

echo "🧪 TESTE DE ENDPOINTS E MÉTODOS DOS CONTROLLERS\n";
echo str_repeat("=", 60) . "\n\n";

$container = new Container();
ContainerBindings::register($container);

// Controllers e métodos principais que devem existir
$controllersToTest = [
    \App\Controllers\AppointmentController::class => [
        'list', 'create', 'get', 'update', 'delete', 
        'confirm', 'complete', 'checkIn', 'availableSlots', 'history'
    ],
    \App\Controllers\ClientController::class => [
        'list', 'create', 'get', 'update', 'delete', 'listPets'
    ],
    \App\Controllers\PetController::class => [
        'list', 'create', 'get', 'update', 'delete', 
        'listAppointments', 'listExams'
    ],
    \App\Controllers\ProfessionalController::class => [
        'list', 'create', 'get', 'update', 'delete',
        'schedule', 'updateSchedule', 'createBlock', 'deleteBlock',
        'getCurrentUserProfessional'
    ],
    \App\Controllers\UserController::class => [
        'list', 'create', 'get', 'update', 'delete', 'updateRole'
    ],
    \App\Controllers\SubscriptionController::class => [
        'create', 'list', 'get', 'update', 'cancel', 'reactivate'
    ],
    \App\Controllers\CustomerController::class => [
        'create', 'list', 'get', 'update'
    ],
    \App\Controllers\AuthController::class => [
        'register', 'registerEmployee', 'login', 'logout', 'me'
    ],
    \App\Controllers\HealthCheckController::class => [
        'basic', 'detailed'
    ],
];

$passed = 0;
$failed = 0;
$errors = [];

foreach ($controllersToTest as $controllerClass => $methods) {
    try {
        echo "Testando " . basename(str_replace('\\', '/', $controllerClass)) . "...\n";
        
        $controller = $container->make($controllerClass);
        
        if (!($controller instanceof $controllerClass)) {
            throw new \Exception("Tipo incorreto");
        }
        
        foreach ($methods as $method) {
            if (!method_exists($controller, $method)) {
                throw new \Exception("Método '{$method}' não existe");
            }
        }
        
        echo "  ✅ OK (todos os métodos existem)\n";
        $passed++;
        
    } catch (\Throwable $e) {
        echo "  ❌ ERRO: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = basename(str_replace('\\', '/', $controllerClass)) . ": " . $e->getMessage();
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESULTADOS:\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: {$failed}\n";
echo "📈 Total: " . count($controllersToTest) . "\n\n";

if ($failed > 0) {
    echo "❌ ERROS ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "✅ TODOS OS CONTROLLERS E MÉTODOS ESTÃO OK!\n\n";
    exit(0);
}

