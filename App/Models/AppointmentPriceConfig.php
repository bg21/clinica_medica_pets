<?php

namespace App\Models;

/**
 * Model para gerenciar configurações de preços para agendamentos
 */
class AppointmentPriceConfig extends BaseModel
{
    protected string $table = 'appointment_price_config';
    protected bool $usesSoftDeletes = false;

    /**
     * Campos permitidos para ordenação
     */
    protected function getAllowedOrderFields(): array
    {
        return [
            'id',
            'appointment_type',
            'specialty',
            'professional_id',
            'price_id',
            'is_default',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Campos permitidos para seleção
     */
    protected function getAllowedSelectFields(): array
    {
        return [
            'id',
            'tenant_id',
            'appointment_type',
            'specialty',
            'professional_id',
            'price_id',
            'is_default',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Busca configuração por tenant e ID (proteção IDOR)
     */
    public function findByTenantAndId(int $tenantId, int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE id = :id 
             AND tenant_id = :tenant_id 
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
     * Busca preço sugerido com sistema de prioridade:
     * 1. Profissional específico
     * 2. Especialidade
     * 3. Tipo de consulta
     * 
     * @param int $tenantId ID do tenant
     * @param string|null $appointmentType Tipo de consulta
     * @param string|null $specialty Especialidade
     * @param int|null $professionalId ID do profissional
     * @return string|null ID do preço sugerido ou null
     */
    public function findSuggestedPrice(
        int $tenantId,
        ?string $appointmentType = null,
        ?string $specialty = null,
        ?int $professionalId = null
    ): ?string {
        // Prioridade 1: Profissional específico
        if ($professionalId !== null) {
            $config = $this->findOne([
                'tenant_id' => $tenantId,
                'professional_id' => $professionalId
            ]);
            if ($config && !empty($config['price_id'])) {
                return $config['price_id'];
            }
        }

        // Prioridade 2: Especialidade
        if (!empty($specialty)) {
            $config = $this->findOne([
                'tenant_id' => $tenantId,
                'specialty' => $specialty,
                'professional_id' => null
            ]);
            if ($config && !empty($config['price_id'])) {
                return $config['price_id'];
            }
        }

        // Prioridade 3: Tipo de consulta
        if (!empty($appointmentType)) {
            $config = $this->findOne([
                'tenant_id' => $tenantId,
                'appointment_type' => $appointmentType,
                'specialty' => null,
                'professional_id' => null
            ]);
            if ($config && !empty($config['price_id'])) {
                return $config['price_id'];
            }
        }

        return null;
    }

    /**
     * Busca todas as configurações do tenant
     */
    public function findByTenant(int $tenantId, array $filters = []): array
    {
        $conditions = ['tenant_id' => $tenantId];

        if (!empty($filters['appointment_type'])) {
            $conditions['appointment_type'] = $filters['appointment_type'];
        }

        if (!empty($filters['specialty'])) {
            $conditions['specialty'] = $filters['specialty'];
        }

        if (isset($filters['professional_id'])) {
            $conditions['professional_id'] = $filters['professional_id'];
        }

        $orderBy = ['appointment_type' => 'ASC', 'specialty' => 'ASC', 'professional_id' => 'ASC'];

        return $this->findAll($conditions, $orderBy);
    }

    /**
     * Cria uma nova configuração de preço
     */
    public function create(int $tenantId, array $data): int
    {
        // Valida se tenant existe
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findById($tenantId);
        if (!$tenant) {
            throw new \RuntimeException("Tenant com ID {$tenantId} não encontrado");
        }

        // Valida se professional existe (se fornecido)
        if (!empty($data['professional_id'])) {
            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, (int)$data['professional_id']);
            if (!$professional) {
                throw new \RuntimeException("Profissional com ID {$data['professional_id']} não encontrado");
            }
        }

        $configData = [
            'tenant_id' => $tenantId,
            'appointment_type' => $data['appointment_type'] ?? null,
            'specialty' => $data['specialty'] ?? null,
            'professional_id' => !empty($data['professional_id']) ? (int)$data['professional_id'] : null,
            'price_id' => $data['price_id'],
            'is_default' => isset($data['is_default']) ? (bool)$data['is_default'] : false
        ];

        return $this->insert($configData);
    }

    /**
     * Atualiza uma configuração de preço
     */
    public function updateConfig(int $tenantId, int $id, array $data): bool
    {
        // Valida se configuração existe e pertence ao tenant
        $config = $this->findByTenantAndId($tenantId, $id);
        if (!$config) {
            throw new \RuntimeException("Configuração com ID {$id} não encontrada ou não pertence ao tenant {$tenantId}");
        }

        // Valida se professional existe (se alterado)
        if (isset($data['professional_id']) && $data['professional_id'] != $config['professional_id']) {
            if (!empty($data['professional_id'])) {
                $professionalModel = new Professional();
                $professional = $professionalModel->findByTenantAndId($tenantId, (int)$data['professional_id']);
                if (!$professional) {
                    throw new \RuntimeException("Profissional com ID {$data['professional_id']} não encontrado");
                }
            }
        }

        $updateData = [];
        $allowedFields = ['appointment_type', 'specialty', 'professional_id', 'price_id', 'is_default'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'professional_id') {
                    $updateData[$field] = !empty($data[$field]) ? (int)$data[$field] : null;
                } elseif ($field === 'is_default') {
                    $updateData[$field] = (bool)$data[$field];
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

    /**
     * Busca uma configuração específica
     */
    public function findOne(array $conditions): ?array
    {
        $where = [];
        $params = [];

        foreach ($conditions as $key => $value) {
            if ($value === null) {
                $where[] = "{$key} IS NULL";
            } else {
                $where[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}

