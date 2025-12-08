<?php

/**
 * Script para executar a migration de criação da tabela tenant_rate_limits
 * 
 * Este script executa o SQL da migration usando a mesma conexão do sistema.
 */

// Carrega autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Carrega configurações
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
    Config::load();
}

use App\Utils\Database;
use App\Services\Logger;

echo "🚀 EXECUTANDO MIGRATION: tenant_rate_limits\n";
echo "================================================================================\n\n";

try {
    $db = Database::getInstance();
    
    // Lê o arquivo SQL
    $sqlFile = __DIR__ . '/../db/migrations/create_tenant_rate_limits_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new \RuntimeException("Arquivo de migration não encontrado: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new \RuntimeException("Erro ao ler arquivo de migration");
    }
    
    // Remove comentários SQL (-- comentário)
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Remove linhas vazias
    $sql = preg_replace('/^\s*[\r\n]/m', '', $sql);
    
    // Divide em comandos (separados por ;)
    $commands = array_filter(
        array_map('trim', explode(';', $sql)),
        function($cmd) {
            return !empty($cmd);
        }
    );
    
    echo "📋 Executando " . count($commands) . " comando(s) SQL...\n\n";
    
    $executed = 0;
    $errors = [];
    
    foreach ($commands as $index => $command) {
        if (empty(trim($command))) {
            continue;
        }
        
        try {
            echo "  [" . ($index + 1) . "] Executando comando...\n";
            $db->exec($command);
            $executed++;
            echo "      ✅ Comando executado com sucesso\n";
        } catch (\PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Se a tabela já existe, não é um erro crítico
            if (strpos($errorMsg, 'already exists') !== false || 
                strpos($errorMsg, 'Duplicate table') !== false) {
                echo "      ⚠️  Tabela já existe - pulando\n";
                $executed++;
            } else {
                echo "      ❌ Erro: {$errorMsg}\n";
                $errors[] = [
                    'command' => substr($command, 0, 100) . '...',
                    'error' => $errorMsg
                ];
            }
        }
    }
    
    echo "\n";
    echo "================================================================================\n";
    echo "📊 RESUMO DA MIGRATION\n";
    echo "================================================================================\n";
    echo "✅ Comandos executados: {$executed}\n";
    
    if (!empty($errors)) {
        echo "❌ Erros encontrados: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "   - {$error['error']}\n";
        }
    } else {
        echo "❌ Erros: 0\n";
    }
    
    // Verifica se a tabela foi criada
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'tenant_rate_limits'");
        if ($stmt->rowCount() > 0) {
            echo "\n✅ Tabela 'tenant_rate_limits' existe no banco de dados!\n";
            
            // Mostra estrutura da tabela
            $stmt = $db->query("DESCRIBE tenant_rate_limits");
            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo "\n📋 Estrutura da tabela:\n";
            echo "   " . str_pad("Campo", 25) . str_pad("Tipo", 30) . "Null  Key\n";
            echo "   " . str_repeat("-", 70) . "\n";
            foreach ($columns as $column) {
                echo "   " . str_pad($column['Field'], 25) . 
                     str_pad($column['Type'], 30) . 
                     str_pad($column['Null'], 5) . 
                     $column['Key'] . "\n";
            }
        } else {
            echo "\n⚠️  Tabela 'tenant_rate_limits' não foi encontrada no banco de dados.\n";
        }
    } catch (\PDOException $e) {
        echo "\n⚠️  Não foi possível verificar a tabela: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    if (!empty($errors)) {
        echo "⚠️  Migration concluída com erros. Verifique os erros acima.\n";
        exit(1);
    } else {
        echo "🎉 Migration executada com sucesso!\n";
        exit(0);
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

