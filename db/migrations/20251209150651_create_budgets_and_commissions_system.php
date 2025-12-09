<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration: Sistema de Orçamentos e Comissões
 * 
 * Esta migration cria o sistema completo de orçamentos e comissões:
 * - budgets: Orçamentos criados pelos funcionários
 * - commission_config: Configuração de porcentagem de comissão por tenant
 * - commissions: Histórico de comissões pagas aos funcionários
 */
final class CreateBudgetsAndCommissionsSystem extends AbstractMigration
{
    /**
     * Migrate Up - Cria todas as tabelas do sistema
     */
    public function up(): void
    {
        // =====================================================
        // TABELA: budgets (Orçamentos)
        // =====================================================
        if (!$this->hasTable('budgets')) {
            $budgets = $this->table('budgets', [
                'id' => 'id',
                'engine' => 'InnoDB',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Orçamentos criados pelos funcionários'
            ]);
            
            $budgets->addColumn('tenant_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'ID do tenant'
            ])
            ->addColumn('customer_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'ID do cliente/tutor (FK para customers)'
            ])
            ->addColumn('pet_id', 'integer', [
                'signed' => false,
                'null' => true,
                'comment' => 'ID do pet (opcional, FK para pets)'
            ])
            ->addColumn('created_by_user_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'ID do funcionário que criou o orçamento (FK para users)'
            ])
            ->addColumn('budget_number', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Número único do orçamento (ex: ORC-2025-001)'
            ])
            ->addColumn('total_amount', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'default' => 0.00,
                'comment' => 'Valor total do orçamento'
            ])
            ->addColumn('status', 'enum', [
                'values' => ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'],
                'default' => 'draft',
                'null' => false,
                'comment' => 'Status do orçamento: draft (rascunho), sent (enviado), accepted (aceito), rejected (rejeitado), expired (expirado), converted (convertido em venda)'
            ])
            ->addColumn('valid_until', 'date', [
                'null' => true,
                'comment' => 'Data de validade do orçamento'
            ])
            ->addColumn('items', 'json', [
                'null' => true,
                'comment' => 'Itens do orçamento (JSON): [{description, quantity, unit_price, total}]'
            ])
            ->addColumn('notes', 'text', [
                'null' => true,
                'comment' => 'Observações do orçamento'
            ])
            ->addColumn('converted_to_invoice_id', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'ID da fatura Stripe quando orçamento é convertido em venda'
            ])
            ->addColumn('converted_at', 'datetime', [
                'null' => true,
                'comment' => 'Data/hora em que o orçamento foi convertido em venda'
            ])
            ->addColumn('deleted_at', 'timestamp', [
                'null' => true,
                'default' => null,
                'comment' => 'Data de exclusão lógica (soft delete)'
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addIndex(['tenant_id'], ['name' => 'idx_tenant_id'])
            ->addIndex(['customer_id'], ['name' => 'idx_customer_id'])
            ->addIndex(['pet_id'], ['name' => 'idx_pet_id'])
            ->addIndex(['created_by_user_id'], ['name' => 'idx_created_by_user_id'])
            ->addIndex(['budget_number'], ['name' => 'idx_budget_number'])
            ->addIndex(['status'], ['name' => 'idx_status'])
            ->addIndex(['valid_until'], ['name' => 'idx_valid_until'])
            ->addIndex(['converted_at'], ['name' => 'idx_converted_at'])
            ->addIndex(['deleted_at'], ['name' => 'idx_deleted_at'])
            ->addIndex(['tenant_id', 'budget_number'], ['unique' => true, 'name' => 'unique_tenant_budget_number'])
            ->addIndex(['tenant_id', 'status'], ['name' => 'idx_tenant_status'])
            ->addIndex(['tenant_id', 'created_by_user_id'], ['name' => 'idx_tenant_user'])
            ->addForeignKey('tenant_id', 'tenants', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_budgets_tenant_id'
            ])
            ->addForeignKey('customer_id', 'customers', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_budgets_customer_id'
            ])
            ->addForeignKey('pet_id', 'pets', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_budgets_pet_id'
            ])
            ->addForeignKey('created_by_user_id', 'users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_budgets_created_by_user_id'
            ])
            ->create();
        }

        // =====================================================
        // TABELA: commission_config (Configuração de Comissão)
        // =====================================================
        if (!$this->hasTable('commission_config')) {
            $commissionConfig = $this->table('commission_config', [
                'id' => 'id',
                'engine' => 'InnoDB',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Configuração de porcentagem de comissão por tenant'
            ]);
            
            $commissionConfig->addColumn('tenant_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'ID do tenant'
            ])
            ->addColumn('commission_percentage', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false,
                'default' => 0.00,
                'comment' => 'Porcentagem de comissão sobre orçamentos fechados (ex: 5.00 = 5%)'
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
                'comment' => 'Se a comissão está ativa para este tenant'
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addIndex(['tenant_id'], ['unique' => true, 'name' => 'unique_tenant_id'])
            ->addIndex(['is_active'], ['name' => 'idx_is_active'])
            ->addForeignKey('tenant_id', 'tenants', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_commission_config_tenant_id'
            ])
            ->create();
        }

        // =====================================================
        // TABELA: commissions (Comissões Pagas)
        // =====================================================
        if (!$this->hasTable('commissions')) {
            $commissions = $this->table('commissions', [
                'id' => 'id',
                'engine' => 'InnoDB',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Histórico de comissões pagas aos funcionários'
            ]);
            
            $commissions->addColumn('tenant_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'ID do tenant'
            ])
            ->addColumn('budget_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'ID do orçamento que gerou a comissão (FK para budgets)'
            ])
            ->addColumn('user_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'ID do funcionário que recebeu a comissão (FK para users)'
            ])
            ->addColumn('budget_total', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Valor total do orçamento no momento da conversão'
            ])
            ->addColumn('commission_percentage', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false,
                'comment' => 'Porcentagem de comissão aplicada'
            ])
            ->addColumn('commission_amount', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Valor da comissão calculada'
            ])
            ->addColumn('status', 'enum', [
                'values' => ['pending', 'paid', 'cancelled'],
                'default' => 'pending',
                'null' => false,
                'comment' => 'Status da comissão: pending (pendente), paid (paga), cancelled (cancelada)'
            ])
            ->addColumn('paid_at', 'datetime', [
                'null' => true,
                'comment' => 'Data/hora em que a comissão foi paga'
            ])
            ->addColumn('payment_reference', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'Referência do pagamento (ex: número do comprovante)'
            ])
            ->addColumn('notes', 'text', [
                'null' => true,
                'comment' => 'Observações sobre a comissão'
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addIndex(['tenant_id'], ['name' => 'idx_tenant_id'])
            ->addIndex(['budget_id'], ['name' => 'idx_budget_id'])
            ->addIndex(['user_id'], ['name' => 'idx_user_id'])
            ->addIndex(['status'], ['name' => 'idx_status'])
            ->addIndex(['paid_at'], ['name' => 'idx_paid_at'])
            ->addIndex(['tenant_id', 'user_id'], ['name' => 'idx_tenant_user'])
            ->addIndex(['tenant_id', 'status'], ['name' => 'idx_tenant_status'])
            ->addIndex(['budget_id'], ['unique' => true, 'name' => 'unique_budget_id'])
            ->addForeignKey('tenant_id', 'tenants', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_commissions_tenant_id'
            ])
            ->addForeignKey('budget_id', 'budgets', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_commissions_budget_id'
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_commissions_user_id'
            ])
            ->create();
        }
    }

    /**
     * Migrate Down - Remove todas as tabelas (ordem inversa)
     */
    public function down(): void
    {
        if ($this->hasTable('commissions')) {
            $this->table('commissions')->drop()->save();
        }
        if ($this->hasTable('commission_config')) {
            $this->table('commission_config')->drop()->save();
        }
        if ($this->hasTable('budgets')) {
            $this->table('budgets')->drop()->save();
        }
    }
}
