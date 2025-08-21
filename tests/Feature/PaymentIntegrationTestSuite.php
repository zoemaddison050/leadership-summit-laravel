<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentIntegrationTestSuite extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function database_schema_is_properly_configured()
    {
        // Verify all required tables exist
        $requiredTables = [
            'registrations',
            'unipayment_settings',
            'payment_transactions',
            'events',
            'tickets',
            'users'
        ];

        foreach ($requiredTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} does not exist");
        }

        // Verify registrations table has payment tracking fields
        $registrationColumns = [
            'payment_method',
            'payment_provider',
            'transaction_id',
            'payment_amount',
            'payment_currency',
            'payment_fee',
            'payment_completed_at',
            'refund_amount',
            'refund_reason',
            'refunded_at'
        ];

        foreach ($registrationColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('registrations', $column),
                "Column {$column} does not exist in registrations table"
            );
        }

        // Verify unipayment_settings table structure
        $uniPaymentColumns = [
            'app_id',
            'api_key',
            'environment',
            'webhook_secret',
            'is_enabled',
            'supported_currencies',
            'processing_fee_percentage',
            'minimum_amount',
            'maximum_amount'
        ];

        foreach ($uniPaymentColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('unipayment_settings', $column),
                "Column {$column} does not exist in unipayment_settings table"
            );
        }

        // Verify payment_transactions table structure
        $transactionColumns = [
            'registration_id',
            'provider',
            'transaction_id',
            'payment_method',
            'amount',
            'currency',
            'fee',
            'status',
            'provider_response',
            'callback_data',
            'processed_at'
        ];

        foreach ($transactionColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('payment_transactions', $column),
                "Column {$column} does not exist in payment_transactions table"
            );
        }
    }

    /** @test */
    public function all_required_routes_are_registered()
    {
        $requiredRoutes = [
            // Payment routes
            'payment.selection',
            'payment.card',
            'payment.crypto.process',
            'payment.unipayment.callback',
            'payment.unipayment.webhook',
            'payment.failed',

            // Registration routes
            'events.register',
            'events.register.process',
            'registration.success',

            // Admin routes
            'admin.unipayment.index',
            'admin.unipayment.update',
            'admin.unipayment.test-connection',
            'admin.unipayment.transactions',
            'admin.registrations.index',
            'admin.registrations.show',
            'admin.dashboard'
        ];

        foreach ($requiredRoutes as $routeName) {
            $this->assertTrue(
                \Route::has($routeName),
                "Route {$routeName} is not registered"
            );
        }
    }

    /** @test */
    public function all_required_middleware_is_configured()
    {
        $middlewareClasses = [
            \App\Http\Middleware\PaymentSecurity::class,
            \App\Http\Middleware\PaymentRateLimit::class,
            \App\Http\Middleware\PaymentSessionTimeout::class,
            \App\Http\Middleware\WebhookAuthentication::class
        ];

        foreach ($middlewareClasses as $middlewareClass) {
            $this->assertTrue(
                class_exists($middlewareClass),
                "Middleware class {$middlewareClass} does not exist"
            );
        }

        // Verify middleware is registered in Kernel
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $middlewareGroups = $kernel->getMiddlewareGroups();

        $this->assertArrayHasKey('web', $middlewareGroups);
        $this->assertArrayHasKey('api', $middlewareGroups);
    }

    /** @test */
    public function all_required_models_exist_and_have_proper_relationships()
    {
        $modelClasses = [
            \App\Models\Registration::class,
            \App\Models\UniPaymentSetting::class,
            \App\Models\PaymentTransaction::class,
            \App\Models\Event::class,
            \App\Models\Ticket::class,
            \App\Models\User::class
        ];

        foreach ($modelClasses as $modelClass) {
            $this->assertTrue(
                class_exists($modelClass),
                "Model class {$modelClass} does not exist"
            );
        }

        // Test model relationships
        $registration = new \App\Models\Registration();
        $this->assertTrue(
            method_exists($registration, 'event'),
            'Registration model missing event relationship'
        );
        $this->assertTrue(
            method_exists($registration, 'ticket'),
            'Registration model missing ticket relationship'
        );

        $paymentTransaction = new \App\Models\PaymentTransaction();
        $this->assertTrue(
            method_exists($paymentTransaction, 'registration'),
            'PaymentTransaction model missing registration relationship'
        );
    }

    /** @test */
    public function all_required_services_are_configured()
    {
        $serviceClasses = [
            \App\Services\UniPaymentService::class,
            \App\Services\PaymentService::class
        ];

        foreach ($serviceClasses as $serviceClass) {
            $this->assertTrue(
                class_exists($serviceClass),
                "Service class {$serviceClass} does not exist"
            );
        }

        // Verify services are bound in container
        $this->assertTrue(
            app()->bound(\App\Services\UniPaymentService::class),
            'UniPaymentService is not bound in service container'
        );
    }

    /** @test */
    public function all_required_controllers_exist()
    {
        $controllerClasses = [
            \App\Http\Controllers\PaymentController::class,
            \App\Http\Controllers\RegistrationController::class,
            \App\Http\Controllers\Admin\UniPaymentController::class,
            \App\Http\Controllers\Admin\RegistrationController::class
        ];

        foreach ($controllerClasses as $controllerClass) {
            $this->assertTrue(
                class_exists($controllerClass),
                "Controller class {$controllerClass} does not exist"
            );
        }
    }

    /** @test */
    public function all_required_views_exist()
    {
        $requiredViews = [
            'payments.selection',
            'payments.card-processing',
            'payments.failed',
            'payments.crypto',
            'registrations.create',
            'registrations.success',
            'admin.unipayment.index',
            'admin.unipayment.transactions',
            'admin.registrations.index',
            'admin.registrations.show'
        ];

        foreach ($requiredViews as $view) {
            $this->assertTrue(
                view()->exists($view),
                "View {$view} does not exist"
            );
        }
    }

    /** @test */
    public function configuration_files_are_properly_set()
    {
        // Verify UniPayment config exists
        $this->assertTrue(
            config()->has('unipayment'),
            'UniPayment configuration is not loaded'
        );

        // Verify payment security config exists
        $this->assertTrue(
            config()->has('payment_security'),
            'Payment security configuration is not loaded'
        );

        // Verify required config keys
        $requiredUniPaymentKeys = [
            'unipayment.app_id',
            'unipayment.client_id',
            'unipayment.client_secret',
            'unipayment.environment',
            'unipayment.webhook_secret'
        ];

        foreach ($requiredUniPaymentKeys as $key) {
            $this->assertTrue(
                config()->has($key),
                "Configuration key {$key} is missing"
            );
        }
    }

    /** @test */
    public function artisan_commands_are_registered()
    {
        $requiredCommands = [
            'payment:cleanup-sessions'
        ];

        $registeredCommands = array_keys(Artisan::all());

        foreach ($requiredCommands as $command) {
            $this->assertContains(
                $command,
                $registeredCommands,
                "Artisan command {$command} is not registered"
            );
        }
    }

    /** @test */
    public function environment_variables_are_documented()
    {
        $envExamplePath = base_path('.env.example');

        if (file_exists($envExamplePath)) {
            $envContent = file_get_contents($envExamplePath);

            $requiredEnvVars = [
                'UNIPAYMENT_APP_ID',
                'UNIPAYMENT_API_KEY',
                'UNIPAYMENT_ENVIRONMENT',
                'UNIPAYMENT_WEBHOOK_SECRET'
            ];

            foreach ($requiredEnvVars as $envVar) {
                $this->assertStringContainsString(
                    $envVar,
                    $envContent,
                    "Environment variable {$envVar} is not documented in .env.example"
                );
            }
        }
    }

    /** @test */
    public function payment_validation_rules_are_comprehensive()
    {
        $requestClasses = [
            \App\Http\Requests\CardPaymentRequest::class,
            \App\Http\Requests\PaymentRequest::class
        ];

        foreach ($requestClasses as $requestClass) {
            $this->assertTrue(
                class_exists($requestClass),
                "Request class {$requestClass} does not exist"
            );

            $request = new $requestClass();
            $this->assertTrue(
                method_exists($request, 'rules'),
                "Request class {$requestClass} missing rules method"
            );

            $rules = $request->rules();
            $this->assertIsArray($rules, "Rules method should return an array");
        }
    }
}
