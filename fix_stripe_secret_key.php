<?php
/**
 * Script para adicionar a coluna stripe_secret_key à tabela tenant_stripe_accounts
 * Execute: php fix_stripe_secret_key.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Carrega configurações
require_once __DIR__ . '/config/config.php';
\Config::load();

use App\Utils\Database;

echo "=== Adicionando coluna stripe_secret_key ===\n\n";

try {
    $db = Database::getInstance();
    
    // Verifica se a coluna já existe
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'tenant_stripe_accounts'
        AND COLUMN_NAME = 'stripe_secret_key'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "✅ Coluna 'stripe_secret_key' já existe. Nenhuma alteração necessária.\n";
        exit(0);
    }
    
    echo "Coluna não encontrada. Adicionando...\n";
    
    // Adiciona a coluna
    $db->exec("
        ALTER TABLE `tenant_stripe_accounts` 
        ADD COLUMN `stripe_secret_key` TEXT NULL 
        COMMENT 'API Key secreta do Stripe do tenant (criptografada)' 
        AFTER `metadata`
    ");
    
    echo "✅ Coluna 'stripe_secret_key' adicionada com sucesso!\n\n";
    
    // Verifica novamente
    $stmt = $db->query("
        SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_COMMENT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'tenant_stripe_accounts'
        AND COLUMN_NAME = 'stripe_secret_key'
    ");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "Detalhes da coluna:\n";
        echo "  - Nome: {$column['COLUMN_NAME']}\n";
        echo "  - Tipo: {$column['DATA_TYPE']}\n";
        echo "  - Null: {$column['IS_NULLABLE']}\n";
        echo "  - Comentário: {$column['COLUMN_COMMENT']}\n";
    }
    
    echo "\n✅ Pronto! Agora você pode salvar a API key do Stripe.\n";
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

