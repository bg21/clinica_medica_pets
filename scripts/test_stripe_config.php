<?php

/**
 * Script para testar configuração do Stripe
 * 
 * Uso: php scripts/test_stripe_config.php
 * 
 * Testa:
 * - Se STRIPE_SECRET está configurado
 * - Se a chave é válida
 * - Se STRIPE_WEBHOOK_SECRET está configurado (opcional)
 * - Conectividade com a API Stripe
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

echo "========================================\n";
echo "Teste de Configuração Stripe\n";
echo "========================================\n\n";

// Testa STRIPE_SECRET
echo "1. Testando STRIPE_SECRET...\n";
$secret = Config::get('STRIPE_SECRET');

if (empty($secret)) {
    echo "   ❌ STRIPE_SECRET não configurado no .env\n";
    echo "   💡 Adicione: STRIPE_SECRET=sk_test_xxx no arquivo .env\n\n";
    exit(1);
}

echo "   ✅ STRIPE_SECRET encontrado\n";
echo "   📝 Prefixo: " . substr($secret, 0, 7) . "...\n";

// Verifica formato
if (!preg_match('/^sk_(test|live)_/', $secret)) {
    echo "   ⚠️  AVISO: Formato da chave pode estar incorreto\n";
    echo "   💡 Deve começar com 'sk_test_' ou 'sk_live_'\n\n";
} else {
    $mode = strpos($secret, 'sk_test_') === 0 ? 'TEST' : 'LIVE';
    echo "   📊 Modo: {$mode}\n";
}

// Testa se a chave é válida
echo "\n2. Testando conectividade com Stripe API...\n";
try {
    $stripe = new \Stripe\StripeClient($secret);
    $account = $stripe->accounts->retrieve();
    
    echo "   ✅ Conexão bem-sucedida!\n";
    echo "   📝 Conta ID: {$account->id}\n";
    echo "   📝 País: {$account->country}\n";
    echo "   📝 Email: " . ($account->email ?? 'N/A') . "\n";
    
} catch (\Stripe\Exception\AuthenticationException $e) {
    echo "   ❌ Erro de autenticação: {$e->getMessage()}\n";
    echo "   💡 Verifique se a chave está correta e ativa no Dashboard Stripe\n\n";
    exit(1);
} catch (\Stripe\Exception\ApiConnectionException $e) {
    echo "   ❌ Erro de conexão: {$e->getMessage()}\n";
    echo "   💡 Verifique sua conexão com a internet\n\n";
    exit(1);
} catch (\Exception $e) {
    echo "   ❌ Erro inesperado: {$e->getMessage()}\n\n";
    exit(1);
}

// Testa STRIPE_WEBHOOK_SECRET (opcional)
echo "\n3. Testando STRIPE_WEBHOOK_SECRET...\n";
$webhookSecret = Config::get('STRIPE_WEBHOOK_SECRET');

if (empty($webhookSecret)) {
    echo "   ⚠️  STRIPE_WEBHOOK_SECRET não configurado (opcional para testes)\n";
    echo "   💡 Adicione: STRIPE_WEBHOOK_SECRET=whsec_xxx no arquivo .env\n";
    echo "   💡 Obtenha em: Dashboard Stripe > Developers > Webhooks\n";
} else {
    echo "   ✅ STRIPE_WEBHOOK_SECRET encontrado\n";
    echo "   📝 Prefixo: " . substr($webhookSecret, 0, 7) . "...\n";
    
    if (!preg_match('/^whsec_/', $webhookSecret)) {
        echo "   ⚠️  AVISO: Formato pode estar incorreto (deve começar com 'whsec_')\n";
    }
}

// Resumo
echo "\n========================================\n";
echo "Resumo\n";
echo "========================================\n";
echo "✅ STRIPE_SECRET: " . (empty($secret) ? "❌ Não configurado" : "✅ Configurado") . "\n";
echo "✅ Conectividade: " . (isset($account) ? "✅ OK" : "❌ Falhou") . "\n";
echo "✅ STRIPE_WEBHOOK_SECRET: " . (empty($webhookSecret) ? "⚠️  Não configurado (opcional)" : "✅ Configurado") . "\n";

if (!empty($secret) && isset($account)) {
    echo "\n🎉 Configuração Stripe está correta!\n";
    exit(0);
} else {
    echo "\n❌ Há problemas na configuração. Corrija antes de continuar.\n";
    exit(1);
}

