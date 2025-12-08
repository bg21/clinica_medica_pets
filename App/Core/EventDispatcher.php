<?php

namespace App\Core;

use App\Services\Logger;

/**
 * Sistema de eventos (Event Dispatcher)
 * 
 * Permite desacoplar ações através de eventos e listeners.
 * Facilita extensibilidade e testes.
 */
class EventDispatcher
{
    /**
     * Array de listeners registrados por evento
     * 
     * @var array<string, array<callable>>
     */
    private array $listeners = [];
    
    /**
     * Registra um listener para um evento
     * 
     * @param string $event Nome do evento (ex: 'user.created')
     * @param callable $listener Função a ser executada quando o evento for disparado
     * @return void
     */
    public function listen(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        
        $this->listeners[$event][] = $listener;
    }
    
    /**
     * Remove um listener específico de um evento
     * 
     * @param string $event Nome do evento
     * @param callable $listener Listener a ser removido
     * @return bool True se foi removido, false caso contrário
     */
    public function removeListener(string $event, callable $listener): bool
    {
        if (!isset($this->listeners[$event])) {
            return false;
        }
        
        $key = array_search($listener, $this->listeners[$event], true);
        if ($key !== false) {
            unset($this->listeners[$event][$key]);
            $this->listeners[$event] = array_values($this->listeners[$event]); // Reindexa
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove todos os listeners de um evento
     * 
     * @param string $event Nome do evento
     * @return void
     */
    public function removeAllListeners(string $event): void
    {
        unset($this->listeners[$event]);
    }
    
    /**
     * Dispara um evento, executando todos os listeners registrados
     * 
     * @param string $event Nome do evento
     * @param array $payload Dados a serem passados para os listeners
     * @return void
     */
    public function dispatch(string $event, array $payload = []): void
    {
        if (!isset($this->listeners[$event]) || empty($this->listeners[$event])) {
            return;
        }
        
        foreach ($this->listeners[$event] as $listener) {
            try {
                $listener($payload);
            } catch (\Throwable $e) {
                // Log erro, mas não interrompe execução de outros listeners
                Logger::error("Erro ao executar listener do evento '{$event}'", [
                    'event' => $event,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
    
    /**
     * Verifica se há listeners registrados para um evento
     * 
     * @param string $event Nome do evento
     * @return bool
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }
    
    /**
     * Retorna todos os eventos que têm listeners registrados
     * 
     * @return array
     */
    public function getRegisteredEvents(): array
    {
        return array_keys($this->listeners);
    }
    
    /**
     * Retorna quantidade de listeners para um evento
     * 
     * @param string $event Nome do evento
     * @return int
     */
    public function getListenerCount(string $event): int
    {
        if (!isset($this->listeners[$event])) {
            return 0;
        }
        
        return count($this->listeners[$event]);
    }
    
    /**
     * Limpa todos os listeners (útil para testes)
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->listeners = [];
    }
}

