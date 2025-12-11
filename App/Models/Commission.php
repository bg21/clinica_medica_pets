<?php

namespace App\Models;

/**
 * Model para gerenciar comissões pagas
 */
class Commission extends BaseModel
{
    protected string $table = 'commissions';
    protected bool $usesSoftDeletes = false;

    /**
     * Campos permitidos para ordenação
     */
    protected function getAllowedOrderFields(): array
    {
        return [
            'id',
            'commission_amount',
            'status',
            'paid_at',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Busca comissão por tenant e ID (proteção IDOR)
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID da comissão
     * @return array|null Comissão encontrada ou null
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
     * Busca comissões por tenant com paginação
     * 
     * @param int $tenantId ID do tenant
     * @param int $page Número da página
     * @param int $limit Itens por página
     * @param array $filters Filtros (user_id, status, start_date, end_date)
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
        
        // Adiciona filtros simples
        if (!empty($filters['user_id'])) {
            $conditions['user_id'] = (int)$filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $conditions['status'] = $filters['status'];
        }
        
        // ✅ CORREÇÃO: Filtros de data são processados manualmente para compatibilidade SQLite/MySQL
        // O BaseModel não processa operadores em chaves, então construímos a query manualmente
        $hasDateFilters = !empty($filters['start_date']) || !empty($filters['end_date']);
        
        $orderBy = [];
        if (!empty($filters['sort'])) {
            $allowedFields = $this->getAllowedOrderFields();
            if (in_array($filters['sort'], $allowedFields, true)) {
                $orderBy[$filters['sort']] = $filters['direction'] ?? 'DESC';
            }
        } else {
            $orderBy['created_at'] = 'DESC';
        }
        
        // Se há filtros de data, constrói query manualmente
        if ($hasDateFilters) {
            return $this->findByTenantWithDateFilters($tenantId, $page, $limit, $filters, $conditions, $orderBy, $offset);
        }
        
        // Caso contrário, usa métodos do BaseModel
        try {
            $result = $this->findAllWithCount($conditions, $orderBy, $limit, $offset);
        } catch (\Exception $e) {
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
     * Busca comissões com filtros de data (query manual para compatibilidade)
     * 
     * @param int $tenantId ID do tenant
     * @param int $page Número da página
     * @param int $limit Itens por página
     * @param array $filters Filtros incluindo start_date e end_date
     * @param array $conditions Condições já processadas
     * @param array $orderBy Ordenação
     * @param int $offset Offset calculado
     * @return array Dados paginados
     */
    private function findByTenantWithDateFilters(
        int $tenantId,
        int $page,
        int $limit,
        array $filters,
        array $conditions,
        array $orderBy,
        int $offset
    ): array {
        // Constrói WHERE clause
        $whereParts = ['tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];
        
        // Adiciona condições simples
        foreach ($conditions as $key => $value) {
            if ($key !== 'tenant_id') {
                $paramKey = str_replace('.', '_', $key);
                $whereParts[] = "{$key} = :{$paramKey}";
                $params[$paramKey] = $value;
            }
        }
        
        // Adiciona filtros de data
        if (!empty($filters['start_date'])) {
            $whereParts[] = "created_at >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $whereParts[] = "created_at <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        
        $whereClause = implode(' AND ', $whereParts);
        
        // Constrói ORDER BY
        $orderParts = [];
        foreach ($orderBy as $field => $direction) {
            $orderParts[] = "{$field} " . strtoupper($direction);
        }
        $orderClause = !empty($orderParts) ? 'ORDER BY ' . implode(', ', $orderParts) : '';
        
        // Query para contar total
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(":{$key}", $value);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetch(\PDO::FETCH_ASSOC)['total'];
        
        // Query para buscar dados
        $dataSql = "SELECT * FROM {$this->table} WHERE {$whereClause} {$orderClause} LIMIT :limit OFFSET :offset";
        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $dataStmt->bindValue(":{$key}", $value);
        }
        $dataStmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();
        $data = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Marca comissão como paga
     * 
     * @param int $id ID da comissão
     * @param string|null $paymentReference Referência do pagamento
     * @param string|null $notes Observações
     * @return bool Sucesso da operação
     */
    public function markAsPaid(int $id, ?string $paymentReference = null, ?string $notes = null): bool
    {
        $data = [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ];
        
        if ($paymentReference !== null) {
            $data['payment_reference'] = $paymentReference;
        }
        
        if ($notes !== null) {
            $data['notes'] = $notes;
        }
        
        return $this->update($id, $data);
    }

    /**
     * Calcula total de comissões por funcionário
     * 
     * @param int $tenantId ID do tenant
     * @param int $userId ID do funcionário
     * @param string|null $status Status da comissão (opcional)
     * @return array Estatísticas de comissão
     */
    public function getTotalByUser(int $tenantId, int $userId, ?string $status = null): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(commission_amount) as total_amount,
                    SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_amount
                FROM {$this->table}
                WHERE tenant_id = :tenant_id 
                AND user_id = :user_id";
        
        $params = [
            'tenant_id' => $tenantId,
            'user_id' => $userId
        ];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return [
            'total_count' => (int)($result['total_count'] ?? 0),
            'total_amount' => (float)($result['total_amount'] ?? 0),
            'paid_amount' => (float)($result['paid_amount'] ?? 0),
            'pending_amount' => (float)($result['pending_amount'] ?? 0)
        ];
    }

    /**
     * Verifica se já existe comissão para um orçamento
     * 
     * @param int $budgetId ID do orçamento
     * @return bool True se já existe
     */
    public function existsForBudget(int $budgetId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE budget_id = :budget_id"
        );
        $stmt->execute(['budget_id' => $budgetId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (int)($result['count'] ?? 0) > 0;
    }
}

