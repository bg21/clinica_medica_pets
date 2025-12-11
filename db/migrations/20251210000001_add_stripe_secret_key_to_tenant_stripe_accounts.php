<?php

use Phinx\Migration\AbstractMigration;

class AddStripeSecretKeyToTenantStripeAccounts extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('tenant_stripe_accounts');
        
        // Verifica se a coluna já existe
        if ($table->hasColumn('stripe_secret_key')) {
            $this->output->writeln('AVISO: Coluna stripe_secret_key já existe. Pulando adição.');
            return;
        }

        // Adiciona coluna para armazenar API key do Stripe (criptografada)
        $table->addColumn('stripe_secret_key', 'text', [
            'null' => true,
            'comment' => 'API Key secreta do Stripe do tenant (criptografada)'
        ])->update();
    }
}

