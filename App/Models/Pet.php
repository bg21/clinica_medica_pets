<?php

namespace App\Models;

/**
 * Model para gerenciar pets (animais dos tutores)
 */
class Pet extends BaseModel
{
    protected string $table = 'pets';
    protected bool $usesSoftDeletes = true; // ✅ Ativa soft deletes

    /**
     * Campos permitidos para ordenação
     */
    protected function getAllowedOrderFields(): array
    {
        return [
            'id',
            'name',
            'species',
            'breed',
            'birth_date',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Busca pets por tenant e ID (proteção IDOR)
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do pet
     * @return array|null Pet encontrado ou null
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
     * Busca pets por customer (tutor)
     * 
     * @param int $tenantId ID do tenant
     * @param int $customerId ID do customer (tutor)
     * @return array Lista de pets
     */
    public function findByCustomer(int $tenantId, int $customerId): array
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId
        ], ['name' => 'ASC']);
    }

    /**
     * Busca pets por tenant com paginação
     * 
     * @param int $tenantId ID do tenant
     * @param int $page Número da página
     * @param int $limit Itens por página
     * @param array $filters Filtros (search, customer_id, species)
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
                'chip LIKE' => "%{$search}%",
                'species LIKE' => "%{$search}%",
                'breed LIKE' => "%{$search}%"
            ];
        }
        
        if (!empty($filters['customer_id'])) {
            $conditions['customer_id'] = (int)$filters['customer_id'];
        }
        
        if (!empty($filters['species'])) {
            $conditions['species'] = $filters['species'];
        }
        
        $orderBy = [];
        if (!empty($filters['sort'])) {
            $allowedFields = $this->getAllowedOrderFields();
            if (in_array($filters['sort'], $allowedFields, true)) {
                $orderBy[$filters['sort']] = $filters['direction'] ?? 'ASC';
            }
        } else {
            $orderBy['created_at'] = 'DESC';
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
     * Cria um novo pet
     * ✅ VALIDAÇÃO: Valida se customer_id existe e pertence ao tenant
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados do pet
     * @return int ID do pet criado
     * @throws \RuntimeException Se customer não existir ou não pertencer ao tenant
     */
    public function create(int $tenantId, array $data): int
    {
        // ✅ Validação: verifica se customer existe e pertence ao tenant
        if (empty($data['customer_id'])) {
            throw new \InvalidArgumentException('customer_id é obrigatório');
        }
        
        $customerModel = new Customer();
        $customer = $customerModel->findByTenantAndId($tenantId, (int)$data['customer_id']);
        
        if (!$customer) {
            throw new \RuntimeException("Customer com ID {$data['customer_id']} não encontrado ou não pertence ao tenant {$tenantId}");
        }
        
        $petData = [
            'tenant_id' => $tenantId,
            'customer_id' => (int)$data['customer_id'],
            'name' => $data['name'] ?? null,
            'chip' => !empty($data['chip']) ? trim($data['chip']) : null,
            'porte' => $data['porte'] ?? null,
            'species' => $data['species'] ?? null,
            'breed' => $data['breed'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'weight' => isset($data['weight']) ? (float)$data['weight'] : null,
            'color' => $data['color'] ?? null,
            'notes' => $data['notes'] ?? null
        ];
        
        return $this->insert($petData);
    }

    /**
     * Atualiza um pet
     * ✅ VALIDAÇÃO: Valida se pet pertence ao tenant
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do pet
     * @param array $data Dados para atualizar
     * @return bool Sucesso da operação
     * @throws \RuntimeException Se pet não existir ou não pertencer ao tenant
     */
    public function updatePet(int $tenantId, int $id, array $data): bool
    {
        // ✅ Validação: verifica se pet existe e pertence ao tenant
        $pet = $this->findByTenantAndId($tenantId, $id);
        if (!$pet) {
            throw new \RuntimeException("Pet com ID {$id} não encontrado ou não pertence ao tenant {$tenantId}");
        }
        
        // Se customer_id foi alterado, valida novo customer
        if (isset($data['customer_id']) && $data['customer_id'] != $pet['customer_id']) {
            $customerModel = new Customer();
            $customer = $customerModel->findByTenantAndId($tenantId, (int)$data['customer_id']);
            
            if (!$customer) {
                throw new \RuntimeException("Customer com ID {$data['customer_id']} não encontrado ou não pertence ao tenant {$tenantId}");
            }
        }
        
        $updateData = [];
        $allowedFields = ['name', 'chip', 'porte', 'species', 'breed', 'birth_date', 'gender', 'weight', 'color', 'notes', 'customer_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'weight') {
                    $updateData[$field] = (float)$data[$field];
                } elseif ($field === 'customer_id') {
                    $updateData[$field] = (int)$data[$field];
                } elseif ($field === 'chip') {
                    // Trata chip: se vazio, define como null
                    $updateData[$field] = !empty(trim($data[$field])) ? trim($data[$field]) : null;
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
}

