<?php

namespace App\Models;

/**
 * Model para gerenciar tipos de exames
 */
class ExamType extends BaseModel
{
    protected string $table = 'exam_types';
    protected bool $usesSoftDeletes = true; // ✅ Ativa soft deletes

    /**
     * Campos permitidos para ordenação
     */
    protected function getAllowedOrderFields(): array
    {
        return [
            'id',
            'name',
            'category',
            'status',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Busca tipo de exame por tenant e ID (proteção IDOR)
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do tipo de exame
     * @return array|null Tipo de exame encontrado ou null
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
     * Busca tipos de exame ativos por tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Lista de tipos de exame ativos
     */
    public function findActiveByTenant(int $tenantId): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'status' => 'active'
        ], ['name' => 'ASC']);
    }

    /**
     * Busca tipos de exame por tenant com paginação
     * 
     * @param int $tenantId ID do tenant
     * @param int $page Número da página
     * @param int $limit Itens por página
     * @param array $filters Filtros (search, category, status)
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
                'name LIKE' => "%{$search}%",
                'description LIKE' => "%{$search}%"
            ];
        }
        
        if (!empty($filters['category'])) {
            $conditions['category'] = $filters['category'];
        }
        
        if (!empty($filters['status'])) {
            $conditions['status'] = $filters['status'];
        }
        
        $orderBy = [];
        if (!empty($filters['sort'])) {
            $allowedFields = $this->getAllowedOrderFields();
            if (in_array($filters['sort'], $allowedFields, true)) {
                $orderBy[$filters['sort']] = $filters['direction'] ?? 'ASC';
            }
        } else {
            $orderBy['name'] = 'ASC';
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
     * Cria um novo tipo de exame
     * ✅ VALIDAÇÃO: Valida dados obrigatórios
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados do tipo de exame
     * @return int ID do tipo de exame criado
     * @throws \RuntimeException Se validações falharem
     */
    public function create(int $tenantId, array $data): int
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('name é obrigatório');
        }
        
        if (empty($data['category'])) {
            throw new \InvalidArgumentException('category é obrigatório');
        }
        
        $examTypeData = [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active'
        ];
        
        return $this->insert($examTypeData);
    }

    /**
     * Atualiza um tipo de exame
     * ✅ VALIDAÇÃO: Valida se tipo de exame pertence ao tenant
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do tipo de exame
     * @param array $data Dados para atualizar
     * @return bool Sucesso da operação
     * @throws \RuntimeException Se tipo de exame não existir ou não pertencer ao tenant
     */
    public function updateExamType(int $tenantId, int $id, array $data): bool
    {
        // ✅ Validação: verifica se tipo de exame existe e pertence ao tenant
        $examType = $this->findByTenantAndId($tenantId, $id);
        if (!$examType) {
            throw new \RuntimeException("Tipo de exame com ID {$id} não encontrado ou não pertence ao tenant {$tenantId}");
        }
        
        $updateData = [];
        $allowedFields = ['name', 'category', 'description', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return true; // Nada para atualizar
        }
        
        return $this->update($id, $updateData);
    }
}

