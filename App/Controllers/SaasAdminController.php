<?php

namespace App\Controllers;

use App\Models\SaasAdmin;
use App\Models\SaasAdminSession;
use App\Services\Logger;
use App\Utils\Validator;
use App\Utils\ResponseHelper;
use Flight;

/**
 * Controller para gerenciar administradores do SaaS
 * 
 * Gerencia login, logout e CRUD de administradores master
 */
class SaasAdminController
{
    private SaasAdmin $adminModel;
    private SaasAdminSession $sessionModel;

    public function __construct()
    {
        $this->adminModel = new SaasAdmin();
        $this->sessionModel = new SaasAdminSession();
    }

    /**
     * Login de administrador do SaaS
     * POST /v1/saas-admin/login
     */
    public function login(): void
    {
        try {
            // Obtém dados do request (suporta JSON e form-data)
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            
            // Se não conseguiu decodificar JSON, tenta obter de Flight
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                try {
                    $data = Flight::request()->data->getData();
                } catch (\Exception $e) {
                    // Se Flight também falhar, usa $_POST como fallback
                    $data = $_POST;
                }
            }
            
            Logger::debug("Dados recebidos no login de admin SaaS", [
                'raw_input' => substr($rawInput, 0, 100),
                'data' => $data,
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown'
            ]);

            // Validação
            $errors = [];
            if (empty($data['email'])) {
                $errors['email'] = 'Email é obrigatório';
            }
            if (empty($data['password'])) {
                $errors['password'] = 'Senha é obrigatória';
            }

            if (!empty($errors)) {
                Logger::warning("Validação falhou no login de admin SaaS", ['errors' => $errors]);
                ResponseHelper::sendValidationError('Dados inválidos', $errors);
                return;
            }

            // Busca administrador
            $admin = $this->adminModel->findByEmail($data['email']);

            if (!$admin) {
                Logger::warning("Tentativa de login com email não encontrado", [
                    'email' => $data['email']
                ]);
                ResponseHelper::sendError('Credenciais inválidas', [], 401);
                return;
            }
            
            if (!$admin['is_active']) {
                Logger::warning("Tentativa de login com administrador inativo", [
                    'email' => $data['email'],
                    'admin_id' => $admin['id']
                ]);
                ResponseHelper::sendError('Sua conta está inativa. Entre em contato com o suporte.', [], 403);
                return;
            }

            // Verifica senha
            $passwordValid = $this->adminModel->verifyPassword($data['password'], $admin['password_hash']);
            
            Logger::debug("Verificação de senha", [
                'email' => $data['email'],
                'admin_id' => $admin['id'],
                'password_valid' => $passwordValid,
                'password_hash_start' => substr($admin['password_hash'], 0, 20) . '...'
            ]);
            
            if (!$passwordValid) {
                Logger::warning("Tentativa de login com senha incorreta", [
                    'email' => $data['email'],
                    'admin_id' => $admin['id']
                ]);
                ResponseHelper::sendError('Credenciais inválidas', [], 401);
                return;
            }

            // Cria sessão
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $sessionId = $this->sessionModel->create($admin['id'], $ipAddress, $userAgent, 24);

            // Atualiza último login
            $this->adminModel->updateLastLogin($admin['id'], $ipAddress);

            Logger::info("Login de administrador SaaS bem-sucedido", [
                'admin_id' => $admin['id'],
                'email' => $admin['email']
            ]);

            ResponseHelper::sendSuccess([
                'session_id' => $sessionId,
                'admin' => [
                    'id' => $admin['id'],
                    'email' => $admin['email'],
                    'name' => $admin['name']
                ]
            ], 200, 'Login realizado com sucesso');

        } catch (\Exception $e) {
            Logger::error("Erro ao fazer login de administrador SaaS", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            ResponseHelper::sendError('Erro ao processar login', [], 500);
        }
    }

    /**
     * Logout de administrador do SaaS
     * POST /v1/saas-admin/logout
     */
    public function logout(): void
    {
        try {
            $sessionId = $this->getSessionId();

            if ($sessionId) {
                $this->sessionModel->deleteSession($sessionId);
            }

            ResponseHelper::sendSuccess([], 200, 'Logout realizado com sucesso');

        } catch (\Exception $e) {
            Logger::error("Erro ao fazer logout de administrador SaaS", [
                'error' => $e->getMessage()
            ]);
            ResponseHelper::sendError('Erro ao processar logout', [], 500);
        }
    }

