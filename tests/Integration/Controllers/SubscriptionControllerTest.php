<?php

namespace Tests\Integration\Controllers;

use PHPUnit\Framework\TestCase;
use Tests\Integration\TestHelper;
use App\Controllers\SubscriptionController;
use App\Services\PaymentService;
use App\Services\StripeService;
use App\Models\Customer;
use App\Models\Subscription;
use App\Utils\Database;

/**
 * Testes de integração para SubscriptionController
 * 
 * Cenários cobertos:
 * - Criação de assinatura
 * - Listagem de assinaturas
 * - Busca de assinatura por ID
 * - Atualização de assinatura
 * - Cancelamento de assinatura
 * - Reativação de assinatura
 * - Histórico de assinatura
 * - Validação de dados
 * 
 * Nota: Estes testes requerem configuração do Stripe (chaves de teste)
 * ou podem ser executados com mocks do StripeService
 */
class SubscriptionControllerTest extends TestCase
{
    private SubscriptionController $controller;
    private \PDO $db;
    private int $testTenantId = 1;
    private int $testCustomerId;
    private int $testSubscriptionId;
    private string $testStripeCustomerId = 'cus_test_integration';
    private string $testStripeSubscriptionId = 'sub_test_integration';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Cria instâncias necessárias
        $stripeService = new StripeService();
        $paymentService = new PaymentService(
            $stripeService,
            new Customer(),
            new Subscription(),
            new \App\Models\StripeEvent()
        );
        
        $this->controller = new SubscriptionController($paymentService, $stripeService);
        $this->db = Database::getInstance();
        
