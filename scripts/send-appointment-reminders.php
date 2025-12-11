<?php
/**
 * Script de Cron para Enviar Lembretes de Agendamento
 * 
 * Este script deve ser executado diariamente via cron para enviar
 * lembretes de agendamento 24 horas antes do horário marcado.
 * 
 * Configuração do Cron (executar diariamente às 08:00):
 * 0 8 * * * /usr/bin/php /caminho/para/projeto/scripts/send-appointment-reminders.php
 * 
 * Ou via Windows Task Scheduler:
 * php.exe D:\xampp\htdocs\clinica_medica\scripts\send-appointment-reminders.php
 */

// Carrega autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Carrega configurações
require_once __DIR__ . '/../config/bootstrap.php';

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Professional;
use App\Services\EmailService;
use App\Services\Logger;
use App\Utils\Database;

try {
    Logger::info("=== INÍCIO: Envio de Lembretes de Agendamento ===");
    
    $db = Database::getInstance();
    $emailService = new EmailService();
    $appointmentModel = new Appointment();
    $customerModel = new Customer();
    $petModel = new Pet();
    $professionalModel = new Professional();
    
    // Calcula data/hora de amanhã (24h a partir de agora)
    $now = new \DateTime();
    $tomorrow = clone $now;
    $tomorrow->modify('+1 day');
    
    // Define janela de tempo: amanhã entre 00:00 e 23:59
    $startDate = $tomorrow->format('Y-m-d') . ' 00:00:00';
    $endDate = $tomorrow->format('Y-m-d') . ' 23:59:59';
    
    Logger::info("Buscando agendamentos para amanhã", [
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);
    
    // Busca agendamentos agendados ou confirmados para amanhã
    $sql = "
        SELECT a.*, a.tenant_id
        FROM appointments a
        WHERE a.status IN ('scheduled', 'confirmed')
        AND a.appointment_date >= :start_date
        AND a.appointment_date <= :end_date
        AND a.deleted_at IS NULL
        ORDER BY a.appointment_date ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ]);
    
    $appointments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $totalAppointments = count($appointments);
    
    Logger::info("Agendamentos encontrados para lembrete", [
        'total' => $totalAppointments
    ]);
    
    $sentCount = 0;
    $errorCount = 0;
    
    foreach ($appointments as $appointment) {
        try {
            // Busca dados relacionados
            $customer = $customerModel->findById((int)$appointment['customer_id']);
            $pet = $petModel->findById((int)$appointment['pet_id']);
            $professional = $professionalModel->findById((int)$appointment['professional_id']);
            
            // Verifica se tem email do cliente
            if (!$customer || empty($customer['email'])) {
                Logger::warning("Agendamento sem email do cliente", [
                    'appointment_id' => $appointment['id'],
                    'customer_id' => $appointment['customer_id']
                ]);
                continue;
            }
            
            // Verifica se já foi enviado lembrete (evita duplicatas)
            // Nota: Você pode adicionar um campo `reminder_sent_at` na tabela appointments
            // para rastrear se o lembrete já foi enviado
            
            // Envia lembrete
            $sent = $emailService->sendAppointmentReminder(
                $appointment,
                $customer,
                $pet ?: [],
                $professional ?: []
            );
            
            if ($sent) {
                $sentCount++;
                Logger::info("Lembrete enviado com sucesso", [
                    'appointment_id' => $appointment['id'],
                    'customer_email' => $customer['email'],
                    'appointment_date' => $appointment['appointment_date']
                ]);
                
                // Opcional: Marca como enviado no banco
                // $appointmentModel->update((int)$appointment['id'], [
                //     'reminder_sent_at' => date('Y-m-d H:i:s')
                // ]);
            } else {
                $errorCount++;
                Logger::error("Falha ao enviar lembrete", [
                    'appointment_id' => $appointment['id'],
                    'customer_email' => $customer['email']
                ]);
            }
        } catch (\Exception $e) {
            $errorCount++;
            Logger::error("Erro ao processar lembrete de agendamento", [
                'appointment_id' => $appointment['id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    Logger::info("=== FIM: Envio de Lembretes de Agendamento ===", [
        'total_appointments' => $totalAppointments,
        'sent_count' => $sentCount,
        'error_count' => $errorCount
    ]);
    
    // Retorna código de saída apropriado
    exit($errorCount > 0 ? 1 : 0);
    
} catch (\Exception $e) {
    Logger::error("Erro fatal ao executar script de lembretes", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    exit(1);
}

