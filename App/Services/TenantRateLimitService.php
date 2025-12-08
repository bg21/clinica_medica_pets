<?php

namespace App\Services;

use App\Models\TenantRateLimit;
use App\Services\CacheService;
use App\Services\Logger;

/**
 * Service para gerenciar limites de rate limiting por tenant
 * 
 * Permite configurar limites customizados por tenant, endpoint e método HTTP.
 * Usa cache para melhor performance.
 */
class TenantRateLimitService
{
    private TenantRateLimit $model;
    private const CACHE_TTL = 300; // 5 minutos
    private const CACHE_PREFIX = 'tenant_rate_limit:';

    public function __construct(TenantRateLimit $model)
    {
        $this->model = $model;
    }

    /**
     * Obtém limites configurados para um tenant
     * 
     * @param int|null $tenantId ID do tenant (null para limites padrão)
     * @param string|null $endpoint Endpoint específico (opcional)
     * @param string|null $method Método HTTP (opcional)
     * @return array|null Limites ['limit_per_minute' => int, 'limit_per_hour' => int] ou null se não configurado
     */
    public function getLimits(?int $tenantId, ?string $endpoint = null, ?string $method = null): ?array
    {
        // Se não tem tenant, retorna null (usa limites padrão)
        if ($tenantId === null) {
            return null;
        }

        try {
            // Tenta buscar do cache primeiro
            $cacheKey = $this->buildCacheKey($tenantId, $endpoint, $method);
            $cached = CacheService::getJson($cacheKey);
            
            if ($cached !== null) {
                return $cached;
            }

            // Busca do banco de dados
            $limit = $this->model->findByTenantEndpointMethod($tenantId, $endpoint, $method);
            
            if ($limit) {
                $result = [
                    'limit_per_minute' => (int)$limit['limit_per_minute'],
                    'limit_per_hour' => (int)$limit['limit_per_hour']
                ];
                
                // Armazena no cache
                CacheService::setJson($cacheKey, $result, self::CACHE_TTL);
                
                return $result;
            }

            // Se não encontrou limite específico, tenta buscar limite global do tenant
            if ($endpoint !== null || $method !== null) {
                $globalLimit = $this->model->findByTenantEndpointMethod($tenantId, null, null);
                
                if ($globalLimit) {
                    $result = [
                        'limit_per_minute' => (int)$globalLimit['limit_per_minute'],
                        'limit_per_hour' => (int)$globalLimit['limit_per_hour']
                    ];
                    
                    // Armazena no cache
                    CacheService::setJson($cacheKey, $result, self::CACHE_TTL);
                    
                    return $result;
                }
            }

            // Não encontrou limite configurado
            return null;
        } catch (\Exception $e) {
            // ✅ SEGURANÇA: Em caso de erro, retorna null para usar limites padrão
            // Não quebra a aplicação se houver problema com a tabela ou banco
            Logger::warning("Erro ao buscar limites de rate limit do tenant, usando limites padrão", [
                'tenant_id' => $tenantId,
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Define limites para um tenant
     * 
     * @param int $tenantId ID do tenant
     * @param array $limits ['limit_per_minute' => int, 'limit_per_hour' => int]
     * @param string|null $endpoint Endpoint específico (null para limite global)
     * @param string|null $method Método HTTP (null para todos os métodos)
     * @return bool True se criado/atualizado com sucesso
     */
    public function setLimits(int $tenantId, array $limits, ?string $endpoint = null, ?string $method = null): bool
    {
        try {
            $data = [
                'endpoint' => $endpoint,
                'method' => $method,
                'limit_per_minute' => (int)$limits['limit_per_minute'],
                'limit_per_hour' => (int)$limits['limit_per_hour']
            ];

            $this->model->createOrUpdate($tenantId, $data);

            // Invalida cache
            $this->invalidateCache($tenantId, $endpoint, $method);

            Logger::info("Limites de rate limit atualizados para tenant", [
                'tenant_id' => $tenantId,
                'endpoint' => $endpoint,
                'method' => $method,
                'limits' => $limits
            ]);

            return true;
        } catch (\Exception $e) {
            Logger::error("Erro ao definir limites de rate limit", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove limites de um tenant
     * 
     * @param int $tenantId ID do tenant
     * @param string|null $endpoint Endpoint específico (null para limite global)
     * @param string|null $method Método HTTP (null para todos os métodos)
     * @return bool True se removido com sucesso
     */
    public function removeLimits(int $tenantId, ?string $endpoint = null, ?string $method = null): bool
    {
        try {
            $removed = $this->model->removeLimit($tenantId, $endpoint, $method);

            if ($removed) {
                // Invalida cache
                $this->invalidateCache($tenantId, $endpoint, $method);

                Logger::info("Limites de rate limit removidos para tenant", [
                    'tenant_id' => $tenantId,
                    'endpoint' => $endpoint,
                    'method' => $method
                ]);
            }

            return $removed;
        } catch (\Exception $e) {
            Logger::error("Erro ao remover limites de rate limit", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lista todos os limites configurados para um tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Lista de limites
     */
    public function listLimits(int $tenantId): array
    {
        return $this->model->findByTenant($tenantId);
    }

    /**
     * Constrói chave de cache
     */
    private function buildCacheKey(?int $tenantId, ?string $endpoint, ?string $method): string
    {
        $parts = [self::CACHE_PREFIX, (string)$tenantId];
        
        if ($endpoint !== null) {
            $parts[] = md5($endpoint);
        } else {
            $parts[] = 'global';
        }
        
        if ($method !== null) {
            $parts[] = strtoupper($method);
        } else {
            $parts[] = 'all';
        }
        
        return implode(':', $parts);
    }

    /**
     * Invalida cache para um tenant
     */
    private function invalidateCache(int $tenantId, ?string $endpoint, ?string $method): void
    {
        // Invalida cache específico
        $cacheKey = $this->buildCacheKey($tenantId, $endpoint, $method);
        CacheService::delete($cacheKey);

        // Se endpoint ou method foram especificados, também invalida cache global
        if ($endpoint !== null || $method !== null) {
            $globalCacheKey = $this->buildCacheKey($tenantId, null, null);
            CacheService::delete($globalCacheKey);
        }
    }
}