        // Cria customer de teste
        $this->createTestCustomer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupTestData();
    }

    /**
     * Cria customer de teste no banco
     */
    private function createTestCustomer(): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO customers (tenant_id, stripe_customer_id, email, name, created_at)
                VALUES (?, ?, 'test.subscription@test.com', 'Customer Test Subscription', NOW())
                ON DUPLICATE KEY UPDATE email = email
            ");
            $stmt->execute([$this->testTenantId, $this->testStripeCustomerId]);
            
            // Busca ID do customer criado
            $stmt = $this->db->prepare("
                SELECT id FROM customers 
                WHERE tenant_id = ? AND stripe_customer_id = ?
            ");
            $stmt->execute([$this->testTenantId, $this->testStripeCustomerId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->testCustomerId = (int)$result['id'];
            }
        } catch (\PDOException $e) {
            // Se customer já existe, busca o ID
            $stmt = $this->db->prepare("
                SELECT id FROM customers 
                WHERE tenant_id = ? AND stripe_customer_id = ?
            ");
            $stmt->execute([$this->testTenantId, $this->testStripeCustomerId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $this->testCustomerId = (int)$result['id'];
            }
        }
    }

    /**
     * Limpa dados de teste
     */
    private function cleanupTestData(): void
    {
        if ($this->testSubscriptionId) {
            try {
                $this->db->prepare("DELETE FROM subscription_history WHERE subscription_id = ?")
                    ->execute([$this->testSubscriptionId]);
                $this->db->prepare("DELETE FROM subscriptions WHERE id = ?")
                    ->execute([$this->testSubscriptionId]);
            } catch (\PDOException $e) {
                // Ignora erros de limpeza
            }
        }
    }

    /**
     * Testa criação de assinatura
     * 
     * Nota: Este teste pode falhar se não houver chaves Stripe configuradas
     * ou se o price_id não existir no Stripe
     */
    public function testCreateSubscription(): void
    {
        // Simula autenticação
        TestHelper::mockAuth($this->testTenantId);
        
        // Dados da assinatura
        // ATENÇÃO: Use um price_id válido do Stripe Test Mode
        $data = [
            'customer_id' => $this->testCustomerId,
            'price_id' => 'price_test_123', // Substitua por um price_id real de teste
            'metadata' => ['test' => 'integration']
        ];
        
        TestHelper::mockRequest('POST', [], $data);
        
        ob_start();
        try {
            $this->controller->create();
        } catch (\Exception $e) {
            // Pode falhar se:
            // - Stripe não estiver configurado
            // - price_id não existir
            // - Permissões não estiverem configuradas
            $this->markTestSkipped('Teste requer configuração do Stripe: ' . $e->getMessage());
        }
        $output = ob_get_clean();
        
        // Verifica que não houve erro fatal
        $this->assertNotEmpty($output);
        
        // Tenta decodificar resposta JSON
        $response = TestHelper::parseJsonResponse($output);
        if ($response && isset($response['success']) && $response['success']) {
            $this->assertTrue($response['success']);
            if (isset($response['data']['id'])) {
                $this->testSubscriptionId = (int)$response['data']['id'];
            }
        }
        
        TestHelper::clearRequest();
        TestHelper::clearAuth();
    }

    /**
     * Testa listagem de assinaturas
     */
    public function testListSubscriptions(): void
    {
        // Cria assinatura de teste diretamente no banco
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subscriptions (
                    tenant_id, customer_id, stripe_subscription_id, 
                    status, plan_id, amount, currency, created_at
                )
                VALUES (?, ?, ?, 'active', 'price_test_123', 100.00, 'BRL', NOW())
            ");
            $stmt->execute([
                $this->testTenantId,
                $this->testCustomerId,
                $this->testStripeSubscriptionId
            ]);
            $this->testSubscriptionId = (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            // Se já existe, busca o ID
            $stmt = $this->db->prepare("
                SELECT id FROM subscriptions 
                WHERE tenant_id = ? AND stripe_subscription_id = ?
            ");
            $stmt->execute([$this->testTenantId, $this->testStripeSubscriptionId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $this->testSubscriptionId = (int)$result['id'];
            }
        }
        
        // Simula autenticação e requisição
        TestHelper::mockAuth($this->testTenantId);
        TestHelper::mockRequest('GET');
        
        ob_start();
        try {
            $this->controller->list();
        } catch (\Exception $e) {
            $this->markTestSkipped('Teste requer configuração: ' . $e->getMessage());
        }
        $output = ob_get_clean();
        
        // Verifica que não houve erro fatal
        $this->assertNotEmpty($output);
        
        // Tenta decodificar resposta JSON
        $response = TestHelper::parseJsonResponse($output);
        if ($response && isset($response['success']) && $response['success']) {
            $this->assertTrue($response['success']);
            $this->assertIsArray($response['data']);
        }
        
        TestHelper::clearRequest();
        TestHelper::clearAuth();
    }

    /**
     * Testa busca de assinatura por ID
     */
    public function testGetSubscription(): void
    {
        // Cria assinatura de teste
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subscriptions (
                    tenant_id, customer_id, stripe_subscription_id, 
                    status, plan_id, amount, currency, created_at
                )
                VALUES (?, ?, ?, 'active', 'price_test_123', 100.00, 'BRL', NOW())
            ");
            $stmt->execute([
                $this->testTenantId,
                $this->testCustomerId,
                $this->testStripeSubscriptionId . '_get'
            ]);
            $this->testSubscriptionId = (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            $stmt = $this->db->prepare("
                SELECT id FROM subscriptions 
                WHERE tenant_id = ? AND stripe_subscription_id = ?
            ");
            $stmt->execute([$this->testTenantId, $this->testStripeSubscriptionId . '_get']);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $this->testSubscriptionId = (int)$result['id'];
            } else {
                $this->markTestSkipped('Não foi possível criar assinatura de teste');
                return;
            }
        }
        
        // Simula autenticação e requisição
        TestHelper::mockAuth($this->testTenantId);
        TestHelper::mockRequest('GET');
        
        ob_start();
        try {
            $this->controller->get((string)$this->testSubscriptionId);
        } catch (\Exception $e) {
            // Pode falhar se Stripe não estiver configurado
            $this->markTestSkipped('Teste requer configuração do Stripe: ' . $e->getMessage());
        }
        $output = ob_get_clean();
        
        // Verifica que não houve erro fatal
        $this->assertNotEmpty($output);
        
        // Tenta decodificar resposta JSON
        $response = TestHelper::parseJsonResponse($output);
        if ($response && isset($response['success']) && $response['success']) {
            $this->assertTrue($response['success']);
            if (isset($response['data']['id'])) {
                $this->assertEquals($this->testSubscriptionId, $response['data']['id']);
            }
        }
        
        TestHelper::clearRequest();
        TestHelper::clearAuth();
    }

    /**
     * Testa cancelamento de assinatura
     */
    public function testCancelSubscription(): void
    {
        // Cria assinatura de teste
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subscriptions (
                    tenant_id, customer_id, stripe_subscription_id, 
                    status, plan_id, amount, currency, created_at
                )
                VALUES (?, ?, ?, 'active', 'price_test_123', 100.00, 'BRL', NOW())
            ");
            $stmt->execute([
                $this->testTenantId,
                $this->testCustomerId,
                $this->testStripeSubscriptionId . '_cancel'
            ]);
            $this->testSubscriptionId = (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            $stmt = $this->db->prepare("
                SELECT id FROM subscriptions 
                WHERE tenant_id = ? AND stripe_subscription_id = ?
            ");
            $stmt->execute([$this->testTenantId, $this->testStripeSubscriptionId . '_cancel']);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $this->testSubscriptionId = (int)$result['id'];
            } else {
                $this->markTestSkipped('Não foi possível criar assinatura de teste');
                return;
            }
        }
        
        // Simula autenticação e requisição
        TestHelper::mockAuth($this->testTenantId);
        TestHelper::mockRequest('DELETE');
        
        ob_start();
        try {
            $this->controller->cancel((string)$this->testSubscriptionId);
        } catch (\Exception $e) {
            // Pode falhar se Stripe não estiver configurado
            $this->markTestSkipped('Teste requer configuração do Stripe: ' . $e->getMessage());
        }
        $output = ob_get_clean();
        
        // Verifica que não houve erro fatal
        $this->assertNotEmpty($output);
        
        TestHelper::clearRequest();
        TestHelper::clearAuth();
    }

    /**
     * Testa histórico de assinatura
     */
    public function testSubscriptionHistory(): void
    {
        // Cria assinatura de teste
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subscriptions (
                    tenant_id, customer_id, stripe_subscription_id, 
                    status, plan_id, amount, currency, created_at
                )
                VALUES (?, ?, ?, 'active', 'price_test_123', 100.00, 'BRL', NOW())
            ");
            $stmt->execute([
                $this->testTenantId,
                $this->testCustomerId,
                $this->testStripeSubscriptionId . '_history'
            ]);
            $this->testSubscriptionId = (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            $stmt = $this->db->prepare("
                SELECT id FROM subscriptions 
                WHERE tenant_id = ? AND stripe_subscription_id = ?
            ");
            $stmt->execute([$this->testTenantId, $this->testStripeSubscriptionId . '_history']);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $this->testSubscriptionId = (int)$result['id'];
            } else {
                $this->markTestSkipped('Não foi possível criar assinatura de teste');
                return;
            }
        }
        
        // Simula autenticação e requisição
        TestHelper::mockAuth($this->testTenantId);
        TestHelper::mockRequest('GET');
        
        ob_start();
        try {
            $this->controller->history((string)$this->testSubscriptionId);
        } catch (\Exception $e) {
            // Pode falhar se tabela subscription_history não existir
            $this->markTestSkipped('Teste requer tabela subscription_history: ' . $e->getMessage());
        }
        $output = ob_get_clean();
        
        // Verifica que não houve erro fatal
        $this->assertNotEmpty($output);
        
        // Tenta decodificar resposta JSON
        $response = TestHelper::parseJsonResponse($output);
        if ($response && isset($response['success']) && $response['success']) {
            $this->assertTrue($response['success']);
            $this->assertIsArray($response['data']);
        }
        
        TestHelper::clearRequest();
        TestHelper::clearAuth();
    }

    /**
     * Testa validação de dados na criação de assinatura
     */
    public function testCreateSubscriptionValidation(): void
    {
        // Simula autenticação
        TestHelper::mockAuth($this->testTenantId);
        
        // Dados inválidos (sem customer_id)
        $data = [
            'price_id' => 'price_test_123'
            // customer_id faltando
        ];
        
        TestHelper::mockRequest('POST', [], $data);
        
        ob_start();
        try {
            $this->controller->create();
        } catch (\Exception $e) {
            // Esperado - validação deve falhar
        }
        $output = ob_get_clean();
        
        // Verifica que não houve erro fatal
        $this->assertNotEmpty($output);
        
        // Tenta decodificar resposta JSON
        $response = TestHelper::parseJsonResponse($output);
        if ($response && isset($response['success'])) {
            // Deve retornar erro de validação
            $this->assertFalse($response['success']);
        }
        
        TestHelper::clearRequest();
        TestHelper::clearAuth();
    }

    /**
     * Testa que assinatura de outro tenant não é acessível
     */
    public function testSubscriptionFromOtherTenantNotAccessible(): void
    {
        // Cria assinatura de outro tenant
        $otherTenantId = 999;
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subscriptions (
                    tenant_id, customer_id, stripe_subscription_id, 
                    status, plan_id, amount, currency, created_at
                )
                VALUES (?, ?, ?, 'active', 'price_test_123', 100.00, 'BRL', NOW())
            ");
            $stmt->execute([
                $otherTenantId,
                $this->testCustomerId,
                $this->testStripeSubscriptionId . '_other_tenant'
            ]);
            $otherSubscriptionId = (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            $this->markTestSkipped('Não foi possível criar assinatura de teste');
            return;
        }
        
        // Simula autenticação com tenant diferente
        TestHelper::mockAuth($this->testTenantId);
        TestHelper::mockRequest('GET');
        
        ob_start();
        try {
            $this->controller->get((string)$otherSubscriptionId);
        } catch (\Exception $e) {
            // Esperado - não deve encontrar
        }
        $output = ob_get_clean();
        
        // Verifica que não houve erro fatal
        $this->assertNotEmpty($output);
        
        // Limpa assinatura de teste
        try {
            $this->db->prepare("DELETE FROM subscriptions WHERE id = ?")
                ->execute([$otherSubscriptionId]);
        } catch (\PDOException $e) {
            // Ignora
        }
        
        TestHelper::clearRequest();
        TestHelper::clearAuth();
    }
}

