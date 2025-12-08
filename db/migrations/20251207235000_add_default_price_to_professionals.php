<?php

use Phinx\Migration\AbstractMigration;

class AddDefaultPriceToProfessionals extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('professionals');
        
        if (!$table->hasColumn('default_price_id')) {
            $table->addColumn('default_price_id', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'status',
                'comment' => 'ID do preço padrão no Stripe para este profissional'
            ])
            ->addIndex(['default_price_id'], ['name' => 'idx_default_price_id'])
            ->update();
        }
    }
}

