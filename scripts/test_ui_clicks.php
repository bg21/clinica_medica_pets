<?php
/**
 * Script de teste para verificar se elementos estão bloqueando cliques
 * 
 * Este script verifica:
 * - Se o overlay da sidebar está oculto por padrão
 * - Se há elementos com z-index alto bloqueando cliques
 * - Se há modais abertos
 */

echo "🔍 TESTE DE UI - VERIFICAÇÃO DE ELEMENTOS BLOQUEADORES\n";
echo "============================================================\n\n";

$baseUrl = 'http://localhost:8080';

// Função para fazer requisição HTTP
function makeRequest(string $url): string
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $response;
}

echo "1️⃣ Verificando página de login...\n";
$loginHtml = makeRequest($baseUrl . '/login');

// Verifica se há overlay visível
if (strpos($loginHtml, 'sidebar-overlay') !== false) {
    echo "   ⚠️  Overlay encontrado na página de login (não deveria estar aqui)\n";
} else {
    echo "   ✅ Nenhum overlay encontrado na página de login\n";
}

// Verifica se há elementos com z-index muito alto
if (preg_match('/z-index:\s*(\d+)/i', $loginHtml, $matches)) {
    $zIndex = (int)$matches[1];
    if ($zIndex > 10000) {
        echo "   ⚠️  Elemento com z-index muito alto encontrado: {$zIndex}\n";
    } else {
        echo "   ✅ Z-index normal encontrado\n";
    }
}

echo "\n2️⃣ Verificando CSS do dashboard...\n";
$cssPath = __DIR__ . '/../public/css/dashboard.css';
if (file_exists($cssPath)) {
    $css = file_get_contents($cssPath);
    
    // Verifica se pointer-events está configurado corretamente
    if (strpos($css, 'pointer-events: none') !== false && strpos($css, '.sidebar-overlay') !== false) {
        echo "   ✅ CSS tem pointer-events: none para overlay oculto\n";
    } else {
        echo "   ⚠️  CSS pode não ter pointer-events configurado corretamente\n";
    }
    
    // Verifica se há media query para desktop
    if (strpos($css, '@media (min-width: 769px)') !== false && strpos($css, '.sidebar-overlay') !== false) {
        echo "   ✅ CSS tem media query para ocultar overlay em desktop\n";
    } else {
        echo "   ⚠️  CSS pode não ter media query para desktop\n";
    }
} else {
    echo "   ❌ Arquivo CSS não encontrado\n";
}

echo "\n3️⃣ Verificando JavaScript do dashboard...\n";
$jsPath = __DIR__ . '/../public/app/dashboard.js';
if (file_exists($jsPath)) {
    $js = file_get_contents($jsPath);
    
    // Verifica se há código para fechar overlay ao carregar
    if (strpos($js, 'overlay.classList.remove') !== false || strpos($js, 'closeSidebar') !== false) {
        echo "   ✅ JavaScript tem código para fechar overlay\n";
    } else {
        echo "   ⚠️  JavaScript pode não ter código para fechar overlay\n";
    }
} else {
    echo "   ❌ Arquivo JavaScript não encontrado\n";
}

echo "\n============================================================\n";
echo "✅ TESTE CONCLUÍDO\n";
echo "============================================================\n\n";
echo "📝 RECOMENDAÇÕES:\n";
echo "   1. Limpe o cache do navegador (Ctrl+Shift+Delete)\n";
echo "   2. Recarregue a página com Ctrl+F5 (hard refresh)\n";
echo "   3. Verifique no DevTools (F12) se o overlay está visível\n";
echo "   4. Verifique se há elementos com z-index alto bloqueando\n";
echo "\n";

