<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UniPaymentSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class WebhookSignatureValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create UniPayment settings for testing
        UniPaymentSetting::create([
            'app_id' => 'test-app-id',
            'api_key' => 'test-api-key',
            'environment' => 'sandbox',
            'webhook_secret' => 'test-webhook-secret',
            'is_enabled' => true,
            'supported_currencies' => ['USD'],
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00
        ]);
    }

    /** @test */
    public function webhook_endpoint_returns_401_for_invalid_signature()
    {
        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
        ]);

        $invalidSignature = 'sha256=' . hash_hmac('sha256', $payload, 'wrong-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $invalidSignature
        ]);

        $response->assertStatus(401);
        $response->assertSeeText('Invalid signature');
    }

    /** @test */
    public function webhook_endpoint_returns_401_for_missing_signature()
    {
        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
        ]);

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true));

        $response->assertStatus(401);
        $response->assertSeeText('Missing signature');
    }

    /** @test */
    public function webhook_endpoint_returns_401_for_invalid_signature_format()
    {
        // Create a request with invalid signature format
        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
        ]);

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => 'invalid-format-signature!@#'
        ]);

        // The middleware should reject invalid signature formats
        $response->assertStatus(401);
        $response->assertSeeText('Invalid signature');
    }

    /** @test */
    public function webhook_endpoint_accepts_request_without_signature_when_no_secret_configured()
    {
        // Update settings to remove webhook secret
        UniPaymentSetting::first()->update(['webhook_secret' => null]);

        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
        ]);

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true));

        // Should not return 401 since no signature validation is required
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function webhook_endpoint_returns_400_for_missing_invoice_id()
    {
        $payload = json_encode([
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
            // Missing invoice_id
        ]);

        $validSignature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $response = $this->postJson('/payment/unipayment/webhook', json_decode($payload, true), [
            'X-UniPayment-Signature' => $validSignature
        ]);

        $response->assertStatus(400);
        $response->assertSeeText('Missing invoice_id in webhook data');
    }
}
