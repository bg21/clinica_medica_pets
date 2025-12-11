<?php

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\CommissionController;
use App\Services\CommissionService;
use App\Models\Commission;
use App\Models\CommissionConfig;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;
use RuntimeException;

/**
 * Testes unitários para CommissionController
 * 
 * Cenários cobertos:
 * - Listar comissões (com e sem filtros)
 * - Buscar comissão por ID
 * - Marcar comissão como paga
 * - Buscar estatísticas por usuário
 * - Buscar estatísticas gerais
 * - Atualizar configuração
 * - Buscar configuração
 * - Validações de permissões e autenticação
 * - Validações de entrada
 */
class CommissionControllerTest extends TestCase
{
    private CommissionController $controller;
    private $mockCommissionService;
    private $mockCommissionModel;
    private $mockCommissionConfigModel;
    private $mockRequest;
    private $originalFlight;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpa output buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Salva estado original do Flight
        $this->originalFlight = Flight::class;

        // Limpa Flight
        Flight::clear();

        // Cria mocks
        $this->mockCommissionService = $this->createMock(CommissionService::class);
        $this->mockCommissionModel = $this->createMock(Commission::class);
        $this->mockCommissionConfigModel = $this->createMock(CommissionConfig::class);

        // Cria controller com injeção de dependência
        $this->controller = new CommissionController(
            $this->mockCommissionService,
            $this->mockCommissionModel,
            $this->mockCommissionConfigModel
        );

        // Mock do request do Flight
        $this->mockRequest = $this->createMock(\stdClass::class);
        $this->mockRequest->query = $this->createMock(\stdClass::class);

