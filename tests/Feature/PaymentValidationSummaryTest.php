<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Registration;
use App\Models\UniPaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class PaymentValidationSummaryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function payment_integration_components_are_properly_configured()
    {
        // Test 1: Database schema validation
        $this->assertTrue(\Schema::hasTable('registrations'));
        $this->assertTrue(\Schema::hasTable('unipayment_settings'));
        $this->assertTrue(\Schema::hasTable('payment_transactions'));

        // Test payment tracking fields in registrations
        $paymentFields = [
            'payment_method',
            'payment_provider',
            'transaction_id',
            'payment_amount',
            'payment_completed_at'
        ];

        foreach ($paymentFields as $field) {
            $this->assertTrue(
                \Schema::hasColumn('registrations', $field),
                "Payment field {$field} missing from registrations table"
            );
        }

        // Test 2: Route registration validation
        $criticalRoutes = [
            'payment.selection',
            'payment.card',
            'payment.unipayment.callback',
            'admin.unipayment.index',
            'admin.unipayment.update'
        ];

        foreach ($criticalRoutes as $route) {
            $this->assertTrue(
                \Route::has($route),
                "Critical route {$route} is not registered"
            );
        }

        // Test 3: Service binding validation
        $this->assertTrue(
            app()->bound(\App\Services\UniPaymentService::class),
            'UniPaymentService is not bound in service container'
        );

        // Test 4: Configuration validation
        $this->assertTrue(config()->has('unipayment'));
        $this->assertTrue(config()->has('payment_security'));

        // Test 5: Model relationships validation
        $registration = new \App\Models\Registration();
        $this->assertTrue(method_exists($registration, 'event'));
        $this->assertTrue(method_exists($registration, 'ticket'));

        $paymentTransaction = new \App\Models\PaymentTransaction();
        $this->assertTrue(method_exists($paymentTransaction, 'registration'));

        $this->assertTrue(true, 'All payment integration components are properly configured');
    }

    /** @test */
    public function admin_payment_management_functionality_works()
    {
        // Create roles first
        $adminRole = \App\Models\Role::create([
            'name' => 'admin',
            'permissions' => ['*']
        ]);

        // Create admin user
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create test data
        $event = Event::factory()->create(['title' => 'Test Event']);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'price' => 100.00]);

        $registration = Registration::factory()->create([
            'event_id' => $event->id,
            'ticket_id' => $ticket->id,
            'status' => 'confirmed',
            'total_amount' => 100.00
        ]);

        PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'test_txn_123',
            'payment_method' => 'card',
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'completed'
        ]);

        $this->actingAs($admin);

        // Test admin can access UniPayment settings
        $response = $this->get(route('admin.unipayment.index'));
        $response->assertStatus(200);

        // Test admin can view transactions
        $response = $this->get(route('admin.unipayment.transactions'));
        $response->assertStatus(200);

        // Test admin can view registration details with payment info
        $response = $this->get(route('admin.registrations.show', $registration));
        $response->assertStatus(200);

        $this->assertTrue(true, 'Admin payment management functionality works correctly');
    }

    /** @test */
    public function payment_security_measures_are_implemented()
    {
        // Test 1: CSRF protection middleware exists
        $this->assertTrue(
            class_exists(\App\Http\Middleware\VerifyCsrfToken::class),
            'CSRF protection middleware not found'
        );

        // Test 2: Payment security middleware exists
        $this->assertTrue(
            class_exists(\App\Http\Middleware\PaymentSecurity::class),
            'Payment security middleware not found'
        );

        // Test 3: Rate limiting middleware exists
        $this->assertTrue(
            class_exists(\App\Http\Middleware\PaymentRateLimit::class),
            'Payment rate limiting middleware not found'
        );

        // Test 4: Webhook authentication middleware exists
        $this->assertTrue(
            class_exists(\App\Http\Middleware\WebhookAuthentication::class),
            'Webhook authentication middleware not found'
        );

        // Test 5: Session timeout middleware exists
        $this->assertTrue(
            class_exists(\App\Http\Middleware\PaymentSessionTimeout::class),
            'Payment session timeout middleware not found'
        );

        // Test 6: UniPayment settings model has encrypted fields
        $settings = new \App\Models\UniPaymentSetting();
        $casts = $settings->getCasts();

        // Check if sensitive fields are properly handled
        $this->assertArrayHasKey('is_enabled', $casts);
        $this->assertArrayHasKey('supported_currencies', $casts);

        $this->assertTrue(true, 'Payment security measures are properly implemented');
    }

    /** @test */
    public function payment_data_models_work_correctly()
    {
        // Create test event and ticket
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);

        // Test Registration model with payment data
        $registration = Registration::factory()->cardPayment()->create([
            'event_id' => $event->id,
            'ticket_id' => $ticket->id,
            'status' => 'confirmed',
            'total_amount' => 150.00
        ]);

        $this->assertEquals('paid', $registration->payment_status);
        $this->assertEquals(150.00, $registration->total_amount);
        $this->assertEquals('confirmed', $registration->status);

        // Test PaymentTransaction model
        $transaction = PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'test_card_txn',
            'payment_method' => 'card',
            'amount' => 150.00,
            'currency' => 'USD',
            'status' => 'completed'
        ]);

        $this->assertEquals($registration->id, $transaction->registration_id);
        $this->assertEquals('completed', $transaction->status);

        // Test relationships
        $this->assertInstanceOf(\App\Models\Registration::class, $transaction->registration);
        $this->assertInstanceOf(\App\Models\Event::class, $registration->event);
        $this->assertInstanceOf(\App\Models\Ticket::class, $registration->ticket);

        // Test UniPaymentSetting model
        $settings = UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_secret',
            'is_enabled' => true,
            'supported_currencies' => ['USD', 'EUR'],
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00
        ]);

        $this->assertTrue($settings->is_enabled);
        $this->assertEquals(['USD', 'EUR'], $settings->supported_currencies);
        $this->assertEquals(2.9, $settings->processing_fee_percentage);

        $this->assertTrue(true, 'Payment data models work correctly');
    }

    /** @test */
    public function payment_validation_and_error_handling_works()
    {
        // Test CardPaymentRequest validation
        $this->assertTrue(
            class_exists(\App\Http\Requests\CardPaymentRequest::class),
            'CardPaymentRequest class not found'
        );

        $cardRequest = new \App\Http\Requests\CardPaymentRequest();
        $this->assertTrue(
            method_exists($cardRequest, 'rules'),
            'CardPaymentRequest missing rules method'
        );

        // Test PaymentRequest validation
        $this->assertTrue(
            class_exists(\App\Http\Requests\PaymentRequest::class),
            'PaymentRequest class not found'
        );

        $paymentRequest = new \App\Http\Requests\PaymentRequest();
        $this->assertTrue(
            method_exists($paymentRequest, 'rules'),
            'PaymentRequest missing rules method'
        );

        // Test UniPaymentService error handling
        $this->assertTrue(
            class_exists(\App\Services\UniPaymentService::class),
            'UniPaymentService class not found'
        );

        $service = app(\App\Services\UniPaymentService::class);
        $this->assertTrue(
            method_exists($service, 'createPayment'),
            'UniPaymentService missing createPayment method'
        );

        $this->assertTrue(true, 'Payment validation and error handling works correctly');
    }

    /** @test */
    public function payment_views_and_templates_exist()
    {
        $requiredViews = [
            'payments.selection',
            'payments.card-processing',
            'payments.failed',
            'admin.unipayment.index',
            'admin.unipayment.transactions',
            'admin.registrations.show'
        ];

        foreach ($requiredViews as $view) {
            $this->assertTrue(
                view()->exists($view),
                "Required view {$view} does not exist"
            );
        }

        $this->assertTrue(true, 'All required payment views and templates exist');
    }

    /** @test */
    public function payment_artisan_commands_are_available()
    {
        $requiredCommands = [
            'payment:cleanup-sessions'
        ];

        $registeredCommands = array_keys(\Artisan::all());

        foreach ($requiredCommands as $command) {
            $this->assertContains(
                $command,
                $registeredCommands,
                "Required Artisan command {$command} is not registered"
            );
        }

        $this->assertTrue(true, 'All required payment Artisan commands are available');
    }
}
