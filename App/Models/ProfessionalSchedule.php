<?php

namespace App\Models;

/**
 * Model para gerenciar horários de trabalho dos profissionais
 */
class ProfessionalSchedule extends BaseModel
{
    protected string $table = 'professional_schedules';
    protected bool $usesSoftDeletes = false;

    /**
     * Busca horários de um profissional
     * 
     * @param int $tenantId ID do tenant
     * @param int $professionalId ID do profissional
     * @return array Lista de horários
     */
    public function findByProfessional(int $tenantId, int $professionalId): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'professional_id' => $professionalId,
            'is_available' => true
        ], ['day_of_week' => 'ASC', 'start_time' => 'ASC']);
    }

    /**
     * Busca horário específico por profissional e dia da semana
     * 
     * @param int $tenantId ID do tenant
     * @param int $professionalId ID do profissional
     * @param int $dayOfWeek Dia da semana (0=domingo, 1=segunda, ..., 6=sábado)
     * @return array|null Horário encontrado ou null
     */
    public function findByProfessionalAndDay(int $tenantId, int $professionalId, int $dayOfWeek): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE tenant_id = :tenant_id 
             AND professional_id = :professional_id 
             AND day_of_week = :day_of_week 
             LIMIT 1"
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'professional_id' => $professionalId,
            'day_of_week' => $dayOfWeek
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Busca horários de todos os profissionais de um tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Lista de horários agrupados por profissional
     */
    public function findAllByTenant(int $tenantId): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'is_available' => true
        ], ['professional_id' => 'ASC', 'day_of_week' => 'ASC', 'start_time' => 'ASC']);
    }

    /**
     * Cria ou atualiza horário de um profissional
     * 
     * @param int $tenantId ID do tenant
     * @param int $professionalId ID do profissional
     * @param int $dayOfWeek Dia da semana
     * @param string $startTime Hora de início (HH:MM:SS)
     * @param string $endTime Hora de fim (HH:MM:SS)
     * @param bool $isAvailable Se está disponível
     * @return int ID do horário criado/atualizado
     */
    public function upsertSchedule(
        int $tenantId,
        int $professionalId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        bool $isAvailable = true
    ): int {
        // Verifica se já existe
        $existing = $this->findByProfessionalAndDay($tenantId, $professionalId, $dayOfWeek);
        
        $data = [
            'tenant_id' => $tenantId,
            'professional_id' => $professionalId,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_available' => $isAvailable
        ];
        
        if ($existing) {
            // Atualiza
            $this->update($existing['id'], $data);
            return $existing['id'];
        } else {
            // Cria
            return $this->insert($data);
        }
    }

    /**
     * Remove todos os horários de um profissional
     * 
     * @param int $tenantId ID do tenant
     * @param int $professionalId ID do profissional
     * @return bool Sucesso
     */
    public function deleteByProfessional(int $tenantId, int $professionalId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} 
             WHERE tenant_id = :tenant_id 
             AND professional_id = :professional_id"
        );
        return $stmt->execute([
            'tenant_id' => $tenantId,
            'professional_id' => $professionalId
        ]);
    }
}

