<?php

namespace App\Repositories;

use App\Models\User;
use App\Traits\CacheableRepository;

/**
 * Repository para abstrair acesso a dados de usuários
 * 
 * ✅ IMPLEMENTAÇÃO: Repository Pattern para User
 * ✅ NOVO: Implementa cache automático para melhor performance
 */
class UserRepository
{
    use CacheableRepository;

    private User $model;
    
    // ✅ NOVO: Configuração de cache
    protected string $cachePrefix = 'user';
    protected int $defaultCacheTtl = 300; // 5 minutos

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Busca usuário por tenant e ID
     * 
     * ✅ NOVO: Implementa cache automático
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do usuário
     * @return array|null
     */
    public function findByTenantAndId(int $tenantId, int $id): ?array
    {
        // Tenta buscar do cache primeiro
        $cacheKey = $this->buildCacheKeyByTenantAndId($tenantId, $id);
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Busca do banco de dados
        $user = $this->model->findById($id);
        $result = $user && $user['tenant_id'] == $tenantId ? $user : null;
        
        // Armazena no cache se encontrado
        if ($result !== null) {
            $this->setCache($cacheKey, $result);
        }
        
        return $result;
    }

    /**
     * Busca usuários por tenant
     * 
     * ✅ NOVO: Implementa cache automático com suporte a filtros
     * 
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros adicionais (ex: ['role' => 'admin', 'status' => 'active'])
     * @return array
     */
    public function findByTenant(int $tenantId, array $filters = []): array
    {
        // Tenta buscar do cache primeiro
        $cacheKey = $this->buildCacheKeyForList($tenantId, $filters);
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Busca do banco de dados
        $conditions = array_merge(['tenant_id' => $tenantId], $filters);
        $result = $this->model->findAll($conditions);
        
        // Armazena no cache
        $this->setCache($cacheKey, $result);
        
        return $result;
    }

    /**
     * Busca usuário por email e tenant
     * 
     * @param string $email Email do usuário
     * @param int $tenantId ID do tenant
     * @return array|null
     */
    public function findByEmailAndTenant(string $email, int $tenantId): ?array
    {
        return $this->model->findByEmailAndTenant($email, $tenantId);
    }

    /**
     * Verifica se email já existe no tenant
     * 
     * @param string $email Email a verificar
     * @param int $tenantId ID do tenant
     * @return bool
     */
    public function emailExists(string $email, int $tenantId): bool
    {
        return $this->model->emailExists($email, $tenantId);
    }

    /**
     * Busca usuário por ID
     * 
     * ✅ NOVO: Implementa cache automático
     * 
     * @param int $id ID do usuário
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        // Tenta buscar do cache primeiro
        $cacheKey = $this->buildCacheKeyById($id);
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Busca do banco de dados
        $result = $this->model->findById($id);
        
        // Armazena no cache se encontrado
        if ($result !== null) {
            $this->setCache($cacheKey, $result);
        }
        
        return $result;
    }

    /**
     * Cria um novo usuário
     * 
     * ✅ NOVO: Invalida cache automaticamente
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados do usuário
     * @return int ID do usuário criado
     */
    public function create(int $tenantId, array $data): int
    {
        // Se senha foi fornecida, faz hash
        if (isset($data['password'])) {
            $data['password_hash'] = $this->model->hashPassword($data['password']);
            unset($data['password']);
        }

        // Garante tenant_id
        $data['tenant_id'] = $tenantId;

        // Valores padrão
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }
        if (!isset($data['role'])) {
            $data['role'] = 'viewer';
        }

        $id = $this->model->insert($data);
        
        // Invalida cache de listagem do tenant
        $this->invalidateListCache($tenantId);
        
        return $id;
    }

    /**
     * Atualiza usuário
     * 
     * ✅ NOVO: Invalida cache automaticamente
     * 
     * @param int $id ID do usuário
     * @param array $data Dados para atualizar
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        // Se senha foi fornecida, faz hash
        if (isset($data['password'])) {
            $data['password_hash'] = $this->model->hashPassword($data['password']);
            unset($data['password']);
        }

        $result = $this->model->update($id, $data);
        
        if ($result) {
            // Invalida cache do registro
            $this->invalidateRecordCache($id);
            
            // Invalida cache de listagem (busca tenant_id do registro)
            $user = $this->model->findById($id);
            if ($user && isset($user['tenant_id'])) {
                $this->invalidateListCache($user['tenant_id']);
            }
        }
        
        return $result;
    }

    /**
     * Deleta usuário (soft delete se aplicável)
     * 
     * ✅ NOVO: Invalida cache automaticamente
     * 
     * @param int $id ID do usuário
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Busca tenant_id antes de deletar para invalidar cache
        $user = $this->model->findById($id);
        $tenantId = $user['tenant_id'] ?? null;
        
        $result = $this->model->delete($id);
        
        if ($result) {
            // Invalida cache do registro
            $this->invalidateRecordCache($id);
            
            // Invalida cache de listagem se tenant_id foi encontrado
            if ($tenantId !== null) {
                $this->invalidateListCache($tenantId);
            }
        }
        
        return $result;
    }

    /**
     * Atualiza role do usuário
     * 
     * @param int $userId ID do usuário
     * @param string $role Nova role (admin, editor, viewer)
     * @return bool
     */
    public function updateRole(int $userId, string $role): bool
    {
        return $this->model->updateRole($userId, $role);
    }

    /**
     * Verifica se usuário é admin
     * 
     * @param int $userId ID do usuário
     * @return bool
     */
    public function isAdmin(int $userId): bool
    {
        return $this->model->isAdmin($userId);
    }

    /**
     * Verifica senha do usuário
     * 
     * @param string $password Senha em texto plano
     * @param string $hash Hash da senha
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return $this->model->verifyPassword($password, $hash);
    }

    /**
     * Cria hash de senha
     * 
     * @param string $password Senha em texto plano
     * @return string Hash da senha
     */
    public function hashPassword(string $password): string
    {
        return $this->model->hashPassword($password);
    }
}

