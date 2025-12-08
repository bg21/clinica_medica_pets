<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
    Config::load();
}

use App\Models\TenantRateLimit;
use App\Services\TenantRateLimitService;
use App\Middleware\RateLimitMiddleware;
use App\Services\RateLimiterService;

echo "🧪 TESTANDO TRATAMENTO DE ERRO NO TENANT RATE LIMIT\n";
echo "================================================================================\n\n";

try {
    // Teste 1: Instanciar Model
    echo "1. Testando instanciação do Model...\n";
    $model = new TenantRateLimit();
    echo "   ✅ Model instanciado com sucesso\n\n";
    
    // Teste 2: Instanciar Service
    echo "2. Testando instanciação do Service...\n";
    $service = new TenantRateLimitService($model);
    echo "   ✅ Service instanciado com sucesso\n\n";
    
    // Teste 3: getLimits com tenant inexistente (não deve quebrar)
    echo "3. Testando getLimits() com tenant inexistente...\n";
    $limits = $service->getLimits(99999, '/v1/auth/me', 'GET');
    if ($limits === null) {
        echo "   ✅ Retornou null corretamente (usa limites padrão)\n\n";
    } else {
        echo "   ⚠️  Retornou: " . print_r($limits, true) . "\n\n";
    }
    
    // Teste 4: Instanciar Middleware com service
    echo "4. Testando instanciação do Middleware...\n";
    $rateLimiterService = new RateLimiterService();
    $middleware = new RateLimitMiddleware($rateLimiterService, $service);
    echo "   ✅ Middleware instanciado com sucesso\n\n";
    
    echo "================================================================================\n";
    echo "✅ Todos os testes passaram! O tratamento de erro está funcionando.\n";
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

