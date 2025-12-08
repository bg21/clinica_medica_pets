<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\Tenant;
use App\Services\Logger;
use App\Core\EventDispatcher;
use Flight;

/**
 * Service para lógica de negócio de usuários
 * 
 * Centraliza regras de negócio, validações e operações complexas
 * relacionadas a usuários.
 */
class UserService
{
    public function __construct(
        private UserRepository $repository,
        private Tenant $tenantModel
    ) {}
    
    /**
     * Obtém o EventDispatcher (via Flight ou cria novo)
     * 
     * @return EventDispatcher
     */
    private function getEventDispatcher(): EventDispatcher
    {
        $dispatcher = Flight::get('event_dispatcher');
        if ($dispatcher instanceof EventDispatcher) {
            return $dispatcher;
        }
        
        // Fallback: cria novo dispatcher (não ideal, mas funciona)
        return new EventDispatcher();
    }

    /**
     * Lista usuários do tenant
     * 
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros (role, status, search)
     * @return array Lista de usuários
     */
    public function listUsers(int $tenantId, array $filters = []): array
    {
        $users = $this->repository->findByTenant($tenantId, $filters);
        
        // Remove senha do retorno
        $users = array_map(function($user) {
            unset($user['password_hash']);
            return $user;
        }, $users);
        
        return $users;
    }

    /**
     * Obtém um usuário específico
     * 
     * @param int $tenantId ID do tenant
     * @param int $userId ID do usuário
     * @return array|null Dados do usuário ou null se não encontrado
     */
    public function getUser(int $tenantId, int $userId): ?array
    {
        $user = $this->repository->findByTenantAndId($tenantId, $userId);
        
        if ($user) {
            // Remove senha do retorno
            unset($user['password_hash']);
        }
        
        return $user;
    }

    /**
     * Cria um novo usuário com validações de negócio
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados do usuário
     * @return array Usuário criado
     * @throws \InvalidArgumentException Se validação falhar
     * @throws \RuntimeException Se erro ao criar
     */
    public function createUser(int $tenantId, array $data): array
    {
        // Valida se o tenant existe e está ativo
        $tenant = $this->tenantModel->findById($tenantId);
        if (!$tenant) {
            throw new \RuntimeException('Tenant não encontrado');
        }
        if ($tenant['status'] !== 'active') {
            throw new \RuntimeException('O tenant está inativo. Não é possível criar usuários.');
        }
        
        // Validações de negócio
        $errors = $this->validateUserData($data, $tenantId);
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Dados inválidos: ' . implode(', ', $errors));
        }
        
        // Verifica se o email já existe no tenant
        $existingUser = $this->repository->findByEmailAndTenant($data['email'], $tenantId);
        if ($existingUser) {
            throw new \RuntimeException('Já existe um usuário com este email neste tenant');
        }
        
        // Prepara dados para criação
        $userData = [
            'email' => $data['email'],
            'password' => $data['password'],
            'name' => $data['name'] ?? null,
            'role' => $data['role'] ?? 'viewer'
        ];
        
        // Cria usuário
        try {
            $userId = $this->repository->create($tenantId, $userData);
        } catch (\PDOException $e) {
            // Captura erro de constraint única (race condition)
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                throw new \RuntimeException('Já existe um usuário com este email neste tenant');
            }
            
