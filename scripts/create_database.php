<?php

/**
 * Script para criar o banco de dados e todas as tabelas
 * 
 * Uso: php scripts/create_database.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;

// Carrega variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Configurações do banco
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'clinica_medica';

echo "========================================\n";
echo "Criação do Banco de Dados\n";
echo "========================================\n\n";

echo "Configurações:\n";
echo "  Host: {$dbHost}\n";
echo "  Usuário: {$dbUser}\n";
echo "  Banco: {$dbName}\n\n";

try {
    // Conecta ao MySQL sem especificar o banco
    $pdo = new PDO(
        "mysql:host={$dbHost};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "✓ Conectado ao MySQL\n";
    
    // Cria o banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Banco de dados '{$dbName}' criado/verificado\n\n";
    
    // Seleciona o banco
    $pdo->exec("USE `{$dbName}`");
    echo "✓ Banco de dados selecionado\n\n";
    
    // Lê o arquivo SQL
    $sqlFile = __DIR__ . '/../db/schema_completo.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comentários e comandos USE DATABASE (já estamos conectados)
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/^USE\s+.*;$/mi', '', $sql);
    
    // Divide em comandos individuais
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^CREATE DATABASE/i', $stmt);
        }
    );
    
    echo "Executando criação de tabelas...\n";
    echo "========================================\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        try {
            // Extrai o nome da tabela para exibir
            if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                echo "Criando tabela: {$tableName}... ";
            } else {
                echo "Executando comando... ";
            }
            
            $pdo->exec($statement);
            echo "✓\n";
            $successCount++;
        } catch (PDOException $e) {
            // Ignora erro se a tabela já existir
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ (já existe)\n";
            } else {
                echo "✗ ERRO: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n========================================\n";
    echo "Resumo:\n";
    echo "  ✓ Sucesso: {$successCount}\n";
    if ($errorCount > 0) {
        echo "  ✗ Erros: {$errorCount}\n";
    }
    echo "\n";
    
    // Verifica quais tabelas foram criadas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tabelas criadas no banco '{$dbName}':\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    
    echo "\n✓ Banco de dados criado com sucesso!\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

