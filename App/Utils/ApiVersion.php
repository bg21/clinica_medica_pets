<?php

namespace App\Utils;

/**
 * Utilitário para gerenciar versionamento de API
 * 
 * Facilita a organização e gerenciamento de diferentes versões da API
 */
class ApiVersion
{
    /**
     * Versão atual da API
     */
    public const CURRENT_VERSION = 'v1';
    
    /**
     * Versões suportadas
     */
    public const SUPPORTED_VERSIONS = ['v1'];
    
    /**
     * Versão padrão (usada quando nenhuma versão é especificada)
     */
    public const DEFAULT_VERSION = 'v1';
    
    /**
     * Versão mais antiga ainda suportada
     */
    public const OLDEST_SUPPORTED_VERSION = 'v1';
    
    /**
     * Extrai a versão da URL da requisição
     * 
     * @param string $uri URI da requisição (ex: /v1/customers)
     * @return string Versão extraída (ex: v1) ou null se não encontrada
     */
    public static function extractFromUri(string $uri): ?string
    {
        // Remove query string
        $path = parse_url($uri, PHP_URL_PATH) ?? $uri;
        
        // Verifica padrão /v{numero}/
        if (preg_match('#^/v(\d+)/#', $path, $matches)) {
            return 'v' . $matches[1];
        }
        
        return null;
    }
    
    /**
     * Extrai a versão do header Accept
     * 
     * @return string|null Versão extraída ou null se não encontrada
     */
    public static function extractFromHeader(): ?string
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        
        // Verifica padrão application/vnd.api.v{numero}+json
        if (preg_match('/application\/vnd\.api\.v(\d+)\+json/', $acceptHeader, $matches)) {
            return 'v' . $matches[1];
        }
        
        return null;
    }
    
    /**
     * Obtém a versão da requisição atual
     * 
     * Prioridade:
     * 1. Header Accept (application/vnd.api.v{numero}+json)
     * 2. URI (/v{numero}/...)
     * 3. Versão padrão
     * 
     * @return string Versão da API
     */
    public static function getCurrentVersion(): string
    {
        // Tenta obter do header primeiro
        $versionFromHeader = self::extractFromHeader();
        if ($versionFromHeader !== null && self::isSupported($versionFromHeader)) {
            return $versionFromHeader;
        }
        
        // Tenta obter da URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $versionFromUri = self::extractFromUri($requestUri);
        if ($versionFromUri !== null && self::isSupported($versionFromUri)) {
            return $versionFromUri;
        }
        
        // Retorna versão padrão
        return self::DEFAULT_VERSION;
    }
    
    /**
     * Verifica se uma versão é suportada
     * 
     * @param string $version Versão a verificar (ex: v1, v2)
     * @return bool True se suportada
     */
    public static function isSupported(string $version): bool
    {
        return in_array($version, self::SUPPORTED_VERSIONS, true);
    }
    
    /**
     * Verifica se uma versão está deprecada
     * 
     * @param string $version Versão a verificar
     * @return bool True se deprecada
     */
    public static function isDeprecated(string $version): bool
    {
        // Por enquanto, nenhuma versão está deprecada
        // Quando v2 for lançada, v1 pode ser marcada como deprecada
        return false;
    }
    
    /**
     * Obtém data de deprecação de uma versão
     * 
     * @param string $version Versão a verificar
     * @return string|null Data de deprecação ou null se não deprecada
     */
    public static function getDeprecationDate(string $version): ?string
    {
        // Por enquanto, nenhuma versão está deprecada
        return null;
    }
    
    /**
     * Obtém data de remoção de uma versão
     * 
     * @param string $version Versão a verificar
     * @return string|null Data de remoção ou null se não programada
     */
    public static function getRemovalDate(string $version): ?string
    {
        // Por enquanto, nenhuma versão está programada para remoção
        return null;
    }
    
    /**
     * Constrói URL com versão
     * 
     * @param string $endpoint Endpoint sem versão (ex: /customers)
     * @param string|null $version Versão (null para usar versão atual)
     * @return string URL completa (ex: /v1/customers)
     */
    public static function buildUrl(string $endpoint, ?string $version = null): string
    {
        $version = $version ?? self::CURRENT_VERSION;
        
        // Remove barra inicial se existir
        $endpoint = ltrim($endpoint, '/');
        
        // Remove versão se já existir
        $endpoint = preg_replace('#^v\d+/#', '', $endpoint);
        
        return '/' . $version . '/' . $endpoint;
    }
    
    /**
     * Remove versão da URL
     * 
     * @param string $uri URI com versão (ex: /v1/customers)
     * @return string URI sem versão (ex: /customers)
     */
    public static function removeVersion(string $uri): string
    {
        return preg_replace('#^/v\d+/#', '/', $uri);
    }
}