        // Configura Flight
        Flight::set('tenant_id', 1);
        Flight::set('is_user_auth', false);
        Flight::set('is_master', false);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        Flight::clear();
        unset($GLOBALS['mock_json_input']);
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tearDown();
    }

    /**
     * Helper para mockar Flight::request()
     */
    private function mockFlightRequest(array $queryParams = [], ?string $jsonBody = null): void
    {
        // Cria objeto request mockado
        $request = new \stdClass();
        $request->query = new \stdClass();
        
        // Atribui valores aos query params
        foreach ($queryParams as $key => $value) {
            $request->query->$key = $value;
        }
        
        // Mock Flight::request() usando map
        Flight::map('request', function () use ($request) {
            return $request;
        });

        // Mock JSON input se fornecido
        if ($jsonBody !== null) {
            $GLOBALS['mock_json_input'] = $jsonBody;
        } else {
            unset($GLOBALS['mock_json_input']);
        }
    }

    /**
     * Helper para capturar output do Flight::halt() e Flight::json()
     */
    private function captureFlightOutput(callable $callback): array
    {
        ob_start();
        
        try {
            $callback();
        } catch (\Exception $e) {
            // Flight::halt() lança exceção, capturamos aqui
        }
        
        $output = ob_get_clean();
        
        // Tenta decodificar JSON
        $decoded = json_decode($output, true);
        return $decoded ?: ['raw' => $output];
    }

    /**
     * Testa listar comissões com sucesso
     */
    public function testListSuccess(): void
    {
        $this->mockFlightRequest(['page' => '1', 'limit' => '20']);

        $expectedResult = [
            'data' => [
                [
                    'id' => 1,
                    'user_id' => 5,
                    'commission_amount' => 150.00,
                    'status' => 'pending'
                ]
            ],
            'total' => 1,
            'page' => 1,
            'limit' => 20,
            'total_pages' => 1
        ];

        // Mock PermissionHelper para não bloquear
        $this->mockPermissionHelper(true);

        // Mock Commission model
        $this->mockCommissionModel
            ->expects($this->once())
            ->method('findByTenant')
            ->with(1, 1, 20, [])
            ->willReturn($expectedResult);

        $output = $this->captureFlightOutput(function () {
            $this->controller->list();
        });

        $this->assertArrayHasKey('success', $output);
        $this->assertTrue($output['success']);
        $this->assertArrayHasKey('data', $output);
        $this->assertEquals(1, $output['data']['total']);
    }

    /**
     * Testa listar comissões sem autenticação
     */
    public function testListWithoutAuthentication(): void
    {
        Flight::set('tenant_id', null);

        $output = $this->captureFlightOutput(function () {
            $this->controller->list();
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Não autenticado', $output['error']);
    }

    /**
     * Testa listar comissões com filtros
     */
    public function testListWithFilters(): void
    {
        $this->mockFlightRequest([
            'page' => '1',
            'limit' => '10',
            'user_id' => '5',
            'status' => 'pending',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ]);

        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $expectedResult = [
            'data' => [],
            'total' => 0,
            'page' => 1,
            'limit' => 10,
            'total_pages' => 0
        ];

        $this->mockCommissionModel
            ->expects($this->once())
            ->method('findByTenant')
            ->with(1, 1, 10, [
                'user_id' => 5,
                'status' => 'pending',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31'
            ])
            ->willReturn($expectedResult);

        $output = $this->captureFlightOutput(function () {
            $this->controller->list();
        });

        $this->assertArrayHasKey('success', $output);
        $this->assertTrue($output['success']);
    }

    /**
     * Testa buscar comissão por ID com sucesso
     */
    public function testGetSuccess(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $commission = [
            'id' => 100,
            'tenant_id' => 1,
            'user_id' => 5,
            'commission_amount' => 150.00,
            'status' => 'pending'
        ];

        $this->mockCommissionModel
            ->expects($this->once())
            ->method('findByTenantAndId')
            ->with(1, 100)
            ->willReturn($commission);

        $output = $this->captureFlightOutput(function () {
            $this->controller->get('100');
        });

        $this->assertArrayHasKey('success', $output);
        $this->assertTrue($output['success']);
        $this->assertArrayHasKey('data', $output);
        $this->assertEquals(100, $output['data']['id']);
    }

    /**
     * Testa buscar comissão com ID inválido
     */
    public function testGetWithInvalidId(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $output = $this->captureFlightOutput(function () {
            $this->controller->get('0');
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Dados inválidos', $output['error']);
    }

    /**
     * Testa buscar comissão com ID negativo
     */
    public function testGetWithNegativeId(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $output = $this->captureFlightOutput(function () {
            $this->controller->get('-1');
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Dados inválidos', $output['error']);
    }

    /**
     * Testa buscar comissão não encontrada
     */
    public function testGetNotFound(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $this->mockCommissionModel
            ->expects($this->once())
            ->method('findByTenantAndId')
            ->with(1, 999)
            ->willReturn(null);

        $output = $this->captureFlightOutput(function () {
            $this->controller->get('999');
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Não encontrado', $output['error']);
    }

    /**
     * Testa marcar comissão como paga com sucesso
     */
    public function testMarkPaidSuccess(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $commissionId = 100;
        $paymentReference = 'PAY-12345';
        $notes = 'Pagamento via transferência';

        $updatedCommission = [
            'id' => $commissionId,
            'tenant_id' => 1,
            'user_id' => 5,
            'commission_amount' => 150.00,
            'status' => 'paid',
            'payment_reference' => $paymentReference,
            'notes' => $notes,
            'paid_at' => date('Y-m-d H:i:s')
        ];

        $this->mockCommissionService
            ->expects($this->once())
            ->method('markAsPaid')
            ->with(1, $commissionId, $paymentReference, $notes)
            ->willReturn($updatedCommission);

        $GLOBALS['mock_json_input'] = json_encode([
            'payment_reference' => $paymentReference,
            'notes' => $notes
        ]);

        $output = $this->captureFlightOutput(function () use ($commissionId) {
            $this->controller->markPaid($commissionId);
        });

        $this->assertArrayHasKey('success', $output);
        $this->assertTrue($output['success']);
        $this->assertArrayHasKey('data', $output);
        $this->assertEquals('paid', $output['data']['status']);
    }

    /**
     * Testa marcar comissão como paga sem dados opcionais
     */
    public function testMarkPaidWithoutOptionalFields(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $commissionId = 100;

        $updatedCommission = [
            'id' => $commissionId,
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ];

        $this->mockCommissionService
            ->expects($this->once())
            ->method('markAsPaid')
            ->with(1, $commissionId, null, null)
            ->willReturn($updatedCommission);

        $GLOBALS['mock_json_input'] = json_encode([]);

        $output = $this->captureFlightOutput(function () use ($commissionId) {
            $this->controller->markPaid($commissionId);
        });

        $this->assertArrayHasKey('success', $output);
        $this->assertTrue($output['success']);
    }

    /**
     * Testa marcar comissão como paga com ID inválido
     */
    public function testMarkPaidWithInvalidId(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $output = $this->captureFlightOutput(function () {
            $this->controller->markPaid('0');
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Dados inválidos', $output['error']);
    }

    /**
     * Testa marcar comissão como paga quando comissão não existe
     */
    public function testMarkPaidCommissionNotFound(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $commissionId = 999;

        $this->mockCommissionService
            ->expects($this->once())
            ->method('markAsPaid')
            ->with(1, $commissionId, null, null)
            ->willThrowException(new RuntimeException('Comissão não encontrada'));

        $GLOBALS['mock_json_input'] = json_encode([]);

        $output = $this->captureFlightOutput(function () use ($commissionId) {
            $this->controller->markPaid($commissionId);
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Comissão não encontrada', $output['message']);
    }

    /**
     * Testa marcar comissão como paga quando já está paga
     */
    public function testMarkPaidAlreadyPaid(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $commissionId = 100;

        $this->mockCommissionService
            ->expects($this->once())
            ->method('markAsPaid')
            ->with(1, $commissionId, null, null)
            ->willThrowException(new RuntimeException('Comissão já foi marcada como paga'));

        $GLOBALS['mock_json_input'] = json_encode([]);

        $output = $this->captureFlightOutput(function () use ($commissionId) {
            $this->controller->markPaid($commissionId);
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Comissão já foi marcada como paga', $output['message']);
    }

    /**
     * Testa buscar estatísticas por usuário com sucesso
     */
    public function testGetUserStatsSuccess(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $userId = 5;
        $stats = [
            'total_count' => 10,
            'total_amount' => 1500.00,
            'paid_amount' => 1000.00,
            'pending_amount' => 500.00
        ];

        $this->mockCommissionService
            ->expects($this->once())
            ->method('getStatsByUser')
            ->with(1, $userId)
            ->willReturn($stats);

        $output = $this->captureFlightOutput(function () use ($userId) {
            $this->controller->getUserStats($userId);
        });

        $this->assertArrayHasKey('success', $output);
        $this->assertTrue($output['success']);
        $this->assertArrayHasKey('data', $output);
        $this->assertEquals(10, $output['data']['total_count']);
    }

    /**
     * Testa buscar estatísticas por usuário com ID inválido
     */
    public function testGetUserStatsWithInvalidUserId(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $output = $this->captureFlightOutput(function () {
            $this->controller->getUserStats('0');
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Dados inválidos', $output['error']);
    }

    /**
     * Testa buscar estatísticas gerais com sucesso
     */
    public function testGetGeneralStatsSuccess(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelperCheck(true);

        $stats = [
            'total_count' => 50,
            'total_amount' => 5000.00,
            'paid_amount' => 3000.00,
            'pending_amount' => 2000.00,
            'total_users' => 5
        ];

        $this->mockCommissionService
            ->expects($this->once())
            ->method('getGeneralStats')
            ->with(1)
            ->willReturn($stats);

        $output = $this->captureFlightOutput(function () {
            $this->controller->getGeneralStats();
        });

        $this->assertArrayHasKey('success', $output);
        $this->assertTrue($output['success']);
        $this->assertArrayHasKey('data', $output);
        $this->assertEquals(50, $output['data']['total_count']);
    }

    /**
     * Testa buscar estatísticas gerais sem permissão
     */
    public function testGetGeneralStatsWithoutPermission(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelperCheck(false);

        $output = $this->captureFlightOutput(function () {
            $this->controller->getGeneralStats();
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Acesso negado', $output['error']);
    }

    /**
     * Testa atualizar configuração com sucesso
     */
    public function testUpdateConfigSuccess(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $config = [
            'id' => 1,
            'tenant_id' => 1,
            'commission_percentage' => 5.5,
            'is_active' => true
        ];

        $this->mockCommissionService
            ->expects($this->once())
            ->method('updateConfig')
            ->with(1, 5.5, true)
            ->willReturn($config);

        $GLOBALS['mock_json_input'] = json_encode([
            'commission_percentage' => 5.5,
            'is_active' => true
        ]);

        $output = $this->captureFlightOutput(function () {
            $this->controller->updateConfig();
        });

        $this->assertArrayHasKey('success', $output);
        $this->assertTrue($output['success']);
        $this->assertArrayHasKey('data', $output);
        $this->assertEquals(5.5, $output['data']['commission_percentage']);
    }

    /**
     * Testa atualizar configuração sem commission_percentage
     */
    public function testUpdateConfigWithoutPercentage(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $GLOBALS['mock_json_input'] = json_encode([
            'is_active' => true
        ]);

        $output = $this->captureFlightOutput(function () {
            $this->controller->updateConfig();
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Dados inválidos', $output['error']);
    }

    /**
     * Testa atualizar configuração com JSON inválido
     */
    public function testUpdateConfigWithInvalidJson(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $GLOBALS['mock_json_input'] = '{invalid json}';

        $output = $this->captureFlightOutput(function () {
            $this->controller->updateConfig();
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('JSON inválido', $output['error']);
    }

    /**
     * Testa atualizar configuração com porcentagem inválida
     */
    public function testUpdateConfigWithInvalidPercentage(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelper(true);

        $this->mockCommissionService
            ->expects($this->once())
            ->method('updateConfig')
            ->with(1, 150.0, true)
            ->willThrowException(new RuntimeException('Porcentagem deve estar entre 0 e 100'));

        $GLOBALS['mock_json_input'] = json_encode([
            'commission_percentage' => 150.0,
            'is_active' => true
        ]);

        $output = $this->captureFlightOutput(function () {
            $this->controller->updateConfig();
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Porcentagem deve estar entre 0 e 100', $output['message']);
    }

    /**
     * Testa buscar configuração com sucesso
     */
    public function testGetConfigSuccess(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelperCheck(true);

        $config = [
            'id' => 1,
            'tenant_id' => 1,
            'commission_percentage' => 5.5,
            'is_active' => true
        ];

        $this->mockCommissionConfigModel
            ->expects($this->once())
            ->method('findByTenant')
            ->with(1)
            ->willReturn($config);

        $output = $this->captureFlightOutput(function () {
            $this->controller->getConfig();
        });

        $this->assertArrayHasKey('success', $output);
        $this->assertTrue($output['success']);
        $this->assertArrayHasKey('data', $output);
        $this->assertEquals(5.5, $output['data']['commission_percentage']);
    }

    /**
     * Testa buscar configuração sem permissão
     */
    public function testGetConfigWithoutPermission(): void
    {
        Flight::set('tenant_id', 1);
        $this->mockPermissionHelperCheck(false);

        $output = $this->captureFlightOutput(function () {
            $this->controller->getConfig();
        });

        $this->assertArrayHasKey('error', $output);
        $this->assertEquals('Acesso negado', $output['error']);
    }

    /**
     * Helper para mockar PermissionHelper::require()
     */
    private function mockPermissionHelper(bool $shouldPass): void
    {
        // PermissionHelper é estático, então precisamos usar uma abordagem diferente
        // Por enquanto, vamos apenas configurar Flight para simular o comportamento
        if ($shouldPass) {
            Flight::set('is_user_auth', false); // API Key não precisa de permissão
        } else {
            Flight::set('is_user_auth', true);
            Flight::set('user_role', 'user'); // Não admin
        }
    }

    /**
     * Helper para mockar PermissionHelper::check()
     */
    private function mockPermissionHelperCheck(bool $shouldPass): void
    {
        if ($shouldPass) {
            Flight::set('is_user_auth', false); // API Key sempre passa
        } else {
            Flight::set('is_user_auth', true);
            Flight::set('user_role', 'user'); // Não admin, sem permissão
        }
    }
}

