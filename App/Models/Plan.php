<?php

namespace App\Models;

use App\Utils\Database;

/**
 * Model para gerenciar planos
 */
class Plan
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lista todos os planos
     */
    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM plans WHERE 1=1";
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
     * Busca plano por ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM plans WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca plano por plan_id (ex: 'basic', 'professional')
     */
    public function findByPlanId(string $planId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM plans WHERE plan_id = :plan_id");
        $stmt->execute(['plan_id' => $planId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca plano por Stripe price_id
     */
    public function findByStripePriceId(string $priceId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM plans 
            WHERE stripe_price_id_monthly = :price_id 
               OR stripe_price_id_yearly = :price_id
        ");
        $stmt->execute(['price_id' => $priceId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Cria um novo plano
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO plans (
            plan_id, name, description, monthly_price, yearly_price,
            max_users, features, stripe_price_id_monthly, stripe_price_id_yearly,
            is_active, sort_order
        ) VALUES (
            :plan_id, :name, :description, :monthly_price, :yearly_price,
            :max_users, :features, :stripe_price_id_monthly, :stripe_price_id_yearly,
            :is_active, :sort_order
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'plan_id' => $data['plan_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'monthly_price' => $data['monthly_price'] ?? 0,
            'yearly_price' => $data['yearly_price'] ?? 0,
            'max_users' => $data['max_users'] ?? null,
            'features' => json_encode($data['features'] ?? []),
            'stripe_price_id_monthly' => $data['stripe_price_id_monthly'] ?? null,
            'stripe_price_id_yearly' => $data['stripe_price_id_yearly'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza um plano
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = [
            'name', 'description', 'monthly_price', 'yearly_price',
            'max_users', 'features', 'stripe_price_id_monthly', 'stripe_price_id_yearly',
            'is_active', 'sort_order'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'features') {
                    $fields[] = "{$field} = :{$field}";
                    $params[$field] = json_encode($data[$field]);
                } else {
                    $fields[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE plans SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Deleta um plano (soft delete)
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE plans SET is_active = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Obtém módulos de um plano
     */
    public function getModules(int $planId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.* 
            FROM modules m
            INNER JOIN plan_modules pm ON m.id = pm.module_id
            WHERE pm.plan_id = :plan_id
            ORDER BY m.sort_order ASC, m.name ASC
        ");
        $stmt->execute(['plan_id' => $planId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona módulo a um plano
     */
    public function addModule(int $planId, int $moduleId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO plan_modules (plan_id, module_id) 
                VALUES (:plan_id, :module_id)
            ");
            return $stmt->execute([
                'plan_id' => $planId,
                'module_id' => $moduleId
            ]);
        } catch (\PDOException $e) {
            // Ignora erro de duplicata
            if ($e->getCode() == 23000) {
                return true;
            }
            throw $e;
        }
    }

    /**
     * Remove módulo de um plano
     */
    public function removeModule(int $planId, int $moduleId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM plan_modules 
            WHERE plan_id = :plan_id AND module_id = :module_id
        ");
        return $stmt->execute([
            'plan_id' => $planId,
            'module_id' => $moduleId
        ]);
    }

    /**
     * Define módulos de um plano (substitui todos)
     */
    public function setModules(int $planId, array $moduleIds): bool
    {
        $this->db->beginTransaction();
        try {
            // Remove todos os módulos atuais
            $stmt = $this->db->prepare("DELETE FROM plan_modules WHERE plan_id = :plan_id");
            $stmt->execute(['plan_id' => $planId]);

            // Adiciona novos módulos
            if (!empty($moduleIds)) {
                $stmt = $this->db->prepare("
                    INSERT INTO plan_modules (plan_id, module_id) 
                    VALUES (:plan_id, :module_id)
                ");
                
                foreach ($moduleIds as $moduleId) {
                    $stmt->execute([
                        'plan_id' => $planId,
                        'module_id' => $moduleId
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

