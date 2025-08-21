<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EnhancedWebhookErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_handles_empty_payload_with_proper_status_code()
    {
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('warning')->andReturn(true);

        $response = $this->post('/payment/unipayment/webhook', [], [
            'Content-Type' => 'application/json',
            'X-UniPayment-Signature' => 'test-signature'
        ]);

        $response->assertStatus(400);
        $response->assertSee('Bad Request: Empty payload');
    }

    public function test_webhook_handles_invalid_json_with_proper_status_code()
    {
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('warning')->andReturn(true);

        $response = $this->post('/payment/unipayment/webhook', [], [
            'Content-Type' => 'application/json',
            'X-UniPayment-Signature' => 'test-signature'
        ]);

        // Send invalid JSON
        $response = $this->call('POST', '/payment/unipayment/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_UNIPAYMENT_SIGNATURE' => 'test-signature'
        ], 'invalid json content');

        $response->assertStatus(400);
        $response->assertSee('Bad Request: Invalid JSON payload');
    }

    public function test_webhook_detects_duplicates_with_comprehensive_check()
    {
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('warning')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);

        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'event_type' => 'payment_completed',
            'order_id' => 'test-order-456'
        ]);

        // First request should succeed (mocked)
        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->once()
                ->andReturn([
                    'success' => true,
                    'invoice_id' => 'test-invoice-123',
                    'status' => 'completed',
                    'verified' => true
                ]);
        });

        $response = $this->call('POST', '/payment/unipayment/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_UNIPAYMENT_SIGNATURE' => 'test-signature'
        ], $payload);

        $response->assertStatus(200);

        // Second identical request should be detected as duplicate
        $response = $this->call('POST', '/payment/unipayment/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_UNIPAYMENT_SIGNATURE' => 'test-signature'
        ], $payload);

        $response->assertStatus(200);
        $response->assertSee('OK - Duplicate processed');
    }

    public function test_webhook_includes_processing_metadata_in_response_headers()
    {
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('warning')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);

        $payload = json_encode([
            'invoice_id' => 'test-invoice-789',
            'event_type' => 'payment_completed'
        ]);

        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->once()
                ->andReturn([
                    'success' => true,
                    'invoice_id' => 'test-invoice-789',
                    'status' => 'completed',
                    'verified' => true
                ]);
        });

        $response = $this->call('POST', '/payment/unipayment/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_UNIPAYMENT_SIGNATURE' => 'test-signature'
        ], $payload);

        $response->assertStatus(200);
        $response->assertHeader('X-Webhook-ID');
        $response->assertHeader('X-Processing-Time-MS');
    }

    public function test_webhook_tracks_failure_rates()
    {
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('warning')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);

        $payload = json_encode([
            'invoice_id' => 'test-invoice-fail',
            'event_type' => 'payment_failed'
        ]);

        $this->mock(\App\Services\UniPaymentOfficialService::class, function ($mock) {
            $mock->shouldReceive('handleWebhookNotification')
                ->once()
                ->andReturn([
                    'success' => false,
                    'error' => 'Payment processing failed',
                    'error_type' => 'payment_declined',
                    'http_status' => 422
                ]);
        });

        $response = $this->call('POST', '/payment/unipayment/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_UNIPAYMENT_SIGNATURE' => 'test-signature'
        ], $payload);

        $response->assertStatus(422);
        $response->assertSee('Unprocessable Entity: Payment processing failed');
        $response->assertHeader('X-Webhook-ID');
        $response->assertHeader('X-Error-Type', 'payment_declined');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();
    }
}
