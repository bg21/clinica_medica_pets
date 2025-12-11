<?php

namespace App\Utils;

use Config;
use App\Services\Logger;

/**
 * Helper para criptografia de dados sensíveis
 * Usa AES-256-CBC para criptografar/descriptografar dados
 */
class EncryptionHelper
{
    private const CIPHER = 'AES-256-CBC';
    private const KEY_LENGTH = 32; // 256 bits

    /**
     * Obtém chave de criptografia do ambiente
     * Se não existir, gera uma baseada em APP_KEY ou usa fallback
     * 
     * @return string Chave de 32 bytes (256 bits)
     */
    private static function getEncryptionKey(): string
    {
        // Tenta obter do .env
        $key = Config::get('ENCRYPTION_KEY');
        
        if (!empty($key)) {
            // Se a chave for menor que 32 bytes, faz hash para garantir 32 bytes
            if (strlen($key) < self::KEY_LENGTH) {
                return substr(hash('sha256', $key, true), 0, self::KEY_LENGTH);
            }
            return substr($key, 0, self::KEY_LENGTH);
        }

        // Fallback: usa APP_KEY se existir
        $appKey = Config::get('APP_KEY');
        if (!empty($appKey)) {
            return substr(hash('sha256', $appKey, true), 0, self::KEY_LENGTH);
        }

        // Último fallback: usa uma chave baseada em constantes do sistema
        // ⚠️ AVISO: Em produção, sempre defina ENCRYPTION_KEY no .env
        $fallbackKey = Config::get('STRIPE_SECRET') ?? 'default_fallback_key_change_in_production';
        return substr(hash('sha256', $fallbackKey, true), 0, self::KEY_LENGTH);
    }

    /**
     * Criptografa uma string
     * 
     * @param string $data Dados a serem criptografados
     * @return string Dados criptografados (formato: iv:encrypted_data)
     */
    public static function encrypt(string $data): string
    {
        try {
            $key = self::getEncryptionKey();
            $ivLength = openssl_cipher_iv_length(self::CIPHER);
            
            if ($ivLength === false) {
                throw new \RuntimeException("Erro ao obter tamanho do IV para " . self::CIPHER);
            }

            // Gera IV aleatório
            $iv = openssl_random_pseudo_bytes($ivLength);
            
            if ($iv === false) {
                throw new \RuntimeException("Erro ao gerar IV");
            }

            // Criptografa os dados
            $encrypted = openssl_encrypt($data, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
            
            if ($encrypted === false) {
                throw new \RuntimeException("Erro ao criptografar dados");
            }

            // Retorna IV + dados criptografados em base64
            return base64_encode($iv . $encrypted);
        } catch (\Exception $e) {
            Logger::error("Erro ao criptografar dados", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException("Erro ao criptografar dados: " . $e->getMessage());
        }
    }

    /**
     * Descriptografa uma string
     * 
     * @param string $encryptedData Dados criptografados (formato: iv:encrypted_data em base64)
     * @return string Dados descriptografados
     */
    public static function decrypt(string $encryptedData): string
    {
        try {
            $key = self::getEncryptionKey();
            $ivLength = openssl_cipher_iv_length(self::CIPHER);
            
            if ($ivLength === false) {
                throw new \RuntimeException("Erro ao obter tamanho do IV para " . self::CIPHER);
            }

            // Decodifica base64
            $data = base64_decode($encryptedData, true);
            
            if ($data === false) {
                throw new \RuntimeException("Erro ao decodificar dados base64");
            }

            // Extrai IV e dados criptografados
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);

            // Descriptografa
            $decrypted = openssl_decrypt($encrypted, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
            
            if ($decrypted === false) {
                throw new \RuntimeException("Erro ao descriptografar dados");
            }

            return $decrypted;
        } catch (\Exception $e) {
            Logger::error("Erro ao descriptografar dados", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException("Erro ao descriptografar dados: " . $e->getMessage());
        }
    }

    /**
     * Verifica se uma string está criptografada
     * (verifica se é base64 válido e tem tamanho mínimo esperado)
     * 
     * @param string $data String a verificar
     * @return bool True se parece estar criptografada
     */
    public static function isEncrypted(string $data): bool
    {
        // Verifica se é base64 válido
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            return false;
        }

        // Verifica se tem tamanho mínimo (IV + pelo menos alguns bytes de dados)
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        return strlen($decoded) > ($ivLength ?? 16);
    }
}

