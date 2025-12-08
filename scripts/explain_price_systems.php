<?php

echo "=== EXPLICAÇÃO DOS SISTEMAS DE PREÇOS ===\n\n";

echo "1. 📋 /clinic/specialties (Especialidades da Clínica)\n";
echo "   URL: http://localhost:8080/clinic/specialties\n";
echo "   Função: Cadastrar ESPECIALIDADES que a clínica atende\n";
echo "   Exemplos: Clínica Geral, Cirurgia, Dermatologia, Cardiologia\n";
echo "   Cada especialidade pode ter um PREÇO PADRÃO\n";
echo "   Tabela: clinic_specialties\n";
echo "   Campo: price_id (preço padrão da especialidade)\n\n";

echo "2. 💰 /clinic/appointment-price-config (Configuração de Preços)\n";
echo "   URL: http://localhost:8080/clinic/appointment-price-config\n";
echo "   Função: Criar REGRAS de preços para agendamentos\n";
echo "   Permite configurar preços por:\n";
echo "     - Tipo de consulta (consulta, cirurgia, vacinação)\n";
echo "     - Especialidade (texto livre - pode não estar cadastrada)\n";
echo "     - Profissional específico\n";
echo "   Tabela: appointment_price_config\n";
echo "   Sistema de PRIORIDADE:\n";
echo "     1. Profissional específico (maior prioridade)\n";
echo "     2. Especialidade\n";
echo "     3. Tipo de consulta (menor prioridade)\n\n";

echo "=== DIFERENÇA ===\n\n";
echo "📋 Especialidades (/clinic/specialties):\n";
echo "   - Lista de especialidades que a clínica ATENDE\n";
echo "   - Cada uma tem um preço padrão\n";
echo "   - É um CATÁLOGO de especialidades\n";
echo "   - Exemplo: 'Clínica Geral' com preço R$ 150,00\n\n";

echo "💰 Configuração de Preços (/clinic/appointment-price-config):\n";
echo "   - Sistema de REGRAS para sugerir preços automaticamente\n";
echo "   - Quando criar agendamento, o sistema busca:\n";
echo "     * Preço do profissional específico?\n";
echo "     * Se não, preço da especialidade?\n";
echo "     * Se não, preço do tipo de consulta?\n";
echo "   - Permite criar regras complexas\n";
echo "   - Exemplo: 'Consulta de Clínica Geral' = R$ 150,00\n\n";

echo "=== COMO USAR ===\n\n";
echo "1. Primeiro, cadastre as ESPECIALIDADES em /clinic/specialties\n";
echo "   - Ex: 'Clínica Geral' com preço R$ 150,00\n";
echo "   - Ex: 'Cirurgia' com preço R$ 500,00\n\n";

echo "2. Depois, configure REGRAS em /clinic/appointment-price-config\n";
echo "   - Ex: 'Consulta' + 'Clínica Geral' = R$ 150,00\n";
echo "   - Ex: 'Cirurgia' + 'Cirurgia' = R$ 500,00\n";
echo "   - Ex: Profissional 'Dr. João' = R$ 200,00 (sobrescreve especialidade)\n\n";

echo "=== RESUMO ===\n";
echo "✅ /clinic/specialties = CATÁLOGO de especialidades (já criado!)\n";
echo "✅ /clinic/appointment-price-config = REGRAS de preços (já existe)\n";
echo "✅ Ambos funcionam juntos para sugerir preços automaticamente\n";

