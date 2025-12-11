<?php

use Phinx\Migration\AbstractMigration;

/**
 * Migration: Criar tabelas de planos e módulos
 * 
 * Permite gerenciar planos e módulos via banco de dados
 * ao invés de arquivo de configuração
 */
class CreatePlansAndModulesTables extends AbstractMigration
{
    public function change()
    {
        // Tabela de módulos
        $modules = $this->table('modules', [
            'id' => 'id',
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Módulos disponíveis no sistema'
        ]);
        
        $modules->addColumn('module_id', 'string', ['limit' => 100, 'null' => false, 'comment' => 'ID único do módulo (ex: vaccines)'])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Nome do módulo'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição do módulo'])
                ->addColumn('icon', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Ícone Bootstrap Icons'])
                ->addColumn('is_active', 'boolean', ['default' => true, 'comment' => 'Se o módulo está ativo'])
                ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => 'Ordem de exibição'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
                ->addIndex(['module_id'], ['unique' => true, 'name' => 'idx_module_id'])
                ->addIndex(['is_active'], ['name' => 'idx_is_active'])
                ->create();

        // Tabela de planos
        $plans = $this->table('plans', [
            'id' => 'id',
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Planos de assinatura disponíveis'
        ]);
        
        $plans->addColumn('plan_id', 'string', ['limit' => 100, 'null' => false, 'comment' => 'ID único do plano (ex: basic)'])
              ->addColumn('name', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Nome do plano'])
              ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição do plano'])
              ->addColumn('monthly_price', 'integer', ['null' => false, 'default' => 0, 'comment' => 'Preço mensal em centavos'])
              ->addColumn('yearly_price', 'integer', ['null' => false, 'default' => 0, 'comment' => 'Preço anual em centavos'])
              ->addColumn('max_users', 'integer', ['null' => true, 'comment' => 'Limite de usuários (null = ilimitado)'])
              ->addColumn('features', 'json', ['null' => true, 'comment' => 'Array de features do plano (JSON)'])
              ->addColumn('stripe_price_id_monthly', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Price ID do Stripe (mensal)'])
              ->addColumn('stripe_price_id_yearly', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Price ID do Stripe (anual)'])
              ->addColumn('is_active', 'boolean', ['default' => true, 'comment' => 'Se o plano está ativo'])
              ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => 'Ordem de exibição'])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addIndex(['plan_id'], ['unique' => true, 'name' => 'idx_plan_id'])
              ->addIndex(['is_active'], ['name' => 'idx_is_active'])
              ->create();

        // Tabela de relacionamento plano-módulo (muitos para muitos)
        $planModules = $this->table('plan_modules', [
            'id' => false,
            'primary_key' => ['plan_id', 'module_id'],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Relacionamento entre planos e módulos'
        ]);
        
        $planModules->addColumn('plan_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID do plano'])
                    ->addColumn('module_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID do módulo'])
                    ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
                    ->addForeignKey('plan_id', 'plans', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                    ->addForeignKey('module_id', 'modules', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                    ->addIndex(['plan_id'], ['name' => 'idx_plan_id'])
                    ->addIndex(['module_id'], ['name' => 'idx_module_id'])
                    ->create();
    }
}

