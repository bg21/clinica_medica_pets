<?php

namespace App\Middleware;

use App\Utils\ApiVersion;
use App\Utils\ResponseHelper;
use Flight;

/**
 * Middleware para gerenciar versionamento de API
 * 
 * Valida versão da API e adiciona headers informativos
 */
class ApiVersionMiddleware
{
    /**
     * Processa a requisição e valida a versão da API
     * 
     * Deve ser chamado no before('start')
     */
    public function handle(): void
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        
        // Ignora rotas que não são da API versionada
        if (!preg_match('#^/v\d+/#', $requestUri)) {
            return;
        }
        
        // Extrai versão da URI
        $version = ApiVersion::extractFromUri($requestUri);
        
        // Se não encontrou versão na URI, tenta do header
        if ($version === null) {
            $version = ApiVersion::extractFromHeader();
        }
        
        // Se ainda não encontrou, usa padrão
        if ($version === null) {
            $version = ApiVersion::DEFAULT_VERSION;
        }
        
        // Armazena versão no Flight para uso posterior
        Flight::set('api_version', $version);
        
        // Verifica se versão é suportada
        if (!ApiVersion::isSupported($version)) {
            ResponseHelper::sendError(
                'Versão da API não suportada',
                "A versão '{$version}' não é suportada. Versões disponíveis: " . implode(', ', ApiVersion::SUPPORTED_VERSIONS),
                'UNSUPPORTED_API_VERSION',
                400,
                [
                    'supported_versions' => ApiVersion::SUPPORTED_VERSIONS,
                    'current_version' => ApiVersion::CURRENT_VERSION,
                    'requested_version' => $version
                ]
            );
            Flight::stop();
            return;
        }
        
        // Adiciona headers informativos
        $this->addVersionHeaders($version);
    }
    
    /**
     * Adiciona headers HTTP relacionados à versão da API
     * 
     * @param string $version Versão da API
     */
    private function addVersionHeaders(string $version): void
    {
        // Header com versão atual
        header('X-API-Version: ' . $version);
        
        // Header com versão mais recente
        header('X-API-Latest-Version: ' . ApiVersion::CURRENT_VERSION);
        
        // Header com versões suportadas
        header('X-API-Supported-Versions: ' . implode(', ', ApiVersion::SUPPORTED_VERSIONS));
        
        // Se versão está deprecada, adiciona header de aviso
        if (ApiVersion::isDeprecated($version)) {
            $deprecationDate = ApiVersion::getDeprecationDate($version);
            $removalDate = ApiVersion::getRemovalDate($version);
            
            $warning = "299 - \"Esta versão da API está deprecada";
            if ($deprecationDate) {
                $warning .= " desde {$deprecationDate}";
            }
            if ($removalDate) {
                $warning .= ". Será removida em {$removalDate}";
            }
            $warning .= ". Por favor, migre para " . ApiVersion::CURRENT_VERSION . "\"";
            
            header('Warning: ' . $warning);
        }
    }
}

