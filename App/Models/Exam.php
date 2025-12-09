<?php

namespace App\Models;

/**
 * Model para gerenciar exames
 */
class Exam extends BaseModel
{
    protected string $table = 'exams';
    protected bool $usesSoftDeletes = true; // ✅ Ativa soft deletes

    /**
     * Campos permitidos para ordenação
     */
    protected function getAllowedOrderFields(): array
    {
        return [
            'id',
            'exam_date',
            'exam_time',
            'status',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Busca exame por tenant e ID (proteção IDOR)
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do exame
     * @return array|null Exame encontrado ou null
     */
    public function findByTenantAndId(int $tenantId, int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE id = :id 
             AND tenant_id = :tenant_id 
             AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Busca exames por pet
     * 
     * @param int $tenantId ID do tenant
     * @param int $petId ID do pet
     * @return array Lista de exames
     */
    public function findByPet(int $tenantId, int $petId): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'pet_id' => $petId
        ], ['exam_date' => 'DESC', 'exam_time' => 'DESC']);
    }

    /**
     * Busca exames por profissional
     * 
     * @param int $tenantId ID do tenant
     * @param int $professionalId ID do profissional
     * @param string|null $date Data específica (Y-m-d) ou null para todas
     * @return array Lista de exames
     */
    public function findByProfessional(int $tenantId, int $professionalId, ?string $date = null): array
    {
        $conditions = [
            'tenant_id' => $tenantId,
            'professional_id' => $professionalId
        ];
        
        if ($date) {
            $conditions['DATE(exam_date)'] = $date;
        }
        
        return $this->findAll($conditions, ['exam_date' => 'DESC', 'exam_time' => 'DESC']);
    }

