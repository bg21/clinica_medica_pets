<?php

namespace App\Models;

use App\Utils\Database;
use App\Services\Logger;

/**
 * Model para gerenciar administradores do SaaS
 */
class SaasAdmin extends BaseModel
{
    protected string $table = 'saas_admins';

    /**
     * Busca administrador por email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE email = :email"
        );
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Busca administrador por ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Cria hash de senha usando bcrypt
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verifica senha
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Cria novo administrador
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (email, password_hash, name, is_active) 
                VALUES (:email, :password_hash, :name, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'email' => $data['email'],
            'password_hash' => $this->hashPassword($data['password']),
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza administrador
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['email', 'name', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        // Se forneceu nova senha, atualiza
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password_hash = :password_hash";
            $params['password_hash'] = $this->hashPassword($data['password']);
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Atualiza último login
     */
    public function updateLastLogin(int $id, ?string $ipAddress = null): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} 
             SET last_login_at = NOW(), last_login_ip = :ip_address, updated_at = NOW() 
             WHERE id = :id"
        );
        return $stmt->execute([
            'id' => $id,
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Lista todos os administradores
     * Sobrescreve BaseModel::findAll() para incluir campos específicos
     */
    public function findAll(array $conditions = [], array $orderBy = [], ?int $limit = null, int $offset = 0): array
    {
        // Se não há condições e não há orderBy customizado, usa a implementação padrão
        if (empty($conditions) && empty($orderBy)) {
            // Usa ordem padrão: created_at DESC
            $orderBy = ['created_at' => 'DESC'];
        }
        
        // Se há orderBy vazio mas queremos ordem padrão, adiciona
        if (empty($orderBy)) {
            $orderBy = ['created_at' => 'DESC'];
        }
        
        // Chama o método pai com os parâmetros corretos
        $results = parent::findAll($conditions, $orderBy, $limit, $offset);
        
        // Remove password_hash da resposta (segurança)
        return array_map(function($admin) {
            unset($admin['password_hash']);
            return $admin;
        }, $results);
    }

    /**
     * Deleta administrador (soft delete)
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_active = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Verifica se email já existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }
}

