<?php

namespace App\Utils;

use App\Utils\Database;
use App\Services\Logger;
use PDO;
use PDOException;

/**
 * Classe helper para gerenciar transações de banco de dados
 * 
 * Garante integridade de dados em operações que envolvem múltiplas tabelas
 * ou múltiplas operações de banco de dados.
 */
class Transaction
{
    /**
     * Executa callback dentro de uma transação
     * 
     * Se o callback lançar qualquer exceção, a transação será revertida automaticamente.
     * 
     * @param callable $callback Função a executar dentro da transação
     *                            Recebe a instância PDO como parâmetro
     * @return mixed Retorno do callback
     * @throws \Throwable Qualquer exceção lançada pelo callback
     */
    public static function execute(callable $callback): mixed
    {
        $db = Database::getInstance();
        
        // Verifica se já está em uma transação
        $alreadyInTransaction = $db->inTransaction();
        
        if ($alreadyInTransaction) {
            // Se já está em transação, apenas executa o callback
            // Isso permite transações aninhadas (não suportadas nativamente pelo MySQL)
            // mas permite que o código funcione sem erros
            try {
                return $callback($db);
            } catch (\Throwable $e) {
                // Re-lança a exceção para que a transação externa possa fazer rollback
                throw $e;
            }
        }
        
        try {
            $db->beginTransaction();
            
            $result = $callback($db);
            
            $db->commit();
            
            return $result;
        } catch (\Throwable $e) {
            // Faz rollback apenas se iniciou a transação
            if (!$alreadyInTransaction && $db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (PDOException $rollbackException) {
                    // Log erro de rollback, mas não mascarar a exceção original
                    Logger::error("Erro ao fazer rollback da transação", [
                        'rollback_error' => $rollbackException->getMessage(),
                        'original_error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // Log do erro que causou o rollback
            Logger::error("Transação revertida: " . $e->getMessage(), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-lança a exceção original
            throw $e;
        }
    }
    
    /**
     * Executa múltiplas operações em uma transação
     * 
     * Útil quando você tem uma lista de callables para executar
     * 
     * @param array $callbacks Array de callables a executar
     * @return array Array com os resultados de cada callback
     * @throws \Throwable Qualquer exceção lançada por qualquer callback
     */
    public static function executeMultiple(array $callbacks): array
    {
        return self::execute(function($db) use ($callbacks) {
            $results = [];
            foreach ($callbacks as $callback) {
                if (!is_callable($callback)) {
                    throw new \InvalidArgumentException('Todos os elementos devem ser callables');
                }
                $results[] = $callback($db);
            }
            return $results;
        });
    }
}

