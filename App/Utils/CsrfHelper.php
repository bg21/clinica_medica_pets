<?php

namespace App\Utils;

use App\Services\CacheService;
use App\Services\Logger;

/**
 * Helper para gerenciar tokens CSRF
 * 
 * Gera, valida e gerencia tokens CSRF para proteção contra ataques
 * Cross-Site Request Forgery (CSRF).
 */
class CsrfHelper
{
    /**
     * Tempo de expiração do token CSRF em segundos (30 minutos)
     */
    private const TOKEN_EXPIRATION = 1800;
    
    /**
     * Prefixo para chaves de cache
     */
    private const CACHE_PREFIX = 'csrf:';
    
    /**
     * Gera um novo token CSRF para uma sessão
     * 
     * @param string $sessionId ID da sessão do usuário
     * @return string Token CSRF gerado
     */
    public static function generateToken(string $sessionId): string
    {
        // Gera token seguro usando random_bytes
        $token = bin2hex(random_bytes(32));
        
        // Armazena token no cache com expiração
        $cacheKey = self::CACHE_PREFIX . $sessionId;
        $success = CacheService::set($cacheKey, $token, self::TOKEN_EXPIRATION);
        
        // Se cache não está disponível, ainda retorna o token
        // (o sistema pode funcionar sem cache, mas tokens não serão persistidos)
        if (!$success) {
            Logger::warning("Cache não disponível ao gerar token CSRF", [
                'session_id' => substr($sessionId, 0, 20) . '...'
            ]);
        }
        
        Logger::debug("Token CSRF gerado", [
            'session_id' => substr($sessionId, 0, 20) . '...',
            'expires_in' => self::TOKEN_EXPIRATION,
            'cache_available' => $success
        ]);
        
        return $token;
    }
    
    /**
     * Valida um token CSRF
     * 
     * @param string $sessionId ID da sessão do usuário
     * @param string $token Token CSRF a ser validado
     * @return bool True se válido, false caso contrário
     */
    public static function validateToken(string $sessionId, string $token): bool
    {
        if (empty($sessionId) || empty($token)) {
            return false;
        }
        
        $cacheKey = self::CACHE_PREFIX . $sessionId;
        $expectedToken = CacheService::get($cacheKey);
        
        // Se cache não está disponível, não pode validar (retorna false por segurança)
        if ($expectedToken === null) {
            // Verifica se é porque cache não está disponível ou token não existe
            // Por segurança, assume que token é inválido
            Logger::warning("Token CSRF não encontrado no cache ou cache indisponível", [
                'session_id' => substr($sessionId, 0, 20) . '...'
            ]);
            return false;
        }
        
        // ✅ SEGURANÇA: Usa hash_equals para prevenir timing attacks
        $isValid = hash_equals($expectedToken, $token);
        
        if (!$isValid) {
            Logger::warning("Token CSRF inválido", [
                'session_id' => substr($sessionId, 0, 20) . '...'
            ]);
        }
        
        return $isValid;
    }
    
    /**
     * Obtém o token CSRF atual de uma sessão (sem regenerar)
     * 
     * @param string $sessionId ID da sessão do usuário
     * @return string|null Token CSRF ou null se não existir
     */
    public static function getToken(string $sessionId): ?string
    {
        if (empty($sessionId)) {
            return null;
        }
        
        $cacheKey = self::CACHE_PREFIX . $sessionId;
        return CacheService::get($cacheKey);
    }
    
    /**
     * Regenera o token CSRF (útil após login ou logout)
     * 
     * @param string $sessionId ID da sessão do usuário
     * @return string Novo token CSRF
     */
    public static function regenerateToken(string $sessionId): string
    {
        // Remove token antigo
        self::invalidateToken($sessionId);
        
        // Gera novo token
        return self::generateToken($sessionId);
    }
    
    /**
     * Invalida o token CSRF de uma sessão
     * 
     * @param string $sessionId ID da sessão do usuário
     * @return void
     */
    public static function invalidateToken(string $sessionId): void
    {
        if (empty($sessionId)) {
            return;
        }
        
        $cacheKey = self::CACHE_PREFIX . $sessionId;
        $success = CacheService::delete($cacheKey);
        
        if (!$success) {
            Logger::warning("Erro ao invalidar token CSRF (cache pode estar indisponível)", [
                'session_id' => substr($sessionId, 0, 20) . '...'
            ]);
        } else {
            Logger::debug("Token CSRF invalidado", [
                'session_id' => substr($sessionId, 0, 20) . '...'
            ]);
        }
    }
    
    /**
     * Verifica se um token CSRF existe e está válido (não expirado)
     * 
     * @param string $sessionId ID da sessão do usuário
     * @return bool True se existe e está válido
     */
    public static function hasValidToken(string $sessionId): bool
    {
        if (empty($sessionId)) {
            return false;
        }
        
        $cacheKey = self::CACHE_PREFIX . $sessionId;
        return CacheService::get($cacheKey) !== null;
    }
}

