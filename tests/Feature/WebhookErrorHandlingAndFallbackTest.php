<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UniPaymentSetting;
use App\Models\Registration;
use App\Models\PaymentTransaction;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\WebhookUrlGenerator;
use App\Services\WebhookMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class WebhookErrorHandlingAndFallbackTest extends TestCase
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

        Cache::flush();
        $this->setupTestData();
    }

    protected function setupTestData()
    {
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
    public function handles_database_connection_failure_during_webhook_processing()
    {
        // Mock database failure
        DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database connection failed'));

        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        Log::shouldReceive('error')
            ->with('UniPayment webhook processing failed', \Mockery::type('array'))
            ->once();

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(500);
    }

    /** @test */
    public function handles_webhook_url_generation_failure_in_payment_flow()
    {
        $this->mock(WebhookUrlGenerator::class, function ($mock) {
            $mock->shouldReceive('getValidatedWebhookUrl')
                ->andReturn([
                    'url' => null,
                    'environment' => 'testing',
                    'validation' => [
                        'valid' => false,
                        'accessible' => false,
                        'errors' => ['Failed to generate webhook URL'],
                        'warnings' => []
                    ]
                ]);
        });

        $response = $this->post(route('payment.process'), [
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john@example.com',
            'attendee_phone' => '+1234567890',
            'payment_method' => 'crypto'
        ]);

        // Should handle gracefully and show error
        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function handles_webhook_url_accessibility_warnings()
    {
        $this->mock(WebhookUrlGenerator::class, function ($mock) {
            $mock->shouldReceive('getValidatedWebhookUrl')
                ->andReturn([
                    'url' => 'http://localhost:8000/payment/unipayment/webhook',
                    'environment' => 'development',
                    'validation' => [
                        'valid' => true,
                        'accessible' => false,
                        'errors' => [],
                        'warnings' => ['Webhook URL may not be accessible from external services']
                    ]
                ]);

            $mock->shouldReceive('getWebhookRecommendations')
                ->andReturn([
                    [
                        'type' => 'setup',
                        'message' => 'Install and run ngrok for webhook testing'
                    ]
                ]);
        });

        Log::shouldReceive('warning')
            ->with('Webhook URL may not be accessible', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('Webhook setup recommendations', \Mockery::type('array'))
            ->once();

        $response = $this->post(route('payment.process'), [
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john@example.com',
            'attendee_phone' => '+1234567890',
            'payment_method' => 'crypto'
        ]);

        // Should proceed with fallback handling
        $response->assertRedirect();
    }

    /** @test */
    public function handles_unipayment_service_unavailable_error()
    {
        // Mock UniPayment service failure
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andReturn([
                    'success' => false,
                    'error' => 'Service unavailable',
                    'error_type' => 'service_unavailable',
                    'verified' => false
                ]);
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

        $response->assertStatus(503); // Service Unavailable
        $response->assertSeeText('Service unavailable');
    }

    /** @test */
    public function handles_webhook_signature_verification_failure()
    {
        // Mock service returning signature verification failure
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andReturn([
                    'success' => false,
                    'error' => 'Invalid signature',
                    'error_type' => 'signature_invalid',
                    'verified' => false
                ]);
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

        $response->assertStatus(401); // Unauthorized
        $response->assertSeeText('Invalid signature');
    }

    /** @test */
    public function handles_malformed_webhook_payload_gracefully()
    {
        // Mock service returning payload parsing error
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andReturn([
                    'success' => false,
                    'error' => 'Malformed payload',
                    'error_type' => 'payload_invalid',
                    'verified' => true
                ]);
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

        $response->assertStatus(400); // Bad Request
        $response->assertSeeText('Malformed payload');
    }

    /** @test */
    public function implements_webhook_retry_mechanism_for_transient_failures()
    {
        // Mock transient failure
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andReturn([
                    'success' => false,
                    'error' => 'Temporary database error',
                    'error_type' => 'database_error',
                    'verified' => true,
                    'retryable' => true
                ]);
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

        $response->assertStatus(500); // Internal Server Error (retryable)
    }

    /** @test */
    public function handles_webhook_processing_timeout_gracefully()
    {
        // Mock timeout scenario
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andThrow(new \Exception('Processing timeout after 30 seconds'));
        });

        Log::shouldReceive('error')
            ->with('UniPayment webhook processing failed', \Mockery::type('array'))
            ->once();

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
    public function logs_comprehensive_error_information()
    {
        // Mock service failure with detailed error info
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andReturn([
                    'success' => false,
                    'error' => 'Payment validation failed',
                    'error_type' => 'validation_error',
                    'verified' => true,
                    'details' => [
                        'field' => 'amount',
                        'expected' => 100.00,
                        'received' => 99.99
                    ]
                ]);
        });

        Log::shouldReceive('warning')
            ->with('UniPayment webhook processing failed', \Mockery::on(function ($logData) {
                return isset($logData['webhook_id']) &&
                    isset($logData['error']) &&
                    isset($logData['error_type']) &&
                    isset($logData['processing_time_ms']) &&
                    isset($logData['service_response_keys']);
            }))
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
    public function handles_webhook_rate_limiting()
    {
        // Simulate rate limiting by sending multiple requests quickly
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Send multiple requests in quick succession
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
                'X-UniPayment-Signature' => $signature
            ]);

            // First request should succeed, subsequent ones might be rate limited
            if ($i === 0) {
                $this->assertIn($response->getStatusCode(), [200, 429]);
            }
        }
    }

    /** @test */
    public function handles_webhook_with_corrupted_data()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed',
            'corrupted_field' => str_repeat('x', 10000) // Very large field
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        // Should handle gracefully
        $this->assertIn($response->getStatusCode(), [200, 400, 413]); // OK, Bad Request, or Payload Too Large
    }

    /** @test */
    public function implements_webhook_fallback_to_callback_verification()
    {
        // Mock webhook failure but successful callback verification
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andReturn([
                    'success' => false,
                    'error' => 'Webhook processing failed',
                    'error_type' => 'processing_error',
                    'verified' => true,
                    'fallback_to_callback' => true
                ]);
        });

        Log::shouldReceive('info')
            ->with('Webhook processing failed, will rely on callback verification', \Mockery::type('array'))
            ->once();

        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(200); // Accept webhook but rely on callback
    }

    /** @test */
    public function handles_webhook_monitoring_service_failure()
    {
        // Mock monitoring service failure
        $this->mock(WebhookMonitoringService::class, function ($mock) {
            $mock->shouldReceive('logWebhookEvent')
                ->andThrow(new \Exception('Monitoring service unavailable'));
        });

        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Webhook processing should continue even if monitoring fails
        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function handles_memory_exhaustion_during_webhook_processing()
    {
        // Mock memory exhaustion
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andThrow(new \Exception('Allowed memory size exhausted'));
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
    public function handles_concurrent_webhook_processing()
    {
        // Simulate concurrent processing of the same webhook
        $payload = json_encode([
            'event_type' => 'payment.completed',
            'invoice_id' => 'test-invoice-123',
            'status' => 'completed'
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Process webhook multiple times concurrently (simulated)
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
                'X-UniPayment-Signature' => $signature
            ]);
        }

        // First should succeed, others should be handled as duplicates
        $this->assertEquals(200, $responses[0]->getStatusCode());

        foreach (array_slice($responses, 1) as $response) {
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    /** @test */
    public function provides_detailed_error_responses_for_debugging()
    {
        config(['app.debug' => true]);

        // Mock service failure
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->andReturn([
                    'success' => false,
                    'error' => 'Detailed error for debugging',
                    'error_type' => 'debug_error',
                    'verified' => true,
                    'debug_info' => [
                        'step' => 'payment_validation',
                        'expected_amount' => 100.00,
                        'received_amount' => 99.99
                    ]
                ]);
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

        $response->assertStatus(400);
        $response->assertSeeText('Detailed error for debugging');
    }

    /** @test */
    public function handles_webhook_with_missing_required_fields()
    {
        $payload = json_encode([
            'event_type' => 'payment.completed',
            // Missing invoice_id and status
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $signature
        ]);

        $response->assertStatus(400);
        $response->assertSeeText('Missing invoice_id in webhook data');
    }
}
