<?php

/**
 * Script para testar se os DTOs podem ser instanciados e validam corretamente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

echo "🧪 TESTE DE DTOs\n";
echo str_repeat("=", 60) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

// Testa ClientCreateDTO
try {
    echo "Testando ClientCreateDTO::fromArray()... ";
    $dto = \App\DTOs\ClientCreateDTO::fromArray([
        'name' => 'Cliente Teste',
        'email' => 'teste@example.com',
        'status' => 'active'
    ], 1);
    
    if ($dto->name === 'Cliente Teste' && $dto->tenantId === 1) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "ClientCreateDTO: Propriedades incorretas";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "ClientCreateDTO: " . $e->getMessage();
}

// Testa validação de ClientCreateDTO (deve falhar)
try {
    echo "Testando ClientCreateDTO validação (deve falhar)... ";
    try {
        $dto = \App\DTOs\ClientCreateDTO::fromArray([
            'name' => '', // Nome vazio
        ], 1);
        echo "❌ FALHOU (deveria lançar exceção)\n";
        $failed++;
        $errors[] = "ClientCreateDTO: Validação não funcionou";
    } catch (\InvalidArgumentException $e) {
        echo "✅ OK\n";
        $passed++;
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "ClientCreateDTO validação: " . $e->getMessage();
}

// Testa AppointmentCreateDTO
try {
    echo "Testando AppointmentCreateDTO::fromArray()... ";
    $dto = \App\DTOs\AppointmentCreateDTO::fromArray([
        'professional_id' => 1,
        'client_id' => 1,
        'pet_id' => 1,
        'appointment_date' => date('Y-m-d', strtotime('+1 day')),
        'appointment_time' => '10:00',
        'duration_minutes' => 30
    ], 1);
    
    if ($dto->professionalId === 1 && $dto->tenantId === 1) {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "AppointmentCreateDTO: Propriedades incorretas";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "AppointmentCreateDTO: " . $e->getMessage();
}

// Testa ProfessionalCreateDTO
try {
    echo "Testando ProfessionalCreateDTO::fromArray()... ";
    $dto = \App\DTOs\ProfessionalCreateDTO::fromArray([
        'user_id' => 1,
        'crmv' => 'CRMV123',
        'status' => 'active'
    ], 1);
    
    if ($dto->userId === 1 && $dto->crmv === 'CRMV123') {
        echo "✅ OK\n";
        $passed++;
    } else {
        echo "❌ FALHOU\n";
        $failed++;
        $errors[] = "ProfessionalCreateDTO: Propriedades incorretas";
    }
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $failed++;
    $errors[] = "ProfessionalCreateDTO: " . $e->getMessage();
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
    echo "✅ TODOS OS DTOs FUNCIONAM CORRETAMENTE!\n\n";
    exit(0);
}