    /**
     * Busca exames por status
     * 
     * @param int $tenantId ID do tenant
     * @param string $status Status do exame (pending, scheduled, completed, cancelled)
     * @return array Lista de exames
     */
    public function findByStatus(int $tenantId, string $status): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'status' => $status
        ], ['exam_date' => 'ASC', 'exam_time' => 'ASC']);
    }

    /**
     * Busca exames por tenant com paginação
     * 
     * @param int $tenantId ID do tenant
     * @param int $page Número da página
     * @param int $limit Itens por página
     * @param array $filters Filtros (search, status, pet_id, professional_id, exam_type_id, date_from, date_to)
     * @return array Dados paginados
     */
    public function findByTenant(
        int $tenantId, 
        int $page = 1, 
        int $limit = 20, 
        array $filters = []
    ): array {
        $offset = ($page - 1) * $limit;
        $conditions = ['tenant_id' => $tenantId];
        
        // Adiciona filtros
        if (!empty($filters['status'])) {
            $conditions['status'] = $filters['status'];
        }
        
        if (!empty($filters['pet_id'])) {
            $conditions['pet_id'] = (int)$filters['pet_id'];
        }
        
        if (!empty($filters['professional_id'])) {
            $conditions['professional_id'] = (int)$filters['professional_id'];
        }
        
        if (!empty($filters['exam_type_id'])) {
            $conditions['exam_type_id'] = (int)$filters['exam_type_id'];
        }
        
        if (!empty($filters['client_id'])) {
            $conditions['client_id'] = (int)$filters['client_id'];
        }
        
        // Filtro por data
        if (!empty($filters['date_from'])) {
            $conditions['exam_date >='] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions['exam_date <='] = $filters['date_to'];
        }
        
        $orderBy = [];
        if (!empty($filters['sort'])) {
            $allowedFields = $this->getAllowedOrderFields();
            if (in_array($filters['sort'], $allowedFields, true)) {
                $orderBy[$filters['sort']] = $filters['direction'] ?? 'DESC';
            }
        } else {
            $orderBy['exam_date'] = 'DESC';
            $orderBy['exam_time'] = 'DESC';
        }
        
        // Usa método otimizado com COUNT
        try {
            $result = $this->findAllWithCount($conditions, $orderBy, $limit, $offset);
        } catch (\Exception $e) {
            // Fallback: usa método antigo (2 queries)
            $result = [
                'data' => $this->findAll($conditions, $orderBy, $limit, $offset),
                'total' => $this->count($conditions)
            ];
        }
        
        return [
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($result['total'] / $limit)
        ];
    }

    /**
     * Cria um novo exame
     * ✅ VALIDAÇÃO: Valida se pet, client e professional existem e pertencem ao tenant
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados do exame
     * @return int ID do exame criado
     * @throws \RuntimeException Se validações falharem
     */
    public function create(int $tenantId, array $data): int
    {
        // ✅ Validação: verifica se pet existe e pertence ao tenant
        if (empty($data['pet_id'])) {
            throw new \InvalidArgumentException('pet_id é obrigatório');
        }
        
        $petModel = new Pet();
        $pet = $petModel->findByTenantAndId($tenantId, (int)$data['pet_id']);
        if (!$pet) {
            throw new \RuntimeException("Pet com ID {$data['pet_id']} não encontrado ou não pertence ao tenant {$tenantId}");
        }
        
        // ✅ Validação: verifica se client existe e pertence ao tenant
        if (empty($data['client_id'])) {
            throw new \InvalidArgumentException('client_id é obrigatório');
        }
        
        $customerModel = new Customer();
        $customer = $customerModel->findByTenantAndId($tenantId, (int)$data['client_id']);
        if (!$customer) {
            throw new \RuntimeException("Cliente com ID {$data['client_id']} não encontrado ou não pertence ao tenant {$tenantId}");
        }
        
        // ✅ Validação: verifica se customer do pet corresponde ao client informado
        if ($pet['customer_id'] != $data['client_id']) {
            throw new \RuntimeException("Pet não pertence ao cliente informado");
        }
        
        // ✅ Validação: verifica se professional existe (se fornecido)
        if (!empty($data['professional_id'])) {
            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, (int)$data['professional_id']);
            if (!$professional) {
                throw new \RuntimeException("Profissional com ID {$data['professional_id']} não encontrado ou não pertence ao tenant {$tenantId}");
            }
            
            if ($professional['status'] !== 'active') {
                throw new \RuntimeException("Profissional não está ativo");
            }
        }
        
        // ✅ Validação: verifica se exam_type existe (se fornecido)
        if (!empty($data['exam_type_id'])) {
            $examTypeModel = new ExamType();
            $examType = $examTypeModel->findByTenantAndId($tenantId, (int)$data['exam_type_id']);
            if (!$examType) {
                throw new \RuntimeException("Tipo de exame com ID {$data['exam_type_id']} não encontrado ou não pertence ao tenant {$tenantId}");
            }
        }
        
        if (empty($data['exam_date'])) {
            throw new \InvalidArgumentException('exam_date é obrigatório');
        }
        
        $examData = [
            'tenant_id' => $tenantId,
            'pet_id' => (int)$data['pet_id'],
            'client_id' => (int)$data['client_id'],
            'professional_id' => !empty($data['professional_id']) ? (int)$data['professional_id'] : null,
            'exam_type_id' => !empty($data['exam_type_id']) ? (int)$data['exam_type_id'] : null,
            'exam_date' => $data['exam_date'],
            'exam_time' => $data['exam_time'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null,
            'results' => $data['results'] ?? null,
            'stripe_invoice_id' => $data['stripe_invoice_id'] ?? null
        ];
        
        // Trata metadata se fornecido
        if (!empty($data['metadata'])) {
            $examData['metadata'] = is_array($data['metadata']) 
                ? json_encode($data['metadata']) 
                : $data['metadata'];
        }
        
        return $this->insert($examData);
    }

    /**
     * Atualiza um exame
     * ✅ VALIDAÇÃO: Valida se exame pertence ao tenant
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do exame
     * @param array $data Dados para atualizar
     * @return bool Sucesso da operação
     * @throws \RuntimeException Se exame não existir ou não pertencer ao tenant
     */
    public function updateExam(int $tenantId, int $id, array $data): bool
    {
        // ✅ Validação: verifica se exame existe e pertence ao tenant
        $exam = $this->findByTenantAndId($tenantId, $id);
        if (!$exam) {
            throw new \RuntimeException("Exame com ID {$id} não encontrado ou não pertence ao tenant {$tenantId}");
        }
        
        // ✅ Validação: verifica se professional existe (se alterado)
        if (isset($data['professional_id']) && $data['professional_id'] != $exam['professional_id']) {
            if (!empty($data['professional_id'])) {
                $professionalModel = new Professional();
                $professional = $professionalModel->findByTenantAndId($tenantId, (int)$data['professional_id']);
                if (!$professional) {
                    throw new \RuntimeException("Profissional com ID {$data['professional_id']} não encontrado ou não pertence ao tenant {$tenantId}");
                }
            }
        }
        
        // ✅ Validação: verifica se exam_type existe (se alterado)
        if (isset($data['exam_type_id']) && $data['exam_type_id'] != $exam['exam_type_id']) {
            if (!empty($data['exam_type_id'])) {
                $examTypeModel = new ExamType();
                $examType = $examTypeModel->findByTenantAndId($tenantId, (int)$data['exam_type_id']);
                if (!$examType) {
                    throw new \RuntimeException("Tipo de exame com ID {$data['exam_type_id']} não encontrado ou não pertence ao tenant {$tenantId}");
                }
            }
        }
        
        // ✅ Validação: se status mudou para completed, atualiza completed_at
        if (isset($data['status']) && $data['status'] === 'completed' && $exam['status'] !== 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        
        // ✅ Validação: se status mudou para cancelled, atualiza cancelled_at
        if (isset($data['status']) && $data['status'] === 'cancelled' && $exam['status'] !== 'cancelled') {
            $data['cancelled_at'] = date('Y-m-d H:i:s');
            // Se cancelled_by não foi fornecido, tenta obter do contexto (Flight)
            if (empty($data['cancelled_by'])) {
                $userId = \Flight::get('user_id');
                if ($userId) {
                    $data['cancelled_by'] = (int)$userId;
                }
            }
        }
        
        $updateData = [];
        $allowedFields = [
            'pet_id', 'client_id', 'professional_id', 'exam_type_id',
            'exam_date', 'exam_time', 'status', 'notes', 'results',
            'cancellation_reason', 'cancelled_by', 'cancelled_at', 'completed_at',
            'stripe_invoice_id', 'metadata'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if (in_array($field, ['pet_id', 'client_id', 'professional_id', 'exam_type_id', 'cancelled_by'])) {
                    $updateData[$field] = !empty($data[$field]) ? (int)$data[$field] : null;
                } elseif ($field === 'metadata' && is_array($data[$field])) {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }
        
        if (empty($updateData)) {
            return true; // Nada para atualizar
        }
        
        return $this->update($id, $updateData);
    }
}

