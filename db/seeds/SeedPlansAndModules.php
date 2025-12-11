<?php

use Phinx\Seed\AbstractSeed;

/**
 * Seed: Popular tabelas de planos e módulos com dados iniciais
 * 
 * Baseado no arquivo App/Config/plans.php
 */
class SeedPlansAndModules extends AbstractSeed
{
    public function run(): void
    {
        // Carrega configuração do arquivo PHP (para migração inicial)
        $config = require __DIR__ . '/../../App/Config/plans.php';
        
        $modules = $config['modules'] ?? [];
        $plans = $config['plans'] ?? [];
        
        // Insere módulos
        $modulesTable = $this->table('modules');
        $moduleIds = [];
        $moduleDataArray = [];
        
        foreach ($modules as $moduleId => $module) {
            $data = [
                'module_id' => $module['id'],
                'name' => $module['name'],
                'description' => $module['description'] ?? null,
                'icon' => $module['icon'] ?? null,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $moduleDataArray[] = $data;
        }
        
        // Insere todos os módulos de uma vez
        $modulesTable->insert($moduleDataArray)->saveData();
        
        // Busca IDs inseridos
        foreach ($modules as $moduleId => $module) {
            $result = $this->query("SELECT id FROM modules WHERE module_id = '{$module['id']}'")->fetch();
            if ($result) {
                $moduleIds[$module['id']] = $result['id'];
            }
        }
        
        // Insere planos
        $plansTable = $this->table('plans');
        $planIds = [];
        $planDataArray = [];
        
        foreach ($plans as $planId => $plan) {
            $data = [
                'plan_id' => $plan['id'],
                'name' => $plan['name'],
                'description' => $plan['description'] ?? null,
                'monthly_price' => $plan['monthly_price'] ?? 0,
                'yearly_price' => $plan['yearly_price'] ?? 0,
                'max_users' => $plan['max_users'],
                'features' => json_encode($plan['features'] ?? []),
                'stripe_price_id_monthly' => $plan['stripe_price_ids']['monthly'] ?? null,
                'stripe_price_id_yearly' => $plan['stripe_price_ids']['yearly'] ?? null,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $planDataArray[] = $data;
        }
        
        // Insere todos os planos de uma vez
        $plansTable->insert($planDataArray)->saveData();
        
        // Busca IDs inseridos e cria relacionamentos
        $planModulesTable = $this->table('plan_modules');
        
        foreach ($plans as $planId => $plan) {
            $result = $this->query("SELECT id FROM plans WHERE plan_id = '{$plan['id']}'")->fetch();
            if ($result) {
                $planDbId = $result['id'];
                $planIds[$plan['id']] = $planDbId;
                
                // Insere relacionamento plano-módulo
                $planModules = $plan['modules'] ?? [];
                $planModuleData = [];
                
                foreach ($planModules as $moduleId) {
                    if (isset($moduleIds[$moduleId])) {
                        $planModuleData[] = [
                            'plan_id' => $planDbId,
                            'module_id' => $moduleIds[$moduleId],
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                    }
                }
                
                if (!empty($planModuleData)) {
                    $planModulesTable->insert($planModuleData)->saveData();
                }
            }
        }
    }
}

