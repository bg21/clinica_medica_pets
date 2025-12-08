<?php

/**
 * Script para deletar TODOS os produtos e preços do Stripe
 * 
 * ATENÇÃO: Este script é destrutivo e não pode ser desfeito!
 * Execute apenas se tiver certeza.
 * 
 * Este script:
 * 1. Lista todos os produtos
 * 2. Para cada produto, lista e desativa todos os preços associados
 * 3. Depois tenta deletar o produto
 * 4. Lista e desativa todos os preços que não estão associados a produtos
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
    
    echo "🔍 Buscando produtos e preços no Stripe...\n\n";
    
    // ============================================
    // 1. BUSCA TODOS OS PRODUTOS
    // ============================================
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
    echo "📦 Encontrados {$totalProducts} produto(s)\n";
    
    // ============================================
    // 2. BUSCA TODOS OS PREÇOS
    // ============================================
    $allPrices = [];
    $hasMore = true;
    $priceOptions = ['limit' => 100];
    
    while ($hasMore) {
        $prices = $client->prices->all($priceOptions);
        $allPrices = array_merge($allPrices, $prices->data);
        $hasMore = $prices->has_more ?? false;
        
        if ($hasMore && !empty($prices->data)) {
            $lastPriceId = end($prices->data)->id;
            $priceOptions['starting_after'] = $lastPriceId;
        } else {
            break;
        }
    }
    
    $totalPrices = count($allPrices);
    echo "💰 Encontrados {$totalPrices} preço(s)\n\n";
    
    if ($totalProducts === 0 && $totalPrices === 0) {
        echo "✅ Nenhum produto ou preço encontrado na conta Stripe.\n";
        exit(0);
    }
    
    // ============================================
    // 3. LISTA RESUMO
    // ============================================
    echo "📋 Resumo:\n";
    echo "   Produtos: {$totalProducts}\n";
    echo "   Preços: {$totalPrices}\n\n";
    
    echo "⚠️  ATENÇÃO: Você está prestes a deletar/desativar TODOS os produtos e preços!\n";
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
    $deactivatedProducts = 0;
    $deactivatedPrices = 0;
    $errors = 0;
    
    // ============================================
    // 4. PROCESSA CADA PRODUTO
    // ============================================
    foreach ($allProducts as $product) {
        try {
            echo "📦 Processando produto: {$product->name} ({$product->id})...\n";
            
            // Lista e desativa todos os preços associados
            $productPrices = [];
            $hasMorePrices = true;
            $productPriceOptions = ['product' => $product->id, 'limit' => 100];
            
            while ($hasMorePrices) {
                try {
                    $prices = $client->prices->all($productPriceOptions);
                    
                    foreach ($prices->data as $price) {
                        try {
                            echo "   🗑️  Desativando preço: {$price->id}... ";
                            
                            $client->prices->update($price->id, ['active' => false]);
                            echo "✅ Desativado\n";
                            
                            $deactivatedPrices++;
                            $productPrices[] = $price->id;
                            
                        } catch (\Exception $e) {
                            echo "❌ Erro: " . $e->getMessage() . "\n";
                            $errors++;
                        }
                    }
                    
                    $hasMorePrices = $prices->has_more ?? false;
                    
                    if ($hasMorePrices && !empty($prices->data)) {
                        $lastPriceId = end($prices->data)->id;
                        $productPriceOptions['starting_after'] = $lastPriceId;
                    } else {
                        break;
                    }
                    
                } catch (\Exception $e) {
                    echo "   ⚠️  Erro ao listar preços: " . $e->getMessage() . "\n";
                    break;
                }
            }
            
            // IMPORTANTE: O Stripe NÃO permite excluir produtos que têm preços associados
            // Mesmo que os preços estejam desativados, o produto não pode ser excluído
            // Isso é uma limitação do Stripe para manter integridade financeira
            
            // Verifica se o produto tem preços
            $hasPrices = count($productPrices) > 0;
            
            if (!$hasPrices) {
                // Se não tem preços, pode tentar excluir
                echo "   🗑️  Excluindo produto (sem preços)... ";
                
                try {
                    $deletedProduct = $client->products->delete($product->id);
                    
                    if (isset($deletedProduct->deleted) && $deletedProduct->deleted === true) {
                        echo "✅ EXCLUÍDO\n";
                        $deletedProducts++;
                    } else {
                        // Se não foi deletado, desativa
                        $client->products->update($product->id, ['active' => false]);
                        echo "⚠️  Desativado (não pode ser excluído)\n";
                        $deactivatedProducts++;
                    }
                } catch (\Exception $e) {
                    // Se não conseguir excluir, desativa
                    try {
                        $client->products->update($product->id, ['active' => false]);
                        echo "⚠️  Desativado (não pode ser excluído)\n";
                        $deactivatedProducts++;
                    } catch (\Exception $e2) {
                        echo "❌ Erro: " . $e2->getMessage() . "\n";
                        $errors++;
                    }
                }
            } else {
                // Tem preços - Stripe não permite excluir, apenas desativar
                echo "   ⚠️  Produto tem preços associados - Stripe não permite excluir, apenas desativar\n";
                echo "   🗑️  Desativando produto... ";
                
                try {
                    $client->products->update($product->id, ['active' => false]);
                    echo "✅ Desativado\n";
                    $deactivatedProducts++;
                } catch (\Exception $e) {
                    echo "❌ Erro: " . $e->getMessage() . "\n";
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
    
    // ============================================
    // 5. DESATIVA PREÇOS ÓRFÃOS (sem produto)
    // ============================================
    echo "🔍 Verificando preços órfãos (sem produto associado)...\n\n";
    
    foreach ($allPrices as $price) {
        // Pula se já foi processado com algum produto
        $productId = is_string($price->product) ? $price->product : ($price->product->id ?? null);
        
        if (!$productId) {
            continue;
        }
        
        // Verifica se o produto ainda existe
        try {
            $product = $client->products->retrieve($productId);
            // Se o produto existe, o preço já foi processado acima
            continue;
        } catch (\Exception $e) {
            // Produto não existe mais, desativa o preço órfão
            try {
                if ($price->active) {
                    echo "🗑️  Desativando preço órfão: {$price->id} (produto {$productId} não existe)... ";
                    $client->prices->update($price->id, ['active' => false]);
                    echo "✅ Desativado\n";
                    $deactivatedPrices++;
                }
            } catch (\Exception $e2) {
                echo "❌ Erro ao desativar preço órfão: " . $e2->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    // ============================================
    // 6. RESUMO FINAL
    // ============================================
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 Resumo Final:\n";
    echo str_repeat("=", 60) . "\n";
    echo "   Total de produtos processados: {$totalProducts}\n";
    echo "   ✅ Produtos EXCLUÍDOS: {$deletedProducts}\n";
    echo "   ⚠️  Produtos desativados (não puderam ser excluídos): {$deactivatedProducts}\n";
    echo "   💰 Preços desativados: {$deactivatedPrices}\n";
    echo "   ❌ Erros: {$errors}\n";
    echo str_repeat("=", 60) . "\n";
    
    if ($errors === 0) {
        echo "\n✅ Processamento concluído!\n\n";
        echo "📝 Notas importantes:\n";
        echo "   • Produtos EXCLUÍDOS foram removidos permanentemente do Stripe\n";
        echo "   • Produtos desativados não puderam ser excluídos (têm assinaturas ativas)\n";
        echo "   • Preços NÃO podem ser excluídos no Stripe, apenas desativados\n";
        echo "   • Preços desativados não aparecerão mais na lista de preços ativos\n";
    } else {
        echo "\n⚠️  Alguns itens não puderam ser processados. Verifique os erros acima.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Erro fatal: " . $e->getMessage() . "\n";
    Logger::error("Erro fatal ao deletar produtos e preços Stripe", ['error' => $e->getMessage()]);
    exit(1);
}

