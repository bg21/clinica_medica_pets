<?php

namespace App\Models;

/**
 * Model para gerenciar configuração de comissão
 */
class CommissionConfig extends BaseModel
{
    protected string $table = 'commission_config';
    protected bool $usesSoftDeletes = false;

    /**
     * Busca configuração por tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array|null Configuração encontrada ou null
     */
    public function findByTenant(int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE tenant_id = :tenant_id 
             LIMIT 1"
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Cria ou atualiza configuração de comissão
     * 
     * @param int $tenantId ID do tenant
     * @param float $percentage Porcentagem de comissão (ex: 5.00 para 5%)
     * @param bool $isActive Se está ativa
     * @return bool Sucesso da operação
     */
    public function upsert(int $tenantId, float $percentage, bool $isActive = true): bool
    {
        $existing = $this->findByTenant($tenantId);
        
        if ($existing) {
            return $this->update($existing['id'], [
                'commission_percentage' => $percentage,
                'is_active' => $isActive
            ]);
        } else {
            $this->insert([
                'tenant_id' => $tenantId,
                'commission_percentage' => $percentage,
                'is_active' => $isActive
            ]);
            return true;
        }
    }

    /**
     * Verifica se comissão está ativa para o tenant
     * 
     * @param int $tenantId ID do tenant
     * @return bool True se está ativa
     */
    public function isActive(int $tenantId): bool
    {
        $config = $this->findByTenant($tenantId);
        return $config && $config['is_active'] && $config['commission_percentage'] > 0;
    }

    /**
     * Retorna porcentagem de comissão do tenant
     * 
     * @param int $tenantId ID do tenant
     * @return float Porcentagem de comissão (0 se não configurado)
     */
    public function getPercentage(int $tenantId): float
    {
        $config = $this->findByTenant($tenantId);
        if ($config && $config['is_active']) {
            return (float)$config['commission_percentage'];
        }
        return 0.0;
    }
}

