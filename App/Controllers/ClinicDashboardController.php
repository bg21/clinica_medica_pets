<?php

namespace App\Controllers;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Customer;
use App\Models\Professional;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;
use Config;

/**
 * Controller para Dashboard da Clínica
 */
class ClinicDashboardController
{
    private Appointment $appointmentModel;
    private Pet $petModel;
    private Customer $customerModel;
    private Professional $professionalModel;

    public function __construct(
        Appointment $appointmentModel,
        Pet $petModel,
        Customer $customerModel,
        Professional $professionalModel
    ) {
        $this->appointmentModel = $appointmentModel;
        $this->petModel = $petModel;
        $this->customerModel = $customerModel;
        $this->professionalModel = $professionalModel;
    }

    /**
     * Retorna os KPIs do dashboard
     * GET /v1/clinic/dashboard/kpis
     */
    public function getKPIs(): void
    {
        try {
            PermissionHelper::require('view_dashboard');

            $tenantId = Flight::get('tenant_id');
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_dashboard_kpis']);
                return;
            }

            $today = date('Y-m-d');
            $todayStart = $today . ' 00:00:00';
            $todayEnd = $today . ' 23:59:59';

            // Consultas do dia
            $todayAppointments = $this->appointmentModel->findAll([
                'tenant_id' => $tenantId,
                'appointment_date >=' => $todayStart,
                'appointment_date <=' => $todayEnd
            ]);
            $todayAppointmentsCount = count($todayAppointments);

            // Agendamentos pendentes (scheduled + confirmed)
            $db = \App\Utils\Database::getInstance();
            $stmt = $db->prepare(
                "SELECT COUNT(*) as total FROM appointments 
                 WHERE tenant_id = :tenant_id 
                 AND status IN ('scheduled', 'confirmed')
                 AND deleted_at IS NULL"
            );
            $stmt->execute(['tenant_id' => $tenantId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $pendingAppointmentsCount = (int)($result['total'] ?? 0);

            // Pets cadastrados (total)
            $pets = $this->petModel->findAll(['tenant_id' => $tenantId]);
            $petsCount = count($pets);

            // Clientes cadastrados (total)
            $customers = $this->customerModel->findAll(['tenant_id' => $tenantId]);
            $customersCount = count($customers);

            // Profissionais ativos
            $professionals = $this->professionalModel->findAll([
                'tenant_id' => $tenantId,
                'status' => 'active'
            ]);
            $professionalsCount = count($professionals);

            // Consultas concluídas hoje
            $completedToday = $this->appointmentModel->findAll([
                'tenant_id' => $tenantId,
                'status' => 'completed',
                'appointment_date >=' => $todayStart,
                'appointment_date <=' => $todayEnd
            ]);
            $completedTodayCount = count($completedToday);

            ResponseHelper::sendSuccess([
                'consultas_hoje' => $todayAppointmentsCount,
                'agendamentos_pendentes' => $pendingAppointmentsCount,
                'pets_cadastrados' => $petsCount,
                'clientes_cadastrados' => $customersCount,
                'profissionais_ativos' => $professionalsCount,
                'consultas_concluidas_hoje' => $completedTodayCount
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao buscar KPIs do dashboard: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
            }
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar KPIs do dashboard',
                'DASHBOARD_KPIS_ERROR',
                ['tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Retorna estatísticas de consultas por período
     * GET /v1/clinic/dashboard/appointments-stats
     */
    public function getAppointmentsStats(): void
    {
        try {
            PermissionHelper::require('view_dashboard');

            $tenantId = Flight::get('tenant_id');
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_appointments_stats']);
                return;
            }

            $query = Flight::request()->query->getData();
            $days = isset($query['days']) ? (int)$query['days'] : 30;

            $startDate = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
            $endDate = date('Y-m-d 23:59:59');

            // Busca agendamentos no período
            $appointments = $this->appointmentModel->findAll([
                'tenant_id' => $tenantId,
                'appointment_date >=' => $startDate,
                'appointment_date <=' => $endDate
            ], ['appointment_date' => 'ASC']);

            // Agrupa por data
            $statsByDate = [];
            $statsByStatus = [
                'scheduled' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'no_show' => 0
            ];

            foreach ($appointments as $appointment) {
                $date = date('Y-m-d', strtotime($appointment['appointment_date']));
                
                if (!isset($statsByDate[$date])) {
                    $statsByDate[$date] = 0;
                }
                $statsByDate[$date]++;

                $status = $appointment['status'] ?? 'scheduled';
                if (isset($statsByStatus[$status])) {
                    $statsByStatus[$status]++;
                }
            }

            // Formata para arrays ordenados
            $dates = [];
            $counts = [];
            ksort($statsByDate);
            foreach ($statsByDate as $date => $count) {
                $dates[] = $date;
                $counts[] = $count;
            }

            ResponseHelper::sendSuccess([
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $days
                ],
                'by_date' => [
                    'dates' => $dates,
                    'counts' => $counts
                ],
                'by_status' => $statsByStatus,
                'total' => count($appointments)
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao buscar estatísticas de agendamentos: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
            }
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar estatísticas de agendamentos',
                'DASHBOARD_APPOINTMENTS_STATS_ERROR',
                ['tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Retorna próximos agendamentos
     * GET /v1/clinic/dashboard/upcoming-appointments
     */
    public function getUpcomingAppointments(): void
    {
        try {
            PermissionHelper::require('view_dashboard');

            $tenantId = Flight::get('tenant_id');
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_upcoming_appointments']);
                return;
            }

            $query = Flight::request()->query->getData();
            $limit = isset($query['limit']) ? (int)$query['limit'] : 10;

            $now = date('Y-m-d H:i:s');

            // Busca próximos agendamentos (scheduled ou confirmed)
            $db = \App\Utils\Database::getInstance();
            $stmt = $db->prepare(
                "SELECT * FROM appointments 
                 WHERE tenant_id = :tenant_id 
                 AND status IN ('scheduled', 'confirmed')
                 AND appointment_date >= :now
                 AND deleted_at IS NULL
                 ORDER BY appointment_date ASC
                 LIMIT :limit"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'now' => $now,
                'limit' => $limit
            ]);
            $appointments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Enriquece com dados de pet, customer e professional
            $enrichedAppointments = [];
            foreach ($appointments as $appointment) {
                $enriched = $appointment;

                // Busca pet
                if (!empty($appointment['pet_id'])) {
                    $pet = $this->petModel->findById((int)$appointment['pet_id']);
                    $enriched['pet_name'] = $pet['name'] ?? null;
                    $enriched['pet_species'] = $pet['species'] ?? null;
                }

                // Busca customer
                if (!empty($appointment['customer_id'])) {
                    $customer = $this->customerModel->findById((int)$appointment['customer_id']);
                    $enriched['customer_name'] = $customer['name'] ?? null;
                    $enriched['customer_phone'] = $customer['phone'] ?? null;
                }

                // Busca professional
                if (!empty($appointment['professional_id'])) {
                    $professional = $this->professionalModel->findById((int)$appointment['professional_id']);
                    $enriched['professional_name'] = $professional['name'] ?? null;
                }

                $enrichedAppointments[] = $enriched;
            }

            ResponseHelper::sendSuccess([
                'appointments' => $enrichedAppointments,
                'total' => count($enrichedAppointments)
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao buscar próximos agendamentos: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
            }
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao buscar próximos agendamentos',
                'DASHBOARD_UPCOMING_APPOINTMENTS_ERROR',
                ['tenant_id' => $tenantId ?? null]
            );
        }
    }
}

