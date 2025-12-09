<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\CommissionConfig;
use App\Models\Commission;
use App\Models\User;
use App\Services\Logger;

/**
 * Service para gerenciar orçamentos e calcular comissões
 */
class BudgetService
{
    private Budget $budgetModel;
    private CommissionConfig $commissionConfigModel;
    private Commission $commissionModel;
    private User $userModel;

    public function __construct(
        Budget $budgetModel,
        CommissionConfig $commissionConfigModel,
        Commission $commissionModel,
        User $userModel
    ) {
        $this->budgetModel = $budgetModel;
        $this->commissionConfigModel = $commissionConfigModel;
        $this->commissionModel = $commissionModel;
        $this->userModel = $userModel;
    }

    /**
     * Cria um novo orçamento
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados do orçamento
     * @return array Dados do orçamento criado
     * @throws \RuntimeException Se validações falharem
     */
    public function createBudget(int $tenantId, array $data): array
    {
        // Validações
        if (empty($data['customer_id'])) {
            throw new \RuntimeException("customer_id é obrigatório");
        }

        if (empty($data['created_by_user_id'])) {
            throw new \RuntimeException("created_by_user_id é obrigatório");
        }

        // Valida se usuário existe e pertence ao tenant
        $user = $this->userModel->findById((int)$data['created_by_user_id']);
        if (!$user || $user['tenant_id'] != $tenantId) {
            throw new \RuntimeException("Usuário não encontrado ou não pertence ao tenant");
        }

        // Calcula total se items foram fornecidos
        $totalAmount = 0.00;
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $quantity = (float)($item['quantity'] ?? 1);
                $unitPrice = (float)($item['unit_price'] ?? 0);
                $totalAmount += $quantity * $unitPrice;
            }
        } else {
            $totalAmount = (float)($data['total_amount'] ?? 0);
        }

        // Gera número do orçamento
        $budgetNumber = $this->budgetModel->generateBudgetNumber($tenantId);

        // Prepara dados para inserção
        $budgetData = [
            'tenant_id' => $tenantId,
            'customer_id' => (int)$data['customer_id'],
            'pet_id' => !empty($data['pet_id']) ? (int)$data['pet_id'] : null,
            'created_by_user_id' => (int)$data['created_by_user_id'],
            'budget_number' => $budgetNumber,
            'total_amount' => $totalAmount,
            'status' => $data['status'] ?? 'draft',
            'valid_until' => $data['valid_until'] ?? null,
            'items' => !empty($data['items']) ? json_encode($data['items']) : null,
            'notes' => $data['notes'] ?? null
        ];

        $budgetId = $this->budgetModel->insert($budgetData);
        $budget = $this->budgetModel->findById($budgetId);

        Logger::info("Orçamento criado", [
            'tenant_id' => $tenantId,
            'budget_id' => $budgetId,
            'budget_number' => $budgetNumber,
            'user_id' => $data['created_by_user_id']
        ]);

        return $budget;
    }

    /**
     * Marca orçamento como convertido (fechado) e calcula comissão
     * 
     * @param int $tenantId ID do tenant
     * @param int $budgetId ID do orçamento
     * @param string $invoiceId ID da fatura Stripe (opcional)
     * @return array Dados do orçamento e comissão criada
     * @throws \RuntimeException Se validações falharem
     */
    public function convertBudget(int $tenantId, int $budgetId, ?string $invoiceId = null): array
    {
        // Busca orçamento
        $budget = $this->budgetModel->findByTenantAndId($tenantId, $budgetId);
        if (!$budget) {
            throw new \RuntimeException("Orçamento não encontrado");
        }

        if ($budget['status'] === 'converted') {
            throw new \RuntimeException("Orçamento já foi convertido");
        }

        // Verifica se já existe comissão para este orçamento
        if ($this->commissionModel->existsForBudget($budgetId)) {
            throw new \RuntimeException("Comissão já foi criada para este orçamento");
        }

        // Marca orçamento como convertido
        if ($invoiceId) {
            $this->budgetModel->markAsConverted($budgetId, $invoiceId);
        } else {
            $this->budgetModel->update($budgetId, [
                'status' => 'converted',
                'converted_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Busca orçamento atualizado
        $budget = $this->budgetModel->findById($budgetId);

        // Calcula e cria comissão
        $commission = null;
        if ($this->commissionConfigModel->isActive($tenantId)) {
            $commission = $this->calculateAndCreateCommission($tenantId, $budget);
        }

        Logger::info("Orçamento convertido e comissão calculada", [
            'tenant_id' => $tenantId,
            'budget_id' => $budgetId,
            'invoice_id' => $invoiceId,
            'commission_id' => $commission['id'] ?? null
        ]);

        return [
            'budget' => $budget,
            'commission' => $commission
        ];
    }

    /**
     * Calcula e cria comissão para um orçamento convertido
     * 
     * @param int $tenantId ID do tenant
     * @param array $budget Dados do orçamento
     * @return array|null Dados da comissão criada ou null se não aplicável
     */
    private function calculateAndCreateCommission(int $tenantId, array $budget): ?array
    {
        // Busca porcentagem de comissão
        $percentage = $this->commissionConfigModel->getPercentage($tenantId);
        
        if ($percentage <= 0) {
            return null;
        }

        // Calcula valor da comissão
        $budgetTotal = (float)$budget['total_amount'];
        $commissionAmount = ($budgetTotal * $percentage) / 100;

        // Cria registro de comissão
        $commissionData = [
            'tenant_id' => $tenantId,
            'budget_id' => (int)$budget['id'],
            'user_id' => (int)$budget['created_by_user_id'],
            'budget_total' => $budgetTotal,
            'commission_percentage' => $percentage,
            'commission_amount' => $commissionAmount,
            'status' => 'pending'
        ];

        $commissionId = $this->commissionModel->insert($commissionData);
        $commission = $this->commissionModel->findById($commissionId);

        Logger::info("Comissão criada", [
            'tenant_id' => $tenantId,
            'budget_id' => $budget['id'],
            'user_id' => $budget['created_by_user_id'],
            'commission_amount' => $commissionAmount,
            'percentage' => $percentage
        ]);

        return $commission;
    }

    /**
     * Atualiza orçamento
     * 
     * @param int $tenantId ID do tenant
     * @param int $budgetId ID do orçamento
     * @param array $data Dados para atualizar
     * @return array Dados do orçamento atualizado
     * @throws \RuntimeException Se validações falharem
     */
    public function updateBudget(int $tenantId, int $budgetId, array $data): array
    {
        $budget = $this->budgetModel->findByTenantAndId($tenantId, $budgetId);
        if (!$budget) {
            throw new \RuntimeException("Orçamento não encontrado");
        }

        if ($budget['status'] === 'converted') {
            throw new \RuntimeException("Não é possível atualizar um orçamento já convertido");
        }

        // Recalcula total se items foram atualizados
        if (!empty($data['items']) && is_array($data['items'])) {
            $totalAmount = 0.00;
            foreach ($data['items'] as $item) {
                $quantity = (float)($item['quantity'] ?? 1);
                $unitPrice = (float)($item['unit_price'] ?? 0);
                $totalAmount += $quantity * $unitPrice;
            }
            $data['total_amount'] = $totalAmount;
            $data['items'] = json_encode($data['items']);
        }

        // Campos permitidos para atualização
        $allowedFields = ['status', 'valid_until', 'items', 'total_amount', 'notes', 'pet_id'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return $budget;
        }

        $this->budgetModel->update($budgetId, $updateData);
        return $this->budgetModel->findById($budgetId);
    }
}

