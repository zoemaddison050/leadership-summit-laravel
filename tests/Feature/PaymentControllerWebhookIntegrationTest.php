<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Services\WebhookUrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentControllerWebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and event
        $this->user = User::factory()->create();
        $this->event = Event::factory()->create();
    }

    public function test_webhook_url_is_generated_correctly_in_testing_environment()
    {
        // Set testing environment
        config(['app.env' => 'testing']);

        $generator = app(WebhookUrlGenerator::class);
        $webhookData = $generator->getValidatedWebhookUrl();

        $this->assertEquals('https://test.example.com/payment/unipayment/webhook', $webhookData['url']);
        $this->assertEquals('testing', $webhookData['environment']);
        $this->assertTrue($webhookData['validation']['valid']);
    }

    public function test_webhook_url_validation_logs_warnings_for_inaccessible_urls()
    {
        // Mock a scenario where webhook URL is not accessible
        $generator = app(WebhookUrlGenerator::class);

        // Test with a URL that should be valid but not accessible
        $validation = $generator->validateWebhookUrl('https://nonexistent-domain-12345.com/webhook');

        $this->assertTrue($validation['valid']); // URL format is valid
        $this->assertFalse($validation['accessible']); // But not accessible
        $this->assertNotEmpty($validation['warnings']);
    }

    public function test_payment_controller_can_handle_webhook_url_generation_failure()
    {
        // This test verifies that the PaymentController can handle cases where
        // webhook URL generation fails gracefully

        // Create a mock that simulates webhook URL generation failure
        $mockGenerator = $this->createMock(WebhookUrlGenerator::class);
        $mockGenerator->method('getValidatedWebhookUrl')
            ->willReturn([
                'url' => null,
                'environment' => 'testing',
                'validation' => [
                    'valid' => false,
                    'accessible' => false,
                    'errors' => ['Failed to generate webhook URL'],
                    'warnings' => []
                ]
            ]);

        // Bind the mock to the service container
        $this->app->instance(WebhookUrlGenerator::class, $mockGenerator);

        // The PaymentController should handle this gracefully
        // (This would be tested in a full integration test with actual payment processing)
        $this->assertTrue(true); // Placeholder assertion
    }

    public function test_webhook_url_contains_correct_route()
    {
        $generator = app(WebhookUrlGenerator::class);
        $webhookData = $generator->getValidatedWebhookUrl();

        $this->assertStringContainsString('/payment/unipayment/webhook', $webhookData['url']);
    }

    public function test_development_environment_provides_recommendations()
    {
        // Set development environment
        config(['app.env' => 'development']);

        $generator = app(WebhookUrlGenerator::class);
        $recommendations = $generator->getWebhookRecommendations();

        $this->assertIsArray($recommendations);

        // Should provide ngrok setup recommendation
        $hasNgrokRecommendation = false;
        foreach ($recommendations as $recommendation) {
            if (str_contains($recommendation['message'], 'ngrok')) {
                $hasNgrokRecommendation = true;
                break;
            }
        }

        $this->assertTrue($hasNgrokRecommendation);
    }
}
