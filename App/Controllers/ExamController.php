<?php

namespace App\Controllers;

use App\Models\Exam;
use App\Models\ExamType;
use App\Models\Pet;
use App\Models\Customer;
use App\Models\Professional;
use App\Services\ExamService;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use App\Utils\Validator;
use App\Traits\HasModuleAccess;
use Flight;

/**
 * Controller para gerenciar exames
 */
class ExamController
{
    use HasModuleAccess;
    
    private ExamService $examService;
    private const MODULE_ID = 'exams';

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    /**
     * Cria um novo exame
     * POST /v1/clinic/exams
     */
    public function create(): void
    {
        try {
            PermissionHelper::require('create_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_exam']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'create_exam']);
                return;
            }
            
            // Validação básica
            if (empty($data['pet_id']) || empty($data['client_id']) || empty($data['exam_date'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    [
                        'pet_id' => 'Obrigatório',
                        'client_id' => 'Obrigatório',
                        'exam_date' => 'Obrigatório'
                    ],
                    ['action' => 'create_exam']
                );
                return;
            }
            
            // Extrai price_id e auto_charge se fornecidos
            $priceId = $data['price_id'] ?? null;
            $autoCharge = isset($data['auto_charge']) ? (bool)$data['auto_charge'] : false;
            
            // Remove campos que não são do exame
            unset($data['price_id'], $data['auto_charge']);
            
            // Cria exame com integração de pagamento (se price_id fornecido)
            $result = $this->examService->createExamWithPayment(
                $tenantId,
                $data,
                $priceId,
                $autoCharge
            );
            
            $responseData = $result['exam'];
            if ($result['invoice']) {
                $responseData['invoice'] = $result['invoice'];
            }
            
            ResponseHelper::sendCreated($responseData, 'Exame criado com sucesso');
        } catch (\InvalidArgumentException $e) {
            ResponseHelper::sendValidationError(
                $e->getMessage(),
                [],
                ['action' => 'create_exam']
            );
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'EXAM_CREATE_ERROR',
                ['action' => 'create_exam', 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar exame',
                'EXAM_CREATE_ERROR',
                ['action' => 'create_exam', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista exames do tenant
     * GET /v1/clinic/exams
     */
    public function list(): void
    {
        try {
            PermissionHelper::require('view_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_exams']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $queryParams = Flight::request()->query;
            $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
            $limit = isset($queryParams['limit']) ? min(100, max(1, (int)$queryParams['limit'])) : 20;
            
            $filters = [];
            if (!empty($queryParams['status'])) {
                $filters['status'] = $queryParams['status'];
            }
            if (!empty($queryParams['pet_id'])) {
                $filters['pet_id'] = (int)$queryParams['pet_id'];
            }
            if (!empty($queryParams['professional_id'])) {
                $filters['professional_id'] = (int)$queryParams['professional_id'];
            }
            if (!empty($queryParams['exam_type_id'])) {
                $filters['exam_type_id'] = (int)$queryParams['exam_type_id'];
            }
            if (!empty($queryParams['client_id'])) {
                $filters['client_id'] = (int)$queryParams['client_id'];
            }
            if (!empty($queryParams['date_from'])) {
                $filters['date_from'] = $queryParams['date_from'];
            }
            if (!empty($queryParams['date_to'])) {
                $filters['date_to'] = $queryParams['date_to'];
            }
            if (!empty($queryParams['sort'])) {
                $filters['sort'] = $queryParams['sort'];
                $filters['direction'] = $queryParams['direction'] ?? 'DESC';
            }
            
            $examModel = new Exam();
            $result = $examModel->findByTenant($tenantId, $page, $limit, $filters);

            // Carrega relacionamentos para cada exame
            $exams = [];
            $petModel = new Pet();
            $customerModel = new Customer();
            $examTypeModel = new ExamType();
            $professionalModel = new Professional();
            
            foreach ($result['data'] as $exam) {
                // Carrega pet
                if (!empty($exam['pet_id'])) {
                    $pet = $petModel->findByTenantAndId($tenantId, (int)$exam['pet_id']);
                    if ($pet) {
                        $exam['pet'] = $pet;
                    }
                }
                
                // Carrega client/customer
                if (!empty($exam['client_id'])) {
                    $client = $customerModel->findByTenantAndId($tenantId, (int)$exam['client_id']);
                    if ($client) {
                        $exam['client'] = $client;
                    }
                }
                
                // Carrega exam_type
                if (!empty($exam['exam_type_id'])) {
                    $examType = $examTypeModel->findByTenantAndId($tenantId, (int)$exam['exam_type_id']);
                    if ($examType) {
                        $exam['exam_type'] = $examType;
                    }
                }
                
                // Carrega professional
                if (!empty($exam['professional_id'])) {
                    $professional = $professionalModel->findByTenantAndId($tenantId, (int)$exam['professional_id']);
                    if ($professional) {
                        $exam['professional'] = $professional;
                    }
                }
                
                $exams[] = $exam;
            }

            $responseData = [
                'data' => $exams,
                'meta' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total_pages' => $result['total_pages']
                ]
            ];
            
            ResponseHelper::sendSuccess($responseData['data'], 200, null, $responseData['meta']);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar exames',
                'EXAM_LIST_ERROR',
                ['action' => 'list_exams', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém exame por ID
     * GET /v1/clinic/exams/:id
     */
    public function get(string $id): void
    {
        try {
            PermissionHelper::require('view_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_exam', 'exam_id' => $id]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }

            $examModel = new Exam();
            $exam = $examModel->findByTenantAndId($tenantId, (int)$id);

            if (!$exam) {
                ResponseHelper::sendNotFoundError('Exame', ['action' => 'get_exam', 'exam_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }

            ResponseHelper::sendSuccess($exam);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter exame',
                'EXAM_GET_ERROR',
                ['action' => 'get_exam', 'exam_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Atualiza um exame
     * PUT /v1/clinic/exams/:id
     */
    public function update(string $id): void
    {
        try {
            PermissionHelper::require('update_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_exam', 'exam_id' => $id]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_exam', 'exam_id' => $id]);
                return;
            }
            
            $examModel = new Exam();
            $examModel->updateExam($tenantId, (int)$id, $data);
            $exam = $examModel->findById((int)$id);
            
            ResponseHelper::sendSuccess($exam, 'Exame atualizado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError(
                $e,
                $e->getMessage(),
                'EXAM_UPDATE_ERROR',
                ['action' => 'update_exam', 'exam_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar exame',
                'EXAM_UPDATE_ERROR',
                ['action' => 'update_exam', 'exam_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Deleta um exame (soft delete)
     * DELETE /v1/clinic/exams/:id
     */
    public function delete(string $id): void
    {
        try {
            PermissionHelper::require('delete_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_exam', 'exam_id' => $id]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }

            $examModel = new Exam();
            $exam = $examModel->findByTenantAndId($tenantId, (int)$id);

            if (!$exam) {
                ResponseHelper::sendNotFoundError('Exame', ['action' => 'delete_exam', 'exam_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }
            
            $examModel->delete((int)$id);
            
            ResponseHelper::sendSuccess(null, 'Exame deletado com sucesso');
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao deletar exame',
                'EXAM_DELETE_ERROR',
                ['action' => 'delete_exam', 'exam_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista exames por pet
     * GET /v1/clinic/exams/pet/:pet_id
     */
    public function listByPet(string $petId): void
    {
        try {
            PermissionHelper::require('view_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_exams_by_pet', 'pet_id' => $petId]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            // Verifica se pet existe e pertence ao tenant
            $petModel = new Pet();
            $pet = $petModel->findByTenantAndId($tenantId, (int)$petId);
            
            if (!$pet) {
                ResponseHelper::sendNotFoundError('Pet', ['action' => 'list_exams_by_pet', 'pet_id' => $petId]);
                return;
            }
            
            $examModel = new Exam();
            $exams = $examModel->findByPet($tenantId, (int)$petId);
            
            ResponseHelper::sendSuccess($exams);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar exames do pet',
                'EXAM_LIST_BY_PET_ERROR',
                ['action' => 'list_exams_by_pet', 'pet_id' => $petId, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Lista exames por profissional
     * GET /v1/clinic/exams/professional/:professional_id
     */
    public function listByProfessional(string $professionalId): void
    {
        try {
            PermissionHelper::require('view_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_exams_by_professional', 'professional_id' => $professionalId]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            // Verifica se profissional existe e pertence ao tenant
            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, (int)$professionalId);
            
            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional', ['action' => 'list_exams_by_professional', 'professional_id' => $professionalId]);
                return;
            }
            
            $queryParams = Flight::request()->query;
            $date = $queryParams['date'] ?? null;
            
            $examModel = new Exam();
            $exams = $examModel->findByProfessional($tenantId, (int)$professionalId, $date);
            
            ResponseHelper::sendSuccess($exams);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar exames do profissional',
                'EXAM_LIST_BY_PROFESSIONAL_ERROR',
                ['action' => 'list_exams_by_professional', 'professional_id' => $professionalId, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Processa pagamento de um exame existente
     * POST /v1/clinic/exams/:id/pay
     */
    public function pay(string $id): void
    {
        try {
            PermissionHelper::require('update_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'pay_exam', 'exam_id' => $id]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'pay_exam', 'exam_id' => $id]);
                return;
            }
            
            if (empty($data['price_id'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['price_id' => 'Obrigatório'],
                    ['action' => 'pay_exam', 'exam_id' => $id]
                );
                return;
            }
            
            $autoCharge = isset($data['auto_charge']) ? (bool)$data['auto_charge'] : false;
            
            $result = $this->examService->processExamPayment(
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
                'EXAM_PAY_ERROR',
                ['action' => 'pay_exam', 'exam_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao processar pagamento',
                'EXAM_PAY_ERROR',
                ['action' => 'pay_exam', 'exam_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém invoice de um exame
     * GET /v1/clinic/exams/:id/invoice
     */
    public function getInvoice(string $id): void
    {
        try {
            PermissionHelper::require('view_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_exam_invoice', 'exam_id' => $id]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $invoice = $this->examService->getExamInvoice($tenantId, (int)$id);
            
            if (!$invoice) {
                ResponseHelper::sendNotFoundError('Invoice', ['action' => 'get_exam_invoice', 'exam_id' => $id]);
                return;
            }
            
            ResponseHelper::sendSuccess($invoice);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter invoice do exame',
                'EXAM_INVOICE_GET_ERROR',
                ['action' => 'get_exam_invoice', 'exam_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    // =====================================================================
    // TIPOS DE EXAMES (CRUD)
    // =====================================================================

    /**
     * Lista tipos de exame ativos do tenant
     * GET /v1/clinic/exam-types
     */
    public function listExamTypes(): void
    {
        try {
            PermissionHelper::require('view_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'list_exam_types']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $examTypeModel = new ExamType();
            $examTypes = $examTypeModel->findActiveByTenant($tenantId);
            
            ResponseHelper::sendSuccess($examTypes);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao listar tipos de exame',
                'EXAM_TYPE_LIST_ERROR',
                ['action' => 'list_exam_types', 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Obtém tipo de exame por ID
     * GET /v1/clinic/exam-types/:id
     */
    public function getExamType(string $id): void
    {
        try {
            PermissionHelper::require('view_exams');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_exam_type', 'exam_type_id' => $id]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }

            $examTypeModel = new ExamType();
            $examType = $examTypeModel->findByTenantAndId($tenantId, (int)$id);

            if (!$examType) {
                ResponseHelper::sendNotFoundError('Tipo de exame', ['action' => 'get_exam_type', 'exam_type_id' => $id, 'tenant_id' => $tenantId]);
                return;
            }

            ResponseHelper::sendSuccess($examType);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter tipo de exame',
                'EXAM_TYPE_GET_ERROR',
                ['action' => 'get_exam_type', 'exam_type_id' => $id, 'tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Cria um novo tipo de exame
     * POST /v1/clinic/exam-types
     */
    public function createExamType(): void
    {
        try {
            PermissionHelper::require('create_exam_types');
            
            $tenantId = Flight::get('tenant_id');
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_exam_type']);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'create_exam_type']);
                return;
            }
            
            $errors = Validator::validateExamTypeCreate($data);
            if (!empty($errors)) {
                ResponseHelper::sendValidationError('Dados inválidos', $errors, ['action' => 'create_exam_type']);
                return;
            }
            
            $examTypeModel = new ExamType();
            $examTypeId = $examTypeModel->create($tenantId, $data);
            $examType = $examTypeModel->findById($examTypeId);
            
            ResponseHelper::sendCreated($examType, 'Tipo de exame criado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError($e, $e->getMessage(), 'EXAM_TYPE_CREATE_ERROR', ['action' => 'create_exam_type', 'tenant_id' => $tenantId ?? null]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao criar tipo de exame', 'EXAM_TYPE_CREATE_ERROR', ['action' => 'create_exam_type', 'tenant_id' => $tenantId ?? null]);
        }
    }

    /**
     * Atualiza um tipo de exame
     * PUT /v1/clinic/exam-types/:id
     */
    public function updateExamType(string $id): void
    {
        try {
            PermissionHelper::require('update_exam_types');
            
            $tenantId = Flight::get('tenant_id');
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_exam_type', 'exam_type_id' => $id]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_exam_type', 'exam_type_id' => $id]);
                return;
            }
            
            $errors = Validator::validateExamTypeUpdate($data);
            if (!empty($errors)) {
                ResponseHelper::sendValidationError('Dados inválidos', $errors, ['action' => 'update_exam_type']);
                return;
            }
            
            $examTypeModel = new ExamType();
            $examTypeModel->updateExamType($tenantId, (int)$id, $data);
            $examType = $examTypeModel->findByTenantAndId($tenantId, (int)$id);
            
            ResponseHelper::sendSuccess($examType, 'Tipo de exame atualizado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError($e, $e->getMessage(), 'EXAM_TYPE_UPDATE_ERROR', ['action' => 'update_exam_type', 'exam_type_id' => $id, 'tenant_id' => $tenantId ?? null]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao atualizar tipo de exame', 'EXAM_TYPE_UPDATE_ERROR', ['action' => 'update_exam_type', 'exam_type_id' => $id, 'tenant_id' => $tenantId ?? null]);
        }
    }

    /**
     * Deleta um tipo de exame (soft delete)
     * DELETE /v1/clinic/exam-types/:id
     */
    public function deleteExamType(string $id): void
    {
        try {
            PermissionHelper::require('delete_exam_types');
            
            $tenantId = Flight::get('tenant_id');
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_exam_type', 'exam_type_id' => $id]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }
            
            $examTypeModel = new ExamType();
            $examTypeModel->deleteExamType($tenantId, (int)$id);
            
            ResponseHelper::sendSuccess(null, 'Tipo de exame deletado com sucesso');
        } catch (\RuntimeException $e) {
            ResponseHelper::sendGenericError($e, $e->getMessage(), 'EXAM_TYPE_DELETE_ERROR', ['action' => 'delete_exam_type', 'exam_type_id' => $id, 'tenant_id' => $tenantId ?? null]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao deletar tipo de exame', 'EXAM_TYPE_DELETE_ERROR', ['action' => 'delete_exam_type', 'exam_type_id' => $id, 'tenant_id' => $tenantId ?? null]);
        }
    }

    /**
     * Conta exames agendados por tipo de exame
     * GET /v1/clinic/exam-types/:id/count
     */
    public function countExamsByType(string $id): void
    {
        try {
            PermissionHelper::require('view_exams');

            $tenantId = Flight::get('tenant_id');
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'count_exams_by_type', 'exam_type_id' => $id]);
                return;
            }
            
            // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
            if (!$this->checkModuleAccess()) {
                return;
            }

            $queryParams = Flight::request()->query;
            $status = $queryParams['status'] ?? 'scheduled';

            $examModel = new Exam();
            $count = $examModel->countByExamType($tenantId, (int)$id, $status);

            ResponseHelper::sendSuccess([
                'exam_type_id' => (int)$id,
                'status' => $status,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao contar exames por tipo', 'EXAM_TYPE_COUNT_ERROR', ['action' => 'count_exams_by_type', 'exam_type_id' => $id, 'tenant_id' => $tenantId ?? null]);
        }
    }
}

