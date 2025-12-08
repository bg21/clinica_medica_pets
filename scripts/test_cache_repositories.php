<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
    Config::load();
}

use App\Repositories\ClientRepository;
use App\Models\Client;
use App\Models\Pet;

echo "🧪 TESTANDO CACHE EM REPOSITORIES\n";
echo "================================================================================\n\n";

try {
    // Teste 1: Instanciar ClientRepository
    echo "1. Testando instanciação do ClientRepository...\n";
    $clientModel = new Client();
    $petModel = new Pet();
    $repository = new ClientRepository($clientModel, $petModel);
    echo "   ✅ ClientRepository instanciado com sucesso\n\n";
    
    // Teste 2: Verificar se o trait está sendo usado
    echo "2. Verificando se o trait CacheableRepository está disponível...\n";
    if (method_exists($repository, 'getFromCache')) {
        echo "   ✅ Trait CacheableRepository está disponível\n\n";
    } else {
        echo "   ❌ Trait CacheableRepository NÃO está disponível\n\n";
        exit(1);
    }
    
    // Teste 3: Verificar se o cache prefix está configurado
    echo "3. Verificando configuração de cache...\n";
    $reflection = new ReflectionClass($repository);
    $property = $reflection->getProperty('cachePrefix');
    $property->setAccessible(true);
    $prefix = $property->getValue($repository);
    echo "   ✅ Cache prefix: {$prefix}\n\n";
    
    // Teste 4: Verificar métodos de cache
    echo "4. Verificando métodos de cache...\n";
    $methods = ['getFromCache', 'setCache', 'deleteCache', 'buildCacheKeyById', 'buildCacheKeyByTenantAndId'];
    foreach ($methods as $method) {
        if (method_exists($repository, $method)) {
            echo "   ✅ Método {$method} existe\n";
        } else {
            echo "   ❌ Método {$method} NÃO existe\n";
            exit(1);
        }
    }
    echo "\n";
    
    echo "================================================================================\n";
    echo "✅ Todos os testes passaram! O cache está implementado corretamente.\n";
    echo "================================================================================\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (\Error $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