    /**
     * Lista todos os administradores (apenas para outros admins)
     * GET /v1/saas-admin/admins
     */
    public function listAdmins(): void
    {
        try {
            // Verifica se é saas_admin
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado', [], 403);
                return;
            }

            $conditions = [];
            if (isset($_GET['is_active'])) {
                $conditions['is_active'] = (bool)$_GET['is_active'];
            }

            // Usa findAll com a assinatura correta do BaseModel
            $admins = $this->adminModel->findAll($conditions, ['created_at' => 'DESC']);

            // password_hash já é removido no método findAll() do SaasAdmin

            ResponseHelper::sendSuccess($admins, 200, 'Administradores listados com sucesso');

        } catch (\Exception $e) {
            Logger::error("Erro ao listar administradores SaaS", [
                'error' => $e->getMessage()
            ]);
            ResponseHelper::sendError('Erro ao listar administradores', [], 500);
        }
    }

    /**
     * Cria novo administrador
     * POST /v1/saas-admin/admins
     */
    public function createAdmin(): void
    {
        try {
            // Verifica se é saas_admin
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado', [], 403);
                return;
            }

            $data = Flight::request()->data->getData();

            // Validação
            $errors = [];
            if (empty($data['email'])) {
                $errors['email'] = 'Email é obrigatório';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email inválido';
            } elseif ($this->adminModel->emailExists($data['email'])) {
                $errors['email'] = 'Email já cadastrado';
            }

            if (empty($data['password'])) {
                $errors['password'] = 'Senha é obrigatória';
            } elseif (strlen($data['password']) < 8) {
                $errors['password'] = 'Senha deve ter no mínimo 8 caracteres';
            }

            if (empty($data['name'])) {
                $errors['name'] = 'Nome é obrigatório';
            }

            if (!empty($errors)) {
                ResponseHelper::sendValidationError('Dados inválidos', $errors);
                return;
            }

            $adminId = $this->adminModel->create($data);

            Logger::info("Novo administrador SaaS criado", [
                'admin_id' => $adminId,
                'email' => $data['email']
            ]);

            ResponseHelper::sendSuccess(['id' => $adminId], 201, 'Administrador criado com sucesso');

        } catch (\Exception $e) {
            Logger::error("Erro ao criar administrador SaaS", [
                'error' => $e->getMessage()
            ]);
            ResponseHelper::sendError('Erro ao criar administrador', [], 500);
        }
    }

    /**
     * Atualiza administrador
     * PUT /v1/saas-admin/admins/@id
     */
    public function updateAdmin(string $id): void
    {
        try {
            // Verifica se é saas_admin
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado', [], 403);
                return;
            }

            $admin = $this->adminModel->findById((int)$id);
            if (!$admin) {
                ResponseHelper::sendError('Administrador não encontrado', [], 404);
                return;
            }

            $data = Flight::request()->data->getData();

            // Validação
            $errors = [];
            if (isset($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Email inválido';
                } elseif ($this->adminModel->emailExists($data['email'], (int)$id)) {
                    $errors['email'] = 'Email já cadastrado';
                }
            }

            if (isset($data['password']) && strlen($data['password']) < 8) {
                $errors['password'] = 'Senha deve ter no mínimo 8 caracteres';
            }

            if (!empty($errors)) {
                ResponseHelper::sendValidationError('Dados inválidos', $errors);
                return;
            }

            $this->adminModel->update((int)$id, $data);

            Logger::info("Administrador SaaS atualizado", [
                'admin_id' => $id
            ]);

            ResponseHelper::sendSuccess([], 200, 'Administrador atualizado com sucesso');

        } catch (\Exception $e) {
            Logger::error("Erro ao atualizar administrador SaaS", [
                'error' => $e->getMessage()
            ]);
            ResponseHelper::sendError('Erro ao atualizar administrador', [], 500);
        }
    }

    /**
     * Remove administrador (soft delete)
     * DELETE /v1/saas-admin/admins/@id
     */
    public function deleteAdmin(string $id): void
    {
        try {
            // Verifica se é saas_admin
            if (!Flight::get('is_saas_admin')) {
                ResponseHelper::sendError('Acesso negado', [], 403);
                return;
            }

            $admin = $this->adminModel->findById((int)$id);
            if (!$admin) {
                ResponseHelper::sendError('Administrador não encontrado', [], 404);
                return;
            }

            // Não permite deletar a si mesmo
            $currentAdminId = Flight::get('saas_admin_id');
            if ($currentAdminId && (int)$currentAdminId === (int)$id) {
                ResponseHelper::sendError('Você não pode desativar sua própria conta', [], 400);
                return;
            }

            $this->adminModel->delete((int)$id);

            Logger::info("Administrador SaaS desativado", [
                'admin_id' => $id
            ]);

            ResponseHelper::sendSuccess([], 200, 'Administrador desativado com sucesso');

        } catch (\Exception $e) {
            Logger::error("Erro ao desativar administrador SaaS", [
                'error' => $e->getMessage()
            ]);
            ResponseHelper::sendError('Erro ao desativar administrador', [], 500);
        }
    }

    /**
     * Obtém session ID do request
     */
    private function getSessionId(): ?string
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
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
}

