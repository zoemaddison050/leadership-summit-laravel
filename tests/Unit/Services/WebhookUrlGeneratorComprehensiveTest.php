<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\WebhookUrlGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class WebhookUrlGeneratorComprehensiveTest extends TestCase
{
    protected WebhookUrlGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new WebhookUrlGenerator();
    }

    /** @test */
    public function generates_correct_webhook_url_for_testing_environment()
    {
        Config::set('app.env', 'testing');

        $generator = new WebhookUrlGenerator();
        $url = $generator->generateUniPaymentWebhookUrl();

        $this->assertEquals('https://test.example.com/payment/unipayment/webhook', $url);
    }

    /** @test */
    public function generates_correct_webhook_url_for_development_environment()
    {
        Config::set('app.env', 'development');
        Config::set('app.url', 'http://localhost:8000');

        $generator = new WebhookUrlGenerator();
        $url = $generator->generateUniPaymentWebhookUrl();

        $this->assertEquals('http://localhost:8000/payment/unipayment/webhook', $url);
    }

    /** @test */
    public function generates_correct_webhook_url_for_production_environment()
    {
        Config::set('app.env', 'production');
        Config::set('app.url', 'https://example.com');

        $generator = new WebhookUrlGenerator();
        $url = $generator->generateUniPaymentWebhookUrl();

        $this->assertEquals('https://example.com/payment/unipayment/webhook', $url);
    }

    /** @test */
    public function throws_exception_when_production_app_url_not_configured()
    {
        Config::set('app.env', 'production');
        Config::set('app.url', null);

        $generator = new WebhookUrlGenerator();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('APP_URL must be configured in production environment');

        $generator->generateUniPaymentWebhookUrl();
    }

    /** @test */
    public function detects_ngrok_url_in_development()
    {
        Config::set('app.env', 'development');

        Http::fake([
            'http://127.0.0.1:4040/api/tunnels' => Http::response([
                'tunnels' => [
                    [
                        'public_url' => 'https://abc123.ngrok.io',
                        'proto' => 'https'
                    ]
                ]
            ])
        ]);

        $generator = new WebhookUrlGenerator();
        $url = $generator->generateUniPaymentWebhookUrl();

        $this->assertEquals('https://abc123.ngrok.io/payment/unipayment/webhook', $url);
    }

    /** @test */
    public function handles_ngrok_api_failure_gracefully()
    {
        Config::set('app.env', 'development');
        Config::set('app.url', 'http://localhost:8000');

        Http::fake([
            'http://127.0.0.1:4040/api/tunnels' => Http::response([], 500)
        ]);

        $generator = new WebhookUrlGenerator();
        $url = $generator->generateUniPaymentWebhookUrl();

        $this->assertEquals('http://localhost:8000/payment/unipayment/webhook', $url);
    }

    /** @test */
    public function uses_tunnel_url_environment_variable()
    {
        Config::set('app.env', 'development');
        putenv('TUNNEL_URL=https://custom-tunnel.example.com');

        Http::fake([
            'http://127.0.0.1:4040/api/tunnels' => Http::response([], 404)
        ]);

        $generator = new WebhookUrlGenerator();
        $url = $generator->generateUniPaymentWebhookUrl();

        $this->assertEquals('https://custom-tunnel.example.com/payment/unipayment/webhook', $url);

        putenv('TUNNEL_URL'); // Clear environment variable
    }

    /** @test */
    public function validates_valid_webhook_url_format()
    {
        $result = $this->generator->validateWebhookUrl('https://example.com/payment/unipayment/webhook');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function rejects_invalid_url_format()
    {
        $result = $this->generator->validateWebhookUrl('not-a-valid-url');

        $this->assertFalse($result['valid']);
        $this->assertContains('Invalid URL format', $result['errors']);
    }

    /** @test */
    public function requires_https_in_production_environment()
    {
        Config::set('app.env', 'production');

        $generator = new WebhookUrlGenerator();
        $result = $generator->validateWebhookUrl('http://example.com/webhook');

        $this->assertFalse($result['valid']);
        $this->assertContains('HTTPS required in production environment', $result['errors']);
    }

    /** @test */
    public function rejects_localhost_in_production_environment()
    {
        Config::set('app.env', 'production');

        $generator = new WebhookUrlGenerator();
        $result = $generator->validateWebhookUrl('https://localhost:8000/webhook');

        $this->assertFalse($result['valid']);
        $this->assertContains('Localhost URLs not accessible in production', $result['errors']);
    }

    /** @test */
    public function allows_http_in_development_environment()
    {
        Config::set('app.env', 'development');

        $generator = new WebhookUrlGenerator();
        $result = $generator->validateWebhookUrl('http://localhost:8000/webhook');

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function tests_webhook_accessibility_successfully()
    {
        Http::fake([
            'https://accessible.example.com/webhook' => Http::response('', 200)
        ]);

        $accessible = $this->generator->isWebhookAccessible('https://accessible.example.com/webhook');

        $this->assertTrue($accessible);
    }

    /** @test */
    public function handles_webhook_accessibility_failure()
    {
        Http::fake([
            'https://not-accessible.example.com/webhook' => Http::response('', 500)
        ]);

        $accessible = $this->generator->isWebhookAccessible('https://not-accessible.example.com/webhook');

        $this->assertFalse($accessible);
    }

    /** @test */
    public function considers_405_method_not_allowed_as_accessible()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('Method Not Allowed', 405)
        ]);

        $accessible = $this->generator->isWebhookAccessible('https://example.com/webhook');

        $this->assertTrue($accessible);
    }

    /** @test */
    public function considers_404_not_found_as_accessible()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('Not Found', 404)
        ]);

        $accessible = $this->generator->isWebhookAccessible('https://example.com/webhook');

        $this->assertTrue($accessible);
    }

    /** @test */
    public function handles_network_timeout_gracefully()
    {
        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $accessible = $this->generator->isWebhookAccessible('https://timeout.example.com/webhook');

        $this->assertFalse($accessible);
    }

    /** @test */
    public function returns_validated_webhook_url_with_metadata()
    {
        Config::set('app.env', 'testing');

        $generator = new WebhookUrlGenerator();
        $result = $generator->getValidatedWebhookUrl();

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('environment', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertEquals('testing', $result['environment']);
        $this->assertEquals('https://test.example.com/payment/unipayment/webhook', $result['url']);
        $this->assertTrue($result['validation']['valid']);
    }

    /** @test */
    public function handles_webhook_url_generation_exception()
    {
        Config::set('app.env', 'production');
        Config::set('app.url', null);

        $generator = new WebhookUrlGenerator();
        $result = $generator->getValidatedWebhookUrl();

        $this->assertNull($result['url']);
        $this->assertFalse($result['validation']['valid']);
        $this->assertNotEmpty($result['validation']['errors']);
    }

    /** @test */
    public function provides_development_recommendations()
    {
        Config::set('app.env', 'development');

        Http::fake([
            'http://127.0.0.1:4040/api/tunnels' => Http::response([], 404)
        ]);

        $generator = new WebhookUrlGenerator();
        $recommendations = $generator->getWebhookRecommendations();

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        $setupRecommendation = collect($recommendations)->firstWhere('type', 'setup');
        $this->assertNotNull($setupRecommendation);
        $this->assertStringContainsString('ngrok', $setupRecommendation['message']);
    }

    /** @test */
    public function provides_production_configuration_recommendations()
    {
        Config::set('app.env', 'production');
        Config::set('app.url', null);

        $generator = new WebhookUrlGenerator();
        $recommendations = $generator->getWebhookRecommendations();

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        $configRecommendation = collect($recommendations)->firstWhere('type', 'configuration');
        $this->assertNotNull($configRecommendation);
        $this->assertStringContainsString('APP_URL', $configRecommendation['message']);
    }

    /** @test */
    public function provides_https_security_recommendations()
    {
        Config::set('app.env', 'production');
        Config::set('app.url', 'http://example.com');

        $generator = new WebhookUrlGenerator();
        $recommendations = $generator->getWebhookRecommendations();

        $this->assertIsArray($recommendations);

        $securityRecommendation = collect($recommendations)->firstWhere('type', 'security');
        $this->assertNotNull($securityRecommendation);
        $this->assertStringContainsString('HTTPS', $securityRecommendation['message']);
    }

    /** @test */
    public function detects_environment_correctly()
    {
        Config::set('app.env', 'testing');
        $this->assertEquals('testing', $this->generator->detectEnvironment());

        Config::set('app.env', 'local');
        $this->assertEquals('development', $this->generator->detectEnvironment());

        Config::set('app.env', 'development');
        $this->assertEquals('development', $this->generator->detectEnvironment());

        Config::set('app.env', 'production');
        $this->assertEquals('production', $this->generator->detectEnvironment());

        Config::set('app.env', 'staging');
        $this->assertEquals('unknown', $this->generator->detectEnvironment());
    }

    /** @test */
    public function logs_warning_for_potentially_inaccessible_development_url()
    {
        Config::set('app.env', 'development');
        Config::set('app.url', 'http://localhost');

        Log::shouldReceive('warning')
            ->once()
            ->with('Development webhook URL may not be accessible externally', \Mockery::type('array'));

        Http::fake([
            'http://127.0.0.1:4040/api/tunnels' => Http::response([], 404)
        ]);

        putenv('TUNNEL_URL'); // Ensure no tunnel URL is set

        $generator = new WebhookUrlGenerator();
        $url = $generator->generateUniPaymentWebhookUrl();

        $this->assertEquals('http://localhost:8000/payment/unipayment/webhook', $url);
    }

    /** @test */
    public function handles_multiple_ngrok_tunnels()
    {
        Config::set('app.env', 'development');

        Http::fake([
            'http://127.0.0.1:4040/api/tunnels' => Http::response([
                'tunnels' => [
                    [
                        'public_url' => 'http://abc123.ngrok.io',
                        'proto' => 'http'
                    ],
                    [
                        'public_url' => 'https://abc123.ngrok.io',
                        'proto' => 'https'
                    ]
                ]
            ])
        ]);

        $generator = new WebhookUrlGenerator();
        $url = $generator->generateUniPaymentWebhookUrl();

        // Should prefer HTTPS tunnel
        $this->assertEquals('https://abc123.ngrok.io/payment/unipayment/webhook', $url);
    }

    /** @test */
    public function validates_webhook_url_with_warnings_for_inaccessible_url()
    {
        Http::fake([
            'https://inaccessible.example.com/webhook' => Http::response('', 500)
        ]);

        $result = $this->generator->validateWebhookUrl('https://inaccessible.example.com/webhook');

        $this->assertTrue($result['valid']);
        $this->assertFalse($result['accessible']);
        $this->assertContains('Webhook URL may not be accessible from external services', $result['warnings']);
    }
}