            // Re-lança exceção se não for constraint única
            throw $e;
        }
        
        // Busca usuário criado
        $user = $this->repository->findById($userId);
        
        if (!$user) {
            throw new \RuntimeException('Usuário criado mas não encontrado');
        }
        
        // Remove senha do retorno
        unset($user['password_hash']);
        
        // Dispara evento
        $this->getEventDispatcher()->dispatch('user.created', [
            'user' => $user,
            'tenant_id' => $tenantId
        ]);
        
        Logger::info("Usuário criado com sucesso", [
            'action' => 'create_user',
            'user_id' => $userId,
            'tenant_id' => $tenantId
        ]);
        
        return $user;
    }

    /**
     * Atualiza um usuário com validações de negócio
     * 
     * @param int $tenantId ID do tenant
     * @param int $userId ID do usuário
     * @param array $data Dados para atualizar
     * @return array Usuário atualizado
     * @throws \InvalidArgumentException Se validação falhar
     * @throws \RuntimeException Se usuário não encontrado ou erro ao atualizar
     */
    public function updateUser(int $tenantId, int $userId, array $data): array
    {
        // Verifica se usuário existe
        $user = $this->repository->findByTenantAndId($tenantId, $userId);
        
        if (!$user) {
            throw new \RuntimeException('Usuário não encontrado');
        }
        
        // Validações de negócio
        $errors = [];
        
        // Se email foi fornecido e mudou, verifica se já existe
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            $existingUser = $this->repository->findByEmailAndTenant($data['email'], $tenantId);
            if ($existingUser && $existingUser['id'] != $userId) {
                $errors['email'] = 'Já existe um usuário com este email neste tenant';
            }
        }
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Dados inválidos: ' . implode(', ', $errors));
        }
        
        // Prepara dados para atualização
        $updateData = [];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['password'])) $updateData['password'] = $data['password'];
        if (isset($data['role'])) $updateData['role'] = $data['role'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        
        if (empty($updateData)) {
            throw new \InvalidArgumentException('Nenhum campo para atualizar');
        }
        
        // Atualiza usuário
        $success = $this->repository->update($userId, $updateData);
        
        if (!$success) {
            throw new \RuntimeException('Falha ao atualizar usuário');
        }
        
        // Busca usuário atualizado
        $updatedUser = $this->repository->findByTenantAndId($tenantId, $userId);
        
        if (!$updatedUser) {
            throw new \RuntimeException('Usuário atualizado mas não encontrado');
        }
        
        // Remove senha do retorno
        unset($updatedUser['password_hash']);
        
        // Dispara evento
        $this->getEventDispatcher()->dispatch('user.updated', [
            'user' => $updatedUser,
            'tenant_id' => $tenantId
        ]);
        
        Logger::info("Usuário atualizado com sucesso", [
            'action' => 'update_user',
            'user_id' => $userId,
            'tenant_id' => $tenantId
        ]);
        
        return $updatedUser;
    }

    /**
     * Deleta um usuário (soft delete)
     * 
     * @param int $tenantId ID do tenant
     * @param int $userId ID do usuário
     * @return bool True se deletado com sucesso
     * @throws \RuntimeException Se usuário não encontrado
     */
    public function deleteUser(int $tenantId, int $userId): bool
    {
        // Verifica se usuário existe
        $user = $this->repository->findByTenantAndId($tenantId, $userId);
        
        if (!$user) {
            throw new \RuntimeException('Usuário não encontrado');
        }
        
        // Deleta usuário
        $success = $this->repository->delete($userId);
        
        if ($success) {
            // Dispara evento
            $this->getEventDispatcher()->dispatch('user.deleted', [
                'user' => $user,
                'tenant_id' => $tenantId
            ]);
            
            Logger::info("Usuário deletado com sucesso", [
                'action' => 'delete_user',
                'user_id' => $userId,
                'tenant_id' => $tenantId
            ]);
        }
        
        return $success;
    }

    /**
     * Atualiza role do usuário
     * 
     * @param int $tenantId ID do tenant
     * @param int $userId ID do usuário
     * @param string $role Nova role (admin, editor, viewer)
     * @return array Usuário atualizado
     * @throws \RuntimeException Se usuário não encontrado ou erro ao atualizar
     */
    public function updateUserRole(int $tenantId, int $userId, string $role): array
    {
        // Verifica se usuário existe
        $user = $this->repository->findByTenantAndId($tenantId, $userId);
        
        if (!$user) {
            throw new \RuntimeException('Usuário não encontrado');
        }
        
        // Valida role
        $validRoles = ['admin', 'editor', 'viewer'];
        if (!in_array($role, $validRoles)) {
            throw new \InvalidArgumentException('Role inválida. Use: ' . implode(', ', $validRoles));
        }
        
        // Atualiza role
        $success = $this->repository->updateRole($userId, $role);
        
        if (!$success) {
            throw new \RuntimeException('Falha ao atualizar role do usuário');
        }
        
        // Busca usuário atualizado
        $updatedUser = $this->repository->findByTenantAndId($tenantId, $userId);
        
        if (!$updatedUser) {
            throw new \RuntimeException('Usuário atualizado mas não encontrado');
        }
        
        // Remove senha do retorno
        unset($updatedUser['password_hash']);
        
        Logger::info("Role do usuário atualizada", [
            'action' => 'update_user_role',
            'user_id' => $userId,
            'new_role' => $role,
            'tenant_id' => $tenantId
        ]);
        
        return $updatedUser;
    }

    /**
     * Valida dados do usuário
     * 
     * @param array $data Dados do usuário
     * @param int $tenantId ID do tenant
     * @return array Array de erros (vazio se válido)
     */
    private function validateUserData(array $data, int $tenantId): array
    {
        $errors = [];
        
        // Validação de email
        if (empty($data['email'])) {
            $errors[] = 'Email é obrigatório';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }
        
        // Validação de senha (apenas para criação)
        if (isset($data['password']) && empty($data['password'])) {
            $errors[] = 'Senha é obrigatória';
        } elseif (isset($data['password']) && strlen($data['password']) < 6) {
            $errors[] = 'Senha deve ter no mínimo 6 caracteres';
        }
        
        // Validação de role
        if (isset($data['role'])) {
            $validRoles = ['admin', 'editor', 'viewer'];
            if (!in_array($data['role'], $validRoles)) {
                $errors[] = 'Role inválida. Use: ' . implode(', ', $validRoles);
            }
        }
        
        return $errors;
    }
}

