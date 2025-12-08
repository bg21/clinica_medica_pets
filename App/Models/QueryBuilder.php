<?php

namespace App\Models;

use PDO;
use App\Utils\PaginationHelper;

/**
 * Query Builder fluente para construir queries SQL de forma segura e intuitiva
 * 
 * Permite construir queries complexas usando métodos encadeados:
 * 
 * Exemplo:
 * $results = $model->query()
 *     ->where('tenant_id', $tenantId)
 *     ->where('status', 'active')
 *     ->whereBetween('created_at', $startDate, $endDate)
 *     ->whereIn('id', [1, 2, 3])
 *     ->orderBy('created_at', 'DESC')
 *     ->limit(20)
 *     ->get();
 */
class QueryBuilder
{
    private PDO $db;
    private string $table;
    private bool $usesSoftDeletes;
    private string $primaryKey;
    
    private array $wheres = [];
    private array $whereIns = [];
    private array $whereBetweens = [];
    private array $whereNulls = [];
    private array $whereNotNulls = [];
    private array $orWheres = [];
    private array $orderBys = [];
    private ?int $limitValue = null;
    private int $offsetValue = 0;
    private array $selectFields = ['*'];
    private array $withRelations = [];
    private array $params = [];
    private int $paramCounter = 0;
    
    /**
     * @param PDO $db Instância do PDO
     * @param string $table Nome da tabela
     * @param bool $usesSoftDeletes Se o model usa soft deletes
     * @param string $primaryKey Chave primária
     */
    public function __construct(PDO $db, string $table, bool $usesSoftDeletes, string $primaryKey = 'id')
    {
        $this->db = $db;
        $this->table = $table;
        $this->usesSoftDeletes = $usesSoftDeletes;
        $this->primaryKey = $primaryKey;
    }
    
    /**
     * Adiciona condição WHERE simples
     * 
     * @param string $column Coluna
     * @param mixed $value Valor (ou operador se $value for null)
     * @param mixed|null $value2 Valor (se $value for operador)
     * @return self
     */
    public function where(string $column, $value, $value2 = null): self
    {
        // Suporta where('column', '>', value) ou where('column', value)
        if ($value2 !== null) {
            $operator = $value;
            $value = $value2;
        } else {
            $operator = '=';
        }
        
        // Valida operador
        $allowedOperators = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'];
        if (!in_array(strtoupper($operator), $allowedOperators, true)) {
            $operator = '=';
        }
        
        $paramKey = $this->getParamKey($column);
        $this->wheres[] = [
            'column' => $this->sanitizeColumn($column),
            'operator' => strtoupper($operator),
            'value' => $value,
            'param' => $paramKey
        ];
        
        $this->params[$paramKey] = $value;
        
        return $this;
    }
    
