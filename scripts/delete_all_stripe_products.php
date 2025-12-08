<?php

/**
 * Script para deletar todos os produtos do Stripe
 * 
 * ATENÇÃO: Este script é destrutivo e não pode ser desfeito!
 * Execute apenas se tiver certeza.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega configurações
require_once __DIR__ . '/../config/config.php';
Config::load();

use App\Services\StripeService;
use App\Services\Logger;

try {
    $stripeService = new StripeService();
    
    echo "🔍 Buscando produtos no Stripe...\n\n";
    
    // Lista todos os produtos (ativos e inativos)
    // Busca todos os produtos paginando se necessário
    $allProducts = [];
    $hasMore = true;
    $options = ['limit' => 100];
    
    while ($hasMore) {
        $products = $stripeService->listProducts($options);
        $allProducts = array_merge($allProducts, $products->data);
        $hasMore = $products->has_more ?? false;
        
        if ($hasMore && !empty($products->data)) {
            // Pega o último ID para paginação
            $lastId = end($products->data)->id;
            $options['starting_after'] = $lastId;
        } else {
            break;
        }
    }
    
    $totalProducts = count($allProducts);
    
    if ($totalProducts === 0) {
        echo "✅ Nenhum produto encontrado na conta Stripe.\n";
        exit(0);
    }
    
    echo "📦 Encontrados {$totalProducts} produto(s):\n\n";
    
    // Lista os produtos
    foreach ($allProducts as $index => $product) {
        echo sprintf(
            "%d. [%s] %s (ID: %s)\n",
            $index + 1,
            $product->active ? 'ATIVO' : 'INATIVO',
            $product->name ?? 'Sem nome',
            $product->id
        );
    }
    
    echo "\n⚠️  ATENÇÃO: Você está prestes a deletar TODOS os {$totalProducts} produto(s)!\n";
    echo "Esta ação NÃO pode ser desfeita!\n\n";
    
    // Confirmação
    echo "Digite 'CONFIRMAR' para continuar ou qualquer outra coisa para cancelar: ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if ($line !== 'CONFIRMAR') {
        echo "\n❌ Operação cancelada pelo usuário.\n";
        exit(0);
    }
    
    echo "\n🗑️  Iniciando exclusão dos produtos...\n\n";
    
    $deleted = 0;
    $errors = 0;
    
    foreach ($allProducts as $product) {
        try {
            echo "Deletando: {$product->name} ({$product->id})... ";
            
            // Deleta o produto
            $stripeService->deleteProduct($product->id);
            
            echo "✅ Deletado\n";
            $deleted++;
            
        } catch (\Exception $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            $errors++;
            Logger::error("Erro ao deletar produto Stripe", [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📊 Resumo:\n";
    echo "   Total de produtos: {$totalProducts}\n";
    echo "   ✅ Deletados: {$deleted}\n";
    echo "   ❌ Erros: {$errors}\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($errors === 0) {
        echo "\n✅ Todos os produtos foram deletados com sucesso!\n";
    } else {
        echo "\n⚠️  Alguns produtos não puderam ser deletados. Verifique os erros acima.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Erro fatal: " . $e->getMessage() . "\n";
    Logger::error("Erro fatal ao deletar produtos Stripe", ['error' => $e->getMessage()]);
    exit(1);
}

