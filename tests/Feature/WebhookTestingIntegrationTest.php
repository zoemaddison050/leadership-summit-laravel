<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\UniPaymentSetting;
use App\Services\WebhookTestingService;
use App\Services\WebhookMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebhookTestingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $webhookTestingService;
    private $webhookMonitoringService;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();

        // Create admin role and user
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System Administrator'
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role_id' => $adminRole->id
        ]);

        // Create UniPayment settings
        UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_enabled' => true,
            'webhook_url' => 'https://example.com/webhook',
            'is_enabled' => true
        ]);

        $this->webhookTestingService = app(WebhookTestingService::class);
        $this->webhookMonitoringService = app(WebhookMonitoringService::class);
    }

    /** @test */
    public function admin_can_access_webhook_diagnostics()
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.unipayment.webhook-diagnostics'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'diagnostics' => [
                    'timestamp',
                    'environment',
                    'app_url',
                    'webhook_url',
                    'configuration',
                    'recommendations'
                ]
            ]);
    }

    /** @test */
    public function admin_can_test_webhook_accessibility()
    {
        $response = $this->actingAs($this->admin)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson(route('admin.unipayment.test-webhook-accessibility'), [
                'webhook_url' => 'https://httpbin.org/status/200'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'test_result' => [
                    'url',
                    'accessible',
                    'response_time',
                    'status_code',
                    'tested_at'
                ]
            ]);
    }

    /** @test */
    public function admin_can_test_webhook_with_payload()
    {
        $response = $this->actingAs($this->admin)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson(route('admin.unipayment.test-webhook-payload'), [
                'webhook_url' => 'https://httpbin.org/post'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'test_result' => [
                    'url',
                    'success',
                    'response_time',
                    'status_code',
                    'tested_at',
                    'payload'
                ]
            ]);
    }

    /** @test */
    public function admin_can_get_webhook_metrics()
    {
        // Add some test data to cache
        Cache::put('webhook_counter_total', 10);
        Cache::put('webhook_counter_success', 8);
        Cache::put('webhook_counter_error', 2);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.unipayment.webhook-metrics'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'metrics' => [
                    'period_hours',
                    'total_events',
                    'successful_events',
                    'failed_events',
                    'error_rate'
                ]
            ]);

        $metrics = $response->json('metrics');
        $this->assertEquals(10, $metrics['total_events']);
        $this->assertEquals(8, $metrics['successful_events']);
        $this->assertEquals(2, $metrics['failed_events']);
        $this->assertEquals(20.0, $metrics['error_rate']);
    }

    /** @test */
    public function admin_can_get_webhook_health_status()
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.unipayment.webhook-health'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'health' => [
                    'status',
                    'error_rate',
                    'issues'
                ]
            ]);
    }

    /** @test */
    public function admin_can_reset_webhook_counters()
    {
        // Set some initial values
        Cache::put('webhook_counter_total', 10);
        Cache::put('webhook_counter_success', 8);
        Cache::put('webhook_counter_error', 2);

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson(route('admin.unipayment.reset-webhook-counters'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook monitoring counters reset successfully.'
            ]);

        // Verify counters are reset
        $this->assertNull(Cache::get('webhook_counter_total'));
        $this->assertNull(Cache::get('webhook_counter_success'));
        $this->assertNull(Cache::get('webhook_counter_error'));
    }

    /** @test */
    public function admin_can_clear_webhook_test_cache()
    {
        $response = $this->actingAs($this->admin)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson(route('admin.unipayment.clear-webhook-test-cache'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook test cache cleared successfully.'
            ]);
    }

    /** @test */
    public function webhook_testing_service_can_test_accessibility()
    {
        $result = $this->webhookTestingService->testWebhookAccessibility('https://httpbin.org/status/200');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('accessible', $result);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertArrayHasKey('status_code', $result);
        $this->assertArrayHasKey('tested_at', $result);
    }

    /** @test */
    public function webhook_testing_service_can_test_with_payload()
    {
        $result = $this->webhookTestingService->testWebhookWithPayload('https://httpbin.org/post');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertArrayHasKey('status_code', $result);
        $this->assertArrayHasKey('payload', $result);
    }

    /** @test */
    public function webhook_testing_service_can_run_diagnostics()
    {
        $result = $this->webhookTestingService->runDiagnostics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('environment', $result);
        $this->assertArrayHasKey('app_url', $result);
        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    /** @test */
    public function webhook_monitoring_service_can_log_events()
    {
        $this->webhookMonitoringService->logWebhookEvent('payment.completed', [
            'order_id' => 'test_order_123',
            'amount' => 100.00
        ]);

        // Verify cache counters are updated
        $this->assertEquals(1, Cache::get('webhook_counter_total'));
    }

    /** @test */
    public function webhook_monitoring_service_can_get_metrics()
    {
        // Add some test data
        Cache::put('webhook_counter_total', 5);
        Cache::put('webhook_counter_success', 4);
        Cache::put('webhook_counter_error', 1);

        $metrics = $this->webhookMonitoringService->getWebhookMetrics(24);

        $this->assertIsArray($metrics);
        $this->assertEquals(5, $metrics['total_events']);
        $this->assertEquals(4, $metrics['successful_events']);
        $this->assertEquals(1, $metrics['failed_events']);
        $this->assertEquals(20.0, $metrics['error_rate']);
    }

    /** @test */
    public function non_admin_cannot_access_webhook_testing_endpoints()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('admin.unipayment.webhook-diagnostics'));

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_webhook_testing_endpoints()
    {
        $response = $this->getJson(route('admin.unipayment.webhook-diagnostics'));

        $response->assertStatus(401);
    }
}
