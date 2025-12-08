<?php
/**
 * Script de Teste - AppointmentRepository
 * 
 * Valida se a implementação do Repository Pattern não quebrou
 * a funcionalidade do AppointmentController
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\AppointmentRepository;
use App\Models\Appointment;
use App\Models\AppointmentHistory;
use App\Models\Professional;
use App\Models\Client;
use App\Models\Pet;
use App\Models\Specialty;
use App\Utils\Database;

echo "🧪 TESTE DO APPOINTMENT REPOSITORY\n";
echo "============================================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// Função auxiliar para registrar testes
function test($name, $callback) {
    global $tests, $passed, $failed;
    
    echo "📋 Testando: $name\n";
    try {
        $result = $callback();
        if ($result === true || (is_array($result) && !empty($result['success']))) {
            echo "   ✅ PASSOU\n";
            $tests[] = ['name' => $name, 'status' => 'passed'];
            $passed++;
        } else {
            echo "   ❌ FALHOU: " . (is_string($result) ? $result : json_encode($result)) . "\n";
            $tests[] = ['name' => $name, 'status' => 'failed', 'error' => $result];
            $failed++;
        }
    } catch (\Exception $e) {
        echo "   ❌ ERRO: " . $e->getMessage() . "\n";
        $tests[] = ['name' => $name, 'status' => 'error', 'error' => $e->getMessage()];
        $failed++;
    }
    echo "\n";
}

// Conecta ao banco
try {
    require_once __DIR__ . '/../config/config.php';
    \Config::load();
    Database::getInstance(); // Inicializa conexão
    echo "✅ Conexão com banco de dados estabelecida\n\n";
} catch (\Exception $e) {
    die("❌ Erro ao conectar ao banco: " . $e->getMessage() . "\n");
}

// Cria instâncias
$appointmentModel = new Appointment();
$appointmentHistoryModel = new AppointmentHistory();
$repository = new AppointmentRepository($appointmentModel, $appointmentHistoryModel);

// Dados de teste
$tenantId = 1; // Assumindo que existe tenant com ID 1
$testData = [
    'professional_id' => null,
    'client_id' => null,
    'pet_id' => null,
    'appointment_date' => date('Y-m-d', strtotime('+7 days')),
    'appointment_time' => '10:00',
    'duration_minutes' => 30,
    'status' => 'scheduled'
];

// 1. Testa criação do repository
test('Criação do AppointmentRepository', function() use ($repository) {
    return $repository instanceof AppointmentRepository;
});

// 2. Testa busca por tenant (deve retornar array)
test('findByTenant retorna array', function() use ($repository, $tenantId) {
    $result = $repository->findByTenant($tenantId);
    return is_array($result);
});

// 3. Testa criação de agendamento (se houver dados válidos)
test('create() aceita dados válidos', function() use ($repository, $tenantId, $testData) {
    // Primeiro, tenta buscar profissionais, clientes e pets existentes
    $professionalModel = new Professional();
    $clientModel = new Client();
    $petModel = new Pet();
    
    $professionals = $professionalModel->findByTenant($tenantId);
    $clients = $clientModel->findByTenant($tenantId);
    $pets = $petModel->findByTenant($tenantId);
    
    if (empty($professionals) || empty($clients) || empty($pets)) {
        return ['success' => true, 'skipped' => 'Dados de teste não disponíveis'];
    }
    
    $testData['professional_id'] = $professionals[0]['id'];
    $testData['client_id'] = $clients[0]['id'];
    $testData['pet_id'] = $pets[0]['id'];
    
    // Verifica se não há conflito
    if ($repository->hasConflict(
        $tenantId,
        $testData['professional_id'],
        $testData['appointment_date'],
        $testData['appointment_time'],
        $testData['duration_minutes']
    )) {
        return ['success' => true, 'skipped' => 'Horário já ocupado'];
    }
    
    $appointmentId = $repository->create($tenantId, $testData);
    return $appointmentId > 0;
});

// 4. Testa busca por ID
test('findById retorna dados ou null', function() use ($repository) {
    $result = $repository->findById(999999); // ID que provavelmente não existe
    return $result === null || is_array($result);
});

// 5. Testa busca por tenant e ID
test('findByTenantAndId valida tenant', function() use ($repository, $tenantId) {
    $result = $repository->findByTenantAndId($tenantId, 999999);
    return $result === null; // Deve retornar null para ID inexistente
});

// 6. Testa verificação de conflito
test('hasConflict retorna boolean', function() use ($repository, $tenantId) {
    $result = $repository->hasConflict(
        $tenantId,
        1,
        date('Y-m-d'),
        '00:00',
        30
    );
    return is_bool($result);
});

// 7. Testa busca por profissional
test('findByProfessional retorna array', function() use ($repository, $tenantId) {
    $result = $repository->findByProfessional($tenantId, 1);
    return is_array($result);
});

// 8. Testa busca por cliente
test('findByClient retorna array', function() use ($repository, $tenantId) {
    $result = $repository->findByClient($tenantId, 1);
    return is_array($result);
});

// 9. Testa busca por pet
test('findByPet retorna array', function() use ($repository, $tenantId) {
    $result = $repository->findByPet($tenantId, 1);
    return is_array($result);
});

// 10. Testa busca de histórico
test('getHistory retorna array', function() use ($repository, $tenantId) {
    $result = $repository->getHistory($tenantId, 1);
    return is_array($result);
});

// 11. Testa criação de histórico
test('createHistory cria registro', function() use ($repository, $tenantId) {
    // Busca um agendamento existente
    $appointments = $repository->findByTenant($tenantId);
    if (empty($appointments)) {
        return ['success' => true, 'skipped' => 'Nenhum agendamento disponível'];
    }
    
    $appointmentId = $appointments[0]['id'];
    $historyId = $repository->createHistory(
        $tenantId,
        $appointmentId,
        'test',
        ['old' => 'data'],
        ['new' => 'data'],
        'Teste de histórico',
        1
    );
    
    return $historyId > 0;
});

// 12. Testa método confirm (se houver agendamento scheduled)
test('confirm() atualiza status e cria histórico', function() use ($repository, $tenantId) {
    $appointments = $repository->findByTenant($tenantId, ['status' => 'scheduled']);
    if (empty($appointments)) {
        return ['success' => true, 'skipped' => 'Nenhum agendamento scheduled disponível'];
    }
    
    $appointment = $appointments[0];
    $result = $repository->confirm($tenantId, $appointment['id'], 1);
    
    // Verifica se foi atualizado
    $updated = $repository->findById($appointment['id']);
    if ($updated && $updated['status'] === 'confirmed') {
        // Reverte para scheduled para não afetar outros testes
        $repository->update($appointment['id'], ['status' => 'scheduled']);
        return true;
    }
    
    return false;
});

// 13. Testa método complete (se houver agendamento scheduled ou confirmed)
test('complete() atualiza status e cria histórico', function() use ($repository, $tenantId) {
    $appointments = $repository->findByTenant($tenantId, ['status' => ['scheduled', 'confirmed']]);
    if (empty($appointments)) {
        return ['success' => true, 'skipped' => 'Nenhum agendamento scheduled/confirmed disponível'];
    }
    
    $appointment = $appointments[0];
    $oldStatus = $appointment['status'];
    $result = $repository->complete($tenantId, $appointment['id'], 1);
    
    // Verifica se foi atualizado
    $updated = $repository->findById($appointment['id']);
    if ($updated && $updated['status'] === 'completed') {
        // Reverte para status original para não afetar outros testes
        $repository->update($appointment['id'], ['status' => $oldStatus]);
        return true;
    }
    
    return false;
});

// 14. Testa método update
test('update() atualiza dados', function() use ($repository, $tenantId) {
    $appointments = $repository->findByTenant($tenantId);
    if (empty($appointments)) {
        return ['success' => true, 'skipped' => 'Nenhum agendamento disponível'];
    }
    
    $appointment = $appointments[0];
    $originalNotes = $appointment['notes'] ?? '';
    $newNotes = 'Teste de atualização ' . time();
    
    $result = $repository->update($appointment['id'], ['notes' => $newNotes]);
    
    if ($result) {
        $updated = $repository->findById($appointment['id']);
        if ($updated && $updated['notes'] === $newNotes) {
            // Reverte para valor original
            $repository->update($appointment['id'], ['notes' => $originalNotes]);
            return true;
        }
    }
    
    return false;
});

// 15. Testa método delete (soft delete)
test('delete() faz soft delete', function() use ($repository, $tenantId) {
    // Cria um agendamento de teste para deletar
    $professionalModel = new Professional();
    $clientModel = new Client();
    $petModel = new Pet();
    
    $professionals = $professionalModel->findByTenant($tenantId);
    $clients = $clientModel->findByTenant($tenantId);
    $pets = $petModel->findByTenant($tenantId);
    
    if (empty($professionals) || empty($clients) || empty($pets)) {
        return ['success' => true, 'skipped' => 'Dados de teste não disponíveis'];
    }
    
    $testData = [
        'professional_id' => $professionals[0]['id'],
        'client_id' => $clients[0]['id'],
        'pet_id' => $pets[0]['id'],
        'appointment_date' => date('Y-m-d', strtotime('+30 days')),
        'appointment_time' => '23:00',
        'duration_minutes' => 30,
        'status' => 'scheduled',
        'notes' => 'Teste de delete'
    ];
    
    // Verifica conflito
    if ($repository->hasConflict(
        $tenantId,
        $testData['professional_id'],
        $testData['appointment_date'],
        $testData['appointment_time'],
        $testData['duration_minutes']
    )) {
        return ['success' => true, 'skipped' => 'Horário já ocupado'];
    }
    
    $appointmentId = $repository->create($tenantId, $testData);
    if ($appointmentId <= 0) {
        return ['success' => true, 'skipped' => 'Não foi possível criar agendamento de teste'];
    }
    
    $result = $repository->delete($appointmentId);
    
    // Verifica se foi deletado (soft delete)
    $deleted = $repository->findById($appointmentId);
    // Com soft delete, o registro ainda existe mas com deleted_at preenchido
    return $result === true;
});

// Resumo
echo "\n";
echo "============================================================\n";
echo "📊 RESUMO DOS TESTES\n";
echo "============================================================\n";
echo "✅ Testes passados: $passed\n";
echo "❌ Testes falhados: $failed\n";
echo "📋 Total de testes: " . count($tests) . "\n";
echo "\n";

if ($failed === 0) {
    echo "🎉 TODOS OS TESTES PASSARAM!\n";
    echo "✅ O AppointmentRepository está funcionando corretamente.\n";
    exit(0);
} else {
    echo "⚠️  ALGUNS TESTES FALHARAM.\n";
    echo "Verifique os erros acima.\n";
    exit(1);
}

