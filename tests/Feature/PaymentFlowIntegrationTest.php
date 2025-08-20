<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Event;
use App\Models\Registration;
use App\Models\PaymentTransaction;
use App\Models\UniPaymentSetting;
use App\Services\UniPaymentService;
use UniPayment\SDK\BillingAPI;
use UniPayment\SDK\Configuration;
use UniPayment\SDK\Model\CreateInvoiceResponse;
use UniPayment\SDK\Model\GetInvoiceByIdResponse;
use UniPayment\SDK\Model\InvoiceData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Mockery;

class PaymentFlowIntegrationTest extends TestCase
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
            'name' => 'Test Conference 2025',
            'description' => 'A test conference for payment integration',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(31),
            'location' => 'Test Convention Center',
            'max_attendees' => 500,
            'is_active' => true,
            'is_default' => false
        ]);

        // Mock UniPayment dependencies
        $this->mockBillingAPI = Mockery::mock(BillingAPI::class);
        $this->mockConfiguration = Mockery::mock(Configuration::class);

        // Bind mocks to container
        $this->app->bind(BillingAPI::class, function () {
            return $this->mockBillingAPI;
        });

        $this->app->bind(Configuration::class, function () {
            return $this->mockConfiguration;
        });

        // Set up UniPayment configuration
        Config::set('unipayment', [
            'app_id' => 'test_app_id',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_webhook_secret',
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

        // Create UniPayment settings in database
        UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_webhook_secret',
            'is_enabled' => true,
            'supported_currencies' => ['USD'],
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_completes_full_card_payment_registration_flow()
    {
        // Step 1: Set up registration data in session
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567',
            'total_amount' => 99.99,
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        // Step 2: Access payment selection page
        $response = $this->get(route('payment.selection', $this->event));

        $response->assertStatus(200);
        $response->assertViewIs('payments.selection');
        $response->assertViewHas('event', $this->event);
        $response->assertViewHas('registrationData', $registrationData);
        $response->assertViewHas('paymentOptions');

        // Step 3: Mock UniPayment API for payment creation
        $mockInvoiceResponse = Mockery::mock(CreateInvoiceResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getInvoiceId')->andReturn('test_invoice_123');
        $mockInvoiceData->shouldReceive('getInvoiceUrl')->andReturn('https://sandbox.unipayment.io/invoice/test_invoice_123');
        $mockInvoiceResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andReturn($mockInvoiceResponse);

        Log::shouldReceive('log')->atLeast()->once();

        // Step 4: Process card payment
        $cardPaymentData = [
            'event_id' => $this->event->id,
            'total_amount' => '99.99',
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $response = $this->post(route('payment.card.process', $this->event), $cardPaymentData);

        $response->assertStatus(302);
        $response->assertSessionHas('payment_session');

        // Verify payment session data
        $paymentSession = Session::get('payment_session');
        $this->assertIsArray($paymentSession);
        $this->assertEquals('test_invoice_123', $paymentSession['invoice_id']);
        $this->assertEquals($this->event->id, $paymentSession['event_id']);

        // Step 5: Simulate successful payment callback
        $callbackData = [
            'invoice_id' => 'test_invoice_123',
            'status' => 'confirmed',
            'order_id' => $paymentSession['order_id'],
            'price_amount' => 99.99,
            'price_currency' => 'USD',
            'transaction_id' => 'txn_123456',
            'ext_args' => json_encode(['event_id' => $this->event->id])
        ];

        // Mock payment status verification
        $mockStatusResponse = Mockery::mock(GetInvoiceByIdResponse::class);
        $mockStatusData = Mockery::mock(InvoiceData::class);

        $mockStatusData->shouldReceive('getStatus')->andReturn('confirmed');
        $mockStatusResponse->shouldReceive('getData')->andReturn($mockStatusData);

        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->with('test_invoice_123')
            ->andReturn($mockStatusResponse);

        $response = $this->get(route('payment.unipayment.callback', $callbackData));

        $response->assertStatus(302);
        $response->assertRedirect(route('registration.success'));

        // Step 6: Verify registration was created
        $this->assertDatabaseHas('registrations', [
            'event_id' => $this->event->id,
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'test_invoice_123',
            'payment_status' => 'completed'
        ]);

        // Step 7: Verify payment transaction was recorded
        $this->assertDatabaseHas('payment_transactions', [
            'provider' => 'unipayment',
            'transaction_id' => 'test_invoice_123',
            'payment_method' => 'card',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'completed'
        ]);

        // Step 8: Verify session cleanup
        $this->assertFalse(Session::has('registration_data'));
        $this->assertFalse(Session::has('payment_session'));
    }

    /** @test */
    public function it_handles_payment_method_switching()
    {
        // Set up registration data
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Jane Smith',
            'attendee_email' => 'jane.smith@example.com',
            'attendee_phone' => '+1 (555) 987-6543',
            'total_amount' => 149.99,
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        // Access payment selection page
        $response = $this->get(route('payment.selection', $this->event));
        $response->assertStatus(200);

        // Switch to card payment method
        $response = $this->post(route('payment.switch-method', $this->event), [
            'method' => 'card'
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.selection', $this->event));
        $response->assertSessionHas('success');

        // Verify registration data was preserved with payment method preference
        $updatedRegistrationData = Session::get('registration_data');
        $this->assertEquals('card', $updatedRegistrationData['preferred_payment_method']);
        $this->assertEquals('Jane Smith', $updatedRegistrationData['attendee_name']);
        $this->assertEquals(149.99, $updatedRegistrationData['total_amount']);

        // Switch to crypto payment method
        $response = $this->post(route('payment.switch-method', $this->event), [
            'method' => 'crypto'
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.crypto', $this->event));
        $response->assertSessionHas('success');

        // Verify registration data was preserved with new payment method preference
        $updatedRegistrationData = Session::get('registration_data');
        $this->assertEquals('crypto', $updatedRegistrationData['preferred_payment_method']);
        $this->assertEquals('Jane Smith', $updatedRegistrationData['attendee_name']);
    }

    /** @test */
    public function it_handles_payment_failure_and_retry()
    {
        // Set up registration data
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Bob Wilson',
            'attendee_email' => 'bob.wilson@example.com',
            'attendee_phone' => '+1 (555) 456-7890',
            'total_amount' => 75.00,
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        // Mock failed payment creation
        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new \UniPayment\SDK\UnipaymentSDKException('Payment processing failed'));

        Log::shouldReceive('log')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once();

        // Attempt card payment
        $cardPaymentData = [
            'event_id' => $this->event->id,
            'total_amount' => '75.00',
            'attendee_name' => 'Bob Wilson',
            'attendee_email' => 'bob.wilson@example.com',
            'attendee_phone' => '+1 (555) 456-7890'
        ];

        $response = $this->post(route('payment.card.process', $this->event), $cardPaymentData);

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.failed'));
        $response->assertSessionHas('error');

        // Verify registration data is still preserved for retry
        $this->assertTrue(Session::has('registration_data'));
        $preservedData = Session::get('registration_data');
        $this->assertEquals('Bob Wilson', $preservedData['attendee_name']);

        // Verify no registration was created
        $this->assertDatabaseMissing('registrations', [
            'event_id' => $this->event->id,
            'attendee_email' => 'bob.wilson@example.com'
        ]);

        // Test retry with successful payment
        $mockInvoiceResponse = Mockery::mock(CreateInvoiceResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getInvoiceId')->andReturn('retry_invoice_456');
        $mockInvoiceData->shouldReceive('getInvoiceUrl')->andReturn('https://sandbox.unipayment.io/invoice/retry_invoice_456');
        $mockInvoiceResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andReturn($mockInvoiceResponse);

        $response = $this->post(route('payment.card.process', $this->event), $cardPaymentData);

        $response->assertStatus(302);
        $response->assertSessionHas('payment_session');

        $paymentSession = Session::get('payment_session');
        $this->assertEquals('retry_invoice_456', $paymentSession['invoice_id']);
    }

    /** @test */
    public function it_validates_payment_amount_limits_for_card_payments()
    {
        // Test amount below minimum
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Test User',
            'attendee_email' => 'test@example.com',
            'attendee_phone' => '+1 (555) 123-4567',
            'total_amount' => 0.50, // Below minimum
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        $response = $this->post(route('payment.switch-method', $this->event), [
            'method' => 'card'
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.selection', $this->event));
        $response->assertSessionHas('error');
        $response->assertSessionHasErrors();

        // Test amount above maximum
        $registrationData['total_amount'] = 15000.00; // Above maximum
        Session::put('registration_data', $registrationData);

        $response = $this->post(route('payment.switch-method', $this->event), [
            'method' => 'card'
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('payment.selection', $this->event));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function it_handles_expired_registration_data()
    {
        // Set up expired registration data
        $expiredRegistrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Expired User',
            'attendee_email' => 'expired@example.com',
            'attendee_phone' => '+1 (555) 999-0000',
            'total_amount' => 99.99,
            'expires_at' => now()->subMinutes(5) // Expired 5 minutes ago
        ];

        Session::put('registration_data', $expiredRegistrationData);

        // Try to access payment selection page
        $response = $this->get(route('payment.selection', $this->event));

        $response->assertStatus(302);
        $response->assertRedirect(route('events.show', $this->event));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('registration_data');

        // Try to process card payment with expired data
        $cardPaymentData = [
            'event_id' => $this->event->id,
            'total_amount' => '99.99',
            'attendee_name' => 'Expired User',
            'attendee_email' => 'expired@example.com',
            'attendee_phone' => '+1 (555) 999-0000'
        ];

        $response = $this->post(route('payment.card.process', $this->event), $cardPaymentData);

        $response->assertStatus(302);
        $response->assertRedirect(route('events.show', $this->event));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function it_handles_webhook_notifications_correctly()
    {
        // Create a registration and payment transaction
        $registration = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Webhook Test User',
            'attendee_email' => 'webhook@example.com',
            'attendee_phone' => '+1 (555) 111-2222',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'webhook_invoice_789',
            'payment_amount' => 199.99,
            'payment_currency' => 'USD',
            'payment_status' => 'pending'
        ]);

        PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'webhook_invoice_789',
            'payment_method' => 'card',
            'amount' => 199.99,
            'currency' => 'USD',
            'status' => 'pending'
        ]);

        // Prepare webhook payload
        $webhookPayload = [
            'invoice_id' => 'webhook_invoice_789',
            'status' => 'confirmed',
            'order_id' => 'REG_' . $registration->id . '_' . time(),
            'price_amount' => 199.99,
            'price_currency' => 'USD',
            'transaction_id' => 'txn_webhook_789'
        ];

        $payload = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payload, 'test_webhook_secret');

        // Mock webhook signature verification
        $this->mockStatic(\UniPayment\SDK\Utils\WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret', $signature)
            ->andReturn(true);

        // Mock payment status verification
        $mockStatusResponse = Mockery::mock(GetInvoiceByIdResponse::class);
        $mockStatusData = Mockery::mock(InvoiceData::class);

        $mockStatusData->shouldReceive('getStatus')->andReturn('confirmed');
        $mockStatusResponse->shouldReceive('getData')->andReturn($mockStatusData);

        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->with('webhook_invoice_789')
            ->andReturn($mockStatusResponse);

        Log::shouldReceive('log')->atLeast()->once();

        // Send webhook notification
        $response = $this->post(route('payment.unipayment.webhook'), [], [
            'X-UniPayment-Signature' => $signature,
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(200);
        $response->assertSee('OK');

        // Verify registration was updated
        $registration->refresh();
        $this->assertEquals('completed', $registration->payment_status);
        $this->assertNotNull($registration->payment_completed_at);

        // Verify payment transaction was updated
        $transaction = PaymentTransaction::where('transaction_id', 'webhook_invoice_789')->first();
        $this->assertEquals('completed', $transaction->status);
        $this->assertNotNull($transaction->processed_at);
    }

    /** @test */
    public function it_rejects_invalid_webhook_signatures()
    {
        $webhookPayload = [
            'invoice_id' => 'invalid_webhook_123',
            'status' => 'confirmed'
        ];

        $payload = json_encode($webhookPayload);
        $invalidSignature = 'invalid_signature_hash';

        // Mock webhook signature verification failure
        $this->mockStatic(\UniPayment\SDK\Utils\WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret', $invalidSignature)
            ->andReturn(false);

        Log::shouldReceive('warning')->once();

        // Send webhook with invalid signature
        $response = $this->post(route('payment.unipayment.webhook'), [], [
            'X-UniPayment-Signature' => $invalidSignature,
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(401);
        $response->assertSee('Unauthorized');
    }

    /** @test */
    public function it_checks_payment_availability_correctly()
    {
        $response = $this->get(route('payment.availability', $this->event));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'available_methods' => ['crypto', 'card'],
            'has_valid_registration' => false
        ]);

        // Test with valid registration data
        $registrationData = [
            'event_id' => $this->event->id,
            'attendee_name' => 'Availability Test',
            'attendee_email' => 'availability@example.com',
            'attendee_phone' => '+1 (555) 000-1111',
            'total_amount' => 99.99,
            'expires_at' => now()->addMinutes(30)
        ];

        Session::put('registration_data', $registrationData);

        $response = $this->get(route('payment.availability', $this->event));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'available_methods' => ['crypto', 'card'],
            'has_valid_registration' => true
        ]);
    }

    /**
     * Helper method to mock static classes
     */
    protected function mockStatic($class)
    {
        return Mockery::mock('alias:' . $class);
    }
}
