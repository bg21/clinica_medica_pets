<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;
use App\Models\Module;
use App\Services\CacheService;

/**
 * Service para gerenciar limites de planos
 * 
 * ✅ ATUALIZADO: Agora lê do banco de dados (tabelas plans, modules, plan_modules)
 * ✅ FALLBACK: Se não houver dados no banco, usa App/Config/plans.php
 * 
 * Verifica limites de recursos baseados no plano de assinatura do tenant
 */
class PlanLimitsService
{
    private Plan $planModel;
    private Module $moduleModel;

    public function __construct()
    {
        $this->planModel = new Plan();
        $this->moduleModel = new Module();
    }

    /**
     * Carrega configuração de planos do banco de dados
     * 
     * @return array Configuração completa de planos e módulos
     */
    private function getPlansConfig(): array
    {
        static $config = null;
        
        if ($config === null) {
            // Tenta carregar do banco de dados primeiro
            try {
                $config = $this->loadFromDatabase();
                
                // Se não houver dados no banco, usa arquivo como fallback
                if (empty($config['plans']) && empty($config['modules'])) {
                    $config = $this->loadFromFile();
                }
            } catch (\Exception $e) {
                // Se houver erro, usa arquivo como fallback
                \App\Services\Logger::warning("Erro ao carregar planos do banco, usando arquivo", [
                    'error' => $e->getMessage()
                ]);
                $config = $this->loadFromFile();
            }
        }
        
        return $config;
    }

    /**
     * Carrega planos e módulos do banco de dados
     */
    private function loadFromDatabase(): array
    {
        $plans = $this->planModel->findAll(['is_active' => true]);
        $modules = $this->moduleModel->findAll(['is_active' => true]);
        
        $config = [
            'modules' => [],
            'plans' => [],
            'settings' => [
                'trial_days' => 14,
                'yearly_discount_percentage' => 17,
                'currency' => 'BRL',
                'currency_symbol' => 'R$'
            ]
        ];
        
        // Converte módulos para formato esperado
        foreach ($modules as $module) {
            $config['modules'][$module['module_id']] = [
                'id' => $module['module_id'],
                'name' => $module['name'],
                'description' => $module['description'],
                'icon' => $module['icon']
            ];
        }
        
        // Converte planos para formato esperado
        foreach ($plans as $plan) {
            $planModules = $this->planModel->getModules($plan['id']);
            $moduleIds = [];
            
            foreach ($planModules as $module) {
                $moduleIds[] = $module['module_id'];
            }
            
            $config['plans'][$plan['plan_id']] = [
                'id' => $plan['plan_id'],
                'name' => $plan['name'],
                'description' => $plan['description'],
                'monthly_price' => (int) $plan['monthly_price'],
                'yearly_price' => (int) $plan['yearly_price'],
                'max_users' => $plan['max_users'],
                'features' => json_decode($plan['features'] ?? '[]', true),
                'modules' => $moduleIds,
                'stripe_price_ids' => [
                    'monthly' => $plan['stripe_price_id_monthly'],
                    'yearly' => $plan['stripe_price_id_yearly']
                ]
            ];
        }
        
        return $config;
    }

    /**
     * Carrega planos e módulos do arquivo PHP (fallback)
     */
    private function loadFromFile(): array
    {
        $configPath = __DIR__ . '/../Config/plans.php';
        if (file_exists($configPath)) {
            return require $configPath;
        }
        
        return $this->getDefaultConfig();
    }

    /**
     * Configuração padrão (fallback)
     */
    private function getDefaultConfig(): array
    {
        return [
            'modules' => [],
            'plans' => [],
            'settings' => [
                'trial_days' => 14,
                'yearly_discount_percentage' => 17,
                'currency' => 'BRL',
                'currency_symbol' => 'R$'
            ]
        ];
    }

    /**
     * Obtém plano por price_id do Stripe
     * 
     * @param string $priceId ID do preço no Stripe
     * @return array|null Dados do plano ou null se não encontrado
     */
    private function getPlanByPriceId(string $priceId): ?array
    {
        $config = $this->getPlansConfig();
        $plans = $config['plans'] ?? [];
        
        // Busca plano que tenha este price_id
        foreach ($plans as $planId => $plan) {
            $stripePriceIds = $plan['stripe_price_ids'] ?? [];
            if ($stripePriceIds['monthly'] === $priceId || $stripePriceIds['yearly'] === $priceId) {
                $billingInterval = $stripePriceIds['monthly'] === $priceId ? 'month' : 'year';
                return array_merge($plan, [
                    'plan_id' => $planId,
                    'billing_interval' => $billingInterval
                ]);
            }
        }
        
        return null;
    }

