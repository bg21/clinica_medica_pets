<?php
/**
 * Script de Teste - Emails de Agendamento
 * 
 * Testa o envio dos 4 tipos de emails de agendamento:
 * - appointment_created
 * - appointment_confirmed
 * - appointment_cancelled
 * - appointment_reminder
 * 
 * Uso: php scripts/test_appointment_emails.php
 */

// Carrega autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Carrega configurações
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Carrega classe Config
require_once __DIR__ . '/../config/config.php';
Config::load();

use App\Services\EmailService;
use App\Services\Logger;

// Email de destino para teste
$testEmail = 'juhcosta23@gmail.com';

echo "========================================\n";
echo "TESTE DE EMAILS DE AGENDAMENTO\n";
echo "========================================\n\n";

echo "Email de destino: {$testEmail}\n";
echo "Ambiente: " . ($_ENV['APP_ENV'] ?? 'development') . "\n";
echo "Driver de email: " . ($_ENV['MAIL_DRIVER'] ?? 'smtp') . "\n\n";

try {
    $emailService = new EmailService(true); // true = debug mode
    
    // Dados de exemplo para os agendamentos
    $appointment = [
        'id' => 999,
        'appointment_date' => '2025-12-10 14:30:00',
        'type' => 'Consulta Veterinária',
        'notes' => 'Primeira consulta do pet. Trazer carteira de vacinação.',
        'status' => 'scheduled'
    ];
    
    $client = [
        'id' => 1,
        'name' => 'João Silva',
        'email' => $testEmail
    ];
    
    $pet = [
        'id' => 1,
        'name' => 'Rex'
    ];
    
    $professional = [
        'id' => 1,
        'name' => 'Dr. Carlos Veterinário'
    ];
    
    $cancelReason = 'Cliente solicitou cancelamento';
    
    echo "========================================\n";
    echo "TESTE 1: Email de Agendamento Criado\n";
    echo "========================================\n";
    $result1 = $emailService->sendAppointmentCreated($appointment, $client, $pet, $professional);
    echo $result1 ? "✅ Email enviado com sucesso!\n\n" : "❌ Falha ao enviar email\n\n";
    
    sleep(2); // Aguarda 2 segundos entre envios
    
    echo "========================================\n";
    echo "TESTE 2: Email de Agendamento Confirmado\n";
    echo "========================================\n";
    $appointment['status'] = 'confirmed';
    $result2 = $emailService->sendAppointmentConfirmed($appointment, $client, $pet, $professional);
    echo $result2 ? "✅ Email enviado com sucesso!\n\n" : "❌ Falha ao enviar email\n\n";
    
    sleep(2);
    
    echo "========================================\n";
    echo "TESTE 3: Email de Agendamento Cancelado\n";
    echo "========================================\n";
    $appointment['status'] = 'cancelled';
    $result3 = $emailService->sendAppointmentCancelled($appointment, $client, $pet, $professional, $cancelReason);
    echo $result3 ? "✅ Email enviado com sucesso!\n\n" : "❌ Falha ao enviar email\n\n";
    
    sleep(2);
    
    echo "========================================\n";
    echo "TESTE 4: Email de Lembrete de Agendamento\n";
    echo "========================================\n";
    $appointment['status'] = 'confirmed';
    $appointment['appointment_date'] = date('Y-m-d H:i:s', strtotime('+1 day')); // Amanhã
    $result4 = $emailService->sendAppointmentReminder($appointment, $client, $pet, $professional);
    echo $result4 ? "✅ Email enviado com sucesso!\n\n" : "❌ Falha ao enviar email\n\n";
    
    echo "========================================\n";
    echo "RESUMO DOS TESTES\n";
    echo "========================================\n";
    echo "1. Agendamento Criado:     " . ($result1 ? "✅" : "❌") . "\n";
    echo "2. Agendamento Confirmado:  " . ($result2 ? "✅" : "❌") . "\n";
    echo "3. Agendamento Cancelado:   " . ($result3 ? "✅" : "❌") . "\n";
    echo "4. Lembrete de Agendamento: " . ($result4 ? "✅" : "❌") . "\n\n";
    
    $totalSuccess = ($result1 ? 1 : 0) + ($result2 ? 1 : 0) + ($result3 ? 1 : 0) + ($result4 ? 1 : 0);
    echo "Total: {$totalSuccess}/4 emails enviados com sucesso\n\n";
    
    if ($_ENV['APP_ENV'] === 'development' && ($_ENV['MAIL_DRIVER'] ?? 'smtp') === 'log') {
        $logFile = dirname(__DIR__) . '/logs/emails-' . date('Y-m-d') . '.log';
        echo "📝 Nota: Em modo de desenvolvimento com driver 'log', os emails foram salvos em:\n";
        echo "   {$logFile}\n\n";
    } else {
        echo "📧 Verifique a caixa de entrada (e spam) do email: {$testEmail}\n\n";
    }
    
    exit(0);
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

