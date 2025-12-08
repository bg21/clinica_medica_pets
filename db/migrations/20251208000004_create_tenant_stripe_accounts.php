<?php

use Phinx\Migration\AbstractMigration;

class CreateTenantStripeAccounts extends AbstractMigration
{
    public function change()
    {
        // Verifica se a tabela já existe
        if ($this->hasTable('tenant_stripe_accounts')) {
            $this->output->writeln('AVISO: Tabela tenant_stripe_accounts já existe. Pulando criação.');
            return;
        }

        $table = $this->table('tenant_stripe_accounts', [
            'id' => 'id',
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Contas Stripe Connect vinculadas aos tenants'
        ]);

        $table->addColumn('tenant_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do tenant'])
            ->addColumn('stripe_account_id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'ID da conta Stripe Connect'])
            ->addColumn('account_type', 'string', ['limit' => 50, 'default' => 'express', 'comment' => 'Tipo: express ou standard'])
            ->addColumn('charges_enabled', 'boolean', ['default' => false, 'comment' => 'Se pode receber pagamentos'])
            ->addColumn('payouts_enabled', 'boolean', ['default' => false, 'comment' => 'Se pode receber saques'])
            ->addColumn('details_submitted', 'boolean', ['default' => false, 'comment' => 'Se completou onboarding'])
            ->addColumn('onboarding_completed', 'boolean', ['default' => false, 'comment' => 'Se onboarding foi completado'])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Email da conta Stripe'])
            ->addColumn('country', 'string', ['limit' => 2, 'null' => true, 'comment' => 'País (ISO 2 letras)'])
            ->addColumn('metadata', 'json', ['null' => true, 'comment' => 'Metadados adicionais'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP']);

        $table->addForeignKey('tenant_id', 'tenants', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_tenant_stripe_accounts_tenant_id'
        ]);

        $table->addIndex(['tenant_id'], ['unique' => true, 'name' => 'idx_tenant_stripe_accounts_tenant_unique']);
        $table->addIndex(['stripe_account_id'], ['unique' => true, 'name' => 'idx_tenant_stripe_accounts_stripe_id_unique']);
        $table->addIndex(['charges_enabled'], ['name' => 'idx_tenant_stripe_accounts_charges_enabled']);

        $table->create();
    }
}

