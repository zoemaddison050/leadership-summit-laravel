<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UniPaymentSetting;
use App\Models\Registration;
use App\Models\PaymentTransaction;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Role;
use App\Services\WebhookMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WebhookProcessingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $event;
    protected $ticket;
    protected $registration;
    protected $paymentTransaction;
    protected $uniPaymentSettings;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache
        Cache::flush();

        // Create test data
        $this->setupTestData();
    }

    protected function setupTestData()
    {
        // Create UniPayment settings
        $this->uniPaymentSettings = UniPaymentSetting::create([
            'app_id' => 'test-app-id',
            'api_key' => 'test-api-key',
            'environment' => 'sandbox',
            'webhook_secret' => 'test-webhook-secret',
            'webhook_enabled' => true,
            'is_enabled' => true,
            'supported_currencies' => ['USD'],
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00
        ]);

        // Create event and ticket
        $this->event = Event::create([
            'title' => 'Test Event',
            'description' => 'Test Description',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(31),
            'location' => 'Test Location',
            'status' => 'active'
        ]);

        $this->ticket = Ticket::create([
            'event_id' => $this->event->id,
            'name' => 'General Admission',
            'description' => 'General admission ticket',
            'price' => 100.00,
            'quantity_available' => 100,
            'is_active' => true
        ]);

        // Create registration
        $this->registration = Registration::create([
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john@example.com',
            'attendee_phone' => '+1234567890',
            'payment_status' => 'pending',
            'registration_status' => 'pending',
            'total_amount' => 100.00
        ]);

        // Create payment transaction
        $this->paymentTransaction = PaymentTransaction::create([
            'registration_id' => $this->registration->id,
            'invoice_id' => 'test-invoice-123',
            'order_id' => 'test-order-123',
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_method' => 'crypto',
            'provider' => 'unipayment'
        ]);
    }

    /** @test */
    public function processes_successful_payment_webhook_correctly()
    {
        Mail::fake();

        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'order_id' => 'test-order-123',
            'status' => 'completed',
            'amount' => 100.00,
            'currency' => 'USD',
            'timestamp' => now()->toISOString()
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature,
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(200);

        // Verify payment transaction is updated
        $this->paymentTransaction->refresh();
        $this->assertEquals('completed', $this->paymentTransaction->status);

        // Verify registration is updated
        $this->registration->refresh();
        $this->assertEquals('confirmed', $this->registration->payment_status);
        $this->assertEquals('confirmed', $this->registration->registration_status);

        // Verify confirmation email is sent
        Mail::assertQueued(\App\Mail\RegistrationSuccessMail::class);
    }

    /** @test */
    public function handles_webhook_signature_validation()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $invalidSignature = 'sha256=' . hash_hmac('sha256', $payload, 'wrong-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $invalidSignature
        ]);

        $response->assertStatus(401);
        $response->assertSeeText('Invalid signature');

        // Verify payment transaction is not updated
        $this->paymentTransaction->refresh();
        $this->assertEquals('pending', $this->paymentTransaction->status);
    }

    /** @test */
    public function handles_missing_webhook_signature()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true));

        $response->assertStatus(401);
        $response->assertSeeText('Missing signature');
    }

    /** @test */
    public function handles_invalid_json_payload()
    {
        $invalidPayload = 'invalid-json-data';
        $signature = 'sha256=' . hash_hmac('sha256', $invalidPayload, 'test-webhook-secret');

        $response = $this->post('/payment/unipayment/webhook', [], [
            'X-UniPayment-Signature' => $signature,
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(400);
        $response->assertSeeText('Bad Request: Empty payload');
    }

    /** @test */
    public function handles_missing_invoice_id_in_payload()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'status' => 'completed'
            // Missing invoice_id
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(400);
        $response->assertSeeText('Missing invoice_id in webhook data');
    }

    /** @test */
    public function handles_webhook_for_non_existent_payment()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'non-existent-invoice',
            'order_id' => 'non-existent-order',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        // Should still return 200 to prevent retries
        $response->assertStatus(200);
    }

    /** @test */
    public function prevents_duplicate_webhook_processing()
    {
        Mail::fake();

        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'order_id' => 'test-order-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        // First webhook
        $response1 = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response1->assertStatus(200);

        // Second identical webhook (duplicate)
        $response2 = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response2->assertStatus(200);
        $response2->assertSeeText('Duplicate processed');

        // Verify email is only sent once
        Mail::assertQueued(\App\Mail\RegistrationSuccessMail::class, 1);
    }

    /** @test */
    public function handles_failed_payment_webhook()
    {
        Mail::fake();

        $payload = json_encode([
            'event_type' => 'payment.failed',
            'invoice_id' => 'test-invoice-123',
            'order_id' => 'test-order-123',
            'status' => 'failed',
            'error_message' => 'Insufficient funds'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(200);

        // Verify payment transaction is updated
        $this->paymentTransaction->refresh();
        $this->assertEquals('failed', $this->paymentTransaction->status);

        // Verify registration status
        $this->registration->refresh();
        $this->assertEquals('failed', $this->registration->payment_status);

        // Verify failure email is sent
        Mail::assertQueued(\App\Mail\PaymentDeclinedMail::class);
    }

    /** @test */
    public function logs_webhook_processing_events()
    {
        Log::shouldReceive('info')
            ->with('UniPayment webhook processing started', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('UniPayment webhook processed successfully', \Mockery::type('array'))
            ->once();

        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);
    }

    /** @test */
    public function updates_webhook_monitoring_metrics()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        // Verify monitoring counters are updated
        $this->assertEquals(1, Cache::get('webhook_counter_total'));
        $this->assertNotNull(Cache::get('webhook_last_event_at'));

        $eventsByType = Cache::get('webhook_events_by_type', []);
        $this->assertEquals(1, $eventsByType['payment.completed']);
    }

    /** @test */
    public function handles_webhook_processing_timeout()
    {
        // Mock a service that throws a timeout exception
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andThrow(new \Exception('Processing timeout'));
        });

        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(500);
    }

    /** @test */
    public function handles_webhook_without_signature_when_no_secret_configured()
    {
        // Update settings to remove webhook secret
        $this->uniPaymentSettings->update(['webhook_secret' => null]);

        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true));

        // Should not return 401 since no signature validation is required
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function handles_partial_payment_webhook()
    {
        $payload = json_encode([
            'event_type' => 'payment.partial',
            'invoice_id' => 'test-invoice-123',
            'order_id' => 'test-order-123',
            'status' => 'partial',
            'amount_paid' => 50.00,
            'amount_remaining' => 50.00
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(200);

        // Verify payment transaction is updated
        $this->paymentTransaction->refresh();
        $this->assertEquals('partial', $this->paymentTransaction->status);

        // Verify registration remains pending for partial payments
        $this->registration->refresh();
        $this->assertEquals('pending', $this->registration->payment_status);
    }

    /** @test */
    public function handles_expired_payment_webhook()
    {
        $payload = json_encode([
            'event_type' => 'payment.expired',
            'invoice_id' => 'test-invoice-123',
            'order_id' => 'test-order-123',
            'status' => 'expired'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(200);

        // Verify payment transaction is updated
        $this->paymentTransaction->refresh();
        $this->assertEquals('expired', $this->paymentTransaction->status);

        // Verify registration is marked as expired
        $this->registration->refresh();
        $this->assertEquals('expired', $this->registration->payment_status);
    }

    /** @test */
    public function measures_webhook_processing_time()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $startTime = microtime(true);

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Verify processing time is recorded
        $processingTimes = Cache::get('webhook_processing_times', []);
        $this->assertNotEmpty($processingTimes);
        $this->assertLessThan(5000, $processingTimes[0]); // Should be less than 5 seconds
    }

    /** @test */
    public function handles_webhook_with_invalid_signature_format()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => 'invalid-signature-format'
        ]);

        $response->assertStatus(401);
        $response->assertSeeText('Invalid signature');
    }

    /** @test */
    public function handles_webhook_with_different_http_methods()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Test GET request (should fail)
        $response = $this->getJson('/payment/unipayment/webhook', [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(405); // Method Not Allowed

        // Test PUT request (should fail)
        $response = $this->putJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(405); // Method Not Allowed
    }
}
