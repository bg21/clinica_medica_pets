<?php

namespace App\Models;

/**
 * Model para gerenciar limites de rate limiting por tenant
 * 
 * Permite configurar limites customizados por tenant, endpoint e método HTTP.
 */
class TenantRateLimit extends BaseModel
{
    protected string $table = 'tenant_rate_limits';
    protected bool $usesSoftDeletes = false; // Não usa soft deletes para esta tabela

    /**
     * Busca limites configurados para um tenant específico
     * 
     * @param int $tenantId ID do tenant
     * @return array Lista de limites configurados
     */
    public function findByTenant(int $tenantId): array
    {
        return $this->findAll(['tenant_id' => $tenantId]);
    }

    /**
     * Busca limite específico para tenant, endpoint e método
     * 
     * @param int $tenantId ID do tenant
     * @param string|null $endpoint Endpoint (null para limite global)
     * @param string|null $method Método HTTP (null para todos os métodos)
     * @return array|null Limite configurado ou null se não encontrado
     */
    public function findByTenantEndpointMethod(?int $tenantId, ?string $endpoint = null, ?string $method = null): ?array
    {
        if ($tenantId === null) {
            return null;
        }
        
        try {
            // Constrói query SQL
            $sql = "SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id";
            $params = ['tenant_id' => $tenantId];
            
            // Adiciona condições para endpoint
            if ($endpoint !== null) {
                $sql .= " AND endpoint = :endpoint";
                $params['endpoint'] = $endpoint;
            } else {
                $sql .= " AND endpoint IS NULL";
            }
            
            // Adiciona condições para method
            if ($method !== null) {
                $sql .= " AND method = :method";
                $params['method'] = $method;
            } else {
                $sql .= " AND method IS NULL";
            }
            
            $sql .= " LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result;
            }
            
            // Se não encontrou e endpoint/method foram especificados, tenta buscar limite global
            if ($endpoint !== null || $method !== null) {
                $sql = "SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND endpoint IS NULL AND method IS NULL LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['tenant_id' => $tenantId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                return $result ?: null;
            }
            
            return null;
        } catch (\PDOException $e) {
            // ✅ SEGURANÇA: Se a tabela não existir ou houver erro, retorna null
            // O sistema vai usar limites padrão
            \App\Services\Logger::warning("Erro ao buscar limite de tenant no banco de dados", [
                'tenant_id' => $tenantId,
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Cria ou atualiza limite para um tenant
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados do limite ['endpoint' => string|null, 'method' => string|null, 'limit_per_minute' => int, 'limit_per_hour' => int]
     * @return int ID do registro criado ou atualizado
     */
    public function createOrUpdate(int $tenantId, array $data): int
    {
        $endpoint = $data['endpoint'] ?? null;
        $method = $data['method'] ?? null;
        
        // Verifica se já existe
        $existing = $this->findByTenantEndpointMethod($tenantId, $endpoint, $method);
        
        if ($existing) {
            // Atualiza
            $updateData = [
                'limit_per_minute' => (int)$data['limit_per_minute'],
                'limit_per_hour' => (int)$data['limit_per_hour']
            ];
            $this->update($existing['id'], $updateData);
            return $existing['id'];
        } else {
            // Cria novo
            $insertData = [
                'tenant_id' => $tenantId,
                'endpoint' => $endpoint,
                'method' => $method,
                'limit_per_minute' => (int)$data['limit_per_minute'],
                'limit_per_hour' => (int)$data['limit_per_hour']
            ];
            return $this->insert($insertData);
        }
    }

    /**
     * Remove limite específico
     * 
     * @param int $tenantId ID do tenant
     * @param string|null $endpoint Endpoint (null para limite global)
     * @param string|null $method Método HTTP (null para todos os métodos)
     * @return bool True se removido com sucesso
     */
    public function removeLimit(int $tenantId, ?string $endpoint = null, ?string $method = null): bool
    {
        $limit = $this->findByTenantEndpointMethod($tenantId, $endpoint, $method);
        
        if ($limit) {
            return $this->delete($limit['id']);
        }
        
        return false;
    }
}

