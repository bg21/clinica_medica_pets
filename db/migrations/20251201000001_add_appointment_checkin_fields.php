<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona campos de check-in na tabela appointments
 * 
 * Esta migration adiciona os campos:
 * - checked_in_at: Data/hora em que o tutor/paciente fez check-in
 * - checked_in_by: ID do usuário que registrou o check-in (pode ser o próprio tutor ou funcionário)
 */
final class AddAppointmentCheckinFields extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('appointments');
        
        // Adiciona campos de check-in
        $table->addColumn('checked_in_at', 'datetime', [
            'null' => true,
            'after' => 'completed_by',
            'comment' => 'Data e hora em que o tutor/paciente fez check-in'
        ])
        ->addColumn('checked_in_by', 'integer', [
            'signed' => false,
            'null' => true,
            'after' => 'checked_in_at',
            'comment' => 'ID do usuário que registrou o check-in'
        ])
        ->addIndex(['checked_in_by'], ['name' => 'idx_checked_in_by'])
        ->update();
        
        // Adiciona foreign key (opcional, pode ser NULL)
        try {
            $this->execute('ALTER TABLE `appointments` ADD CONSTRAINT `fk_appointments_checked_in_by` FOREIGN KEY (`checked_in_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        } catch (\Exception $e) {
            // Ignora se já existir
        }
    }

    public function down(): void
    {
        $table = $this->table('appointments');
        
        // Remove foreign key primeiro
        try {
            $this->execute('ALTER TABLE `appointments` DROP FOREIGN KEY `fk_appointments_checked_in_by`');
        } catch (\Exception $e) {
            // Ignora se não existir
        }
        
        // Remove colunas
        $table->removeColumn('checked_in_by')
              ->removeColumn('checked_in_at')
              ->update();
    }
}

