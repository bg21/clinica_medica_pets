<?php

/**
 * Script de teste de integração para Sanitizer com DTOs
 * 
 * Verifica se os DTOs estão aplicando sanitização corretamente.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\DTOs\ClientCreateDTO;
use App\DTOs\ClientUpdateDTO;
use App\DTOs\AppointmentCreateDTO;
use App\DTOs\ProfessionalCreateDTO;
use App\DTOs\ProfessionalUpdateDTO;

echo "🧪 TESTANDO INTEGRAÇÃO DO SANITIZER COM DTOs\n";
echo str_repeat("=", 80) . "\n\n";

$tests = [];
$passed = 0;
$failed = 0;

function test(string $name, callable $test): void
{
    global $tests, $passed, $failed;
    
    try {
        $result = $test();
        if ($result === true) {
            echo "✅ {$name}\n";
            $passed++;
        } else {
            echo "❌ {$name}\n";
            echo "   Esperado: true, Recebido: " . var_export($result, true) . "\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ {$name}\n";
        echo "   Erro: " . $e->getMessage() . "\n";
        $failed++;
    }
    
    $tests[] = $name;
}

echo "📋 Testando ClientCreateDTO\n";
echo str_repeat("-", 80) . "\n";

test("ClientCreateDTO - Sanitiza HTML no nome", function() {
    $dto = ClientCreateDTO::fromArray([
        'name' => '<script>alert("xss")</script>',
        'email' => 'teste@exemplo.com'
    ], 1);
    
    // O nome deve ter HTML escapado
    return strpos($dto->name, '<script>') === false;
});

test("ClientCreateDTO - Sanitiza email", function() {
    $dto = ClientCreateDTO::fromArray([
        'name' => 'Teste',
        'email' => '  teste@exemplo.com  '
    ], 1);
    
    // Email deve estar sem espaços
    return $dto->email === 'teste@exemplo.com';
});

test("ClientCreateDTO - Sanitiza telefone", function() {
    $dto = ClientCreateDTO::fromArray([
        'name' => 'Teste',
        'phone' => '(11) 98765-4321@#$'
    ], 1);
    
    // Telefone deve ter caracteres inválidos removidos
    return $dto->phone === '(11) 98765-4321';
});

test("ClientCreateDTO - Sanitiza documento", function() {
    $dto = ClientCreateDTO::fromArray([
        'name' => 'Teste',
        'document' => '123.456.789-00'
    ], 1);
    
    // Documento deve ter apenas números
    return $dto->document === '12345678900';
});

echo "\n📋 Testando ClientUpdateDTO\n";
echo str_repeat("-", 80) . "\n";

test("ClientUpdateDTO - Sanitiza HTML no nome", function() {
    $dto = ClientUpdateDTO::fromArray([
        'name' => '<script>alert("xss")</script>'
    ], 1, 1);
    
    // O nome deve ter HTML escapado
    return strpos($dto->name, '<script>') === false;
});

test("ClientUpdateDTO - Sanitiza email", function() {
    $dto = ClientUpdateDTO::fromArray([
        'email' => '  teste@exemplo.com  '
    ], 1, 1);
    
    // Email deve estar sem espaços
    return $dto->email === 'teste@exemplo.com';
});

echo "\n📋 Testando AppointmentCreateDTO\n";
echo str_repeat("-", 80) . "\n";

test("AppointmentCreateDTO - Sanitiza IDs", function() {
    $dto = AppointmentCreateDTO::fromArray([
        'professional_id' => '123',
        'client_id' => '456',
        'pet_id' => '789',
        'appointment_date' => '2025-12-31',
        'appointment_time' => '10:00'
    ], 1);
    
    // IDs devem ser inteiros
    return is_int($dto->professionalId) && 
           is_int($dto->clientId) && 
           is_int($dto->petId);
});

test("AppointmentCreateDTO - Sanitiza data e hora", function() {
    $dto = AppointmentCreateDTO::fromArray([
        'professional_id' => 1,
        'client_id' => 1,
        'pet_id' => 1,
        'appointment_date' => '  2025-12-31  ',
        'appointment_time' => '  10:00  '
    ], 1);
    
    // Data e hora devem estar sem espaços
    return $dto->appointmentDate === '2025-12-31' && 
           $dto->appointmentTime === '10:00';
});

echo "\n📋 Testando ProfessionalCreateDTO\n";
echo str_repeat("-", 80) . "\n";

test("ProfessionalCreateDTO - Sanitiza CRMV", function() {
    $dto = ProfessionalCreateDTO::fromArray([
        'user_id' => 1,
        'crmv' => '  CRMV123  '
    ], 1);
    
    // CRMV deve estar sem espaços
    return $dto->crmv === 'CRMV123';
});

test("ProfessionalCreateDTO - Sanitiza duração", function() {
    $dto = ProfessionalCreateDTO::fromArray([
        'user_id' => 1,
        'crmv' => 'CRMV123',
        'default_consultation_duration' => '30'
    ], 1);
    
    // Duração deve ser inteiro
    return is_int($dto->defaultConsultationDuration) && 
           $dto->defaultConsultationDuration === 30;
});

echo "\n📋 Testando ProfessionalUpdateDTO\n";
echo str_repeat("-", 80) . "\n";

test("ProfessionalUpdateDTO - Sanitiza CRMV", function() {
    $dto = ProfessionalUpdateDTO::fromArray([
        'crmv' => '  CRMV123  '
    ], 1, 1);
    
    // CRMV deve estar sem espaços
    return $dto->crmv === 'CRMV123';
});

test("ProfessionalUpdateDTO - Sanitiza duração", function() {
    $dto = ProfessionalUpdateDTO::fromArray([
        'default_consultation_duration' => '45'
    ], 1, 1);
    
    // Duração deve ser inteiro
    return is_int($dto->defaultConsultationDuration) && 
           $dto->defaultConsultationDuration === 45;
});

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "📊 RESUMO DOS TESTES\n";
echo str_repeat("=", 80) . "\n";
echo "Total de testes: " . count($tests) . "\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: {$failed}\n";
echo "\n";

if ($failed === 0) {
    echo "🎉 Todos os testes passaram!\n";
    exit(0);
} else {
    echo "⚠️  Alguns testes falharam. Verifique os erros acima.\n";
    exit(1);
}

