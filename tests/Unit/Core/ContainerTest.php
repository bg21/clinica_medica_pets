<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Container;
use App\Core\ContainerBindings;
use App\Models\Appointment;
use App\Services\EmailService;
use App\Repositories\AppointmentRepository;

/**
 * Testes para o Container de Injeção de Dependências
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    /**
     * Testa se o container pode ser instanciado
     */
    public function testContainerCanBeInstantiated(): void
    {
        $container = new Container();
        $this->assertInstanceOf(Container::class, $container);
    }

    /**
     * Testa binding simples
     */
    public function testSimpleBinding(): void
    {
        $this->container->bind('test', fn() => 'test-value');
        $result = $this->container->make('test');
        $this->assertEquals('test-value', $result);
    }

    /**
     * Testa singleton pattern
     */
    public function testSingletonPattern(): void
    {
        $counter = 0;
        $this->container->bind('counter', function() use (&$counter) {
            $counter++;
            return $counter;
        }, true);

        $first = $this->container->make('counter');
        $second = $this->container->make('counter');

        $this->assertEquals(1, $first);
        $this->assertEquals(1, $second); // Deve ser a mesma instância
        $this->assertEquals(1, $counter); // Factory só foi chamada uma vez
    }

    /**
     * Testa auto-resolve de classe simples
     */
    public function testAutoResolveSimpleClass(): void
    {
        $instance = $this->container->make(EmailService::class);
        $this->assertInstanceOf(EmailService::class, $instance);
    }

    /**
     * Testa auto-resolve com dependências
     */
    public function testAutoResolveWithDependencies(): void
    {
        // Registra dependências
        $this->container->bind(Appointment::class, Appointment::class, true);
        $this->container->bind(\App\Models\AppointmentHistory::class, \App\Models\AppointmentHistory::class, true);

        $repository = $this->container->make(AppointmentRepository::class);
        $this->assertInstanceOf(AppointmentRepository::class, $repository);
    }

    /**
     * Testa se ContainerBindings registra todos os bindings
     */
    public function testContainerBindingsRegistration(): void
    {
        ContainerBindings::register($this->container);

        // Testa alguns bindings importantes
        $this->assertTrue($this->container->has(\App\Services\StripeService::class));
        $this->assertTrue($this->container->has(\App\Services\PaymentService::class));
        $this->assertTrue($this->container->has(\App\Repositories\AppointmentRepository::class));
    }

    /**
     * Testa resolução de Services
     */
    public function testResolveServices(): void
    {
        ContainerBindings::register($this->container);

        $stripeService = $this->container->make(\App\Services\StripeService::class);
        $this->assertInstanceOf(\App\Services\StripeService::class, $stripeService);

        // Deve ser singleton (mesma instância)
        $stripeService2 = $this->container->make(\App\Services\StripeService::class);
        $this->assertSame($stripeService, $stripeService2);
    }

    /**
     * Testa resolução de Repositories
     */
    public function testResolveRepositories(): void
    {
        ContainerBindings::register($this->container);

        $repository = $this->container->make(\App\Repositories\AppointmentRepository::class);
        $this->assertInstanceOf(\App\Repositories\AppointmentRepository::class, $repository);
    }

    /**
     * Testa resolução de Controllers
     */
    public function testResolveControllers(): void
    {
        ContainerBindings::register($this->container);

        $controller = $this->container->make(\App\Controllers\AppointmentController::class);
        $this->assertInstanceOf(\App\Controllers\AppointmentController::class, $controller);
    }

    /**
     * Testa que controllers não são singletons
     */
    public function testControllersAreNotSingletons(): void
    {
        ContainerBindings::register($this->container);

        $controller1 = $this->container->make(\App\Controllers\AppointmentController::class);
        $controller2 = $this->container->make(\App\Controllers\AppointmentController::class);

        $this->assertNotSame($controller1, $controller2);
    }

    /**
     * Testa erro quando classe não existe
     */
    public function testThrowsExceptionWhenClassNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Classe NonExistentClass não encontrada');
        
        $this->container->make('NonExistentClass');
    }

    /**
     * Testa método has()
     */
    public function testHasMethod(): void
    {
        $this->assertFalse($this->container->has('test'));
        
        $this->container->bind('test', fn() => 'value');
        $this->assertTrue($this->container->has('test'));
    }

    /**
     * Testa método clear()
     */
    public function testClearMethod(): void
    {
        $this->container->bind('test', fn() => 'value');
        $this->assertTrue($this->container->has('test'));
        
        $this->container->clear();
        $this->assertFalse($this->container->has('test'));
    }
}

