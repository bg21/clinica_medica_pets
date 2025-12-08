<?php

namespace App\Traits;

use App\Services\CacheService;
use App\Services\Logger;

/**
 * Trait para adicionar funcionalidade de cache em repositories
 * 
 * Facilita implementação de cache automático em métodos de busca
 * com invalidação automática em operações de escrita.
 * 
 * IMPORTANTE: A classe que usa este trait DEVE definir:
 * - protected string $cachePrefix = 'nome_do_recurso';
 * - protected int $defaultCacheTtl = 300; (opcional, padrão é 300 segundos)
 */
trait CacheableRepository
{
    /**
     * TTL padrão para cache (em segundos)
     * Pode ser sobrescrito na classe
     */
    protected int $defaultCacheTtl = 300; // 5 minutos

    /**
     * Obtém valor do cache
     * 
     * @param string $key Chave do cache
     * @return array|null Valor do cache ou null se não encontrado
     */
    protected function getFromCache(string $key): ?array
    {
        try {
            return CacheService::getJson($key);
        } catch (\Exception $e) {
            Logger::warning("Erro ao ler cache em repository", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Armazena valor no cache
     * 
     * @param string $key Chave do cache
     * @param array $value Valor a armazenar
     * @param int|null $ttl TTL em segundos (null para usar padrão)
     * @return bool True se armazenado com sucesso
     */
    protected function setCache(string $key, array $value, ?int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? $this->defaultCacheTtl;
            return CacheService::setJson($key, $value, $ttl);
        } catch (\Exception $e) {
            Logger::warning("Erro ao escrever cache em repository", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove valor do cache
     * 
     * @param string $key Chave do cache
     * @return bool True se removido com sucesso
     */
    protected function deleteCache(string $key): bool
    {
        try {
            return CacheService::delete($key);
        } catch (\Exception $e) {
            Logger::warning("Erro ao deletar cache em repository", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Constrói chave de cache para findById
     * 
     * @param int $id ID do registro
     * @return string Chave do cache
     */
    protected function buildCacheKeyById(int $id): string
    {
        return "{$this->cachePrefix}:id:{$id}";
    }

    /**
     * Constrói chave de cache para findByTenantAndId
     * 
     * @param int $tenantId ID do tenant
     * @param int $id ID do registro
     * @return string Chave do cache
     */
    protected function buildCacheKeyByTenantAndId(int $tenantId, int $id): string
    {
        return "{$this->cachePrefix}:tenant:{$tenantId}:id:{$id}";
    }

    /**
     * Constrói chave de cache para listagem com filtros
     * 
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros aplicados
     * @return string Chave do cache
     */
    protected function buildCacheKeyForList(int $tenantId, array $filters = []): string
    {
        // Ordena filtros para garantir consistência na chave
        ksort($filters);
        
        // Cria hash dos filtros para evitar chaves muito longas
        $filtersHash = !empty($filters) ? ':' . md5(json_encode($filters)) : '';
        
        return "{$this->cachePrefix}:list:{$tenantId}{$filtersHash}";
    }

    /**
     * Constrói padrão de chave para invalidar cache de listagem
     * 
     * @param int $tenantId ID do tenant
     * @return string Padrão de chave
     */
    protected function buildCachePatternForList(int $tenantId): string
    {
        return "{$this->cachePrefix}:list:{$tenantId}:*";
    }

    /**
     * Invalida cache de um registro específico
     * 
     * @param int $id ID do registro
     * @param int|null $tenantId ID do tenant (opcional)
     * @return void
     */
    protected function invalidateRecordCache(int $id, ?int $tenantId = null): void
    {
        // Invalida cache por ID
        $this->deleteCache($this->buildCacheKeyById($id));
        
        // Invalida cache por tenant e ID se tenant fornecido
        if ($tenantId !== null) {
            $this->deleteCache($this->buildCacheKeyByTenantAndId($tenantId, $id));
        }
    }

    /**
     * Invalida cache de listagem de um tenant
     * 
     * @param int $tenantId ID do tenant
     * @return void
     */
    protected function invalidateListCache(int $tenantId): void
    {
        try {
            $redis = CacheService::getRedisClient();
            if ($redis) {
                $pattern = $this->buildCachePatternForList($tenantId);
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
        } catch (\Exception $e) {
            Logger::warning("Erro ao invalidar cache de listagem", [
                'tenant_id' => $tenantId,
                'pattern' => $this->buildCachePatternForList($tenantId),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalida todo o cache relacionado a um tenant
     * 
     * @param int $tenantId ID do tenant
     * @return void
     */
    protected function invalidateTenantCache(int $tenantId): void
    {
        try {
            $redis = CacheService::getRedisClient();
            if ($redis) {
                // Invalida cache de listagem
                $listPattern = $this->buildCachePatternForList($tenantId);
                $listKeys = $redis->keys($listPattern);
                
                // Invalida cache de registros por tenant
                $recordPattern = "{$this->cachePrefix}:tenant:{$tenantId}:*";
                $recordKeys = $redis->keys($recordPattern);
                
                $allKeys = array_merge($listKeys, $recordKeys);
                if (!empty($allKeys)) {
                    $redis->del($allKeys);
                }
            }
        } catch (\Exception $e) {
            Logger::warning("Erro ao invalidar cache do tenant", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

