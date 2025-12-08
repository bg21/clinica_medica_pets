<?php
/**
 * Script de Limpeza Completa de Cache
 * 
 * Limpa:
 * - Cache do PHP (opcache)
 * - Cache de arquivos estáticos
 * - Cache do navegador (via headers)
 * - Arquivos temporários
 */

echo "🧹 LIMPEZA COMPLETA DE CACHE\n";
echo "============================================================\n\n";

$cleared = [];
$errors = [];

// 1. Limpar OPcache do PHP (se estiver habilitado)
echo "1️⃣ Limpando OPcache do PHP...\n";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        $cleared[] = "OPcache do PHP";
        echo "   ✅ OPcache limpo com sucesso\n";
    } else {
        $errors[] = "Falha ao limpar OPcache";
        echo "   ⚠️  OPcache não pôde ser limpo (pode não estar habilitado)\n";
    }
} else {
    echo "   ℹ️  OPcache não está disponível\n";
}

// 2. Limpar cache de arquivos estáticos (se houver pasta cache)
echo "\n2️⃣ Verificando cache de arquivos estáticos...\n";
$cacheDirs = [
    __DIR__ . '/../storage/cache',
    __DIR__ . '/../public/cache',
    __DIR__ . '/../cache',
    __DIR__ . '/../tmp',
    __DIR__ . '/../temp'
];

foreach ($cacheDirs as $cacheDir) {
    if (is_dir($cacheDir)) {
        echo "   Limpando: {$cacheDir}...\n";
        $files = glob($cacheDir . '/*');
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (@unlink($file)) {
                    $count++;
                }
            } elseif (is_dir($file)) {
                // Remove diretórios recursivamente
                $dirFiles = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($file, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($dirFiles as $dirFile) {
                    if ($dirFile->isDir()) {
                        @rmdir($dirFile->getRealPath());
                    } else {
                        @unlink($dirFile->getRealPath());
                        $count++;
                    }
                }
                @rmdir($file);
            }
        }
        if ($count > 0) {
            $cleared[] = "Cache de arquivos ({$count} arquivos removidos)";
            echo "   ✅ {$count} arquivo(s) removido(s)\n";
        } else {
            echo "   ℹ️  Pasta vazia\n";
        }
    }
}

// 3. Limpar cache do localStorage (via arquivo de instruções)
echo "\n3️⃣ Gerando instruções para limpar cache do navegador...\n";
$cacheClearFile = __DIR__ . '/../public/cache_clear.txt';
file_put_contents($cacheClearFile, date('Y-m-d H:i:s') . "\n");
$cleared[] = "Arquivo de instruções de cache criado";
echo "   ✅ Arquivo de instruções criado\n";

// 4. Limpar arquivos de sessão antigos (opcional)
echo "\n4️⃣ Verificando sessões antigas...\n";
if (function_exists('session_save_path')) {
    $sessionPath = session_save_path();
    if ($sessionPath && is_dir($sessionPath)) {
        $sessionFiles = glob($sessionPath . '/sess_*');
        $oldSessions = 0;
        $now = time();
        foreach ($sessionFiles as $sessionFile) {
            // Remove sessões com mais de 24 horas
            if (filemtime($sessionFile) < ($now - 86400)) {
                @unlink($sessionFile);
                $oldSessions++;
            }
        }
        if ($oldSessions > 0) {
            $cleared[] = "Sessões antigas ({$oldSessions} removidas)";
            echo "   ✅ {$oldSessions} sessão(ões) antiga(s) removida(s)\n";
        } else {
            echo "   ℹ️  Nenhuma sessão antiga encontrada\n";
        }
    }
}

// 5. Limpar logs antigos (opcional - apenas se muito grandes)
echo "\n5️⃣ Verificando logs grandes...\n";
$logDirs = [
    __DIR__ . '/../storage/logs',
    __DIR__ . '/../logs'
];

