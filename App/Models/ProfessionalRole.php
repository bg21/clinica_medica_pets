<?php

namespace App\Models;

/**
 * Model para gerenciar roles profissionais (funções dos profissionais na clínica)
 */
class ProfessionalRole extends BaseModel
{
    protected string $table = 'professional_roles';
    protected bool $usesSoftDeletes = true;

    /**
     * Busca roles profissionais ativas por tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Lista de roles profissionais
     */
    public function findActiveByTenant(int $tenantId): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'is_active' => true
        ], ['sort_order' => 'ASC', 'name' => 'ASC']);
    }

    /**
     * Busca role profissional por ID e tenant
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID da role
     * @return array|null Role encontrada ou null
     */
    public function findByTenantAndId(int $tenantId, int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE id = :id 
             AND tenant_id = :tenant_id 
             AND is_active = 1
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
}

