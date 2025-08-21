<?php

namespace Tests\Feature;

use App\Models\UniPaymentSetting;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $settings;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System Administrator'
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@test.com'
        ]);

        // Create UniPayment settings
        $this->settings = UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_secret',
            'webhook_enabled' => true,
            'is_enabled' => true
        ]);
    }

    /** @test */
    public function admin_can_view_webhook_configuration()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.index'));

        $response->assertStatus(200);
        $response->assertSee('Webhook Configuration');
        $response->assertSee('Custom Webhook URL');
        $response->assertSee('Enable Webhooks');
        $response->assertSee('Webhook Status');
    }

    /** @test */
    public function admin_can_update_webhook_settings()
    {
        $webhookData = [
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_secret' => 'updated_secret',
            'webhook_url' => 'https://example.com/webhook',
            'webhook_enabled' => true,
            'is_enabled' => true,
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00,
            'supported_currencies' => 'USD, EUR'
        ];

        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.unipayment.index'))
            ->patch(route('admin.unipayment.update'), $webhookData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('unipayment_settings', [
            'webhook_url' => 'https://example.com/webhook',
            'webhook_enabled' => true,
            'webhook_secret' => 'updated_secret'
        ]);
    }

    /** @test */
    public function admin_can_get_webhook_status()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.webhook-status'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'status' => [
                'enabled',
                'configured',
                'url',
                'last_test',
                'test_status',
                'test_response',
                'last_received',
                'retry_count'
            ]
        ]);
    }

    /** @test */
    public function admin_can_generate_webhook_url()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.generate-webhook-url'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'webhook_url'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['webhook_url']);
        $this->assertStringContainsString('unipayment/webhook', $data['webhook_url']);
    }

    /** @test */
    public function admin_can_validate_webhook_url()
    {
        $validUrl = 'https://example.com/webhook';

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.unipayment.validate-webhook-url'), [
                'webhook_url' => $validUrl
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'valid',
            'accessible',
            'webhook_url'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertTrue($data['valid']);
        $this->assertEquals($validUrl, $data['webhook_url']);
    }

    /** @test */
    public function webhook_url_validation_fails_for_invalid_url()
    {
        $invalidUrl = 'not-a-valid-url';

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.unipayment.validate-webhook-url'), [
                'webhook_url' => $invalidUrl
            ]);

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'success',
            'message',
            'valid'
        ]);

        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertFalse($data['valid']);
    }

    /** @test */
    public function webhook_test_requires_authentication()
    {
        $response = $this->post(route('admin.unipayment.test-webhook'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function webhook_test_requires_admin_role()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)
            ->post(route('admin.unipayment.test-webhook'));

        $response->assertStatus(403);
    }

    /** @test */
    public function model_can_get_webhook_url()
    {
        // Test with custom URL
        $this->settings->update(['webhook_url' => 'https://custom.example.com/webhook']);
        $this->assertEquals('https://custom.example.com/webhook', $this->settings->getWebhookUrl());

        // Test with auto-generated URL
        $this->settings->update(['webhook_url' => null]);
        $webhookUrl = $this->settings->getWebhookUrl();
        $this->assertNotNull($webhookUrl);
        $this->assertStringContainsString('unipayment/webhook', $webhookUrl);
    }

    /** @test */
    public function model_can_check_webhook_configuration()
    {
        // Test enabled and configured
        $this->settings->update([
            'webhook_enabled' => true,
            'webhook_url' => 'https://example.com/webhook'
        ]);
        $this->assertTrue($this->settings->isWebhookConfigured());

        // Test disabled
        $this->settings->update(['webhook_enabled' => false]);
        $this->assertFalse($this->settings->isWebhookConfigured());

        // Test enabled but no URL
        $this->settings->update([
            'webhook_enabled' => true,
            'webhook_url' => null
        ]);
        // Should still be configured because it can generate URL
        $this->assertTrue($this->settings->isWebhookConfigured());
    }

    /** @test */
    public function model_can_update_webhook_test_status()
    {
        $this->settings->updateWebhookTestStatus('success', 'Test response');

        $this->settings->refresh();
        $this->assertEquals('success', $this->settings->webhook_test_status);
        $this->assertEquals('Test response', $this->settings->webhook_test_response);
        $this->assertNotNull($this->settings->last_webhook_test);
    }

    /** @test */
    public function model_can_record_webhook_received()
    {
        $this->settings->update(['webhook_retry_count' => 3]);

        $this->settings->recordWebhookReceived();

        $this->settings->refresh();
        $this->assertNotNull($this->settings->last_webhook_received);
        $this->assertEquals(0, $this->settings->webhook_retry_count);
    }

    /** @test */
    public function model_can_increment_retry_count()
    {
        $this->assertEquals(0, $this->settings->webhook_retry_count);

        $this->settings->incrementWebhookRetryCount();

        $this->settings->refresh();
        $this->assertEquals(1, $this->settings->webhook_retry_count);
    }

    /** @test */
    public function model_can_get_webhook_status()
    {
        $status = $this->settings->getWebhookStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('enabled', $status);
        $this->assertArrayHasKey('configured', $status);
        $this->assertArrayHasKey('url', $status);
        $this->assertArrayHasKey('last_test', $status);
        $this->assertArrayHasKey('test_status', $status);
        $this->assertArrayHasKey('test_response', $status);
        $this->assertArrayHasKey('last_received', $status);
        $this->assertArrayHasKey('retry_count', $status);
    }
}
