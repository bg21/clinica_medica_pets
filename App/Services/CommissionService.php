<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionConfig;
use App\Services\Logger;
use PDO;

/**
 * Service para gerenciar comissões
 */
class CommissionService
{
    private Commission $commissionModel;
    private CommissionConfig $commissionConfigModel;

    public function __construct(
        Commission $commissionModel,
        CommissionConfig $commissionConfigModel
    ) {
        $this->commissionModel = $commissionModel;
        $this->commissionConfigModel = $commissionConfigModel;
    }

    /**
     * Marca comissão como paga
     * 
     * @param int $tenantId ID do tenant
     * @param int $commissionId ID da comissão
     * @param string|null $paymentReference Referência do pagamento
     * @param string|null $notes Observações
     * @return array Dados da comissão atualizada
     * @throws \RuntimeException Se validações falharem
     */
    public function markAsPaid(
        int $tenantId, 
        int $commissionId, 
        ?string $paymentReference = null, 
        ?string $notes = null
    ): array {
        $commission = $this->commissionModel->findByTenantAndId($tenantId, $commissionId);
        if (!$commission) {
            throw new \RuntimeException("Comissão não encontrada");
        }

        if ($commission['status'] === 'paid') {
            throw new \RuntimeException("Comissão já foi marcada como paga");
        }

        $this->commissionModel->markAsPaid($commissionId, $paymentReference, $notes);
        
        Logger::info("Comissão marcada como paga", [
            'tenant_id' => $tenantId,
            'commission_id' => $commissionId,
            'user_id' => $commission['user_id']
        ]);

        return $this->commissionModel->findById($commissionId);
    }

    /**
     * Atualiza configuração de comissão do tenant
     * 
     * @param int $tenantId ID do tenant
     * @param float $percentage Porcentagem de comissão (ex: 5.00 para 5%)
     * @param bool $isActive Se está ativa
     * @return array Dados da configuração
     */
    public function updateConfig(int $tenantId, float $percentage, bool $isActive = true): array
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \RuntimeException("Porcentagem deve estar entre 0 e 100");
        }

        $this->commissionConfigModel->upsert($tenantId, $percentage, $isActive);
        
        Logger::info("Configuração de comissão atualizada", [
            'tenant_id' => $tenantId,
            'percentage' => $percentage,
            'is_active' => $isActive
        ]);

        return $this->commissionConfigModel->findByTenant($tenantId);
    }

    /**
     * Busca estatísticas de comissões por funcionário
     * 
     * @param int $tenantId ID do tenant
     * @param int $userId ID do funcionário
     * @return array Estatísticas
     */
    public function getStatsByUser(int $tenantId, int $userId): array
    {
        return $this->commissionModel->getTotalByUser($tenantId, $userId);
    }

    /**
     * Busca estatísticas gerais de comissões do tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Estatísticas gerais
     */
    public function getGeneralStats(int $tenantId): array
    {
        try {
            $db = \App\Utils\Database::getInstance();
            $stmt = $db->prepare(
                "SELECT 
                    COUNT(*) as total_count,
                    COALESCE(SUM(commission_amount), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END), 0) as paid_amount,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END), 0) as pending_amount,
                    COUNT(DISTINCT user_id) as total_users
                FROM commissions
                WHERE tenant_id = :tenant_id"
            );
            $stmt->execute(['tenant_id' => $tenantId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'total_count' => (int)($result['total_count'] ?? 0),
                'total_amount' => (float)($result['total_amount'] ?? 0),
                'paid_amount' => (float)($result['paid_amount'] ?? 0),
                'pending_amount' => (float)($result['pending_amount'] ?? 0),
                'total_users' => (int)($result['total_users'] ?? 0)
            ];
        } catch (\PDOException $e) {
            // Se a tabela não existir, retorna valores padrão
            Logger::error("Erro ao buscar estatísticas de comissão", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            return [
                'total_count' => 0,
                'total_amount' => 0.0,
                'paid_amount' => 0.0,
                'pending_amount' => 0.0,
                'total_users' => 0
            ];
        }
    }
}

