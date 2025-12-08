<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddChipAndPorteToPets extends AbstractMigration
{
    /**
     * Adiciona campos chip e porte na tabela pets
     */
    public function change(): void
    {
        // Verifica se a tabela pets existe
        if (!$this->hasTable('pets')) {
            $this->output->writeln('AVISO: Tabela pets não existe. Pulando migration.');
            return;
        }

        $table = $this->table('pets');

        // Adiciona coluna chip (opcional, string)
        if (!$table->hasColumn('chip')) {
            $table->addColumn('chip', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'name',
                'comment' => 'Número do chip do animal (opcional)'
            ]);
        }

        // Adiciona coluna porte (opcional, enum)
        if (!$table->hasColumn('porte')) {
            $table->addColumn('porte', 'enum', [
                'values' => ['pequeno', 'médio', 'grande', 'gigante'],
                'null' => true,
                'after' => 'chip',
                'comment' => 'Porte do animal: pequeno, médio, grande ou gigante'
            ]);
        }

        $table->update();
    }
}
