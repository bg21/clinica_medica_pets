<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona campos de imagem nas tabelas pets, customers e professionals
 */
final class AddImageFieldsToTables extends AbstractMigration
{
    public function up(): void
    {
        // Adiciona coluna photo_url na tabela pets
        if ($this->hasTable('pets')) {
            $petsTable = $this->table('pets');
            if (!$petsTable->hasColumn('photo_url')) {
                $petsTable->addColumn('photo_url', 'string', [
                    'limit' => 500,
                    'null' => true,
                    'after' => 'notes',
                    'comment' => 'URL da foto do pet'
                ]);
                $petsTable->update();
            }
        }

        // Adiciona coluna photo_url na tabela customers
        if ($this->hasTable('customers')) {
            $customersTable = $this->table('customers');
            if (!$customersTable->hasColumn('photo_url')) {
                $customersTable->addColumn('photo_url', 'string', [
                    'limit' => 500,
                    'null' => true,
                    'after' => 'metadata',
                    'comment' => 'URL da foto do tutor'
                ]);
                $customersTable->update();
            }
        }

        // Adiciona coluna photo_url na tabela professionals
        if ($this->hasTable('professionals')) {
            $professionalsTable = $this->table('professionals');
            if (!$professionalsTable->hasColumn('photo_url')) {
                $professionalsTable->addColumn('photo_url', 'string', [
                    'limit' => 500,
                    'null' => true,
                    'after' => 'email',
                    'comment' => 'URL da foto do profissional'
                ]);
                $professionalsTable->update();
            }
        }

        // Adiciona coluna result_file_url na tabela exams (para PDFs de resultados)
        if ($this->hasTable('exams')) {
            $examsTable = $this->table('exams');
            if (!$examsTable->hasColumn('result_file_url')) {
                $examsTable->addColumn('result_file_url', 'string', [
                    'limit' => 500,
                    'null' => true,
                    'after' => 'results',
                    'comment' => 'URL do arquivo de resultado do exame (PDF, imagem, etc.)'
                ]);
                $examsTable->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('pets')) {
            $petsTable = $this->table('pets');
            if ($petsTable->hasColumn('photo_url')) {
                $petsTable->removeColumn('photo_url');
                $petsTable->update();
            }
        }

        if ($this->hasTable('customers')) {
            $customersTable = $this->table('customers');
            if ($customersTable->hasColumn('photo_url')) {
                $customersTable->removeColumn('photo_url');
                $customersTable->update();
            }
        }

        if ($this->hasTable('professionals')) {
            $professionalsTable = $this->table('professionals');
            if ($professionalsTable->hasColumn('photo_url')) {
                $professionalsTable->removeColumn('photo_url');
                $professionalsTable->update();
            }
        }

        if ($this->hasTable('exams')) {
            $examsTable = $this->table('exams');
            if ($examsTable->hasColumn('result_file_url')) {
                $examsTable->removeColumn('result_file_url');
                $examsTable->update();
            }
        }
    }
}

