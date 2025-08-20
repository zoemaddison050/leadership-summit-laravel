<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UniPaymentOfficialService;
use App\Models\UniPaymentSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class UniPaymentWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected $uniPaymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uniPaymentService = new UniPaymentOfficialService();
    }

    /** @test */
    public function it_validates_webhook_signature_successfully()
    {
        // Create UniPayment settings with webhook secret
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

        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
        ]);

        // Calculate correct signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Mock the getPaymentStatus method to avoid API calls
        $mockService = $this->getMockBuilder(UniPaymentOfficialService::class)
            ->onlyMethods(['getPaymentStatus'])
            ->getMock();

        $mockService->method('getPaymentStatus')
            ->willReturn([
                'status' => 'confirmed',
                'order_id' => 'test-order-123',
                'price_amount' => 100.00,
                'price_currency' => 'USD'
            ]);

        $result = $mockService->handleWebhookNotification($payload, $expectedSignature);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['verified']);
        $this->assertEquals('test-invoice-123', $result['invoice_id']);
        $this->assertEquals(200, $result['http_status']);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature()
    {
        // Create UniPayment settings with webhook secret
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

        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
        ]);

        // Create a properly formatted but incorrect signature
        $invalidSignature = 'sha256=' . hash_hmac('sha256', $payload, 'wrong-secret');

        $result = $this->uniPaymentService->handleWebhookNotification($payload, $invalidSignature);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertEquals(401, $result['http_status']);
        $this->assertEquals('Signature verification failed', $result['error']);
    }

    /** @test */
    public function it_rejects_webhook_with_missing_signature_when_secret_configured()
    {
        // Create UniPayment settings with webhook secret
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

        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
        ]);

        $result = $this->uniPaymentService->handleWebhookNotification($payload, '');

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertEquals(401, $result['http_status']);
        $this->assertEquals('Webhook signature required but not provided', $result['error']);
    }

    /** @test */
    public function it_accepts_webhook_without_signature_when_no_secret_configured()
    {
        // Create UniPayment settings without webhook secret
        UniPaymentSetting::create([
            'app_id' => 'test-app-id',
            'api_key' => 'test-api-key',
            'environment' => 'sandbox',
            'webhook_secret' => null,
            'is_enabled' => true,
            'supported_currencies' => ['USD'],
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00
        ]);

        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
        ]);

        // Mock the getPaymentStatus method to avoid API calls
        $mockService = $this->getMockBuilder(UniPaymentOfficialService::class)
            ->onlyMethods(['getPaymentStatus'])
            ->getMock();

        $mockService->method('getPaymentStatus')
            ->willReturn([
                'status' => 'confirmed',
                'order_id' => 'test-order-123',
                'price_amount' => 100.00,
                'price_currency' => 'USD'
            ]);

        $result = $mockService->handleWebhookNotification($payload, '');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['verified']); // Not verified because no signature validation
        $this->assertEquals('test-invoice-123', $result['invoice_id']);
        $this->assertEquals(200, $result['http_status']);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_json_payload()
    {
        $invalidPayload = 'invalid-json-payload';
        $signature = 'sha256=some-signature';

        $result = $this->uniPaymentService->handleWebhookNotification($invalidPayload, $signature);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertEquals(400, $result['http_status']);
        $this->assertStringStartsWith('Invalid JSON payload', $result['error']);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature_format()
    {
        // Create UniPayment settings with webhook secret
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

        $payload = json_encode([
            'invoice_id' => 'test-invoice-123',
            'status' => 'confirmed',
            'order_id' => 'test-order-123'
        ]);

        $invalidFormatSignature = 'invalid-format-with-special-chars!@#';

        $result = $this->uniPaymentService->handleWebhookNotification($payload, $invalidFormatSignature);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertEquals(401, $result['http_status']);
        $this->assertEquals('Invalid signature format', $result['error']);
    }
}