    /**
     * Adiciona condição WHERE IN
     * 
     * @param string $column Coluna
     * @param array $values Array de valores
     * @return self
     */
    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            // Se array vazio, adiciona condição impossível
            $this->wheres[] = [
                'type' => 'impossible',
                'column' => $this->sanitizeColumn($column)
            ];
            return $this;
        }
        
        $paramKeys = [];
        foreach ($values as $index => $value) {
            $paramKey = $this->getParamKey($column . '_in_' . $index);
            $paramKeys[] = ":{$paramKey}";
            $this->params[$paramKey] = $value;
        }
        
        $this->whereIns[] = [
            'column' => $this->sanitizeColumn($column),
            'paramKeys' => $paramKeys
        ];
        
        return $this;
    }
    
    /**
     * Adiciona condição WHERE NOT IN
     * 
     * @param string $column Coluna
     * @param array $values Array de valores
     * @return self
     */
    public function whereNotIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this; // NOT IN com array vazio = sempre verdadeiro
        }
        
        $paramKeys = [];
        foreach ($values as $index => $value) {
            $paramKey = $this->getParamKey($column . '_notin_' . $index);
            $paramKeys[] = ":{$paramKey}";
            $this->params[$paramKey] = $value;
        }
        
        $this->whereIns[] = [
            'column' => $this->sanitizeColumn($column),
            'paramKeys' => $paramKeys,
            'not' => true
        ];
        
        return $this;
    }
    
    /**
     * Adiciona condição WHERE BETWEEN
     * 
     * @param string $column Coluna
     * @param mixed $start Valor inicial
     * @param mixed $end Valor final
     * @return self
     */
    public function whereBetween(string $column, $start, $end): self
    {
        $startParam = $this->getParamKey($column . '_between_start');
        $endParam = $this->getParamKey($column . '_between_end');
        
        $this->whereBetweens[] = [
            'column' => $this->sanitizeColumn($column),
            'startParam' => $startParam,
            'endParam' => $endParam
        ];
        
        $this->params[$startParam] = $start;
        $this->params[$endParam] = $end;
        
        return $this;
    }
    
    /**
     * Adiciona condição WHERE NULL
     * 
     * @param string $column Coluna
     * @return self
     */
    public function whereNull(string $column): self
    {
        $this->whereNulls[] = $this->sanitizeColumn($column);
        return $this;
    }
    
    /**
     * Adiciona condição WHERE NOT NULL
     * 
     * @param string $column Coluna
     * @return self
     */
    public function whereNotNull(string $column): self
    {
        $this->whereNotNulls[] = $this->sanitizeColumn($column);
        return $this;
    }
    
    /**
     * Adiciona condição OR WHERE
     * 
     * @param string $column Coluna
     * @param mixed $value Valor
     * @param string|null $operator Operador (padrão: '=')
     * @return self
     */
    public function orWhere(string $column, $value, ?string $operator = null): self
    {
        $operator = $operator ?? '=';
        
        // Valida operador
        $allowedOperators = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'];
        if (!in_array(strtoupper($operator), $allowedOperators, true)) {
            $operator = '=';
        }
        
        $paramKey = $this->getParamKey($column . '_or');
        $this->orWheres[] = [
            'column' => $this->sanitizeColumn($column),
            'operator' => strtoupper($operator),
            'value' => $value,
            'param' => $paramKey
        ];
        
        $this->params[$paramKey] = $value;
        
        return $this;
    }
    
    /**
     * Adiciona ordenação
     * 
     * @param string $column Coluna
     * @param string $direction Direção (ASC ou DESC)
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC';
        }
        
        $this->orderBys[] = [
            'column' => $this->sanitizeColumn($column),
            'direction' => $direction
        ];
        
        return $this;
    }
    
    /**
     * Define limite
     * 
     * @param int $limit Limite
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limitValue = max(1, $limit);
        return $this;
    }
    
    /**
     * Define offset
     * 
     * @param int $offset Offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offsetValue = max(0, $offset);
        return $this;
    }
    
    /**
     * Define campos a selecionar
     * 
     * @param array|string $fields Campos (array ou string separada por vírgula)
     * @return self
     */
    public function select($fields): self
    {
        if (is_string($fields)) {
            $fields = array_map('trim', explode(',', $fields));
        }
        
        if (!empty($fields)) {
            $this->selectFields = array_map(function($field) {
                return $this->sanitizeColumn(trim($field));
            }, $fields);
        }
        
        return $this;
    }
    
    /**
     * Define relacionamentos para eager loading (placeholder - será implementado depois)
     * 
     * @param array|string $relations Relacionamentos
     * @return self
     */
    public function with($relations): self
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }
        
        $this->withRelations = array_merge($this->withRelations, $relations);
        return $this;
    }
    
    /**
     * Executa query e retorna todos os resultados
     * 
     * @return array
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Aplica eager loading se necessário
        if (!empty($this->withRelations) && !empty($results)) {
            $results = $this->loadRelations($results);
        }
        
        return $results ?: [];
    }
    
    /**
     * Executa query e retorna apenas o primeiro resultado
     * 
     * @return array|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }
    
    /**
     * Conta registros que correspondem às condições
     * 
     * @return int
     */
    public function count(): int
    {
        $sql = $this->buildCountQuery();
        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, false); // Não inclui limit/offset no count
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Retorna resultados paginados usando PaginationHelper
     * 
     * @param array|null $paginationParams Parâmetros de paginação
     * @param int|null $maxPerPage Limite máximo
     * @return array
     */
    public function paginate(?array $paginationParams = null, ?int $maxPerPage = null): array
    {
        // Obtém parâmetros de paginação
        if ($paginationParams === null) {
            $paginationParams = PaginationHelper::getPaginationParams(null, $maxPerPage);
        }
        
        // Valida se há erros
        if (!empty($paginationParams['errors'])) {
            throw new \InvalidArgumentException('Parâmetros de paginação inválidos: ' . json_encode($paginationParams['errors']));
        }
        
        $page = $paginationParams['page'];
        $limit = $paginationParams['limit'];
        $offset = $paginationParams['offset'];
        
        // Obtém total ANTES de aplicar limite/offset
        $total = $this->count();
        
        // Aplica limite e offset para obter dados
        $this->limit($limit);
        $this->offset($offset);
        
        // Obtém dados
        $data = $this->get();
        
        // Formata resposta
        return PaginationHelper::formatResponse($data, $total, $page, $limit);
    }
    
    /**
     * Constrói query SELECT
     * 
     * @return string
     */
    private function buildSelectQuery(): string
    {
        $fields = implode(', ', array_map(fn($f) => $f === '*' ? '*' : "`{$f}`", $this->selectFields));
        $sql = "SELECT {$fields} FROM `{$this->table}`";
        
        $whereClause = $this->buildWhereClause();
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }
        
        if (!empty($this->orderBys)) {
            $orders = array_map(fn($o) => "`{$o['column']}` {$o['direction']}", $this->orderBys);
            $sql .= " ORDER BY " . implode(', ', $orders);
        }
        
        if ($this->limitValue !== null) {
            $sql .= " LIMIT :limit";
            if ($this->offsetValue > 0) {
                $sql .= " OFFSET :offset";
            }
        }
        
        return $sql;
    }
    
    /**
     * Constrói query COUNT
     * 
     * @return string
     */
    private function buildCountQuery(): string
    {
        $sql = "SELECT COUNT(*) as count FROM `{$this->table}`";
        
        $whereClause = $this->buildWhereClause();
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }
        
        // COUNT não usa LIMIT/OFFSET
        return $sql;
    }
    
    /**
     * Constrói cláusula WHERE
     * 
     * @return string
     */
    private function buildWhereClause(): string
    {
        $conditions = [];
        
        // Soft deletes
        if ($this->usesSoftDeletes) {
            $conditions[] = "`deleted_at` IS NULL";
        }
        
        // WHERE simples
        foreach ($this->wheres as $where) {
            if (isset($where['type']) && $where['type'] === 'impossible') {
                $conditions[] = "1 = 0"; // Condição impossível
            } else {
                $conditions[] = "`{$where['column']}` {$where['operator']} :{$where['param']}";
            }
        }
        
        // WHERE IN
        foreach ($this->whereIns as $whereIn) {
            $operator = isset($whereIn['not']) && $whereIn['not'] ? 'NOT IN' : 'IN';
            $conditions[] = "`{$whereIn['column']}` {$operator} (" . implode(', ', $whereIn['paramKeys']) . ")";
        }
        
        // WHERE BETWEEN
        foreach ($this->whereBetweens as $whereBetween) {
            $conditions[] = "`{$whereBetween['column']}` BETWEEN :{$whereBetween['startParam']} AND :{$whereBetween['endParam']}";
        }
        
        // WHERE NULL
        foreach ($this->whereNulls as $column) {
            $conditions[] = "`{$column}` IS NULL";
        }
        
        // WHERE NOT NULL
        foreach ($this->whereNotNulls as $column) {
            $conditions[] = "`{$column}` IS NOT NULL";
        }
        
        // OR WHERE
        if (!empty($this->orWheres)) {
            $orConditions = [];
            foreach ($this->orWheres as $orWhere) {
                $orConditions[] = "`{$orWhere['column']}` {$orWhere['operator']} :{$orWhere['param']}";
            }
            $conditions[] = "(" . implode(' OR ', $orConditions) . ")";
        }
        
        return implode(' AND ', $conditions);
    }
    
    /**
     * Faz bind dos parâmetros no statement
     * 
     * @param \PDOStatement $stmt
     * @param bool $includeLimitOffset Se deve incluir limit/offset (padrão: true)
     * @return void
     */
    private function bindParams(\PDOStatement $stmt, bool $includeLimitOffset = true): void
    {
        foreach ($this->params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        if ($includeLimitOffset && $this->limitValue !== null) {
            $stmt->bindValue(':limit', $this->limitValue, PDO::PARAM_INT);
            if ($this->offsetValue > 0) {
                $stmt->bindValue(':offset', $this->offsetValue, PDO::PARAM_INT);
            }
        }
    }
    
    /**
     * Carrega relacionamentos (eager loading) - placeholder
     * 
     * @param array $results Resultados
     * @return array
     */
    private function loadRelations(array $results): array
    {
        // TODO: Implementar eager loading de relacionamentos
        // Por enquanto, apenas retorna os resultados
        return $results;
    }
    
    /**
     * Gera chave única para parâmetro
     * 
     * @param string $base Nome base
     * @return string
     */
    private function getParamKey(string $base): string
    {
        $this->paramCounter++;
        $key = str_replace(['.', '-'], '_', $base) . '_' . $this->paramCounter;
        return preg_replace('/[^a-zA-Z0-9_]/', '', $key);
    }
    
    /**
     * Sanitiza nome de coluna
     * 
     * @param string $column Nome da coluna
     * @return string
     */
    private function sanitizeColumn(string $column): string
    {
        // Remove caracteres perigosos, mantém apenas alfanuméricos, underscore e ponto
        return preg_replace('/[^a-zA-Z0-9_.]/', '', $column);
    }
}

