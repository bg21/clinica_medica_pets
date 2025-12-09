<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona campo responsible_employee_id na tabela exams
 * 
 * Este campo armazena o ID do funcionário responsável pela realização do exame,
 * diferente do professional_id que é o veterinário que solicitou o exame.
 */
final class AddResponsibleEmployeeToExams extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('exams')) {
            $this->output->writeln('<error>Tabela exams não existe. Execute a migration CreateExamsTable primeiro.</error>');
            throw new \RuntimeException('Tabela exams não existe.');
        }

        $table = $this->table('exams');
        
        // Verifica se a coluna já existe
        $columns = $this->getAdapter()->getColumns('exams');
        $hasColumn = false;
        foreach ($columns as $column) {
            if ($column->getName() === 'responsible_employee_id') {
                $hasColumn = true;
                break;
            }
        }

        if (!$hasColumn) {
            $table->addColumn('responsible_employee_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'professional_id',
                'comment' => 'ID do funcionário responsável pela realização do exame'
            ])
            ->addIndex(['responsible_employee_id'], ['name' => 'idx_responsible_employee_id'])
            ->addForeignKey('responsible_employee_id', 'professionals', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_exam_responsible_employee'
            ])
            ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('exams')) {
            return;
        }
        
        $table = $this->table('exams');
        $columns = $this->getAdapter()->getColumns('exams');
        
        foreach ($columns as $column) {
            if ($column->getName() === 'responsible_employee_id') {
                // Remove foreign key usando SQL direto
                $this->execute("ALTER TABLE `exams` DROP FOREIGN KEY IF EXISTS `fk_exam_responsible_employee`");
                $table->removeIndex(['responsible_employee_id'])
                      ->removeColumn('responsible_employee_id')
                      ->update();
                break;
            }
        }
    }
}
