<?php

namespace App\Core;

use App\Services\EmailService;
use App\Services\CacheService;
use App\Services\Logger;

/**
 * Configuração de listeners de eventos
 * 
 * Centraliza o registro de todos os listeners do sistema
 */
class EventListeners
{
    /**
     * Registra todos os listeners no EventDispatcher
     * 
     * @param EventDispatcher $dispatcher
     * @param Container $container Container para resolver dependências
     */
    public static function register(EventDispatcher $dispatcher, Container $container): void
    {
        // ============================================
        // EVENTOS DE USUÁRIO
        // ============================================
        
        // Listener para invalidação de cache quando usuário é criado/atualizado/deletado
        $dispatcher->listen('user.created', function(array $payload) use ($container) {
            $user = $payload['user'] ?? null;
            
            if (!$user || !isset($user['tenant_id'])) {
                return;
            }
            
            try {
                $cacheService = $container->make(\App\Services\CacheService::class);
                $tenantId = $user['tenant_id'];
                
                // Invalida cache de usuários do tenant
                $cacheService->delete("users_{$tenantId}_*");
            } catch (\Throwable $e) {
                Logger::error('Erro ao invalidar cache de usuário criado (via evento)', [
                    'error' => $e->getMessage(),
                    'tenant_id' => $user['tenant_id'] ?? null
                ]);
            }
        });
        
        $dispatcher->listen('user.updated', function(array $payload) use ($container) {
            $user = $payload['user'] ?? null;
            
            if (!$user || !isset($user['tenant_id'])) {
                return;
            }
            
            try {
                $cacheService = $container->make(\App\Services\CacheService::class);
                $tenantId = $user['tenant_id'];
                
                // Invalida cache de usuários do tenant
                $cacheService->delete("users_{$tenantId}_*");
            } catch (\Throwable $e) {
                Logger::error('Erro ao invalidar cache de usuário atualizado (via evento)', [
                    'error' => $e->getMessage(),
                    'tenant_id' => $user['tenant_id'] ?? null
                ]);
            }
        });
        
        $dispatcher->listen('user.deleted', function(array $payload) use ($container) {
            $user = $payload['user'] ?? null;
            
            if (!$user || !isset($user['tenant_id'])) {
                return;
            }
            
            try {
                $cacheService = $container->make(\App\Services\CacheService::class);
                $tenantId = $user['tenant_id'];
                
                // Invalida cache de usuários do tenant
                $cacheService->delete("users_{$tenantId}_*");
            } catch (\Throwable $e) {
                Logger::error('Erro ao invalidar cache de usuário deletado (via evento)', [
                    'error' => $e->getMessage(),
                    'tenant_id' => $user['tenant_id'] ?? null
                ]);
            }
        });
    }
}

