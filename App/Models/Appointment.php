<?php

namespace App\Models;

/**
 * Model para gerenciar agendamentos
 */
class Appointment extends BaseModel
{
    protected string $table = 'appointments';
    protected bool $usesSoftDeletes = true; // ✅ Ativa soft deletes

    /**
     * Campos permitidos para ordenação
     */
    protected function getAllowedOrderFields(): array
    {
        return [
            'id',
            'appointment_date',
            'status',
            'type',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Busca agendamento por tenant e ID (proteção IDOR)
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do agendamento
     * @return array|null Agendamento encontrado ou null
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
     * Busca agendamentos por pet
     * 
     * @param int $tenantId ID do tenant
     * @param int $petId ID do pet
     * @return array Lista de agendamentos
     */
    public function findByPet(int $tenantId, int $petId): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'pet_id' => $petId
        ], ['appointment_date' => 'ASC']);
    }

    /**
     * Busca agendamentos por profissional
     * 
     * @param int $tenantId ID do tenant
     * @param int $professionalId ID do profissional
     * @param string|null $date Data específica (Y-m-d) ou null para todas
     * @return array Lista de agendamentos
     */
    public function findByProfessional(int $tenantId, int $professionalId, ?string $date = null): array
    {
        $conditions = [
            'tenant_id' => $tenantId,
            'professional_id' => $professionalId
        ];
        
        if ($date) {
            $conditions['DATE(appointment_date)'] = $date;
        }
        
        return $this->findAll($conditions, ['appointment_date' => 'ASC']);
    }

    /**
     * Verifica se há conflito de horário
     * 
     * @param int $tenantId ID do tenant
     * @param int|null $professionalId ID do profissional (null se não especificado)
     * @param string $appointmentDate Data/hora do agendamento (Y-m-d H:i:s)
     * @param int $durationMinutes Duração em minutos
     * @param int|null $excludeId ID do agendamento a excluir (para atualizações)
     * @return bool True se houver conflito
     */
    public function hasConflict(
        int $tenantId,
        ?int $professionalId,
        string $appointmentDate,
        int $durationMinutes,
        ?int $excludeId = null
    ): bool {
        $startTime = date('Y-m-d H:i:s', strtotime($appointmentDate));
        $endTime = date('Y-m-d H:i:s', strtotime($appointmentDate . " +{$durationMinutes} minutes"));
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE tenant_id = :tenant_id 
                AND professional_id = :professional_id
                AND status IN ('scheduled', 'confirmed')
                AND deleted_at IS NULL
                AND (
                    (appointment_date >= :start_time AND appointment_date < :end_time)
                    OR (
                        DATE_ADD(appointment_date, INTERVAL duration_minutes MINUTE) > :start_time
                        AND appointment_date < :end_time
                    )
                )";
        
        $params = [
            'tenant_id' => $tenantId,
            'professional_id' => $professionalId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (int)($result['total'] ?? 0) > 0;
    }

    /**
     * Busca agendamentos por tenant com paginação
     * 
     * @param int $tenantId ID do tenant
     * @param int $page Número da página
     * @param int $limit Itens por página
     * @param array $filters Filtros (search, status, type, date_from, date_to, pet_id, professional_id, customer_id)
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
        
        if (!empty($filters['type'])) {
            $conditions['type'] = $filters['type'];
        }
        
        if (!empty($filters['pet_id'])) {
            $conditions['pet_id'] = (int)$filters['pet_id'];
        }
        
        if (!empty($filters['professional_id'])) {
            $conditions['professional_id'] = (int)$filters['professional_id'];
        }
        
        if (!empty($filters['customer_id'])) {
            $conditions['customer_id'] = (int)$filters['customer_id'];
        }
        
        // Filtro por data
        if (!empty($filters['date_from'])) {
            $conditions['appointment_date >='] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions['appointment_date <='] = $filters['date_to'];
        }
        
        $orderBy = [];
        if (!empty($filters['sort'])) {
            $allowedFields = $this->getAllowedOrderFields();
            if (in_array($filters['sort'], $allowedFields, true)) {
                $orderBy[$filters['sort']] = $filters['direction'] ?? 'ASC';
            }
        } else {
            $orderBy['appointment_date'] = 'ASC';
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
     * Cria um novo agendamento
     * ✅ VALIDAÇÃO: Valida se pet, customer e professional existem e pertencem ao tenant
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados do agendamento
     * @return int ID do agendamento criado
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
        
        // ✅ Validação: verifica se customer existe e pertence ao tenant
        if (empty($data['customer_id'])) {
            throw new \InvalidArgumentException('customer_id é obrigatório');
        }
        
        $customerModel = new Customer();
        $customer = $customerModel->findByTenantAndId($tenantId, (int)$data['customer_id']);
        if (!$customer) {
            throw new \RuntimeException("Customer com ID {$data['customer_id']} não encontrado ou não pertence ao tenant {$tenantId}");
        }
        
        // ✅ Validação: verifica se customer do pet corresponde ao customer informado
        if ($pet['customer_id'] != $data['customer_id']) {
            throw new \RuntimeException("Pet não pertence ao customer informado");
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
        
        // ✅ Validação: verifica conflito de horário (se professional fornecido)
        if (!empty($data['professional_id']) && !empty($data['appointment_date'])) {
            $durationMinutes = (int)($data['duration_minutes'] ?? 30);
            if ($this->hasConflict(
                $tenantId,
                (int)$data['professional_id'],
                $data['appointment_date'],
                $durationMinutes
            )) {
                throw new \RuntimeException("Já existe um agendamento neste horário para este profissional");
            }
        }
        
        $appointmentData = [
            'tenant_id' => $tenantId,
            'pet_id' => (int)$data['pet_id'],
            'customer_id' => (int)$data['customer_id'],
            'professional_id' => !empty($data['professional_id']) ? (int)$data['professional_id'] : null,
            'appointment_date' => $data['appointment_date'],
            'duration_minutes' => (int)($data['duration_minutes'] ?? 30),
            'status' => $data['status'] ?? 'scheduled',
            'type' => $data['type'] ?? null,
            'notes' => $data['notes'] ?? null,
            'stripe_invoice_id' => $data['stripe_invoice_id'] ?? null
        ];
        
        return $this->insert($appointmentData);
    }

    /**
     * Atualiza um agendamento
     * ✅ VALIDAÇÃO: Valida se agendamento pertence ao tenant
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do agendamento
     * @param array $data Dados para atualizar
     * @return bool Sucesso da operação
     * @throws \RuntimeException Se agendamento não existir ou não pertencer ao tenant
     */
    public function updateAppointment(int $tenantId, int $id, array $data): bool
    {
        // ✅ Validação: verifica se agendamento existe e pertence ao tenant
        $appointment = $this->findByTenantAndId($tenantId, $id);
        if (!$appointment) {
            throw new \RuntimeException("Agendamento com ID {$id} não encontrado ou não pertence ao tenant {$tenantId}");
        }
        
        // ✅ Validação: verifica conflito de horário (se data ou professional alterados)
        if ((isset($data['appointment_date']) || isset($data['professional_id']) || isset($data['duration_minutes']))) {
            $professionalId = $data['professional_id'] ?? $appointment['professional_id'];
            $appointmentDate = $data['appointment_date'] ?? $appointment['appointment_date'];
            $durationMinutes = $data['duration_minutes'] ?? $appointment['duration_minutes'];
            
            if ($professionalId && $this->hasConflict(
                $tenantId,
                (int)$professionalId,
                $appointmentDate,
                (int)$durationMinutes,
                $id
            )) {
                throw new \RuntimeException("Já existe um agendamento neste horário para este profissional");
            }
        }
        
        $updateData = [];
        $allowedFields = ['pet_id', 'customer_id', 'professional_id', 'appointment_date', 'duration_minutes', 'status', 'type', 'notes', 'stripe_invoice_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if (in_array($field, ['pet_id', 'customer_id', 'professional_id', 'duration_minutes'])) {
                    $updateData[$field] = !empty($data[$field]) ? (int)$data[$field] : null;
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

