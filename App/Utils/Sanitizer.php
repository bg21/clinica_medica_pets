<?php

namespace App\Utils;

/**
 * Classe para sanitização consistente de inputs
 * 
 * Previne XSS, SQL Injection e garante dados limpos antes da validação.
 * Todos os métodos retornam null se o valor não puder ser sanitizado.
 */
class Sanitizer
{
    /**
     * Sanitiza string genérica
     * 
     * Remove espaços, escapa HTML para prevenir XSS e limita tamanho.
     * 
     * @param mixed $value Valor a sanitizar
     * @param int $maxLength Tamanho máximo (padrão: 255)
     * @param bool $escapeHtml Se deve escapar HTML (padrão: true)
     * @return string|null String sanitizada ou null se inválida
     */
    public static function string($value, int $maxLength = 255, bool $escapeHtml = true): ?string
    {
        if ($value === null) {
            return null;
        }
        
        // Converte para string se não for
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        
        $value = trim((string)$value);
        
        // Retorna null se vazio após trim
        if ($value === '') {
            return null;
        }
        
        // Escapa HTML se solicitado
        if ($escapeHtml) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        // Limita tamanho
        if (mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }
        
        return $value;
    }
    
    /**
     * Sanitiza email
     * 
     * Remove espaços e valida formato de email.
     * 
     * @param mixed $value Valor a sanitizar
     * @return string|null Email sanitizado ou null se inválido
     */
    public static function email($value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        
        $value = trim((string)$value);
        
        if ($value === '') {
            return null;
        }
        
        // Sanitiza email usando filter_var
        $sanitized = filter_var($value, FILTER_SANITIZE_EMAIL);
        
        // Valida formato
        if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        // Limita tamanho (padrão de email: 255 caracteres)
        if (strlen($sanitized) > 255) {
            return null;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiza inteiro
     * 
     * Valida e converte para inteiro, com opção de min/max.
     * 
     * @param mixed $value Valor a sanitizar
     * @param int|null $min Valor mínimo (opcional)
     * @param int|null $max Valor máximo (opcional)
     * @return int|null Inteiro sanitizado ou null se inválido
     */
    public static function int($value, ?int $min = null, ?int $max = null): ?int
    {
        if ($value === null) {
            return null;
        }
        
        // Converte para inteiro
        $int = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($int === false) {
            return null;
        }
        
        // Valida mínimo
        if ($min !== null && $int < $min) {
            return null;
        }
        
        // Valida máximo
        if ($max !== null && $int > $max) {
            return null;
        }
        
        return $int;
    }
    
    /**
     * Sanitiza float
     * 
     * Valida e converte para float, com opção de min/max.
     * 
     * @param mixed $value Valor a sanitizar
     * @param float|null $min Valor mínimo (opcional)
     * @param float|null $max Valor máximo (opcional)
     * @return float|null Float sanitizado ou null se inválido
     */
    public static function float($value, ?float $min = null, ?float $max = null): ?float
    {
        if ($value === null) {
            return null;
        }
        
        // Converte para float
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        
        if ($float === false) {
            return null;
        }
        
        // Valida mínimo
        if ($min !== null && $float < $min) {
            return null;
        }
        
        // Valida máximo
        if ($max !== null && $float > $max) {
            return null;
        }
        
        return $float;
    }
    
    /**
     * Sanitiza URL
     * 
     * Valida e sanitiza URL.
     * 
     * @param mixed $value Valor a sanitizar
     * @param int $maxLength Tamanho máximo (padrão: 2048)
     * @return string|null URL sanitizada ou null se inválida
     */
    public static function url($value, int $maxLength = 2048): ?string
    {
        if ($value === null) {
            return null;
        }
        
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        
        $value = trim((string)$value);
        
        if ($value === '') {
            return null;
        }
        
        // Limita tamanho antes de validar
        if (strlen($value) > $maxLength) {
            return null;
        }
        
        // Sanitiza URL
        $sanitized = filter_var($value, FILTER_SANITIZE_URL);
        
        // Valida formato
        if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiza telefone
     * 
     * Remove caracteres não numéricos e espaços, mantém apenas números, +, -, (, ).
     * 
     * @param mixed $value Valor a sanitizar
     * @param int $maxLength Tamanho máximo (padrão: 50)
     * @return string|null Telefone sanitizado ou null se inválido
     */
    public static function phone($value, int $maxLength = 50): ?string
    {
        if ($value === null) {
            return null;
        }
        
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        
        $value = trim((string)$value);
        
        if ($value === '') {
            return null;
        }
        
        // Remove caracteres inválidos, mantém apenas números, +, -, (, )
        $sanitized = preg_replace('/[^0-9\+\-\(\)\s]/', '', $value);
        $sanitized = trim($sanitized);
        
        if ($sanitized === '') {
            return null;
        }
        
        // Limita tamanho
        if (strlen($sanitized) > $maxLength) {
            return null;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiza documento (CPF/CNPJ)
     * 
     * Remove caracteres não numéricos.
     * 
     * @param mixed $value Valor a sanitizar
     * @param int $maxLength Tamanho máximo (padrão: 50)
     * @return string|null Documento sanitizado ou null se inválido
     */
    public static function document($value, int $maxLength = 50): ?string
    {
        if ($value === null) {
            return null;
        }
        
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        
        $value = trim((string)$value);
        
        if ($value === '') {
            return null;
        }
        
        // Remove caracteres não numéricos
        $sanitized = preg_replace('/[^0-9]/', '', $value);
        
        if ($sanitized === '') {
            return null;
        }
        
        // Limita tamanho
        if (strlen($sanitized) > $maxLength) {
            return null;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiza texto longo (sem escape HTML)
     * 
     * Remove espaços extras e limita tamanho, mas não escapa HTML.
     * Útil para campos de texto que podem conter formatação.
     * 
     * @param mixed $value Valor a sanitizar
     * @param int $maxLength Tamanho máximo (padrão: 65535)
     * @return string|null Texto sanitizado ou null se inválido
     */
    public static function text($value, int $maxLength = 65535): ?string
    {
        if ($value === null) {
            return null;
        }
        
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        
        $value = trim((string)$value);
        
        // Permite string vazia para textos opcionais
        if ($value === '') {
            return '';
        }
        
        // Remove espaços múltiplos
        $value = preg_replace('/\s+/', ' ', $value);
        
        // Limita tamanho
        if (mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }
        
        return $value;
    }
    
    /**
     * Sanitiza slug
     * 
     * Converte para minúsculas, remove caracteres especiais, mantém apenas letras, números e hífens.
     * 
     * @param mixed $value Valor a sanitizar
     * @param int $maxLength Tamanho máximo (padrão: 100)
     * @return string|null Slug sanitizado ou null se inválido
     */
    public static function slug($value, int $maxLength = 100): ?string
    {
        if ($value === null) {
            return null;
        }
        
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        
        $value = trim((string)$value);
        
        if ($value === '') {
            return null;
        }
        
        // Converte para minúsculas
        $value = mb_strtolower($value, 'UTF-8');
        
        // Remove acentos (básico)
        $value = self::removeAccents($value);
        
        // Substitui espaços por hífens
        $value = preg_replace('/\s+/', '-', $value);
        
        // Remove caracteres especiais, mantém apenas letras, números e hífens
        $value = preg_replace('/[^a-z0-9-]/', '', $value);
        
        // Remove hífens múltiplos
        $value = preg_replace('/-+/', '-', $value);
        
        // Remove hífens no início e fim
        $value = trim($value, '-');
        
        if ($value === '') {
            return null;
        }
        
        // Limita tamanho
        if (strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
            $value = rtrim($value, '-'); // Remove hífen no final se cortado
        }
        
        return $value;
    }
    
    /**
     * Sanitiza booleano
     * 
     * Converte para booleano verdadeiro.
     * 
     * @param mixed $value Valor a sanitizar
     * @return bool|null Booleano ou null se não puder ser convertido
     */
    public static function bool($value): ?bool
    {
        if ($value === null) {
            return null;
        }
        
        // Se já é booleano, retorna
        if (is_bool($value)) {
            return $value;
        }
        
        // Converte string para booleano
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off'], true)) {
                return false;
            }
        }
        
        // Converte número para booleano
        if (is_numeric($value)) {
            return (int)$value !== 0;
        }
        
        return null;
    }
    
    /**
     * Sanitiza array de strings
     * 
     * Sanitiza cada elemento do array usando string().
     * 
     * @param mixed $value Valor a sanitizar
     * @param int $maxLength Tamanho máximo por string (padrão: 255)
     * @param int $maxItems Número máximo de itens (padrão: 100)
     * @return array|null Array sanitizado ou null se inválido
     */
    public static function stringArray($value, int $maxLength = 255, int $maxItems = 100): ?array
    {
        if ($value === null) {
            return null;
        }
        
        if (!is_array($value)) {
            return null;
        }
        
        // Limita número de itens
        if (count($value) > $maxItems) {
            return null;
        }
        
        $sanitized = [];
        foreach ($value as $item) {
            $sanitizedItem = self::string($item, $maxLength);
            if ($sanitizedItem !== null) {
                $sanitized[] = $sanitizedItem;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Remove acentos de string (básico)
     * 
     * @param string $value String com acentos
     * @return string String sem acentos
     */
    private static function removeAccents(string $value): string
    {
        $accents = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N'
        ];
        
        return strtr($value, $accents);
    }
}

