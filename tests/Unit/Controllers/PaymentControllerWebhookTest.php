<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\PaymentController;
use App\Services\PaymentService;
use App\Services\WebhookUrlGenerator;
use Tests\TestCase;
use Mockery;

class PaymentControllerWebhookTest extends TestCase
{
    public function test_payment_controller_uses_webhook_url_generator()
    {
        // Mock the services
        $paymentService = Mockery::mock(PaymentService::class);
        $webhookUrlGenerator = Mockery::mock(WebhookUrlGenerator::class);

        // Create controller instance with mocked dependencies
        $controller = new PaymentController($paymentService, $webhookUrlGenerator);

        // Verify that the controller has the webhook URL generator
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('webhookUrlGenerator');
        $property->setAccessible(true);

        $this->assertInstanceOf(WebhookUrlGenerator::class, $property->getValue($controller));
    }

    public function test_webhook_url_generator_is_injected_correctly()
    {
        // Test that the service container can resolve the PaymentController with its dependencies
        $controller = app(PaymentController::class);

        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('webhookUrlGenerator');
        $property->setAccessible(true);

        $this->assertInstanceOf(WebhookUrlGenerator::class, $property->getValue($controller));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
