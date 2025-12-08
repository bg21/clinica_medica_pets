<?php

namespace App\Utils;

use Flight;

/**
 * Helper para padronizar paginação em toda a aplicação
 * 
 * Fornece métodos para:
 * - Obter parâmetros de paginação do request
 * - Validar parâmetros de paginação
 * - Formatar resposta padronizada com metadados
 * - Calcular offset e limites
 */
class PaginationHelper
{
    /**
     * Limite máximo de itens por página
     */
    private const MAX_PER_PAGE = 100;
    
    /**
     * Limite padrão de itens por página
     */
    private const DEFAULT_PER_PAGE = 20;
    
    /**
     * Página padrão
     */
    private const DEFAULT_PAGE = 1;
    
    /**
     * Obtém e valida parâmetros de paginação do request
     * 
     * @param array|null $queryParams Parâmetros da query (se null, obtém do request)
     * @param int|null $maxPerPage Limite máximo customizado (opcional)
     * @return array ['page' => int, 'limit' => int, 'offset' => int, 'errors' => array]
     */
    public static function getPaginationParams(?array $queryParams = null, ?int $maxPerPage = null): array
    {
        // Obtém query params do request se não fornecidos
        if ($queryParams === null) {
            try {
                $queryParams = Flight::request()->query->getData();
                if (!is_array($queryParams)) {
                    $queryParams = [];
                }
            } catch (\Exception $e) {
                $queryParams = [];
            }
        }
        
        // Usa Validator existente para validar
        $validation = Validator::validatePagination($queryParams);
        
        // Ajusta limite máximo se fornecido
        $maxLimit = $maxPerPage ?? self::MAX_PER_PAGE;
        if ($validation['limit'] > $maxLimit) {
            $validation['limit'] = $maxLimit;
            if (empty($validation['errors'])) {
                $validation['errors'] = [];
            }
            $validation['errors']['limit'] = "Limite máximo permitido é {$maxLimit}";
        }
        
        // Calcula offset
        $offset = ($validation['page'] - 1) * $validation['limit'];
        
        return [
            'page' => $validation['page'],
            'limit' => $validation['limit'],
            'offset' => $offset,
            'errors' => $validation['errors']
        ];
    }
    
    /**
     * Formata resposta padronizada com dados e metadados de paginação
     * 
     * @param array $data Dados da página atual
     * @param int $total Total de registros (sem paginação)
     * @param int $page Página atual
     * @param int $perPage Itens por página
     * @return array Resposta formatada
     */
    public static function formatResponse(array $data, int $total, int $page, int $perPage): array
    {
        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'prev_page' => $page > 1 ? $page - 1 : null
            ]
        ];
    }
    
    /**
     * Pagina dados usando um callback que retorna os dados e o total
     * 
     * Útil quando você tem controle total sobre a query
     * 
     * @param callable $dataCallback Callback que recebe (limit, offset) e retorna array de dados
     * @param callable $countCallback Callback que retorna int (total de registros)
     * @param array|null $paginationParams Parâmetros de paginação (se null, obtém do request)
     * @param int|null $maxPerPage Limite máximo customizado
     * @return array Resposta formatada com dados e paginação
     */
    public static function paginate(
        callable $dataCallback,
        callable $countCallback,
        ?array $paginationParams = null,
        ?int $maxPerPage = null
    ): array {
        // Obtém parâmetros de paginação
        if ($paginationParams === null) {
            $paginationParams = self::getPaginationParams(null, $maxPerPage);
        }
        
        // Valida se há erros
        if (!empty($paginationParams['errors'])) {
            throw new \InvalidArgumentException('Parâmetros de paginação inválidos: ' . json_encode($paginationParams['errors']));
        }
        
        $page = $paginationParams['page'];
        $limit = $paginationParams['limit'];
        $offset = $paginationParams['offset'];
        
        // Obtém dados e total
        $data = $dataCallback($limit, $offset);
        $total = $countCallback();
        
        // Formata resposta
        return self::formatResponse($data, $total, $page, $limit);
    }
    
    /**
     * Pagina dados de um array (útil para dados já carregados)
     * 
     * @param array $allData Todos os dados
     * @param array|null $paginationParams Parâmetros de paginação (se null, obtém do request)
     * @param int|null $maxPerPage Limite máximo customizado
     * @return array Resposta formatada com dados e paginação
     */
    public static function paginateArray(
        array $allData,
        ?array $paginationParams = null,
        ?int $maxPerPage = null
    ): array {
        // Obtém parâmetros de paginação
        if ($paginationParams === null) {
            $paginationParams = self::getPaginationParams(null, $maxPerPage);
        }
        
        // Valida se há erros
        if (!empty($paginationParams['errors'])) {
            throw new \InvalidArgumentException('Parâmetros de paginação inválidos: ' . json_encode($paginationParams['errors']));
        }
        
        $page = $paginationParams['page'];
        $limit = $paginationParams['limit'];
        $offset = $paginationParams['offset'];
        
        $total = count($allData);
        $paginatedData = array_slice($allData, $offset, $limit);
        
        // Formata resposta
        return self::formatResponse($paginatedData, $total, $page, $limit);
    }
    
    /**
     * Calcula offset baseado em page e perPage
     * 
     * @param int $page Página atual
     * @param int $perPage Itens por página
     * @return int Offset calculado
     */
    public static function calculateOffset(int $page, int $perPage): int
    {
        return max(0, ($page - 1) * $perPage);
    }
    
    /**
     * Valida se uma página é válida baseado no total de registros
     * 
     * @param int $page Página a validar
     * @param int $total Total de registros
     * @param int $perPage Itens por página
     * @return bool True se válida
     */
    public static function isValidPage(int $page, int $total, int $perPage): bool
    {
        if ($page < 1) {
            return false;
        }
        
        if ($perPage <= 0) {
            return false;
        }
        
        $totalPages = (int)ceil($total / $perPage);
        return $page <= $totalPages;
    }
}

