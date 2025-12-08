<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration: Cria Módulo de Clínica Veterinária
 * 
 * Esta migration cria todas as tabelas necessárias para o módulo de clínica veterinária:
 * - pets: Animais dos tutores (vinculados a customers)
 * - appointments: Agendamentos de consultas
 * - professionals: Veterinários e profissionais da clínica
 * 
 * Segue a arquitetura proposta no GUIA_CLINICA_VETERINARIA.md
 */
final class CreateClinicVeterinaryModule extends AbstractMigration
{
    /**
     * Migrate Up - Cria todas as tabelas do módulo
     * 
     * Migration idempotente: verifica se as tabelas já existem antes de criar
     */
    public function up(): void
    {
        // Verifica se a tabela pets já existe
        if ($this->hasTable('pets')) {
            $this->output->writeln('AVISO: Tabela pets já existe. Pulando criação.');
        } else {
            // =====================================================
            // TABELA: pets (Animais dos tutores)
            // =====================================================
            $pets = $this->table('pets', [
                'id' => 'id',
                'engine' => 'InnoDB',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Animais dos tutores (pets)'
            ]);
        
        $pets->addColumn('tenant_id', 'integer', [
            'signed' => false,
            'null' => false,
            'comment' => 'ID do tenant'
        ])
        ->addColumn('customer_id', 'integer', [
            'signed' => false,
            'null' => false,
            'comment' => 'ID do tutor (FK para customers)'
        ])
        ->addColumn('name', 'string', [
            'limit' => 255,
            'null' => false,
            'comment' => 'Nome do pet'
        ])
        ->addColumn('species', 'string', [
            'limit' => 100,
            'null' => true,
            'comment' => 'Espécie (cão, gato, etc.)'
        ])
        ->addColumn('breed', 'string', [
            'limit' => 100,
            'null' => true,
            'comment' => 'Raça'
        ])
        ->addColumn('birth_date', 'date', [
            'null' => true,
            'comment' => 'Data de nascimento'
        ])
        ->addColumn('gender', 'enum', [
            'values' => ['macho', 'femea'],
            'null' => true,
            'comment' => 'Sexo do animal'
        ])
        ->addColumn('weight', 'decimal', [
            'precision' => 5,
            'scale' => 2,
            'null' => true,
            'comment' => 'Peso em kg'
        ])
        ->addColumn('color', 'string', [
            'limit' => 50,
            'null' => true,
            'comment' => 'Cor/pelagem'
        ])
        ->addColumn('notes', 'text', [
            'null' => true,
            'comment' => 'Observações sobre o pet'
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
        ->addIndex(['deleted_at'], ['name' => 'idx_deleted_at'])
        ->addIndex(['tenant_id', 'customer_id'], ['name' => 'idx_tenant_customer'])
        ->addForeignKey('tenant_id', 'tenants', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_pets_tenant_id'
        ])
        ->addForeignKey('customer_id', 'customers', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_pets_customer_id'
        ])
        ->create();
        }

        // Verifica se a tabela professionals já existe
        if ($this->hasTable('professionals')) {
            $this->output->writeln('AVISO: Tabela professionals já existe. Pulando criação.');
        } else {
            // =====================================================
            // TABELA: professionals (Veterinários e Profissionais)
            // =====================================================
            $professionals = $this->table('professionals', [
                'id' => 'id',
                'engine' => 'InnoDB',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Veterinários e profissionais da clínica'
            ]);
        
        $professionals->addColumn('tenant_id', 'integer', [
            'signed' => false,
            'null' => false,
            'comment' => 'ID do tenant'
        ])
        ->addColumn('user_id', 'integer', [
            'signed' => false,
            'null' => true,
            'comment' => 'ID do usuário (FK para users, se for usuário do sistema)'
        ])
        ->addColumn('name', 'string', [
            'limit' => 255,
            'null' => false,
            'comment' => 'Nome do profissional'
        ])
        ->addColumn('crmv', 'string', [
            'limit' => 50,
            'null' => true,
            'comment' => 'CRMV do veterinário'
        ])
        ->addColumn('cpf', 'string', [
            'limit' => 14,
            'null' => true,
            'comment' => 'CPF do profissional (formato: 000.000.000-00)'
        ])
        ->addColumn('specialty', 'string', [
            'limit' => 100,
            'null' => true,
            'comment' => 'Especialidade'
        ])
        ->addColumn('phone', 'string', [
            'limit' => 20,
            'null' => true,
            'comment' => 'Telefone'
        ])
        ->addColumn('email', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => 'Email'
        ])
        ->addColumn('status', 'enum', [
            'values' => ['active', 'inactive'],
            'default' => 'active',
            'null' => false,
            'comment' => 'Status do profissional'
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
        ->addIndex(['user_id'], ['name' => 'idx_user_id'])
        ->addIndex(['status'], ['name' => 'idx_status'])
        ->addIndex(['crmv'], ['name' => 'idx_crmv'])
        ->addIndex(['cpf'], ['name' => 'idx_cpf'])
        ->addIndex(['tenant_id', 'crmv'], ['unique' => true, 'name' => 'unique_tenant_crmv'])
        ->addForeignKey('tenant_id', 'tenants', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_professionals_tenant_id'
        ])
        ->addForeignKey('user_id', 'users', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'CASCADE',
            'constraint' => 'fk_professionals_user_id'
        ])
        ->create();
        }

