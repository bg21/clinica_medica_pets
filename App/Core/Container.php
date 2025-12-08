<?php

namespace App\Core;

/**
 * Container simples de injeção de dependências
 * 
 * Suporta:
 * - Auto-resolve de dependências usando Reflection
 * - Bindings manuais (factory functions)
 * - Singleton pattern
 * - Resolução recursiva de dependências
 */
class Container
{
    private array $bindings = [];
    private array $singletons = [];
    
    /**
     * Registra uma classe ou factory
     * 
     * @param string $key Nome da classe ou identificador
     * @param callable|string $resolver Factory function ou nome da classe
     * @param bool $singleton Se true, cria apenas uma instância
     */
    public function bind(string $key, callable|string $resolver, bool $singleton = false): void
    {
        $this->bindings[$key] = [
            'resolver' => $resolver,
            'singleton' => $singleton
        ];
    }
    
    /**
     * Resolve uma dependência
     * 
     * @param string $key Nome da classe ou identificador
     * @return mixed Instância resolvida
     * @throws \RuntimeException Se não conseguir resolver
     */
    public function make(string $key): mixed
    {
        // Se não está registrado, tenta auto-resolve
        if (!isset($this->bindings[$key])) {
            return $this->autoResolve($key);
        }
        
        $binding = $this->bindings[$key];
        
        // Se é singleton e já existe, retorna a instância existente
        if ($binding['singleton'] && isset($this->singletons[$key])) {
            return $this->singletons[$key];
        }
        
        $resolver = $binding['resolver'];
        
        // Se é uma factory function, chama ela
        if (is_callable($resolver)) {
            $instance = $resolver($this);
        } else {
            // Se é uma string (nome de classe), tenta auto-resolve
            $instance = $this->autoResolve($resolver);
        }
        
        // Se é singleton, salva a instância
        if ($binding['singleton']) {
            $this->singletons[$key] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Auto-resolve dependências usando Reflection
     * 
     * @param string $class Nome da classe
     * @return object Instância da classe
     * @throws \RuntimeException Se não conseguir resolver
     */
    private function autoResolve(string $class): object
    {
        // Verifica se a classe existe
        if (!class_exists($class)) {
            throw new \RuntimeException("Classe {$class} não encontrada");
        }
        
        $reflection = new \ReflectionClass($class);
        
        // Verifica se é instanciável
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Classe {$class} não é instanciável (pode ser abstrata ou interface)");
        }
        
        // Se não tem construtor, instancia diretamente
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }
        
        // Resolve parâmetros do construtor
        $parameters = $constructor->getParameters();
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            // Se não tem type hint, não consegue resolver
            if ($type === null) {
                // Se tem valor padrão, usa ele
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                
                throw new \RuntimeException(
                    "Não é possível resolver dependência '{$parameter->getName()}' " .
                    "do construtor de {$class} (sem type hint)"
                );
            }
            
            // Só suporta ReflectionNamedType (não union types ou intersection types)
            if (!$type instanceof \ReflectionNamedType) {
                throw new \RuntimeException(
                    "Tipo complexo não suportado para '{$parameter->getName()}' " .
                    "do construtor de {$class}"
                );
            }
            
            $dependencyClass = $type->getName();
            
            // Se é built-in type (string, int, array, etc), não resolve
            if ($type->isBuiltin()) {
                // Se tem valor padrão, usa ele
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                
                throw new \RuntimeException(
                    "Não é possível resolver dependência built-in '{$parameter->getName()}' " .
                    "do construtor de {$class}"
                );
            }
            
            // Resolve recursivamente
            try {
                $dependencies[] = $this->make($dependencyClass);
            } catch (\RuntimeException $e) {
                // Se não conseguiu resolver e tem valor padrão, usa ele
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \RuntimeException(
                        "Não é possível resolver dependência '{$dependencyClass}' " .
                        "do construtor de {$class}: " . $e->getMessage()
                    );
                }
            }
        }
        
        // Cria instância com dependências resolvidas
        return $reflection->newInstanceArgs($dependencies);
    }
    
    /**
     * Verifica se uma dependência está registrada
     * 
     * @param string $key Nome da classe ou identificador
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->bindings[$key]);
    }
    
    /**
     * Limpa todos os bindings e singletons
     * Útil para testes
     */
    public function clear(): void
    {
        $this->bindings = [];
        $this->singletons = [];
    }
    
    /**
     * Obtém instância singleton do container (opcional)
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        static $instance = null;
        
        if ($instance === null) {
            $instance = new self();
        }
        
        return $instance;
    }
}

