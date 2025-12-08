<?php

namespace App\Controllers;

use App\Models\ClinicConfiguration;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;
use Config;

/**
 * Controller para gerenciar configurações da clínica
 */
class ClinicController
{
    /**
     * Obtém configurações da clínica
     * GET /v1/clinic/configuration
     */
    public function getConfiguration(): void
    {
        try {
            // Não requer permissão específica, apenas autenticação (qualquer usuário autenticado pode ver configurações)
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_clinic_configuration']);
                return;
            }
            
            $configModel = new ClinicConfiguration();
            
            try {
                $configuration = $configModel->findByTenant($tenantId);
            } catch (\Throwable $e) {
                if (Config::isDevelopment()) {
                    error_log("ERRO ao buscar configurações no banco: " . $e->getMessage());
                    error_log("Trace: " . $e->getTraceAsString());
                }
                // Se der erro ao buscar, retorna configuração padrão
                $configuration = null;
            }
            
            // Se não existe, retorna configuração padrão
            if (!$configuration) {
                $configuration = [
                    'tenant_id' => $tenantId,
                    'default_appointment_duration' => 30,
                    'time_slot_interval' => 15,
                    'allow_online_booking' => true,
                    'require_confirmation' => false,
                    'cancellation_hours' => 24
                ];
            }
            
            ResponseHelper::sendSuccess($configuration);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao obter configurações: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter configurações da clínica',
                'CLINIC_CONFIG_GET_ERROR',
                ['action' => 'get_clinic_configuration', 'tenant_id' => $tenantId ?? null]
            );
        }
    }
    
    /**
     * Atualiza configurações da clínica
     * PUT /v1/clinic/configuration
     */
    public function updateConfiguration(): void
    {
        try {
            // Não requer permissão específica, apenas autenticação (qualquer usuário autenticado pode editar configurações)
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'update_clinic_configuration']);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::sendInvalidJsonError(['action' => 'update_clinic_configuration']);
                return;
            }
            
            if (!is_array($data)) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['data' => 'Os dados devem ser um objeto JSON válido'],
                    ['action' => 'update_clinic_configuration']
                );
                return;
            }
            
            $configModel = new ClinicConfiguration();
            
            // Valida dados
            $errors = $configModel->validateConfiguration($data);
            if (!empty($errors)) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    $errors,
                    ['action' => 'update_clinic_configuration']
                );
                return;
            }
            
            // Salva configurações
            $configId = $configModel->upsertConfiguration($tenantId, $data);
            
            // Busca configurações atualizadas
            $updatedConfiguration = $configModel->findByTenant($tenantId);
            
            ResponseHelper::sendSuccess($updatedConfiguration, 'Configurações atualizadas com sucesso');
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao atualizar configurações: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao atualizar configurações da clínica',
                'CLINIC_CONFIG_UPDATE_ERROR',
                ['action' => 'update_clinic_configuration', 'tenant_id' => $tenantId ?? null]
            );
        }
    }
    
    /**
     * Faz upload do logo da clínica
     * POST /v1/clinic/logo
     */
    public function uploadLogo(): void
    {
        try {
            // Não requer permissão específica, apenas autenticação
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'upload_clinic_logo']);
                return;
            }
            
            // Verifica se há arquivo enviado
            if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                ResponseHelper::sendValidationError(
                    'Arquivo inválido',
                    ['logo' => 'Nenhum arquivo foi enviado ou ocorreu um erro no upload'],
                    ['action' => 'upload_clinic_logo']
                );
                return;
            }
            
            $file = $_FILES['logo'];
            
            // Valida tipo de arquivo
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                ResponseHelper::sendValidationError(
                    'Tipo de arquivo inválido',
                    ['logo' => 'Apenas imagens são permitidas (JPEG, PNG, GIF, WebP)'],
                    ['action' => 'upload_clinic_logo']
                );
                return;
            }
            
            // Valida tamanho (máximo 5MB)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                ResponseHelper::sendValidationError(
                    'Arquivo muito grande',
                    ['logo' => 'O arquivo deve ter no máximo 5MB'],
                    ['action' => 'upload_clinic_logo']
                );
                return;
            }
            
            // Cria diretório se não existir
            $uploadDir = __DIR__ . '/../../storage/clinic-logos/' . $tenantId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Gera nome único para o arquivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            // Move arquivo
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                ResponseHelper::sendError(
                    500,
                    'Erro ao salvar arquivo',
                    'Não foi possível salvar o arquivo no servidor',
                    'FILE_UPLOAD_ERROR',
                    [],
                    ['action' => 'upload_clinic_logo', 'tenant_id' => $tenantId]
                );
                return;
            }
            
            // Caminho relativo para salvar no banco
            $relativePath = '/storage/clinic-logos/' . $tenantId . '/' . $fileName;
            
            // Atualiza configuração com o caminho do logo
            $configModel = new ClinicConfiguration();
            $existing = $configModel->findByTenant($tenantId);
            
            // Remove logo anterior se existir
            if ($existing && !empty($existing['clinic_logo'])) {
                $oldLogoPath = __DIR__ . '/../../' . ltrim($existing['clinic_logo'], '/');
                if (file_exists($oldLogoPath)) {
                    @unlink($oldLogoPath);
                }
            }
            
            // Salva novo caminho
            $configModel->upsertConfiguration($tenantId, ['clinic_logo' => $relativePath]);
            
            ResponseHelper::sendSuccess([
                'logo_path' => $relativePath,
                'logo_url' => $relativePath // URL relativa para servir o arquivo
            ], 'Logo enviado com sucesso');
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao fazer upload do logo: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao fazer upload do logo',
                'CLINIC_LOGO_UPLOAD_ERROR',
                ['action' => 'upload_clinic_logo', 'tenant_id' => $tenantId ?? null]
            );
        }
    }
}