    /**
     * Mapeia planos Stripe para limites
     * 
     * ✅ ATUALIZADO: Agora lê de App/Config/plans.php
     * 
     * @param string $priceId ID do preço no Stripe
     * @return array Limites do plano
     */
    private function getPlanLimits(string $priceId): array
    {
        $plan = $this->getPlanByPriceId($priceId);
        
        if (!$plan) {
            // Fallback: tenta mapeamento antigo (compatibilidade)
            return $this->getLegacyPlanLimits($priceId);
        }
        
        $config = $this->getPlansConfig();
        $modules = $config['modules'] ?? [];
        
        // Constrói array de módulos disponíveis
        $availableModules = [];
        $planModules = $plan['modules'] ?? [];
        
        foreach ($planModules as $moduleId) {
            if (isset($modules[$moduleId])) {
                $availableModules[$moduleId] = $modules[$moduleId];
            }
        }
        
        return [
            'plan_id' => $plan['id'],
            'plan_name' => $plan['name'],
            'max_users' => $plan['max_users'],
            'billing_interval' => $plan['billing_interval'] ?? 'month',
            'modules' => $availableModules,
            'module_ids' => $planModules, // IDs simples para verificação rápida
            'features' => $plan['features'] ?? [],
            'monthly_price' => $plan['monthly_price'] ?? 0,
            'yearly_price' => $plan['yearly_price'] ?? 0
        ];
    }

    /**
     * Mapeamento legado (compatibilidade com planos antigos)
     */
    private function getLegacyPlanLimits(string $priceId): array
    {
        $legacyPlans = [
            'price_1SWSxHByYvrEJg7OP74k90Wf' => ['max_users' => 1, 'plan_name' => 'Plano Básico', 'billing_interval' => 'month'],
            'price_1SWSxIByYvrEJg7OoCvpeJqj' => ['max_users' => 1, 'plan_name' => 'Plano Básico', 'billing_interval' => 'year'],
            'price_1SWSxIByYvrEJg7ODQHwI1DB' => ['max_users' => 5, 'plan_name' => 'Plano Profissional', 'billing_interval' => 'month'],
            'price_1SWSxIByYvrEJg7OKHZvLIfm' => ['max_users' => 5, 'plan_name' => 'Plano Profissional', 'billing_interval' => 'year'],
            'price_1SWSxJByYvrEJg7OLAoRRj16' => ['max_users' => null, 'plan_name' => 'Plano Premium', 'billing_interval' => 'month'],
            'price_1SWSxJByYvrEJg7OEE1NAOdd' => ['max_users' => null, 'plan_name' => 'Plano Premium', 'billing_interval' => 'year']
        ];
        
        $legacy = $legacyPlans[$priceId] ?? [];
        
        return array_merge([
            'max_users' => null,
            'features' => [],
            'plan_name' => 'Plano Desconhecido',
            'billing_interval' => 'month',
            'modules' => [],
            'module_ids' => []
        ], $legacy);
    }

