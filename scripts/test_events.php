<?php

/**
 * Script para testar se o sistema de eventos está funcionando corretamente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

echo "🧪 TESTE DE SISTEMA DE EVENTOS\n";
echo str_repeat("=", 60) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

// Testa se EventDispatcher pode ser instanciado
try {
    echo "Testando EventDispatcher::instanciação... ";
    $dispatcher = new \App\Core\EventDispatcher();
    
    if ($dispatcher instanceof \App\Core\EventDispatcher) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "EventDispatcher: Instanciação falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "EventDispatcher: " . $e->getMessage();
}

// Testa se pode registrar listeners
try {
    echo "Testando EventDispatcher::listen()... ";
    $dispatcher = new \App\Core\EventDispatcher();
    $dispatcher->listen('test.event', function($payload) {
        return 'OK';
    });
    
    if ($dispatcher->hasListeners('test.event')) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "EventDispatcher::listen: Listener não registrado";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "EventDispatcher::listen: " . $e->getMessage();
}

// Testa se pode disparar eventos
try {
    echo "Testando EventDispatcher::dispatch()... ";
    $dispatcher = new \App\Core\EventDispatcher();
    $executed = false;
    
    $dispatcher->listen('test.dispatch', function($payload) use (&$executed) {
        $executed = true;
    });
    
    $dispatcher->dispatch('test.dispatch', ['test' => 'data']);
    
    if ($executed) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "EventDispatcher::dispatch: Listener não executado";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "EventDispatcher::dispatch: " . $e->getMessage();
}

// Testa se EventDispatcher está no Container
try {
    echo "Testando EventDispatcher no Container... ";
    $container = new \App\Core\Container();
    \App\Core\ContainerBindings::register($container);
    
    $dispatcher = $container->make(\App\Core\EventDispatcher::class);
    
    if ($dispatcher instanceof \App\Core\EventDispatcher) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "EventDispatcher no Container: Tipo incorreto";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "EventDispatcher no Container: " . $e->getMessage();
}

// Testa se EventListeners pode registrar listeners
try {
    echo "Testando EventListeners::register()... ";
    $container = new \App\Core\Container();
    \App\Core\ContainerBindings::register($container);
    
    $dispatcher = $container->make(\App\Core\EventDispatcher::class);
    \App\Core\EventListeners::register($dispatcher, $container);
    
    if ($dispatcher->hasListeners('appointment.created')) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "EventListeners::register: Listeners não registrados";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "EventListeners::register: " . $e->getMessage();
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
    echo "✅ TODOS OS TESTES DE EVENTOS PASSARAM!\n\n";
    exit(0);
}

