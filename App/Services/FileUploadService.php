<?php

namespace App\Services;

use App\Utils\Logger;
use Config;

/**
 * Service para gerenciar uploads de arquivos
 */
class FileUploadService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_DOCUMENT_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];
    
    /**
     * Faz upload de uma imagem
     * 
     * @param array $file Dados do arquivo ($_FILES['field_name'])
     * @param string $category Categoria do upload (pets, customers, professionals, exams)
     * @param int $tenantId ID do tenant
     * @param int|null $entityId ID da entidade (opcional, para organização)
     * @return string Caminho relativo do arquivo salvo
     * @throws \RuntimeException Se houver erro no upload
     */
    public function uploadImage(array $file, string $category, int $tenantId, ?int $entityId = null): string
    {
        // Valida arquivo
        $this->validateFile($file, self::ALLOWED_IMAGE_TYPES);
        
        // Gera nome único
        $extension = $this->getFileExtension($file['name']);
        $fileName = $this->generateFileName($category, $extension, $entityId);
        
        // Define diretório de destino
        $uploadDir = $this->getUploadDirectory($category, $tenantId);
        
        // Cria diretório se não existir
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filePath = $uploadDir . '/' . $fileName;
        
        // Move arquivo
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new \RuntimeException('Erro ao salvar arquivo no servidor');
        }
        
        // Retorna caminho relativo
        return $this->getRelativePath($filePath);
    }
    
    /**
     * Faz upload de um documento (PDF, etc.)
     * 
     * @param array $file Dados do arquivo
     * @param string $category Categoria do upload
     * @param int $tenantId ID do tenant
     * @param int|null $entityId ID da entidade
     * @return string Caminho relativo do arquivo salvo
     * @throws \RuntimeException Se houver erro no upload
     */
    public function uploadDocument(array $file, string $category, int $tenantId, ?int $entityId = null): string
    {
        // Valida arquivo
        $this->validateFile($file, self::ALLOWED_DOCUMENT_TYPES);
        
        // Gera nome único
        $extension = $this->getFileExtension($file['name']);
        $fileName = $this->generateFileName($category, $extension, $entityId);
        
        // Define diretório de destino
        $uploadDir = $this->getUploadDirectory($category, $tenantId);
        
        // Cria diretório se não existir
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filePath = $uploadDir . '/' . $fileName;
        
        // Move arquivo
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new \RuntimeException('Erro ao salvar arquivo no servidor');
        }
        
        // Retorna caminho relativo
        return $this->getRelativePath($filePath);
    }
    
    /**
     * Remove um arquivo
     * 
     * @param string $relativePath Caminho relativo do arquivo
     * @return bool True se removido com sucesso
     */
    public function deleteFile(string $relativePath): bool
    {
        $fullPath = $this->getFullPath($relativePath);
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * Valida arquivo antes do upload
     * 
     * @param array $file Dados do arquivo
     * @param array $allowedTypes Tipos MIME permitidos
     * @throws \RuntimeException Se arquivo inválido
     */
    private function validateFile(array $file, array $allowedTypes): void
    {
        // Verifica se arquivo foi enviado
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Arquivo excede o tamanho máximo permitido pelo servidor',
                UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o tamanho máximo permitido pelo formulário',
                UPLOAD_ERR_PARTIAL => 'Arquivo foi enviado parcialmente',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
                UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
                UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo no disco',
                UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
            ];
            
            $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            throw new \RuntimeException($errorMessages[$error] ?? 'Erro desconhecido no upload');
        }
        
        // Verifica tamanho
        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new \RuntimeException('Arquivo muito grande. Tamanho máximo: ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }
        
        // Verifica tipo MIME
        $mimeType = mime_content_type($file['tmp_name']) ?: $file['type'];
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \RuntimeException('Tipo de arquivo não permitido. Tipos permitidos: ' . implode(', ', $allowedTypes));
        }
    }
    
    /**
     * Obtém extensão do arquivo
     */
    private function getFileExtension(string $fileName): string
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }
    
    /**
     * Gera nome único para o arquivo
     */
    private function generateFileName(string $category, string $extension, ?int $entityId = null): string
    {
        $prefix = $category;
        if ($entityId) {
            $prefix .= '_' . $entityId;
        }
        
        return $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
    }
    
    /**
     * Obtém diretório de upload
     */
    private function getUploadDirectory(string $category, int $tenantId): string
    {
        $storagePath = Config::get('STORAGE_PATH');
        return $storagePath . '/' . $category . '/' . $tenantId;
    }
    
    /**
     * Converte caminho absoluto para relativo
     */
    private function getRelativePath(string $fullPath): string
    {
        $storagePath = Config::get('STORAGE_PATH');
        return str_replace($storagePath . '/', 'storage/', $fullPath);
    }
    
    /**
     * Converte caminho relativo para absoluto
     */
    private function getFullPath(string $relativePath): string
    {
        $storagePath = Config::get('STORAGE_PATH');
        return str_replace('storage/', $storagePath . '/', $relativePath);
    }
    
    /**
     * Obtém URL pública do arquivo
     */
    public function getPublicUrl(string $relativePath): string
    {
        if (empty($relativePath)) {
            return '';
        }
        
        // Remove 'storage/' do início se existir
        $path = ltrim($relativePath, '/');
        if (strpos($path, 'storage/') === 0) {
            return '/' . $path;
        }
        
        return '/storage/' . $path;
    }
}

