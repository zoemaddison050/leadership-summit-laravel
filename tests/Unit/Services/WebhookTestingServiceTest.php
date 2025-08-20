<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\WebhookTestingService;
use App\Services\WebhookUrlGenerator;
use App\Models\UniPaymentSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookTestingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WebhookTestingService $service;
    protected WebhookUrlGenerator $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlGenerator = $this->createMock(WebhookUrlGenerator::class);
        $this->service = new WebhookTestingService($this->urlGenerator);

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function tests_webhook_accessibility_with_head_request()
    {
        $webhookUrl = 'https://example.com/webhook';

        Http::fake([
            $webhookUrl => Http::response('', 200)
        ]);

        $result = $this->service->testWebhookAccessibility($webhookUrl);

        $this->assertEquals($webhookUrl, $result['url']);
        $this->assertTrue($result['accessible']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('HEAD', $result['test_method']);
        $this->assertIsFloat($result['response_time']);
        $this->assertNull($result['error']);
        $this->assertInstanceOf(Carbon::class, $result['tested_at']);
    }

    /** @test */
    public function tests_webhook_accessibility_with_options_fallback()
    {
        $webhookUrl = 'https://example.com/webhook';

        Http::fake([
            $webhookUrl => Http::sequence()
                ->push('', 405) // HEAD request returns Method Not Allowed
                ->push('', 200) // OPTIONS request succeeds
        ]);

        $result = $this->service->testWebhookAccessibility($webhookUrl);

        $this->assertTrue($result['accessible']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('OPTIONS', $result['test_method']);
    }

    /** @test */
    public function considers_404_as_accessible()
    {
        $webhookUrl = 'https://example.com/webhook';

        Http::fake([
            $webhookUrl => Http::response('Not Found', 404)
        ]);

        $result = $this->service->testWebhookAccessibility($webhookUrl);

        $this->assertTrue($result['accessible']);
        $this->assertEquals(404, $result['status_code']);
    }

    /** @test */
    public function considers_405_as_accessible()
    {
        $webhookUrl = 'https://example.com/webhook';

        Http::fake([
            $webhookUrl => Http::sequence()
                ->push('Method Not Allowed', 405) // HEAD request
                ->push('', 200) // OPTIONS request
        ]);

        $result = $this->service->testWebhookAccessibility($webhookUrl);

        $this->assertTrue($result['accessible']);
        $this->assertEquals(200, $result['status_code']); // Should be 200 from OPTIONS
        $this->assertEquals('OPTIONS', $result['test_method']);
    }

    /** @test */
    public function handles_network_timeout_gracefully()
    {
        $webhookUrl = 'https://timeout.example.com/webhook';

        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $result = $this->service->testWebhookAccessibility($webhookUrl);

        $this->assertFalse($result['accessible']);
        $this->assertNull($result['status_code']);
        $this->assertEquals('Connection timeout', $result['error']);
    }

    /** @test */
    public function caches_accessibility_test_results()
    {
        $webhookUrl = 'https://example.com/webhook';

        Http::fake([
            $webhookUrl => Http::response('', 200)
        ]);

        $result = $this->service->testWebhookAccessibility($webhookUrl);

        // Verify result is cached
        $cachedResult = Cache::get("webhook_test_{$webhookUrl}");
        $this->assertNotNull($cachedResult);
        $this->assertEquals($result['accessible'], $cachedResult['accessible']);
    }

    /** @test */
    public function uses_generated_webhook_url_when_none_provided()
    {
        $generatedUrl = 'https://generated.example.com/webhook';

        $this->urlGenerator->expects($this->once())
            ->method('generateUniPaymentWebhookUrl')
            ->willReturn($generatedUrl);

        Http::fake([
            $generatedUrl => Http::response('', 200)
        ]);

        $result = $this->service->testWebhookAccessibility();

        $this->assertEquals($generatedUrl, $result['url']);
    }

    /** @test */
    public function tests_webhook_with_sample_payload()
    {
        $webhookUrl = 'https://example.com/webhook';

        Http::fake([
            $webhookUrl => Http::response('{"status": "ok"}', 200)
        ]);

        $result = $this->service->testWebhookWithPayload($webhookUrl);

        $this->assertEquals($webhookUrl, $result['url']);
        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('{"status": "ok"}', $result['response_body']);
        $this->assertIsFloat($result['response_time']);
        $this->assertNull($result['error']);
        $this->assertInstanceOf(Carbon::class, $result['tested_at']);
        $this->assertIsArray($result['payload']);
        $this->assertTrue($result['payload']['test_mode']);
    }

    /** @test */
    public function handles_webhook_payload_test_failure()
    {
        $webhookUrl = 'https://example.com/webhook';

        Http::fake([
            $webhookUrl => Http::response('Server Error', 500)
        ]);

        $result = $this->service->testWebhookWithPayload($webhookUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals(500, $result['status_code']);
        $this->assertEquals('Server Error', $result['response_body']);
    }

    /** @test */
    public function handles_webhook_payload_network_error()
    {
        $webhookUrl = 'https://error.example.com/webhook';

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $result = $this->service->testWebhookWithPayload($webhookUrl);

        $this->assertFalse($result['success']);
        $this->assertNull($result['status_code']);
        $this->assertEquals('Network error', $result['error']);
    }

    /** @test */
    public function generates_test_payload_with_correct_structure()
    {
        $webhookUrl = 'https://example.com/webhook';

        Http::fake([
            $webhookUrl => Http::response('', 200)
        ]);

        $result = $this->service->testWebhookWithPayload($webhookUrl);

        $payload = $result['payload'];
        $this->assertEquals('test', $payload['event_type']);
        $this->assertEquals('test', $payload['status']);
        $this->assertTrue($payload['test_mode']);
        $this->assertStringStartsWith('test_', $payload['order_id']);
        $this->assertNotEmpty($payload['timestamp']);
    }

    /** @test */
    public function runs_comprehensive_diagnostics()
    {
        $webhookUrl = 'https://example.com/webhook';

        $this->urlGenerator->expects($this->once())
            ->method('generateUniPaymentWebhookUrl')
            ->willReturn($webhookUrl);

        Http::fake([
            $webhookUrl => Http::response('{"status": "ok"}', 200)
        ]);

        // Create UniPayment settings
        UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'is_enabled' => true
        ]);

        $result = $this->service->runDiagnostics();

        $this->assertInstanceOf(Carbon::class, $result['timestamp']);
        $this->assertNotEmpty($result['environment']);
        $this->assertEquals($webhookUrl, $result['webhook_url']);
        $this->assertIsArray($result['url_accessibility']);
        $this->assertIsArray($result['payload_test']);
        $this->assertIsArray($result['configuration']);
        $this->assertIsArray($result['recommendations']);
    }

    /** @test */
    public function diagnostics_handles_webhook_url_generation_failure()
    {
        $this->urlGenerator->expects($this->once())
            ->method('generateUniPaymentWebhookUrl')
            ->willThrowException(new \Exception('URL generation failed'));

        $result = $this->service->runDiagnostics();

        $this->assertNull($result['webhook_url']);
        $this->assertContains('Failed to generate webhook URL: URL generation failed', $result['recommendations']);
    }

    /** @test */
    public function diagnostics_adds_accessibility_recommendation()
    {
        $webhookUrl = 'https://inaccessible.example.com/webhook';

        $this->urlGenerator->expects($this->once())
            ->method('generateUniPaymentWebhookUrl')
            ->willReturn($webhookUrl);

        Http::fake([
            $webhookUrl => Http::response('', 500)
        ]);

        $result = $this->service->runDiagnostics();

        $this->assertContains('Webhook URL is not accessible from external services', $result['recommendations']);
    }

    /** @test */
    public function diagnostics_adds_payload_handling_recommendation()
    {
        $webhookUrl = 'https://example.com/webhook';

        $this->urlGenerator->expects($this->once())
            ->method('generateUniPaymentWebhookUrl')
            ->willReturn($webhookUrl);

        Http::fake([
            $webhookUrl => Http::sequence()
                ->push('', 200) // Accessibility test passes
                ->push('', 400) // Payload test fails
        ]);

        $result = $this->service->runDiagnostics();

        $this->assertContains('Webhook endpoint does not properly handle POST requests', $result['recommendations']);
    }

    /** @test */
    public function diagnostics_adds_ngrok_recommendation_for_local_environment()
    {
        $webhookUrl = 'http://localhost:8000/webhook';

        $this->urlGenerator->expects($this->once())
            ->method('generateUniPaymentWebhookUrl')
            ->willReturn($webhookUrl);

        Http::fake([
            $webhookUrl => Http::response('', 200)
        ]);

        config(['app.env' => 'local']);

        $result = $this->service->runDiagnostics();

        $this->assertContains('Consider using ngrok for local webhook testing', $result['recommendations']);
    }

    /** @test */
    public function checks_webhook_configuration_correctly()
    {
        // Create complete UniPayment settings
        UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_enabled' => true,
            'webhook_url' => 'https://example.com/webhook',
            'is_enabled' => true
        ]);

        config(['app.url' => 'https://example.com']);

        // Mock the URL generator to return the webhook URL
        $this->urlGenerator->expects($this->once())
            ->method('generateUniPaymentWebhookUrl')
            ->willReturn('https://example.com/webhook');

        Http::fake([
            'https://example.com/webhook' => Http::response('', 200)
        ]);

        $result = $this->service->runDiagnostics();

        $config = $result['configuration'];
        $this->assertTrue($config['unipayment_configured']);
        $this->assertTrue($config['webhook_enabled']);
        $this->assertTrue($config['webhook_url_set']);
        $this->assertTrue($config['app_url_set']);
    }

    /** @test */
    public function handles_missing_unipayment_configuration()
    {
        $result = $this->service->runDiagnostics();

        $config = $result['configuration'];
        $this->assertFalse($config['unipayment_configured']);
        $this->assertFalse($config['webhook_enabled']);
        $this->assertFalse($config['webhook_url_set']);
    }

    /** @test */
    public function gets_cached_test_results()
    {
        $webhookUrl = 'https://example.com/webhook';
        $cachedResult = [
            'url' => $webhookUrl,
            'accessible' => true,
            'status_code' => 200
        ];

        Cache::put("webhook_test_{$webhookUrl}", $cachedResult, 300);

        $result = $this->service->getCachedTestResults($webhookUrl);

        $this->assertEquals($cachedResult, $result);
    }

    /** @test */
    public function returns_null_for_non_cached_results()
    {
        $result = $this->service->getCachedTestResults('https://not-cached.example.com/webhook');

        $this->assertNull($result);
    }

    /** @test */
    public function clears_specific_webhook_test_cache()
    {
        $webhookUrl = 'https://example.com/webhook';

        Cache::put("webhook_test_{$webhookUrl}", ['test' => 'data'], 300);

        $this->service->clearTestCache($webhookUrl);

        $this->assertNull(Cache::get("webhook_test_{$webhookUrl}"));
    }

    /** @test */
    public function clears_all_webhook_test_cache()
    {
        Cache::put('webhook_test_url1', ['test' => 'data1'], 300);
        Cache::put('webhook_test_url2', ['test' => 'data2'], 300);
        Cache::put('other_cache_key', ['test' => 'data3'], 300);

        $this->service->clearTestCache();

        // All cache should be cleared (simplified implementation)
        $this->assertNull(Cache::get('webhook_test_url1'));
        $this->assertNull(Cache::get('webhook_test_url2'));
        $this->assertNull(Cache::get('other_cache_key'));
    }

    /** @test */
    public function logs_accessibility_test_failure()
    {
        $webhookUrl = 'https://error.example.com/webhook';

        Log::shouldReceive('warning')
            ->once()
            ->with('Webhook accessibility test failed', \Mockery::type('array'));

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $this->service->testWebhookAccessibility($webhookUrl);
    }

    /** @test */
    public function logs_payload_test_failure()
    {
        $webhookUrl = 'https://error.example.com/webhook';

        Log::shouldReceive('warning')
            ->once()
            ->with('Webhook payload test failed', \Mockery::type('array'));

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $this->service->testWebhookWithPayload($webhookUrl);
    }

    /** @test */
    public function measures_response_time_accurately()
    {
        $webhookUrl = 'https://example.com/webhook';

        Http::fake([
            $webhookUrl => Http::response('', 200)
        ]);

        $result = $this->service->testWebhookAccessibility($webhookUrl);

        $this->assertIsFloat($result['response_time']);
        $this->assertGreaterThan(0, $result['response_time']); // Should be greater than 0
        $this->assertLessThan(1000, $result['response_time']); // Should be less than 1 second
    }
}
