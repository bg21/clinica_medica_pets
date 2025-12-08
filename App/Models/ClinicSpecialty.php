<?php

namespace App\Models;

/**
 * Model para gerenciar especialidades atendidas pela clínica
 */
class ClinicSpecialty extends BaseModel
{
    protected string $table = 'clinic_specialties';
    protected bool $usesSoftDeletes = true;

    /**
     * Campos permitidos para ordenação
     */
    protected function getAllowedOrderFields(): array
    {
        return [
            'id',
            'name',
            'is_active',
            'sort_order',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Busca especialidades ativas por tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Lista de especialidades
     */
    public function findActiveByTenant(int $tenantId): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'is_active' => true
        ], ['sort_order' => 'ASC', 'name' => 'ASC']);
    }

    /**
     * Busca especialidade por tenant e ID (proteção IDOR)
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID da especialidade
     * @return array|null Especialidade encontrada ou null
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
     * Busca especialidade por nome e tenant
     * 
     * @param int $tenantId ID do tenant
     * @param string $name Nome da especialidade
     * @return array|null Especialidade encontrada ou null
     */
    public function findByName(int $tenantId, string $name): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE tenant_id = :tenant_id 
             AND name = :name 
             AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'name' => $name
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
}

