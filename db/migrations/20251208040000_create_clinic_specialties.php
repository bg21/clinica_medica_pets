<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria tabela de especialidades atendidas pela clínica
 * Cada especialidade pode ter um preço padrão associado
 */
final class CreateClinicSpecialties extends AbstractMigration
{
    public function change(): void
    {
        // Verifica se a tabela já existe
        if ($this->hasTable('clinic_specialties')) {
            $this->output->writeln('AVISO: Tabela clinic_specialties já existe. Pulando criação.');
            return;
        }

        $table = $this->table('clinic_specialties', [
            'id' => 'id',
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Especialidades atendidas pela clínica com preços associados'
        ]);

        $table->addColumn('tenant_id', 'integer', [
            'signed' => false,
            'null' => false,
            'comment' => 'ID do tenant'
        ])
        ->addColumn('name', 'string', [
            'limit' => 100,
            'null' => false,
            'comment' => 'Nome da especialidade (ex: Clínica Geral, Cirurgia, Dermatologia)'
        ])
        ->addColumn('description', 'text', [
            'null' => true,
            'comment' => 'Descrição da especialidade'
        ])
        ->addColumn('price_id', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => 'ID do preço padrão no Stripe para esta especialidade'
        ])
        ->addColumn('is_active', 'boolean', [
            'default' => true,
            'null' => false,
            'comment' => 'Se a especialidade está ativa'
        ])
        ->addColumn('sort_order', 'integer', [
            'default' => 0,
            'null' => false,
            'comment' => 'Ordem de exibição'
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
        ->addColumn('deleted_at', 'timestamp', [
            'null' => true,
            'default' => null,
            'comment' => 'Soft delete'
        ])
        ->addIndex(['tenant_id'], ['name' => 'idx_tenant_id'])
        ->addIndex(['is_active'], ['name' => 'idx_is_active'])
        ->addIndex(['deleted_at'], ['name' => 'idx_deleted_at'])
        ->addIndex(['tenant_id', 'name'], [
            'unique' => true,
            'name' => 'idx_tenant_name_unique'
        ])
        ->addForeignKey('tenant_id', 'tenants', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_clinic_specialties_tenant_id'
        ])
        ->create();
    }
}

