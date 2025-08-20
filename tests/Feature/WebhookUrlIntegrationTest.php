<?php

namespace Tests\Feature;

use App\Services\WebhookUrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookUrlIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_url_generation_in_testing_environment()
    {
        // Set testing environment
        config(['app.env' => 'testing']);

        $generator = app(WebhookUrlGenerator::class);
        $webhookData = $generator->getValidatedWebhookUrl();

        $this->assertNotNull($webhookData['url']);
        $this->assertEquals('testing', $webhookData['environment']);
        $this->assertTrue($webhookData['validation']['valid']);
        $this->assertEquals('https://test.example.com/payment/unipayment/webhook', $webhookData['url']);
    }

    public function test_webhook_url_validation_rejects_invalid_urls()
    {
        $generator = app(WebhookUrlGenerator::class);

        $validation = $generator->validateWebhookUrl('invalid-url');

        $this->assertFalse($validation['valid']);
        $this->assertContains('Invalid URL format', $validation['errors']);
    }

    public function test_webhook_url_validation_accepts_valid_urls()
    {
        $generator = app(WebhookUrlGenerator::class);

        $validation = $generator->validateWebhookUrl('https://example.com/webhook');

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
    }

    public function test_production_environment_requires_https()
    {
        // Temporarily set production environment
        $originalEnv = config('app.env');
        config(['app.env' => 'production']);

        $generator = app(WebhookUrlGenerator::class);

        $validation = $generator->validateWebhookUrl('http://example.com/webhook');

        $this->assertFalse($validation['valid']);
        $this->assertContains('HTTPS required in production environment', $validation['errors']);

        // Restore original environment
        config(['app.env' => $originalEnv]);
    }

    public function test_production_environment_rejects_localhost()
    {
        // Temporarily set production environment
        $originalEnv = config('app.env');
        config(['app.env' => 'production']);

        $generator = app(WebhookUrlGenerator::class);

        $validation = $generator->validateWebhookUrl('https://localhost:8000/webhook');

        $this->assertFalse($validation['valid']);
        $this->assertContains('Localhost URLs not accessible in production', $validation['errors']);

        // Restore original environment
        config(['app.env' => $originalEnv]);
    }

    public function test_webhook_recommendations_for_development()
    {
        // Set development environment
        $originalEnv = config('app.env');
        config(['app.env' => 'development']);

        $generator = app(WebhookUrlGenerator::class);
        $recommendations = $generator->getWebhookRecommendations();

        $this->assertIsArray($recommendations);
        // Should have recommendations for development setup
        $this->assertNotEmpty($recommendations);

        // Restore original environment
        config(['app.env' => $originalEnv]);
    }
}
