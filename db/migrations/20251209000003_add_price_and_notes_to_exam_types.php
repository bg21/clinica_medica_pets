<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona campos price_id e notes na tabela exam_types
 * 
 * price_id: ID do preço no Stripe para o tipo de exame
 * notes: Observações/instruções sobre o tipo de exame
 */
final class AddPriceAndNotesToExamTypes extends AbstractMigration
{
    public function up(): void
    {
        // Verifica se a tabela exam_types existe
        if (!$this->hasTable('exam_types')) {
            $this->output->writeln('<comment>Tabela exam_types não existe. Pulando esta migration.</comment>');
            return; // Retorna sem erro, apenas pula a migration
        }
        
        $table = $this->table('exam_types');
        
        // Adiciona colunas se não existirem (usando SQL direto para verificar)
        $adapter = $this->getAdapter();
        $columns = $adapter->getColumns('exam_types');
        $hasPriceId = false;
        $hasNotes = false;
        
        foreach ($columns as $column) {
            if ($column->getName() === 'price_id') {
                $hasPriceId = true;
            }
            if ($column->getName() === 'notes') {
                $hasNotes = true;
            }
        }
        
        if (!$hasPriceId) {
            $table->addColumn('price_id', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'description',
                'comment' => 'ID do preço no Stripe para este tipo de exame'
            ]);
        }
        
        if (!$hasNotes) {
            $table->addColumn('notes', 'text', [
                'null' => true,
                'after' => 'price_id',
                'comment' => 'Observações e instruções sobre o tipo de exame'
            ]);
        }
        
        if (!$hasPriceId || !$hasNotes) {
            $table->update();
        } else {
            $this->output->writeln('<info>Colunas price_id e notes já existem. Pulando...</info>');
        }
    }

    public function down(): void
    {
        $table = $this->table('exam_types');
        
        if ($this->getAdapter()->getColumns('exam_types')) {
            $columns = $this->getAdapter()->getColumns('exam_types');
            foreach ($columns as $column) {
                if ($column->getName() === 'price_id' || $column->getName() === 'notes') {
                    $table->removeColumn($column->getName());
                }
            }
            $table->update();
        }
    }
}

