<?php

namespace App\Models;

/**
 * Model para gerenciar profissionais (veterinários)
 */
class Professional extends BaseModel
{
    protected string $table = 'professionals';
    protected bool $usesSoftDeletes = false; // Não usa soft deletes (usa status)

    /**
     * Campos permitidos para ordenação
     */
    protected function getAllowedOrderFields(): array
    {
        return [
            'id',
            'name',
            'crmv',
            'specialty',
            'status',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Normaliza string removendo acentos para comparação
     * 
     * @param string $str String a normalizar
     * @return string String normalizada
     */
    private function normalizeString(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = str_replace(
            ['á', 'à', 'ã', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'õ', 'ô', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ'],
            ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n'],
            $str
        );
        return $str;
    }

    /**
     * Busca profissional por tenant e ID (proteção IDOR)
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do profissional
     * @return array|null Profissional encontrado ou null
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
     * Busca profissional por CRMV
     * 
     * @param int $tenantId ID do tenant
     * @param string $crmv CRMV do profissional
     * @return array|null Profissional encontrado ou null
     */
    public function findByCrmv(int $tenantId, string $crmv): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE tenant_id = :tenant_id 
             AND crmv = :crmv 
             LIMIT 1"
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'crmv' => $crmv
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Busca profissional por user_id
     * 
     * @param int $tenantId ID do tenant
     * @param int $userId ID do usuário
     * @return array|null Profissional encontrado ou null
     */
    public function findByUserId(int $tenantId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE tenant_id = :tenant_id 
             AND user_id = :user_id 
             LIMIT 1"
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Busca profissionais por tenant com paginação
     * 
     * @param int $tenantId ID do tenant
     * @param int $page Número da página
     * @param int $limit Itens por página
     * @param array $filters Filtros (search, status, specialty)
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
                'crmv LIKE' => "%{$search}%",
                'specialty LIKE' => "%{$search}%"
            ];
        }
        
        if (!empty($filters['status'])) {
            $conditions['status'] = $filters['status'];
        }
        
        if (!empty($filters['specialty'])) {
            $conditions['specialty'] = $filters['specialty'];
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
     * Busca profissionais ativos por tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Lista de profissionais ativos
     */
    public function findActiveByTenant(int $tenantId): array
    {
        return $this->findAll(
            [
                'tenant_id' => $tenantId,
                'status' => 'active'
            ],
            ['name' => 'ASC']
        );
    }

    /**
     * Cria um novo profissional
     * ✅ VALIDAÇÃO: Valida se tenant_id existe e CRMV único
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados do profissional
     * @return int ID do profissional criado
     * @throws \RuntimeException Se tenant não existir ou CRMV duplicado
     */
    public function create(int $tenantId, array $data): int
    {
        // ✅ Validação: verifica se tenant existe
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findById($tenantId);
        if (!$tenant) {
            throw new \RuntimeException("Tenant com ID {$tenantId} não encontrado");
        }
        
        // ✅ Validação: verifica se professional_role_id existe e se é veterinário (CRMV obrigatório)
        if (!empty($data['professional_role_id'])) {
            $roleModel = new \App\Models\ProfessionalRole();
            $roleId = (int)$data['professional_role_id'];
            $role = $roleModel->findByTenantAndId($tenantId, $roleId);
            
            if ($role) {
                $roleName = $role['name'] ?? '';
                $roleNameLower = mb_strtolower($roleName, 'UTF-8');
                
                // Verifica se é veterinário (com ou sem acento, lidando com problemas de encoding)
                // Remove acentos para comparação mais robusta
                $roleNameNormalized = $this->normalizeString($roleNameLower);
                
                // Verifica de múltiplas formas para lidar com problemas de encoding
                $isVeterinario = (
                    strpos($roleNameNormalized, 'veterinario') !== false || 
                    strpos($roleNameLower, 'veterinário') !== false ||
                    strpos($roleNameLower, 'veterinario') !== false ||
                    strpos($roleNameLower, 'veterinã') !== false || // Para encoding incorreto
                    strpos($roleName, 'Veterin') !== false // Case insensitive parcial
                );
                
                // Verifica se CRMV está vazio
                $crmv = isset($data['crmv']) ? trim($data['crmv']) : '';
                $crmvEmpty = empty($crmv);
                
                if ($isVeterinario && $crmvEmpty) {
                    throw new \RuntimeException("CRMV é obrigatório para veterinários. Função selecionada: '{$roleName}' (ID: {$roleId})");
                }
            } else {
                // Role não encontrada - pode ser um problema, mas não bloqueia
                \App\Services\Logger::warning("Professional role não encontrada", [
                    'tenant_id' => $tenantId,
                    'professional_role_id' => $roleId
                ]);
            }
        }
        
        // ✅ Validação: verifica se CRMV já existe (se fornecido)
        if (!empty($data['crmv'])) {
            $existing = $this->findByCrmv($tenantId, $data['crmv']);
            if ($existing) {
                throw new \RuntimeException("CRMV {$data['crmv']} já está cadastrado para este tenant");
            }
        }
        
        // ✅ Validação: verifica se user_id existe (se fornecido)
        if (!empty($data['user_id'])) {
            $userModel = new User();
            $user = $userModel->findById((int)$data['user_id']);
            if (!$user || $user['tenant_id'] != $tenantId) {
                throw new \RuntimeException("User com ID {$data['user_id']} não encontrado ou não pertence ao tenant {$tenantId}");
            }
        }
        
        // Se user_id foi fornecido, busca nome e email do usuário
        $userName = $data['name'] ?? null;
        $userEmail = $data['email'] ?? null;
        
        if (!empty($data['user_id'])) {
            $userModel = new User();
            $user = $userModel->findById((int)$data['user_id']);
            if ($user) {
                // Usa nome e email do usuário se não foram fornecidos
                if (empty($userName)) {
                    $userName = $user['name'] ?? null;
                }
                if (empty($userEmail)) {
                    $userEmail = $user['email'] ?? null;
                }
            }
        }
        
        $professionalData = [
            'tenant_id' => $tenantId,
            'user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            'professional_role_id' => !empty($data['professional_role_id']) ? (int)$data['professional_role_id'] : null,
            'name' => $userName,
            'crmv' => $data['crmv'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'specialty' => $data['specialty'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $userEmail,
            'status' => $data['status'] ?? 'active',
            'default_price_id' => $data['default_price_id'] ?? null
        ];
        
        return $this->insert($professionalData);
    }

    /**
     * Atualiza um profissional
     * ✅ VALIDAÇÃO: Valida se profissional pertence ao tenant
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do profissional
     * @param array $data Dados para atualizar
     * @return bool Sucesso da operação
     * @throws \RuntimeException Se profissional não existir ou não pertencer ao tenant
     */
    public function updateProfessional(int $tenantId, int $id, array $data): bool
    {
        // ✅ Validação: verifica se profissional existe e pertence ao tenant
        $professional = $this->findByTenantAndId($tenantId, $id);
        if (!$professional) {
            throw new \RuntimeException("Profissional com ID {$id} não encontrado ou não pertence ao tenant {$tenantId}");
        }
        
        // ✅ Validação: verifica se CRMV já existe em outro profissional (se alterado)
        if (!empty($data['crmv']) && $data['crmv'] != $professional['crmv']) {
            $existing = $this->findByCrmv($tenantId, $data['crmv']);
            if ($existing && $existing['id'] != $id) {
                throw new \RuntimeException("CRMV {$data['crmv']} já está cadastrado para outro profissional");
            }
        }
        
        // ✅ Validação: verifica se user_id existe (se alterado)
        if (isset($data['user_id']) && $data['user_id'] != $professional['user_id']) {
            if (!empty($data['user_id'])) {
                $userModel = new User();
                $user = $userModel->findById((int)$data['user_id']);
                if (!$user || $user['tenant_id'] != $tenantId) {
                    throw new \RuntimeException("User com ID {$data['user_id']} não encontrado ou não pertence ao tenant {$tenantId}");
                }
            }
        }
        
        $updateData = [];
        $allowedFields = ['name', 'crmv', 'cpf', 'specialty', 'phone', 'email', 'status', 'user_id', 'professional_role_id', 'default_price_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'user_id' || $field === 'professional_role_id') {
                    $updateData[$field] = !empty($data[$field]) ? (int)$data[$field] : null;
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

