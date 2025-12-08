<?php

use Phinx\Migration\AbstractMigration;

class CreateAppointmentPriceConfig extends AbstractMigration
{
    public function change()
    {
        // Verifica se a tabela já existe
        if ($this->hasTable('appointment_price_config')) {
            $this->output->writeln('AVISO: Tabela appointment_price_config já existe. Pulando criação.');
            return;
        }

        // =====================================================
        // TABELA: appointment_price_config (Configuração de Preços)
        // =====================================================
        $table = $this->table('appointment_price_config', [
            'id' => 'id',
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Configuração de preços por tipo de consulta, especialidade ou profissional'
        ]);
        
        $table->addColumn('tenant_id', 'integer', [
            'signed' => false,
            'null' => false,
            'comment' => 'ID do tenant'
        ])
        ->addColumn('appointment_type', 'string', [
            'limit' => 50,
            'null' => true,
            'comment' => 'Tipo de consulta (consulta, cirurgia, vacinação, etc.)'
        ])
        ->addColumn('specialty', 'string', [
            'limit' => 100,
            'null' => true,
            'comment' => 'Especialidade (Clínica Geral, Cirurgia, Dermatologia, etc.)'
        ])
        ->addColumn('professional_id', 'integer', [
            'signed' => false,
            'null' => true,
            'comment' => 'ID do profissional (NULL = preço padrão, ou ID específico)'
        ])
        ->addColumn('price_id', 'string', [
            'limit' => 255,
            'null' => false,
            'comment' => 'ID do preço no Stripe'
        ])
        ->addColumn('is_default', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'Se é o preço padrão para esta combinação'
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
        ->addIndex(['appointment_type'], ['name' => 'idx_appointment_type'])
        ->addIndex(['specialty'], ['name' => 'idx_specialty'])
        ->addIndex(['professional_id'], ['name' => 'idx_professional_id'])
        ->addIndex(['tenant_id', 'appointment_type'], ['name' => 'idx_tenant_type'])
        ->addIndex(['tenant_id', 'specialty'], ['name' => 'idx_tenant_specialty'])
        ->addIndex(['tenant_id', 'professional_id'], ['name' => 'idx_tenant_professional'])
        ->addIndex(['tenant_id', 'appointment_type', 'specialty', 'professional_id'], [
            'name' => 'idx_tenant_type_specialty_professional'
        ])
        ->addForeignKey('tenant_id', 'tenants', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_price_config_tenant_id'
        ])
        ->addForeignKey('professional_id', 'professionals', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_price_config_professional_id'
        ])
        ->create();
    }
}

