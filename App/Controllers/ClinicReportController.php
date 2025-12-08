<?php

namespace App\Controllers;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Customer;
use App\Models\Professional;
use App\Utils\ResponseHelper;
use App\Services\StripeService;
use Flight;
use Config;

/**
 * Controller para relatórios da clínica
 */
class ClinicReportController
{
    /**
     * Relatório de consultas por período
     * GET /v1/clinic/reports/appointments
     */
    public function appointmentsReport(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'appointments_report']);
                return;
            }
            
            $query = Flight::request()->query->getData();
            $startDate = $query['start_date'] ?? date('Y-m-01'); // Primeiro dia do mês atual
            $endDate = $query['end_date'] ?? date('Y-m-t'); // Último dia do mês atual
            $status = $query['status'] ?? null;
            $professionalId = $query['professional_id'] ?? null;
            
            $appointmentModel = new Appointment();
            $db = \App\Utils\Database::getInstance();
            
            // Query base
            $sql = "SELECT 
                        a.*,
                        p.name as pet_name,
                        p.species,
                        c.name as customer_name,
                        c.phone as customer_phone,
                        pr.name as professional_name
                    FROM appointments a
                    INNER JOIN pets p ON a.pet_id = p.id
                    INNER JOIN customers c ON a.customer_id = c.id
                    LEFT JOIN professionals pr ON a.professional_id = pr.id
                    WHERE a.tenant_id = :tenant_id
                    AND a.deleted_at IS NULL
                    AND DATE(a.appointment_date) BETWEEN :start_date AND :end_date";
            
            $params = [
                'tenant_id' => $tenantId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
            
            if ($status) {
                $sql .= " AND a.status = :status";
                $params['status'] = $status;
            }
            
            if ($professionalId) {
                $sql .= " AND a.professional_id = :professional_id";
                $params['professional_id'] = $professionalId;
            }
            
            $sql .= " ORDER BY a.appointment_date ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $appointments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Estatísticas
            $stats = [
                'total' => count($appointments),
                'by_status' => [],
                'by_type' => [],
                'by_professional' => []
            ];
            
            foreach ($appointments as $apt) {
                // Por status
                $statusKey = $apt['status'] ?? 'unknown';
                $stats['by_status'][$statusKey] = ($stats['by_status'][$statusKey] ?? 0) + 1;
                
                // Por tipo
                $typeKey = $apt['type'] ?? 'sem_tipo';
                $stats['by_type'][$typeKey] = ($stats['by_type'][$typeKey] ?? 0) + 1;
                
                // Por profissional
                $profKey = $apt['professional_name'] ?? 'Sem profissional';
                $stats['by_professional'][$profKey] = ($stats['by_professional'][$profKey] ?? 0) + 1;
            }
            
            ResponseHelper::sendSuccess([
                'appointments' => $appointments,
                'statistics' => $stats,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao gerar relatório de consultas: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao gerar relatório de consultas',
                'APPOINTMENTS_REPORT_ERROR',
                ['action' => 'appointments_report']
            );
        }
    }
    
    /**
     * Relatório de exames realizados
     * GET /v1/clinic/reports/exams
     */
    public function examsReport(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'exams_report']);
                return;
            }
            
            $query = Flight::request()->query->getData();
            $startDate = $query['start_date'] ?? date('Y-m-01');
            $endDate = $query['end_date'] ?? date('Y-m-t');
            $status = $query['status'] ?? null;
            
            $db = \App\Utils\Database::getInstance();
            
            // Verifica se a tabela exams existe
            $tableCheck = $db->query("SHOW TABLES LIKE 'exams'");
            if ($tableCheck->rowCount() === 0) {
                ResponseHelper::sendSuccess([
                    'exams' => [],
                    'statistics' => [
                        'total' => 0,
                        'by_status' => [],
                        'by_type' => []
                    ],
                    'message' => 'Tabela de exames não existe ainda'
                ]);
                return;
            }
            
            $sql = "SELECT 
                        e.*,
                        p.name as pet_name,
                        p.species,
                        c.name as customer_name,
                        et.name as exam_type_name
                    FROM exams e
                    INNER JOIN pets p ON e.pet_id = p.id
                    INNER JOIN customers c ON e.client_id = c.id
                    LEFT JOIN exam_types et ON e.exam_type_id = et.id
                    WHERE e.tenant_id = :tenant_id
                    AND e.deleted_at IS NULL
                    AND DATE(e.exam_date) BETWEEN :start_date AND :end_date";
            
            $params = [
                'tenant_id' => $tenantId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
            
            if ($status) {
                $sql .= " AND e.status = :status";
                $params['status'] = $status;
            }
            
            $sql .= " ORDER BY e.exam_date DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $exams = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Estatísticas
            $stats = [
                'total' => count($exams),
                'by_status' => [],
                'by_type' => []
            ];
            
            foreach ($exams as $exam) {
                $statusKey = $exam['status'] ?? 'unknown';
                $stats['by_status'][$statusKey] = ($stats['by_status'][$statusKey] ?? 0) + 1;
                
                $typeKey = $exam['exam_type_name'] ?? 'Sem tipo';
                $stats['by_type'][$typeKey] = ($stats['by_type'][$typeKey] ?? 0) + 1;
            }
            
            ResponseHelper::sendSuccess([
                'exams' => $exams,
                'statistics' => $stats,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao gerar relatório de exames: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao gerar relatório de exames',
                'EXAMS_REPORT_ERROR',
                ['action' => 'exams_report']
            );
        }
    }
    
    /**
     * Relatório de vacinações pendentes
     * GET /v1/clinic/reports/vaccinations
     */
    public function vaccinationsReport(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'vaccinations_report']);
                return;
            }
            
            // Vacinações são agendamentos do tipo "vacinação" que estão pendentes
            $appointmentModel = new Appointment();
            $db = \App\Utils\Database::getInstance();
            
            $sql = "SELECT 
                        a.*,
                        p.name as pet_name,
                        p.species,
                        p.birth_date,
                        c.name as customer_name,
                        c.phone as customer_phone,
                        pr.name as professional_name
                    FROM appointments a
                    INNER JOIN pets p ON a.pet_id = p.id
                    INNER JOIN customers c ON a.customer_id = c.id
                    LEFT JOIN professionals pr ON a.professional_id = pr.id
                    WHERE a.tenant_id = :tenant_id
                    AND a.deleted_at IS NULL
                    AND (a.type LIKE '%vacina%' OR a.type LIKE '%vaccination%')
                    AND a.status IN ('scheduled', 'confirmed')
                    AND a.appointment_date >= NOW()
                    ORDER BY a.appointment_date ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['tenant_id' => $tenantId]);
            $vaccinations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            ResponseHelper::sendSuccess([
                'vaccinations' => $vaccinations,
                'total_pending' => count($vaccinations)
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao gerar relatório de vacinações: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao gerar relatório de vacinações',
                'VACCINATIONS_REPORT_ERROR',
                ['action' => 'vaccinations_report']
            );
        }
    }
    
    /**
     * Relatório financeiro da clínica
     * GET /v1/clinic/reports/financial
     */
    public function financialReport(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'financial_report']);
                return;
            }
            
            $query = Flight::request()->query->getData();
            $startDate = $query['start_date'] ?? date('Y-m-01');
            $endDate = $query['end_date'] ?? date('Y-m-t');
            
            $appointmentModel = new Appointment();
            $db = \App\Utils\Database::getInstance();
            
            // Busca agendamentos com invoice no período
            $sql = "SELECT 
                        a.id,
                        a.appointment_date,
                        a.status,
                        a.type,
                        a.stripe_invoice_id,
                        p.name as pet_name,
                        c.name as customer_name
                    FROM appointments a
                    INNER JOIN pets p ON a.pet_id = p.id
                    INNER JOIN customers c ON a.customer_id = c.id
                    WHERE a.tenant_id = :tenant_id
                    AND a.deleted_at IS NULL
                    AND a.stripe_invoice_id IS NOT NULL
                    AND DATE(a.appointment_date) BETWEEN :start_date AND :end_date
                    ORDER BY a.appointment_date DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'tenant_id' => $tenantId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            $appointments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Busca informações dos invoices no Stripe
            $stripeService = new StripeService();
            $financialData = [
                'total_revenue' => 0,
                'paid_revenue' => 0,
                'pending_revenue' => 0,
                'invoices' => [],
                'by_status' => [
                    'paid' => 0,
                    'open' => 0,
                    'draft' => 0,
                    'void' => 0
                ]
            ];
            
            foreach ($appointments as $apt) {
                if (empty($apt['stripe_invoice_id'])) {
                    continue;
                }
                
                try {
                    $invoice = $stripeService->getInvoice($apt['stripe_invoice_id']);
                    
                    $amount = ($invoice->amount_due ?? 0) / 100; // Converte de centavos
                    $status = $invoice->status ?? 'unknown';
                    
                    $financialData['invoices'][] = [
                        'appointment_id' => $apt['id'],
                        'invoice_id' => $invoice->id,
                        'status' => $status,
                        'amount' => $amount,
                        'currency' => strtoupper($invoice->currency ?? 'brl'),
                        'appointment_date' => $apt['appointment_date'],
                        'pet_name' => $apt['pet_name'],
                        'customer_name' => $apt['customer_name'],
                        'type' => $apt['type']
                    ];
                    
                    $financialData['total_revenue'] += $amount;
                    
                    if ($status === 'paid') {
                        $financialData['paid_revenue'] += $amount;
                        $financialData['by_status']['paid']++;
                    } elseif ($status === 'open') {
                        $financialData['pending_revenue'] += $amount;
                        $financialData['by_status']['open']++;
                    } else {
                        $financialData['by_status'][$status] = ($financialData['by_status'][$status] ?? 0) + 1;
                    }
                } catch (\Exception $e) {
                    // Ignora erros ao buscar invoice (pode não existir mais)
                    if (Config::isDevelopment()) {
                        error_log("Erro ao buscar invoice {$apt['stripe_invoice_id']}: " . $e->getMessage());
                    }
                }
            }
            
            ResponseHelper::sendSuccess([
                'financial' => $financialData,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao gerar relatório financeiro: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao gerar relatório financeiro',
                'FINANCIAL_REPORT_ERROR',
                ['action' => 'financial_report']
            );
        }
    }
    
    /**
     * Relatório de pets mais atendidos
     * GET /v1/clinic/reports/top-pets
     */
    public function topPetsReport(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'top_pets_report']);
                return;
            }
            
            $query = Flight::request()->query->getData();
            $startDate = $query['start_date'] ?? date('Y-m-01');
            $endDate = $query['end_date'] ?? date('Y-m-t');
            $limit = (int)($query['limit'] ?? 10);
            
            $appointmentModel = new Appointment();
            $db = \App\Utils\Database::getInstance();
            
            $sql = "SELECT 
                        p.id,
                        p.name as pet_name,
                        p.species,
                        p.breed,
                        p.birth_date,
                        c.name as customer_name,
                        COUNT(a.id) as appointment_count,
                        MAX(a.appointment_date) as last_appointment
                    FROM pets p
                    INNER JOIN customers c ON p.customer_id = c.id
                    INNER JOIN appointments a ON p.id = a.pet_id
                    WHERE a.tenant_id = :tenant_id
                    AND a.deleted_at IS NULL
                    AND p.deleted_at IS NULL
                    AND DATE(a.appointment_date) BETWEEN :start_date AND :end_date
                    GROUP BY p.id, p.name, p.species, p.breed, p.birth_date, c.name
                    ORDER BY appointment_count DESC
                    LIMIT :limit";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'tenant_id' => $tenantId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'limit' => $limit
            ]);
            $topPets = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            ResponseHelper::sendSuccess([
                'top_pets' => $topPets,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'limit' => $limit
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao gerar relatório de pets mais atendidos: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao gerar relatório de pets mais atendidos',
                'TOP_PETS_REPORT_ERROR',
                ['action' => 'top_pets_report']
            );
        }
    }
}

