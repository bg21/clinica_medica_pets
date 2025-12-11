<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Commission;
use PDO;

/**
 * Testes unitários para Commission Model
 * 
 * Cenários cobertos:
 * - Buscar comissão por tenant e ID
 * - Buscar comissões por tenant com paginação
 * - Buscar comissões com filtros
 * - Marcar comissão como paga
 * - Calcular total por usuário
 * - Verificar se existe comissão para orçamento
 */
class CommissionTest extends TestCase
{
    private ?PDO $testDb = null;
    private Commission $commissionModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Cria banco SQLite em memória
        $this->testDb = new PDO('sqlite::memory:');
        $this->testDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Cria tabela
        $this->testDb->exec("
            CREATE TABLE commissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                budget_id INTEGER,
                commission_amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                payment_reference VARCHAR(255),
                notes TEXT,
                paid_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insere dados de teste
        $this->testDb->exec("
            INSERT INTO commissions (tenant_id, user_id, budget_id, commission_amount, status, created_at)
            VALUES 
                (1, 5, 10, 100.00, 'pending', '2024-01-15 10:00:00'),
                (1, 5, 11, 150.00, 'paid', '2024-01-16 10:00:00'),
                (1, 6, 12, 200.00, 'pending', '2024-01-17 10:00:00'),
                (1, 6, 13, 75.00, 'paid', '2024-01-18 10:00:00'),
                (2, 5, 14, 300.00, 'pending', '2024-01-19 10:00:00')
        ");

        // Cria instância do modelo e injeta banco de teste
        $this->commissionModel = new Commission();
        $reflection = new \ReflectionClass($this->commissionModel);
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $dbProperty->setValue($this->commissionModel, $this->testDb);
    }

    protected function tearDown(): void
    {
        if ($this->testDb !== null) {
            $this->testDb = null;
        }
        parent::tearDown();
    }

    /**
     * Testa buscar comissão por tenant e ID com sucesso
     */
    public function testFindByTenantAndIdSuccess(): void
    {
        $result = $this->commissionModel->findByTenantAndId(1, 1);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals(1, $result['tenant_id']);
        $this->assertEquals(5, $result['user_id']);
        $this->assertEquals(100.00, $result['commission_amount']);
    }

    /**
     * Testa buscar comissão por tenant e ID - não encontrada
     */
    public function testFindByTenantAndIdNotFound(): void
    {
        $result = $this->commissionModel->findByTenantAndId(1, 999);

        $this->assertNull($result);
    }

    /**
     * Testa buscar comissão de outro tenant (proteção IDOR)
     */
    public function testFindByTenantAndIdDifferentTenant(): void
    {
        // Tenta buscar comissão ID 1 que pertence ao tenant 1, mas usando tenant 2
        $result = $this->commissionModel->findByTenantAndId(2, 1);

        $this->assertNull($result);
    }

    /**
     * Testa buscar comissões por tenant com paginação
     */
    public function testFindByTenantWithPagination(): void
    {
        $result = $this->commissionModel->findByTenant(1, 1, 2);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('total_pages', $result);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(2, $result['limit']);
        $this->assertEquals(4, $result['total']); // 4 comissões do tenant 1
        $this->assertCount(2, $result['data']); // 2 itens na primeira página
    }

    /**
     * Testa buscar comissões por tenant - segunda página
     */
    public function testFindByTenantSecondPage(): void
    {
        $result = $this->commissionModel->findByTenant(1, 2, 2);

        $this->assertEquals(2, $result['page']);
        $this->assertCount(2, $result['data']); // Mais 2 itens na segunda página
    }

    /**
     * Testa buscar comissões com filtro por user_id
     */
    public function testFindByTenantWithUserIdFilter(): void
    {
        $result = $this->commissionModel->findByTenant(1, 1, 20, ['user_id' => 5]);

        $this->assertIsArray($result);
        $this->assertEquals(2, $result['total']); // 2 comissões do user 5 no tenant 1
        $this->assertCount(2, $result['data']);
        
        // Verifica que todas são do user 5
        foreach ($result['data'] as $commission) {
            $this->assertEquals(5, $commission['user_id']);
        }
    }

    /**
     * Testa buscar comissões com filtro por status
     */
    public function testFindByTenantWithStatusFilter(): void
    {
        $result = $this->commissionModel->findByTenant(1, 1, 20, ['status' => 'paid']);

        $this->assertIsArray($result);
        $this->assertEquals(2, $result['total']); // 2 comissões pagas no tenant 1
        
        // Verifica que todas estão pagas
        foreach ($result['data'] as $commission) {
            $this->assertEquals('paid', $commission['status']);
        }
    }

    /**
     * Testa buscar comissões com filtro por data
     * 
     * ✅ CORRIGIDO: O método agora constrói a query SQL manualmente para compatibilidade
     */
    public function testFindByTenantWithDateFilter(): void
    {
        $result = $this->commissionModel->findByTenant(1, 1, 20, [
            'start_date' => '2024-01-16',
            'end_date' => '2024-01-17'
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('total_pages', $result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
        
        // Verifica que apenas comissões no período são retornadas
        foreach ($result['data'] as $commission) {
            $createdAt = strtotime($commission['created_at']);
            $startDate = strtotime('2024-01-16 00:00:00');
            $endDate = strtotime('2024-01-17 23:59:59');
            $this->assertGreaterThanOrEqual($startDate, $createdAt);
            $this->assertLessThanOrEqual($endDate, $createdAt);
        }
    }

    /**
     * Testa buscar comissões com ordenação customizada
     */
    public function testFindByTenantWithSorting(): void
    {
        $result = $this->commissionModel->findByTenant(1, 1, 20, [
            'sort' => 'commission_amount',
            'direction' => 'ASC'
        ]);

        $this->assertIsArray($result);
        $this->assertCount(4, $result['data']);
        
        // Verifica ordenação crescente
        $amounts = array_column($result['data'], 'commission_amount');
        $sortedAmounts = $amounts;
        sort($sortedAmounts);
        $this->assertEquals($sortedAmounts, $amounts);
    }

    /**
     * Testa marcar comissão como paga com sucesso
     */
    public function testMarkAsPaidSuccess(): void
    {
        $commissionId = 1;
        $paymentReference = 'PAY-12345';
        $notes = 'Pagamento via transferência bancária';

        $result = $this->commissionModel->markAsPaid($commissionId, $paymentReference, $notes);

        $this->assertTrue($result);

        // Verifica que foi atualizado no banco
        $stmt = $this->testDb->prepare("SELECT * FROM commissions WHERE id = ?");
        $stmt->execute([$commissionId]);
        $commission = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('paid', $commission['status']);
        $this->assertEquals($paymentReference, $commission['payment_reference']);
        $this->assertEquals($notes, $commission['notes']);
        $this->assertNotEmpty($commission['paid_at']);
    }

    /**
     * Testa marcar comissão como paga sem campos opcionais
     */
    public function testMarkAsPaidWithoutOptionalFields(): void
    {
        $commissionId = 3; // Comissão pendente

        $result = $this->commissionModel->markAsPaid($commissionId);

        $this->assertTrue($result);

        // Verifica que foi atualizado
        $stmt = $this->testDb->prepare("SELECT * FROM commissions WHERE id = ?");
        $stmt->execute([$commissionId]);
        $commission = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('paid', $commission['status']);
        $this->assertNotEmpty($commission['paid_at']);
    }

    /**
     * Testa calcular total por usuário
     */
    public function testGetTotalByUser(): void
    {
        $result = $this->commissionModel->getTotalByUser(1, 5);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('total_amount', $result);
        $this->assertArrayHasKey('paid_amount', $result);
        $this->assertArrayHasKey('pending_amount', $result);
        
        $this->assertEquals(2, $result['total_count']);
        $this->assertEquals(250.00, $result['total_amount']); // 100 + 150
        $this->assertEquals(150.00, $result['paid_amount']);
        $this->assertEquals(100.00, $result['pending_amount']);
    }

    /**
     * Testa calcular total por usuário com filtro de status
     */
    public function testGetTotalByUserWithStatus(): void
    {
        $result = $this->commissionModel->getTotalByUser(1, 5, 'paid');

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total_count']);
        $this->assertEquals(150.00, $result['total_amount']);
        $this->assertEquals(150.00, $result['paid_amount']);
        $this->assertEquals(0.00, $result['pending_amount']);
    }

    /**
     * Testa calcular total por usuário sem comissões
     */
    public function testGetTotalByUserNoCommissions(): void
    {
        $result = $this->commissionModel->getTotalByUser(1, 999);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_count']);
        $this->assertEquals(0.0, $result['total_amount']);
        $this->assertEquals(0.0, $result['paid_amount']);
        $this->assertEquals(0.0, $result['pending_amount']);
    }

    /**
     * Testa verificar se existe comissão para orçamento - existe
     */
    public function testExistsForBudgetTrue(): void
    {
        $result = $this->commissionModel->existsForBudget(10);

        $this->assertTrue($result);
    }

    /**
     * Testa verificar se existe comissão para orçamento - não existe
     */
    public function testExistsForBudgetFalse(): void
    {
        $result = $this->commissionModel->existsForBudget(999);

        $this->assertFalse($result);
    }

    /**
     * Testa buscar comissões com filtros combinados
     */
    public function testFindByTenantWithMultipleFilters(): void
    {
        $result = $this->commissionModel->findByTenant(1, 1, 20, [
            'user_id' => 5,
            'status' => 'pending'
        ]);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']); // Apenas 1 comissão pendente do user 5
        
        foreach ($result['data'] as $commission) {
            $this->assertEquals(5, $commission['user_id']);
            $this->assertEquals('pending', $commission['status']);
        }
    }
}

