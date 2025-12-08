<?php

namespace App\Models;

/**
 * Model para gerenciar bloqueios de agenda dos profissionais
 */
class ScheduleBlock extends BaseModel
{
    protected string $table = 'schedule_blocks';
    protected bool $usesSoftDeletes = false;

    /**
     * Busca bloqueios de um profissional em um período
     * 
     * @param int $tenantId ID do tenant
     * @param int $professionalId ID do profissional
     * @param string|null $startDate Data de início (YYYY-MM-DD)
     * @param string|null $endDate Data de fim (YYYY-MM-DD)
     * @return array Lista de bloqueios
     */
    public function findByProfessional(
        int $tenantId,
        int $professionalId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE tenant_id = :tenant_id 
                AND professional_id = :professional_id";
        
        $params = [
            'tenant_id' => $tenantId,
            'professional_id' => $professionalId
        ];
        
        if ($startDate) {
            $sql .= " AND end_datetime >= :start_date";
            $params['start_date'] = $startDate . ' 00:00:00';
        }
        
        if ($endDate) {
            $sql .= " AND start_datetime <= :end_date";
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " ORDER BY start_datetime ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica se há bloqueio em um horário específico
     * 
     * @param int $tenantId ID do tenant
     * @param int $professionalId ID do profissional
     * @param string $datetime Data e hora (YYYY-MM-DD HH:MM:SS)
     * @return bool True se há bloqueio
     */
    public function hasBlock(int $tenantId, int $professionalId, string $datetime): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE tenant_id = :tenant_id 
             AND professional_id = :professional_id 
             AND start_datetime <= :datetime 
             AND end_datetime >= :datetime"
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'professional_id' => $professionalId,
            'datetime' => $datetime
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Busca bloqueios de todos os profissionais de um tenant em um período
     * 
     * @param int $tenantId ID do tenant
     * @param string|null $startDate Data de início
     * @param string|null $endDate Data de fim
     * @return array Lista de bloqueios
     */
    public function findAllByTenant(
        int $tenantId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE tenant_id = :tenant_id";
        
        $params = ['tenant_id' => $tenantId];
        
        if ($startDate) {
            $sql .= " AND end_datetime >= :start_date";
            $params['start_date'] = $startDate . ' 00:00:00';
        }
        
        if ($endDate) {
            $sql .= " AND start_datetime <= :end_date";
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " ORDER BY professional_id ASC, start_datetime ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

