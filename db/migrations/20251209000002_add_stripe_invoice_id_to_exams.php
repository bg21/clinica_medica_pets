<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona campo stripe_invoice_id na tabela exams
 * 
 * Este campo armazena o ID da invoice no Stripe para vincular
 * o exame com o pagamento, seguindo o mesmo padrão de appointments
 */
final class AddStripeInvoiceIdToExams extends AbstractMigration
{
    public function up(): void
    {
        // Verifica se a tabela exams existe
        if (!$this->hasTable('exams')) {
            $this->output->writeln('<error>Tabela exams não existe. Execute a migration CreateExamsTable primeiro.</error>');
            throw new \RuntimeException('Tabela exams não existe. Execute a migration 20251127023000_create_exams_table.php primeiro.');
        }
        
        // Verifica se a coluna já existe usando SQL direto
        $adapter = $this->getAdapter();
        $columns = $adapter->getColumns('exams');
        $columnExists = false;
        
        foreach ($columns as $column) {
            if ($column->getName() === 'stripe_invoice_id') {
                $columnExists = true;
                break;
            }
        }
        
        if ($columnExists) {
            $this->output->writeln('<info>Coluna stripe_invoice_id já existe na tabela exams. Pulando...</info>');
            return;
        }
        
        $table = $this->table('exams');
        $table->addColumn('stripe_invoice_id', 'string', [
            'limit' => 255,
            'null' => true,
            'after' => 'metadata',
            'comment' => 'ID da fatura no Stripe (vinculação com pagamento)'
        ])
        ->addIndex(['stripe_invoice_id'], ['name' => 'idx_stripe_invoice_id'])
        ->update();
    }

    public function down(): void
    {
        $exams = $this->table('exams');
        $exams->removeIndex(['stripe_invoice_id'])
              ->removeColumn('stripe_invoice_id')
              ->update();
    }
}

