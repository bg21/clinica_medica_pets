<?php
/**
 * Script para testar o sistema de agenda de profissionais
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

Config::load();

use App\Utils\Database;
use App\Models\Professional;
use App\Models\ProfessionalSchedule;
use App\Models\ScheduleBlock;

$db = Database::getInstance();

echo "=== TESTE DE AGENDA DE PROFISSIONAIS ===\n\n";

// 1. Verifica se há profissionais cadastrados
$professionalModel = new Professional();
$tenantId = 3; // Ajuste conforme necessário

echo "1. Verificando profissionais do tenant {$tenantId}...\n";

// Busca profissionais usando método correto
$stmt = $db->prepare("SELECT * FROM professionals WHERE tenant_id = :tenant_id ORDER BY id ASC LIMIT 5");
$stmt->execute(['tenant_id' => $tenantId]);
$professionals = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($professionals)) {
    echo "❌ Nenhum profissional encontrado. Crie um profissional primeiro.\n";
    exit(1);
}

$professional = $professionals[0];
$professionalId = $professional['id'];

echo "✅ Profissional encontrado: ID {$professionalId}, Nome: " . ($professional['name'] ?? 'N/A') . "\n\n";

// 2. Testa criação de horários
echo "2. Testando criação de horários...\n";
$scheduleModel = new ProfessionalSchedule();

// Limpa horários existentes primeiro
$scheduleModel->deleteByProfessional($tenantId, $professionalId);
echo "   - Horários anteriores removidos\n";

// Cria alguns horários de exemplo
$testSchedules = [
    ['day' => 1, 'start' => '08:00:00', 'end' => '12:00:00'], // Segunda-feira
    ['day' => 1, 'start' => '14:00:00', 'end' => '18:00:00'], // Segunda-feira (tarde)
    ['day' => 3, 'start' => '08:00:00', 'end' => '18:00:00'], // Quarta-feira
    ['day' => 5, 'start' => '08:00:00', 'end' => '12:00:00'], // Sexta-feira
];

foreach ($testSchedules as $schedule) {
    $scheduleId = $scheduleModel->upsertSchedule(
        $tenantId,
        $professionalId,
        $schedule['day'],
        $schedule['start'],
        $schedule['end'],
        true
    );
    echo "   ✅ Horário criado: Dia {$schedule['day']} ({$schedule['start']} - {$schedule['end']}) - ID: {$scheduleId}\n";
}

// 3. Verifica se os horários foram salvos
echo "\n3. Verificando horários salvos...\n";
$savedSchedules = $scheduleModel->findByProfessional($tenantId, $professionalId);

if (empty($savedSchedules)) {
    echo "❌ Nenhum horário encontrado no banco!\n";
} else {
    echo "✅ Total de horários encontrados: " . count($savedSchedules) . "\n";
    foreach ($savedSchedules as $sched) {
        $dayNames = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        $dayName = $dayNames[$sched['day_of_week']] ?? "Dia {$sched['day_of_week']}";
        echo "   - {$dayName}: {$sched['start_time']} - {$sched['end_time']} (Disponível: " . ($sched['is_available'] ? 'Sim' : 'Não') . ")\n";
    }
}

// 4. Testa busca por dia específico
echo "\n4. Testando busca por dia específico (Segunda-feira = 1)...\n";
$mondaySchedule = $scheduleModel->findByProfessionalAndDay($tenantId, $professionalId, 1);

if ($mondaySchedule) {
    echo "✅ Horário de segunda-feira encontrado: {$mondaySchedule['start_time']} - {$mondaySchedule['end_time']}\n";
} else {
    echo "ℹ️  Nenhum horário específico para segunda-feira\n";
}

// 5. Testa criação de bloqueio
echo "\n5. Testando criação de bloqueio...\n";
$blockModel = new ScheduleBlock();

$blockId = $blockModel->insert([
    'tenant_id' => $tenantId,
    'professional_id' => $professionalId,
    'start_datetime' => date('Y-m-d', strtotime('+7 days')) . ' 10:00:00',
    'end_datetime' => date('Y-m-d', strtotime('+7 days')) . ' 12:00:00',
    'reason' => 'Teste de bloqueio - Almoço'
]);

echo "✅ Bloqueio criado com ID: {$blockId}\n";

// 6. Verifica bloqueios salvos
echo "\n6. Verificando bloqueios salvos...\n";
$blocks = $blockModel->findByProfessional($tenantId, $professionalId);

if (empty($blocks)) {
    echo "❌ Nenhum bloqueio encontrado no banco!\n";
} else {
    echo "✅ Total de bloqueios encontrados: " . count($blocks) . "\n";
    foreach ($blocks as $block) {
        echo "   - {$block['start_datetime']} até {$block['end_datetime']} - Motivo: " . ($block['reason'] ?? 'N/A') . "\n";
    }
}

// 7. Testa verificação de bloqueio em horário específico
echo "\n7. Testando verificação de bloqueio...\n";
$testDateTime = date('Y-m-d', strtotime('+7 days')) . ' 11:00:00';
$hasBlock = $blockModel->hasBlock($tenantId, $professionalId, $testDateTime);

if ($hasBlock) {
    echo "✅ Bloqueio detectado corretamente para {$testDateTime}\n";
} else {
    echo "ℹ️  Nenhum bloqueio detectado para {$testDateTime}\n";
}

// 8. Verifica diretamente no banco
echo "\n8. Verificação direta no banco de dados...\n";
$stmt = $db->prepare("SELECT COUNT(*) as total FROM professional_schedules WHERE tenant_id = :tenant_id AND professional_id = :professional_id");
$stmt->execute(['tenant_id' => $tenantId, 'professional_id' => $professionalId]);
$count = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   - Total de registros em professional_schedules: {$count['total']}\n";

$stmt = $db->prepare("SELECT COUNT(*) as total FROM schedule_blocks WHERE tenant_id = :tenant_id AND professional_id = :professional_id");
$stmt->execute(['tenant_id' => $tenantId, 'professional_id' => $professionalId]);
$count = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   - Total de registros em schedule_blocks: {$count['total']}\n";

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "\nPróximos passos:\n";
echo "1. Acesse http://localhost:8080/clinic/professional-schedule\n";
echo "2. Selecione o profissional ID {$professionalId}\n";
echo "3. Verifique se os horários aparecem na interface\n";
echo "4. Tente salvar uma alteração e verifique se persiste\n";
