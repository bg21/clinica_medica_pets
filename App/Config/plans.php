<?php
/**
 * Configuração de Planos e Módulos
 * 
 * Este arquivo define a estrutura completa de planos, valores e módulos disponíveis.
 * 
 * ✅ FÁCIL DE EDITAR: Modifique este arquivo para alterar planos, valores ou módulos
 * 
 * Estrutura:
 * - 'plans': Define os planos disponíveis
 * - 'modules': Define todos os módulos do sistema
 * - 'module_mapping': Mapeia quais módulos estão disponíveis em cada plano
 */

return [
    /**
     * Definição de todos os módulos do sistema
     * 
     * Cada módulo tem:
     * - 'id': Identificador único (usado no código)
     * - 'name': Nome amigável para exibição
     * - 'description': Descrição do módulo
     * - 'icon': Ícone Bootstrap Icons (opcional)
     */
    'modules' => [
        'customers' => [
            'id' => 'customers',
            'name' => 'Clientes',
            'description' => 'Gerenciamento de clientes (tutores)',
            'icon' => 'bi-people'
        ],
        'pets' => [
            'id' => 'pets',
            'name' => 'Pacientes',
            'description' => 'Gerenciamento de pets/animais',
            'icon' => 'bi-heart'
        ],
        'appointments' => [
            'id' => 'appointments',
            'name' => 'Agenda',
            'description' => 'Agendamento de consultas e atendimentos',
            'icon' => 'bi-calendar3'
        ],
        'services' => [
            'id' => 'services',
            'name' => 'Atendimentos',
            'description' => 'Registro de atendimentos e prontuários',
            'icon' => 'bi-clipboard-check'
        ],
        'vaccines' => [
            'id' => 'vaccines',
            'name' => 'Vacinas',
            'description' => 'Controle de vacinação e carteira de vacinas',
            'icon' => 'bi-shield-check'
        ],
        'exams' => [
            'id' => 'exams',
            'name' => 'Exames',
            'description' => 'Controle de exames e resultados',
            'icon' => 'bi-file-earmark-medical'
        ],
        'prescriptions' => [
            'id' => 'prescriptions',
            'name' => 'Receitas',
            'description' => 'Prescrições médicas e receitas veterinárias',
            'icon' => 'bi-prescription'
        ],
        'hospitalization' => [
            'id' => 'hospitalization',
            'name' => 'Internação',
            'description' => 'Controle de internações e leitos',
            'icon' => 'bi-hospital'
        ],
        'financial' => [
            'id' => 'financial',
            'name' => 'Financeiro',
            'description' => 'Controle financeiro completo',
            'icon' => 'bi-cash-stack'
        ],
        'sales' => [
            'id' => 'sales',
            'name' => 'Vendas',
            'description' => 'Vendas de produtos e serviços',
            'icon' => 'bi-cart'
        ],
        'documents' => [
            'id' => 'documents',
            'name' => 'Documentos',
            'description' => 'Armazenamento e gestão de documentos',
            'icon' => 'bi-folder'
        ],
        'products' => [
            'id' => 'products',
            'name' => 'Produtos & Serviços',
            'description' => 'Catálogo de produtos e serviços',
            'icon' => 'bi-box'
        ],
        'reports' => [
            'id' => 'reports',
            'name' => 'Relatórios',
            'description' => 'Relatórios gerenciais e análises',
            'icon' => 'bi-graph-up'
        ],
        'users' => [
            'id' => 'users',
            'name' => 'Gerenciamento de Usuários',
            'description' => 'Gestão de usuários, permissões e papéis',
            'icon' => 'bi-person-gear'
        ],
        'fiscal' => [
            'id' => 'fiscal',
            'name' => 'Módulo Fiscal',
            'description' => 'Integração fiscal e emissão de notas fiscais',
            'icon' => 'bi-receipt'
        ]
    ],

    /**
     * Definição dos planos disponíveis
     * 
     * Cada plano tem:
     * - 'id': Identificador único
     * - 'name': Nome do plano
     * - 'description': Descrição do plano
     * - 'monthly_price': Preço mensal em centavos (ex: 2900 = R$ 29,00)
     * - 'yearly_price': Preço anual em centavos (ex: 29000 = R$ 290,00)
     * - 'max_users': Limite de usuários (null = ilimitado)
     * - 'features': Array de features/recursos especiais
     * - 'modules': Array de IDs dos módulos disponíveis (referência a 'modules' acima)
     * - 'stripe_price_ids': Mapeamento de price_id do Stripe (mensal e anual)
     */
    'plans' => [
        'basic' => [
            'id' => 'basic',
            'name' => 'Básico',
            'description' => 'Ideal para clínicas pequenas que estão começando',
            'monthly_price' => 4900, // R$ 49,00
            'yearly_price' => 49000,  // R$ 490,00 (17% desconto)
            'max_users' => 1,
            'features' => [
                'Atendimento básico',
                'Gestão de clientes e pacientes',
                'Agenda simples',
                'Suporte por email'
            ],
            'modules' => [
                'customers',
                'pets',
                'appointments',
                'services'
            ],
            'stripe_price_ids' => [
                'monthly' => null, // Será preenchido quando criar no Stripe
                'yearly' => null
            ]
        ],
        'professional' => [
            'id' => 'professional',
            'name' => 'Profissional',
            'description' => 'Para clínicas em crescimento com necessidades avançadas',
            'monthly_price' => 9900, // R$ 99,00
            'yearly_price' => 99000,  // R$ 990,00 (17% desconto)
            'max_users' => 3,
            'features' => [
                'Tudo do Básico',
                'Controle de vacinação',
                'Exames e receitas',
                'Relatórios básicos',
                'Suporte prioritário'
            ],
            'modules' => [
                'customers',
                'pets',
                'appointments',
                'services',
                'vaccines',
                'exams',
                'prescriptions',
                'reports'
            ],
            'stripe_price_ids' => [
                'monthly' => null,
                'yearly' => null
            ]
        ],
        'premium' => [
            'id' => 'premium',
            'name' => 'Premium',
            'description' => 'Solução completa para clínicas estabelecidas',
            'monthly_price' => 19900, // R$ 199,00
            'yearly_price' => 199000, // R$ 1.990,00 (17% desconto)
            'max_users' => 6,
            'features' => [
                'Tudo do Profissional',
                'Controle de internações',
                'Módulo financeiro completo',
                'Vendas e produtos',
                'Gestão de documentos',
                'Relatórios avançados',
                'Suporte prioritário 24/7'
            ],
            'modules' => [
                'customers',
                'pets',
                'appointments',
                'services',
                'vaccines',
                'exams',
                'prescriptions',
                'hospitalization',
                'financial',
                'sales',
                'documents',
                'products',
                'reports',
                'users'
            ],
            'stripe_price_ids' => [
                'monthly' => null,
                'yearly' => null
            ]
        ],
        'enterprise' => [
            'id' => 'enterprise',
            'name' => 'Enterprise',
            'description' => 'Para grandes clínicas e redes veterinárias',
            'monthly_price' => 39900, // R$ 399,00
            'yearly_price' => 399000, // R$ 3.990,00 (17% desconto)
            'max_users' => null, // Ilimitado
            'features' => [
                'Tudo do Premium',
                'Módulo fiscal completo',
                'API avançada',
                'Integrações personalizadas',
                'Suporte dedicado',
                'Treinamento personalizado',
                'SLA garantido'
            ],
            'modules' => [
                'customers',
                'pets',
                'appointments',
                'services',
                'vaccines',
                'exams',
                'prescriptions',
                'hospitalization',
                'financial',
                'sales',
                'documents',
                'products',
                'reports',
                'users',
                'fiscal'
            ],
            'stripe_price_ids' => [
                'monthly' => null,
                'yearly' => null
            ]
        ]
    ],

    /**
     * Configurações gerais
     */
    'settings' => [
        'trial_days' => 14, // Dias de teste grátis
        'yearly_discount_percentage' => 17, // Desconto para pagamento anual
        'currency' => 'BRL',
        'currency_symbol' => 'R$'
    ]
];

