<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
    Config::load();
}

use App\Services\Logger;
use Flight;

echo "🧪 TESTANDO LOGGING ESTRUTURADO COM CONTEXTO\n";
echo "================================================================================\n\n";

// Simula contexto do Flight
Flight::set('request_id', 'test_request_12345678901234567890123456789012');
Flight::set('tenant_id', 3);
Flight::set('user_id', 10);

// Simula IP do cliente
$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

echo "📋 Testando Logger::info() com contexto automático...\n";
echo "--------------------------------------------------------------------------------\n";

// Teste 1: Log sem contexto (deve adicionar automaticamente)
Logger::info('Teste de log sem contexto explícito');

// Teste 2: Log com contexto parcial (deve adicionar o que falta)
Logger::info('Teste de log com contexto parcial', [
    'appointment_id' => 123,
    'action' => 'created'
]);

// Teste 3: Log com contexto completo (não deve sobrescrever)
Logger::info('Teste de log com contexto completo', [
    'appointment_id' => 456,
    'tenant_id' => 5, // Deve manter este valor, não sobrescrever
    'user_id' => 20,  // Deve manter este valor, não sobrescrever
    'ip_address' => '10.0.0.1' // Deve manter este valor, não sobrescrever
]);

echo "\n✅ Testes de logging executados!\n";
echo "💡 Verifique o arquivo de log para ver se o contexto foi adicionado corretamente.\n";
echo "   Os logs devem incluir automaticamente: request_id, tenant_id, user_id, ip_address\n\n";

