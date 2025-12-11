<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\CommissionConfig;
use PDO;

/**
 * Testes unitários para CommissionConfig Model
 * 
 * Cenários cobertos:
 * - Buscar configuração por tenant
 * - Criar configuração (upsert)
 * - Atualizar configuração existente (upsert)
 * - Verificar se comissão está ativa
 * - Retornar porcentagem de comissão
 */
class CommissionConfigTest extends TestCase
{
    private ?PDO $testDb = null;
    private CommissionConfig $configModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Cria banco SQLite em memória
        $this->testDb = new PDO('sqlite::memory:');
        $this->testDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Cria tabela
        $this->testDb->exec("
            CREATE TABLE commission_config (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id INTEGER NOT NULL UNIQUE,
                commission_percentage DECIMAL(5,2) NOT NULL,
                is_active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insere dados de teste
        $this->testDb->exec("
            INSERT INTO commission_config (tenant_id, commission_percentage, is_active, created_at)
            VALUES 
                (1, 5.00, 1, '2024-01-01 10:00:00'),
                (2, 10.00, 0, '2024-01-01 10:00:00'),
                (3, 0.00, 1, '2024-01-01 10:00:00')
        ");

        // Cria instância do modelo e injeta banco de teste
        $this->configModel = new CommissionConfig();
        $reflection = new \ReflectionClass($this->configModel);
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $dbProperty->setValue($this->configModel, $this->testDb);
    }

    protected function tearDown(): void
    {
        if ($this->testDb !== null) {
            $this->testDb = null;
        }
        parent::tearDown();
    }

    /**
     * Testa buscar configuração por tenant com sucesso
     */
    public function testFindByTenantSuccess(): void
    {
        $result = $this->configModel->findByTenant(1);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals(1, $result['tenant_id']);
        $this->assertEquals(5.00, $result['commission_percentage']);
        $this->assertEquals(1, $result['is_active']);
    }

    /**
     * Testa buscar configuração por tenant - não encontrada
     */
    public function testFindByTenantNotFound(): void
    {
        $result = $this->configModel->findByTenant(999);

        $this->assertNull($result);
    }

    /**
     * Testa criar nova configuração (upsert)
     */
    public function testUpsertCreateNew(): void
    {
        $tenantId = 4;
        $percentage = 7.5;
        $isActive = true;

        $result = $this->configModel->upsert($tenantId, $percentage, $isActive);

        $this->assertTrue($result);

        // Verifica que foi criada
        $config = $this->configModel->findByTenant($tenantId);
        $this->assertIsArray($config);
        $this->assertEquals($tenantId, $config['tenant_id']);
        $this->assertEquals($percentage, $config['commission_percentage']);
        $this->assertEquals($isActive, $config['is_active']);
    }

    /**
     * Testa atualizar configuração existente (upsert)
     */
    public function testUpsertUpdateExisting(): void
    {
        $tenantId = 1;
        $newPercentage = 8.5;
        $newIsActive = false;

        $result = $this->configModel->upsert($tenantId, $newPercentage, $newIsActive);

        $this->assertTrue($result);

        // Verifica que foi atualizada
        $config = $this->configModel->findByTenant($tenantId);
        $this->assertIsArray($config);
        $this->assertEquals($newPercentage, $config['commission_percentage']);
        $this->assertEquals($newIsActive, $config['is_active']);
    }

    /**
     * Testa upsert com porcentagem zero
     */
    public function testUpsertWithZeroPercentage(): void
    {
        $tenantId = 5;
        $percentage = 0.0;
        $isActive = true;

        $result = $this->configModel->upsert($tenantId, $percentage, $isActive);

        $this->assertTrue($result);

        $config = $this->configModel->findByTenant($tenantId);
        $this->assertEquals(0.0, $config['commission_percentage']);
    }

    /**
     * Testa upsert com porcentagem máxima
     */
    public function testUpsertWithMaxPercentage(): void
    {
        $tenantId = 6;
        $percentage = 100.0;
        $isActive = true;

        $result = $this->configModel->upsert($tenantId, $percentage, $isActive);

        $this->assertTrue($result);

        $config = $this->configModel->findByTenant($tenantId);
        $this->assertEquals(100.0, $config['commission_percentage']);
    }

    /**
     * Testa verificar se comissão está ativa - ativa
     */
    public function testIsActiveTrue(): void
    {
        $result = $this->configModel->isActive(1);

        $this->assertTrue($result);
    }

    /**
     * Testa verificar se comissão está ativa - inativa
     */
    public function testIsActiveFalse(): void
    {
        $result = $this->configModel->isActive(2);

        $this->assertFalse($result);
    }

    /**
     * Testa verificar se comissão está ativa - porcentagem zero
     */
    public function testIsActiveWithZeroPercentage(): void
    {
        $result = $this->configModel->isActive(3);

        $this->assertFalse($result); // Porcentagem zero = inativa
    }

    /**
     * Testa verificar se comissão está ativa - não existe configuração
     */
    public function testIsActiveNoConfig(): void
    {
        $result = $this->configModel->isActive(999);

        $this->assertFalse($result);
    }

    /**
     * Testa retornar porcentagem de comissão - com configuração ativa
     */
    public function testGetPercentageActive(): void
    {
        $result = $this->configModel->getPercentage(1);

        $this->assertEquals(5.00, $result);
    }

    /**
     * Testa retornar porcentagem de comissão - configuração inativa
     */
    public function testGetPercentageInactive(): void
    {
        $result = $this->configModel->getPercentage(2);

        $this->assertEquals(0.0, $result); // Retorna 0 se inativa
    }

    /**
     * Testa retornar porcentagem de comissão - não existe configuração
     */
    public function testGetPercentageNoConfig(): void
    {
        $result = $this->configModel->getPercentage(999);

        $this->assertEquals(0.0, $result);
    }

    /**
     * Testa retornar porcentagem de comissão - porcentagem zero mas ativa
     */
    public function testGetPercentageZeroButActive(): void
    {
        $result = $this->configModel->getPercentage(3);

        $this->assertEquals(0.0, $result); // Porcentagem zero retorna 0 mesmo se is_active = 1
    }

    /**
     * Testa atualizar configuração mantendo alguns valores
     */
    public function testUpsertPartialUpdate(): void
    {
        $tenantId = 1;
        $newPercentage = 12.5;
        // Mantém is_active como true (padrão)

        $result = $this->configModel->upsert($tenantId, $newPercentage, true);

        $this->assertTrue($result);

        $config = $this->configModel->findByTenant($tenantId);
        $this->assertEquals($newPercentage, $config['commission_percentage']);
        $this->assertEquals(1, $config['is_active']);
    }

    /**
     * Testa criar múltiplas configurações para diferentes tenants
     */
    public function testUpsertMultipleTenants(): void
    {
        $tenants = [
            ['id' => 10, 'percentage' => 3.5, 'active' => true],
            ['id' => 11, 'percentage' => 6.0, 'active' => true],
            ['id' => 12, 'percentage' => 9.5, 'active' => false]
        ];

        foreach ($tenants as $tenant) {
            $result = $this->configModel->upsert(
                $tenant['id'],
                $tenant['percentage'],
                $tenant['active']
            );
            $this->assertTrue($result);
        }

        // Verifica todas
        foreach ($tenants as $tenant) {
            $config = $this->configModel->findByTenant($tenant['id']);
            $this->assertIsArray($config);
            $this->assertEquals($tenant['percentage'], $config['commission_percentage']);
            $this->assertEquals($tenant['active'], $config['is_active']);
        }
    }
}

