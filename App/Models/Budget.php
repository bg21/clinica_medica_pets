<?php

namespace App\Models;

/**
 * Model para gerenciar orçamentos
 */
class Budget extends BaseModel
{
    protected string $table = 'budgets';
    protected bool $usesSoftDeletes = true;

    /**
     * Campos permitidos para ordenação
     */
    protected function getAllowedOrderFields(): array
    {
        return [
            'id',
            'budget_number',
            'total_amount',
            'status',
            'valid_until',
            'created_at',
            'updated_at',
            'converted_at'
        ];
    }

    /**
     * Gera número único de orçamento
     * 
     * @param int $tenantId ID do tenant
     * @return string Número do orçamento (ex: ORC-2025-001)
     */
    public function generateBudgetNumber(int $tenantId): string
    {
        $year = date('Y');
        $prefix = "ORC-{$year}-";
        
        // Busca o último número do ano
        $stmt = $this->db->prepare(
            "SELECT budget_number FROM {$this->table} 
             WHERE tenant_id = :tenant_id 
             AND budget_number LIKE :prefix
             AND deleted_at IS NULL
             ORDER BY budget_number DESC 
             LIMIT 1"
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'prefix' => "{$prefix}%"
        ]);
        $last = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($last) {
            // Extrai o número do último orçamento
            $lastNumber = (int)substr($last['budget_number'], strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Busca orçamento por tenant e ID (proteção IDOR)
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do orçamento
     * @return array|null Orçamento encontrado ou null
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
     * Busca orçamentos por tenant com paginação
     * 
     * @param int $tenantId ID do tenant
     * @param int $page Número da página
     * @param int $limit Itens por página
     * @param array $filters Filtros (search, status, user_id, customer_id)
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
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $conditions['OR'] = [
                'budget_number LIKE' => "%{$search}%",
                'notes LIKE' => "%{$search}%"
            ];
        }
        
        if (!empty($filters['status'])) {
            $conditions['status'] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $conditions['created_by_user_id'] = (int)$filters['user_id'];
        }
        
        if (!empty($filters['customer_id'])) {
            $conditions['customer_id'] = (int)$filters['customer_id'];
        }
        
        $orderBy = [];
        if (!empty($filters['sort'])) {
            $allowedFields = $this->getAllowedOrderFields();
            if (in_array($filters['sort'], $allowedFields, true)) {
                $orderBy[$filters['sort']] = $filters['direction'] ?? 'DESC';
            }
        } else {
            $orderBy['created_at'] = 'DESC';
        }
        
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
     * Marca orçamento como convertido (fechado)
     * 
     * @param int $id ID do orçamento
     * @param string $invoiceId ID da fatura Stripe
     * @return bool Sucesso da operação
     */
    public function markAsConverted(int $id, string $invoiceId): bool
    {
        return $this->update($id, [
            'status' => 'converted',
            'converted_to_invoice_id' => $invoiceId,
            'converted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Busca orçamentos convertidos por funcionário
     * 
     * @param int $tenantId ID do tenant
     * @param int $userId ID do funcionário
     * @param string $startDate Data inicial (opcional)
     * @param string $endDate Data final (opcional)
     * @return array Lista de orçamentos convertidos
     */
    public function findConvertedByUser(
        int $tenantId, 
        int $userId, 
        ?string $startDate = null, 
        ?string $endDate = null
    ): array {
        $conditions = [
            'tenant_id' => $tenantId,
            'created_by_user_id' => $userId,
            'status' => 'converted'
        ];
        
        if ($startDate) {
            $conditions['converted_at >='] = $startDate;
        }
        
        if ($endDate) {
            $conditions['converted_at <='] = $endDate;
        }
        
        return $this->findAll($conditions, ['converted_at' => 'DESC']);
    }
}

