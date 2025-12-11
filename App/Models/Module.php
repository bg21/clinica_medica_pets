<?php

namespace App\Models;

use App\Utils\Database;

/**
 * Model para gerenciar módulos
 */
class Module
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lista todos os módulos
     */
    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM modules WHERE 1=1";
        $params = [];

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = $filters['is_active'] ? 1 : 0;
        }

        $sql .= " ORDER BY sort_order ASC, name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca módulo por ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM modules WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca módulo por module_id (ex: 'vaccines', 'hospitalization')
     */
    public function findByModuleId(string $moduleId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM modules WHERE module_id = :module_id");
        $stmt->execute(['module_id' => $moduleId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Cria um novo módulo
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO modules (
            module_id, name, description, icon, is_active, sort_order
        ) VALUES (
            :module_id, :name, :description, :icon, :is_active, :sort_order
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'module_id' => $data['module_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza um módulo
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['name', 'description', 'icon', 'is_active', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE modules SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Deleta um módulo (soft delete)
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE modules SET is_active = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

