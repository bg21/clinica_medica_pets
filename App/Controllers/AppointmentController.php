<?php

namespace App\Controllers;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Customer;
use App\Models\Professional;
use App\Services\AppointmentService;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;

/**
 * Controller para gerenciar agendamentos
 */
class AppointmentController
{
    private AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }
    /**
     * Cria um novo agendamento
     * POST /v1/clinic/appointments
     */
    public function create(): void
    {
        try {
            PermissionHelper::require('create_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_appointment']);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'create_appointment']);
                return;
            }
            
            // Validação básica
            if (empty($data['pet_id']) || empty($data['customer_id']) || empty($data['appointment_date'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    [
                        'pet_id' => 'Obrigatório',
                        'customer_id' => 'Obrigatório',
                        'appointment_date' => 'Obrigatório'
                    ],
                    ['action' => 'create_appointment']
                );
                return;
            }
            
            // Extrai price_id e auto_charge se fornecidos
            $priceId = $data['price_id'] ?? null;
            $autoCharge = isset($data['auto_charge']) ? (bool)$data['auto_charge'] : false;
            
            // Remove campos que não são do agendamento
            unset($data['price_id'], $data['auto_charge']);
            
            // Cria agendamento com integração de pagamento (se price_id fornecido)
            $result = $this->appointmentService->createAppointmentWithPayment(
                $tenantId,
                $data,
                $priceId,
                $autoCharge
            );
            
            $responseData = $result['appointment'];
            if ($result['invoice']) {
                $responseData['invoice'] = $result['invoice'];
            }
            
            ResponseHelper::sendCreated($responseData, 'Agendamento criado com sucesso');
        } catch (\InvalidArgumentException $e) {
            ResponseHelper::sendValidationError(
                $e->getMessage(),
                [],
                ['action' => 'create_appointment']
            );
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'APPOINTMENT_CREATE_ERROR',
                ['action' => 'create_appointment', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar agendamento',
                'APPOINTMENT_CREATE_ERROR',
                ['action' => 'create_appointment', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista agendamentos do tenant
     * GET /v1/clinic/appointments
     */
    public function list(): void
    {
        try {
            PermissionHelper::require('view_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_appointments']);
                return;
            }
            
            $queryParams = Flight::request()->query;
            $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
            $limit = isset($queryParams['limit']) ? min(100, max(1, (int)$queryParams['limit'])) : 20;
            
            $filters = [];
            if (!empty($queryParams['status'])) {
                $filters['status'] = $queryParams['status'];
            }
            if (!empty($queryParams['type'])) {
                $filters['type'] = $queryParams['type'];
            }
            if (!empty($queryParams['pet_id'])) {
                $filters['pet_id'] = (int)$queryParams['pet_id'];
            }
            if (!empty($queryParams['professional_id'])) {
                $filters['professional_id'] = (int)$queryParams['professional_id'];
            }
            if (!empty($queryParams['customer_id'])) {
                $filters['customer_id'] = (int)$queryParams['customer_id'];
            }
            if (!empty($queryParams['date_from'])) {
                $filters['date_from'] = $queryParams['date_from'];
            }
            if (!empty($queryParams['date_to'])) {
                $filters['date_to'] = $queryParams['date_to'];
            }
            if (!empty($queryParams['sort'])) {
                $filters['sort'] = $queryParams['sort'];
                $filters['direction'] = $queryParams['direction'] ?? 'ASC';
            }
            
            $appointmentModel = new Appointment();
            $result = $appointmentModel->findByTenant($tenantId, $page, $limit, $filters);

            $responseData = [
                'appointments' => $result['data'],
                'meta' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total_pages' => $result['total_pages']
                ]
            ];
            
            ResponseHelper::sendSuccess($responseData['appointments'], 200, null, $responseData['meta']);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar agendamentos',
                'APPOINTMENT_LIST_ERROR',
                ['action' => 'list_appointments', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém agendamento por ID
     * GET /v1/clinic/appointments/:id
     */
    public function get(string $id): void
    {
        try {
            PermissionHelper::require('view_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_appointment', 'appointment_id' => $id]);
                return;
            }

            $appointmentModel = new Appointment();
            $appointment = $appointmentModel->findByTenantAndId($tenantId, (int)$id);

            if (!$appointment) {
                ResponseHelper::sendNotFoundError('Agendamento', ['action' => 'get_appointment', 'appointment_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }

            ResponseHelper::sendSuccess($appointment);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter agendamento',
                'APPOINTMENT_GET_ERROR',
                ['action' => 'get_appointment', 'appointment_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Atualiza um agendamento
     * PUT /v1/clinic/appointments/:id
     */
    public function update(string $id): void
    {
        try {
            PermissionHelper::require('update_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_appointment', 'appointment_id' => $id]);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_appointment', 'appointment_id' => $id]);
                return;
            }
            
            $appointmentModel = new Appointment();
            $appointmentModel->updateAppointment($tenantId, (int)$id, $data);
            $appointment = $appointmentModel->findById((int)$id);
            
            ResponseHelper::sendSuccess($appointment, 'Agendamento atualizado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'APPOINTMENT_UPDATE_ERROR',
                ['action' => 'update_appointment', 'appointment_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar agendamento',
                'APPOINTMENT_UPDATE_ERROR',
                ['action' => 'update_appointment', 'appointment_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Deleta um agendamento (soft delete)
     * DELETE /v1/clinic/appointments/:id
     */
    public function delete(string $id): void
    {
        try {
            PermissionHelper::require('delete_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_appointment', 'appointment_id' => $id]);
                return;
            }

            $appointmentModel = new Appointment();
            $appointment = $appointmentModel->findByTenantAndId($tenantId, (int)$id);

            if (!$appointment) {
                ResponseHelper::sendNotFoundError('Agendamento', ['action' => 'delete_appointment', 'appointment_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }
            
            $appointmentModel->delete((int)$id);
            
            ResponseHelper::sendSuccess(null, 'Agendamento deletado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao deletar agendamento',
                'APPOINTMENT_DELETE_ERROR',
                ['action' => 'delete_appointment', 'appointment_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista agendamentos por pet
     * GET /v1/clinic/appointments/pet/:pet_id
     */
    public function listByPet(string $petId): void
    {
        try {
            PermissionHelper::require('view_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_appointments_by_pet', 'pet_id' => $petId]);
                return;
            }
            
            // Verifica se pet existe e pertence ao tenant
            $petModel = new Pet();
            $pet = $petModel->findByTenantAndId($tenantId, (int)$petId);
            
            if (!$pet) {
                ResponseHelper::sendNotFoundError('Pet', ['action' => 'list_appointments_by_pet', 'pet_id' => $petId]);
                return;
            }
            
            $appointmentModel = new Appointment();
            $appointments = $appointmentModel->findByPet($tenantId, (int)$petId);
            
            ResponseHelper::sendSuccess($appointments);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar agendamentos do pet',
                'APPOINTMENT_LIST_BY_PET_ERROR',
                ['action' => 'list_appointments_by_pet', 'pet_id' => $petId, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista agendamentos por profissional
     * GET /v1/clinic/appointments/professional/:professional_id
     */
    public function listByProfessional(string $professionalId): void
    {
        try {
            PermissionHelper::require('view_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_appointments_by_professional', 'professional_id' => $professionalId]);
                return;
            }
            
            // Verifica se profissional existe e pertence ao tenant
            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, (int)$professionalId);
            
            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional', ['action' => 'list_appointments_by_professional', 'professional_id' => $professionalId]);
                return;
            }
            
            $queryParams = Flight::request()->query;
            $date = $queryParams['date'] ?? null;
            
            $appointmentModel = new Appointment();
            $appointments = $appointmentModel->findByProfessional($tenantId, (int)$professionalId, $date);
            
            ResponseHelper::sendSuccess($appointments);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar agendamentos do profissional',
                'APPOINTMENT_LIST_BY_PROFESSIONAL_ERROR',
                ['action' => 'list_appointments_by_professional', 'professional_id' => $professionalId, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Processa pagamento de um agendamento existente
     * POST /v1/clinic/appointments/:id/pay
     */
    public function pay(string $id): void
    {
        try {
            PermissionHelper::require('update_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'pay_appointment', 'appointment_id' => $id]);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'pay_appointment', 'appointment_id' => $id]);
                return;
            }
            
            if (empty($data['price_id'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['price_id' => 'Obrigatório'],
                    ['action' => 'pay_appointment', 'appointment_id' => $id]
                );
                return;
            }
            
            $autoCharge = isset($data['auto_charge']) ? (bool)$data['auto_charge'] : false;
            
            $result = $this->appointmentService->processAppointmentPayment(
                $tenantId,
                (int)$id,
                $data['price_id'],
                $autoCharge
            );
            
            ResponseHelper::sendSuccess($result, 'Pagamento processado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'APPOINTMENT_PAY_ERROR',
                ['action' => 'pay_appointment', 'appointment_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao processar pagamento',
                'APPOINTMENT_PAY_ERROR',
                ['action' => 'pay_appointment', 'appointment_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém invoice de um agendamento
     * GET /v1/clinic/appointments/:id/invoice
     */
    public function getInvoice(string $id): void
    {
        try {
            PermissionHelper::require('view_appointments');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_appointment_invoice', 'appointment_id' => $id]);
                return;
            }
            
            $invoice = $this->appointmentService->getAppointmentInvoice($tenantId, (int)$id);
            
            if (!$invoice) {
                ResponseHelper::sendNotFoundError('Invoice', ['action' => 'get_appointment_invoice', 'appointment_id' => $id]);
                return;
            }
            
            ResponseHelper::sendSuccess($invoice);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter invoice do agendamento',
                'APPOINTMENT_INVOICE_GET_ERROR',
                ['action' => 'get_appointment_invoice', 'appointment_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

