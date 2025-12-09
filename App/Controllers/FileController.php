<?php

namespace App\Controllers;

use App\Services\FileUploadService;
use App\Utils\ResponseHelper;
use App\Models\Pet;
use App\Models\Customer;
use App\Models\Professional;
use Flight;
use Config;

/**
 * Controller para gerenciar uploads de arquivos
 */
class FileController
{
    private FileUploadService $fileUploadService;
    
    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }
    
    /**
     * Upload de foto de pet
     * POST /v1/files/pets/:id/photo
     */
    public function uploadPetPhoto(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'upload_pet_photo']);
                return;
            }
            
            $petId = (int)Flight::request()->params['id'];
            
            // Verifica se pet existe e pertence ao tenant
            $petModel = new Pet();
            $pet = $petModel->findByTenantAndId($tenantId, $petId);
            
            if (!$pet) {
                ResponseHelper::sendNotFoundError('Pet não encontrado', ['action' => 'upload_pet_photo']);
                return;
            }
            
            // Verifica se arquivo foi enviado
            if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                ResponseHelper::sendValidationError(
                    'Arquivo inválido',
                    ['photo' => 'Nenhum arquivo foi enviado ou ocorreu um erro no upload'],
                    ['action' => 'upload_pet_photo']
                );
                return;
            }
            
            // Remove foto antiga se existir
            if (!empty($pet['photo_url'])) {
                $this->fileUploadService->deleteFile($pet['photo_url']);
            }
            
            // Faz upload
            $relativePath = $this->fileUploadService->uploadImage(
                $_FILES['photo'],
                'pets',
                $tenantId,
                $petId
            );
            
            // Atualiza pet com nova foto
            $petModel->update($petId, ['photo_url' => $relativePath]);
            
            ResponseHelper::sendSuccess([
                'photo_url' => $relativePath,
                'public_url' => $this->fileUploadService->getPublicUrl($relativePath)
            ], 'Foto do pet atualizada com sucesso');
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao fazer upload de foto de pet: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao fazer upload de foto do pet',
                'PET_PHOTO_UPLOAD_ERROR',
                ['action' => 'upload_pet_photo']
            );
        }
    }
    
    /**
     * Upload de foto de tutor (customer)
     * POST /v1/files/customers/:id/photo
     */
    public function uploadCustomerPhoto(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'upload_customer_photo']);
                return;
            }
            
            $customerId = (int)Flight::request()->params['id'];
            
            // Verifica se customer existe e pertence ao tenant
            $customerModel = new Customer();
            $customer = $customerModel->findByTenantAndId($tenantId, $customerId);
            
            if (!$customer) {
                ResponseHelper::sendNotFoundError('Tutor não encontrado', ['action' => 'upload_customer_photo']);
                return;
            }
            
            // Verifica se arquivo foi enviado
            if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                ResponseHelper::sendValidationError(
                    'Arquivo inválido',
                    ['photo' => 'Nenhum arquivo foi enviado ou ocorreu um erro no upload'],
                    ['action' => 'upload_customer_photo']
                );
                return;
            }
            
            // Remove foto antiga se existir
            if (!empty($customer['photo_url'])) {
                $this->fileUploadService->deleteFile($customer['photo_url']);
            }
            
            // Faz upload
            $relativePath = $this->fileUploadService->uploadImage(
                $_FILES['photo'],
                'customers',
                $tenantId,
                $customerId
            );
            
            // Atualiza customer com nova foto
            $customerModel->update($customerId, ['photo_url' => $relativePath]);
            
            ResponseHelper::sendSuccess([
                'photo_url' => $relativePath,
                'public_url' => $this->fileUploadService->getPublicUrl($relativePath)
            ], 'Foto do tutor atualizada com sucesso');
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao fazer upload de foto de tutor: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao fazer upload de foto do tutor',
                'CUSTOMER_PHOTO_UPLOAD_ERROR',
                ['action' => 'upload_customer_photo']
            );
        }
    }
    
    /**
     * Upload de foto de profissional
     * POST /v1/files/professionals/:id/photo
     */
    public function uploadProfessionalPhoto(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'upload_professional_photo']);
                return;
            }
            
            $professionalId = (int)Flight::request()->params['id'];
            
            // Verifica se professional existe e pertence ao tenant
            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, $professionalId);
            
            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional não encontrado', ['action' => 'upload_professional_photo']);
                return;
            }
            
            // Verifica se arquivo foi enviado
            if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                ResponseHelper::sendValidationError(
                    'Arquivo inválido',
                    ['photo' => 'Nenhum arquivo foi enviado ou ocorreu um erro no upload'],
                    ['action' => 'upload_professional_photo']
                );
                return;
            }
            
            // Remove foto antiga se existir
            if (!empty($professional['photo_url'])) {
                $this->fileUploadService->deleteFile($professional['photo_url']);
            }
            
            // Faz upload
            $relativePath = $this->fileUploadService->uploadImage(
                $_FILES['photo'],
                'professionals',
                $tenantId,
                $professionalId
            );
            
            // Atualiza professional com nova foto
            $professionalModel->update($professionalId, ['photo_url' => $relativePath]);
            
            ResponseHelper::sendSuccess([
                'photo_url' => $relativePath,
                'public_url' => $this->fileUploadService->getPublicUrl($relativePath)
            ], 'Foto do profissional atualizada com sucesso');
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao fazer upload de foto de profissional: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao fazer upload de foto do profissional',
                'PROFESSIONAL_PHOTO_UPLOAD_ERROR',
                ['action' => 'upload_professional_photo']
            );
        }
    }
    
    /**
     * Remove foto de pet
     * DELETE /v1/files/pets/:id/photo
     */
    public function deletePetPhoto(): void
    {
        try {
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_pet_photo']);
                return;
            }
            
            $petId = (int)Flight::request()->params['id'];
            
            // Verifica se pet existe
            $petModel = new Pet();
            $pet = $petModel->findByTenantAndId($tenantId, $petId);
            
            if (!$pet) {
                ResponseHelper::sendNotFoundError('Pet não encontrado', ['action' => 'delete_pet_photo']);
                return;
            }
            
            // Remove arquivo se existir
            if (!empty($pet['photo_url'])) {
                $this->fileUploadService->deleteFile($pet['photo_url']);
                $petModel->update($petId, ['photo_url' => null]);
            }
            
            ResponseHelper::sendSuccess([], 'Foto removida com sucesso');
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao remover foto de pet: " . $e->getMessage());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao remover foto do pet',
                'PET_PHOTO_DELETE_ERROR',
                ['action' => 'delete_pet_photo']
            );
        }
    }
}

