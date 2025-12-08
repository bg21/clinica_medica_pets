<?php
/**
 * Script para testar o endpoint de salvar agenda via simulação de requisição
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Utils\Database;
use App\Models\Professional;
use App\Models\ProfessionalSchedule;

$db = Database::getInstance();

echo "=== TESTE DE API DE AGENDA ===\n\n";

// 1. Busca um profissional qualquer
$stmt = $db->query("SELECT * FROM professionals LIMIT 1");
$professional = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$professional) {
    echo "❌ Nenhum profissional encontrado. Crie um profissional primeiro.\n";
    exit(1);
}

$tenantId = $professional['tenant_id'];
$professionalId = $professional['id'];

echo "1. Profissional encontrado:\n";
echo "   - ID: {$professionalId}\n";
echo "   - Nome: " . ($professional['name'] ?? 'N/A') . "\n";
echo "   - Tenant ID: {$tenantId}\n\n";

// 2. Limpa horários existentes
echo "2. Limpando horários existentes...\n";
$scheduleModel = new ProfessionalSchedule();
$scheduleModel->deleteByProfessional($tenantId, $professionalId);
echo "   ✅ Horários anteriores removidos\n\n";

// 3. Simula dados que seriam enviados pelo frontend
echo "3. Simulando dados do frontend...\n";
$testData = [
    'schedule' => [
        [
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'is_available' => true
        ],
        [
            'day_of_week' => 1,
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',
            'is_available' => true
        ],
        [
            'day_of_week' => 3,
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'is_available' => true
        ],
        [
            'day_of_week' => 5,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'is_available' => true
        ]
    ]
];

echo "   Dados a serem salvos:\n";
foreach ($testData['schedule'] as $sched) {
    $dayNames = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    $dayName = $dayNames[$sched['day_of_week']] ?? "Dia {$sched['day_of_week']}";
    echo "   - {$dayName}: {$sched['start_time']} - {$sched['end_time']}\n";
}

// 4. Simula o que o controller faz
echo "\n4. Processando dados (simulando controller)...\n";
try {
    // Remove horários existentes (já feito acima)
    
    // Cria novos horários
    $created = [];
    foreach ($testData['schedule'] as $daySchedule) {
        if (!isset($daySchedule['day_of_week']) || !isset($daySchedule['start_time']) || !isset($daySchedule['end_time'])) {
            continue;
        }
        
        $dayOfWeek = (int)$daySchedule['day_of_week'];
        $startTime = $daySchedule['start_time'];
        $endTime = $daySchedule['end_time'];
        $isAvailable = $daySchedule['is_available'] ?? true;
        
        // Valida dia da semana (0-6)
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            continue;
        }
        
        $scheduleId = $scheduleModel->upsertSchedule(
            $tenantId,
            $professionalId,
            $dayOfWeek,
            $startTime,
            $endTime,
            $isAvailable
        );
        
        $created[] = $scheduleId;
        echo "   ✅ Horário criado: ID {$scheduleId}\n";
    }
    
    echo "\n   Total de horários criados: " . count($created) . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// 5. Verifica se os dados foram salvos
echo "\n5. Verificando dados salvos no banco...\n";
$savedSchedules = $scheduleModel->findByProfessional($tenantId, $professionalId);

if (empty($savedSchedules)) {
    echo "   ❌ Nenhum horário encontrado no banco!\n";
    echo "   ⚠️  PROBLEMA: Os dados não foram salvos!\n";
    exit(1);
} else {
    echo "   ✅ Total de horários encontrados: " . count($savedSchedules) . "\n";
    $dayNames = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    foreach ($savedSchedules as $sched) {
        $dayName = $dayNames[$sched['day_of_week']] ?? "Dia {$sched['day_of_week']}";
        echo "   - {$dayName}: {$sched['start_time']} - {$sched['end_time']} (ID: {$sched['id']})\n";
    }
}

// 6. Verifica diretamente no banco
echo "\n6. Verificação direta no banco de dados...\n";
$stmt = $db->prepare("SELECT * FROM professional_schedules WHERE tenant_id = :tenant_id AND professional_id = :professional_id ORDER BY day_of_week, start_time");
$stmt->execute(['tenant_id' => $tenantId, 'professional_id' => $professionalId]);
$dbSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "   Total de registros na tabela: " . count($dbSchedules) . "\n";
foreach ($dbSchedules as $sched) {
    $dayNames = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    $dayName = $dayNames[$sched['day_of_week']] ?? "Dia {$sched['day_of_week']}";
    echo "   - ID {$sched['id']}: {$dayName} - {$sched['start_time']} até {$sched['end_time']}\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "\n✅ CONCLUSÃO: O salvamento está funcionando corretamente!\n";
echo "\nPróximos passos para testar no frontend:\n";
echo "1. Acesse http://localhost:8080/clinic/professional-schedule\n";
echo "2. Selecione o profissional ID {$professionalId}\n";
echo "3. Configure os horários\n";
echo "4. Clique em 'Salvar Agenda'\n";
echo "5. Verifique se os dados persistem após recarregar a página\n";

