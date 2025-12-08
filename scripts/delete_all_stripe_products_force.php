<?php

/**
 * Script para deletar TODOS os produtos e preços do Stripe
 * 
 * ATENÇÃO: Este script é destrutivo e não pode ser desfeito!
 * Execute apenas se tiver certeza.
 * 
 * Este script:
 * 1. Lista todos os produtos
 * 2. Para cada produto, lista e deleta todos os preços associados
 * 3. Depois deleta o produto
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega configurações
require_once __DIR__ . '/../config/config.php';
Config::load();

use App\Services\StripeService;
use App\Services\Logger;

try {
    $stripeService = new StripeService();
    $client = $stripeService->getClient();
    
    echo "🔍 Buscando produtos no Stripe...\n\n";
    
    // Busca todos os produtos paginando se necessário
    $allProducts = [];
    $hasMore = true;
    $options = ['limit' => 100];
    
    while ($hasMore) {
        $products = $stripeService->listProducts($options);
        $allProducts = array_merge($allProducts, $products->data);
        $hasMore = $products->has_more ?? false;
        
        if ($hasMore && !empty($products->data)) {
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
    
    echo "\n⚠️  ATENÇÃO: Você está prestes a deletar TODOS os {$totalProducts} produto(s) e seus preços!\n";
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
    
    echo "\n🗑️  Iniciando exclusão dos produtos e preços...\n\n";
    
    $deletedProducts = 0;
    $deletedPrices = 0;
    $errors = 0;
    
    foreach ($allProducts as $product) {
        try {
            echo "📦 Processando: {$product->name} ({$product->id})...\n";
            
            // 1. Lista e deleta todos os preços associados
            $hasMorePrices = true;
            $priceOptions = ['product' => $product->id, 'limit' => 100];
            
            while ($hasMorePrices) {
                try {
                    $prices = $client->prices->all($priceOptions);
                    
                    foreach ($prices->data as $price) {
                        try {
                            echo "   🗑️  Deletando preço: {$price->id}... ";
                            
                            // Desativa o preço (Stripe não permite deletar preços)
                            $client->prices->update($price->id, ['active' => false]);
                            echo "✅ Desativado\n";
                            
                            $deletedPrices++;
                            
                        } catch (\Exception $e) {
                            echo "❌ Erro: " . $e->getMessage() . "\n";
                            $errors++;
                        }
                    }
                    
                    $hasMorePrices = $prices->has_more ?? false;
                    
                    if ($hasMorePrices && !empty($prices->data)) {
                        $lastPriceId = end($prices->data)->id;
                        $priceOptions['starting_after'] = $lastPriceId;
                    } else {
                        break;
                    }
                    
                } catch (\Exception $e) {
                    echo "   ⚠️  Erro ao listar preços: " . $e->getMessage() . "\n";
                    break;
                }
            }
            
            // 2. Agora tenta deletar o produto
            echo "   🗑️  Deletando produto... ";
            
            try {
                // Tenta deletar diretamente
                $client->products->delete($product->id);
                echo "✅ Deletado\n";
                $deletedProducts++;
                
            } catch (\Exception $e) {
                // Se não puder deletar, desativa
                try {
                    $client->products->update($product->id, ['active' => false]);
                    echo "⚠️  Desativado (não pode ser deletado - pode ter assinaturas ativas)\n";
                    $deletedProducts++;
                } catch (\Exception $e2) {
                    echo "❌ Erro: " . $e2->getMessage() . "\n";
                    $errors++;
                }
            }
            
            echo "\n";
            
        } catch (\Exception $e) {
            echo "❌ Erro ao processar produto: " . $e->getMessage() . "\n\n";
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
    echo "   ✅ Produtos processados: {$deletedProducts}\n";
    echo "   ✅ Preços deletados/desativados: {$deletedPrices}\n";
    echo "   ❌ Erros: {$errors}\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($errors === 0) {
        echo "\n✅ Todos os produtos e preços foram processados com sucesso!\n";
        echo "⚠️  NOTA: Alguns produtos podem aparecer como 'inativos' no Stripe se tiverem assinaturas ativas.\n";
        echo "   Esses produtos não podem ser deletados enquanto houver assinaturas ativas.\n";
    } else {
        echo "\n⚠️  Alguns produtos não puderam ser processados. Verifique os erros acima.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Erro fatal: " . $e->getMessage() . "\n";
    Logger::error("Erro fatal ao deletar produtos Stripe", ['error' => $e->getMessage()]);
    exit(1);
}

