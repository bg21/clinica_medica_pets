<?php

namespace App\Core;

/**
 * Configuração de bindings do Container de Injeção de Dependências
 * 
 * Centraliza todos os bindings do sistema para facilitar manutenção
 */
class ContainerBindings
{
    /**
     * Registra todos os bindings no container
     * 
     * @param Container $container
     */
    public static function register(Container $container): void
    {
        // ============================================
        // CORE SERVICES (singletons)
        // ============================================
        $container->bind(EventDispatcher::class, EventDispatcher::class, true);
        
        // ============================================
        // MODELS (singletons - uma instância por request)
        // ============================================
        
        $container->bind(\App\Models\User::class, \App\Models\User::class, true);
        $container->bind(\App\Models\Customer::class, \App\Models\Customer::class, true);
        $container->bind(\App\Models\Subscription::class, \App\Models\Subscription::class, true);
        $container->bind(\App\Models\StripeEvent::class, \App\Models\StripeEvent::class, true);
        $container->bind(\App\Models\Tenant::class, \App\Models\Tenant::class, true);
        $container->bind(\App\Models\UserSession::class, \App\Models\UserSession::class, true);
        $container->bind(\App\Models\TenantRateLimit::class, \App\Models\TenantRateLimit::class, true);
        $container->bind(\App\Models\Pet::class, \App\Models\Pet::class, true);
        $container->bind(\App\Models\Professional::class, \App\Models\Professional::class, true);
        $container->bind(\App\Models\Appointment::class, \App\Models\Appointment::class, true);
        $container->bind(\App\Models\AppointmentPriceConfig::class, \App\Models\AppointmentPriceConfig::class, true);
        $container->bind(\App\Models\TenantStripeAccount::class, \App\Models\TenantStripeAccount::class, true);
        $container->bind(\App\Models\ClinicSpecialty::class, \App\Models\ClinicSpecialty::class, true);
        $container->bind(\App\Models\ProfessionalRole::class, \App\Models\ProfessionalRole::class, true);
        $container->bind(\App\Models\ProfessionalSchedule::class, \App\Models\ProfessionalSchedule::class, true);
        $container->bind(\App\Models\ScheduleBlock::class, \App\Models\ScheduleBlock::class, true);
        $container->bind(\App\Models\Exam::class, \App\Models\Exam::class, true);
        $container->bind(\App\Models\ExamType::class, \App\Models\ExamType::class, true);
        $container->bind(\App\Models\Budget::class, \App\Models\Budget::class, true);
        $container->bind(\App\Models\CommissionConfig::class, \App\Models\CommissionConfig::class, true);
        $container->bind(\App\Models\Commission::class, \App\Models\Commission::class, true);
        
        // ============================================
        // REPOSITORIES (singletons)
        // ============================================
        
        $container->bind(\App\Repositories\UserRepository::class, function(Container $container) {
            return new \App\Repositories\UserRepository(
                $container->make(\App\Models\User::class)
            );
        }, true);
        
        // ============================================
        // SERVICES (singletons)
        // ============================================
        
        $container->bind(\App\Services\StripeService::class, \App\Services\StripeService::class, true);
        
        $container->bind(\App\Services\PaymentService::class, function(Container $container) {
            return new \App\Services\PaymentService(
                $container->make(\App\Services\StripeService::class),
                $container->make(\App\Models\Customer::class),
                $container->make(\App\Models\Subscription::class),
                $container->make(\App\Models\StripeEvent::class)
            );
        }, true);
        
        $container->bind(\App\Services\EmailService::class, \App\Services\EmailService::class, true);
        $container->bind(\App\Services\RateLimiterService::class, \App\Services\RateLimiterService::class, true);
        $container->bind(\App\Services\PlanLimitsService::class, \App\Services\PlanLimitsService::class, true);
        
        $container->bind(\App\Services\StripeConnectService::class, function(Container $container) {
            return new \App\Services\StripeConnectService(
                $container->make(\App\Services\StripeService::class),
                $container->make(\App\Models\TenantStripeAccount::class)
            );
        }, true);
        
        // Service de integração de agendamentos com pagamentos
        $container->bind(\App\Services\AppointmentService::class, function(Container $container) {
            return new \App\Services\AppointmentService(
                $container->make(\App\Services\StripeService::class),
                $container->make(\App\Models\Appointment::class),
                $container->make(\App\Models\Customer::class),
                $container->make(\App\Models\Pet::class)
            );
        }, true);
        
        // Service de integração de exames com pagamentos
        $container->bind(\App\Services\ExamService::class, function(Container $container) {
            return new \App\Services\ExamService(
                $container->make(\App\Services\StripeService::class),
                $container->make(\App\Models\Exam::class),
                $container->make(\App\Models\ExamType::class),
                $container->make(\App\Models\Customer::class),
                $container->make(\App\Models\Pet::class)
            );
        }, true);
        
        // Service de orçamentos e comissões
        $container->bind(\App\Services\BudgetService::class, function(Container $container) {
            return new \App\Services\BudgetService(
                $container->make(\App\Models\Budget::class),
                $container->make(\App\Models\CommissionConfig::class),
                $container->make(\App\Models\Commission::class),
                $container->make(\App\Models\User::class)
            );
        }, true);
        
        // Service de comissões
        $container->bind(\App\Services\CommissionService::class, function(Container $container) {
            return new \App\Services\CommissionService(
                $container->make(\App\Models\Commission::class),
                $container->make(\App\Models\CommissionConfig::class)
            );
        }, true);
        
        // ✅ NOVO: Service para gerenciar limites de rate limiting por tenant
        $container->bind(\App\Services\TenantRateLimitService::class, function(Container $container) {
            return new \App\Services\TenantRateLimitService(
                $container->make(\App\Models\TenantRateLimit::class)
            );
        }, true);
        
        // ✅ NOVO: Services de domínio (lógica de negócio)
        $container->bind(\App\Services\UserService::class, function(Container $container) {
            return new \App\Services\UserService(
                $container->make(\App\Repositories\UserRepository::class),
                $container->make(\App\Models\Tenant::class)
            );
        }, true);
        
        // ============================================
        // CONTROLLERS (não são singletons - nova instância por request)
        // ============================================
        
        // Controllers que usam services
        $container->bind(\App\Controllers\UserController::class, function(Container $container) {
            return new \App\Controllers\UserController(
                $container->make(\App\Services\UserService::class),
                $container->make(\App\Repositories\UserRepository::class)
            );
        }, false);
        
        // Controllers que usam services
        $container->bind(\App\Controllers\SubscriptionController::class, function(Container $container) {
            return new \App\Controllers\SubscriptionController(
                $container->make(\App\Services\PaymentService::class),
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\CustomerController::class, function(Container $container) {
            return new \App\Controllers\CustomerController(
                $container->make(\App\Services\PaymentService::class),
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\CheckoutController::class, function(Container $container) {
            return new \App\Controllers\CheckoutController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\SaasController::class, function(Container $container) {
            return new \App\Controllers\SaasController(
                $container->make(\App\Services\StripeService::class),
                $container->make(\App\Services\PaymentService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\StripeConnectController::class, function(Container $container) {
            return new \App\Controllers\StripeConnectController(
                $container->make(\App\Services\StripeConnectService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\WebhookController::class, function(Container $container) {
            return new \App\Controllers\WebhookController(
                $container->make(\App\Services\PaymentService::class),
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\BillingPortalController::class, function(Container $container) {
            return new \App\Controllers\BillingPortalController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\InvoiceController::class, function(Container $container) {
            return new \App\Controllers\InvoiceController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\PriceController::class, function(Container $container) {
            return new \App\Controllers\PriceController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\PaymentController::class, function(Container $container) {
            return new \App\Controllers\PaymentController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\StatsController::class, function(Container $container) {
            return new \App\Controllers\StatsController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\CouponController::class, function(Container $container) {
            return new \App\Controllers\CouponController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\ProductController::class, function(Container $container) {
            return new \App\Controllers\ProductController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\PromotionCodeController::class, function(Container $container) {
            return new \App\Controllers\PromotionCodeController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\SetupIntentController::class, function(Container $container) {
            return new \App\Controllers\SetupIntentController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\SubscriptionItemController::class, function(Container $container) {
            return new \App\Controllers\SubscriptionItemController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\TaxRateController::class, function(Container $container) {
            return new \App\Controllers\TaxRateController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\InvoiceItemController::class, function(Container $container) {
            return new \App\Controllers\InvoiceItemController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\BalanceTransactionController::class, function(Container $container) {
            return new \App\Controllers\BalanceTransactionController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\DisputeController::class, function(Container $container) {
            return new \App\Controllers\DisputeController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\ChargeController::class, function(Container $container) {
            return new \App\Controllers\ChargeController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        $container->bind(\App\Controllers\PayoutController::class, function(Container $container) {
            return new \App\Controllers\PayoutController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        
        // Controllers que precisam de services
        $container->bind(\App\Controllers\ReportController::class, function(Container $container) {
            return new \App\Controllers\ReportController(
                $container->make(\App\Services\StripeService::class)
            );
        }, false);
        $container->bind(\App\Controllers\AuditLogController::class, \App\Controllers\AuditLogController::class, false);
        $container->bind(\App\Controllers\PerformanceController::class, \App\Controllers\PerformanceController::class, false);
        $container->bind(\App\Controllers\HealthCheckController::class, \App\Controllers\HealthCheckController::class, false);
        $container->bind(\App\Controllers\SwaggerController::class, \App\Controllers\SwaggerController::class, false);
        $container->bind(\App\Controllers\PermissionController::class, \App\Controllers\PermissionController::class, false);
        $container->bind(\App\Controllers\TraceController::class, \App\Controllers\TraceController::class, false);
        
        // Controllers de Clínica Veterinária
        $container->bind(\App\Controllers\PetController::class, \App\Controllers\PetController::class, false);
        $container->bind(\App\Controllers\ProfessionalController::class, \App\Controllers\ProfessionalController::class, false);
        $container->bind(\App\Controllers\ClinicSpecialtyController::class, \App\Controllers\ClinicSpecialtyController::class, false);
        $container->bind(\App\Controllers\ProfessionalScheduleController::class, \App\Controllers\ProfessionalScheduleController::class, false);
        $container->bind(\App\Controllers\ClinicController::class, \App\Controllers\ClinicController::class, false);
        $container->bind(\App\Controllers\ClinicReportController::class, \App\Controllers\ClinicReportController::class, false);
        $container->bind(\App\Controllers\ClinicDashboardController::class, function(Container $container) {
            return new \App\Controllers\ClinicDashboardController(
                $container->make(\App\Models\Appointment::class),
                $container->make(\App\Models\Pet::class),
                $container->make(\App\Models\Customer::class),
                $container->make(\App\Models\Professional::class)
            );
        }, false);
        $container->bind(\App\Services\FileUploadService::class, \App\Services\FileUploadService::class, false);
        $container->bind(\App\Controllers\FileController::class, function(Container $container) {
            return new \App\Controllers\FileController(
                $container->make(\App\Services\FileUploadService::class)
            );
        }, false);
        $container->bind(\App\Controllers\AppointmentController::class, function(Container $container) {
            return new \App\Controllers\AppointmentController(
                $container->make(\App\Services\AppointmentService::class)
            );
        }, false);
        $container->bind(\App\Controllers\AppointmentPriceConfigController::class, function(Container $container) {
            return new \App\Controllers\AppointmentPriceConfigController(
                $container->make(\App\Models\AppointmentPriceConfig::class),
                $container->make(\App\Models\Professional::class)
            );
        }, false);
        $container->bind(\App\Controllers\SearchController::class, function(Container $container) {
            return new \App\Controllers\SearchController(
                $container->make(\App\Models\Pet::class),
                $container->make(\App\Models\Customer::class),
                $container->make(\App\Models\Appointment::class),
                $container->make(\App\Models\Professional::class)
            );
        }, false);
        
        // Controller de Exames
        $container->bind(\App\Controllers\ExamController::class, function(Container $container) {
            return new \App\Controllers\ExamController(
                $container->make(\App\Services\ExamService::class)
            );
        }, false);
        
        // Controller de Orçamentos
        $container->bind(\App\Controllers\BudgetController::class, function(Container $container) {
            return new \App\Controllers\BudgetController(
                $container->make(\App\Services\BudgetService::class)
            );
        }, false);
        
        // Controller de Comissões
        $container->bind(\App\Controllers\CommissionController::class, function(Container $container) {
            return new \App\Controllers\CommissionController(
                $container->make(\App\Services\CommissionService::class)
            );
        }, false);
        
        // AuthController e UserController precisam de dependências específicas
        $container->bind(\App\Controllers\AuthController::class, function(Container $container) {
            return new \App\Controllers\AuthController();
        }, false);
    }
}

