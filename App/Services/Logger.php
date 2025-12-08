<?php

namespace App\Services;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use App\Handlers\DatabaseLogHandler;
use Config;

/**
 * Serviço de logging usando Monolog com rotação automática
 */
class Logger
{
    private static ?MonologLogger $instance = null;

    /**
     * Obtém instância única do logger
     */
    public static function getInstance(): MonologLogger
    {
        if (self::$instance === null) {
            // Configuração de logs
            $logPath = Config::get('LOG_PATH', 'logs/app.log');
            $logDir = __DIR__ . '/../../' . dirname($logPath);
            $logFile = basename($logPath);
            
            // Garante que o diretório de logs existe
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            
            // Remove extensão do arquivo (RotatingFileHandler adiciona data automaticamente)
            $logFile = preg_replace('/\.log$/', '', $logFile);
            $logFilePath = $logDir . '/' . $logFile . '.log';

            self::$instance = new MonologLogger('saas_payments');

            // ✅ OTIMIZAÇÃO: Nível de log baseado em ambiente (reduz verbosidade em produção)
            $logLevel = Config::isDevelopment() 
                ? MonologLogger::DEBUG 
                : MonologLogger::INFO; // Em produção, apenas INFO e acima
            
            // RotatingFileHandler: rotação diária, mantém 30 dias de logs
            $maxFiles = (int)Config::get('LOG_MAX_FILES', 30);
            $handler = new RotatingFileHandler(
                $logFilePath,
                $maxFiles, // Mantém 30 arquivos (30 dias)
                $logLevel, // ✅ Nível dinâmico baseado em ambiente
                true, // Bubble (propaga para outros handlers)
                0644  // Permissões do arquivo
            );
            
            $formatter = new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s'
            );
            $handler->setFormatter($formatter);

            self::$instance->pushHandler($handler);
            
            // ✅ TRACING: Adiciona handler para salvar logs no banco de dados
            // Apenas se LOG_DATABASE_ENABLED estiver habilitado
            if (Config::get('LOG_DATABASE_ENABLED', 'true') === 'true') {
                try {
                    // Converte nível de int para Level enum (Monolog 3.x)
                    $dbLogLevel = \Monolog\Level::fromValue($logLevel);
                    $dbHandler = new DatabaseLogHandler(
                        $dbLogLevel, // Mesmo nível do arquivo (Level enum)
                        true, // Bubble
                        10, // Buffer size (salva em lotes de 10)
                        5   // Flush interval (segundos)
                    );
                    self::$instance->pushHandler($dbHandler);
                } catch (\Exception $e) {
                    // Se falhar ao criar handler de banco, continua apenas com arquivo
                    // Não deve quebrar a aplicação se o banco estiver indisponível
                    error_log("Erro ao inicializar DatabaseLogHandler: " . $e->getMessage());
                }
            }
        }

        return self::$instance;
    }

    /**
     * Log de informação
     * Sanitiza contexto automaticamente
     */
    /**
     * Log de informação
     * Sanitiza contexto automaticamente
     * ✅ TRACING: Inclui request_id automaticamente
     */
    public static function info(string $message, array $context = []): void
    {
        $sanitizedContext = self::sanitizeContext($context);
        $contextWithTrace = self::addRequestId($sanitizedContext);
        self::getInstance()->info($message, $contextWithTrace);
    }

    /**
     * Log de erro
     * Sanitiza contexto automaticamente
     * ✅ TRACING: Inclui request_id automaticamente
     */
    public static function error(string $message, array $context = []): void
    {
        $sanitizedContext = self::sanitizeContext($context);
        $contextWithTrace = self::addRequestId($sanitizedContext);
        self::getInstance()->error($message, $contextWithTrace);
    }

    /**
     * Log de debug
     * Sanitiza contexto automaticamente
     * ✅ TRACING: Inclui request_id automaticamente
     */
    public static function debug(string $message, array $context = []): void
    {
        $sanitizedContext = self::sanitizeContext($context);
        $contextWithTrace = self::addRequestId($sanitizedContext);
        self::getInstance()->debug($message, $contextWithTrace);
    }

    /**
     * Log de warning
     * Sanitiza contexto automaticamente
     * ✅ TRACING: Inclui request_id automaticamente
     */
    public static function warning(string $message, array $context = []): void
    {
        $sanitizedContext = self::sanitizeContext($context);
        $contextWithTrace = self::addRequestId($sanitizedContext);
        self::getInstance()->warning($message, $contextWithTrace);
    }
    
    /**
     * Adiciona contexto automático aos logs
     * 
     * Inclui automaticamente: request_id, tenant_id, user_id, ip_address
     * 
     * @param array $context Contexto original
     * @return array Contexto enriquecido
     */
    private static function addRequestId(array $context): array
    {
        // ✅ NOVO: Adiciona request_id se disponível
        $requestId = \Flight::get('request_id');
        if ($requestId !== null && !isset($context['request_id'])) {
            $context['request_id'] = $requestId;
        }
        
        // ✅ NOVO: Adiciona tenant_id se disponível
        $tenantId = \Flight::get('tenant_id');
        if ($tenantId !== null && !isset($context['tenant_id'])) {
            $context['tenant_id'] = $tenantId;
        }
        
        // ✅ NOVO: Adiciona user_id se disponível
        $userId = \Flight::get('user_id');
        if ($userId !== null && !isset($context['user_id'])) {
            $context['user_id'] = $userId;
        }
        
        // ✅ NOVO: Adiciona ip_address se disponível
        $ipAddress = self::getClientIp();
        if ($ipAddress !== null && !isset($context['ip_address'])) {
            $context['ip_address'] = $ipAddress;
        }
        
        return $context;
    }
    
    /**
     * Obtém IP do cliente
     * 
     * @return string|null IP do cliente ou null se não disponível
     */
    private static function getClientIp(): ?string
    {
        // Verifica vários headers comuns de proxy
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',        // Nginx
            'HTTP_X_FORWARDED_FOR',  // Proxies
            'REMOTE_ADDR'            // IP direto
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Se for X-Forwarded-For, pega o primeiro IP
                if ($header === 'HTTP_X_FORWARDED_FOR') {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Valida IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        // Fallback para REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    /**
     * Sanitiza contexto de log removendo dados sensíveis
     * 
     * @param array $context Contexto original
     * @return array Contexto sanitizado
     */
    private static function sanitizeContext(array $context): array
    {
        return \App\Utils\ErrorHandler::sanitizeContext($context);
    }
}

