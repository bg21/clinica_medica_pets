<?php

/**
 * Script para testar se todos os controllers podem ser instanciados
 * da mesma forma que são instanciados no index.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
Config::load();

use App\Core\Container;
use App\Core\ContainerBindings;

echo "🧪 TESTE DE INSTANCIAÇÃO DE CONTROLLERS\n";
echo str_repeat("=", 60) . "\n\n";

$container = new Container();
ContainerBindings::register($container);

// Lista de todos os controllers usados no index.php
$controllers = [
    'customerController' => \App\Controllers\CustomerController::class,
    'checkoutController' => \App\Controllers\CheckoutController::class,
    'subscriptionController' => \App\Controllers\SubscriptionController::class,
    'webhookController' => \App\Controllers\WebhookController::class,
    'billingPortalController' => \App\Controllers\BillingPortalController::class,
    'invoiceController' => \App\Controllers\InvoiceController::class,
    'priceController' => \App\Controllers\PriceController::class,
    'paymentController' => \App\Controllers\PaymentController::class,
    'statsController' => \App\Controllers\StatsController::class,
    'couponController' => \App\Controllers\CouponController::class,
    'productController' => \App\Controllers\ProductController::class,
    'promotionCodeController' => \App\Controllers\PromotionCodeController::class,
    'setupIntentController' => \App\Controllers\SetupIntentController::class,
    'subscriptionItemController' => \App\Controllers\SubscriptionItemController::class,
    'taxRateController' => \App\Controllers\TaxRateController::class,
    'invoiceItemController' => \App\Controllers\InvoiceItemController::class,
    'balanceTransactionController' => \App\Controllers\BalanceTransactionController::class,
    'disputeController' => \App\Controllers\DisputeController::class,
    'chargeController' => \App\Controllers\ChargeController::class,
    'payoutController' => \App\Controllers\PayoutController::class,
    'reportController' => \App\Controllers\ReportController::class,
    'auditLogController' => \App\Controllers\AuditLogController::class,
    'performanceController' => \App\Controllers\PerformanceController::class,
    'healthCheckController' => \App\Controllers\HealthCheckController::class,
    'swaggerController' => \App\Controllers\SwaggerController::class,
    'professionalController' => \App\Controllers\ProfessionalController::class,
    'specialtyController' => \App\Controllers\SpecialtyController::class,
    'appointmentController' => \App\Controllers\AppointmentController::class,
    'petController' => \App\Controllers\PetController::class,
    'clientController' => \App\Controllers\ClientController::class,
    'examTypeController' => \App\Controllers\ExamTypeController::class,
    'examController' => \App\Controllers\ExamController::class,
    'clinicController' => \App\Controllers\ClinicController::class,
    'authController' => \App\Controllers\AuthController::class,
    'userController' => \App\Controllers\UserController::class,
    'permissionController' => \App\Controllers\PermissionController::class,
    'traceController' => \App\Controllers\TraceController::class,
];

$passed = 0;
$failed = 0;
$errors = [];

foreach ($controllers as $varName => $className) {
    try {
        echo "Testando {$varName} ({$className})... ";
        
        $instance = $container->make($className);
        
        if ($instance instanceof $className) {
            echo "✅ OK\n";
            $passed++;
        } else {
            echo "❌ FALHOU (tipo incorreto)\n";
            $failed++;
            $errors[] = "{$varName}: Tipo incorreto";
        }
    } catch (\Throwable $e) {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "{$varName}: " . $e->getMessage();
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESULTADOS:\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: {$failed}\n";
echo "📈 Total: " . count($controllers) . "\n\n";

if ($failed > 0) {
    echo "❌ ERROS ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "✅ TODOS OS CONTROLLERS PODEM SER INSTANCIADOS!\n\n";
    exit(0);
}

