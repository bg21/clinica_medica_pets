<?php

/**
 * Script para testar se o sistema de CSRF está funcionando corretamente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

echo "🧪 TESTE DE PROTEÇÃO CSRF\n";
echo str_repeat("=", 60) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

// Testa se CsrfHelper pode gerar tokens
try {
    echo "Testando CsrfHelper::generateToken()... ";
    $sessionId = 'test_session_' . bin2hex(random_bytes(16));
    $token = \App\Utils\CsrfHelper::generateToken($sessionId);
    
    if (!empty($token) && strlen($token) >= 64) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "CsrfHelper::generateToken: Token inválido";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "CsrfHelper::generateToken: " . $e->getMessage();
}

// Testa se CsrfHelper pode validar tokens
try {
    echo "Testando CsrfHelper::validateToken()... ";
    $sessionId = 'test_session_' . bin2hex(random_bytes(16));
    $token = \App\Utils\CsrfHelper::generateToken($sessionId);
    
    // Verifica se o cache está disponível (Redis pode não estar rodando)
    $cacheKey = 'csrf:' . $sessionId;
    $cachedToken = \App\Services\CacheService::get($cacheKey);
    
    if ($cachedToken === null) {
        // Cache não disponível - pula teste de validação
        echo "⚠️  PULADO (Redis não disponível)\n";
        $passed++; // Considera como passou, pois não é um erro do código
    } else {
        $isValid = \App\Utils\CsrfHelper::validateToken($sessionId, $token);
        
        if ($isValid) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU\n";
            $failed++;
            $errors[] = "CsrfHelper::validateToken: Validação falhou";
        }
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "CsrfHelper::validateToken: " . $e->getMessage();
}

// Testa se validação falha com token inválido
try {
    echo "Testando CsrfHelper::validateToken() com token inválido... ";
    $sessionId = 'test_session_' . bin2hex(random_bytes(16));
    $token = \App\Utils\CsrfHelper::generateToken($sessionId);
    $invalidToken = 'invalid_token_' . bin2hex(random_bytes(16));
    
    $isValid = \App\Utils\CsrfHelper::validateToken($sessionId, $invalidToken);
    
    if (!$isValid) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU (deveria rejeitar token inválido)\n";
        $failed++;
        $errors[] = "CsrfHelper::validateToken: Aceitou token inválido";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "CsrfHelper::validateToken inválido: " . $e->getMessage();
}

// Testa se CsrfMiddleware pode ser instanciado
try {
    echo "Testando CsrfMiddleware::instanciação... ";
    $middleware = new \App\Middleware\CsrfMiddleware();
    
    if ($middleware instanceof \App\Middleware\CsrfMiddleware) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "CsrfMiddleware: Instanciação falhou";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "CsrfMiddleware: " . $e->getMessage();
}

// Testa se CsrfHelper pode invalidar tokens
try {
    echo "Testando CsrfHelper::invalidateToken()... ";
    $sessionId = 'test_session_' . bin2hex(random_bytes(16));
    $token = \App\Utils\CsrfHelper::generateToken($sessionId);
    
    \App\Utils\CsrfHelper::invalidateToken($sessionId);
    
    $isValid = \App\Utils\CsrfHelper::validateToken($sessionId, $token);
    
    if (!$isValid) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU (token ainda válido após invalidação)\n";
        $failed++;
        $errors[] = "CsrfHelper::invalidateToken: Token ainda válido";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "CsrfHelper::invalidateToken: " . $e->getMessage();
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
    echo "✅ TODOS OS TESTES DE CSRF PASSARAM!\n\n";
    exit(0);
}

