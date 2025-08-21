<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\WebhookUrlGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class WebhookUrlGeneratorTest extends TestCase
{
    protected WebhookUrlGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new WebhookUrlGenerator();
    }

    public function test_detects_testing_environment()
    {
        $this->assertEquals('testing', $this->generator->detectEnvironment());
    }

    public function test_generates_testing_webhook_url()
    {
        $url = $this->generator->generateUniPaymentWebhookUrl();
        $this->assertEquals('https://test.example.com/payment/unipayment/webhook', $url);
    }

    public function test_validates_valid_webhook_url()
    {
        $url = 'https://example.com/payment/unipayment/webhook';
        $result = $this->generator->validateWebhookUrl($url);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validates_invalid_webhook_url()
    {
        $url = 'not-a-valid-url';
        $result = $this->generator->validateWebhookUrl($url);

        $this->assertFalse($result['valid']);
        $this->assertContains('Invalid URL format', $result['errors']);
    }

    public function test_rejects_localhost_in_production()
    {
        // Temporarily set environment to production
        Config::set('app.env', 'production');

        // Create a new generator instance to pick up the config change
        $generator = new WebhookUrlGenerator();

        $url = 'https://localhost:8000/payment/unipayment/webhook';
        $result = $generator->validateWebhookUrl($url);

        $this->assertFalse($result['valid']);
        $this->assertContains('Localhost URLs not accessible in production', $result['errors']);
    }

    public function test_requires_https_in_production()
    {
        // Temporarily set environment to production
        Config::set('app.env', 'production');

        // Create a new generator instance to pick up the config change
        $generator = new WebhookUrlGenerator();

        $url = 'http://example.com/payment/unipayment/webhook';
        $result = $generator->validateWebhookUrl($url);

        $this->assertFalse($result['valid']);
        $this->assertContains('HTTPS required in production environment', $result['errors']);
    }

    public function test_webhook_accessibility_test()
    {
        Http::fake([
            'https://accessible.example.com/*' => Http::response('', 200),
            'https://not-accessible.example.com/*' => Http::response('', 500),
        ]);

        $this->assertTrue($this->generator->isWebhookAccessible('https://accessible.example.com/webhook'));
        $this->assertFalse($this->generator->isWebhookAccessible('https://not-accessible.example.com/webhook'));
    }

    public function test_get_validated_webhook_url()
    {
        $result = $this->generator->getValidatedWebhookUrl();

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('environment', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertEquals('testing', $result['environment']);
        $this->assertEquals('https://test.example.com/payment/unipayment/webhook', $result['url']);
    }

    public function test_get_webhook_recommendations_for_development()
    {
        // Mock development environment
        Config::set('app.env', 'local');

        // Create a new generator instance to pick up the config change
        $generator = new WebhookUrlGenerator();

        $recommendations = $generator->getWebhookRecommendations();

        $this->assertIsArray($recommendations);
        // Should recommend ngrok setup since we can't detect it in tests
        $this->assertNotEmpty($recommendations);
    }

    public function test_get_webhook_recommendations_for_production()
    {
        // Mock production environment without APP_URL
        Config::set('app.env', 'production');
        Config::set('app.url', null);

        // Create a new generator instance to pick up the config change
        $generator = new WebhookUrlGenerator();

        $recommendations = $generator->getWebhookRecommendations();

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        // Should recommend setting APP_URL
        $found = false;
        foreach ($recommendations as $rec) {
            if (str_contains($rec['message'], 'APP_URL')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should recommend setting APP_URL');
    }
}