        // Verifica se a tabela appointments já existe
        if ($this->hasTable('appointments')) {
            $this->output->writeln('AVISO: Tabela appointments já existe. Pulando criação.');
        } else {
            // =====================================================
            // TABELA: appointments (Agendamentos)
            // =====================================================
            $appointments = $this->table('appointments', [
                'id' => 'id',
                'engine' => 'InnoDB',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Agendamentos de consultas'
            ]);
        
        $appointments->addColumn('tenant_id', 'integer', [
            'signed' => false,
            'null' => false,
            'comment' => 'ID do tenant'
        ])
        ->addColumn('pet_id', 'integer', [
            'signed' => false,
            'null' => false,
            'comment' => 'ID do pet'
        ])
        ->addColumn('professional_id', 'integer', [
            'signed' => false,
            'null' => true,
            'comment' => 'ID do profissional/veterinário'
        ])
        ->addColumn('customer_id', 'integer', [
            'signed' => false,
            'null' => false,
            'comment' => 'ID do tutor (FK para customers)'
        ])
        ->addColumn('appointment_date', 'datetime', [
            'null' => false,
            'comment' => 'Data e hora do agendamento'
        ])
        ->addColumn('duration_minutes', 'integer', [
            'default' => 30,
            'null' => false,
            'comment' => 'Duração em minutos'
        ])
        ->addColumn('status', 'enum', [
            'values' => ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'],
            'default' => 'scheduled',
            'null' => false,
            'comment' => 'Status do agendamento'
        ])
        ->addColumn('type', 'string', [
            'limit' => 100,
            'null' => true,
            'comment' => 'Tipo (consulta, cirurgia, exame)'
        ])
        ->addColumn('notes', 'text', [
            'null' => true,
            'comment' => 'Observações'
        ])
        ->addColumn('stripe_invoice_id', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => 'ID da fatura no Stripe (vinculação com pagamento)'
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
        ->addIndex(['pet_id'], ['name' => 'idx_pet_id'])
        ->addIndex(['professional_id'], ['name' => 'idx_professional_id'])
        ->addIndex(['customer_id'], ['name' => 'idx_customer_id'])
        ->addIndex(['appointment_date'], ['name' => 'idx_appointment_date'])
        ->addIndex(['status'], ['name' => 'idx_status'])
        ->addIndex(['deleted_at'], ['name' => 'idx_deleted_at'])
        ->addIndex(['tenant_id', 'professional_id', 'appointment_date'], [
            'name' => 'idx_tenant_prof_date'
        ])
        ->addForeignKey('tenant_id', 'tenants', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_appointments_tenant_id'
        ])
        ->addForeignKey('pet_id', 'pets', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_appointments_pet_id'
        ])
        ->addForeignKey('customer_id', 'customers', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_appointments_customer_id'
        ])
        ->addForeignKey('professional_id', 'professionals', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'CASCADE',
            'constraint' => 'fk_appointments_professional_id'
        ])
        ->create();
        }
    }

    /**
     * Migrate Down - Remove todas as tabelas (ordem inversa)
     */
    public function down(): void
    {
        // Remove tabelas na ordem inversa (respeitando foreign keys)
        $this->table('appointments')->drop()->save();
        $this->table('professionals')->drop()->save();
        $this->table('pets')->drop()->save();
    }
}
