<?php

echo "=== DE ONDE VEM O 'PREÇO PADRÃO'? ===\n\n";

echo "📋 FLUXO COMPLETO:\n\n";

echo "1. CRIAÇÃO DO PREÇO (Stripe)\n";
echo "   📍 Onde: Menu → Produtos/Preços\n";
echo "   📍 URL: http://localhost:8080/prices\n";
echo "   📍 O que faz:\n";
echo "      - Cria um PRODUTO no Stripe (ex: 'Consulta Veterinária')\n";
echo "      - Cria um PREÇO para esse produto (ex: R$ 150,00)\n";
echo "      - O preço fica armazenado no Stripe (não no banco local)\n";
echo "      - Cada preço tem um ID único (ex: 'price_1234567890')\n\n";

echo "2. CARREGAMENTO DOS PREÇOS (API)\n";
echo "   📍 Endpoint: GET /v1/prices\n";
echo "   📍 O que faz:\n";
echo "      - Busca todos os preços do Stripe\n";
echo "      - Retorna lista de preços disponíveis\n";
echo "      - Cada preço tem: ID, valor, moeda, produto associado\n\n";

echo "3. SELEÇÃO NA ESPECIALIDADE\n";
echo "   📍 Onde: Menu → Especialidades → Nova Especialidade\n";
echo "   📍 Campo: 'Preço Padrão'\n";
echo "   📍 O que faz:\n";
echo "      - Mostra dropdown com todos os preços do Stripe\n";
echo "      - Você seleciona qual preço usar para essa especialidade\n";
echo "      - O ID do preço é salvo no campo 'price_id' da tabela 'clinic_specialties'\n\n";

echo "4. ARMAZENAMENTO\n";
echo "   📍 Tabela: clinic_specialties\n";
echo "   📍 Campo: price_id (VARCHAR 255)\n";
echo "   📍 Valor: ID do preço no Stripe (ex: 'price_1234567890')\n";
echo "   📍 Pode ser NULL (especialidade sem preço definido)\n\n";

echo "=== RESUMO ===\n";
echo "✅ Preços são criados em: Menu → Produtos/Preços\n";
echo "✅ Preços vêm do: Stripe (sistema de pagamentos)\n";
echo "✅ Na especialidade você apenas: SELECIONA qual preço usar\n";
echo "✅ O sistema salva apenas: O ID do preço (não o valor)\n";
echo "✅ O valor real fica no: Stripe\n\n";

echo "=== EXEMPLO PRÁTICO ===\n";
echo "1. Você cria um produto 'Consulta Veterinária' com preço R$ 150,00 no Stripe\n";
echo "   → Stripe gera ID: 'price_abc123'\n\n";
echo "2. Você cria especialidade 'Clínica Geral'\n";
echo "   → Seleciona o preço 'price_abc123' no dropdown\n";
echo "   → Sistema salva: price_id = 'price_abc123' na tabela\n\n";
echo "3. Quando precisar usar o preço:\n";
echo "   → Sistema busca 'price_abc123' no Stripe\n";
echo "   → Stripe retorna: R$ 150,00\n\n";

echo "💡 VANTAGEM: Se você mudar o preço no Stripe, todas as especialidades\n";
echo "   que usam esse preço automaticamente terão o novo valor!\n";

