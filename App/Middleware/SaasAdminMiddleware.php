<?php

namespace App\Middleware;

use App\Models\SaasAdminSession;
use App\Services\Logger;
use Flight;

/**
 * Middleware de autenticação de administradores do SaaS
 * 
 * Valida sessões de administradores master e injeta dados no Flight
 */
class SaasAdminMiddleware
{
    private SaasAdminSession $sessionModel;

    public function __construct()
    {
        $this->sessionModel = new SaasAdminSession();
    }

    /**
     * Valida autenticação de administrador SaaS e injeta dados no request
     * 
     * @return array|null Dados do administrador autenticado ou null se inválido
     */
    public function handle(): ?array
    {
        $sessionId = $this->getSessionId();

        if (!$sessionId) {
            return $this->unauthorized('Token de sessão não fornecido');
        }

        $session = $this->sessionModel->validate($sessionId);

        if (!$session) {
            Logger::warning("Tentativa de acesso com sessão de admin SaaS inválida", [
                'session_id' => substr($sessionId, 0, 20) . '...'
            ]);
            return $this->unauthorized('Sessão inválida ou expirada');
        }

        // Injeta dados no Flight
        Flight::set('saas_admin_id', (int)$session['admin_id']);
        Flight::set('saas_admin_email', $session['email']);
        Flight::set('saas_admin_name', $session['name']);
        Flight::set('is_saas_admin', true);
        Flight::set('is_user_auth', false);
        Flight::set('is_master', false);
        Flight::set('tenant_id', null);

        Logger::debug("Autenticação de administrador SaaS bem-sucedida", [
            'admin_id' => $session['admin_id']
        ]);

        return [
            'admin_id' => (int)$session['admin_id'],
            'email' => $session['email'],
            'name' => $session['name']
        ];
    }

    /**
     * Obtém session ID do request
     */
    private function getSessionId(): ?string
    {
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

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        if (isset($_COOKIE['saas_admin_session_id'])) {
            return $_COOKIE['saas_admin_session_id'];
        }

        if (isset($_GET['saas_admin_session_id'])) {
            return $_GET['saas_admin_session_id'];
        }

        return null;
    }

    /**
     * Retorna resposta de não autorizado
     */
    private function unauthorized(string $message): ?array
    {
        Flight::json(['error' => $message], 401);
        Flight::stop();
        return null;
    }
}

