<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Event;
use App\Models\Registration;
use App\Models\PaymentTransaction;
use App\Services\UniPaymentService;
use UniPayment\SDK\BillingAPI;
use UniPayment\SDK\Configuration;
use UniPayment\SDK\Model\CreateInvoiceResponse;
use UniPayment\SDK\Model\GetInvoiceByIdResponse;
use UniPayment\SDK\Model\InvoiceData;
use UniPayment\SDK\UnipaymentSDKException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Mockery;

class PaymentErrorRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected $event;
    protected $mockBillingAPI;
    protected $mockConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test event
        $this->event = Event::create([
            'name' => 'Error Recovery Test Event',
            'description' => 'Event for testing payment error recovery',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(31),
            'location' => 'Error Recovery Test Location',
            'max_attendees' => 100,
            'is_active' => true,
            'is_default' => false
        ]);

        // Mock UniPayment dependencies
        $this->mockBillingAPI = Mockery::mock(BillingAPI::class);
        $this->mockConfiguration = Mockery::mock(Configuration::class);

        $this->app->bind(BillingAPI::class, function () {
            return $this->mockBillingAPI;
        });

        $this->app->bind(Configuration::class, function () {
            return $this->mockConfiguration;
        });

        // Set up UniPayment configuration
        Config::set('unipayment', [
            'app_id' => 'error_test_app_id',
            'client_id' => 'error_test_client_id',
            'client_secret' => 'error_test_client_secret',
            'environment' => 'sandbox',
            'webhook_secret' => 'error_test_webhook_secret',
            'supported_currencies' => ['USD'],
            'default_currency' => 'USD',
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00,
            'logging' => [
                'enabled' => true,
                'level' => 'info'
            ]
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_handles_api_connection_timeout_gracefully()
    {
        // Set up registration data
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Timeout Test User',
            'attendee_email' => 'timeout@example.com',
            'attendee_phone' => '+1 (555) 111-1111',
            'total_amount' => 99.99,
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        // Mock API timeout
        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new UnipaymentSDKException('Connection timeout'));

        Log::shouldReceive('log')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once();

        // Attempt payment processing
        $cardPaymentData = [
            'event_id' => $this->event->id,
            'total_amount' => '99.99',
            'attendee_name' => 'Timeout Test User',
            'attendee_email' => 'timeout@example.com',
            'attendee_phone' => '+1 (555) 111-1111'
        ];

        $response = $this->post(route('payment.card.process', $this->event), $cardPaymentData);

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.failed'));
        $response->assertSessionHas('error');

        // Verify registration data is preserved for retry
        $this->assertTrue(Session::has('registration_data'));
        $preservedData = Session::get('registration_data');
        $this->assertEquals('Timeout Test User', $preservedData['attendee_name']);

        // Verify error message suggests retry
        $errorMessage = Session::get('error');
        $this->assertStringContainsString('temporarily unavailable', strtolower($errorMessage));
    }

    /** @test */
    public function it_handles_invalid_api_credentials_error()
    {
        // Set up registration data
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Auth Error User',
            'attendee_email' => 'auth-error@example.com',
            'attendee_phone' => '+1 (555) 222-2222',
            'total_amount' => 149.99,
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        // Mock authentication error
        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new UnipaymentSDKException('Authentication failed: Invalid API credentials'));

        Log::shouldReceive('log')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once();

        $cardPaymentData = [
            'event_id' => $this->event->id,
            'total_amount' => '149.99',
            'attendee_name' => 'Auth Error User',
            'attendee_email' => 'auth-error@example.com',
            'attendee_phone' => '+1 (555) 222-2222'
        ];

        $response = $this->post(route('payment.card.process', $this->event), $cardPaymentData);

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.failed'));
        $response->assertSessionHas('error');

        // Verify error message suggests alternative payment method
        $errorMessage = Session::get('error');
        $this->assertStringContainsString('temporarily unavailable', strtolower($errorMessage));

        // Verify registration data is preserved
        $this->assertTrue(Session::has('registration_data'));
    }

    /** @test */
    public function it_handles_payment_amount_validation_errors()
    {
        // Set up registration data with amount below minimum
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Amount Error User',
            'attendee_email' => 'amount-error@example.com',
            'attendee_phone' => '+1 (555) 333-3333',
            'total_amount' => 0.50, // Below minimum
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        $cardPaymentData = [
            'event_id' => $this->event->id,
            'total_amount' => '0.50',
            'attendee_name' => 'Amount Error User',
            'attendee_email' => 'amount-error@example.com',
            'attendee_phone' => '+1 (555) 333-3333'
        ];

        $response = $this->post(route('payment.card.process', $this->event), $cardPaymentData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['total_amount']);

        // Test amount above maximum
        $registrationData['total_amount'] = 15000.00;
        Session::put('registration_data', $registrationData);

        $cardPaymentData['total_amount'] = '15000.00';

        $response = $this->post(route('payment.card.process', $this->event), $cardPaymentData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['total_amount']);
    }

    /** @test */
    public function it_handles_webhook_processing_failures()
    {
        // Create a registration with pending payment
        $registration = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Webhook Error User',
            'attendee_email' => 'webhook-error@example.com',
            'attendee_phone' => '+1 (555) 444-4444',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'webhook_error_invoice',
            'payment_amount' => 199.99,
            'payment_currency' => 'USD',
            'payment_status' => 'pending'
        ]);

        PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'webhook_error_invoice',
            'payment_method' => 'card',
            'amount' => 199.99,
            'currency' => 'USD',
            'status' => 'pending'
        ]);

        // Mock webhook signature verification success but API verification failure
        $webhookPayload = [
            'invoice_id' => 'webhook_error_invoice',
            'status' => 'confirmed',
            'order_id' => 'REG_' . $registration->id . '_' . time(),
            'price_amount' => 199.99,
            'price_currency' => 'USD'
        ];

        $payload = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payload, 'error_test_webhook_secret');

        $this->mockStatic(\UniPayment\SDK\Utils\WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'error_test_webhook_secret', $signature)
            ->andReturn(true);

        // Mock API verification failure
        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->with('webhook_error_invoice')
            ->andThrow(new UnipaymentSDKException('API verification failed'));

        Log::shouldReceive('log')->atLeast()->once();
        Log::shouldReceive('warning')->atLeast()->once();

        $response = $this->post(route('payment.unipayment.webhook'), [], [
            'X-UniPayment-Signature' => $signature,
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(200);
        $response->assertSee('OK');

        // Verify registration status is updated based on webhook data even if API verification fails
        $registration->refresh();
        $this->assertEquals('completed', $registration->payment_status);
    }

    /** @test */
    public function it_handles_duplicate_webhook_notifications()
    {
        // Create a completed registration
        $registration = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Duplicate Webhook User',
            'attendee_email' => 'duplicate@example.com',
            'attendee_phone' => '+1 (555) 555-5555',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'duplicate_webhook_invoice',
            'payment_amount' => 89.99,
            'payment_currency' => 'USD',
            'payment_status' => 'completed',
            'payment_completed_at' => now()->subMinutes(10)
        ]);

        PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'duplicate_webhook_invoice',
            'payment_method' => 'card',
            'amount' => 89.99,
            'currency' => 'USD',
            'status' => 'completed',
            'processed_at' => now()->subMinutes(10)
        ]);

        $webhookPayload = [
            'invoice_id' => 'duplicate_webhook_invoice',
            'status' => 'confirmed',
            'order_id' => 'REG_' . $registration->id . '_' . time(),
            'price_amount' => 89.99,
            'price_currency' => 'USD'
        ];

        $payload = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payload, 'error_test_webhook_secret');

        $this->mockStatic(\UniPayment\SDK\Utils\WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'error_test_webhook_secret', $signature)
            ->andReturn(true);

        Log::shouldReceive('log')->atLeast()->once();
        Log::shouldReceive('info')->atLeast()->once();

        // Send duplicate webhook
        $response = $this->post(route('payment.unipayment.webhook'), [], [
            'X-UniPayment-Signature' => $signature,
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(200);
        $response->assertSee('OK');

        // Verify registration status remains unchanged
        $originalCompletedAt = $registration->payment_completed_at;
        $registration->refresh();
        $this->assertEquals('completed', $registration->payment_status);
        $this->assertEquals($originalCompletedAt->timestamp, $registration->payment_completed_at->timestamp);
    }

    /** @test */
    public function it_handles_payment_session_corruption()
    {
        // Set up corrupted payment session
        Session::put('payment_session', 'corrupted_data');

        // Set up valid registration data
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Session Corruption User',
            'attendee_email' => 'corruption@example.com',
            'attendee_phone' => '+1 (555) 666-6666',
            'total_amount' => 75.00,
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        // Try to access payment processing page
        $response = $this->get(route('payments.card-processing', $this->event));

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.selection', $this->event));
        $response->assertSessionHas('error');

        // Verify corrupted session was cleared
        $this->assertFalse(Session::has('payment_session'));

        // Verify registration data is preserved
        $this->assertTrue(Session::has('registration_data'));
    }

    /** @test */
    public function it_handles_network_interruption_during_payment()
    {
        // Set up registration data
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Network Error User',
            'attendee_email' => 'network@example.com',
            'attendee_phone' => '+1 (555) 777-7777',
            'total_amount' => 125.00,
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        // Mock network error during payment creation
        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new \Exception('Network error: Connection reset by peer'));

        Log::shouldReceive('log')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once();

        $cardPaymentData = [
            'event_id' => $this->event->id,
            'total_amount' => '125.00',
            'attendee_name' => 'Network Error User',
            'attendee_email' => 'network@example.com',
            'attendee_phone' => '+1 (555) 777-7777'
        ];

        $response = $this->post(route('payment.card.process', $this->event), $cardPaymentData);

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.failed'));
        $response->assertSessionHas('error');

        // Verify helpful error message
        $errorMessage = Session::get('error');
        $this->assertStringContainsString('network', strtolower($errorMessage));

        // Verify registration data is preserved for retry
        $this->assertTrue(Session::has('registration_data'));
    }

    /** @test */
    public function it_handles_malformed_webhook_data()
    {
        $malformedPayload = '{"invoice_id":"test","status":'; // Incomplete JSON
        $signature = hash_hmac('sha256', $malformedPayload, 'error_test_webhook_secret');

        $this->mockStatic(\UniPayment\SDK\Utils\WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($malformedPayload, 'error_test_webhook_secret', $signature)
            ->andReturn(true);

        Log::shouldReceive('error')->atLeast()->once();

        $response = $this->post(route('payment.unipayment.webhook'), [], [
            'X-UniPayment-Signature' => $signature,
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(400);
        $response->assertSee('Bad Request');
    }

    /** @test */
    public function it_handles_payment_callback_with_missing_registration()
    {
        // Simulate callback for non-existent registration
        $callbackData = [
            'invoice_id' => 'non_existent_invoice',
            'status' => 'confirmed',
            'order_id' => 'REG_999999_' . time(),
            'price_amount' => 99.99,
            'price_currency' => 'USD'
        ];

        Log::shouldReceive('warning')->atLeast()->once();

        $response = $this->get(route('payment.unipayment.callback', $callbackData));

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.failed'));
        $response->assertSessionHas('error');

        $errorMessage = Session::get('error');
        $this->assertStringContainsString('registration not found', strtolower($errorMessage));
    }

    /** @test */
    public function it_handles_concurrent_payment_attempts()
    {
        // Set up registration data
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Concurrent User',
            'attendee_email' => 'concurrent@example.com',
            'attendee_phone' => '+1 (555) 888-8888',
            'total_amount' => 199.99,
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        // Mock successful first payment
        $mockInvoiceResponse = Mockery::mock(CreateInvoiceResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getInvoiceId')->andReturn('concurrent_invoice_1');
        $mockInvoiceData->shouldReceive('getInvoiceUrl')->andReturn('https://sandbox.unipayment.io/invoice/concurrent_invoice_1');
        $mockInvoiceResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andReturn($mockInvoiceResponse);

        Log::shouldReceive('log')->atLeast()->once();

        $cardPaymentData = [
            'event_id' => $this->event->id,
            'total_amount' => '199.99',
            'attendee_name' => 'Concurrent User',
            'attendee_email' => 'concurrent@example.com',
            'attendee_phone' => '+1 (555) 888-8888'
        ];

        // First payment attempt
        $response1 = $this->post(route('payment.card.process', $this->event), $cardPaymentData);
        $response1->assertStatus(302);
        $response1->assertSessionHas('payment_session');

        // Second concurrent payment attempt (should be blocked)
        $response2 = $this->post(route('payment.card.process', $this->event), $cardPaymentData);
        $response2->assertStatus(302);
        $response2->assertRedirect(route('payments.card-processing', $this->event));
        $response2->assertSessionHas('info');

        $infoMessage = Session::get('info');
        $this->assertStringContainsString('already in progress', strtolower($infoMessage));
    }

    /**
     * Helper method to mock static classes
     */
    protected function mockStatic($class)
    {
        return Mockery::mock('alias:' . $class);
    }
}
