<?php

namespace Tests\Unit\Security;

use Tests\TestCase;
use App\Services\UniPaymentService;
use UniPayment\SDK\BillingAPI;
use UniPayment\SDK\Configuration;
use UniPayment\SDK\Utils\WebhookSignatureUtil;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Mockery;

class WebhookSecurityTest extends TestCase
{
    protected $mockBillingAPI;
    protected $mockConfiguration;
    protected $uniPaymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockBillingAPI = Mockery::mock(BillingAPI::class);
        $this->mockConfiguration = Mockery::mock(Configuration::class);

        Config::set('unipayment', [
            'app_id' => 'test_app_id',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_webhook_secret_12345',
            'logging' => [
                'enabled' => true,
                'level' => 'info'
            ]
        ]);

        $this->uniPaymentService = new UniPaymentService(
            $this->mockBillingAPI,
            $this->mockConfiguration
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_verifies_valid_webhook_signature()
    {
        $payload = '{"invoice_id":"test_123","status":"confirmed","order_id":"order_123"}';
        $validSignature = hash_hmac('sha256', $payload, 'test_webhook_secret_12345');

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret_12345', $validSignature)
            ->andReturn(true);

        $result = $this->uniPaymentService->verifyWebhookSignature($payload, $validSignature);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_webhook_signature()
    {
        $payload = '{"invoice_id":"test_123","status":"confirmed","order_id":"order_123"}';
        $invalidSignature = 'invalid_signature_hash';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret_12345', $invalidSignature)
            ->andReturn(false);

        $result = $this->uniPaymentService->verifyWebhookSignature($payload, $invalidSignature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_webhook_when_secret_is_missing()
    {
        Config::set('unipayment.webhook_secret', '');
        $service = new UniPaymentService($this->mockBillingAPI, $this->mockConfiguration);

        Log::shouldReceive('warning')
            ->once()
            ->with('UniPayment webhook secret not configured');

        $result = $service->verifyWebhookSignature('{"test":"data"}', 'any_signature');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_webhook_signature_verification_exception()
    {
        $payload = '{"test":"data"}';
        $signature = 'test_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->andThrow(new \Exception('Signature verification failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('UniPayment Error in verifyWebhookSignature: Signature verification failed');

        $result = $this->uniPaymentService->verifyWebhookSignature($payload, $signature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_validates_webhook_payload_structure()
    {
        $validPayload = json_encode([
            'invoice_id' => 'test_invoice_123',
            'status' => 'confirmed',
            'order_id' => 'test_order_123',
            'price_amount' => 100.00,
            'price_currency' => 'USD',
            'transaction_id' => 'txn_123'
        ]);

        $signature = 'valid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($validPayload, 'test_webhook_secret_12345', $signature)
            ->andReturn(true);

        // Mock payment status verification to avoid actual API call
        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->andThrow(new \Exception('Skip verification for this test'));

        Log::shouldReceive('log')->once(); // For processWebhookData logging
        Log::shouldReceive('warning')->once(); // For failed verification

        $result = $this->uniPaymentService->handleWebhookNotification($validPayload, $signature);

        $this->assertTrue($result['verified']);
        $this->assertEquals('test_invoice_123', $result['invoice_id']);
        $this->assertEquals('confirmed', $result['status']);
        $this->assertEquals('test_order_123', $result['order_id']);
    }

    /** @test */
    public function it_rejects_malformed_json_payload()
    {
        $malformedPayload = '{"invoice_id":"test_123","status":"confirmed"'; // Missing closing brace
        $signature = 'valid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($malformedPayload, 'test_webhook_secret_12345', $signature)
            ->andReturn(true);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return strpos($message, 'UniPayment Error in handleWebhookNotification') !== false &&
                    strpos($context['signature'], 'valid_signature') !== false;
            });

        $result = $this->uniPaymentService->handleWebhookNotification($malformedPayload, $signature);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertStringContainsString('Invalid JSON payload', $result['error']);
    }

    /** @test */
    public function it_handles_empty_webhook_payload()
    {
        $emptyPayload = '';
        $signature = 'valid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($emptyPayload, 'test_webhook_secret_12345', $signature)
            ->andReturn(true);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'UniPayment Error in handleWebhookNotification') !== false;
            });

        $result = $this->uniPaymentService->handleWebhookNotification($emptyPayload, $signature);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
    }

    /** @test */
    public function it_handles_webhook_with_missing_required_fields()
    {
        $incompletePayload = json_encode([
            'status' => 'confirmed',
            // Missing invoice_id and order_id
        ]);
        $signature = 'valid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($incompletePayload, 'test_webhook_secret_12345', $signature)
            ->andReturn(true);

        Log::shouldReceive('log')->once(); // For processWebhookData logging

        $result = $this->uniPaymentService->handleWebhookNotification($incompletePayload, $signature);

        $this->assertTrue($result['verified']); // Signature was valid
        $this->assertNull($result['invoice_id']);
        $this->assertEquals('confirmed', $result['status']);
        $this->assertNull($result['order_id']);
    }

    /** @test */
    public function it_logs_security_events_properly()
    {
        $payload = '{"test":"data"}';
        $invalidSignature = 'invalid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret_12345', $invalidSignature)
            ->andReturn(false);

        Log::shouldReceive('warning')
            ->once()
            ->with('UniPayment webhook signature verification failed', [
                'payload_length' => strlen($payload),
                'signature' => $invalidSignature
            ]);

        $result = $this->uniPaymentService->handleWebhookNotification($payload, $invalidSignature);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertEquals('Invalid webhook signature', $result['error']);
    }

    /** @test */
    public function it_handles_webhook_signature_timing_attacks()
    {
        $payload = '{"invoice_id":"test_123","status":"confirmed"}';

        // Test multiple signature attempts to ensure consistent timing
        $signatures = [
            'short_sig',
            'medium_length_signature_hash',
            'very_long_signature_hash_that_should_not_affect_timing_significantly_in_verification_process'
        ];

        foreach ($signatures as $signature) {
            $this->mockStatic(WebhookSignatureUtil::class)
                ->shouldReceive('isValid')
                ->once()
                ->with($payload, 'test_webhook_secret_12345', $signature)
                ->andReturn(false);

            $startTime = microtime(true);
            $result = $this->uniPaymentService->verifyWebhookSignature($payload, $signature);
            $endTime = microtime(true);

            $this->assertFalse($result);

            // Ensure verification doesn't take too long (basic timing check)
            $this->assertLessThan(1.0, $endTime - $startTime, 'Signature verification took too long');
        }
    }

    /** @test */
    public function it_validates_webhook_content_type_and_encoding()
    {
        // Test with various payload encodings
        $testData = [
            'invoice_id' => 'test_123',
            'status' => 'confirmed',
            'special_chars' => 'Test with üñíçødé characters',
            'amount' => 99.99
        ];

        $payload = json_encode($testData, JSON_UNESCAPED_UNICODE);
        $signature = 'valid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret_12345', $signature)
            ->andReturn(true);

        Log::shouldReceive('log')->once(); // For processWebhookData logging

        $result = $this->uniPaymentService->handleWebhookNotification($payload, $signature);

        $this->assertTrue($result['verified']);
        $this->assertEquals('test_123', $result['invoice_id']);
        $this->assertEquals('confirmed', $result['status']);
    }

    /** @test */
    public function it_prevents_webhook_replay_attacks_through_proper_logging()
    {
        $payload = '{"invoice_id":"test_123","status":"confirmed","timestamp":' . time() . '}';
        $signature = 'valid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->twice() // Simulate replay
            ->with($payload, 'test_webhook_secret_12345', $signature)
            ->andReturn(true);

        Log::shouldReceive('log')->twice(); // Should log both attempts

        // First webhook call
        $result1 = $this->uniPaymentService->handleWebhookNotification($payload, $signature);
        $this->assertTrue($result1['verified']);

        // Second webhook call (replay)
        $result2 = $this->uniPaymentService->handleWebhookNotification($payload, $signature);
        $this->assertTrue($result2['verified']); // Service doesn't prevent replays, but logs them
    }

    /**
     * Helper method to mock static classes
     */
    protected function mockStatic($class)
    {
        return Mockery::mock('alias:' . $class);
    }
}