    /**
     * Obtém limites do plano com cache
     * 
     * @param string $priceId ID do preço no Stripe
     * @return array Limites do plano
     */
    private function getPlanLimitsCached(string $priceId): array
    {
        $cacheKey = "plan_limits:{$priceId}";
        $cached = CacheService::getJson($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $limits = $this->getPlanLimits($priceId);
        CacheService::setJson($cacheKey, $limits, 300); // 5 minutos
        
        return $limits;
    }
    
    /**
     * Verifica limite de usuários
     * 
     * @param int $tenantId ID do tenant
     * @return array Resultado da verificação com informações de limite
     */
    public function checkUserLimit(int $tenantId): array
    {
        $subscription = $this->getActiveSubscription($tenantId);
        if (!$subscription) {
            return [
                'allowed' => false, 
                'reason' => 'Nenhuma assinatura ativa',
                'current' => 0,
                'limit' => 0,
                'remaining' => 0,
                'percentage' => 0
            ];
        }
        
        $limits = $this->getPlanLimitsCached($subscription['plan_id']);
        if ($limits['max_users'] === null) {
            // Ilimitado
            $currentCount = (new User())->count([
                'tenant_id' => $tenantId,
                'status' => 'active'
            ]);
            
            return [
                'allowed' => true,
                'unlimited' => true,
                'current' => $currentCount,
                'limit' => null,
                'remaining' => null,
                'percentage' => 0
            ];
        }
        
        $currentCount = (new User())->count([
            'tenant_id' => $tenantId,
            'status' => 'active'
        ]);
        
        $percentage = $limits['max_users'] > 0 
            ? round(($currentCount / $limits['max_users']) * 100, 2)
            : 0;
        
        return [
            'allowed' => $currentCount < $limits['max_users'],
            'current' => $currentCount,
            'limit' => $limits['max_users'],
            'remaining' => max(0, $limits['max_users'] - $currentCount),
            'percentage' => $percentage,
            'near_limit' => $percentage >= 80 && $percentage < 100,
            'at_limit' => $percentage >= 100
        ];
    }
    
    /**
     * Obtém todos os limites do plano atual do tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Limites completos do plano
     */
    public function getAllLimits(int $tenantId): array
    {
        $subscription = $this->getActiveSubscription($tenantId);
        
        if (!$subscription) {
            return [
                'has_subscription' => false,
                'subscription' => null,
                'limits' => null,
                'users' => $this->checkUserLimit($tenantId)
            ];
        }
        
        $limits = $this->getPlanLimitsCached($subscription['plan_id']);
        
        return [
            'has_subscription' => true,
            'subscription' => [
                'id' => $subscription['id'],
                'plan_id' => $subscription['plan_id'],
                'plan_name' => $subscription['plan_name'] ?? 'Plano',
                'status' => $subscription['status']
            ],
            'limits' => $limits,
            'users' => $this->checkUserLimit($tenantId)
        ];
    }
    
    /**
     * Obtém assinatura ativa do tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array|null Assinatura ativa ou null
     */
    private function getActiveSubscription(int $tenantId): ?array
    {
        $subscriptionModel = new Subscription();
        return $subscriptionModel->findActiveByTenant($tenantId);
    }

    /**
     * Verifica se um módulo está disponível no plano do tenant
     * 
     * @param int $tenantId ID do tenant
     * @param string $moduleId ID do módulo (ex: 'vaccines', 'hospitalization')
     * @return bool True se o módulo está disponível
     */
    public function hasModule(int $tenantId, string $moduleId): bool
    {
        $subscription = $this->getActiveSubscription($tenantId);
        if (!$subscription) {
            return false;
        }
        
        $limits = $this->getPlanLimitsCached($subscription['plan_id']);
        $moduleIds = $limits['module_ids'] ?? [];
        
        return in_array($moduleId, $moduleIds);
    }

    /**
     * Obtém lista de módulos disponíveis no plano do tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array Lista de módulos disponíveis
     */
    public function getAvailableModules(int $tenantId): array
    {
        $subscription = $this->getActiveSubscription($tenantId);
        if (!$subscription) {
            return [];
        }
        
        $limits = $this->getPlanLimitsCached($subscription['plan_id']);
        return $limits['modules'] ?? [];
    }

    /**
     * Obtém todos os planos disponíveis (para exibição)
     * 
     * ✅ ATUALIZADO: Busca primeiro no banco de dados
     * 
     * @return array Lista de planos com informações completas
     */
    public function getAllPlans(): array
    {
        // Tenta buscar no banco de dados primeiro
        try {
            $dbPlans = $this->planModel->findAll(['is_active' => true]);
            if (!empty($dbPlans)) {
                $settings = [
                    'currency_symbol' => 'R$',
                    'currency' => 'BRL'
                ];
                
                $result = [];
                foreach ($dbPlans as $plan) {
                    $planModules = $this->planModel->getModules($plan['id']);
                    $modules = [];
                    
                    foreach ($planModules as $module) {
                        $modules[] = [
                            'id' => $module['module_id'],
                            'name' => $module['name'],
                            'description' => $module['description'],
                            'icon' => $module['icon']
                        ];
                    }
                    
                    $result[] = [
                        'id' => $plan['plan_id'],
                        'name' => $plan['name'],
                        'description' => $plan['description'],
                        'monthly_price' => (int) $plan['monthly_price'],
                        'yearly_price' => (int) $plan['yearly_price'],
                        'monthly_price_formatted' => $this->formatPrice((int) $plan['monthly_price'], $settings),
                        'yearly_price_formatted' => $this->formatPrice((int) $plan['yearly_price'], $settings),
                        'max_users' => $plan['max_users'],
                        'features' => json_decode($plan['features'] ?? '[]', true),
                        'modules' => $modules,
                        'stripe_price_ids' => [
                            'monthly' => $plan['stripe_price_id_monthly'],
                            'yearly' => $plan['stripe_price_id_yearly']
                        ]
                    ];
                }
                
                return $result;
            }
        } catch (\Exception $e) {
            // Se houver erro, continua para buscar no arquivo
        }
        
        // Fallback: busca no arquivo de configuração
        $config = $this->getPlansConfig();
        $plans = $config['plans'] ?? [];
        $settings = $config['settings'] ?? [];
        
        $result = [];
        foreach ($plans as $planId => $plan) {
            $result[] = [
                'id' => $planId,
                'name' => $plan['name'],
                'description' => $plan['description'],
                'monthly_price' => $plan['monthly_price'],
                'yearly_price' => $plan['yearly_price'],
                'monthly_price_formatted' => $this->formatPrice($plan['monthly_price'], $settings),
                'yearly_price_formatted' => $this->formatPrice($plan['yearly_price'], $settings),
                'max_users' => $plan['max_users'],
                'features' => $plan['features'],
                'modules' => $this->getModulesForPlan($plan['modules'] ?? []),
                'stripe_price_ids' => $plan['stripe_price_ids']
            ];
        }
        
        return $result;
    }

    /**
     * Obtém informações dos módulos para um plano
     */
    private function getModulesForPlan(array $moduleIds): array
    {
        $config = $this->getPlansConfig();
        $allModules = $config['modules'] ?? [];
        
        $modules = [];
        foreach ($moduleIds as $moduleId) {
            if (isset($allModules[$moduleId])) {
                $modules[] = $allModules[$moduleId];
            }
        }
        
        return $modules;
    }

    /**
     * Formata preço para exibição
     */
    private function formatPrice(int $priceInCents, array $settings): string
    {
        $symbol = $settings['currency_symbol'] ?? 'R$';
        $amount = $priceInCents / 100;
        return $symbol . ' ' . number_format($amount, 2, ',', '.');
    }
}

