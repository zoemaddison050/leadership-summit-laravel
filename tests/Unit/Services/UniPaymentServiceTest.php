<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UniPaymentService;
use UniPayment\SDK\BillingAPI;
use UniPayment\SDK\Configuration;
use UniPayment\SDK\Model\CreateInvoiceRequest;
use UniPayment\SDK\Model\CreateInvoiceResponse;
use UniPayment\SDK\Model\GetInvoiceByIdResponse;
use UniPayment\SDK\Model\InvoiceData;
use UniPayment\SDK\UnipaymentSDKException;
use UniPayment\SDK\Utils\WebhookSignatureUtil;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Mockery;
use Exception;

class UniPaymentServiceTest extends TestCase
{
    protected $mockBillingAPI;
    protected $mockConfiguration;
    protected $uniPaymentService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the BillingAPI and Configuration
        $this->mockBillingAPI = Mockery::mock(BillingAPI::class);
        $this->mockConfiguration = Mockery::mock(Configuration::class);

        // Set up test configuration
        Config::set('unipayment', [
            'app_id' => 'test_app_id',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_webhook_secret',
            'supported_currencies' => ['USD', 'EUR'],
            'default_currency' => 'USD',
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00,
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
    public function it_checks_if_unipayment_is_configured()
    {
        $this->assertTrue($this->uniPaymentService->isConfigured());

        // Test with missing configuration
        Config::set('unipayment.app_id', '');
        $service = new UniPaymentService($this->mockBillingAPI, $this->mockConfiguration);
        $this->assertFalse($service->isConfigured());
    }

    /** @test */
    public function it_returns_environment()
    {
        $this->assertEquals('sandbox', $this->uniPaymentService->getEnvironment());
    }

    /** @test */
    public function it_returns_supported_currencies()
    {
        $currencies = $this->uniPaymentService->getSupportedCurrencies();
        $this->assertEquals(['USD', 'EUR'], $currencies);
    }

    /** @test */
    public function it_returns_default_currency()
    {
        $this->assertEquals('USD', $this->uniPaymentService->getDefaultCurrency());
    }

    /** @test */
    public function it_returns_processing_fee_percentage()
    {
        $this->assertEquals(2.9, $this->uniPaymentService->getProcessingFeePercentage());
    }

    /** @test */
    public function it_returns_minimum_amount()
    {
        $this->assertEquals(1.00, $this->uniPaymentService->getMinimumAmount());
    }

    /** @test */
    public function it_returns_maximum_amount()
    {
        $this->assertEquals(10000.00, $this->uniPaymentService->getMaximumAmount());
    }

    /** @test */
    public function it_creates_payment_successfully()
    {
        // Mock the response
        $mockResponse = Mockery::mock(CreateInvoiceResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getInvoiceId')->andReturn('test_invoice_id');
        $mockResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->with(Mockery::type(CreateInvoiceRequest::class))
            ->andReturn($mockResponse);

        Log::shouldReceive('log')->twice(); // For logging API calls

        $result = $this->uniPaymentService->createPayment(
            100.00,
            'USD',
            'test_order_123',
            'Test Payment',
            'Test payment description',
            'https://example.com/notify',
            'https://example.com/redirect',
            ['registration_id' => 123]
        );

        $this->assertInstanceOf(CreateInvoiceResponse::class, $result);
    }

    /** @test */
    public function it_throws_exception_when_creating_payment_with_invalid_config()
    {
        Config::set('unipayment.app_id', '');
        $service = new UniPaymentService($this->mockBillingAPI, $this->mockConfiguration);

        $this->expectException(UnipaymentSDKException::class);
        $this->expectExceptionMessage('Failed to create payment: UniPayment is not properly configured');

        $service->createPayment(
            100.00,
            'USD',
            'test_order_123',
            'Test Payment',
            'Test payment description',
            'https://example.com/notify',
            'https://example.com/redirect'
        );
    }

    /** @test */
    public function it_handles_sdk_exception_when_creating_payment()
    {
        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new UnipaymentSDKException('API Error'));

        Log::shouldReceive('log')->once(); // For logging API call
        Log::shouldReceive('error')->once(); // For logging error

        $this->expectException(UnipaymentSDKException::class);
        $this->expectExceptionMessage('API Error');

        $this->uniPaymentService->createPayment(
            100.00,
            'USD',
            'test_order_123',
            'Test Payment',
            'Test payment description',
            'https://example.com/notify',
            'https://example.com/redirect'
        );
    }

    /** @test */
    public function it_gets_payment_status_successfully()
    {
        $mockResponse = Mockery::mock(GetInvoiceByIdResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getStatus')->andReturn('confirmed');
        $mockResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->with('test_invoice_id')
            ->andReturn($mockResponse);

        Log::shouldReceive('log')->twice(); // For logging API calls

        $result = $this->uniPaymentService->getPaymentStatus('test_invoice_id');

        $this->assertInstanceOf(GetInvoiceByIdResponse::class, $result);
    }

    /** @test */
    public function it_throws_exception_when_getting_payment_status_with_invalid_config()
    {
        Config::set('unipayment.app_id', '');
        $service = new UniPaymentService($this->mockBillingAPI, $this->mockConfiguration);

        $this->expectException(UnipaymentSDKException::class);
        $this->expectExceptionMessage('Failed to get payment status: UniPayment is not properly configured');

        $service->getPaymentStatus('test_invoice_id');
    }

    /** @test */
    public function it_verifies_payment_successfully()
    {
        $mockResponse = Mockery::mock(GetInvoiceByIdResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getStatus')->andReturn('confirmed');
        $mockResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->with('test_invoice_id')
            ->andReturn($mockResponse);

        Log::shouldReceive('log')->twice(); // For logging API calls

        $result = $this->uniPaymentService->verifyPayment('test_invoice_id');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_payment_verification_fails()
    {
        $mockResponse = Mockery::mock(GetInvoiceByIdResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getStatus')->andReturn('failed');
        $mockResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->with('test_invoice_id')
            ->andReturn($mockResponse);

        Log::shouldReceive('log')->twice(); // For logging API calls

        $result = $this->uniPaymentService->verifyPayment('test_invoice_id');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_when_payment_verification_throws_exception()
    {
        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->andThrow(new Exception('API Error'));

        Log::shouldReceive('log')->once(); // For logging API call
        Log::shouldReceive('error')->once(); // For logging error

        $result = $this->uniPaymentService->verifyPayment('test_invoice_id');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_verifies_webhook_signature_successfully()
    {
        $payload = '{"test": "data"}';
        $signature = 'valid_signature';

        // Mock the WebhookSignatureUtil
        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret', $signature)
            ->andReturn(true);

        $result = $this->uniPaymentService->verifyWebhookSignature($payload, $signature);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_webhook_signature_is_invalid()
    {
        $payload = '{"test": "data"}';
        $signature = 'invalid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret', $signature)
            ->andReturn(false);

        $result = $this->uniPaymentService->verifyWebhookSignature($payload, $signature);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_when_webhook_secret_is_not_configured()
    {
        Config::set('unipayment.webhook_secret', '');
        $service = new UniPaymentService($this->mockBillingAPI, $this->mockConfiguration);

        Log::shouldReceive('warning')->once();

        $result = $service->verifyWebhookSignature('{"test": "data"}', 'signature');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_processes_webhook_data_successfully()
    {
        $webhookData = [
            'invoice_id' => 'test_invoice_123',
            'status' => 'confirmed',
            'order_id' => 'test_order_123',
            'price_amount' => 100.00,
            'price_currency' => 'USD',
            'transaction_id' => 'txn_123',
            'ext_args' => '{"registration_id": 123}'
        ];

        Log::shouldReceive('log')->once(); // For logging API call

        $result = $this->uniPaymentService->processWebhookData($webhookData);

        $this->assertEquals('test_invoice_123', $result['invoice_id']);
        $this->assertEquals('confirmed', $result['status']);
        $this->assertEquals('test_order_123', $result['order_id']);
        $this->assertEquals(100.00, $result['amount']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals('txn_123', $result['transaction_id']);
        $this->assertEquals(['registration_id' => 123], $result['ext_args']);
        $this->assertEquals($webhookData, $result['raw_data']);
    }

    /** @test */
    public function it_handles_payment_callback_successfully()
    {
        $callbackData = [
            'invoice_id' => 'test_invoice_123',
            'status' => 'confirmed',
            'order_id' => 'test_order_123',
            'price_amount' => 100.00,
            'price_currency' => 'USD',
            'transaction_id' => 'txn_123',
            'ext_args' => '{"registration_id": 123}'
        ];

        $mockResponse = Mockery::mock(GetInvoiceByIdResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getStatus')->andReturn('confirmed');
        $mockResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->with('test_invoice_123')
            ->andReturn($mockResponse);

        Log::shouldReceive('log')->twice(); // For logging API calls

        $result = $this->uniPaymentService->handlePaymentCallback($callbackData);

        $this->assertTrue($result['success']);
        $this->assertEquals('test_invoice_123', $result['invoice_id']);
        $this->assertEquals('confirmed', $result['status']);
        $this->assertEquals('test_order_123', $result['order_id']);
        $this->assertTrue($result['verified']);
    }

    /** @test */
    public function it_handles_payment_callback_with_missing_data()
    {
        $callbackData = [
            'status' => 'confirmed',
            // Missing invoice_id and order_id
        ];

        Log::shouldReceive('log')->once(); // For logging API call
        Log::shouldReceive('error')->once(); // For logging error

        $result = $this->uniPaymentService->handlePaymentCallback($callbackData);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertStringContainsString('Missing required callback data', $result['error']);
    }

    /** @test */
    public function it_handles_webhook_notification_successfully()
    {
        $webhookData = [
            'invoice_id' => 'test_invoice_123',
            'status' => 'confirmed',
            'order_id' => 'test_order_123',
            'price_amount' => 100.00,
            'price_currency' => 'USD',
            'transaction_id' => 'txn_123'
        ];

        $payload = json_encode($webhookData);
        $signature = 'valid_signature';

        // Mock webhook signature verification
        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret', $signature)
            ->andReturn(true);

        // Mock payment status verification
        $mockResponse = Mockery::mock(GetInvoiceByIdResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getStatus')->andReturn('confirmed');
        $mockResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->with('test_invoice_123')
            ->andReturn($mockResponse);

        Log::shouldReceive('log')->twice(); // For logging API calls

        $result = $this->uniPaymentService->handleWebhookNotification($payload, $signature);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['verified']);
        $this->assertEquals('test_invoice_123', $result['invoice_id']);
        $this->assertEquals('confirmed', $result['status']);
        $this->assertEquals('confirmed', $result['verified_status']);
    }

    /** @test */
    public function it_handles_webhook_notification_with_invalid_signature()
    {
        $payload = '{"test": "data"}';
        $signature = 'invalid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret', $signature)
            ->andReturn(false);

        Log::shouldReceive('warning')->once();

        $result = $this->uniPaymentService->handleWebhookNotification($payload, $signature);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertEquals('Invalid webhook signature', $result['error']);
    }

    /** @test */
    public function it_handles_webhook_notification_with_invalid_json()
    {
        $payload = 'invalid json';
        $signature = 'valid_signature';

        $this->mockStatic(WebhookSignatureUtil::class)
            ->shouldReceive('isValid')
            ->once()
            ->with($payload, 'test_webhook_secret', $signature)
            ->andReturn(true);

        Log::shouldReceive('error')->once();

        $result = $this->uniPaymentService->handleWebhookNotification($payload, $signature);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertStringContainsString('Invalid JSON payload', $result['error']);
    }

    /** @test */
    public function it_identifies_successful_payment_status()
    {
        $this->assertTrue($this->uniPaymentService->isPaymentSuccessful('confirmed'));
        $this->assertTrue($this->uniPaymentService->isPaymentSuccessful('complete'));
        $this->assertTrue($this->uniPaymentService->isPaymentSuccessful('paid'));
        $this->assertTrue($this->uniPaymentService->isPaymentSuccessful('success'));
        $this->assertTrue($this->uniPaymentService->isPaymentSuccessful('CONFIRMED')); // Case insensitive

        $this->assertFalse($this->uniPaymentService->isPaymentSuccessful('pending'));
        $this->assertFalse($this->uniPaymentService->isPaymentSuccessful('failed'));
        $this->assertFalse($this->uniPaymentService->isPaymentSuccessful(null));
    }

    /** @test */
    public function it_identifies_failed_payment_status()
    {
        $this->assertTrue($this->uniPaymentService->isPaymentFailed('failed'));
        $this->assertTrue($this->uniPaymentService->isPaymentFailed('cancelled'));
        $this->assertTrue($this->uniPaymentService->isPaymentFailed('expired'));
        $this->assertTrue($this->uniPaymentService->isPaymentFailed('invalid'));
        $this->assertTrue($this->uniPaymentService->isPaymentFailed('FAILED')); // Case insensitive

        $this->assertFalse($this->uniPaymentService->isPaymentFailed('confirmed'));
        $this->assertFalse($this->uniPaymentService->isPaymentFailed('pending'));
        $this->assertFalse($this->uniPaymentService->isPaymentFailed(null));
    }

    /** @test */
    public function it_updates_transaction_status()
    {
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'confirmed'
        ];

        Log::shouldReceive('log')->once(); // For logging API call

        $result = $this->uniPaymentService->updateTransactionStatus(
            'test_invoice_123',
            'test_order_123',
            $paymentData
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_exception_when_updating_transaction_status()
    {
        // Force an exception by passing invalid data
        Log::shouldReceive('log')->once()->andThrow(new Exception('Logging error'));
        Log::shouldReceive('error')->once();

        $result = $this->uniPaymentService->updateTransactionStatus(
            'test_invoice_123',
            'test_order_123',
            []
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_tests_connection_successfully()
    {
        $mockResponse = Mockery::mock(CreateInvoiceResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getInvoiceId')->andReturn('test_connection_invoice');
        $mockResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->with(Mockery::type(CreateInvoiceRequest::class))
            ->andReturn($mockResponse);

        $result = $this->uniPaymentService->testConnection();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Connection successful', $result['message']);
        $this->assertEquals('sandbox', $result['data']['environment']);
        $this->assertEquals('test_app_id', $result['data']['app_id']);
        $this->assertEquals('test_connection_invoice', $result['data']['test_invoice_id']);
    }

    /** @test */
    public function it_handles_authentication_error_in_connection_test()
    {
        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new UnipaymentSDKException('Authentication failed: Invalid credentials'));

        Log::shouldReceive('error')->once();

        $result = $this->uniPaymentService->testConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Authentication failed', $result['message']);
    }

    /** @test */
    public function it_handles_network_error_in_connection_test()
    {
        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new UnipaymentSDKException('Network connection timeout'));

        Log::shouldReceive('error')->once();

        $result = $this->uniPaymentService->testConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Network error', $result['message']);
    }

    /** @test */
    public function it_handles_missing_credentials_in_connection_test()
    {
        $result = $this->uniPaymentService->testConnection('', '');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('App ID and API Key are required', $result['message']);
    }

    /** @test */
    public function it_handles_other_sdk_errors_as_successful_connection()
    {
        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new UnipaymentSDKException('Validation error: Amount too small'));

        Log::shouldReceive('info')->once();

        $result = $this->uniPaymentService->testConnection();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Connection successful (with minor API validation error)', $result['message']);
        $this->assertArrayHasKey('warning', $result);
    }

    /**
     * Helper method to mock static classes
     */
    protected function mockStatic($class)
    {
        return Mockery::mock('alias:' . $class);
    }
}
