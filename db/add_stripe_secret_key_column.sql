-- Adiciona coluna stripe_secret_key à tabela tenant_stripe_accounts
-- Execute este script no MySQL se a coluna não existir

-- Verifica se a coluna já existe antes de adicionar
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tenant_stripe_accounts'
    AND COLUMN_NAME = 'stripe_secret_key'
);

-- Se não existir, adiciona a coluna
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `tenant_stripe_accounts` 
     ADD COLUMN `stripe_secret_key` TEXT NULL COMMENT ''API Key secreta do Stripe do tenant (criptografada)'' AFTER `metadata`',
    'SELECT ''Coluna stripe_secret_key já existe. Nenhuma alteração necessária.'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se foi criada com sucesso
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ Coluna stripe_secret_key criada com sucesso!'
        ELSE '❌ Erro: Coluna stripe_secret_key não foi criada'
    END AS resultado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'tenant_stripe_accounts'
AND COLUMN_NAME = 'stripe_secret_key';