foreach ($logDirs as $logDir) {
    if (is_dir($logDir)) {
        $logFiles = glob($logDir . '/*.log');
        $largeLogs = 0;
        foreach ($logFiles as $logFile) {
            // Se o log tiver mais de 10MB, trunca
            if (filesize($logFile) > 10 * 1024 * 1024) {
                file_put_contents($logFile, "Log truncado em " . date('Y-m-d H:i:s') . "\n");
                $largeLogs++;
            }
        }
        if ($largeLogs > 0) {
            $cleared[] = "Logs grandes truncados ({$largeLogs} arquivos)";
            echo "   ✅ {$largeLogs} log(s) grande(s) truncado(s)\n";
        }
    }
}

// 6. Limpar cache de versão de arquivos (timestamps)
echo "\n6️⃣ Atualizando timestamps de arquivos estáticos...\n";
$staticFiles = [
    __DIR__ . '/../public/css/dashboard.css',
    __DIR__ . '/../public/app/dashboard.js',
    __DIR__ . '/../public/app/security.js',
    __DIR__ . '/../public/app/validations.js'
];

foreach ($staticFiles as $file) {
    if (file_exists($file)) {
        touch($file);
    }
}
$cleared[] = "Timestamps de arquivos estáticos atualizados";
echo "   ✅ Timestamps atualizados\n";

// 7. Limpar cache do Redis (se disponível)
echo "\n7️⃣ Limpando cache do Redis...\n";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Tenta conectar ao Redis diretamente
    $redisUrl = 'redis://127.0.0.1:6379'; // URL padrão
    
    // Tenta ler do arquivo de config se existir
    $configFile = __DIR__ . '/../config/config.php';
    if (file_exists($configFile)) {
        $config = include $configFile;
        if (isset($config['REDIS_URL'])) {
            $redisUrl = $config['REDIS_URL'];
        }
    }
    
    if (class_exists('Predis\Client')) {
        try {
            $redis = new \Predis\Client($redisUrl, [
                'parameters' => [
                    'timeout' => 1.0,
                    'read_timeout' => 1.0,
                    'write_timeout' => 1.0
                ]
            ]);
            
            // Testa conexão
            $redis->ping();
            
            $keys = $redis->keys('*');
            if (count($keys) > 0) {
                $redis->del($keys);
                $cleared[] = "Cache do Redis (" . count($keys) . " chaves removidas)";
                echo "   ✅ " . count($keys) . " chave(s) removida(s) do Redis\n";
            } else {
                echo "   ℹ️  Nenhuma chave encontrada no Redis\n";
            }
        } catch (\Exception $e) {
            echo "   ℹ️  Redis não disponível ou não configurado: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ℹ️  Predis não está instalado\n";
    }
} catch (\Exception $e) {
    echo "   ℹ️  Não foi possível limpar cache do Redis: " . $e->getMessage() . "\n";
}

// 8. Limpar cache do Composer (se necessário)
echo "\n8️⃣ Verificando cache do Composer...\n";
if (file_exists(__DIR__ . '/../composer.json')) {
    $composerCache = getenv('COMPOSER_CACHE_DIR') ?: (getenv('HOME') . '/.composer/cache');
    if (is_dir($composerCache)) {
        echo "   ℹ️  Cache do Composer encontrado (não será limpo automaticamente)\n";
        echo "      Para limpar: composer clear-cache\n";
    }
}

// Resumo
echo "\n============================================================\n";
echo "📊 RESUMO DA LIMPEZA\n";
echo "============================================================\n\n";

if (count($cleared) > 0) {
    echo "✅ Caches limpos:\n";
    foreach ($cleared as $item) {
        echo "   - {$item}\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "⚠️  Avisos:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
    echo "\n";
}

echo "🎯 PRÓXIMOS PASSOS:\n";
echo "   1. Limpe o cache do navegador (Ctrl+Shift+Delete)\n";
echo "   2. Faça um hard refresh (Ctrl+F5)\n";
echo "   3. Teste a aplicação novamente\n";
echo "\n";

echo "✅ Limpeza de cache concluída!\n";

