<?php

namespace App\Models;

/**
 * Model para gerenciar eventos Stripe (idempotência de webhooks)
 */
class StripeEvent extends BaseModel
{
    protected string $table = 'stripe_events';

    /**
     * Verifica se evento já foi processado
     */
    public function isProcessed(string $eventId): bool
    {
        $event = $this->findBy('event_id', $eventId);
        return $event && $event['processed'] == 1;
    }

    /**
     * Marca evento como processado
     */
    public function markAsProcessed(string $eventId, string $eventType, array $data): int
    {
        $existing = $this->findBy('event_id', $eventId);

        $eventData = [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'processed' => true,
            'data' => json_encode($data)
        ];

        if ($existing) {
            $this->update($existing['id'], $eventData);
            return $existing['id'];
        }

        return $this->insert($eventData);
    }

    /**
     * Registra evento (antes de processar)
     */
    public function register(string $eventId, string $eventType, array $data): int
    {
        $existing = $this->findBy('event_id', $eventId);

        if ($existing) {
            return $existing['id'];
        }

        return $this->insert([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'processed' => false,
            'data' => json_encode($data)
        ]);
    }

    /**
     * Busca eventos falhados (não processados) nas últimas N horas
     * 
     * @param int $hours Número de horas para buscar (padrão: 24)
     * @return array Lista de eventos falhados
     */
    public function findFailedEvents(int $hours = 24): array
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $sql = "
            SELECT 
                id,
                event_id,
                event_type,
                processed,
                created_at,
                data
            FROM {$this->table}
            WHERE (processed = 0 OR processed IS NULL)
                AND created_at >= :date_from
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date_from' => $dateFrom]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Conta eventos falhados por tipo nas últimas N horas
     * 
     * @param int $hours Número de horas para buscar (padrão: 24)
     * @return array Contagem de eventos falhados por tipo
     */
    public function countFailedEventsByType(int $hours = 24): array
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $sql = "
            SELECT 
                event_type,
                COUNT(*) as failure_count
            FROM {$this->table}
            WHERE (processed = 0 OR processed IS NULL)
                AND created_at >= :date_from
            GROUP BY event_type
            ORDER BY failure_count DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date_from' => $dateFrom]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

