<?php

namespace App\Middleware;

use App\Utils\CsrfHelper;
use App\Utils\ResponseHelper;
use App\Services\Logger;
use Flight;

/**
 * Middleware de proteção CSRF
 * 
 * Valida tokens CSRF em requisições que modificam dados (POST, PUT, PATCH, DELETE).
 * Protege contra ataques Cross-Site Request Forgery (CSRF).
 */
class CsrfMiddleware
{
    /**
     * Métodos HTTP que requerem validação CSRF
     */
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    
    /**
     * Rotas que devem ser excluídas da validação CSRF
     * (ex: webhooks externos, endpoints públicos)
     */
    private const EXCLUDED_ROUTES = [
        '/v1/webhook',           // Webhooks do Stripe não podem ter CSRF
        '/v1/auth/login',        // Login não precisa de CSRF (usa rate limiting)
        '/v1/auth/logout',       // Logout pode não precisar (mas pode ser adicionado depois)
    ];
    
    /**
     * Valida token CSRF na requisição atual
     * 
     * @return bool True se válido ou se não precisa validar, false se inválido
     */
    public function validate(): bool
    {
        $method = Flight::request()->method;
        
        // Apenas valida métodos que modificam dados
        if (!in_array($method, self::PROTECTED_METHODS)) {
            return true;
        }
        
        // Verifica se rota está excluída
        $requestUri = Flight::request()->url;
        foreach (self::EXCLUDED_ROUTES as $excludedRoute) {
            if (strpos($requestUri, $excludedRoute) === 0) {
                Logger::debug("Rota excluída da validação CSRF", [
                    'route' => $requestUri
                ]);
                return true;
            }
        }
        
        // Obtém session ID (pode vir de UserAuthMiddleware ou AuthMiddleware)
        $sessionId = $this->getSessionId();
        
        if (!$sessionId) {
            // Se não há sessão, não pode validar CSRF
            // Isso pode acontecer em rotas que usam API Key ao invés de Session
            // Para essas rotas, podemos pular CSRF ou implementar outro mecanismo
            Logger::debug("Sem session ID, pulando validação CSRF", [
                'route' => $requestUri,
                'method' => $method
            ]);
            return true; // Por enquanto, permite se não há sessão (compatibilidade)
        }
        
        // Obtém token CSRF do request
        $token = $this->getTokenFromRequest();
        
        if (!$token) {
            Logger::warning("Token CSRF não fornecido", [
                'route' => $requestUri,
                'method' => $method,
                'session_id' => substr($sessionId, 0, 20) . '...'
            ]);
            
            ResponseHelper::sendError(
                'Token CSRF não fornecido',
                403,
                'CSRF_TOKEN_MISSING',
                [
                    'message' => 'Token CSRF é obrigatório para esta operação. Obtenha um token em /v1/auth/csrf-token'
                ],
                ['action' => 'csrf_validation', 'route' => $requestUri]
            );
            Flight::stop();
            return false;
        }
        
        // Valida token
        if (!CsrfHelper::validateToken($sessionId, $token)) {
            Logger::warning("Token CSRF inválido", [
                'route' => $requestUri,
                'method' => $method,
                'session_id' => substr($sessionId, 0, 20) . '...'
            ]);
            
            ResponseHelper::sendError(
                'Token CSRF inválido ou expirado',
                403,
                'CSRF_TOKEN_INVALID',
                [
                    'message' => 'Token CSRF inválido ou expirado. Obtenha um novo token em /v1/auth/csrf-token'
                ],
                ['action' => 'csrf_validation', 'route' => $requestUri]
            );
            Flight::stop();
            return false;
        }
        
        Logger::debug("Token CSRF validado com sucesso", [
            'route' => $requestUri,
            'method' => $method
        ]);
        
        return true;
    }
    
    /**
     * Obtém session ID do request
     * 
     * @return string|null Session ID ou null se não encontrado
     */
    private function getSessionId(): ?string
    {
        // Tenta obter do Flight (pode ter sido setado por UserAuthMiddleware)
        $sessionId = Flight::get('session_id');
        if ($sessionId) {
            return $sessionId;
        }
        
        return null;
    }
    
    /**
     * Obtém token CSRF do request
     * 
     * Tenta obter de:
     * 1. Header X-CSRF-Token
     * 2. Campo _token no body (JSON ou form-data)
     * 
     * @return string|null Token CSRF ou null se não encontrado
     */
    private function getTokenFromRequest(): ?string
    {
        // Tenta obter do header
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$headerName] = $value;
                }
            }
        }
        
        $token = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if ($token) {
            return trim($token);
        }
        
        // Tenta obter do body (JSON ou form-data)
        $data = Flight::request()->data->getData();
        if (is_array($data) && isset($data['_token'])) {
            return trim($data['_token']);
        }
        
        // Tenta obter via RequestCache (para JSON)
        try {
            $jsonData = \App\Utils\RequestCache::getJsonInput();
            if (is_array($jsonData) && isset($jsonData['_token'])) {
                return trim($jsonData['_token']);
            }
        } catch (\Exception $e) {
            // Ignora erros ao ler JSON
        }
        
        return null;
    }
}

